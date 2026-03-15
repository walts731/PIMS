<?php
require_once '../config.php';

echo "<h1>Fuel Transactions Database Table Display</h1>";

// Get all fuel IN transactions from fuel_transactions
echo "<h2>Fuel IN Transactions - Complete Database View</h2>";

$query = "SELECT ft.*, u.first_name, u.last_name 
         FROM fuel_transactions ft 
         LEFT JOIN users u ON ft.user_id = u.id 
         WHERE ft.transaction_type = 'IN' 
         ORDER BY ft.transaction_date DESC";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Found " . $result->num_rows . " fuel IN transactions</p>";
    
    // Display as database table
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered table-striped' style='background: white;'>";
    echo "<thead class='table-dark'>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Transaction Type</th>";
    echo "<th>Fuel Type</th>";
    echo "<th>Quantity (L)</th>";
    echo "<th>Transaction Date</th>";
    echo "<th>Source</th>";
    echo "<th>Supplier</th>";
    echo "<th>Tank Number</th>";
    echo "<th>Recipient Name</th>";
    echo "<th>Purpose</th>";
    echo "<th>Vehicle Equipment</th>";
    echo "<th>User ID</th>";
    echo "<th>Notes</th>";
    echo "<th>Image</th>";
    echo "<th>Created At</th>";
    echo "<th>Updated At</th>";
    echo "<th>Recorded By</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><span class='badge bg-success'>" . $row['transaction_type'] . "</span></td>";
        echo "<td><span class='badge bg-primary'>" . ucfirst($row['fuel_type']) . "</span></td>";
        echo "<td><strong>" . number_format($row['quantity'], 2) . "</strong></td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['transaction_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['source'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['supplier'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['tank_number'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['recipient_name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['purpose'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['vehicle_equipment'] ?? 'N/A') . "</td>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['notes'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($row['image'] ?? 'N/A') . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['updated_at'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    
    // Summary statistics
    echo "<h3>Summary Statistics</h3>";
    echo "<div class='row'>";
    
    // Total fuel IN
    $total_query = "SELECT SUM(quantity) as total, COUNT(*) as count FROM fuel_transactions WHERE transaction_type = 'IN'";
    $total_result = $conn->query($total_query);
    $total_data = $total_result->fetch_assoc();
    
    echo "<div class='col-md-3'>";
    echo "<div class='card bg-success text-white'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>Total Fuel IN</h5>";
    echo "<p class='card-text display-6'>" . number_format($total_data['total'], 2) . " L</p>";
    echo "<small>" . $total_data['count'] . " transactions</small>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // By fuel type
    $by_type_query = "SELECT fuel_type, SUM(quantity) as total, COUNT(*) as count FROM fuel_transactions WHERE transaction_type = 'IN' GROUP BY fuel_type";
    $by_type_result = $conn->query($by_type_query);
    
    while ($type_data = $by_type_result->fetch_assoc()) {
        echo "<div class='col-md-3'>";
        echo "<div class='card bg-info text-white'>";
        echo "<div class='card-body'>";
        echo "<h5 class='card-title'>" . ucfirst($type_data['fuel_type']) . "</h5>";
        echo "<p class='card-text display-6'>" . number_format($type_data['total'], 2) . " L</p>";
        echo "<small>" . $type_data['count'] . " transactions</small>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h4>No Fuel IN Transactions Found</h4>";
    echo "<p>There are currently no fuel IN transactions in the fuel_transactions table.</p>";
    echo "<hr>";
    echo "<h5>Add Sample Data:</h5>";
    
    // Add sample fuel IN transactions
    $sample_data = [
        ['diesel', 100.50, 'DEL-001', 'Petron Corporation'],
        ['gasoline', 75.25, 'DEL-002', 'Shell Philippines'],
        ['premium', 50.00, 'DEL-003', 'Chevron Philippines']
    ];
    
    foreach ($sample_data as $data) {
        $conn->query("INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, user_id) 
                      VALUES ('IN', '$data[0]', $data[1], NOW(), '$data[2]', '$data[3]', 1)");
    }
    
    echo "<p style='color: green;'>✅ Added 3 sample fuel IN transactions</p>";
    echo "<p><a href=''>Refresh this page</a></p>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Database Table Information</h2>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h4>Table Structure</h4>";
echo "<table class='table table-sm'>";
echo "<tr><th>Column</th><th>Type</th><th>Description</th></tr>";

$columns = [
    'id' => 'INT (Primary Key)',
    'transaction_type' => 'ENUM (IN/OUT)',
    'fuel_type' => 'ENUM (diesel/gasoline/premium)',
    'quantity' => 'DECIMAL (10,2)',
    'transaction_date' => 'DATETIME',
    'source' => 'VARCHAR (Receipt #)',
    'supplier' => 'VARCHAR (Supplier name)',
    'tank_number' => 'VARCHAR (Tank location)',
    'recipient_name' => 'VARCHAR (Who received)',
    'purpose' => 'TEXT (Purpose of fuel)',
    'vehicle_equipment' => 'VARCHAR (Vehicle ID)',
    'user_id' => 'INT (Who recorded)',
    'notes' => 'TEXT (Additional notes)',
    'image' => 'VARCHAR (Image path)',
    'created_at' => 'TIMESTAMP',
    'updated_at' => 'TIMESTAMP'
];

foreach ($columns as $col => $type) {
    echo "<tr><td><code>$col</code></td><td>$type</td><td></td></tr>";
}
echo "</table>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h4>Query Used</h4>";
echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px; font-size: 12px;'>";
echo "SELECT ft.*, u.first_name, u.last_name 
FROM fuel_transactions ft 
LEFT JOIN users u ON ft.user_id = u.id 
WHERE ft.transaction_type = 'IN' 
ORDER BY ft.transaction_date DESC";
echo "</pre>";

echo "<h4>Filter Options</h4>";
echo "<ul>";
echo "<li><strong>By Fuel Type:</strong> diesel, gasoline, premium</li>";
echo "<li><strong>By Date Range:</strong> Today, This Week, This Month</li>";
echo "<li><strong>By Supplier:</strong> Any supplier name</li>";
echo "<li><strong>By Quantity:</strong> Min/Max amounts</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<h2>Access Options</h2>";
echo "<div class='btn-group'>";
echo "<a href='fuel_in.php' class='btn btn-primary'>Fuel IN Page</a>";
echo "<a href='../FUEL/dashboard.php?tab=fuelin' class='btn btn-success'>FUEL Dashboard</a>";
echo "<a href='test_connection.php' class='btn btn-info'>Test Connection</a>";
echo "</div>";

echo "<style>";
echo ".table { font-size: 12px; }";
echo ".table th { background: #343a40; color: white; }";
echo ".badge { font-size: 10px; }";
echo ".card { margin-bottom: 10px; }";
echo ".display-6 { font-size: 1.5rem; }";
echo "</style>";
?>
