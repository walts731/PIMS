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

// Debug: Log all POST data
error_log("=== BULK DISPOSE DEBUG ===");
error_log("POST data: " . print_r($_POST, true));
error_log("Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));
error_log("POST CSRF token: " . ($_POST['csrf_token'] ?? 'NOT SET'));

// Verify CSRF token
if (!isset($_POST['csrf_token'])) {
    error_log("CSRF token not provided in POST");
    $_SESSION['error'] = 'Invalid request. CSRF token missing.';
    header('Location: red_tags.php');
    exit();
}

if (!isset($_SESSION['csrf_token'])) {
    error_log("CSRF token not set in session");
    $_SESSION['error'] = 'Invalid request. Session expired.';
    header('Location: red_tags.php');
    exit();
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF token mismatch - Session: " . $_SESSION['csrf_token'] . ", POST: " . $_POST['csrf_token']);
    $_SESSION['error'] = 'Invalid request. CSRF token mismatch.';
    header('Location: red_tags.php');
    exit();
}

error_log("CSRF token validation PASSED");

// Validate required fields
if (!isset($_POST['tag_ids']) || empty($_POST['tag_ids']) || 
    !isset($_POST['disposal_date']) || empty($_POST['disposal_date'])) {
    
    error_log("Missing required fields");
    error_log("Tag IDs: " . ($_POST['tag_ids'] ?? 'NOT SET'));
    error_log("Date: " . ($_POST['disposal_date'] ?? 'NOT SET'));
    
    $_SESSION['error'] = 'Missing required fields. Please fill all required information.';
    header('Location: red_tags.php');
    exit();
}

try {
    $tag_ids = explode(',', $_POST['tag_ids']);
    $disposal_reason = trim($_POST['disposal_reason']);
    $disposal_date = $_POST['disposal_date'];
    $user_id = $_SESSION['user_id'];
    
    error_log("Processing tag IDs: " . implode(', ', $tag_ids));
    
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
        error_log("No valid tag IDs found");
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
            // Simple check if red tag exists
            $check_sql = "SELECT COUNT(*) as count FROM red_tags WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $tag_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            error_log("Red tag ID $tag_id exists: " . $row['count']);
            
            if ($row['count'] == 0) {
                error_log("Red tag ID $tag_id does not exist");
                $error_count++;
                continue;
            }
            
            // Simple update without disposal reason requirement
            $update_sql = "UPDATE red_tags 
                         SET action = 'disposed',
                             disposal_date = ?,
                             updated_by = ?
                         WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sii", $disposal_date, $user_id, $tag_id);
            
            if ($stmt->execute()) {
                error_log("SUCCESS: Updated red tag ID $tag_id");
                $disposed_count++;
            } else {
                error_log("ERROR: Failed to update red tag ID $tag_id - " . $stmt->error);
                $error_count++;
            }
            
        } catch (Exception $e) {
            error_log("Exception disposing red tag ID $tag_id: " . $e->getMessage());
            $error_count++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    error_log("FINAL RESULTS - Disposed: $disposed_count, Errors: $error_count");
    
    // Set appropriate message
    if ($disposed_count > 0) {
        $_SESSION['success'] = "Successfully disposed $disposed_count red tag(s).";
    } else {
        $_SESSION['error'] = "No red tags were disposed. Disposed count: $disposed_count, Error count: $error_count. Please check your selection and try again.";
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Bulk disposal exception: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred during bulk disposal: ' . $e->getMessage();
}

// Regenerate CSRF token
// $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Redirect back to red tags page
header('Location: red_tags.php');
exit();
?>
