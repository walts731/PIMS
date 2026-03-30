<?php
session_start();
require_once '../config.php';

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
    
    // Search borrow requests
    if ($type === 'all' || $type === 'requests') {
        $request_results = searchBorrowRequests($conn, $query, $user_office_id, $limit);
        $results = array_merge($results, $request_results);
    }
    
    // Search consumables
    if ($type === 'all' || $type === 'consumables') {
        $consumable_results = searchConsumables($conn, $query, $user_office_id, $limit);
        $results = array_merge($results, $consumable_results);
    }
    
    // Search users (limited to office)
    if ($type === 'all' || $type === 'users') {
        $user_results = searchUsers($conn, $query, $user_office_id, $limit);
        $results = array_merge($results, $user_results);
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
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Search temporarily unavailable'
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
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE office_id = ? AND (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $office_id, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = 'ssssssssi';
    } else {
        // Search without office_id filter (for compatibility)
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = 'sssssssss';
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

function searchBorrowRequests($conn, $query, $office_id, $limit) {
    $results = [];
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Check if requested_by_office column exists
    $requested_by_office_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM borrow_requests LIKE 'requested_by_office'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $requested_by_office_exists = true;
    }
    
    // Build query based on column existence
    if ($requested_by_office_exists) {
        $sql = "SELECT 
                    br.id, br.purpose, br.status, br.start_date, br.end_date,
                    ai.description as asset_description,
                    u.first_name, u.last_name,
                    'request' as type,
                    (CASE 
                        WHEN br.purpose LIKE ? THEN 10
                        WHEN ai.description LIKE ? THEN 8
                        WHEN CONCAT(u.first_name, ' ', u.last_name) LIKE ? THEN 6
                        ELSE 1
                    END) as relevance
                FROM borrow_requests br
                LEFT JOIN asset_items ai ON br.asset_id = ai.id
                LEFT JOIN users u ON br.requested_by = u.id
                WHERE (br.requested_by_office = ? OR br.requested_to_office = ?)
                AND (
                    br.purpose LIKE ? OR 
                    ai.description LIKE ? OR
                    CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                )
                ORDER BY relevance DESC, br.created_at DESC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $office_id, $office_id, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params) - 2) . 'ii';
    } else {
        // Fallback without office filtering
        $sql = "SELECT 
                    br.id, br.purpose, br.status, br.start_date, br.end_date,
                    ai.description as asset_description,
                    u.first_name, u.last_name,
                    'request' as type,
                    (CASE 
                        WHEN br.purpose LIKE ? THEN 10
                        WHEN ai.description LIKE ? THEN 8
                        WHEN CONCAT(u.first_name, ' ', u.last_name) LIKE ? THEN 6
                        ELSE 1
                    END) as relevance
                FROM borrow_requests br
                LEFT JOIN asset_items ai ON br.asset_id = ai.id
                LEFT JOIN users u ON br.requested_by = u.id
                WHERE (
                    br.purpose LIKE ? OR 
                    ai.description LIKE ? OR
                    CONCAT(u.first_name, ' ', u.last_name) LIKE ?
                )
                ORDER BY relevance DESC, br.created_at DESC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params));
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['url'] = 'requests.php?search=' . urlencode($query);
            $row['title'] = 'Request: ' . substr($row['purpose'], 0, 50) . (strlen($row['purpose']) > 50 ? '...' : '');
            $row['subtitle'] = $row['asset_description'] . ' - ' . $row['first_name'] . ' ' . $row['last_name'];
            $row['badge'] = ucfirst($row['status']);
            $row['badge_class'] = getRequestStatusBadgeClass($row['status']);
            $row['destination'] = 'Requests Page';
            $results[] = $row;
        }
        $stmt->close();
    }
    
    return $results;
}

function searchUsers($conn, $query, $office_id, $limit) {
    $results = [];
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Check if office_id column exists in users table
    $office_id_column_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM users LIKE 'office_id'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $office_id_column_exists = true;
    }
    
    // Build query based on column existence
    if ($office_id_column_exists) {
        $sql = "SELECT 
                    id, first_name, last_name, email, position, role,
                    'user' as type,
                    (CASE 
                        WHEN first_name LIKE ? OR last_name LIKE ? THEN 10
                        WHEN email LIKE ? THEN 8
                        WHEN position LIKE ? THEN 6
                        ELSE 1
                    END) as relevance
                FROM users 
                WHERE office_id = ? 
                AND (
                    first_name LIKE ? OR 
                    last_name LIKE ? OR 
                    email LIKE ? OR 
                    position LIKE ?
                )
                ORDER BY relevance DESC, first_name ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $office_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params) - 1) . 'i';
    } else {
        // Fallback without office filtering
        $sql = "SELECT 
                    id, first_name, last_name, email, position, role,
                    'user' as type,
                    (CASE 
                        WHEN first_name LIKE ? OR last_name LIKE ? THEN 10
                        WHEN email LIKE ? THEN 8
                        WHEN position LIKE ? THEN 6
                        ELSE 1
                    END) as relevance
                FROM users 
                WHERE (
                    first_name LIKE ? OR 
                    last_name LIKE ? OR 
                    email LIKE ? OR 
                    position LIKE ?
                )
                ORDER BY relevance DESC, first_name ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params));
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['url'] = 'profile.php?user_id=' . $row['id'];
            $row['title'] = $row['first_name'] . ' ' . $row['last_name'];
            $row['subtitle'] = $row['position'] ? $row['position'] : $row['email'];
            $row['badge'] = ucfirst($row['role']);
            $row['badge_class'] = getUserRoleBadgeClass($row['role']);
            $row['destination'] = 'User Profile';
            $results[] = $row;
        }
        $stmt->close();
    }
    
    return $results;
}

function searchConsumables($conn, $query, $office_id, $limit) {
    $results = [];
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Check if office_id column exists in consumables table
    $office_id_column_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM consumables LIKE 'office_id'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $office_id_column_exists = true;
    }
    
    // Build query based on column existence
    if ($office_id_column_exists) {
        $sql = "SELECT 
                    id, description, quantity, units, unit_cost, 'consumable' as type,
                    (CASE 
                        WHEN description LIKE ? THEN 10
                        WHEN supplier LIKE ? THEN 8
                        ELSE 1
                    END) as relevance
                FROM consumables 
                WHERE office_id = ? 
                AND (
                    description LIKE ? OR 
                    supplier LIKE ?
                )
                ORDER BY relevance DESC, description ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $office_id, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params) - 1) . 'i';
    } else {
        // Fallback without office filtering
        $sql = "SELECT 
                    id, description, quantity, units, unit_cost, 'consumable' as type,
                    (CASE 
                        WHEN description LIKE ? THEN 10
                        WHEN supplier LIKE ? THEN 8
                        ELSE 1
                    END) as relevance
                FROM consumables 
                WHERE (
                    description LIKE ? OR 
                    supplier LIKE ?
                )
                ORDER BY relevance DESC, description ASC
                LIMIT ?";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = str_repeat('s', count($params));
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['url'] = 'office_consumables.php?search=' . urlencode($query);
            $row['title'] = $row['description'];
            $row['subtitle'] = $row['quantity'] . ' ' . $row['units'] . ' - ₱' . number_format($row['unit_cost'], 2);
            $row['badge'] = 'Consumable';
            $row['badge_class'] = 'bg-info';
            $row['destination'] = 'Consumables Page';
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

function getRequestStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'approved': return 'bg-success';
        case 'denied': return 'bg-danger';
        case 'borrowed': return 'bg-primary';
        case 'returned': return 'bg-info';
        default: return 'bg-secondary';
    }
}

function getUserRoleBadgeClass($role) {
    switch ($role) {
        case 'office_admin': return 'bg-warning text-dark';
        case 'admin': return 'bg-danger';
        case 'user': return 'bg-success';
        default: return 'bg-secondary';
    }
}
?>
