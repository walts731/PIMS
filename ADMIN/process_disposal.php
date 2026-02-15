<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    $_SESSION['error'] = 'Access denied. You do not have permission to perform this action.';
    header('Location: red_tags.php');
    exit();
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = 'Session expired. Please login again.';
    header('Location: ../index.php');
    exit();
}

// Validate CSRF token (simplified for this example)
if (!isset($_POST['action']) || $_POST['action'] !== 'dispose') {
    $_SESSION['error'] = 'Invalid action.';
    header('Location: red_tags.php');
    exit();
}

// Check if this is a red tag disposal or direct asset item disposal
$red_tag_id = intval($_POST['red_tag_id'] ?? 0);
$asset_item_id = intval($_POST['asset_item_id'] ?? 0);
$disposal_reason = trim($_POST['disposal_reason'] ?? '');
$disposal_date = $_POST['disposal_date'] ?? '';

// Validate input
if ($red_tag_id === 0 && $asset_item_id === 0) {
    $_SESSION['error'] = 'Invalid red tag ID or asset item ID.';
    header('Location: red_tags.php');
    exit();
}

if (empty($disposal_reason)) {
    $_SESSION['error'] = 'Disposal reason is required.';
    header('Location: red_tags.php');
    exit();
}

if (empty($disposal_date)) {
    $_SESSION['error'] = 'Disposal date is required.';
    header('Location: red_tags.php');
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    $redirect_page = 'red_tags.php';
    $success_message = '';
    
    if ($red_tag_id > 0) {
        // Handle red tag disposal (existing logic)
        $red_tag_sql = "SELECT * FROM red_tags WHERE id = ?";
        $stmt = $conn->prepare($red_tag_sql);
        $stmt->bind_param("i", $red_tag_id);
        $stmt->execute();
        $red_tag_result = $stmt->get_result();
        
        if ($red_tag_result->num_rows === 0) {
            throw new Exception('Red tag not found.');
        }
        
        $red_tag = $red_tag_result->fetch_assoc();
        $stmt->close();
        
        // Check if action is disposal
        if (strtolower($red_tag['action']) !== 'disposal') {
            throw new Exception('This red tag is not marked for disposal.');
        }
        
        // Update red tag status to disposed
        $update_sql = "UPDATE red_tags SET 
                       action = 'disposed',
                       disposal_reason = ?,
                       disposal_date = ?,
                       updated_at = CURRENT_TIMESTAMP,
                       updated_by = ?
                       WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssii", $disposal_reason, $disposal_date, $_SESSION['user_id'], $red_tag_id);
        $stmt->execute();
        $stmt->close();
        
        // If there's an associated asset item, update its status
        if ($red_tag['asset_item_id']) {
            $update_asset_sql = "UPDATE asset_items SET 
                               status = 'disposed',
                               disposal_date = ?,
                               disposal_reason = ?,
                               last_updated = CURRENT_TIMESTAMP
                               WHERE id = ?";
            
            $stmt = $conn->prepare($update_asset_sql);
            $stmt->bind_param("ssi", $disposal_date, $disposal_reason, $red_tag['asset_item_id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Log the disposal action
        logSystemAction(
            $_SESSION['user_id'], 
            'dispose', 
            'red_tag', 
            "Disposed red tag: {$red_tag['control_no']} - {$red_tag['item_description']} (Reason: {$disposal_reason})"
        );
        
        $success_message = "Item disposed successfully. Control No: {$red_tag['control_no']}";
        
    } elseif ($asset_item_id > 0) {
        // Handle direct asset item disposal
        $asset_sql = "SELECT ai.*, a.description as asset_description FROM asset_items ai 
                      LEFT JOIN assets a ON ai.asset_id = a.id 
                      WHERE ai.id = ?";
        $stmt = $conn->prepare($asset_sql);
        $stmt->bind_param("i", $asset_item_id);
        $stmt->execute();
        $asset_result = $stmt->get_result();
        
        if ($asset_result->num_rows === 0) {
            throw new Exception('Asset item not found.');
        }
        
        $asset = $asset_result->fetch_assoc();
        $stmt->close();
        
        // Check if asset is red_tagged
        if ($asset['status'] !== 'red_tagged') {
            throw new Exception('Only red-tagged assets can be disposed.');
        }
        
        // Update asset item status to disposed
        $update_asset_sql = "UPDATE asset_items SET 
                           status = 'disposed',
                           disposal_date = ?,
                           disposal_reason = ?,
                           last_updated = CURRENT_TIMESTAMP
                           WHERE id = ?";
        
        $stmt = $conn->prepare($update_asset_sql);
        $stmt->bind_param("ssi", $disposal_date, $disposal_reason, $asset_item_id);
        $stmt->execute();
        $stmt->close();
        
        // Log the disposal action
        logSystemAction(
            $_SESSION['user_id'], 
            'dispose', 
            'asset_item', 
            "Disposed asset item: {$asset['description']} (Reason: {$disposal_reason})"
        );
        
        $success_message = "Asset disposed successfully: {$asset['description']}";
        $redirect_page = "view_asset_item.php?id={$asset_item_id}";
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = $success_message;
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    error_log("Disposal error: " . $e->getMessage());
    $_SESSION['error'] = "Error disposing item: " . $e->getMessage();
}

// Redirect back to appropriate page
header('Location: ' . $redirect_page);
exit();
?>
