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
        if (strtolower($red_tag['action']) !== 'disposal' && strtolower($red_tag['action']) !== 'dispose') {
            throw new Exception('This red tag is not marked for disposal.');
        }
        
        // Update red tag status to disposed
        $update_sql = "UPDATE red_tags SET 
                       action = 'disposed',
                       disposal_date = ?,
                       disposal_reason = ?,
                       updated_at = CURRENT_TIMESTAMP,
                       updated_by = ?
                       WHERE id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssii", $disposal_date, $disposal_reason, $_SESSION['user_id'], $red_tag_id);
        $stmt->execute();
        $stmt->close();
        
        // If there's an associated asset item, update its status
        if ($red_tag['asset_item_id']) {
            // Check if this is a component disposal
            if (isset($red_tag['component_type']) && $red_tag['component_type'] !== 'main_asset') {
                // Update component status to disposed
                if ($red_tag['component_type'] === 'peripheral') {
                    // Update peripheral status in peripherals table using peripheral_id
                    $update_component_sql = "UPDATE peripherals SET 
                                           status = 'disposed',
                                           updated_at = CURRENT_TIMESTAMP
                                           WHERE id = ?";
                    
                    $stmt = $conn->prepare($update_component_sql);
                    $stmt->bind_param("i", $red_tag['peripheral_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log component disposal
                    logSystemAction(
                        $_SESSION['user_id'], 
                        'dispose_component', 
                        'red_tag', 
                        "Disposed peripheral component ID {$red_tag['peripheral_id']} for asset ID {$red_tag['asset_item_id']} - {$red_tag['item_description']} (Reason: {$disposal_reason})"
                    );
                    
                } elseif ($red_tag['component_type'] === 'monitor') {
                    // Legacy support for monitor components
                    $update_component_sql = "UPDATE asset_desktop_computers SET 
                                           monitor_status = 'disposed',
                                           updated_at = CURRENT_TIMESTAMP
                                           WHERE asset_item_id = ?";
                    
                    $stmt = $conn->prepare($update_component_sql);
                    $stmt->bind_param("i", $red_tag['asset_item_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log component disposal
                    logSystemAction(
                        $_SESSION['user_id'], 
                        'dispose_component', 
                        'red_tag', 
                        "Disposed monitor component for asset ID {$red_tag['asset_item_id']} - {$red_tag['item_description']} (Reason: {$disposal_reason})"
                    );
                    
                } elseif ($red_tag['component_type'] === 'ups') {
                    // Legacy support for UPS components
                    $update_component_sql = "UPDATE asset_desktop_computers SET 
                                           ups_status = 'disposed',
                                           updated_at = CURRENT_TIMESTAMP
                                           WHERE asset_item_id = ?";
                    
                    $stmt = $conn->prepare($update_component_sql);
                    $stmt->bind_param("i", $red_tag['asset_item_id']);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Log component disposal
                    logSystemAction(
                        $_SESSION['user_id'], 
                        'dispose_component', 
                        'red_tag', 
                        "Disposed UPS component for asset ID {$red_tag['asset_item_id']} - {$red_tag['item_description']} (Reason: {$disposal_reason})"
                    );
                }
                
            } else {
                // Update main asset status (original logic)
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
        
        // Check if asset is red_tagged or unserviceable
        $current_status = strtolower(trim($asset['status']));
        if ($current_status !== 'red_tagged' && $current_status !== 'unserviceable') {
            throw new Exception('Only red-tagged or unserviceable assets can be disposed. Current status: ' . $asset['status']);
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

        // Also update any active red tags for this asset item to 'disposed'
        $update_redtag_sql = "UPDATE red_tags SET 
                             action = 'disposed',
                             disposal_date = ?,
                             disposal_reason = ?,
                             updated_at = CURRENT_TIMESTAMP,
                             updated_by = ?
                             WHERE asset_item_id = ? AND action != 'disposed'";
        $stmt = $conn->prepare($update_redtag_sql);
        $stmt->bind_param("ssii", $disposal_date, $disposal_reason, $_SESSION['user_id'], $asset_item_id);
        $stmt->execute();
        $stmt->close();
        
        $success_message = "Asset disposed successfully: {$asset['description']}";
        $redirect_page = "view_asset_item.php?id={$asset_item_id}";
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = $success_message;
    
    // Create notifications for MAIN_USER
    if ($red_tag_id > 0) {
        createMainUserNotificationsForDisposal($red_tag['item_description'], $disposal_reason, $red_tag['asset_item_id']);
    } elseif ($asset_item_id > 0) {
        createMainUserNotificationsForDisposal($asset['description'], $disposal_reason, $asset_item_id);
    }
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    error_log("Disposal error: " . $e->getMessage());
    $_SESSION['error'] = "Error disposing item: " . $e->getMessage();
}

// Function to create notifications for MAIN_USER when items are disposed
function createMainUserNotificationsForDisposal($item_description, $reason, $asset_item_id) {
    global $conn;
    
    // Get all MAIN_USER users
    $main_users_query = "SELECT id FROM users WHERE role = 'main_user' AND is_active = 1";
    $main_users_result = $conn->query($main_users_query);
    
    if ($main_users_result && $main_users_result->num_rows > 0) {
        while ($main_user = $main_users_result->fetch_assoc()) {
            $user_id = $main_user['id'];
            
            $title = "Asset Item Disposed";
            $message = "Asset Item '{$item_description}' has been marked as disposed. Reason: {$reason}";
            $type = "danger";
            $related_id = $asset_item_id;
            $related_type = "asset"; // Link back to the asset item view
            
            // Insert notification
            $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
            $stmt->execute();
        }
    }
}

// Check if this is an AJAX request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    
    if (isset($_SESSION['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $_SESSION['error']
        ]);
        unset($_SESSION['error']);
    } else {
        echo json_encode([
            'success' => true,
            'message' => $_SESSION['success'] ?? 'Disposal completed successfully',
            'redirect' => $redirect_page
        ]);
        unset($_SESSION['success']);
    }
} else {
    // Redirect back to appropriate page for regular form submissions
    header('Location: ' . $redirect_page);
}

exit();
?>
