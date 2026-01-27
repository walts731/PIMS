<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get item_id from request
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;

if ($item_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Get the current employee assigned to this asset item
$sql = "SELECT employee_id FROM asset_items WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'employee_id' => $row['employee_id']
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Asset item not found']);
}

$stmt->close();
?>
