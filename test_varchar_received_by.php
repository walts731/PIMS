<?php
require_once 'config.php';

echo "Testing received_by as VARCHAR field...\n";

try {
    // Test inserting with received_by as string
    $test_sql = "INSERT INTO `consumable_release_history` 
                 (consumable_id, description, quantity_released, unit_cost, total_value, from_office_id, to_office_id, released_by, received_by, notes) 
                 VALUES (2, 'Test Item VARCHAR', 5, 10.00, 50.00, 1, 2, 1, 'John Doe', 'Test notes with VARCHAR')";
    
    if ($conn->query($test_sql)) {
        echo "✓ Test insert successful with received_by as VARCHAR\n";
        
        // Check the inserted record
        $check_sql = "SELECT received_by FROM `consumable_release_history` WHERE description = 'Test Item VARCHAR'";
        $result = $conn->query($check_sql);
        if ($row = $result->fetch_assoc()) {
            echo "✓ Stored received_by value: '{$row['received_by']}'\n";
        }
        
        // Clean up test record
        $conn->query("DELETE FROM `consumable_release_history` WHERE description = 'Test Item VARCHAR'");
    } else {
        echo "✗ Test insert failed: " . $conn->error . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
