<?php
require_once '../config.php';

echo "<h1>Fuel Transactions Test</h1>";

// Test database connection
echo "<h2>Database Connection</h2>";
if ($conn) {
    echo "<p style='color: green;'>✅ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Check fuel_transactions table
echo "<h2>Fuel Transactions Table</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'fuel_transactions'");
if ($check_table && $check_table->num_rows > 0) {
    echo "<p style='color: green;'>✅ fuel_transactions table exists</p>";
    
    // Show table structure
    $columns = $conn->query("DESCRIBE fuel_transactions");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ fuel_transactions table doesn't exist</p>";
}

// Test fuel IN records
echo "<h2>Fuel IN Records</h2>";
$fuel_in_query = "SELECT ft.*, u.first_name, u.last_name 
                 FROM fuel_transactions ft 
                 LEFT JOIN users u ON ft.user_id = u.id 
                 WHERE ft.transaction_type = 'IN' 
                 ORDER BY ft.transaction_date DESC 
                 LIMIT 10";

$result = $conn->query($fuel_in_query);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $result->num_rows . " fuel IN records</p>";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Date</th><th>Fuel Type</th><th>Quantity</th><th>Supplier</th><th>User</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('M j, Y g:i A', strtotime($row['transaction_date'])) . "</td>";
        echo "<td>" . ucfirst($row['fuel_type']) . "</td>";
        echo "<td>" . number_format($row['quantity'], 2) . " L</td>";
        echo "<td>" . htmlspecialchars($row['supplier'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No fuel IN records found</p>";
}

// Test fuel OUT records
echo "<h2>Fuel OUT Records</h2>";
$fuel_out_query = "SELECT ft.*, u.first_name, u.last_name 
                  FROM fuel_transactions ft 
                  LEFT JOIN users u ON ft.user_id = u.id 
                  WHERE ft.transaction_type = 'OUT' 
                  ORDER BY ft.transaction_date DESC 
                  LIMIT 10";

$result = $conn->query($fuel_out_query);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $result->num_rows . " fuel OUT records</p>";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Date</th><th>Fuel Type</th><th>Quantity</th><th>Receiver</th><th>Purpose</th><th>User</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('M j, Y g:i A', strtotime($row['transaction_date'])) . "</td>";
        echo "<td>" . ucfirst($row['fuel_type']) . "</td>";
        echo "<td>" . number_format($row['quantity'], 2) . " L</td>";
        echo "<td>" . htmlspecialchars($row['recipient_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['purpose'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No fuel OUT records found</p>";
}

// Add sample data if no records exist
echo "<h2>Add Sample Data</h2>";
if ($conn->query("SELECT COUNT(*) as count FROM fuel_transactions")->fetch_assoc()['count'] == 0) {
    echo "<p>Adding sample fuel transactions...</p>";
    
    // Sample fuel IN
    $conn->query("INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, user_id) 
                  VALUES ('IN', 'diesel', 100.50, NOW(), 'DEL-001', 'Sample Supplier', 1)");
    
    // Sample fuel OUT
    $conn->query("INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, recipient_name, purpose, user_id) 
                  VALUES ('OUT', 'diesel', 25.00, NOW(), 'John Doe', 'Test purpose', 1)");
    
    echo "<p style='color: green;'>✅ Sample data added</p>";
    echo "<p><a href='fuel_in.php'>Test Fuel IN Page</a></p>";
} else {
    echo "<p>Database already has records</p>";
}

echo "<hr>";
echo "<p><a href='fuel_in.php' class='btn btn-primary'>Go to Fuel IN Page</a></p>";
echo "<p><a href='../FUEL/dashboard.php' class='btn btn-success'>Go to Fuel Dashboard</a></p>";
?>
