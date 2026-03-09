<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';
require_once 'includes/notification_functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    die("You must be logged in as an office admin to run this script.");
}

echo "<h2>Creating Test Notifications for Office Admin</h2>";

// Get user info
$user_id = $_SESSION['user_id'];
$office_id = $_SESSION['office_id'];
echo "<p>User ID: $user_id, Office ID: $office_id</p>";

// Create several test notifications
$test_notifications = [
    [
        'title' => 'Welcome to Office Notifications!',
        'message' => 'This is your first notification. The system is working correctly!',
        'type' => 'success'
    ],
    [
        'title' => 'Low Stock Alert',
        'message' => 'Office supplies are running low. Please check inventory levels.',
        'type' => 'warning'
    ],
    [
        'title' => 'New Asset Request',
        'message' => 'A new asset request has been submitted for your approval.',
        'type' => 'info'
    ],
    [
        'title' => 'Maintenance Reminder',
        'message' => 'Computer equipment is due for scheduled maintenance.',
        'type' => 'warning'
    ],
    [
        'title' => 'System Update',
        'message' => 'The PIMS system has been updated with new features.',
        'type' => 'system'
    ]
];

$success_count = 0;
foreach ($test_notifications as $notif) {
    $notification_id = createOfficeNotification(
        $user_id,
        $notif['title'],
        $notif['message'],
        $notif['type']
    );
    
    if ($notification_id) {
        echo "<p>✅ Created: {$notif['title']} (ID: $notification_id)</p>";
        $success_count++;
    } else {
        echo "<p>❌ Failed to create: {$notif['title']}</p>";
    }
}

echo "<h3>Results:</h3>";
echo "<p>Successfully created $success_count out of " . count($test_notifications) . " test notifications.</p>";

// Check current notification count
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param('i', $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_count = $unread_row['unread_count'];

echo "<p>Current unread notification count: $unread_count</p>";

// Show recent notifications
echo "<h3>Recent Notifications:</h3>";
$recent_sql = "SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param('i', $user_id);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

if ($recent_result->num_rows === 0) {
    echo "<p>No notifications found.</p>";
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

echo "<hr>";
echo "<p><a href='test_notifications.php'>Go to Test Page</a></p>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
echo "<p><a href='notifications.php'>View All Notifications</a></p>";
?>
