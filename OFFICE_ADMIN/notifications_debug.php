<?php
// Debug version of notifications.php to identify the issue
session_start();

echo "<h2>Notifications Page Debug</h2>";

// Check session variables
echo "<h3>Session Status:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session exists:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No") . "</p>";

echo "<h3>Session Variables:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

if (!empty($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
} else {
    echo "<tr><td colspan='2'>No session variables set</td></tr>";
}

echo "</table>";

// Check authentication requirements
echo "<h3>Authentication Check:</h3>";
$user_id = $_SESSION['user_id'] ?? 'Not set';
$role = $_SESSION['role'] ?? 'Not set';

echo "<p><strong>User ID:</strong> " . htmlspecialchars($user_id) . "</p>";
echo "<p><strong>Role:</strong> " . htmlspecialchars($role) . "</p>";

$auth_check = (
    isset($_SESSION['user_id']) && 
    isset($_SESSION['role']) && 
    $_SESSION['role'] === 'office_admin'
);

echo "<p><strong>Auth Check Result:</strong> " . ($auth_check ? "✅ PASS" : "❌ FAIL") . "</p>";

if (!$auth_check) {
    echo "<h3>Why Authentication Failed:</h3>";
    if (!isset($_SESSION['user_id'])) {
        echo "<p>❌ User ID not set in session</p>";
    }
    if (!isset($_SESSION['role'])) {
        echo "<p>❌ Role not set in session</p>";
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'office_admin') {
        echo "<p>❌ Role is not 'office_admin'. Current role: " . htmlspecialchars($_SESSION['role']) . "</p>";
    }
    
    echo "<h3>Solution:</h3>";
    echo "<p>You need to log in as an office admin user. Available office admin users:</p>";
    
    // Show available office admin users
    require_once '../config.php';
    $sql = "SELECT id, username, role, office FROM users WHERE role = 'office_admin'";
    $result = $conn->query($sql);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Office</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['office']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><a href='../login.php'>Click here to log in</a></p>";
} else {
    echo "<p><strong>✅ Authentication successful! You should be able to see the notifications page.</strong></p>";
    
    // Check if notifications exist
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo "<h3>Your Notifications:</h3>";
    echo "<p><strong>Total notifications:</strong> " . $row['count'] . "</p>";
    
    if ($row['count'] > 0) {
        echo "<p><a href='notifications.php'>Click here to view your notifications</a></p>";
    } else {
        echo "<p>You don't have any notifications yet.</p>";
        echo "<p><a href='test_priority_notifications.php'>Click here to create test notifications</a></p>";
    }
}

$conn->close();
?>
