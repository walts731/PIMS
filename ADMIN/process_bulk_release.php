<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_office_id = intval($_POST['target_office_id'] ?? 0);
    $selected_items = $_POST['selected_items'] ?? [];
    $release_quantities = $_POST['release_quantities'] ?? [];
    $received_by = trim($_POST['received_by'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $release_type = trim($_POST['release_type'] ?? 'with_deduction');
    
    if ($target_office_id <= 0 || empty($selected_items) || empty($received_by)) {
        header("Location: bulk_release_form.php?office_id=$target_office_id&message=" . urlencode("Missing required fields.") . "&type=danger");
        exit();
    }

    try {
        $conn->begin_transaction();
        
        // Get target office name for logging
        $office_stmt = $conn->prepare("SELECT office_name FROM offices WHERE id = ?");
        $office_stmt->bind_param("i", $target_office_id);
        $office_stmt->execute();
        $office_result = $office_stmt->get_result();
        $office_data = $office_result->fetch_assoc();
        $office_stmt->close();
        
        $released_count = 0;
        
        foreach ($selected_items as $source_consumable_id) {
            $source_consumable_id = intval($source_consumable_id);
            $release_quantity = intval($release_quantities[$source_consumable_id] ?? 0);
            
            if ($release_quantity <= 0) continue;
            
            // Get source consumable data
            $source_stmt = $conn->prepare("SELECT * FROM consumables WHERE id = ? FOR UPDATE");
            $source_stmt->bind_param("i", $source_consumable_id);
            $source_stmt->execute();
            $source_result = $source_stmt->get_result();
            if ($source_result->num_rows === 0) continue;
            
            $source_data = $source_result->fetch_assoc();
            $source_quantity = $source_data['quantity'];
            
            if ($release_quantity > $source_quantity) {
                throw new Exception("Cannot release {$release_quantity} units of {$source_data['description']}. Only {$source_quantity} available.");
            }

            // Check Balance Record for target office
            $balance_check_sql = "SELECT id, consumable_id, consumable_description, for_office_id, current_balance 
                                  FROM consumable_balance 
                                  WHERE for_office_id = {$target_office_id} AND consumable_description = '" . $conn->real_escape_string($source_data['description']) . "' 
                                  FOR UPDATE";
            $balance_result = $conn->query($balance_check_sql);
            
            $borrowed_deducted = 0;
            if ($balance_result && $balance_result->num_rows > 0) {
                $balance_data = $balance_result->fetch_assoc();
                if ($release_type === 'with_deduction') {
                    // Process balance deduction
                    $delete_stmt = $conn->prepare("DELETE FROM consumable_balance WHERE id = ?");
                    $delete_stmt->bind_param("i", $balance_data['id']);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    $delete_lend_stmt = $conn->prepare("DELETE FROM lend_consumables WHERE consumable_id = ? AND to_office_id = ?");
                    $delete_lend_stmt->bind_param("ii", $balance_data['consumable_id'], $balance_data['for_office_id']);
                    $delete_lend_stmt->execute();
                    $delete_lend_stmt->close();
                    
                    $borrowed_deducted = $balance_data['current_balance'];
                }
            }

            // Release to Target Office
            $actual_release_quantity = $release_quantity - $borrowed_deducted;
            if ($actual_release_quantity > 0) {
                // Check if target office already has this consumable
                $target_stmt = $conn->prepare("SELECT id, quantity FROM consumables WHERE description = ? AND office_id = ? FOR UPDATE");
                $target_stmt->bind_param("si", $source_data['description'], $target_office_id);
                $target_stmt->execute();
                $target_result = $target_stmt->get_result();

                if ($target_result->num_rows > 0) {
                    $target_data = $target_result->fetch_assoc();
                    $new_target_quantity = $target_data['quantity'] + $actual_release_quantity;
                    $update_target = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
                    $update_target->bind_param("ii", $new_target_quantity, $target_data['id']);
                    $update_target->execute();
                    $update_target->close();
                } else {
                    $insert_target = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, reorder_level, office_id) VALUES (?, ?, ?, ?, ?)");
                    $insert_target->bind_param("sidii", $source_data['description'], $actual_release_quantity, $source_data['unit_cost'], $source_data['reorder_level'], $target_office_id);
                    $insert_target->execute();
                    $insert_target->close();
                }
                $target_stmt->close();
            }

            // Deduct from Source
            $new_source_quantity = $source_quantity - $release_quantity;
            $update_source_stmt = $conn->prepare("UPDATE consumables SET quantity = ? WHERE id = ?");
            $update_source_stmt->bind_param("ii", $new_source_quantity, $source_consumable_id);
            $update_source_stmt->execute();
            $update_source_stmt->close();
            
            // Track in consumable_release_history
            $history_desc = $source_data['description'];
            $history_qty = $release_quantity;
            $history_cost = $source_data['unit_cost'];
            $history_total = $history_qty * $history_cost;
            $history_from = $source_data['office_id'];
            $history_to = $target_office_id;
            $history_released_by = intval($_SESSION['user_id']);
            $history_option = ($release_type === 'with_deduction') ? 'deduct' : 'release';
            
            $history_stmt = $conn->prepare("INSERT INTO consumable_release_history 
                (consumable_id, description, quantity_released, unit_cost, total_value, from_office_id, to_office_id, released_by, received_by, release_option, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->bind_param("isdddiissss", 
                $source_consumable_id, $history_desc, $history_qty, $history_cost, $history_total, 
                $history_from, $history_to, $history_released_by, $received_by, $history_option, $remarks
            );
            $history_stmt->execute();
            $history_stmt->close();
            
            // Log for this item
            $log_remarks = "Bulk Released {$release_quantity} '{$source_data['description']}' to {$office_data['office_name']}. Type: {$release_type}. Returned {$borrowed_deducted} to supply. Remarks: " . ($remarks ?: 'None');
            logSystemAction($_SESSION['user_id'], 'consumable_bulk_released', 'consumable_management', $log_remarks);
            
            $released_count++;
        }
        
        $conn->commit();
        $message = "Successfully bulk released {$released_count} items to {$office_data['office_name']}.";
        header("Location: consumables.php?message=" . urlencode($message) . "&type=success");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error in bulk release: " . $e->getMessage();
        header("Location: bulk_release_form.php?office_id=$target_office_id&message=" . urlencode($message) . "&type=danger");
        exit();
    }
}
?>
