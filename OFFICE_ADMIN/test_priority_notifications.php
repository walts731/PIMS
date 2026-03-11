<?php
/**
 * Test Priority Notifications Script
 * 
 * This script creates test notifications with different priority levels
 * to verify the priority system is working correctly
 */

require_once '../config.php';
require_once 'includes/notification_functions.php';

echo "<h2>Priority Notification Test</h2>";

// Test data - get current office admin user
$user_id = $_SESSION['user_id'] ?? 17; // Default to office admin user 17 if not logged in
$office_id = $_SESSION['office_id'] ?? 5; // Default to office 5 where user 17 is office admin

echo "<h3>Creating Test Notifications with Different Priorities...</h3>";

// Test 1: Critical Priority Notification
echo "<p>Creating Critical Priority Notification...</p>";
$critical_id = createOfficeNotification(
    $user_id, 
    'CRITICAL: System Security Alert', 
    'Unauthorized access attempt detected on the system. Immediate attention required.', 
    'error', 
    null, 
    'system', 
    'critical'
);

if ($critical_id) {
    echo "<p style='color: green;'>✅ Critical notification created (ID: $critical_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create critical notification</p>";
}

// Test 2: High Priority Notification
echo "<p>Creating High Priority Notification...</p>";
$high_id = createOfficeNotification(
    $user_id, 
    'URGENT: Low Stock Alert', 
    'Critical consumable "Printer Paper" is critically low. Only 5 units remaining.', 
    'warning', 
    1, 
    'consumable', 
    'high'
);

if ($high_id) {
    echo "<p style='color: green;'>✅ High priority notification created (ID: $high_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create high priority notification</p>";
}

// Test 3: Medium Priority Notification
echo "<p>Creating Medium Priority Notification...</p>";
$medium_id = createOfficeNotification(
    $user_id, 
    'New Asset Request', 
    'New request from IT Department for "Laptop Computer" approval required.', 
    'info', 
    1, 
    'request', 
    'medium'
);

if ($medium_id) {
    echo "<p style='color: green;'>✅ Medium priority notification created (ID: $medium_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create medium priority notification</p>";
}

// Test 4: Low Priority Notification
echo "<p>Creating Low Priority Notification...</p>";
$low_id = createOfficeNotification(
    $user_id, 
    'Information: System Update Completed', 
    'Monthly system maintenance has been completed successfully. All systems operational.', 
    'success', 
    null, 
    'system', 
    'low'
);

if ($low_id) {
    echo "<p style='color: green;'>✅ Low priority notification created (ID: $low_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create low priority notification</p>";
}

// Test 5: Test specific notification functions with priorities
echo "<h3>Testing Specific Notification Functions...</h3>";

// Test Low Stock Notification (should be High priority)
$low_stock_id = createLowStockNotification($office_id, 1, 'Test Consumable', 2, 10);
if ($low_stock_id) {
    echo "<p style='color: green;'>✅ Low stock notification created with High priority (ID: $low_stock_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create low stock notification</p>";
}

// Test Maintenance Notification (should be High priority)
$maintenance_id = createMaintenanceNotification($office_id, 1, 'Test Asset');
if ($maintenance_id) {
    echo "<p style='color: green;'>✅ Maintenance notification created with High priority (ID: $maintenance_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create maintenance notification</p>";
}

// Test New Request Notification (should be Medium priority)
$request_id = createNewRequestNotification($office_id, 1, 'Borrow', 'Test User');
if ($request_id) {
    echo "<p style='color: green;'>✅ New request notification created with Medium priority (ID: $request_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create new request notification</p>";
}

// Test Consumption Notification (should be Low priority)
$consumption_id = createConsumptionNotification($office_id, 1, 'Test Consumable', 5, 25);
if ($consumption_id) {
    echo "<p style='color: green;'>✅ Consumption notification created with Low priority (ID: $consumption_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create consumption notification</p>";
}

// Test Asset Status Change Notification
echo "<h3>Testing Asset Status Change Notifications...</h3>";

// Test maintenance status (should be High priority)
$maintenance_status_id = createAssetStatusNotification($office_id, 1, 'Test Asset', 'available', 'maintenance');
if ($maintenance_status_id) {
    echo "<p style='color: green;'>✅ Asset status change to maintenance created with High priority (ID: $maintenance_status_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create maintenance status notification</p>";
}

// Test disposed status (should be Critical priority)
$disposed_status_id = createAssetStatusNotification($office_id, 1, 'Test Asset', 'available', 'disposed');
if ($disposed_status_id) {
    echo "<p style='color: green;'>✅ Asset status change to disposed created with Critical priority (ID: $disposed_status_id)</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to create disposed status notification</p>";
}

// Verify the notifications were created with correct priorities
echo "<h3>Verification - Checking Created Notifications</h3>";

$sql = "SELECT id, title, type, priority, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY 
            CASE priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END ASC,
            created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Priority</th><th>Created</th></tr>";

while ($row = $result->fetch_assoc()) {
    $priority_color = '';
    switch($row['priority']) {
        case 'critical': $priority_color = 'color: #dc3545; font-weight: bold;'; break;
        case 'high': $priority_color = 'color: #fd7e14; font-weight: bold;'; break;
        case 'medium': $priority_color = 'color: #ffc107; font-weight: bold;'; break;
        case 'low': $priority_color = 'color: #28a745; font-weight: bold;'; break;
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . htmlspecialchars($row['type']) . "</td>";
    echo "<td style=\"$priority_color\">" . strtoupper(htmlspecialchars($row['priority'])) . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Count by priority
echo "<h3>Priority Distribution</h3>";

$count_sql = "SELECT priority, COUNT(*) as count 
             FROM notifications 
             WHERE user_id = ? 
             GROUP BY priority 
             ORDER BY 
                CASE priority 
                    WHEN 'critical' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    WHEN 'low' THEN 4 
                END ASC";

$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>Priority</th><th>Count</th><th>Percentage</th></tr>";

$total_count = 0;
$priority_counts = [];

while ($row = $count_result->fetch_assoc()) {
    $priority_counts[$row['priority']] = $row['count'];
    $total_count += $row['count'];
}

foreach ($priority_counts as $priority => $count) {
    $percentage = $total_count > 0 ? round(($count / $total_count) * 100, 1) : 0;
    $priority_color = '';
    switch($priority) {
        case 'critical': $priority_color = 'color: #dc3545; font-weight: bold;'; break;
        case 'high': $priority_color = 'color: #fd7e14; font-weight: bold;'; break;
        case 'medium': $priority_color = 'color: #ffc107; font-weight: bold;'; break;
        case 'low': $priority_color = 'color: #28a745; font-weight: bold;'; break;
    }
    
    echo "<tr>";
    echo "<td style=\"$priority_color\">" . strtoupper(htmlspecialchars($priority)) . "</td>";
    echo "<td>" . $count . "</td>";
    echo "<td>" . $percentage . "%</td>";
    echo "</tr>";
}

echo "<tr><td><strong>Total</strong></td><td><strong>$total_count</strong></td><td><strong>100%</strong></td></tr>";
echo "</table>";

echo "<h3>✅ Priority Notification Test Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Visit the <a href='notifications.php'>Notifications page</a> to see the priority system in action</li>";
echo "<li>Verify that notifications are sorted by priority (Critical first, then High, Medium, Low)</li>";
echo "<li>Test the priority filtering tabs</li>";
echo "<li>Check that priority badges are displayed correctly</li>";
echo "<li>Verify that priority-based borders are applied to notification cards</li>";
echo "</ul>";

$conn->close();
?>
