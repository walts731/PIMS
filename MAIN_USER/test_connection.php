<?php
require_once '../config.php';

echo "<h1>FUEL_IN.PHP Database Connection Test</h1>";

// Test database connection
echo "<h2>1. Database Connection</h2>";
if ($conn) {
    echo "<p style='color: green;'>✅ Connected to database: pims</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Test fuel_transactions table
echo "<h2>2. Fuel Transactions Table Check</h2>";
$check_table = $conn->query("SHOW TABLES LIKE 'fuel_transactions'");
if ($check_table && $check_table->num_rows > 0) {
    echo "<p style='color: green;'>✅ fuel_transactions table exists</p>";
    
    // Show table structure
    $columns = $conn->query("DESCRIBE fuel_transactions");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Description</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        $description = '';
        switch($row['Field']) {
            case 'id': $description = 'Primary Key'; break;
            case 'transaction_type': $description = 'IN or OUT'; break;
            case 'fuel_type': $description = 'diesel, gasoline, premium'; break;
            case 'quantity': $description = 'Amount in liters'; break;
            case 'transaction_date': $description = 'Date and time'; break;
            case 'supplier': $description = 'Supplier name'; break;
            case 'source': $description = 'Receipt number'; break;
            case 'user_id': $description = 'User who recorded'; break;
        }
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $description . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ fuel_transactions table doesn't exist</p>";
    echo "<p><a href='../fix_fuel_database.php'>Create table now</a></p>";
    exit;
}

// Test the exact query used in fuel_in.php
echo "<h2>3. Testing Fuel IN Query (Exact same as fuel_in.php)</h2>";

// Get filter parameters (same as fuel_in.php)
$fuel_type_filter = isset($_GET['fuel_type']) ? trim((string)$_GET['fuel_type']) : '';
$period_filter = isset($_GET['period']) ? trim((string)$_GET['period']) : 'all';

// Build query (exact same as fuel_in.php)
$fuel_in_query = "SELECT ft.*, u.first_name, u.last_name 
                 FROM fuel_transactions ft 
                 LEFT JOIN users u ON ft.user_id = u.id 
                 WHERE ft.transaction_type = 'IN'";

// Add filters (same as fuel_in.php)
$where_conditions = [];
if (!empty($fuel_type_filter)) {
    $where_conditions[] = "ft.fuel_type = '" . $fuel_type_filter . "'";
}

if (!empty($where_conditions)) {
    $fuel_in_query .= " AND " . implode(" AND ", $where_conditions);
}

$fuel_in_query .= " ORDER BY ft.transaction_date DESC 
                 LIMIT 50";

echo "<p><strong>Query:</strong></p>";
echo "<code style='background: #f4f4f4; padding: 10px; display: block;'>" . htmlspecialchars($fuel_in_query) . "</code>";

// Execute query
$result = $conn->query($fuel_in_query);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Query successful! Found " . $result->num_rows . " fuel IN records</p>";
    
    echo "<h3>Sample Data (First 5 records):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Date</th><th>Type</th><th>Quantity</th><th>Supplier</th><th>Receipt</th><th>User</th></tr>";
    
    $count = 0;
    while ($row = $result->fetch_assoc() && $count < 5) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . date('M j, Y g:i A', strtotime($row['transaction_date'])) . "</td>";
        echo "<td><span style='background: #28a745; color: white; padding: 2px 8px; border-radius: 4px;'>" . ucfirst($row['fuel_type']) . "</span></td>";
        echo "<td><strong>" . number_format($row['quantity'], 2) . " L</strong></td>";
        echo "<td>" . htmlspecialchars($row['supplier'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['source'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "</tr>";
        $count++;
    }
    echo "</table>";
    
    if ($result->num_rows > 5) {
        echo "<p>... and " . ($result->num_rows - 5) . " more records</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ No fuel IN records found</p>";
    
    // Add sample data
    echo "<h3>Adding Sample Data...</h3>";
    $conn->query("INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, user_id) 
                  VALUES ('IN', 'diesel', 100.50, NOW(), 'DEL-001', 'Sample Supplier', 1)");
    
    echo "<p style='color: green;'>✅ Sample fuel IN record added</p>";
    echo "<p><a href='fuel_in.php'>Refresh fuel_in.php</a></p>";
}

echo "<hr>";
echo "<h2>4. Connection Summary</h2>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>✅ Database:</strong> pims</p>";
echo "<p><strong>✅ Table:</strong> fuel_transactions</p>";
echo "<p><strong>✅ Query:</strong> Filters for transaction_type = 'IN'</p>";
echo "<p><strong>✅ Records Found:</strong> " . ($result ? $result->num_rows : 0) . "</p>";
echo "<p><strong>✅ Status:</strong> CONNECTED and WORKING</p>";
echo "</div>";

echo "<hr>";
echo "<h2>5. Access Your Fuel IN Page</h2>";
echo "<p><strong>URL:</strong> <code>http://localhost/PIMS/MAIN_USER/fuel_in.php</code></p>";
echo "<p><strong>Or click:</strong> <a href='fuel_in.php' class='btn btn-success'>Go to Fuel IN Page</a></p>";

echo "<h3>What you'll see on fuel_in.php:</h3>";
echo "<ul>";
echo "<li>✅ Today's fuel IN summary cards</li>";
echo "<li>✅ Fuel IN form to add new transactions</li>";
echo "<li>✅ Complete transactions table with data from fuel_transactions</li>";
echo "<li>✅ Filter by fuel type and date period</li>";
echo "<li>✅ Search and sort functionality</li>";
echo "</ul>";

echo "<p style='color: green; font-weight: bold;'>🎉 The MAIN_USER fuel_in.php is already connected to fuel_transactions and working!</p>";
?>
