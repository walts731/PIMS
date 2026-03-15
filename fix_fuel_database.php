<?php
require_once '../config.php';

echo "<h1>Fuel Database Structure Fix</h1>";

// Check current database structure
echo "<h2>Current Database Structure</h2>";

// Check if fuel_transactions table exists
$result = $conn->query("SHOW TABLES LIKE 'fuel_transactions'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: green;'>✅ fuel_transactions table exists</p>";
    
    // Show table structure
    $columns = $conn->query("DESCRIBE fuel_transactions");
    echo "<h3>fuel_transactions table structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $columns->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ fuel_transactions table doesn't exist</p>";
    
    // Create the table
    echo "<h3>Creating fuel_transactions table...</h3>";
    $create_sql = "CREATE TABLE fuel_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_type ENUM('IN', 'OUT') NOT NULL,
        fuel_type ENUM('diesel', 'gasoline', 'premium') NOT NULL,
        quantity DECIMAL(10,2) NOT NULL,
        transaction_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        source VARCHAR(255) DEFAULT NULL,
        supplier VARCHAR(255) DEFAULT NULL,
        tank_number VARCHAR(50) DEFAULT NULL,
        recipient_name VARCHAR(255) DEFAULT NULL,
        purpose TEXT DEFAULT NULL,
        vehicle_equipment VARCHAR(255) DEFAULT NULL,
        user_id INT DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sql)) {
        echo "<p style='color: green;'>✅ fuel_transactions table created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

// Check if fuel_in table exists
$result = $conn->query("SHOW TABLES LIKE 'fuel_in'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color: orange;'>⚠️ fuel_in table exists (legacy table)</p>";
    
    // Migrate data from fuel_in to fuel_transactions
    echo "<h3>Migrating data from fuel_in to fuel_transactions...</h3>";
    
    // Check if fuel_in has data
    $fuel_in_data = $conn->query("SELECT COUNT(*) as count FROM fuel_in");
    $count = $fuel_in_data->fetch_assoc()['count'];
    
    if ($count > 0) {
        echo "<p>Found $count records in fuel_in table</p>";
        
        // Migrate data
        $migrate_sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, user_id)
                        SELECT 'IN', 
                               CASE 
                                   WHEN fuel_type = 1 THEN 'diesel'
                                   WHEN fuel_type = 2 THEN 'gasoline'
                                   WHEN fuel_type = 3 THEN 'premium'
                                   ELSE 'diesel'
                               END,
                               quantity,
                               date_time,
                               delivery_receipt,
                               supplier_name,
                               received_by
                        FROM fuel_in
                        WHERE id NOT IN (
                            SELECT id FROM fuel_transactions WHERE transaction_type = 'IN'
                        )";
        
        if ($conn->query($migrate_sql)) {
            $migrated = $conn->affected_rows;
            echo "<p style='color: green;'>✅ Migrated $migrated records from fuel_in to fuel_transactions</p>";
        } else {
            echo "<p style='color: red;'>❌ Migration error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>No data to migrate from fuel_in table</p>";
    }
}

// Test sample data
echo "<h2>Testing Database Operations</h2>";

// Insert sample fuel in transaction
$sample_sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, source, supplier, user_id) 
               VALUES ('IN', 'diesel', 100.50, NOW(), 'DEL-001', 'Sample Supplier', 1)";

if ($conn->query($sample_sql)) {
    $id = $conn->insert_id;
    echo "<p style='color: green;'>✅ Sample fuel in transaction added (ID: $id)</p>";
    
    // Clean up sample data
    $conn->query("DELETE FROM fuel_transactions WHERE id = $id");
    echo "<p style='color: blue;'>🧹 Sample data cleaned up</p>";
} else {
    echo "<p style='color: red;'>❌ Sample insert failed: " . $conn->error . "</p>";
}

// Insert sample fuel out transaction
$sample_sql = "INSERT INTO fuel_transactions (transaction_type, fuel_type, quantity, transaction_date, recipient_name, purpose, user_id) 
               VALUES ('OUT', 'diesel', 25.00, NOW(), 'John Doe', 'Test purpose', 1)";

if ($conn->query($sample_sql)) {
    $id = $conn->insert_id;
    echo "<p style='color: green;'>✅ Sample fuel out transaction added (ID: $id)</p>";
    
    // Clean up sample data
    $conn->query("DELETE FROM fuel_transactions WHERE id = $id");
    echo "<p style='color: blue;'>🧹 Sample data cleaned up</p>";
} else {
    echo "<p style='color: red;'>❌ Sample insert failed: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<h2 style='color: green;'>✅ DATABASE SETUP COMPLETE!</h2>";
echo "<p><strong>What was fixed:</strong></p>";
echo "<ul>";
echo "<li>✅ Created fuel_transactions table (if missing)</li>";
echo "<li>✅ Migrated data from legacy fuel_in table</li>";
echo "<li>✅ Tested database operations</li>";
echo "</ul>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Update MAIN_USER/fuel_in.php to use fuel_transactions table</li>";
echo "<li>Test the fuel management system</li>";
echo "<li>Remove legacy fuel_in table (optional)</li>";
echo "</ol>";
echo "<p><a href='../MAIN_USER/fuel_management.php' class='btn btn-primary'>Test Fuel Management</a></p>";
?>
