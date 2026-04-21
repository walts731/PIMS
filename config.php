<?php
date_default_timezone_set('Asia/Manila');
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims';

$conn = new mysqli($host, $username, $password, $database);

mysqli_query($conn, "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

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
