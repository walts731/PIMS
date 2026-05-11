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
        $division = $_POST['division'];
        $responsibility_center = $_POST['responsibility_center'];
        $ris_no = $_POST['ris_no'];
        $date = $_POST['date'];
        $office = $_POST['office'];
        $code = $_POST['code'];
        $sai_no = $_POST['sai_no'];
        $date_2 = $_POST['date_2'];
        $purpose = $_POST['purpose'];
        $requested_by = $_POST['requested_by'];
        $requested_by_position = $_POST['requested_by_position'];
        $requested_date = $_POST['requested_date'];
        $approved_by = $_POST['approved_by'];
        $approved_by_position = $_POST['approved_by_position'];
        $approved_date = $_POST['approved_date'];
        $issued_by = $_POST['issued_by'];
        $issued_by_position = $_POST['issued_by_position'];
        $issued_date = $_POST['issued_date'];
        $received_by = $_POST['received_by'];
        $received_by_position = $_POST['received_by_position'];
        $received_date = $_POST['received_date'];
        
        // Get items data
        $stock_numbers = $_POST['stock_no'] ?? [];
        $units = $_POST['unit'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $prices = $_POST['price'] ?? [];
        $total_amounts = $_POST['total_amount'] ?? [];
        
        // Validate required fields - only validate office (if needed)
        if (empty($office)) {
            throw new Exception('Office is required');
        }
        
        // Validate items data
        if (empty($descriptions) || empty($quantities) || empty($units)) {
            throw new Exception('At least one item must be added with description, quantity, and unit');
        }
        
        // Check if at least one item has valid data
        $valid_items = 0;
        for ($i = 0; $i < count($descriptions); $i++) {
            if (!empty($descriptions[$i]) && !empty($quantities[$i]) && !empty($units[$i])) {
                $valid_items++;
            }
        }
        
        if ($valid_items == 0) {
            throw new Exception('At least one complete item must be added');
        }
        
        // Check if we should increment counters
        if (isset($_POST['increment_ris_counter']) && $_POST['increment_ris_counter'] == '1') {
            $generated_ris_no = generateNextTag('ris_no');
            if ($generated_ris_no !== null) {
                $ris_no = $generated_ris_no;
                logSystemAction($_SESSION['user_id'], 'RIS counter incremented', 'forms', "Generated RIS number: $ris_no");
            }
        }
        
        if (isset($_POST['increment_sai_counter']) && $_POST['increment_sai_counter'] == '1') {
            $generated_sai_no = generateNextTag('sai_no');
            if ($generated_sai_no !== null) {
                $sai_no = $generated_sai_no;
                logSystemAction($_SESSION['user_id'], 'SAI counter incremented', 'forms', "Generated SAI number: $sai_no");
            }
        }
        
        if (isset($_POST['increment_code_counter']) && $_POST['increment_code_counter'] == '1') {
            $generated_code = generateNextTag('code');
            if ($generated_code !== null) {
                $code = $generated_code;
                logSystemAction($_SESSION['user_id'], 'Code counter incremented', 'forms', "Generated Code: $code");
            }
        }
        
        // Handle optional date fields
        $requested_date_escaped = !empty($requested_date) ? "'".$conn->real_escape_string($requested_date)."'" : "''";
        $approved_date_escaped = !empty($approved_date) ? "'".$conn->real_escape_string($approved_date)."'" : "''";
        $issued_date_escaped = !empty($issued_date) ? "'".$conn->real_escape_string($issued_date)."'" : "''";
        $received_date_escaped = !empty($received_date) ? "'".$conn->real_escape_string($received_date)."'" : "''";
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert RIS form using traditional SQL
        $ris_no_escaped = !empty($ris_no) ? "'".$conn->real_escape_string($ris_no)."'" : 'NULL';
        $sai_no_escaped = !empty($sai_no) ? "'".$conn->real_escape_string($sai_no)."'" : 'NULL';
        $code_escaped = !empty($code) ? "'".$conn->real_escape_string($code)."'" : 'NULL';
        $division_escaped = $conn->real_escape_string($division);
        $office_escaped = $conn->real_escape_string($office);
        $responsibility_center_escaped = $conn->real_escape_string($responsibility_center);
        $date_escaped = !empty($date) ? "'".$conn->real_escape_string($date)."'" : 'NULL';
        $date_2_escaped = !empty($date_2) ? "'".$conn->real_escape_string($date_2)."'" : 'NULL';
        $purpose_escaped = $conn->real_escape_string($purpose);
        $requested_by_escaped = $conn->real_escape_string($requested_by);
        $requested_by_position_escaped = $conn->real_escape_string($requested_by_position);
        $requested_date_escaped = !empty($requested_date) ? "'".$conn->real_escape_string($requested_date)."'" : 'NULL';
        $approved_by_escaped = $conn->real_escape_string($approved_by);
        $approved_by_position_escaped = $conn->real_escape_string($approved_by_position);
        $approved_date_escaped = !empty($approved_date) ? "'".$conn->real_escape_string($approved_date)."'" : 'NULL';
        $issued_by_escaped = $conn->real_escape_string($issued_by);
        $issued_by_position_escaped = $conn->real_escape_string($issued_by_position);
        $issued_date_escaped = !empty($issued_date) ? "'".$conn->real_escape_string($issued_date)."'" : 'NULL';
        $received_by_escaped = $conn->real_escape_string($received_by);
        $received_by_position_escaped = $conn->real_escape_string($received_by_position);
        $received_date_escaped = !empty($received_date) ? "'".$conn->real_escape_string($received_date)."'" : 'NULL';
        $created_by_escaped = intval($_SESSION['user_id']);
        
        $sql = "INSERT INTO ris_forms (ris_no, sai_no, code, division, office, responsibility_center, date, date_2, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, issued_by, issued_by_position, issued_date, received_by, received_by_position, received_date, created_by) 
                VALUES ($ris_no_escaped, $sai_no_escaped, $code_escaped, '$division_escaped', '$office_escaped', '$responsibility_center_escaped', $date_escaped, $date_2_escaped, '$purpose_escaped', '$requested_by_escaped', '$requested_by_position_escaped', $requested_date_escaped, '$approved_by_escaped', '$approved_by_position_escaped', $approved_date_escaped, '$issued_by_escaped', '$issued_by_position_escaped', $issued_date_escaped, '$received_by_escaped', '$received_by_position_escaped', $received_date_escaped, $created_by_escaped)";
        
        error_log("RIS SQL: " . $sql);
        
        if (!$conn->query($sql)) {
            throw new Exception('Failed to save RIS form: ' . $conn->error);
        }
        
        $ris_form_id = $conn->insert_id;
        
        // Validate that we got a valid insert ID
        if ($ris_form_id <= 0) {
            throw new Exception('Failed to get RIS form insert ID');
        }
        
        error_log("RIS Form ID: " . $ris_form_id);
        
        // Insert RIS items
        $item_stmt = $conn->prepare("INSERT INTO ris_items (ris_form_id, stock_no, unit, description, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Prepare statement for consumables
        $consumable_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, units, unit_cost, office_id, for_office_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        // Prepare statement for consumable history
        $history_stmt = $conn->prepare("INSERT INTO consumable_add_history (consumable_id, description, supplier, quantity_added, units, unit_cost, total_value, office_id, to_office_id, added_by, add_date, source, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        
        $total_form_amount = 0;
        for ($i = 0; $i < count($stock_numbers); $i++) {
            if (!empty($descriptions[$i])) {
                $stock_no = intval($stock_numbers[$i]);
                $quantity = floatval($quantities[$i]);
                $price = floatval($prices[$i]);
                $total_amount = floatval($total_amounts[$i]);
                $current_unit = $units[$i] ?? '';
                
                $total_form_amount += $total_amount;
                
                // Insert RIS item
                $item_stmt->bind_param("iissddd", $ris_form_id, $stock_no, $current_unit, $descriptions[$i], $quantity, $price, $total_amount);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save RIS item: ' . $item_stmt->error);
                }
                
                // Insert into consumables table
                // Always use Supply Office (ID = 3) for actual storage
                $supply_office_id = 163;
                
                // Get the original requesting office ID
                $requesting_office_query = $conn->prepare("SELECT id FROM offices WHERE office_name = ? LIMIT 1");
                $requesting_office_query->bind_param("s", $office);
                $requesting_office_query->execute();
                $requesting_office_result = $requesting_office_query->get_result();
                $requesting_office_id = 1; // Default if not found
                
                if ($requesting_office_row = $requesting_office_result->fetch_assoc()) {
                    $requesting_office_id = $requesting_office_row['id'];
                }
                $requesting_office_query->close();
                
                // Check if consumable already exists (to avoid duplicates)
                $check_consumable = $conn->prepare("SELECT id FROM consumables WHERE description = ? AND office_id = ? AND for_office_id = ? LIMIT 1");
                $check_consumable->bind_param("sii", $descriptions[$i], $supply_office_id, $requesting_office_id);
                $check_consumable->execute();
                $check_result = $check_consumable->get_result();
                
                $target_consumable_id = 0;
                
                if ($check_result->num_rows == 0) {
                    // Insert new consumable if it doesn't exist
                    $consumable_stmt->bind_param("sdsdii", $descriptions[$i], $quantity, $current_unit, $price, $supply_office_id, $requesting_office_id);
                    
                    if (!$consumable_stmt->execute()) {
                        throw new Exception('Failed to save consumable: ' . $consumable_stmt->error);
                    }
                    
                    // Get the ID of the newly inserted consumable
                    $new_consumable_id = $conn->insert_id;
                    
                    // Validate that we got a valid insert ID
                    if ($new_consumable_id <= 0) {
                        throw new Exception('Failed to get consumable insert ID');
                    }
                    $target_consumable_id = $new_consumable_id;
                    // No need to update for_office_id since it's already included in the INSERT
                } else {
                    $row = $check_result->fetch_assoc();
                    $target_consumable_id = $row['id'];
                    // Update existing consumable quantity and units
                    $update_consumable = $conn->prepare("UPDATE consumables SET quantity = quantity + ?, units = ?, unit_cost = ?, updated_at = NOW() WHERE description = ? AND office_id = ? AND for_office_id = ?");
                    $update_consumable->bind_param("dsdsii", $quantity, $current_unit, $price, $descriptions[$i], $supply_office_id, $requesting_office_id);
                    $update_consumable->execute();
                    $update_consumable->close();
                }
                
                // Track consumable history
                $supplier_none = 'N/A';
                $added_by_user = intval($_SESSION['user_id']);
                $source_val = 'ris_form';
                $history_notes = "Added via RIS form #" . ($ris_no ?: 'Unknown');
                $qty_int = intval($quantity);
                
                $history_stmt->bind_param("issisddiiiss", 
                    $target_consumable_id, $descriptions[$i], $supplier_none, $qty_int, $current_unit, 
                    $price, $total_amount, $supply_office_id, $requesting_office_id, $added_by_user, $source_val, $history_notes);
                $history_stmt->execute();
                
                $check_consumable->close();
            }
        }
        $item_stmt->close();
        $consumable_stmt->close();
        $history_stmt->close();
        
        // Update total amount in the form
        $update_stmt = $conn->prepare("UPDATE ris_forms SET total_amount = ? WHERE id = ?");
        $update_stmt->bind_param("di", $total_form_amount, $ris_form_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created RIS form', 'forms', "RIS No: $ris_no, Division: $division, Office: $office, Items added to Supply Office");
        
        // Set success message
        $_SESSION['success_message'] = "RIS form saved successfully! RIS Number: $ris_no, Total Amount: " . number_format($total_form_amount, 2) . " (Items added to Supply Office)";
        
        // Redirect back to form
        header('Location: ris_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->ping()) {
            $conn->rollback();
        }
        
        // Log error
        error_log("Error processing RIS form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create RIS form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving RIS form: " . $e->getMessage();
        
        // Redirect back to form
        header('Location: ris_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: ris_form.php');
    exit();
}
?>
