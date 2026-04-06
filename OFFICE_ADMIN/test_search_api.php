<?php
session_start();
require_once '../config.php';

// Simulate session for testing
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 5;

header('Content-Type: application/json');

try {
    $query = $_GET['q'] ?? 'laptop';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    
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
    $asset_results = searchAssetItems($conn, $query, $user_office_id, $limit);
    $results = array_merge($results, $asset_results);
    
    // Search borrow requests
    $request_results = searchBorrowRequests($conn, $query, $user_office_id, $limit);
    $results = array_merge($results, $request_results);
    
    // Sort by relevance score
    usort($results, function($a, $b) {
        return $b['relevance'] <=> $a['relevance'];
    });
    
    // Limit total results
    $results = array_slice($results, 0, $limit);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'debug' => [
            'query' => $query,
            'office_id' => $user_office_id,
            'asset_count' => count($asset_results),
            'request_count' => count($request_results)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
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
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE office_id = ? AND (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT?";
        
        // 12 parameters: 5 for CASE, 1 for office_id, 5 for WHERE, 1 for LIMIT
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $office_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        $types = 'sssssssssssi';
    } else {
        // Fallback without office_id filter - 11 placeholders
        $sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT?";
        
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

function searchBorrowRequests($conn, $query, $office_id, $limit) {
    $results = [];
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    
    // Search borrow requests that involve this office (either as requester or requested_to)
    $sql = "SELECT 
                br.id,
                br.purpose,
                br.status,
                br.start_date,
                br.end_date,
                br.quantity_requested,
                br.quantity_approved,
                a.description as asset_description,
                ai.property_no,
                u1.first_name as requester_first_name,
                u1.last_name as requester_last_name,
                o1.office_name as requester_office,
                o2.office_name as requested_office,
                'request' as type,
                (CASE 
                    WHEN br.purpose LIKE ? THEN 10 
                    WHEN a.description LIKE ? THEN 9 
                    WHEN ai.property_no LIKE ? THEN 8 
                    WHEN u1.first_name LIKE ? OR u1.last_name LIKE ? THEN 7
                    WHEN o1.office_name LIKE ? OR o2.office_name LIKE ? THEN 6
                    ELSE 1 
                END) as relevance
            FROM borrow_requests br
            LEFT JOIN assets a ON br.asset_id = a.id
            LEFT JOIN asset_items ai ON ai.asset_id = a.id
            LEFT JOIN users u1 ON br.requested_by = u1.id
            LEFT JOIN offices o1 ON br.requested_by_office = o1.id
            LEFT JOIN offices o2 ON br.requested_to_office = o2.id
            WHERE (br.requested_by_office = ? OR br.requested_to_office = ?)
            AND (br.purpose LIKE ? OR a.description LIKE ? OR ai.property_no LIKE ? 
                 OR u1.first_name LIKE ? OR u1.last_name LIKE ? 
                 OR o1.office_name LIKE ? OR o2.office_name LIKE ?)
            ORDER BY relevance DESC, br.created_at DESC 
            LIMIT?";
    
    $params = [
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, // for CASE
        $office_id, $office_id, // for WHERE office filter
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, // for WHERE search
        $limit
    ];
    $types = 'sssssssiiissssssi';
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $requester_name = trim($row['requester_first_name'] . ' ' . $row['requester_last_name']);
            
            $row['url'] = 'requests.php?search=' . urlencode($query);
            $row['title'] = $row['purpose'] ? substr($row['purpose'], 0, 50) . '...' : 'Borrow Request';
            $row['subtitle'] = $row['asset_description'] ? $row['asset_description'] : 'Asset ID: ' . $row['asset_id'];
            $row['badge'] = ucfirst($row['status']);
            $row['badge_class'] = getRequestStatusBadgeClass($row['status']);
            $row['destination'] = 'Requests Page';
            $row['requester'] = $requester_name;
            $results[] = $row;
        }
        $stmt->close();
    }
    
    return $results;
}

function getRequestStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'bg-warning';
        case 'approved': return 'bg-success';
        case 'denied': return 'bg-danger';
        case 'borrowed': return 'bg-primary';
        case 'returned': return 'bg-info';
        case 'cancelled': return 'bg-secondary';
        default: return 'bg-secondary';
    }
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
