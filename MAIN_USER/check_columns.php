<?php
require_once '../config.php';

// Check fuel_in columns
$result = $conn->query("SHOW COLUMNS FROM fuel_in");
echo "<h3>fuel_in columns:</h3><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
}
echo "</ul>";

// Check fuel_out columns
$result2 = $conn->query("SHOW COLUMNS FROM fuel_out");
echo "<h3>fuel_out columns:</h3><ul>";
while ($row = $result2->fetch_assoc()) {
    echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
}
echo "</ul>";
?>
