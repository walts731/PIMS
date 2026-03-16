<?php
session_start();
require_once '../../config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

$tank_id = $_GET['id'] ?? 0;

if ($tank_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid tank ID']);
    exit();
}

try {
    $sql = "SELECT 
              id,
              tank_number,
              fuel_type,
              capacity,
              current_level,
              location,
              status,
              last_updated,
              created_at
           FROM fuel_inventory 
           WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $tank_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Tank not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
