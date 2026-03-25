<?php
session_start();
require_once '../config.php';

// Simulate being logged in as office admin
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 1;

// Test the API directly
$request_id = $_GET['request_id'] ?? 1;

echo "<h2>Testing Request Details API</h2>";
echo "<p>Request ID: " . htmlspecialchars($request_id) . "</p>";

// Call the API
$url = "get_request_details_simple.php?request_id=" . $request_id;

echo "<h3>Direct API Call:</h3>";
echo "<p>URL: $url</p>";

// Use file_get_contents to test
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] ?? ""
    ]
]);

$response = file_get_contents($url, false, $context);

echo "<h3>Raw Response:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h3>Response Headers:</h3>";
if (isset($http_response_header)) {
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    foreach ($http_response_header as $header) {
        echo htmlspecialchars($header) . "\n";
    }
    echo "</pre>";
}

echo "<h3>JSON Parse Test:</h3>";
$json_data = json_decode($response, true);
if ($json_data === null) {
    echo "<p style='color: red;'>✗ JSON parse failed: " . json_last_error_msg() . "</p>";
    echo "<p>Last JSON error: " . json_last_error() . "</p>";
} else {
    echo "<p style='color: green;'>✓ JSON parse successful</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    echo htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT));
    echo "</pre>";
}

// Test with curl for more detailed info
echo "<h3>CURL Test:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? "");

$curl_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
echo htmlspecialchars($curl_response);
echo "</pre>";

echo "<p><a href='requests.php'>Back to Requests Page</a></p>";
?>
