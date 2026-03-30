<?php
session_start();
require_once '../config.php';

// Simple session check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die('Not logged in');
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    die('Access denied');
}

header('Content-Type: application/json');

try {
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Connection not established'));
    }
    
    $user_office_id = $_SESSION['office_id'];
    
    // Check if office_id column exists in asset_items
    $office_id_column_exists = false;
    $column_check_sql = "SHOW COLUMNS FROM asset_items LIKE 'office_id'";
    $column_result = $conn->query($column_check_sql);
    if ($column_result && $column_result->num_rows > 0) {
        $office_id_column_exists = true;
    }
    
    // Test asset items query
    if ($office_id_column_exists) {
        $sql = "SELECT COUNT(*) as total_assets FROM asset_items WHERE office_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_office_id);
    } else {
        $sql = "SELECT COUNT(*) as total_assets FROM asset_items";
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $asset_count = $result->fetch_assoc()['total_assets'];
    $stmt->close();
    
    // Test borrow requests query
    $sql = "SELECT COUNT(*) as total_requests FROM borrow_requests WHERE requested_by_office = ? OR requested_to_office = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_office_id, $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request_count = $result->fetch_assoc()['total_requests'];
    $stmt->close();
    
    // Test users query
    $sql = "SELECT COUNT(*) as total_users FROM users WHERE office_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_count = $result->fetch_assoc()['total_users'];
    $stmt->close();
    
    // Get sample data
    if ($office_id_column_exists) {
        $sql = "SELECT description, model, status FROM asset_items WHERE office_id = ? LIMIT 3";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_office_id);
    } else {
        $sql = "SELECT description, model, status FROM asset_items LIMIT 3";
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $sample_assets = [];
    while ($row = $result->fetch_assoc()) {
        $sample_assets[] = $row;
    }
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'office_id' => $user_office_id,
            'office_id_column_exists' => $office_id_column_exists,
            'asset_count' => $asset_count,
            'request_count' => $request_count,
            'user_count' => $user_count,
            'sample_assets' => $sample_assets
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
