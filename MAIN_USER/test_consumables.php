<?php
require_once '../config.php';

// Get all column names from consumables table
echo "<h2>Consumables Table Structure:</h2>";
$result = $conn->query("DESCRIBE consumables");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Show sample data
echo "<h2>Sample Data (First 3 rows):</h2>";
$result2 = $conn->query("SELECT * FROM consumables LIMIT 3");
if ($result2) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    // Get column names
    $fields = $result2->fetch_fields();
    echo "<tr>";
    foreach ($fields as $field) {
        echo "<th>" . $field->name . "</th>";
    }
    echo "</tr>";
    
    // Show data
    $result2->data_seek(0); // Reset pointer
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<td>" . ($row[$field->name] ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}
?>
