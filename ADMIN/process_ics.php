<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $entity_name = $_POST['entity_name'];
        $fund_cluster = $_POST['fund_cluster'];
        $ics_no = $_POST['ics_no'];
        $received_from = $_POST['received_from'] ?? '';
        $received_from_position = $_POST['received_from_position'] ?? '';
        $received_from_date = $_POST['received_from_date'] ?? null;
        $received_by = $_POST['received_by'] ?? '';
        $received_by_position = $_POST['received_by_position'] ?? '';
        $received_by_date = $_POST['received_by_date'] ?? null;
        $items = $_POST['item_no'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $unit_costs = $_POST['unit_cost'] ?? [];
        $total_costs = $_POST['total_cost'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $useful_lives = $_POST['useful_life'] ?? [];
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Auto-generate ICS number to prevent duplicates
        $generated_ics_no = generateNextTag('ics_no');
        if ($generated_ics_no !== null) {
            $ics_no = $generated_ics_no;
            logSystemAction($_SESSION['user_id'], 'ICS number auto-generated', 'forms', "Generated ICS number: $ics_no");
        } else {
            // Fallback: generate simple ICS number with auto-increment
            $current_year = date('Y');
            $result = $conn->query("SELECT MAX(CAST(SUBSTRING(ics_no, -2, 2) AS UNSIGNED)) as max_series FROM ics_forms WHERE ics_no LIKE '%$current_year%' AND ics_no REGEXP '-[0-9]{2}$'");
            $next_series = '01';
            if ($result && $row = $result->fetch_assoc()) {
                $max_series = $row['max_series'];
                if ($max_series) {
                    $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
                }
            }
            $ics_no = "OMMI-$current_year-I-$next_series";
            logSystemAction($_SESSION['user_id'], 'ICS number generated (fallback)', 'forms', "Generated ICS number: $ics_no");
        }
        
        // Get office ID from entity name
        $office_id = null;
        $office_result = $conn->prepare("SELECT id FROM offices WHERE office_name = ?");
        $office_result->bind_param("s", $entity_name);
        $office_result->execute();
        $office_row = $office_result->get_result();
        if ($office_row && $office_row->num_rows > 0) {
            $office_data = $office_row->fetch_assoc();
            $office_id = $office_data['id'];
        }
        $office_result->close();
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert ICS form
        $stmt = $conn->prepare("INSERT INTO ics_forms (entity_name, fund_cluster, ics_no, received_from, received_from_position, received_from_date, received_by, received_by_position, received_by_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssi", $entity_name, $fund_cluster, $ics_no, $received_from, $received_from_position, $received_from_date, $received_by, $received_by_position, $received_by_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save ICS form: ' . $stmt->error);
        }
        
        $ics_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert ICS items
        $item_stmt = $conn->prepare("INSERT INTO ics_items (form_id, item_no, quantity, unit, unit_cost, total_cost, description, useful_life) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $unit_cost = floatval($unit_costs[$i]);
                $total_cost = floatval($total_costs[$i]);
                $useful_life = intval($useful_lives[$i]);
                
                $item_stmt->bind_param("isiddssi", $ics_form_id, $items[$i], $quantity, $units[$i], $unit_cost, $total_cost, $descriptions[$i], $useful_life);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save ICS item: ' . $item_stmt->error);
                }
                
                // Also insert as asset and asset item
                $asset_stmt = $conn->prepare("INSERT INTO assets (description, unit, quantity, unit_cost, office_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $asset_stmt->bind_param("ssidi", $descriptions[$i], $units[$i], $quantity, $unit_cost, $office_id);
                
                if (!$asset_stmt->execute()) {
                    throw new Exception('Failed to save asset: ' . $asset_stmt->error);
                }
                
                $asset_id = $asset_stmt->insert_id;
                $asset_stmt->close();
                
                // Insert multiple asset items based on quantity
                $asset_item_stmt = $conn->prepare("INSERT INTO asset_items (asset_id, ics_id, description, unit, status, value, acquisition_date, office_id, created_at, last_updated) VALUES (?, ?, ?, ?, 'no_tag', ?, CURDATE(), ?, NOW(), NOW())");
                
                // Parse property numbers - handle both single and multiple property numbers
                $individual_property_numbers = [];
                $base_property_number = null;
                if (!empty($items[$i])) {
                    // Check if it's a textarea with multiple property numbers (newline-separated)
                    if (strpos($items[$i], "\n") !== false) {
                        $individual_property_numbers = array_filter(array_map('trim', explode("\n", $items[$i])));
                    } else {
                        // Single property number - we'll need to generate sequential numbers for each item
                        $base_property_number = $items[$i];
                    }
                }
                
                // Create individual asset items for each quantity
                for ($item_num = 1; $item_num <= $quantity; $item_num++) {
                    // Debug: Log the values being inserted
                    logSystemAction($_SESSION['user_id'], 'ICS Asset Item Insert Debug', 'assets', "Item: {$descriptions[$i]}, Unit: '{$units[$i]}', Unit Cost: {$unit_cost}, Office ID: {$office_id}");
                    
                    $asset_item_stmt->bind_param("iissdi", $asset_id, $ics_form_id, $descriptions[$i], $units[$i], $unit_cost, $office_id);
                    
                    if (!$asset_item_stmt->execute()) {
                        throw new Exception('Failed to save asset item ' . $item_num . ': ' . $asset_item_stmt->error);
                    }
                    
                    // Get the asset_item_id for history logging
                    $asset_item_id = $asset_item_stmt->insert_id;
                    
                    // Log asset item creation in history
                    $ics_details = "Created via ICS form $ics_no - Entity: $entity_name, Item No: {$items[$i]}, Quantity: 1, Unit: {$units[$i]}, Unit Cost: ₱" . number_format($unit_cost, 2);
                    $history_sql = "INSERT INTO asset_item_history (item_id, action, details, created_by, created_at) VALUES (?, 'ICS Created', ?, ?, CURRENT_TIMESTAMP)";
                    $history_stmt = $conn->prepare($history_sql);
                    $history_stmt->bind_param("isi", $asset_item_id, $ics_details, $_SESSION['user_id']);
                    $history_stmt->execute();
                    $history_stmt->close();
                    
                    // Assign property number to this asset item
                    $item_property_number = null;
                    if (!empty($individual_property_numbers)) {
                        // Use pre-generated property numbers from textarea
                        if (isset($individual_property_numbers[$item_num - 1])) {
                            $item_property_number = $individual_property_numbers[$item_num - 1];
                        }
                    } elseif (!empty($base_property_number)) {
                        // Generate sequential property numbers from base
                        // Parse the base property number to extract components
                        if (preg_match('/^(.*-)(\d+)(-[^-]+)$/', $base_property_number, $matches)) {
                            $prefix = $matches[1];
                            $series = intval($matches[2]);
                            $suffix = $matches[3];
                            $item_property_number = $prefix . str_pad($series + $item_num - 1, 4, '0', STR_PAD_LEFT) . $suffix;
                        } else {
                            // Fallback: just append item number
                            $item_property_number = $base_property_number . '-' . str_pad($item_num, 2, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Update the asset item with property number if assigned
                    if (!empty($item_property_number)) {
                        $update_stmt = $conn->prepare("UPDATE asset_items SET property_no = ? WHERE id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("si", $item_property_number, $asset_item_id);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                    }
                }
                $asset_item_stmt->close();
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created ICS form', 'forms', "ICS No: $ics_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "ICS form saved successfully! ICS Number: $ics_no";
        
        // Redirect back to form
        header('Location: ics_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing ICS form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create ICS form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving ICS form: " . $e->getMessage();
        
        // Redirect back to form
        header('Location: ics_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: ics_form.php');
    exit();
}
?>
