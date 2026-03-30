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
    
    // Simple asset search without complex logic
    $searchTerm = '%' . $conn->real_escape_string($query) . '%';
    $sql = "SELECT 
                id, description, model, status, 'asset' as type
            FROM asset_items 
            WHERE description LIKE ? OR model LIKE ?
            LIMIT 3";
    
    $debug['sql'] = $sql;
    $debug['searchTerm'] = $searchTerm;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'success' => false, 
            'message' => 'Prepare failed: ' . $conn->error,
            'debug' => $debug
        ]);
        exit();
    }
    
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $row['url'] = 'office_assets.php?search=' . urlencode($query);
        $row['title'] = $row['description'];
        $row['subtitle'] = $row['model'] ?: 'No model';
        $row['badge'] = ucfirst($row['status']);
        $row['badge_class'] = 'bg-primary';
        $row['destination'] = 'Assets Page';
        $results[] = $row;
    }
    $stmt->close();
    
    $debug['results_count'] = count($results);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
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
