<?php
session_start();
require_once '../../config.php';

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
    
    // Get parameters
    $query = $_GET['q'] ?? '';
    $debug['query'] = $query;
    
    if (empty($query) || strlen($query) < 2) {
        echo json_encode([
            'success' => false, 
            'message' => 'Query too short: ' . strlen($query) . ' characters',
            'debug' => $debug
        ]);
        exit();
    }
    
    // Simple test query - just count assets
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    $sql = "SELECT COUNT(*) as count FROM asset_items WHERE description LIKE ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode([
            'success' => false, 
            'message' => 'Prepare failed: ' . $conn->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    $debug['count'] = $count;
    
    // Return success with mock data if we found anything
    if ($count > 0) {
        echo json_encode([
            'success' => true,
            'results' => [
                [
                    'type' => 'asset',
                    'title' => 'Found ' . $count . ' assets matching "' . $query . '"',
                    'subtitle' => 'Click to view all results',
                    'badge' => 'Test',
                    'badge_class' => 'bg-info',
                    'url' => 'office_assets.php?search=' . urlencode($query)
                ]
            ],
            'total' => 1,
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'results' => [],
            'total' => 0,
            'debug' => $debug
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => ['trace' => $e->getTraceAsString()]
    ]);
}
?>
