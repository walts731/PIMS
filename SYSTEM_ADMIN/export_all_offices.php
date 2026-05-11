<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // First, get total count of all offices
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM offices");
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_count = $count_result->fetch_assoc()['total'];
    
    // Get ALL offices from database without any filtering
    $offices = [];
    $stmt = $conn->prepare("
        SELECT o.*, 
               u1.username as created_by_name, 
               u2.username as updated_by_name, 
               p.office_name as parent_office_name, 
               p.office_code as parent_office_code, 
               (SELECT COUNT(*) FROM offices WHERE branch = o.id) as child_count
        FROM offices o 
        LEFT JOIN users u1 ON o.created_by = u1.id 
        LEFT JOIN users u2 ON o.updated_by = u2.id 
        LEFT JOIN offices p ON o.branch = p.id 
        ORDER BY o.id ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $offices[] = $row;
    }
    
    // Debug: Add first and last IDs to response
    $debug_info = [
        'total_in_database' => $total_count,
        'total_exported' => count($offices),
        'first_id' => !empty($offices) ? $offices[0]['id'] : null,
        'last_id' => !empty($offices) ? end($offices)['id'] : null,
        'missing_count' => $total_count - count($offices)
    ];
    
    // Log the export operation
    logSystemAction($_SESSION['user_id'], 'export_all_offices', 'office_management', 
        "Exported all offices data: " . count($offices) . " records (DB total: {$total_count})");
    
    // Return success response with all offices data and debug info
    echo json_encode([
        'success' => true,
        'offices' => $offices,
        'total_count' => count($offices),
        'debug' => $debug_info,
        'message' => 'All offices data retrieved successfully'
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Export all offices error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
