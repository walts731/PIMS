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
    // Search asset items with office information, excluding no_tag and unserviceable status
    $sql = "SELECT ai.id, ai.description, ai.value, ai.acquisition_date, ai.status, 
                   o.office_name 
            FROM asset_items ai 
            LEFT JOIN offices o ON ai.office_id = o.id 
            WHERE (ai.description LIKE ? OR ai.id LIKE ?) 
            AND ai.status NOT IN ('no_tag', 'unserviceable')
            ORDER BY ai.description
            LIMIT 10";
    
    $searchTerm = "%$query%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
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
            'office_name' => $row['office_name']
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
