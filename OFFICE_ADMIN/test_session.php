<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Debug session information
$session_debug = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'logged_in' => $_SESSION['logged_in'] ?? false,
    'role' => $_SESSION['role'] ?? null,
    'office_id' => $_SESSION['office_id'] ?? null,
    'user_id' => $_SESSION['user_id'] ?? null,
    'first_name' => $_SESSION['first_name'] ?? null,
    'last_name' => $_SESSION['last_name'] ?? null,
    'session_data_keys' => array_keys($_SESSION)
];

// Check database connection
$db_debug = [
    'conn_exists' => $conn ? true : false,
    'conn_error' => $conn->connect_error ?? null
];

echo json_encode([
    'success' => true,
    'session' => $session_debug,
    'database' => $db_debug,
    'cookies' => $_COOKIE
]);
?>
