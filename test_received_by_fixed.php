<?php
require_once 'config.php';

echo "Checking existing consumables...\n";

try {
    $result = $conn->query("SELECT id, description FROM `consumables` LIMIT 5");
    
    if ($result && $result->num_rows > 0) {
        echo "Available consumables:\n";
        $consumables = [];
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['id']} - {$row['description']}\n";
            $consumables[] = $row;
        }
        
        // Test with the first available consumable
        $first_consumable = $consumables[0];
        echo "\nTesting with consumable ID: {$first_consumable['id']}\n";
        
        // Test inserting with real consumable_id
        $test_sql = "INSERT INTO `consumable_release_history` 
                     (consumable_id, description, quantity_released, unit_cost, total_value, from_office_id, to_office_id, released_by, received_by, notes) 
                     VALUES ({$first_consumable['id']}, 'Test Item', 5, 10.00, 50.00, 1, 2, 1, 'Test Person', 'Test notes')";
        
        if ($conn->query($test_sql)) {
            echo "✓ Test insert successful with received_by field\n";
            // Clean up test record
            $conn->query("DELETE FROM `consumable_release_history` WHERE description = 'Test Item'");
        } else {
            echo "✗ Test insert failed: " . $conn->error . "\n";
        }
    } else {
        echo "No consumables found in database\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
