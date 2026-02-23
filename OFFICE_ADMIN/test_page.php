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

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

// Get office ID from session
$user_office_id = $_SESSION['office_id'] ?? null;

// Debug: Let's see what's happening
error_log("DEBUG: test_page.php - user_office_id: " . ($user_office_id ?? 'NULL'));
error_log("DEBUG: test_page.php - session data: " . print_r($_SESSION, true));

if (!$user_office_id) {
    $message = 'Office ID not found in session. Please log in again.';
    $message_type = 'danger';
} else {
    $message = 'Test page loaded successfully';
    $message_type = 'success';
    
    // Simple test query
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM asset_items WHERE office_id = ?");
        $stmt->bind_param("i", $user_office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $stmt->close();
    } catch (Exception $e) {
        $message = 'Database error: ' . $e->getMessage();
        $message_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Page - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .debug { background: #f0f0f0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <div class="debug">
        <h3>Session Debug</h3>
        <pre><?php echo 'Session office_id: ' . ($user_office_id ?? 'NULL'); ?></pre>
        
        <h3>Full Session Data</h3>
        <pre><?php echo print_r($_SESSION, true); ?></pre>
        
        <h3>Database Test</h3>
        <p>Asset count: <?php echo $count ?? 'Error'; ?></p>
        
        <h3>Message</h3>
        <p><?php echo $message ?? 'No message'; ?></p>
    </div>
</body>
</html>
