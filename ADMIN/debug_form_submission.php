<?php
session_start();
require_once '../config.php';

echo "<h2>Debug Form Submission</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['items_json'])) {
        echo "<h3>Items JSON:</h3>";
        echo "<pre>";
        echo htmlspecialchars($_POST['items_json']);
        echo "</pre>";
        
        $items = json_decode($_POST['items_json'], true);
        echo "<h3>Decoded Items:</h3>";
        echo "<pre>";
        print_r($items);
        echo "</pre>";
        
        echo "<h3>JSON Validation:</h3>";
        echo "JSON Error: " . json_last_error_msg() . "<br>";
        echo "Items Count: " . (is_array($items) ? count($items) : 0) . "<br>";
        echo "Items Empty: " . (empty($items) ? 'YES' : 'NO') . "<br>";
    } else {
        echo "<h3 style='color: red;'>ERROR: items_json not found in POST data!</h3>";
    }
} else {
    echo "<p>Waiting for form submission...</p>";
    
    // Create a simple test form
    echo "
    <form method='post'>
        <h3>Test Form Submission</h3>
        <input type='hidden' name='action' value='submit_borrow_request'>
        <input type='hidden' name='items_json' id='testItemsJson'>
        
        <label>Guest Name:</label><br>
        <input type='text' name='guest_name' value='Test User'><br><br>
        
        <label>Barangay:</label><br>
        <input type='text' name='barangay' value='Test Barangay'><br><br>
        
        <label>Contact:</label><br>
        <input type='text' name='contact' value='123-456-7890'><br><br>
        
        <label>Date Borrowed:</label><br>
        <input type='date' name='date_borrowed' value='2026-01-15'><br><br>
        
        <label>Schedule Return:</label><br>
        <input type='date' name='schedule_return' value='2026-01-20'><br><br>
        
        <label>Releasing Officer:</label><br>
        <input type='text' name='releasing_officer' value='Test Officer'><br><br>
        
        <label>Approved By:</label><br>
        <input type='text' name='approved_by' value='Test Approver'><br><br>
        
        <button type='button' onclick='setTestItems()'>Set Test Items</button>
        <button type='submit'>Submit Form</button>
    </form>
    
    <script>
    function setTestItems() {
        const testItems = [
            {
                description: 'Test Asset 1\\r\\nInventory Tag: TEST-001\\r\\nProperty No: PROP-001',
                quantity: '1',
                remarks: 'Test remark 1'
            },
            {
                description: 'Test Asset 2\\r\\nInventory Tag: TEST-002\\r\\nProperty No: PROP-002',
                quantity: '2',
                remarks: 'Test remark 2'
            }
        ];
        
        document.getElementById('testItemsJson').value = JSON.stringify(testItems);
        alert('Test items set! Ready to submit.');
    }
    </script>
    ";
}
?>
