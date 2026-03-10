<?php
header('Content-Type: application/json');
require_once '../config.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Empty query']);
    exit;
}

try {
    // Search asset items with office and employee information, only serviceable assets with required fields
    $sql = "SELECT ai.id, ai.description, ai.value, ai.acquisition_date, ai.status, 
                   ai.property_no, ai.inventory_tag, ai.created_at, ai.employee_id,
                   o.office_name, 
                   CONCAT(e.firstname, ' ', e.lastname) as employee_name
            FROM asset_items ai 
            LEFT JOIN offices o ON ai.office_id = o.id 
            LEFT JOIN employees e ON ai.employee_id = e.id
            WHERE (ai.description LIKE ? OR ai.id LIKE ? OR ai.property_no LIKE ? OR ai.inventory_tag LIKE ?) 
            AND ai.status = 'serviceable'
            AND ai.property_no IS NOT NULL 
            AND ai.property_no != ''
            AND ai.inventory_tag IS NOT NULL 
            AND ai.inventory_tag != ''
            ORDER BY ai.description
            LIMIT 10";
    
    $searchTerm = "%$query%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
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
            'office_name' => $row['office_name'],
            'employee_id' => $row['employee_id'],
            'employee_name' => $row['employee_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'assets' => $assets,
        'count' => count($assets)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
