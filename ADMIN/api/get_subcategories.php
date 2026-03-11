<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// Debug logging
error_log("DEBUG: get_subcategories.php called");
error_log("DEBUG: Session data: " . print_r($_SESSION, true));
error_log("DEBUG: GET data: " . print_r($_GET, true));

// Check if user is logged in and has correct role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("DEBUG: User not logged in");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized - not logged in']);
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    error_log("DEBUG: User role not authorized: " . $_SESSION['role']);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized - invalid role']);
    exit();
}

header('Content-Type: application/json');

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
    
    // First, let's check if the table exists and get its structure
    $table_check = $conn->query("DESCRIBE asset_sub_categories");
    if (!$table_check) {
        error_log("DEBUG: Table asset_sub_categories does not exist or query failed: " . $conn->error);
        echo json_encode(['success' => false, 'error' => 'Table asset_sub_categories not found']);
        exit();
    }
    
    error_log("DEBUG: Table asset_sub_categories exists");
    
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
            'count' => count($subcategories)
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
?>
