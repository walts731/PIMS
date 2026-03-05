<?php
require_once '../config.php';

echo "<h2>Fuel Tables Structure Check</h2>";

// Check fuel_in table
echo "<h3>fuel_in table structure:</h3>";
$result = $conn->query("DESCRIBE fuel_in");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "fuel_in table not found or error: " . $conn->error;
}

// Check fuel_out table
echo "<h3>fuel_out table structure:</h3>";
$result = $conn->query("DESCRIBE fuel_out");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "fuel_out table not found or error: " . $conn->error;
}

// Check fuel_transactions table
echo "<h3>fuel_transactions table structure:</h3>";
$result = $conn->query("DESCRIBE fuel_transactions");
if ($result) {
    echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "fuel_transactions table not found or error: " . $conn->error;
}

// Show sample data from fuel_transactions
echo "<h3>Sample data from fuel_transactions:</h3>";
$result = $conn->query("SELECT * FROM fuel_transactions LIMIT 5");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'><tr>";
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th>{$field->name}</th>";
    }
    echo "</tr>";
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data in fuel_transactions or table not found";
}
?>
