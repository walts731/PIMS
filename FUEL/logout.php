<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Start session
session_start();

// Try to include config and logger, but don't fail if they don't exist
try {
    if (file_exists('../config.php')) {
        require_once '../config.php';
    }
    
    if (file_exists('../includes/logger.php')) {
        require_once '../includes/logger.php';
        
        // Log logout if user is logged in and logger function exists
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && function_exists('logSystemAction')) {
            $user_id = $_SESSION['user_id'] ?? null;
            $user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $user_email = $_SESSION['email'] ?? '';
            $user_role = $_SESSION['role'] ?? '';
            
            // Log the logout event
            logSystemAction($user_id, 'logout', 'fuel_authentication', "Fuel user logged out: {$user_name} ({$user_email}) with role: {$user_role}");
        }
    }
} catch (Exception $e) {
    // Continue with logout even if logging fails
    error_log("Logout logging error: " . $e->getMessage());
}

// Destroy all session data
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

session_destroy();

// Redirect to main login page
header('Location: ../index.php');
exit();
?>
