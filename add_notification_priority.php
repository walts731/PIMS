<?php
/**
 * Database Migration Script: Add Priority Levels to Notifications
 * 
 * This script adds a priority column to the notifications table
 * and updates existing notifications with appropriate priority levels
 */

require_once 'config.php';

echo "<h2>Notification Priority Migration</h2>";

try {
    // Step 1: Add priority column to notifications table
    echo "<h3>Step 1: Adding priority column...</h3>";
    
    $alter_sql = "ALTER TABLE notifications 
                  ADD COLUMN priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium' 
                  AFTER type";
    
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✅ Priority column added successfully</p>";
    } else {
        // Check if column already exists
        $check_column = "SHOW COLUMNS FROM notifications LIKE 'priority'";
        $result = $conn->query($check_column);
        
        if ($result->num_rows > 0) {
            echo "<p style='color: orange;'>⚠️ Priority column already exists</p>";
        } else {
            throw new Exception("Failed to add priority column: " . $conn->error);
        }
    }
    
    // Step 2: Update existing notifications with appropriate priorities
    echo "<h3>Step 2: Setting priorities for existing notifications...</h3>";
    
    // Critical priorities for system errors and critical alerts
    $critical_update = "UPDATE notifications 
                        SET priority = 'critical' 
                        WHERE type = 'error' 
                        OR (title LIKE '%critical%' OR title LIKE '%urgent%' OR title LIKE '%emergency%')
                        OR (message LIKE '%critical%' OR message LIKE '%urgent%' OR message LIKE '%emergency%')";
    
    $conn->query($critical_update);
    $critical_count = $conn->affected_rows;
    echo "<p>✅ Set {$critical_count} notifications to 'critical' priority</p>";
    
    // High priorities for warnings and important alerts
    $high_update = "UPDATE notifications 
                    SET priority = 'high' 
                    WHERE type = 'warning' 
                    OR (title LIKE '%alert%' OR title LIKE '%maintenance%' OR title LIKE '%stock%')
                    OR (message LIKE '%alert%' OR message LIKE '%maintenance%' OR message LIKE '%stock%')
                    OR priority = 'medium' AND (
                        title LIKE '%low stock%' OR title LIKE '%maintenance%' OR title LIKE '%due%'
                    )";
    
    $conn->query($high_update);
    $high_count = $conn->affected_rows;
    echo "<p>✅ Set {$high_count} notifications to 'high' priority</p>";
    
    // Low priorities for informational messages
    $low_update = "UPDATE notifications 
                   SET priority = 'low' 
                   WHERE type = 'info' 
                   AND (title LIKE '%information%' OR title LIKE '%update%' OR title LIKE '%success%')
                   AND priority = 'medium'";
    
    $conn->query($low_update);
    $low_count = $conn->affected_rows;
    echo "<p>✅ Set {$low_count} notifications to 'low' priority</p>";
    
    // Step 3: Verify the migration
    echo "<h3>Step 3: Verification</h3>";
    
    $verify_sql = "SELECT priority, COUNT(*) as count 
                   FROM notifications 
                   GROUP BY priority 
                   ORDER BY 
                       CASE priority 
                           WHEN 'critical' THEN 1 
                           WHEN 'high' THEN 2 
                           WHEN 'medium' THEN 3 
                           WHEN 'low' THEN 4 
                       END ASC";
    
    $result = $conn->query($verify_sql);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Priority</th><th>Count</th></tr>";
    
    $total_count = 0;
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . htmlspecialchars($row['priority']) . "</td><td>" . $row['count'] . "</td></tr>";
        $total_count += $row['count'];
    }
    
    echo "<tr><td><strong>Total</strong></td><td><strong>{$total_count}</strong></td></tr>";
    echo "</table>";
    
    // Step 4: Show table structure
    echo "<h3>Step 4: Updated Table Structure</h3>";
    
    $structure_sql = "DESCRIBE notifications";
    $result = $conn->query($structure_sql);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>✅ Migration Completed Successfully!</h3>";
    echo "<p>The notifications table now supports priority levels with the following levels:</p>";
    echo "<ul>";
    echo "<li><strong>Critical</strong> - System failures, security alerts, urgent actions</li>";
    echo "<li><strong>High</strong> - Important deadlines, maintenance due, low stock</li>";
    echo "<li><strong>Medium</strong> - New requests, status changes, general updates</li>";
    echo "<li><strong>Low</strong> - Informational messages, confirmations</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
