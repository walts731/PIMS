<?php
// Start session and simulate login
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'office_admin';
$_SESSION['office_id'] = 5;

// Test direct API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/PIMS/OFFICE_ADMIN/api/search.php?q=laptop&limit=8');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response: $response\n";
echo "Session ID: " . session_id() . "\n";
?>
