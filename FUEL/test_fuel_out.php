<?php
// Simple test to check fuel_transactions database
require_once '../config.php';

echo "<h2>Fuel Transactions Database Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Testing Database Connection</h3>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit();
}

// Test 2: Check if fuel_transactions table exists
echo "<h3>2. Checking if fuel_transactions table exists</h3>";
$table_check = $conn->query("SHOW TABLES LIKE 'fuel_transactions'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ fuel_transactions table exists</p>";
} else {
    echo "<p style='color: red;'>❌ fuel_transactions table does not exist</p>";
    exit();
}

// Test 3: Check table structure
echo "<h3>3. Checking table structure</h3>";
$structure_check = $conn->query("DESCRIBE fuel_transactions");
if ($structure_check) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 4: Check total records
echo "<h3>4. Total records in fuel_transactions</h3>";
$total_check = $conn->query("SELECT COUNT(*) as total FROM fuel_transactions");
if ($total_check && $row = $total_check->fetch_assoc()) {
    echo "<p>Total records: <strong>" . $row['total'] . "</strong></p>";
}

// Test 5: Check transaction types
echo "<h3>5. Transaction types in database</h3>";
$type_check = $conn->query("SELECT transaction_type, COUNT(*) as count FROM fuel_transactions GROUP BY transaction_type");
if ($type_check) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Transaction Type</th><th>Count</th></tr>";
    while ($row = $type_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['transaction_type'] . "</td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 6: Check OUT transactions specifically
echo "<h3>6. Fuel OUT transactions specifically</h3>";
$out_check = $conn->query("SELECT * FROM fuel_transactions WHERE transaction_type = 'OUT' ORDER BY transaction_date DESC LIMIT 5");
if ($out_check && $out_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $out_check->num_rows . " OUT transactions</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Date</th><th>Fuel Type</th><th>Quantity</th><th>Source</th><th>Recipient</th></tr>";
    while ($row = $out_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['transaction_date'] . "</td>";
        echo "<td>" . $row['fuel_type'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . ($row['source'] ?? 'NULL') . "</td>";
        echo "<td>" . ($row['recipient_name'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No OUT transactions found</p>";
}

// Test 7: Check if there are any transactions at all
echo "<h3>7. Any transactions at all</h3>";
$any_check = $conn->query("SELECT * FROM fuel_transactions LIMIT 5");
if ($any_check && $any_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $any_check->num_rows . " transactions total</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Type</th><th>Date</th><th>Fuel Type</th><th>Quantity</th></tr>";
    while ($row = $any_check->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['transaction_type'] . "</td>";
        echo "<td>" . $row['transaction_date'] . "</td>";
        echo "<td>" . $row['fuel_type'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No transactions found at all</p>";
}

// Test 8: Try to insert a test record
echo "<h3>8. Testing INSERT operation</h3>";
$test_insert = "INSERT INTO fuel_transactions 
              (transaction_type, fuel_type, quantity, transaction_date, source, recipient_name, purpose, user_id, created_at, updated_at) 
              VALUES ('OUT', 'diesel', 5.00, NOW(), 'Test Source', 'Test Recipient', 'Test Purpose', 1, NOW(), NOW())";

if ($conn->query($test_insert)) {
    echo "<p style='color: green;'>✅ Test INSERT successful</p>";
    
    // Clean up test record
    $conn->query("DELETE FROM fuel_transactions WHERE source = 'Test Source'");
    echo "<p>✅ Test record cleaned up</p>";
} else {
    echo "<p style='color: red;'>❌ Test INSERT failed: " . $conn->error . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>Visit this test page to diagnose issues with the fuel_transactions database.</p>";
echo "<p><a href='fuel_out.php'>← Back to Fuel OUT Page</a></p>";
?>
