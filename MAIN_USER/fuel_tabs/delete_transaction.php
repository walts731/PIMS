<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel', 'main_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = $data['id'] ?? 0;
$action = $data['action'] ?? '';

if ($action !== 'delete' || $transaction_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get transaction details before deletion for logging and inventory adjustment
    $get_sql = "SELECT transaction_type, quantity, tank_number, fuel_type FROM fuel_transactions WHERE id = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param('i', $transaction_id);
    $get_stmt->execute();
    $transaction_result = $get_stmt->get_result();
    
    if (!$transaction_data = $transaction_result->fetch_assoc()) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit();
    }
    
    // Delete the transaction
    $delete_sql = "DELETE FROM fuel_transactions WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('i', $transaction_id);
    
    if ($delete_stmt->execute()) {
        // Adjust inventory if tank was specified
        if (!empty($transaction_data['tank_number'])) {
            if ($transaction_data['transaction_type'] === 'IN') {
                // Remove fuel from inventory (reverse the addition)
                $update_sql = "UPDATE fuel_inventory SET current_level = current_level - ? WHERE tank_number = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ds', $transaction_data['quantity'], $transaction_data['tank_number']);
                $update_stmt->execute();
                $update_stmt->close();
            } elseif ($transaction_data['transaction_type'] === 'OUT') {
                // Add fuel back to inventory (reverse the removal)
                $update_sql = "UPDATE fuel_inventory SET current_level = current_level + ? WHERE tank_number = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ds', $transaction_data['quantity'], $transaction_data['tank_number']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        // Log the deletion
        logSystemAction($_SESSION['user_id'], 'delete', 'fuel_transaction', 
                       "Deleted {$transaction_data['transaction_type']} transaction ID: {$transaction_id}, {$transaction_data['quantity']}L of {$transaction_data['fuel_type']}");
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting transaction: ' . $delete_stmt->error]);
    }
    
    $delete_stmt->close();
    $get_stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
