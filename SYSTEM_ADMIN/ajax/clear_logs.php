<?php
// AJAX endpoint for clearing logs
session_start();
require_once '../../config.php';

// Function to log system actions (if not already defined)
if (!function_exists('logSystemAction')) {
    function logSystemAction($user_id, $action, $module, $details = null) {
        global $conn;
        
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = $conn->prepare("
                INSERT INTO system_logs (user_id, action, module, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param("isssss", $user_id, $action, $module, $details, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            // Don't throw errors in logging to avoid infinite loops
            error_log("Failed to log system action: " . $e->getMessage());
        }
    }
}

// Check if user is logged in and has correct role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Get clear range from POST data
$range = isset($_POST['range']) ? $_POST['range'] : 'all';

try {
    // Build WHERE clause based on range
    $where_conditions = [];
    $params = [];
    $types = '';
    
    switch ($range) {
        case 'all':
            try {
                // Use DELETE instead of TRUNCATE for clearing all logs
                $conn->query("DELETE FROM system_logs");
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Successfully cleared all system logs."
                ]);
                exit();
            } catch (Exception $delete_error) {
                echo json_encode(['success' => false, 'message' => 'Failed to clear logs']);
                exit();
            }
            break;
        case 'older_than_30':
            $where_conditions[] = "created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'older_than_90':
            $where_conditions[] = "created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            break;
        case 'older_than_365':
            $where_conditions[] = "created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)";
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid clear range']);
            exit();
    }
    
    // For partial deletions, use direct DELETE for maximum speed
    if (!empty($where_conditions)) {
        try {
            $delete_sql = "DELETE FROM system_logs WHERE " . implode(' AND ', $where_conditions);
            
            $stmt = $conn->prepare($delete_sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if ($stmt->execute()) {
                $actual_deleted = $stmt->affected_rows;
                $stmt->close();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Successfully deleted {$actual_deleted} log entries."
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear logs']);
            }
        } catch (Exception $partial_error) {
            echo json_encode(['success' => false, 'message' => 'Partial deletion failed']);
        }
    }
    
} catch (Exception $e) {
    error_log("Clear logs error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

$conn->close();
?>
