<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

header('Content-Type: application/json');

// Debug logging
error_log("DEBUG: get_subcategories_simple.php called");
error_log("DEBUG: GET data: " . print_r($_GET, true));

// Get category ID from request
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

error_log("DEBUG: Category ID received: $category_id");

if ($category_id <= 0) {
    error_log("DEBUG: Invalid category ID");
    echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    exit();
}

try {
    // Check database connection
    if (!$conn) {
        error_log("DEBUG: Database connection failed");
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit();
    }
    
    error_log("DEBUG: Database connection successful");
    
    // First, let's test a simple query to make sure everything works
    $test_query = "SELECT COUNT(*) as count FROM asset_categories WHERE id = ?";
    $test_stmt = $conn->prepare($test_query);
    $test_stmt->bind_param("i", $category_id);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();
    $test_row = $test_result->fetch_assoc();
    
    if ($test_row['count'] == 0) {
        error_log("DEBUG: Category ID $category_id does not exist");
        echo json_encode(['success' => false, 'error' => "Category ID $category_id not found"]);
        exit();
    }
    
    error_log("DEBUG: Category ID $category_id exists");
    
    // Get subcategories for the selected category
    $sql = "SELECT id, sub_category_code, sub_category_name 
            FROM asset_sub_categories 
            WHERE asset_categories_id = ? AND status = 'active' 
            ORDER BY sub_category_code";
    
    error_log("DEBUG: SQL query: $sql with category_id = $category_id");
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("DEBUG: Prepare failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Query preparation failed: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("i", $category_id);
    if (!$stmt->execute()) {
        error_log("DEBUG: Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Query execution failed: ' . $stmt->error]);
        exit();
    }
    
    $result = $stmt->get_result();
    
    $subcategories = [];
    while ($row = $result->fetch_assoc()) {
        $subcategories[] = [
            'id' => $row['id'],
            'code' => $row['sub_category_code'],
            'name' => $row['sub_category_name']
        ];
    }
    
    error_log("DEBUG: Found " . count($subcategories) . " subcategories");
    error_log("DEBUG: Subcategories: " . print_r($subcategories, true));
    
    echo json_encode([
        'success' => true, 
        'subcategories' => $subcategories,
        'debug' => [
            'category_id' => $category_id,
            'count' => count($subcategories),
            'message' => count($subcategories) > 0 ? 'Subcategories found' : 'No active subcategories for this category'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("DEBUG: Exception caught: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

if (isset($stmt)) {
    $stmt->close();
}
if (isset($test_stmt)) {
    $test_stmt->close();
}
?>
