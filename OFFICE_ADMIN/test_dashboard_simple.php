<?php
session_start();
require_once '../config.php';

// Simple dashboard test without complex includes
header('Content-Type: application/json');

try {
    // Basic checks
    $debug = [];
    
    // Session check
    $debug['session'] = [
        'logged_in' => $_SESSION['logged_in'] ?? false,
        'role' => $_SESSION['role'] ?? null,
        'office_id' => $_SESSION['office_id'] ?? null
    ];
    
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not logged in',
            'debug' => $debug
        ]);
        exit();
    }
    
    if ($_SESSION['role'] !== 'office_admin') {
        echo json_encode([
            'success' => false, 
            'message' => 'Wrong role: ' . $_SESSION['role'],
            'debug' => $debug
        ]);
        exit();
    }
    
    // Database check
    $debug['database'] = [
        'conn_exists' => $conn ? true : false,
        'conn_error' => $conn->connect_error ?? null
    ];
    
    if (!$conn) {
        echo json_encode([
            'success' => false, 
            'message' => 'No database connection',
            'debug' => $debug
        ]);
        exit();
    }
    
    if ($conn->connect_error) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database connection error: ' . $conn->connect_error,
            'debug' => $debug
        ]);
        exit();
    }
    
    // Test simple query
    $sql = "SELECT COUNT(*) as count FROM asset_items LIMIT 1";
    $result = $conn->query($sql);
    $count = $result->fetch_assoc()['count'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Simple dashboard test successful',
        'data' => [
            'asset_count' => $count,
            'sql' => $sql
        ],
        'debug' => $debug
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => ['trace' => $e->getTraceAsString()]
    ]);
}
?>
