<?php
// AJAX endpoint for getting log details
session_start();
require_once '../../config.php';

// Check if user is logged in and has correct role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Get log ID from POST data
$log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;

if ($log_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    exit();
}

try {
    // Fetch log details with user information
    $stmt = $conn->prepare("
        SELECT sl.*, u.first_name, u.last_name, u.username 
        FROM system_logs sl 
        LEFT JOIN users u ON sl.user_id = u.id 
        WHERE sl.id = ?
    ");
    
    if ($stmt === false) {
        throw new Exception("Database error");
    }
    
    $stmt->bind_param("i", $log_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $log = $result->fetch_assoc();
        echo json_encode(['success' => true, 'log' => $log]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log entry not found']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching log details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>
