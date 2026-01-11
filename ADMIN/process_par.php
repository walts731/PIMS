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
        $par_no = $_POST['par_no'];
        $remarks = $_POST['remarks'] ?? '';
        $received_by = $_POST['received_by'];
        $issued_by = $_POST['issued_by'];
        $quantities = $_POST['quantity'] ?? [];
        $units = $_POST['unit'] ?? [];
        $descriptions = $_POST['description'] ?? [];
        $property_numbers = $_POST['property_number'] ?? [];
        $dates_acquired = $_POST['date_acquired'] ?? [];
        $amounts = $_POST['amount'] ?? [];
        
        // Validate required fields
        if (empty($entity_name) || empty($fund_cluster) || empty($par_no) || empty($received_by) || empty($issued_by)) {
            throw new Exception('All required fields must be filled');
        }
        
        // Check if we should increment the PAR counter
        if (isset($_POST['increment_par_counter']) && $_POST['increment_par_counter'] == '1') {
            // Generate the actual PAR number (this increments the counter)
            $generated_par_no = generateNextTag('par_no');
            if ($generated_par_no !== null) {
                $par_no = $generated_par_no;
                logSystemAction($_SESSION['user_id'], 'PAR counter incremented', 'forms', "Generated PAR number: $par_no");
            }
        }
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Insert PAR form
        $stmt = $conn->prepare("INSERT INTO par_forms (entity_name, fund_cluster, par_no, remarks, received_by_name, issued_by_name, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssii", $entity_name, $fund_cluster, $par_no, $remarks, $received_by, $issued_by, $_SESSION['user_id'], $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save PAR form: ' . $stmt->error);
        }
        
        $par_form_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert PAR items
        $item_stmt = $conn->prepare("INSERT INTO par_items (form_id, quantity, unit, description, property_number, date_acquired, amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($descriptions); $i++) {
            if (!empty($descriptions[$i])) {
                $quantity = floatval($quantities[$i]);
                $property_number = $property_numbers[$i] ?? null;
                $date_acquired = !empty($dates_acquired[$i]) ? $dates_acquired[$i] : null;
                $amount = floatval($amounts[$i]);
                
                $item_stmt->bind_param("idssdsd", $par_form_id, $quantity, $units[$i], $descriptions[$i], $property_number, $date_acquired, $amount);
                
                if (!$item_stmt->execute()) {
                    throw new Exception('Failed to save PAR item: ' . $item_stmt->error);
                }
            }
        }
        $item_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log the action
        logSystemAction($_SESSION['user_id'], 'Created PAR form', 'forms', "PAR No: $par_no, Entity: $entity_name");
        
        // Set success message
        $_SESSION['success_message'] = "PAR form saved successfully! PAR Number: $par_no";
        
        // Redirect back to the form
        header('Location: par_form.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error processing PAR form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to create PAR form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error saving PAR form: " . $e->getMessage();
        
        // Redirect back to the form
        header('Location: par_form.php');
        exit();
    }
} else {
    // Not a POST request
    header('Location: par_form.php');
    exit();
}
?>
