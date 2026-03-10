<?php
require_once 'config.php';

// SQL commands to create branches table and add sample data
$sql_commands = [
    // Create branches table
    "CREATE TABLE IF NOT EXISTS `branches` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",
    
    // Add foreign key constraint if table exists
    "ALTER TABLE `branches` ADD CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;",
    
    // Insert sample branches data
    "INSERT IGNORE INTO `branches` (`id`, `branch_name`, `branch_code`, `description`, `office_id`, `status`, `created_by`) VALUES
        (1, 'Supply Room', 'SR001', 'Main supply storage room', 3, 'active', 1),
        (2, 'Main Office', 'MO001', 'Primary office location', 1, 'active', 1),
        (3, 'IT Department', 'IT001', 'Information Technology department', 1, 'active', 1),
        (4, 'Maintenance', 'MT001', 'Maintenance and facilities department', 2, 'active', 1),
        (5, 'Warehouse', 'WH001', 'Main warehouse facility', 3, 'active', 1),
        (6, 'Conference Room', 'CR001', 'Meeting and conference facilities', 1, 'active', 1)",
    
    // Add branch_id column to asset_items if it doesn't exist
    "ALTER TABLE `asset_items` ADD COLUMN IF NOT EXISTS `branch_id` int(11) DEFAULT NULL AFTER `office_id`",
    
    // Add index and foreign key for branch_id
    "ALTER TABLE `asset_items` ADD INDEX IF NOT EXISTS `branch_id` (`branch_id`)",
    "ALTER TABLE `asset_items` ADD CONSTRAINT IF NOT EXISTS `asset_items_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL"
];

echo "Setting up branches table...\n";

foreach ($sql_commands as $sql) {
    try {
        $result = $conn->query($sql);
        if ($result) {
            echo "✓ Command executed successfully: " . substr($sql, 0, 50) . "...\n";
        } else {
            echo "✗ Error executing command: " . $conn->error . "\n";
            echo "SQL: " . $sql . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
        echo "SQL: " . $sql . "\n";
    }
}

echo "\nBranches setup completed!\n";

// Verify branches table exists and has data
$check_query = "SELECT COUNT(*) as count FROM branches";
$result = $conn->query($check_query);
if ($result) {
    $row = $result->fetch_assoc();
    echo "Branches table has {$row['count']} records.\n";
    
    // Display branches
    $branches_query = "SELECT * FROM branches ORDER BY branch_name";
    $branches_result = $conn->query($branches_query);
    if ($branches_result) {
        echo "\nCurrent branches:\n";
        echo "ID\tBranch Name\t\tCode\tOffice ID\n";
        echo "----------------------------------------\n";
        while ($branch = $branches_result->fetch_assoc()) {
            echo "{$branch['id']}\t{$branch['branch_name']}\t\t{$branch['branch_code']}\t{$branch['office_id']}\n";
        }
    }
}

$conn->close();
?>
