<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    die("You must be logged in as an office admin to run this script.");
}

$user_id = $_SESSION['user_id'];
$office_id = $_SESSION['office_id'];

echo "<h2>Notification Debug Information</h2>";
echo "<p><strong>User ID:</strong> $user_id</p>";
echo "<p><strong>Office ID:</strong> $office_id</p>";
echo "<p><strong>Role:</strong> " . $_SESSION['role'] . "</p>";

// Test the notifications_handler.php directly
echo "<h3>Testing notifications_handler.php API</h3>";

// Test get_count endpoint
echo "<h4>1. Testing get_count endpoint:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/PIMS/OFFICE_ADMIN/notifications_handler.php?action=get_count');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $http_code</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Test get_notifications endpoint
echo "<h4>2. Testing get_notifications endpoint:</h4>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/PIMS/OFFICE_ADMIN/notifications_handler.php?action=get_notifications&limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Status:</strong> $http_code</p>";
echo "<p><strong>Response:</strong> " . htmlspecialchars($response) . "</p>";

// Check database directly
echo "<h3>Database Check</h3>";

// Count all notifications for this user
$count_sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p><strong>Total notifications in DB:</strong> " . $row['total'] . "</p>";

// Count unread notifications
$unread_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "<p><strong>Unread notifications in DB:</strong> " . $row['unread'] . "</p>";

// Show recent notifications
echo "<h4>Recent Notifications:</h4>";
$recent_sql = "SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No notifications found in database.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
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

// JavaScript Debug
echo "<h3>JavaScript Debug</h3>";
echo "<p>Open your browser console and run these commands:</p>";
echo "<pre>";
echo "// Check if elements exist
console.log('Badge element:', document.getElementById('notificationBadge'));
console.log('Dropdown element:', document.getElementById('notificationDropdown'));
console.log('List element:', document.getElementById('notificationList'));

// Manually update badge
updateNotificationBadge();

// Manually load notifications
loadNotifications();

// Check session
console.log('Session cookie:', document.cookie);
</pre>";

// Test session
echo "<h3>Session Check</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
var_export($_SESSION, true);
echo "</pre>";

echo "<hr>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
echo "<p><a href='notifications.php'>View All Notifications</a></p>";
echo "<p><a href='create_test_notifications.php'>Create More Test Notifications</a></p>";
?>

<script>
// Auto-run some checks
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Notification Debug ===');
    
    // Check elements
    const badge = document.getElementById('notificationBadge');
    const dropdown = document.getElementById('notificationDropdown');
    const list = document.getElementById('notificationList');
    
    console.log('Badge element:', badge);
    console.log('Dropdown element:', dropdown);
    console.log('List element:', list);
    
    // Check badge styles
    if (badge) {
        console.log('Badge display style:', badge.style.display);
        console.log('Badge content:', badge.textContent);
        console.log('Badge visible:', badge.offsetWidth > 0 && badge.offsetHeight > 0);
    }
    
    // Manually trigger update
    setTimeout(() => {
        console.log('Manually updating badge...');
        if (typeof updateNotificationBadge === 'function') {
            updateNotificationBadge();
        } else {
            console.error('updateNotificationBadge function not found');
        }
    }, 1000);
});
</script>
