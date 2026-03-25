<?php
require_once '../config.php';

echo "<h2>Database Setup for User Profile Features</h2>";

// Add required columns to users table
$columns = [
    'theme' => "VARCHAR(20) DEFAULT 'light'",
    'language' => "VARCHAR(10) DEFAULT 'en'",
    'timezone' => "VARCHAR(50) DEFAULT 'UTC'",
    'email_notifications' => "BOOLEAN DEFAULT TRUE",
    'desktop_notifications' => "BOOLEAN DEFAULT FALSE",
    'auto_refresh' => "BOOLEAN DEFAULT TRUE", 
    'refresh_interval' => "INT DEFAULT 30",
    'show_online_status' => "BOOLEAN DEFAULT TRUE",
    'show_activity' => "BOOLEAN DEFAULT TRUE",
    'data_collection' => "BOOLEAN DEFAULT TRUE"
];

echo "<h3>Adding User Settings Columns:</h3>";

foreach ($columns as $column_name => $column_def) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM users LIKE '$column_name'");
    
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $column_name $column_def";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Added column: $column_name</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding $column_name: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>→ Column $column_name already exists</p>";
    }
}

// Create user_sessions table for session management
$sessions_table = "
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(50),
    browser VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_last_activity (last_activity)
)";

if ($conn->query($sessions_table)) {
    echo "<p style='color: green;'>✓ User sessions table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating sessions table: " . $conn->error . "</p>";
}

// Create user_issues table for issue reporting
$issues_table = "
CREATE TABLE IF NOT EXISTS user_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    steps TEXT,
    current_page VARCHAR(255),
    user_agent TEXT,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
)";

if ($conn->query($issues_table)) {
    echo "<p style='color: green;'>✓ User issues table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating issues table: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h3>Database Setup Complete!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li><a href='settings.php'>Test the Settings page</a></li>";
echo "<li><a href='profile.php'>Test the Profile page</a></li>";
echo "<li><a href='dashboard.php'>Test the Profile Dropdown</a></li>";
echo "</ol>";

echo "<p style='color: green; font-weight: bold;'>✅ All database tables and columns are now ready for the enhanced user profile functionality!</p>";
?>
