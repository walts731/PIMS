<?php
session_start();
require_once '../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug information
$debug = [];

try {
    // Check session
    $debug['session'] = [
        'logged_in' => $_SESSION['logged_in'] ?? false,
        'role' => $_SESSION['role'] ?? null,
        'office_id' => $_SESSION['office_id'] ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ];
    
    // Session validation
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode([
            'success' => false, 
            'message' => 'Unauthorized access',
            'debug' => $debug
        ]);
        exit();
    }

    if ($_SESSION['role'] !== 'office_admin') {
        echo json_encode([
            'success' => false, 
            'message' => 'Access denied',
            'debug' => $debug
        ]);
        exit();
    }
    
    // Check database connection
    $debug['database'] = [
        'conn_exists' => $conn ? true : false,
        'conn_error' => $conn->connect_error ?? null
    ];
    
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not established'));
    }
    
    // Get search parameters
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $debug['parameters'] = [
        'query' => $query,
        'type' => $type,
        'limit' => $limit
    ];
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => false, 
            'message' => 'Query too short',
            'debug' => $debug
        ]);
        exit();
    }
    
    $user_office_id = $_SESSION['office_id'];
    $results = [];
    
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Test simple asset search - first check if office_id column exists
    $office_id_column_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM asset_items LIKE 'office_id'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $office_id_column_exists = true;
    }
    
    $debug['office_id_column_exists'] = $office_id_column_exists;
    
    if ($office_id_column_exists) {
        // Search with office_id filter
        $sql = "SELECT 
                    id, description, model, serial_number, property_no, status, 
                    value, office_name, end_user, 'asset' as type,
                    (CASE 
                        WHEN description LIKE ? THEN 10
                        WHEN model LIKE ? THEN 8
                        ELSE 1
                    END) as relevance
                FROM asset_items 
                WHERE office_id = ? 
                AND (
                    description LIKE ? OR 
                    model LIKE ?
                )
                ORDER BY relevance DESC, description ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $user_office_id, $searchTerm, $searchTerm, $limit];
    } else {
        // Search without office_id filter (for compatibility)
        $sql = "SELECT 
                    id, description, model, serial_number, property_no, status, 
                    value, office_name, end_user, 'asset' as type,
                    (CASE 
                        WHEN description LIKE ? THEN 10
                        WHEN model LIKE ? THEN 8
                        ELSE 1
                    END) as relevance
                FROM asset_items 
                WHERE (
                    description LIKE ? OR 
                    model LIKE ?
                )
                ORDER BY relevance DESC, description ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
    }
    
    $debug['sql'] = $sql;
    $debug['params'] = $params;
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($office_id_column_exists) {
            $types = str_repeat('s', count($params) - 1) . 'i';
        } else {
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $debug['query_executed'] = true;
        $debug['num_rows'] = $result->num_rows;
        
        while ($row = $result->fetch_assoc()) {
            $row['url'] = 'office_assets.php?search=' . urlencode($query);
            $row['title'] = $row['description'];
            $row['subtitle'] = $row['model'] ? $row['model'] : $row['property_no'];
            $row['badge'] = ucfirst($row['status']);
            $row['badge_class'] = getAssetStatusBadgeClass($row['status']);
            $results[] = $row;
        }
        $stmt->close();
    } else {
        $debug['prepare_error'] = $conn->error;
    }
    
    $debug['results_count'] = count($results);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
    $debug['trace'] = $e->getTraceAsString();
    
    echo json_encode([
        'success' => false,
        'message' => 'Search error: ' . $e->getMessage(),
        'debug' => $debug
    ]);
}

function getAssetStatusBadgeClass($status) {
    switch ($status) {
        case 'available': return 'bg-success';
        case 'in_use': return 'bg-primary';
        case 'maintenance': return 'bg-warning';
        case 'disposed': case 'unserviceable': return 'bg-danger';
        case 'serviceable': return 'bg-info';
        default: return 'bg-secondary';
    }
}
?>
