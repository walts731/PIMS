<?php
/**
 * PIMS OFFICE_ADMIN Bootstrap Loader
 * 
 * Optimized dependency injection and initialization for OFFICE_ADMIN pages
 * Reduces redundant includes and improves performance
 */

// Set timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Core configuration and database
require_once dirname(__DIR__) . '../../config.php';

// System functions (includes its own config path detection)
require_once dirname(__DIR__) . '../../includes/system_functions.php';

// Core services
require_once dirname(__DIR__) . '../../includes/logger.php';
require_once __DIR__ . '/notification_functions.php';

// Security and session management
checkSessionTimeout();

// Authentication check
function authenticateOfficeAdmin() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../index.php');
        exit();
    }
    
    if ($_SESSION['role'] !== 'office_admin') {
        header('Location: ../index.php');
        exit();
    }
}

// Initialize common OFFICE_ADMIN environment
function initializeOfficeAdmin() {
    global $conn;
    
    // Authenticate user
    authenticateOfficeAdmin();
    
    // Set common headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    
    // Initialize common variables
    $office_id = $_SESSION['office_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;
    
    return [
        'office_id' => $office_id,
        'user_id' => $user_id,
        'conn' => $conn
    ];
}

// Auto-initialize for backward compatibility
return initializeOfficeAdmin();
