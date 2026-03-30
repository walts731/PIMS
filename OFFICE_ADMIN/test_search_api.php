<?php
session_start();
require_once '../config.php';

// Simulate session for testing
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 5;

header('Content-Type: application/json');

try {
    // Test search function
    $query = 'laptop';
    $limit = 8;
    
    echo json_encode([
        'success' => true,
        'message' => 'Testing search API',
        'query' => $query,
        'test' => 'Direct API test'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
