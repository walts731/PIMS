<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}
?>
