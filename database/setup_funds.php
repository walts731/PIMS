<?php
// Database setup script for PIMS Funds Management
require_once __DIR__ . '/../config.php';

echo "<h2>PIMS Funds Database Setup</h2>";

try {
    // Read SQL file
    $sqlFile = __DIR__ . '/../database_funds_setup.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: " . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    echo "<h3>Creating funds management tables...</h3>";
    
    // Split SQL into individual statements - handle multi-line statements properly
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Skip comments and empty lines
        if (empty($trimmedLine) || strpos($trimmedLine, '--') === 0) {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Check if statement ends with semicolon
        if (substr($trimmedLine, -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
            
            try {
                $result = $conn->query($statement);
                if ($result) {
                    echo "<span style='color: green;'>✓ Success</span><br>";
                } else {
                    echo "<span style='color: orange;'>⚠ No result (may be expected)</span><br>";
                }
            } catch (Exception $e) {
                // Check if it's a "table already exists" error
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "<span style='color: blue;'>ℹ Table already exists (skipped)</span><br>";
                } else {
                    echo "<span style='color: red;'>✗ Error: " . $e->getMessage() . "</span><br>";
                }
            }
        }
    }
    
    echo "<h3>Verifying funds tables...</h3>";
    
    // Check if funds tables exist
    $tables = ['funds', 'fund_transactions', 'fund_allocations'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<span style='color: green;'>✓ Table '$table' exists</span><br>";
            
            // Show row count
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc();
            echo "  &nbsp; Records: " . $count['count'] . "<br>";
        } else {
            echo "<span style='color: red;'>✗ Table '$table' not found</span><br>";
        }
    }
    
    // Show sample funds data
    echo "<h3>Sample Funds Data:</h3>";
    $funds = $conn->query("SELECT fund_code, fund_name, fund_cluster, budget_year, initial_amount, current_balance, status FROM funds ORDER BY fund_code");
    if ($funds->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Fund Code</th><th>Fund Name</th><th>Fund Cluster</th><th>Budget Year</th><th>Initial Amount</th><th>Current Balance</th><th>Status</th></tr>";
        while ($row = $funds->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['fund_code']) . "</td>";
            echo "<td>" . htmlspecialchars($row['fund_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['fund_cluster']) . "</td>";
            echo "<td>" . htmlspecialchars($row['budget_year']) . "</td>";
            echo "<td>" . number_format($row['initial_amount'], 2) . "</td>";
            echo "<td>" . number_format($row['current_balance'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No funds data found.</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p><a href='../SYSTEM_ADMIN/funds.php'>Go to Funds Management</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Setup Failed</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3, h4 {
    color: #333;
}
p {
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin: 5px 0;
}
table {
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}
th {
    background: #f2f2f2;
    font-weight: bold;
}
</style>
