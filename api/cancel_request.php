<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Get request ID from POST data
$request_id = $_POST['request_id'] ?? 0;

if (empty($request_id) || !is_numeric($request_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request ID']);
    exit();
}

$office_id = $_SESSION['office_id'];
$user_id = $_SESSION['user_id'];

try {
    // First check if the request exists and belongs to this office
    $query = "SELECT br.* FROM borrow_requests br 
              WHERE br.id = ? AND (br.requested_by_office = ? OR br.requested_to_office = ?)
              AND br.status = 'pending'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $request_id, $office_id, $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found or cannot be cancelled']);
        exit();
    }
    
    $request_data = $result->fetch_assoc();
    
    // Only allow cancellation of pending requests
    if ($request_data['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['error' => 'Only pending requests can be cancelled']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Update the request status to cancelled
    $update_query = "UPDATE borrow_requests 
                    SET status = 'cancelled', 
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $request_id);
    $update_stmt->execute();
    
    // Log the cancellation
    logSystemAction($user_id, "Request cancelled", "borrow_requests", "Borrow request #{$request_id} was cancelled by office admin");
    
    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Request cancelled successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    error_log("Error cancelling request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
