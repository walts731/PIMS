<?php
// Simulate a POST request to test the API
$_POST['request_id'] = 1; // Test with a valid request ID

// Start session (simulate logged in user)
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 1; // Adjust to your office ID
$_SESSION['user_id'] = 1; // Adjust to your user ID

// Capture output
ob_start();
include 'api/cancel_request.php';
$output = ob_get_clean();

echo "<h2>API Test Results</h2>";
echo "<h3>Raw Output:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Check if output is valid JSON
$json_data = json_decode($output);
if ($json_data === null) {
    echo "<h3 style='color: red;'>❌ Invalid JSON Response</h3>";
    echo "<p>The API is not returning valid JSON. This indicates a PHP error.</p>";
    
    // Try to extract the actual error
    if (strpos($output, '<b>') !== false) {
        echo "<h3>Detected PHP Error:</h3>";
        echo "<p>The output contains HTML error tags, which means there's a PHP syntax or runtime error.</p>";
    }
} else {
    echo "<h3 style='color: green;'>✅ Valid JSON Response</h3>";
    echo "<pre>" . print_r($json_data, true) . "</pre>";
}
?>
