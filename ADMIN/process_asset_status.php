<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get and validate input
$asset_id = isset($_POST['asset_id']) ? intval($_POST['asset_id']) : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if ($asset_id === 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters']);
    exit();
}

// Validate status value
$valid_statuses = ['available', 'in_use', 'maintenance', 'disposed'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Update asset item status
    $update_sql = "UPDATE asset_items SET status = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $asset_id);
    $stmt->execute();
    
    // Check if update was successful
    if ($stmt->affected_rows === 0) {
        throw new Exception('Asset item not found or no changes made');
    }
    
    // Log the action
    $action_description = "Updated asset item status to '$status' for asset ID: $asset_id";
    logSystemAction($_SESSION['user_id'], $action_description, 'inventory', 'process_asset_status.php');
    
    // Create notification for MAIN_USER
    createMainUserAssetStatusNotification($asset_id, $status);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Asset status updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Log error
    error_log("Error updating asset status: " . $e->getMessage());
    
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();

// Function to create notifications for MAIN_USER when asset status is updated
function createMainUserAssetStatusNotification($asset_item_id, $new_status) {
    global $conn;
    
    // Get asset item details
    $item_query = "SELECT ai.description, a.description as asset_description 
                   FROM asset_items ai 
                   LEFT JOIN assets a ON ai.asset_id = a.id 
                   WHERE ai.id = ?";
    $item_stmt = $conn->prepare($item_query);
    $item_stmt->bind_param("i", $asset_item_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    
    if ($item_result && $item_row = $item_result->fetch_assoc()) {
        $item_description = $item_row['description'];
        $asset_description = $item_row['asset_description'];
        
        // Get all MAIN_USER users
        $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND status = 'active'";
        $main_users_result = $conn->query($main_users_query);
        
        if ($main_users_result && $main_users_result->num_rows > 0) {
            while ($main_user = $main_users_result->fetch_assoc()) {
                $user_id = $main_user['id'];
                $title = "Asset Status Updated";
                $message = "Asset '{$item_description}' status has been updated to '{$new_status}'.";
                $type = "info";
                $related_id = $asset_item_id;
                $related_type = "asset_item";
                
                // Insert notification
                $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
                $stmt->execute();
            }
        }
    }
    
    $item_stmt->close();
}
?>
