<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get POST data
$action = $_POST['action'] ?? '';
$item_id = intval($_POST['item_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);

// Validate action
if (!in_array($action, ['borrow', 'return'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Validate item ID
if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Validate user ID
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if item exists
    $check_query = "SELECT id, description, status, office_id FROM asset_items WHERE id = ? FOR UPDATE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $item_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit();
    }

    $item = $result->fetch_assoc();

    // Handle different actions
    if ($action === 'borrow') {
        // Check if item is serviceable
        if ($item['status'] !== 'serviceable') {
            echo json_encode(['success' => false, 'message' => 'Item is not available for borrowing']);
            exit();
        }

        // Update item status to borrowed
        $update_query = "UPDATE asset_items SET status = 'borrowed', employee_id = ?, last_updated = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $user_id, $item_id);
        $update_stmt->execute();

        // Log the action
        logSystemAction($user_id, 'borrow', 'asset_item', "Borrowed item: {$item['description']} (ID: {$item_id})");

        // Add to history
        $history_query = "INSERT INTO asset_item_history (item_id, action, details, user_id, created_at) VALUES (?, 'borrowed', 'Item borrowed by user', ?, NOW())";
        $action_type = 'borrowed';
        $success_message = 'Item borrowed successfully!';

    } elseif ($action === 'return') {
        // Check if item is borrowed
        if ($item['status'] !== 'borrowed') {
            echo json_encode(['success' => false, 'message' => 'Item is not currently borrowed']);
            exit();
        }

        // Update item status to serviceable and clear employee_id
        $update_query = "UPDATE asset_items SET status = 'serviceable', employee_id = NULL, last_updated = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $item_id);
        $update_stmt->execute();

        // Log the action
        logSystemAction($user_id, 'return', 'asset_item', "Returned item: {$item['description']} (ID: {$item_id})");

        // Add to history
        $history_query = "INSERT INTO asset_item_history (item_id, action, details, user_id, created_at) VALUES (?, 'returned', 'Item returned by user', ?, NOW())";
        $action_type = 'returned';
        $success_message = 'Item returned successfully!';
    }

    // Add to history if table exists
    try {
        $history_stmt = $conn->prepare($history_query);
        $history_stmt->bind_param("ii", $item_id, $user_id);
        $history_stmt->execute();
    } catch (Exception $e) {
        // History table might not exist, continue anyway
        error_log('History table error: ' . $e->getMessage());
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => $success_message]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    error_log('Borrow item error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

// Close statements if they exist
if (isset($check_stmt)) $check_stmt->close();
if (isset($update_stmt)) $update_stmt->close();
if (isset($history_stmt)) $history_stmt->close();
?>
