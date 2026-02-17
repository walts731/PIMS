<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Handle GET request for subcategories
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_subcategories') {
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    
    if ($category_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        exit();
    }
    
    try {
        $subcategories = [];
        $stmt = $conn->prepare("SELECT id, sub_category_code, sub_category_name FROM asset_sub_categories WHERE asset_categories_id = ? AND status = 'active' ORDER BY sub_category_code");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
        
        $stmt->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'subcategories' => $subcategories]);
        exit();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// Handle GET request for offices
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_offices') {
    try {
        $offices = [];
        $stmt = $conn->prepare("SELECT id, office_name, office_code FROM offices WHERE status = 'active' ORDER BY office_name");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $offices[] = $row;
        }
        
        $stmt->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'offices' => $offices]);
        exit();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}

// If no valid action specified
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>
