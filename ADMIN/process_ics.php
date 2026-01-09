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
        $items = $_POST['item_no'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $unit_costs = $_POST['unit_cost'] ?? [];
        $total_costs = $_POST['total_cost'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $useful_lives = $_POST['useful_life'] ?? [];
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster) || empty($ics_no)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if we should increment ICS counter
        if (isset($_POST['increment_ics_counter']) && $_POST['increment_ics_counter'] == '1') {
            // Generate actual ICS number (this increments the counter)
            $generated_ics_no = generateNextTag('ics_no');
            if ($generated_ics_no !== null) {
                $ics_no = $generated_ics_no;
                logSystemAction($_SESSION['user_id'], 'ICS counter incremented', 'forms', "Generated ICS number: $ics_no");
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert ICS form
        $stmt = $conn->prepare("INSERT INTO ics_forms (entity_name, fund_cluster, ics_no, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssi", $entity_name, $fund_cluster, $ics_no, $_SESSION['user_id'], $_SESSION['user_id']);
        
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
                
                $item_stmt->bind_param("isiddsi", $ics_form_id, $items[$i], $quantity, $units[$i], $unit_cost, $total_cost, $descriptions[$i], $useful_life);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save ICS item: ' . $item_stmt->error);
                }
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
