<?php
// Start output buffering to prevent any HTML output
ob_start();

session_start();
require_once '../../config.php';
require_once '../../includes/system_functions.php';
require_once '../../includes/logger.php';

// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '') {
    // Clean any output buffer
    ob_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    // Prepare response
    $response = ['success' => $success];
    if ($message) {
        $response['message'] = $message;
    }
    
    // Send JSON and exit
    echo json_encode($response);
    exit();
}

// Check session timeout with error handling
try {
    checkSessionTimeout();
} catch (Exception $e) {
    sendJsonResponse(false, 'Session check failed: ' . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    sendJsonResponse(false, 'Unauthorized access');
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    sendJsonResponse(false, 'Unauthorized access');
}

// Handle POST request for status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fund_id']) && isset($_POST['status'])) {
    $fund_id = intval($_POST['fund_id']);
    $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
    
    try {
        // Get fund info for logging
        $info_stmt = $conn->prepare("SELECT fund_code, fund_name FROM funds WHERE id = ?");
        if (!$info_stmt) {
            sendJsonResponse(false, 'Failed to prepare info statement');
        }
        
        $info_stmt->bind_param("i", $fund_id);
        if (!$info_stmt->execute()) {
            sendJsonResponse(false, 'Failed to execute info query');
        }
        
        $fund_info = $info_stmt->get_result()->fetch_assoc();
        
        $stmt = $conn->prepare("UPDATE funds SET status = ?, updated_by = ? WHERE id = ?");
        if (!$stmt) {
            sendJsonResponse(false, 'Failed to prepare update statement');
        }
        
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $fund_id);
        if (!$stmt->execute()) {
            sendJsonResponse(false, 'Failed to update fund status');
        }
        
        // Log the status change
        logSystemAction($_SESSION['user_id'], 'fund_status_updated', 'fund_management', 
            "Updated fund status: {$fund_info['fund_name']} ({$fund_info['fund_code']}) to {$status}");
        
        sendJsonResponse(true, 'Fund status updated successfully');
        
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage());
    }
}

sendJsonResponse(false, 'Invalid request');
?>
