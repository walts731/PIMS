<?php
session_start();
require_once '../config.php';

header('Content-Type: text/plain');

echo "=== NOTIFICATION DEBUG ===\n\n";

// Check session
echo "SESSION CHECK:\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Office ID: " . ($_SESSION['office_id'] ?? 'NOT SET') . "\n\n";

// Check if user is office admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    echo "ERROR: Must be logged in as office admin\n";
    exit();
}

$user_id = $_SESSION['user_id'];

// Check database connection
echo "DATABASE CHECK:\n";
if ($conn && $conn->connect_error) {
    echo "ERROR: Database connection failed\n";
} else {
    echo "Database: Connected\n";
}

// Count notifications
$sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "Total notifications: " . $row['total'] . "\n";

// Count unread
$sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "Unread notifications: " . $row['unread'] . "\n\n";

// Test API directly
echo "API TEST:\n";
echo "Testing notifications_handler.php?action=get_count\n";

// Use file_get_contents for simple test
$api_url = 'http://' . $_SERVER['HTTP_HOST'] . '/PIMS/OFFICE_ADMIN/notifications_handler.php?action=get_count';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . session_name() . '=' . session_id()
    ]
]);

$response = file_get_contents($api_url, false, $context);
echo "API Response: " . $response . "\n";

echo "\n=== END DEBUG ===\n";
?>
