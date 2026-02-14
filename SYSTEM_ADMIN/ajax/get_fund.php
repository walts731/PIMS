<?php
// Start output buffering to prevent any HTML output
ob_start();

session_start();
require_once '../../config.php';
require_once '../../includes/system_functions.php';

// Database connection is already established in config.php
global $conn;

// Disable error display and enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Function to send JSON response and exit
function sendJsonResponse($success, $message = '', $data = null) {
    // Clean any output buffer
    ob_clean();
    
    // Set proper JSON header
    header('Content-Type: application/json');
    
    // Prepare response
    $response = ['success' => $success];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data !== null) {
        $response['fund'] = $data;
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

// Handle GET request for fund data
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        if (!$conn) {
            sendJsonResponse(false, 'Database connection failed');
        }
        
        $stmt = $conn->prepare("SELECT * FROM funds WHERE id = ?");
        if (!$stmt) {
            sendJsonResponse(false, 'Failed to prepare statement');
        }
        
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            sendJsonResponse(false, 'Failed to execute statement');
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            sendJsonResponse(false, 'Failed to get result');
        }
        
        if ($fund = $result->fetch_assoc()) {
            sendJsonResponse(true, '', $fund);
        } else {
            sendJsonResponse(false, 'Fund not found');
        }
        
    } catch (Exception $e) {
        sendJsonResponse(false, 'Error: ' . $e->getMessage());
    }
}

sendJsonResponse(false, 'Invalid request');
?>
