<?php
// AJAX endpoint for exporting logs
session_start();
require_once '../../config.php';

// Check if user is logged in and has correct role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Unauthorized access');
}

if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    die('Insufficient permissions');
}

// Get export parameters
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$include_user_agent = isset($_GET['include_user_agent']) ? $_GET['include_user_agent'] : '1';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    // Build WHERE clause based on date range
    $where_conditions = [];
    $params = [];
    $types = '';
    
    switch ($date_range) {
        case 'today':
            $where_conditions[] = "DATE(sl.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "sl.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "sl.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        case 'custom':
            if (!empty($date_from)) {
                $where_conditions[] = "DATE(sl.created_at) >= ?";
                $params[] = $date_from;
                $types .= 's';
            }
            if (!empty($date_to)) {
                $where_conditions[] = "DATE(sl.created_at) <= ?";
                $params[] = $date_to;
                $types .= 's';
            }
            break;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Fetch logs
    $sql = "
        SELECT sl.id, sl.action, sl.module, sl.details, sl.ip_address, 
               " . ($include_user_agent ? 'sl.user_agent,' : '') . "
               sl.created_at, u.first_name, u.last_name, u.username
        FROM system_logs sl 
        LEFT JOIN users u ON sl.user_id = u.id 
        $where_clause 
        ORDER BY sl.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Generate filename
    $filename = 'system_logs_' . date('Y-m-d_H-i-s');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV header
            $header = ['ID', 'Action', 'Module', 'User', 'Details', 'IP Address', 'Date/Time'];
            if ($include_user_agent) {
                $header[] = 'User Agent';
            }
            fputcsv($output, $header);
            
            // CSV data
            while ($row = $result->fetch_assoc()) {
                $user_info = $row['first_name'] && $row['last_name'] ? 
                    $row['first_name'] . ' ' . $row['last_name'] . ' (@' . $row['username'] . ')' : 
                    'System';
                
                $data = [
                    $row['id'],
                    $row['action'],
                    $row['module'],
                    $user_info,
                    $row['details'] ?: '',
                    $row['ip_address'],
                    $row['created_at']
                ];
                
                if ($include_user_agent) {
                    $data[] = $row['user_agent'] ?: '';
                }
                
                fputcsv($output, $data);
            }
            
            fclose($output);
            break;
            
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $log_entry = [
                    'id' => $row['id'],
                    'action' => $row['action'],
                    'module' => $row['module'],
                    'user' => $row['first_name'] && $row['last_name'] ? 
                        $row['first_name'] . ' ' . $row['last_name'] . ' (@' . $row['username'] . ')' : 
                        'System',
                    'details' => $row['details'] ?: '',
                    'ip_address' => $row['ip_address'],
                    'created_at' => $row['created_at']
                ];
                
                if ($include_user_agent) {
                    $log_entry['user_agent'] = $row['user_agent'] ?: '';
                }
                
                $logs[] = $log_entry;
            }
            
            echo json_encode($logs, JSON_PRETTY_PRINT);
            break;
            
        case 'excel':
            // For Excel export, we'll create a simple CSV that Excel can open
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
            
            $output = fopen('php://output', 'w');
            
            // Excel-friendly header
            $header = ['ID', 'Action', 'Module', 'User', 'Details', 'IP Address', 'Date/Time'];
            if ($include_user_agent) {
                $header[] = 'User Agent';
            }
            fputcsv($output, $header);
            
            // Data
            while ($row = $result->fetch_assoc()) {
                $user_info = $row['first_name'] && $row['last_name'] ? 
                    $row['first_name'] . ' ' . $row['last_name'] . ' (@' . $row['username'] . ')' : 
                    'System';
                
                $data = [
                    $row['id'],
                    $row['action'],
                    $row['module'],
                    $user_info,
                    $row['details'] ?: '',
                    $row['ip_address'],
                    $row['created_at']
                ];
                
                if ($include_user_agent) {
                    $data[] = $row['user_agent'] ?: '';
                }
                
                fputcsv($output, $data);
            }
            
            fclose($output);
            break;
            
        case 'pdf':
            // Simple PDF export (basic text format)
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $filename . '.txt"');
            
            echo "SYSTEM LOGS EXPORT\n";
            echo "Generated: " . date('Y-m-d H:i:s') . "\n";
            echo "Date Range: " . ucfirst($date_range) . "\n";
            echo "========================================\n\n";
            
            while ($row = $result->fetch_assoc()) {
                $user_info = $row['first_name'] && $row['last_name'] ? 
                    $row['first_name'] . ' ' . $row['last_name'] . ' (@' . $row['username'] . ')' : 
                    'System';
                
                echo "ID: " . $row['id'] . "\n";
                echo "Action: " . $row['action'] . "\n";
                echo "Module: " . $row['module'] . "\n";
                echo "User: " . $user_info . "\n";
                echo "Details: " . ($row['details'] ?: 'N/A') . "\n";
                echo "IP Address: " . $row['ip_address'] . "\n";
                if ($include_user_agent) {
                    echo "User Agent: " . ($row['user_agent'] ?: 'N/A') . "\n";
                }
                echo "Date/Time: " . $row['created_at'] . "\n";
                echo "----------------------------------------\n\n";
            }
            break;
            
        default:
            die('Invalid export format');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    die('Export error occurred');
}

$conn->close();
?>
