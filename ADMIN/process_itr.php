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
        $itr_no = $_POST['itr_no'];
        $from_office = $_POST['from_office'];
        $to_office = $_POST['to_office'];
        $transfer_date = $_POST['transfer_date'];
        $transfer_type = $_POST['transfer_type'];
        $transfer_type_others = $_POST['transfer_type_others'] ?? '';
        $end_user = $_POST['end_user'] ?? '';
        $purpose = $_POST['purpose'];
        $approved_by = $_POST['approved_by'];
        $approved_by_position = $_POST['approved_by_position'];
        $approved_date = $_POST['approved_date'];
        $released_by = $_POST['released_by'];
        $released_by_position = $_POST['released_by_position'];
        $released_date = $_POST['released_date'];
        $received_by = $_POST['received_by'];
        $received_by_position = $_POST['received_by_position'];
        $received_date = $_POST['received_date'];
        $items = $_POST['item_no'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $date_acquireds = $_POST['date_acquired'] ?? [];
        $ics_par_nos = $_POST['ics_par_no'] ?? [];
        $conditions = $_POST['condition'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $total_amounts = $_POST['total_amount'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        
        // Debug: Log the form data we received
        logSystemAction($_SESSION['user_id'], 'ITR Form Data Received', 'forms', "From Office: {$from_office}, To Office: {$to_office}, Items: " . json_encode($descriptions));
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster) || empty($itr_no) || empty($from_office) || empty($to_office)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if we should increment ITR counter
        if (isset($_POST['increment_itr_counter']) && $_POST['increment_itr_counter'] == '1') {
            // Generate actual ITR number (this increments the counter)
            $generated_itr_no = generateNextTag('itr_no');
            if ($generated_itr_no !== null) {
                $itr_no = $generated_itr_no;
                logSystemAction($_SESSION['user_id'], 'ITR counter incremented', 'forms', "Generated ITR number: $itr_no");
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert ITR form
        $stmt = $conn->prepare("INSERT INTO itr_forms (entity_name, fund_cluster, itr_no, from_office, to_office, transfer_date, transfer_type, transfer_type_others, end_user, purpose, approved_by, approved_by_position, approved_date, released_by, released_by_position, released_date, received_by, received_by_position, received_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssssssi", $entity_name, $fund_cluster, $itr_no, $from_office, $to_office, $transfer_date, $transfer_type, $transfer_type_others, $end_user, $purpose, $approved_by, $approved_by_position, $approved_date, $released_by, $released_by_position, $released_date, $received_by, $received_by_position, $received_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save ITR form: ' . $stmt->error);
        }
        
        $itr_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Update asset_items table - transfer ownership to "To" employee
        // Use asset_id for precise updates instead of description
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                // The descriptions[] array actually contains asset_id values from the dropdown
                $asset_id = mysqli_real_escape_string($conn, $descriptions[$i]);
                $to_office_safe = mysqli_real_escape_string($conn, $to_office);
                
                // Debug: Log what we're trying to update
                logSystemAction($_SESSION['user_id'], 'ITR Asset Transfer Debug', 'assets', "Attempting to transfer: Asset ID={$asset_id}, To Employee ID={$to_office}");
                
                // First, let's check if this asset exists
                $check_sql = "SELECT id, description, employee_id FROM asset_items WHERE id = '$asset_id'";
                logSystemAction($_SESSION['user_id'], 'ITR Asset Check SQL', 'assets', "Check SQL: $check_sql");
                
                $check_result = mysqli_query($conn, $check_sql);
                $asset_info = null;
                
                if ($check_result) {
                    $asset_info = mysqli_fetch_assoc($check_result);
                }
                
                // Debug: Log what we found
                if ($asset_info) {
                    logSystemAction($_SESSION['user_id'], 'ITR Asset Check Results', 'assets', "Found asset: ID={$asset_info['id']}, Description='{$asset_info['description']}', Current Employee ID={$asset_info['employee_id']}");
                } else {
                    logSystemAction($_SESSION['user_id'], 'ITR Asset Transfer - Asset not found', 'assets', "No asset found with ID={$asset_id}");
                    continue; // Skip to next item
                }
                
                // Update the specific asset item to the "To" employee and set end_user
                $end_user_safe = mysqli_real_escape_string($conn, $end_user);
                $update_sql = "UPDATE asset_items SET employee_id = '$to_office_safe', end_user = '$end_user_safe' WHERE id = '$asset_id'";
                logSystemAction($_SESSION['user_id'], 'ITR Asset Update SQL', 'assets', "Update SQL: $update_sql");
                
                $update_result = mysqli_query($conn, $update_sql);
                
                if (!$update_result) {
                    throw new Exception('Failed to update asset item ownership: ' . mysqli_error($conn));
                }
                
                // Log the transfer
                $affected_rows = mysqli_affected_rows($conn);
                if ($affected_rows > 0) {
                    logSystemAction($_SESSION['user_id'], 'Asset item transferred', 'assets', "Asset ID: {$asset_id}, Description: {$asset_info['description']}, From Employee ID: {$asset_info['employee_id']}, To Employee ID: {$to_office}, End User: {$end_user}, ITR: {$itr_no}, Rows affected: {$affected_rows}");
                    
                    // Add entry to asset_item_history table
                    $from_employee_name = 'Unknown';
                    $to_employee_name = 'Unknown';
                    
                    // Get employee names for history
                    $from_emp_sql = "SELECT firstname, lastname FROM employees WHERE id = ?";
                    $from_emp_stmt = $conn->prepare($from_emp_sql);
                    $from_emp_stmt->bind_param("i", $asset_info['employee_id']);
                    $from_emp_stmt->execute();
                    $from_emp_result = $from_emp_stmt->get_result();
                    if ($from_emp_row = $from_emp_result->fetch_assoc()) {
                        $from_employee_name = $from_emp_row['firstname'] . ' ' . $from_emp_row['lastname'];
                    }
                    $from_emp_stmt->close();
                    
                    $to_emp_sql = "SELECT firstname, lastname FROM employees WHERE id = ?";
                    $to_emp_stmt = $conn->prepare($to_emp_sql);
                    $to_emp_stmt->bind_param("i", $to_office);
                    $to_emp_stmt->execute();
                    $to_emp_result = $to_emp_stmt->get_result();
                    if ($to_emp_row = $to_emp_result->fetch_assoc()) {
                        $to_employee_name = $to_emp_row['firstname'] . ' ' . $to_emp_row['lastname'];
                    }
                    $to_emp_stmt->close();
                    
                    // Create history entry
                    $itr_details = "Transferred via ITR form {$itr_no} - From: {$from_employee_name}, To: {$to_employee_name}, Transfer Type: {$transfer_type}";
                    if (!empty($end_user)) {
                        $itr_details .= ", End User: {$end_user}";
                    }
                    if (!empty($purpose)) {
                        $itr_details .= ", Purpose: {$purpose}";
                    }
                    
                    $history_sql = "INSERT INTO asset_item_history (item_id, action, details, old_value, new_value, created_by, created_at) VALUES (?, 'ITR Transfer', ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                    $history_stmt = $conn->prepare($history_sql);
                    $old_value = "Employee ID: {$asset_info['employee_id']} ({$from_employee_name})";
                    $new_value = "Employee ID: {$to_office} ({$to_employee_name})";
                    $history_stmt->bind_param("isssi", $asset_id, $itr_details, $old_value, $new_value, $_SESSION['user_id']);
                    $history_stmt->execute();
                    $history_stmt->close();
                } else {
                    // Log if no items were updated for debugging
                    logSystemAction($_SESSION['user_id'], 'Asset item transfer - no items updated', 'assets', "Asset ID: {$asset_id}, To Employee ID: {$to_office}, End User: {$end_user}, ITR: {$itr_no}");
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created ITR form', 'forms', "ITR No: $itr_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "ITR form saved successfully! ITR Number: $itr_no";
        
        // Redirect back to form
        header('Location: itr_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing ITR form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create ITR form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving ITR form: " . $e->getMessage();
        
        // Redirect back to form
        header('Location: itr_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: itr_form.php');
    exit();
}
?>
