<?php
require_once '../config.php';

// Check consumables table structure
$result = $conn->query("SHOW COLUMNS FROM consumables");
echo "<h3>consumables columns:</h3><ul>";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
    }
} else {
    echo "<li>Error: " . $conn->error . "</li>";
}
echo "</ul>";

// Check offices table structure
$result2 = $conn->query("SHOW COLUMNS FROM offices");
echo "<h3>offices columns:</h3><ul>";
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        echo "<li>" . $row['Field'] . " - " . $row['Type'] . "</li>";
    }
} else {
    echo "<li>Error: " . $conn->error . "</li>";
}
echo "</ul>";

// Sample data from consumables
$result3 = $conn->query("SELECT * FROM consumables LIMIT 5");
echo "<h3>Sample consumables data:</h3><pre>";
if ($result3) {
    while ($row = $result3->fetch_assoc()) {
        print_r($row);
        echo "<br>";
    }
} else {
    echo "Error: " . $conn->error;
}
echo "</pre>";
?>
