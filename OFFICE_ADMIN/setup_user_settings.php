<?php
require_once '../config.php';

echo "<h2>Adding User Settings Columns to Database</h2>";

// Create user_settings table if it doesn't exist
$create_settings_table = "
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme VARCHAR(20) DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'UTC',
    email_notifications BOOLEAN DEFAULT TRUE,
    desktop_notifications BOOLEAN DEFAULT FALSE,
    auto_refresh BOOLEAN DEFAULT TRUE,
    refresh_interval INT DEFAULT 30,
    show_online_status BOOLEAN DEFAULT TRUE,
    show_activity BOOLEAN DEFAULT TRUE,
    data_collection BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
)";

if ($conn->query($create_settings_table)) {
    echo "<p style='color: green;'>✓ User settings table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating user settings table: " . $conn->error . "</p>";
}

// Alternative: Add columns directly to users table (simpler approach)
$add_columns = [
    "ALTER TABLE users ADD COLUMN theme VARCHAR(20) DEFAULT 'light'",
    "ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'en'", 
    "ALTER TABLE users ADD COLUMN timezone VARCHAR(50) DEFAULT 'UTC'",
    "ALTER TABLE users ADD COLUMN email_notifications BOOLEAN DEFAULT TRUE",
    "ALTER TABLE users ADD COLUMN desktop_notifications BOOLEAN DEFAULT FALSE",
    "ALTER TABLE users ADD COLUMN auto_refresh BOOLEAN DEFAULT TRUE",
    "ALTER TABLE users ADD COLUMN refresh_interval INT DEFAULT 30",
    "ALTER TABLE users ADD COLUMN show_online_status BOOLEAN DEFAULT TRUE",
    "ALTER TABLE users ADD COLUMN show_activity BOOLEAN DEFAULT TRUE",
    "ALTER TABLE users ADD COLUMN data_collection BOOLEAN DEFAULT TRUE"
];

echo "<h3>Adding columns to users table:</h3>";

foreach ($add_columns as $sql) {
    // Extract column name from ALTER TABLE statement
    preg_match("/ADD COLUMN (\w+)/", $sql, $matches);
    $column_name = $matches[1] ?? 'unknown';
    
    // Check if column already exists
    $check_column = "SHOW COLUMNS FROM users LIKE '$column_name'";
    $result = $conn->query($check_column);
    
    if ($result->num_rows == 0) {
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Added column: $column_name</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding column $column_name: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>→ Column $column_name already exists</p>";
    }
}

// Update existing users with default values if columns are new
$update_defaults = "
    UPDATE users SET 
        theme = COALESCE(theme, 'light'),
        language = COALESCE(language, 'en'),
        timezone = COALESCE(timezone, 'UTC'),
        email_notifications = COALESCE(email_notifications, TRUE),
        desktop_notifications = COALESCE(desktop_notifications, FALSE),
        auto_refresh = COALESCE(auto_refresh, TRUE),
        refresh_interval = COALESCE(refresh_interval, 30)
    WHERE theme IS NULL OR language IS NULL OR timezone IS NULL
";

if ($conn->query($update_defaults)) {
    echo "<p style='color: green;'>✓ Updated existing users with default settings</p>";
} else {
    echo "<p style='color: red;'>✗ Error updating defaults: " . $conn->error . "</p>";
}

echo "<h3>Current users table structure:</h3>";
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

echo "<p style='color: green; font-weight: bold;'>✓ Database setup complete!</p>";
echo "<p><a href='settings.php'>Go to Settings Page</a></p>";
?>
