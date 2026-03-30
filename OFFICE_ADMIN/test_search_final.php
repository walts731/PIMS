<?php
session_start();
require_once '../config.php';

// Simulate session for testing
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 5;

echo "<h2>Testing Fixed Search API</h2>";

// Test the search_fixed.php
echo "<h3>Direct API Test</h3>";
echo "<a href='api/search_fixed.php?q=laptop&limit=3' target='_blank'>Click here to test search_fixed.php</a><br>";

// Test with cURL simulation
echo "<h3>Manual API Test</h3>";
try {
    // Set up environment
    $_GET['q'] = 'laptop';
    $_GET['limit'] = 3;
    
    // Include the API
    ob_start();
    include 'api/search_fixed.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Try to parse JSON
    $data = json_decode($output, true);
    if ($data) {
        echo "<h3>JSON Parse Success!</h3>";
        echo "Success: " . ($data['success'] ? 'Yes' : 'No') . "<br>";
        echo "Total Results: " . $data['total'] . "<br>";
        
        if ($data['success'] && isset($data['results'])) {
            echo "<h4>Results:</h4>";
            foreach ($data['results'] as $result) {
                echo "- " . $result['title'] . " (" . $result['badge'] . ")<br>";
            }
        }
    } else {
        echo "<h3>JSON Parse Failed!</h3>";
        echo "Raw output shown above";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
