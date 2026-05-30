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

require_once 'includes/check_permissions.php';
adminRequirePermission('forms.delete', 'can_delete', 'ics_entries.php');

// Get ICS ID from URL
$ics_id = $_GET['id'] ?? 0;
if (empty($ics_id)) {
    header('Location: ics_entries.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get ICS form details for logging
        $stmt = $conn->prepare("SELECT ics_no, entity_name FROM ics_forms WHERE id = ?");
        $stmt->bind_param("i", $ics_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('ICS form not found');
        }
        
        $ics_form = $result->fetch_assoc();
        $stmt->close();
        
        // Begin transaction
        $conn->begin_transaction();
        
        // Delete ICS items (foreign key should handle this, but let's be explicit)
        $stmt = $conn->prepare("DELETE FROM ics_items WHERE form_id = ?");
        $stmt->bind_param("i", $ics_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete ICS items: ' . $stmt->error);
        }
        $stmt->close();
        
        // Delete ICS form
        $stmt = $conn->prepare("DELETE FROM ics_forms WHERE id = ?");
        $stmt->bind_param("i", $ics_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete ICS form: ' . $stmt->error);
        }
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Log action
        logSystemAction($_SESSION['user_id'], 'Deleted ICS form', 'forms', "ICS No: {$ics_form['ics_no']}, Entity: {$ics_form['entity_name']}");
        
        // Set success message
        $_SESSION['success_message'] = "ICS form deleted successfully! ICS Number: {$ics_form['ics_no']}";
        
        // Redirect back to entries
        header('Location: ics_entries.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        // Log error
        error_log("Error deleting ICS form: " . $e->getMessage());
        logSystemAction($_SESSION['user_id'], 'Failed to delete ICS form', 'forms', "Error: " . $e->getMessage());
        
        // Set error message
        $_SESSION['error_message'] = "Error deleting ICS form: " . $e->getMessage();
        
        // Redirect back to entries
        header('Location: ics_entries.php');
        exit();
    }
} else {
    // Not a GET request
    header('Location: ics_entries.php');
    exit();
}
?>
