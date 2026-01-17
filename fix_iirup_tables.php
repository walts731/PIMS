<?php
require_once 'config.php';

echo "<h2>Fixing IIRUP Tables</h2>";

// Drop and recreate iirup_forms table
echo "<p>Dropping and recreating iirup_forms table...</p>";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP TABLE IF EXISTS iirup_forms");

$sql = "CREATE TABLE `iirup_forms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `form_number` varchar(50) NOT NULL,
    `as_of_year` int(4) NOT NULL,
    `accountable_officer` varchar(255) NOT NULL,
    `designation` varchar(255) NOT NULL,
    `department_office` varchar(255) NOT NULL,
    `accountable_officer_name` varchar(255) DEFAULT NULL,
    `accountable_officer_designation` varchar(255) DEFAULT NULL,
    `authorized_official_name` varchar(255) DEFAULT NULL,
    `authorized_official_designation` varchar(255) DEFAULT NULL,
    `inspection_officer_name` varchar(255) DEFAULT NULL,
    `witness_name` varchar(255) DEFAULT NULL,
    `status` enum('draft','submitted','approved','rejected','processed') DEFAULT 'draft',
    `total_items` int(11) DEFAULT 0,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `form_number` (`form_number`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_as_of_year` (`as_of_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<p style='color: green;'>✓ iirup_forms table recreated successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating iirup_forms: " . $conn->error . "</p>";
}

// Drop and recreate iirup_items table
echo "<p>Dropping and recreating iirup_items table...</p>";

$conn->query("DROP TABLE IF EXISTS iirup_items");

$sql = "CREATE TABLE `iirup_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `form_id` int(11) NOT NULL,
    `date_acquired` date DEFAULT NULL,
    `particulars` text NOT NULL,
    `property_no` varchar(100) DEFAULT NULL,
    `quantity` decimal(10,2) DEFAULT 0.00,
    `unit_cost` decimal(15,2) DEFAULT 0.00,
    `total_cost` decimal(15,2) DEFAULT 0.00,
    `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
    `impairment_losses` decimal(15,2) DEFAULT 0.00,
    `carrying_amount` decimal(15,2) DEFAULT 0.00,
    `inventory_remarks` text DEFAULT NULL,
    `disposal_sale` decimal(15,2) DEFAULT 0.00,
    `disposal_transfer` decimal(15,2) DEFAULT 0.00,
    `disposal_destruction` decimal(15,2) DEFAULT 0.00,
    `disposal_others` text DEFAULT NULL,
    `disposal_total` decimal(15,2) DEFAULT 0.00,
    `appraised_value` decimal(15,2) DEFAULT 0.00,
    `total` decimal(15,2) DEFAULT 0.00,
    `or_no` varchar(100) DEFAULT NULL,
    `amount` decimal(15,2) DEFAULT 0.00,
    `dept_office` varchar(255) DEFAULT NULL,
    `control_no` varchar(100) DEFAULT NULL,
    `date_received` date DEFAULT NULL,
    `item_order` int(11) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `form_id` (`form_id`),
    KEY `idx_property_no` (`property_no`),
    KEY `idx_dept_office` (`dept_office`),
    KEY `idx_item_order` (`item_order`),
    CONSTRAINT `iirup_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `iirup_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "<p style='color: green;'>✓ iirup_items table recreated successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating iirup_items: " . $conn->error . "</p>";
}

// Verify tables
echo "<h3>Verification:</h3>";
$result = $conn->query("SHOW TABLES LIKE 'iirup_%'");
if ($result) {
    while ($row = $result->fetch_array()) {
        echo "<p style='color: green;'>✓ Table exists: " . $row[0] . "</p>";
    }
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$conn->close();
?>
