<?php
header('Content-Type: application/json');
require_once '../config.php';

// Get search query and employee ID
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// For dropdown functionality, we allow empty query if employee_id is provided
if (empty($employee_id)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);
    exit;
}

try {
    // Search serviceable assets assigned to the specific employee
    // If query is provided, search within assets; otherwise get all assets
    $sql = "SELECT ai.id, ai.description, ai.value, ai.acquisition_date, ai.status, 
                   ai.property_no, ai.inventory_tag, ai.created_at, 
                   o.office_name 
            FROM asset_items ai 
            LEFT JOIN offices o ON ai.office_id = o.id 
            WHERE ai.employee_id = ?";
    
    $params = [$employee_id];
    $types = "i";
    
    // Add search conditions if query is provided
    if (!empty($query)) {
        $sql .= " AND (ai.description LIKE ? OR ai.property_no LIKE ? OR ai.inventory_tag LIKE ?)";
        $searchTerm = "%$query%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= "sss";
    }
    
    $sql .= " AND ai.status = 'serviceable'
            AND ai.property_no IS NOT NULL 
            AND ai.property_no != ''
            AND ai.inventory_tag IS NOT NULL 
            AND ai.inventory_tag != ''
            AND ai.value IS NOT NULL 
            AND ai.value > 0
            ORDER BY ai.description
            LIMIT 50";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = [
            'id' => $row['id'],
            'description' => $row['description'],
            'value' => $row['value'],
            'acquisition_date' => $row['acquisition_date'],
            'status' => $row['status'],
            'property_no' => $row['property_no'],
            'inventory_tag' => $row['inventory_tag'],
            'created_at' => $row['created_at'],
            'office_name' => $row['office_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'assets' => $assets,
        'count' => count($assets),
        'employee_id' => $employee_id,
        'query' => $query
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
