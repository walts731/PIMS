<?php
session_start();
require_once '../config.php';

// Test script to verify the new borrowing form items format

echo "<h2>Testing New Borrowing Form Items Format</h2>";

// Test 1: Create a simple items array with Description/Asset, Quantity, and Remarks
$test_items = [
    [
        'description' => 'Laptop Computer - Dell Latitude 5420\r\nInventory Tag: INV-001\r\nProperty No: PROP-001',
        'quantity' => '1',
        'remarks' => 'Good condition'
    ],
    [
        'description' => 'Office Chair - Ergonomic\r\nInventory Tag: INV-002\r\nProperty No: PROP-002',
        'quantity' => '2',
        'remarks' => 'Minor scratches'
    ]
];

// Convert to JSON
$items_json = json_encode($test_items);

echo "<h3>Test 1: JSON Format</h3>";
echo "<pre>";
echo htmlspecialchars($items_json);
echo "</pre>";

// Test 2: Validate JSON
$decoded = json_decode($items_json, true);
echo "<h3>Test 2: JSON Validation</h3>";
echo "JSON Valid: " . (json_last_error() === JSON_ERROR_NONE ? "YES" : "NO") . "<br>";

if (json_last_error() === JSON_ERROR_NONE) {
    echo "Items count: " . count($decoded) . "<br>";
    echo "<pre>";
    print_r($decoded);
    echo "</pre>";
}

// Test 3: Simulate database insert
echo "<h3>Test 3: Database Insert Simulation</h3>";

try {
    $stmt = $conn->prepare("INSERT INTO borrow_form_submissions 
        (guest_name, barangay, contact, date_borrowed, schedule_return, releasing_officer, approved_by, items, status, submitted_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
    
    $guest_name = "Test User";
    $barangay = "Test Barangay";
    $contact = "123-456-7890";
    $date_borrowed = "2026-01-15";
    $schedule_return = "2026-01-20";
    $releasing_officer = "Test Officer";
    $approved_by = "Test Approver";
    
    $stmt->bind_param("ssssssss", $guest_name, $barangay, $contact, $date_borrowed, 
                     $schedule_return, $releasing_officer, $approved_by, $items_json);
    
    $result = $stmt->execute();
    
    if ($result) {
        $borrow_id = $stmt->insert_id;
        echo "✅ Database insert successful! Borrow ID: " . $borrow_id . "<br>";
        
        // Test 4: Retrieve and verify the stored data
        echo "<h3>Test 4: Data Retrieval Verification</h3>";
        
        $retrieve_stmt = $conn->prepare("SELECT items FROM borrow_form_submissions WHERE id = ?");
        $retrieve_stmt->bind_param("i", $borrow_id);
        $retrieve_stmt->execute();
        $result = $retrieve_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $stored_items = json_decode($row['items'], true);
            echo "✅ Data retrieved successfully<br>";
            echo "Stored items count: " . count($stored_items) . "<br>";
            
            echo "<h4>Stored Items:</h4>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Description</th><th>Quantity</th><th>Remarks</th></tr>";
            
            foreach ($stored_items as $item) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($item['description']) . "</td>";
                echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                echo "<td>" . htmlspecialchars($item['remarks']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Clean up test data
            $delete_stmt = $conn->prepare("DELETE FROM borrow_form_submissions WHERE id = ?");
            $delete_stmt->bind_param("i", $borrow_id);
            $delete_stmt->execute();
            echo "<br>✅ Test data cleaned up";
            
        } else {
            echo "❌ Failed to retrieve data";
        }
        $retrieve_stmt->close();
        
    } else {
        echo "❌ Database insert failed: " . $stmt->error;
    }
    $stmt->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<h3>Test 5: Format Comparison</h3>";
echo "<h4>Old Format (Complex Wrapper):</h4>";
echo "<pre>";
echo htmlspecialchars(json_encode([
    'data' => $test_items,
    'encoded' => base64_encode(json_encode($test_items)),
    'timestamp' => time()
], JSON_PRETTY_PRINT));
echo "</pre>";

echo "<h4>New Format (Simple):</h4>";
echo "<pre>";
echo htmlspecialchars(json_encode($test_items, JSON_PRETTY_PRINT));
echo "</pre>";

echo "<hr>";
echo "<h2>✅ All tests completed!</h2>";
echo "<p>The new format stores only the essential information (Description/Asset, Quantity, Remarks) in a clean JSON structure.</p>";
?>
