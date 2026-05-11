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
        
        // Fetch employee names for detailed notifications
        $from_employee_name = 'Unknown';
        $to_employee_name = 'Unknown';
        
        $emp_stmt = $conn->prepare("SELECT firstname, lastname FROM employees WHERE id = ?");
        if ($emp_stmt) {
            $emp_stmt->bind_param("i", $from_office);
            $emp_stmt->execute();
            $res = $emp_stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $from_employee_name = $row['firstname'] . ' ' . $row['lastname'];
            }
            
            $emp_stmt->bind_param("i", $to_office);
            $emp_stmt->execute();
            $res = $emp_stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $to_employee_name = $row['firstname'] . ' ' . $row['lastname'];
            }
            $emp_stmt->close();
        }
        
        // Convert date format from MM/DD/YYYY to YYYY-MM-DD for database
        if (!empty($_POST['transfer_date'])) {
            $date_parts = explode('/', $_POST['transfer_date']);
            if (count($date_parts) === 3) {
                $transfer_date = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
            } else {
                $transfer_date = $_POST['transfer_date']; // Fallback if format is unexpected
            }
        } else {
            $transfer_date = null;
        }
        
        $transfer_type = $_POST['transfer_type'];
        $transfer_type_others = $_POST['transfer_type_others'] ?? '';
        $end_user = $_POST['end_user'] ?? '';
        $purpose = $_POST['purpose'];
        $requested_by = $_SESSION['username']; // Use current logged-in user
        $requested_by_position = $_SESSION['position'] ?? 'User'; // Use current user's position
        $requested_date = date('Y-m-d'); // Current date
        $approved_by = $_POST['approved_by'];
        $approved_by_position = $_POST['approved_by_position'];
        
        // Convert approved_date format from MM/DD/YYYY to YYYY-MM-DD for database
        if (!empty($_POST['approved_date'])) {
            $date_parts = explode('/', $_POST['approved_date']);
            if (count($date_parts) === 3) {
                $approved_date = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
            } else {
                $approved_date = $_POST['approved_date']; // Fallback if format is unexpected
            }
        } else {
            $approved_date = null;
        }
        
        $released_by = $_POST['released_by'];
        $released_by_position = $_POST['released_by_position'];
        
        // Convert released_date format from MM/DD/YYYY to YYYY-MM-DD for database
        if (!empty($_POST['released_date'])) {
            $date_parts = explode('/', $_POST['released_date']);
            if (count($date_parts) === 3) {
                $released_date = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
            } else {
                $released_date = $_POST['released_date']; // Fallback if format is unexpected
            }
        } else {
            $released_date = null;
        }
        
        $received_by = $_POST['received_by'];
        $received_by_position = $_POST['received_by_position'];
        
        // Convert received_date format from MM/DD/YYYY to YYYY-MM-DD for database
        if (!empty($_POST['received_date'])) {
            $date_parts = explode('/', $_POST['received_date']);
            if (count($date_parts) === 3) {
                $received_date = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
            } else {
                $received_date = $_POST['received_date']; // Fallback if format is unexpected
            }
        } else {
            $received_date = null;
        }
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
            // Allow form submission without all fields validation
            // throw new Exception('All required fields must be filled');
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
        $stmt = $conn->prepare("INSERT INTO itr_forms (entity_name, fund_cluster, itr_no, from_office, to_office, transfer_date, transfer_type, transfer_type_others, end_user, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, released_by, released_by_position, released_date, received_by, received_by_position, received_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssssssssssssssi", $entity_name, $fund_cluster, $itr_no, $from_office, $to_office, $transfer_date, $transfer_type, $transfer_type_others, $end_user, $purpose, $requested_by, $requested_by_position, $requested_date, $approved_by, $approved_by_position, $approved_date, $released_by, $released_by_position, $released_date, $received_by, $received_by_position, $received_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save ITR form: ' . $stmt->error);
        }
        
        $itr_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert ITR items
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                // Convert date format for date_acquired
                $date_acquired_db = null;
                if (!empty($date_acquireds[$i])) {
                    $date_parts = explode('/', $date_acquireds[$i]);
                    if (count($date_parts) === 3) {
                        $date_acquired_db = $date_parts[2] . '-' . $date_parts[0] . '-' . $date_parts[1];
                    } else {
                        $date_acquired_db = $date_acquireds[$i];
                    }
                }
                
                $item_no = mysqli_real_escape_string($conn, $items[$i]);
                $description = mysqli_real_escape_string($conn, $descriptions[$i]);
                $ics_par_no = mysqli_real_escape_string($conn, $ics_par_nos[$i] ?? '');
                $quantity = floatval($quantities[$i] ?? 1);
                $unit = 'pcs'; // Default unit
                $unit_price = floatval($unit_prices[$i] ?? 0);
                $total_amount = floatval($total_amounts[$i] ?? 0);
                $condition = mysqli_real_escape_string($conn, $conditions[$i] ?? 'serviceable');
                $remarks = mysqli_real_escape_string($conn, $remarks[$i] ?? '');
                
                $stmt = $conn->prepare("INSERT INTO itr_items (form_id, item_no, date_acquired, ics_par_no, description, quantity, unit, unit_price, total_amount, condition_of_inventory, remarks, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->bind_param("iissssdssss", $itr_form_id, $item_no, $date_acquired_db, $ics_par_no, $description, $quantity, $unit, $unit_price, $total_amount, $condition, $remarks);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to save ITR item: ' . $stmt->error);
                }
                $stmt->close();
            }
        }
        
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
        $_SESSION['success_message'] = "ITR form saved successfully! Asset(s) transferred from $from_employee_name to $to_employee_name. ITR Number: $itr_no";
        
        // Create notifications for MAIN_USER
        createMainUserNotificationsForITR($itr_no, $itr_form_id, $from_employee_name, $to_employee_name);
        
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

// Function to create notifications for MAIN_USER when ITR forms are created
function createMainUserNotificationsForITR($itr_no, $itr_form_id, $from_employee_name, $to_employee_name) {
    global $conn;
    
    // Get all MAIN_USER users
    $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
    $main_users_result = $conn->query($main_users_query);
    
    if ($main_users_result && $main_users_result->num_rows > 0) {
        while ($main_user = $main_users_result->fetch_assoc()) {
            $user_id = $main_user['id'];
            
            $title = "New ITR Form Submitted";
            $message = "ITR Form '{$itr_no}' has been successfully submitted. Asset(s) transferred from {$from_employee_name} to {$to_employee_name}.";
            $type = "success";
            $related_id = $itr_form_id;
            $related_type = "itr_form";
            
            // Insert notification
            $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
            $stmt->execute();
        }
    }
}
?>
