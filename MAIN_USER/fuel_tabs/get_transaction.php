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
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel', 'main_user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

$transaction_id = $_GET['id'] ?? 0;

if ($transaction_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid transaction ID']);
    exit();
}

try {
    $sql = "SELECT 
              id,
              transaction_type,
              transaction_date,
              quantity,
              fuel_type,
              supplier,
              vehicle_equipment,
              purpose,
              tank_number,
              odometer_reading,
              driver_name,
              department,
              user_id,
              created_at
           FROM fuel_transactions 
           WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
