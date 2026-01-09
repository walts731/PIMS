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
        $ris_no = $_POST['ris_no'];
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
        $stock_numbers = $_POST['stock_no'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $remarks = $_POST['remarks'] ?? [];
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster) || empty($ris_no)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if we should increment RIS counter
        if (isset($_POST['increment_ris_counter']) && $_POST['increment_ris_counter'] == '1') {
            // Generate actual RIS number (this increments the counter)
            $generated_ris_no = generateNextTag('ris_no');
            if ($generated_ris_no !== null) {
                $ris_no = $generated_ris_no;
                logSystemAction($_SESSION['user_id'], 'RIS counter incremented', 'forms', "Generated RIS number: $ris_no");
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert RIS form
        $stmt = $conn->prepare("INSERT INTO ris_forms (entity_name, fund_cluster, ris_no, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, issued_by, issued_by_position, issued_date, received_by, received_by_position, received_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssssssi", $entity_name, $fund_cluster, $ris_no, $purpose, $requested_by, $requested_by_position, $requested_date, $approved_by, $approved_by_position, $approved_date, $issued_by, $issued_by_position, $issued_date, $received_by, $received_by_position, $received_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save RIS form: ' . $stmt->error);
        }
        
        $ris_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert RIS items
        $item_stmt = $conn->prepare("INSERT INTO ris_items (form_id, stock_no, quantity, unit, description, remarks) VALUES (?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($stock_numbers); $i++) {
            if (!empty($stock_numbers[$i]) && !empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                
                $item_stmt->bind_param("isids", $ris_form_id, $stock_numbers[$i], $quantity, $units[$i], $descriptions[$i], $remarks[$i]);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save RIS item: ' . $item_stmt->error);
                }
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created RIS form', 'forms', "RIS No: $ris_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "RIS form saved successfully! RIS Number: $ris_no";
        
        // Redirect back to form
        header('Location: ris_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
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
