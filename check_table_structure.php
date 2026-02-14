<?php
require_once 'config.php';

echo "Checking consumable_release_history table structure...\n";

try {
    $result = $conn->query("DESCRIBE `consumable_release_history`");
    
    if ($result) {
        echo "Current table structure:\n";
        echo "+----------------+-------------+------+-----+---------+----------------+\n";
        echo "| Field          | Type        | Null | Key | Default | Extra          |\n";
        echo "+----------------+-------------+------+-----+---------+----------------+\n";
        
        while ($row = $result->fetch_assoc()) {
            printf("| %-14s | %-11s | %-5s | %-4s | %-8s | %-15s |\n", 
                $row['Field'], 
                $row['Type'], 
                $row['Null'], 
                $row['Key'], 
                $row['Default'], 
                $row['Extra']
            );
        }
        echo "+----------------+-------------+------+-----+---------+----------------+\n";
    }
    
    // Test inserting a record with received_by
    echo "\nTesting insert with received_by field...\n";
    $test_sql = "INSERT INTO `consumable_release_history` 
                 (consumable_id, description, quantity_released, unit_cost, total_value, from_office_id, to_office_id, released_by, received_by, notes) 
                 VALUES (1, 'Test Item', 5, 10.00, 50.00, 1, 2, 1, 'Test Person', 'Test notes')";
    
    if ($conn->query($test_sql)) {
        echo "✓ Test insert successful\n";
        // Clean up test record
        $conn->query("DELETE FROM `consumable_release_history` WHERE description = 'Test Item'");
    } else {
        echo "✗ Test insert failed: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
