<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Get search query
$query = trim($_GET['q'] ?? '');
if (empty($query) || strlen($query) < 2) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');

try {
    $suggestions = [];
    
    // Check database connection
    if (!$conn || $conn->connect_error) {
        echo json_encode([]);
        exit();
    }
    
    // Search in asset_items
    $asset_stmt = $conn->prepare("
        SELECT ai.id, ai.description, ai.property_no, ai.inventory_tag,
               a.description as asset_description, o.office_name
        FROM asset_items ai
        LEFT JOIN assets a ON ai.asset_id = a.id
        LEFT JOIN offices o ON ai.office_id = o.id
        WHERE ai.description LIKE ? OR ai.property_no LIKE ? OR ai.inventory_tag LIKE ?
        ORDER BY ai.last_updated DESC
        LIMIT 5
    ");
    
    if (!$asset_stmt) {
        echo json_encode([]);
        exit();
    }
    
    $search_pattern = "%$query%";
    $asset_stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern);
    $asset_stmt->execute();
    $asset_result = $asset_stmt->get_result();
    
    while ($row = $asset_result->fetch_assoc()) {
        $text = $row['description'];
        $meta = [];
        
        if (!empty($row['property_no'])) {
            $meta[] = 'Prop No: ' . htmlspecialchars($row['property_no']);
        }
        if (!empty($row['inventory_tag'])) {
            $meta[] = 'Tag: ' . htmlspecialchars($row['inventory_tag']);
        }
        if (!empty($row['office_name'])) {
            $meta[] = htmlspecialchars($row['office_name']);
        }
        
        $suggestions[] = [
            'type' => 'asset',
            'text' => $text,
            'meta' => implode(' • ', $meta),
            'url' => 'view_asset_item.php?id=' . $row['id']
        ];
    }
    $asset_stmt->close();
    
    // Search in employees
    $employee_stmt = $conn->prepare("
        SELECT e.id, e.employee_no, e.firstname, e.lastname, e.position, e.employment_status,
               o.office_name
        FROM employees e
        LEFT JOIN offices o ON e.office_id = o.id
        WHERE e.employee_no LIKE ? OR e.firstname LIKE ? OR e.lastname LIKE ? 
           OR CONCAT(e.firstname, ' ', e.lastname) LIKE ? OR e.position LIKE ? OR e.employment_status LIKE ?
        ORDER BY e.lastname, e.firstname
        LIMIT 5
    ");
    
    if (!$employee_stmt) {
        echo json_encode([]);
        exit();
    }
    
    $employee_stmt->bind_param("ssssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $employee_stmt->execute();
    $employee_result = $employee_stmt->get_result();
    
    while ($row = $employee_result->fetch_assoc()) {
        $text = $row['firstname'] . ' ' . $row['lastname'];
        $meta = [];
        
        $meta[] = 'ID: ' . htmlspecialchars($row['employee_no']);
        if (!empty($row['position'])) {
            $meta[] = htmlspecialchars($row['position']);
        }
        if (!empty($row['employment_status'])) {
            $meta[] = htmlspecialchars($row['employment_status']);
        }
        if (!empty($row['office_name'])) {
            $meta[] = htmlspecialchars($row['office_name']);
        }
        
        $suggestions[] = [
            'type' => 'employee',
            'text' => $text,
            'meta' => implode(' • ', $meta),
            'url' => 'view_employee.php?id=' . $row['id']
        ];
    }
    $employee_stmt->close();
    
    echo json_encode($suggestions);
    
} catch (Exception $e) {
    error_log("Search Suggestions Error: " . $e->getMessage());
    echo json_encode([]);
}
?>
