<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: red_tags.php');
    exit();
}

// Validate required fields
if (!isset($_POST['tag_ids']) || empty($_POST['tag_ids']) || 
    !isset($_POST['disposal_date']) || empty($_POST['disposal_date'])) {
    
    $_SESSION['error'] = 'Missing required fields. Please fill all required information.';
    header('Location: red_tags.php');
    exit();
}

try {
    $tag_ids = explode(',', $_POST['tag_ids']);
    $disposal_reason = trim($_POST['disposal_reason']);
    $disposal_date = $_POST['disposal_date'];
    $user_id = $_SESSION['user_id'];
    
    // Debug logging
    error_log("Bulk dispose request - Tag IDs: " . $_POST['tag_ids']);
    error_log("Bulk dispose request - Reason: " . $disposal_reason);
    error_log("Bulk dispose request - Date: " . $disposal_date);
    
    // Validate tag IDs
    $valid_tag_ids = [];
    foreach ($tag_ids as $tag_id) {
        $tag_id = trim($tag_id);
        if (is_numeric($tag_id) && $tag_id > 0) {
            $valid_tag_ids[] = (int)$tag_id;
        }
    }
    
    error_log("Valid tag IDs: " . implode(', ', $valid_tag_ids));
    
    if (empty($valid_tag_ids)) {
        $_SESSION['error'] = 'Invalid red tag IDs selected.';
        header('Location: red_tags.php');
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    $disposed_count = 0;
    $error_count = 0;
    
    foreach ($valid_tag_ids as $tag_id) {
        try {
            // Check if red tag exists (remove action restrictions for testing)
            $check_sql = "SELECT id, control_no, item_description, asset_item_id, action
                        FROM red_tags 
                        WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $tag_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            error_log("Checking red tag ID $tag_id - Found rows: " . $result->num_rows);
            
            if ($result->num_rows === 0) {
                error_log("ERROR: Red tag ID $tag_id does not exist in database");
                $error_count++;
                continue;
            }
            
            $red_tag = $result->fetch_assoc();
            
            error_log("Red tag found - ID: {$red_tag['id']}, Control No: {$red_tag['control_no']}, Current Action: " . ($red_tag['action'] ?? 'NULL'));
            
            // Check if already disposed
            if (strtolower($red_tag['action'] ?? '') === 'disposed') {
                error_log("Red tag ID $tag_id is already disposed - skipping");
                $error_count++;
                continue;
            }
            
            // Update red tag with disposal information
            $update_sql = "UPDATE red_tags 
                         SET action = 'disposed',
                             disposal_date = ?,
                             updated_by = ?
                         WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sii", $disposal_date, $user_id, $tag_id);
            
            error_log("Updating red tag ID $tag_id with disposal info");
            
            if ($stmt->execute()) {
                error_log("SUCCESS: Red tag ID $tag_id updated successfully");
                
                // Update asset item status if asset_item_id exists
                if (!empty($red_tag['asset_item_id'])) {
                    $update_asset_sql = "UPDATE asset_items 
                                      SET status = 'disposed',
                                          updated_at = NOW(),
                                          updated_by = ?
                                      WHERE id = ?";
                    $stmt = $conn->prepare($update_asset_sql);
                    $stmt->bind_param("ii", $user_id, $red_tag['asset_item_id']);
                    $stmt->execute();
                    error_log("Updated asset item ID {$red_tag['asset_item_id']} to disposed status");
                }
                
                // Log the disposal action
                logSystemAction($user_id, 'bulk_dispose', 'red_tags', 
                    "Bulk disposed red tag ID: {$red_tag['id']}, Control No: {$red_tag['control_no']}");
                
                $disposed_count++;
            } else {
                error_log("ERROR: Failed to update red tag ID $tag_id - " . $stmt->error);
                $error_count++;
            }
            
        } catch (Exception $e) {
            error_log("Error disposing red tag ID $tag_id: " . $e->getMessage());
            $error_count++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("Bulk dispose completed - Disposed count: $disposed_count, Error count: $error_count");
    
    // Set appropriate message
    if ($disposed_count > 0) {
        if ($error_count > 0) {
            $_SESSION['success'] = "Successfully disposed $disposed_count red tag(s). $error_count item(s) could not be disposed.";
        } else {
            $_SESSION['success'] = "Successfully disposed $disposed_count red tag(s). All items have been marked as disposed.";
        }
    } else {
        error_log("ERROR: No red tags were disposed - disposed_count: $disposed_count, error_count: $error_count");
        $_SESSION['error'] = "No red tags were disposed. Please check your selection and try again.";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Bulk disposal error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred during bulk disposal. Please try again.';
}

// Regenerate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Redirect back to red tags page
header('Location: red_tags.php');
exit();
?>
