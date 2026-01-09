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
        $iirup_no = $_POST['iirup_no'];
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
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster) || empty($iirup_no)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if we should increment SAI counter
        if (isset($_POST['increment_sai_counter']) && $_POST['increment_sai_counter'] == '1') {
            // Generate actual SAI number (this increments the counter)
            $generated_sai_no = generateNextTag('sai_no');
            if ($generated_sai_no !== null) {
                $iirup_no = $generated_sai_no;
                logSystemAction($_SESSION['user_id'], 'SAI counter incremented', 'forms', "Generated SAI number: $iirup_no");
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert IIRUP form
        $stmt = $conn->prepare("INSERT INTO iirup_forms (entity_name, fund_cluster, iirup_no, purpose, requested_by, requested_by_position, requested_date, approved_by, approved_by_position, approved_date, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssi", $entity_name, $fund_cluster, $iirup_no, $purpose, $requested_by, $requested_by_position, $requested_date, $approved_by, $approved_by_position, $approved_date, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save IIRUP form: ' . $stmt->error);
        }
        
        $iirup_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert IIRUP items
        $item_stmt = $conn->prepare("INSERT INTO iirup_items (form_id, item_no, description, quantity, unit, unit_price, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $unit_price = floatval($unit_prices[$i]);
                $total_amount = floatval($total_amounts[$i]);
                
                $item_stmt->bind_param("isidddd", $iirup_form_id, $items[$i], $descriptions[$i], $quantity, $units[$i], $unit_price, $total_amount);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save IIRUP item: ' . $item_stmt->error);
                }
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Created IIRUP form', 'forms', "IIRUP No: $iirup_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "IIRUP form saved successfully! IIRUP Number: $iirup_no";
        
        // Redirect back to form
        header('Location: iirup_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing IIRUP form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create IIRUP form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving IIRUP form: " . $e->getMessage();
        
        // Redirect back to form
        header('Location: iirup_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: iirup_form.php');
    exit();
}
?>
