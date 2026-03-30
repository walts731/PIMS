<?php
session_start();
require_once '../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simple session check without complex dependencies
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

try {
    $query = $_GET['q'] ?? '';
    $type = $_GET['type'] ?? 'all';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Query too short']);
        exit();
    }
    
    $user_office_id = $_SESSION['office_id'];
    $results = [];
    
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    // Search asset items
    if ($type === 'all' || $type === 'assets') {
        $asset_results = searchAssetItems($conn, $query, $user_office_id, $limit);
        $results = array_merge($results, $asset_results);
    }
    
    // Sort by relevance score
    usort($results, function($a, $b) {
        return $b['relevance'] <=> $a['relevance'];
    });
    
    // Limit total results
    $results = array_slice($results, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function searchAssetItems($conn, $query, $office_id, $limit) {
    $results = [];
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Check if office_id column exists
    $office_id_column_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM asset_items LIKE 'office_id'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $office_id_column_exists = true;
    }
    
    if ($office_id_column_exists) {
        // Fixed: 12 placeholders for 12 parameters
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE office_id = ? AND (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT ?";
        
        // 12 parameters: 5 for CASE, 1 for office_id, 5 for WHERE, 1 for LIMIT
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $office_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = 'sssssssssssi';
    } else {
        // Fallback without office_id filter - 11 placeholders
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT ?";
        
        // 11 parameters: 5 for CASE, 5 for WHERE, 1 for LIMIT
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = 'ssssssssss';
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['url'] = 'office_assets.php?search=' . urlencode($query);
            $row['title'] = $row['description'];
            $row['subtitle'] = $row['model'] ? $row['model'] : $row['property_no'];
            $row['badge'] = ucfirst($row['status']);
            $row['badge_class'] = getAssetStatusBadgeClass($row['status']);
            $row['destination'] = 'Assets Page';
            $results[] = $row;
        }
        $stmt->close();
    }
    
    return $results;
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
