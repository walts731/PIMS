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
        $purpose = $_POST['purpose'];
        $requested_by = $_POST['requested_by'];
        $requested_by_position = $_POST['requested_by_position'];
        $requested_date = $_POST['requested_date'];
        $approved_by = $_POST['approved_by'];
        $approved_by_position = $_POST['approved_by_position'];
        $approved_date = $_POST['approved_date'];
        $items = $_POST['item_no'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $total_amounts = $_POST['total_amount'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        
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
        $stmt = $conn->prepare("INSERT INTO itr_forms (entity_name, fund_cluster, itr_no, from_office, to_office, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssi", $entity_name, $fund_cluster, $itr_no, $from_office, $to_office, $purpose, $requested_by, $requested_by_position, $requested_date, $approved_by, $approved_by_position, $approved_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save ITR form: ' . $stmt->error);
        }
        
        $itr_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert ITR items
        $item_stmt = $conn->prepare("INSERT INTO itr_items (form_id, item_no, description, quantity, unit, unit_price, total_amount, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $unit_price = floatval($unit_prices[$i]);
                $total_amount = floatval($total_amounts[$i]);
                
                $item_stmt->bind_param("issdddds", $itr_form_id, $items[$i], $descriptions[$i], $quantity, $units[$i], $unit_price, $total_amount, $remarks[$i]);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save ITR item: ' . $item_stmt->error);
                }
            }
        }
        $item_stmt->close();
        
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
