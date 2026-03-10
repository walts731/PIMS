<?php
// Start session
session_start();

// Simple check
echo "<h2>Session Status</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not logged in') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";

if (!isset($_SESSION['user_id'])) {
    echo "<h3 style='color: red;'>ERROR: Not logged in!</h3>";
    echo "<p>Please <a href='../index.php'>login first</a></p>";
    exit();
}

if ($_SESSION['role'] !== 'office_admin') {
    echo "<h3 style='color: red;'>ERROR: Not an office admin!</h3>";
    exit();
}

// Load config
try {
    require_once '../config.php';
    echo "<h3 style='color: green;'>✅ Config loaded successfully</h3>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Config error: " . $e->getMessage() . "</h3>";
    exit();
}

// Check database
if ($conn->connect_error) {
    echo "<h3 style='color: red;'>❌ Database error: " . $conn->connect_error . "</h3>";
    exit();
} else {
    echo "<h3 style='color: green;'>✅ Database connected</h3>";
}

// Create a simple notification
$user_id = $_SESSION['user_id'];
$title = "Test Notification " . date('H:i:s');
$message = "Created at " . date('Y-m-d H:i:s');

$sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iss', $user_id, $title, $message);

if ($stmt->execute()) {
    $notification_id = $conn->insert_id;
    echo "<h3 style='color: green;'>✅ Notification created! ID: $notification_id</h3>";
} else {
    echo "<h3 style='color: red;'>❌ Failed to create notification: " . $stmt->error . "</h3>";
}

// Count notifications
$count_sql = "SELECT COUNT(*) as total, SUM(is_read = 0) as unread FROM notifications WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();

echo "<h3>Notification Counts:</h3>";
echo "Total: " . $count_row['total'] . "<br>";
echo "Unread: " . $count_row['unread'] . "<br>";

// Test API directly
echo "<h3>API Test:</h3>";
$api_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/PIMS/OFFICE_ADMIN/notifications_handler.php?action=get_count';

echo "<p>Testing: $api_url</p>";

// Use cURL if available
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Status: $http_code</p>";
    if ($error) {
        echo "<p style='color: red;'>CURL Error: $error</p>";
    } else {
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>CURL not available</p>";
}

echo "<hr>";
echo "<h3>Manual Test Links:</h3>";
echo "<p><a href='notifications_handler.php?action=get_count' target='_blank'>Test get_count API</a></p>";
echo "<p><a href='notifications_handler.php?action=get_notifications&limit=5' target='_blank'>Test get_notifications API</a></p>";
echo "<p><a href='dashboard.php' target='_blank'>Go to Dashboard</a></p>";
echo "<p><a href='notifications.php' target='_blank'>View All Notifications</a></p>";

// Show recent notifications
echo "<h3>Recent Notifications:</h3>";
$recent_sql = "SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param('i', $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

if ($recent_result->num_rows === 0) {
    echo "<p>No notifications found</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    
    while ($row = $recent_result->fetch_assoc()) {
        $read_status = $row['is_read'] ? 'Yes' : 'No';
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>$read_status</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
