<?php
require_once 'config.php';

echo "Adding received_by column to consumable_release_history table...\n";

try {
    // Add received_by column
    $sql1 = "ALTER TABLE `consumable_release_history` ADD COLUMN `received_by` INT NULL AFTER `released_by`";
    if ($conn->query($sql1)) {
        echo "✓ received_by column added successfully\n";
    } else {
        echo "✗ Error adding received_by column: " . $conn->error . "\n";
    }
    
    // Add index for received_by column
    $sql2 = "ALTER TABLE `consumable_release_history` ADD INDEX `idx_received_by` (`received_by`)";
    if ($conn->query($sql2)) {
        echo "✓ Index for received_by column added successfully\n";
    } else {
        echo "✗ Error adding index: " . $conn->error . "\n";
    }
    
    echo "\nDatabase migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
