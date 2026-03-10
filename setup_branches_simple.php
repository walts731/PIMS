<?php
// Simple setup script for branches table
require_once 'config.php';

echo "<h2>Setting up Branches Table</h2>";

// Create branches table
$create_table = "CREATE TABLE IF NOT EXISTS `branches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `branch_name` varchar(100) NOT NULL,
    `branch_code` varchar(10) NOT NULL,
    `description` text DEFAULT NULL,
    `office_id` int(11) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `created_by` int(11) DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `office_id` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($create_table)) {
    echo "<p style='color: green;'>✓ Branches table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating branches table: " . $conn->error . "</p>";
}

// Insert sample data
$insert_data = "INSERT IGNORE INTO `branches` (`id`, `branch_name`, `branch_code`, `description`, `office_id`, `status`, `created_by`) VALUES
    (1, 'Supply Room', 'SR001', 'Main supply storage room', 3, 'active', 1),
    (2, 'Main Office', 'MO001', 'Primary office location', 1, 'active', 1),
    (3, 'IT Department', 'IT001', 'Information Technology department', 1, 'active', 1),
    (4, 'Maintenance', 'MT001', 'Maintenance and facilities department', 2, 'active', 1),
    (5, 'Warehouse', 'WH001', 'Main warehouse facility', 3, 'active', 1),
    (6, 'Conference Room', 'CR001', 'Meeting and conference facilities', 1, 'active', 1)";

if ($conn->query($insert_data)) {
    echo "<p style='color: green;'>✓ Sample branches data inserted successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error inserting branches data: " . $conn->error . "</p>";
}

// Add branch_id column to asset_items if it doesn't exist
$check_column = "SHOW COLUMNS FROM asset_items LIKE 'branch_id'";
$column_result = $conn->query($check_column);

if ($column_result && $column_result->num_rows == 0) {
    // Column doesn't exist, add it
    $add_column = "ALTER TABLE `asset_items` ADD COLUMN `branch_id` int(11) DEFAULT NULL AFTER `office_id`";
    if ($conn->query($add_column)) {
        echo "<p style='color: green;'>✓ branch_id column added to asset_items table</p>";
        
        // Add index
        $add_index = "ALTER TABLE `asset_items` ADD INDEX `branch_id` (`branch_id`)";
        $conn->query($add_index);
        
        // Add foreign key
        $add_fk = "ALTER TABLE `asset_items` ADD CONSTRAINT `asset_items_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL";
        $conn->query($add_fk);
        
        echo "<p style='color: green;'>✓ branch_id index and foreign key added</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding branch_id column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ branch_id column already exists in asset_items table</p>";
}

// Verify setup
$verify = "SELECT COUNT(*) as count FROM branches";
$result = $conn->query($verify);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p style='color: blue;'>ℹ Branches table now has {$row['count']} records</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='MAIN_USER/branches.php'>Go to Branches Page</a></p>";
echo "<p><a href='MAIN_USER/assets_per_office.php'>Go to Assets per Office Page</a></p>";

$conn->close();
?>
