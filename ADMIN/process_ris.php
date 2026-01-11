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
        
        // Validate required fields
        if (empty($division) || empty($responsibility_center) || empty($ris_no) || empty($date) || 
            empty($office) || empty($code) || empty($sai_no) || empty($date_2) || empty($purpose)) {
            throw new Exception('All required fields must be filled');
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
        $ris_no_escaped = $conn->real_escape_string($ris_no);
        $sai_no_escaped = $conn->real_escape_string($sai_no);
        $code_escaped = $conn->real_escape_string($code);
        $division_escaped = $conn->real_escape_string($division);
        $office_escaped = $conn->real_escape_string($office);
        $responsibility_center_escaped = $conn->real_escape_string($responsibility_center);
        $date_escaped = $conn->real_escape_string($date);
        $date_2_escaped = $conn->real_escape_string($date_2);
        $purpose_escaped = $conn->real_escape_string($purpose);
        $requested_by_escaped = $conn->real_escape_string($requested_by);
        $requested_by_position_escaped = $conn->real_escape_string($requested_by_position);
        $requested_date_escaped = $requested_date ? "'".$conn->real_escape_string($requested_date)."'" : 'NULL';
        $approved_by_escaped = $conn->real_escape_string($approved_by);
        $approved_by_position_escaped = $conn->real_escape_string($approved_by_position);
        $approved_date_escaped = $approved_date ? "'".$conn->real_escape_string($approved_date)."'" : 'NULL';
        $issued_by_escaped = $conn->real_escape_string($issued_by);
        $issued_by_position_escaped = $conn->real_escape_string($issued_by_position);
        $issued_date_escaped = $issued_date ? "'".$conn->real_escape_string($issued_date)."'" : 'NULL';
        $received_by_escaped = $conn->real_escape_string($received_by);
        $received_by_position_escaped = $conn->real_escape_string($received_by_position);
        $received_date_escaped = $received_date ? "'".$conn->real_escape_string($received_date)."'" : 'NULL';
        $created_by_escaped = intval($_SESSION['user_id']);
        
        $sql = "INSERT INTO ris_forms (ris_no, sai_no, code, division, office, responsibility_center, date, date_2, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, issued_by, issued_by_position, issued_date, received_by, received_by_position, received_date, created_by) 
                VALUES ('$ris_no_escaped', '$sai_no_escaped', '$code_escaped', '$division_escaped', '$office_escaped', '$responsibility_center_escaped', '$date_escaped', '$date_2_escaped', '$purpose_escaped', '$requested_by_escaped', '$requested_by_position_escaped', '$requested_date_escaped', '$approved_by_escaped', '$approved_by_position_escaped', '$approved_date_escaped', '$issued_by_escaped', '$issued_by_position_escaped', '$issued_date_escaped', '$received_by_escaped', '$received_by_position_escaped', '$received_date_escaped', $created_by_escaped)";
        
        error_log("RIS SQL: " . $sql);
        
        if (!$conn->query($sql)) {
            throw new Exception('Failed to save RIS form: ' . $conn->error);
        }
        
        $ris_form_id = $conn->insert_id;
        error_log("RIS Form ID: " . $ris_form_id);
        
        // Insert RIS items
        $item_stmt = $conn->prepare("INSERT INTO ris_items (ris_form_id, stock_no, unit, description, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Prepare statement for consumables
        $consumable_stmt = $conn->prepare("INSERT INTO consumables (description, quantity, unit_cost, office_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        
        $total_form_amount = 0;
        for ($i = 0; $i < count($stock_numbers); $i++) {
            if (!empty($descriptions[$i])) {
                $stock_no = intval($stock_numbers[$i]);
                $quantity = floatval($quantities[$i]);
                $price = floatval($prices[$i]);
                $total_amount = floatval($total_amounts[$i]);
                
                $total_form_amount += $total_amount;
                
                // Insert RIS item
                $item_stmt->bind_param("iissddd", $ris_form_id, $stock_no, $units[$i], $descriptions[$i], $quantity, $price, $total_amount);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save RIS item: ' . $item_stmt->error);
                }
                
                // Insert into consumables table
                // Get office_id from offices table using office name
                $office_query = $conn->prepare("SELECT id FROM offices WHERE office_name = ? LIMIT 1");
                $office_query->bind_param("s", $office);
                $office_query->execute();
                $office_result = $office_query->get_result();
                $office_id = 1; // Default office_id if not found
                
                if ($office_row = $office_result->fetch_assoc()) {
                    $office_id = $office_row['id'];
                }
                $office_query->close();
                
                // Check if consumable already exists (to avoid duplicates)
                $check_consumable = $conn->prepare("SELECT id FROM consumables WHERE description = ? AND office_id = ? LIMIT 1");
                $check_consumable->bind_param("si", $descriptions[$i], $office_id);
                $check_consumable->execute();
                $check_result = $check_consumable->get_result();
                
                if ($check_result->num_rows == 0) {
                    // Insert new consumable if it doesn't exist
                    $consumable_stmt->bind_param("sdii", $descriptions[$i], $quantity, $price, $office_id);
                    
                    if (!$consumable_stmt->execute()) {
                        throw new Exception('Failed to save consumable: ' . $consumable_stmt->error);
                    }
                } else {
                    // Update existing consumable quantity
                    $update_consumable = $conn->prepare("UPDATE consumables SET quantity = quantity + ?, unit_cost = ?, updated_at = NOW() WHERE description = ? AND office_id = ?");
                    $update_consumable->bind_param("disi", $quantity, $price, $descriptions[$i], $office_id);
                    $update_consumable->execute();
                    $update_consumable->close();
                }
                
                $check_consumable->close();
            }
        }
        $item_stmt->close();
        $consumable_stmt->close();
        
        // Update total amount in the form
        $update_stmt = $conn->prepare("UPDATE ris_forms SET total_amount = ? WHERE id = ?");
        $update_stmt->bind_param("di", $total_form_amount, $ris_form_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created RIS form', 'forms', "RIS No: $ris_no, Division: $division, Office: $office");
        
        // Set success message
        $_SESSION['success_message'] = "RIS form saved successfully! RIS Number: $ris_no, Total Amount: " . number_format($total_form_amount, 2);
        
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
