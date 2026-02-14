<?php
require_once 'config.php';

echo "Updating received_by column to VARCHAR...\n";

try {
    $sql = "ALTER TABLE `consumable_release_history` MODIFY COLUMN `received_by` VARCHAR(255) NULL";
    if ($conn->query($sql)) {
        echo "✓ received_by column updated to VARCHAR(255) successfully\n";
    } else {
        echo "✗ Error updating column: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
