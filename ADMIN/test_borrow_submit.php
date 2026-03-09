<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    if (isset($_POST['test_submit'])) {
        // Test with hardcoded data
        $guest_name = "Test User";
        $barangay = "Test Barangay";
        $contact = "123456789";
        $date_borrowed = date('Y-m-d');
        $schedule_return = date('Y-m-d', strtotime('+7 days'));
        $releasing_officer = "Test Officer";
        $approved_by = "Test Approver";
        
        // Hardcoded test items
        $items = [
            [
                'asset_item_id' => 1,
                'asset_name' => 'Test Asset',
                'description' => 'Test Description',
                'property_number' => 'TEST001',
                'inventory_tag' => 'TAG001',
                'quantity' => 1,
                'remarks' => 'Test remarks'
            ]
        ];
        
        $items_json = json_encode($items);
        
        echo "<h2>Test Data:</h2>";
        echo "<pre>Items JSON: " . $items_json . "</pre>";
        
        try {
            $conn->begin_transaction();

            // Insert borrow request
            $stmt = $conn->prepare("INSERT INTO borrow_form_submissions 
                (guest_name, barangay, contact, date_borrowed, schedule_return, releasing_officer, approved_by, items, status, submitted_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW(), NOW())");
            
            $stmt->bind_param("ssssssss", $guest_name, $barangay, $contact, $date_borrowed, 
                             $schedule_return, $releasing_officer, $approved_by, $items_json);
            
            $result = $stmt->execute();
            echo "<h2>Database Insert Result:</h2>";
            echo "<pre>Success: " . ($result ? 'YES' : 'NO') . "</pre>";
            echo "<pre>Affected rows: " . $stmt->affected_rows . "</pre>";
            
            $borrow_id = $stmt->insert_id;
            echo "<pre>Borrow ID: " . $borrow_id . "</pre>";
            
            $stmt->close();
            $conn->commit();
            
            echo "<h2>Success!</h2>";
            echo "<p>Test borrow request inserted with ID: $borrow_id</p>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<h2>Error:</h2>";
            echo "<pre>" . $e->getMessage() . "</pre>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Borrow Submit</title>
</head>
<body>
    <h1>Test Borrow Submission</h1>
    
    <form method="POST">
        <input type="hidden" name="test_submit" value="1">
        <button type="submit">Submit Test Borrow Request</button>
    </form>
    
    <hr>
    
    <h2>Current Serviceable Assets:</h2>
    <?php
    try {
        $stmt = $conn->prepare("SELECT ai.id as asset_item_id, a.description as asset_description, ai.description as item_description, 
                                       ai.property_number, ai.inventory_tag, ai.status
                               FROM asset_items ai 
                               JOIN assets a ON ai.asset_id = a.id 
                               WHERE ai.status = 'serviceable' 
                               ORDER BY a.description, ai.property_number LIMIT 5");
        $stmt->execute();
        $result = $stmt->get_result();
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Asset</th><th>Description</th><th>Property No</th><th>Inventory Tag</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['asset_item_id'] . "</td>";
            echo "<td>" . $row['asset_description'] . "</td>";
            echo "<td>" . $row['item_description'] . "</td>";
            echo "<td>" . $row['property_number'] . "</td>";
            echo "<td>" . $row['inventory_tag'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $stmt->close();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>
    
    <br><br>
    <a href="new_borrow_request.php">Back to New Borrow Request</a>
</body>
</html>
