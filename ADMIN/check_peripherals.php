<?php
require_once '../config.php';

echo "Checking peripherals table structure...\n";
$result = $conn->query('DESCRIBE peripherals');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
$conn->close();
?>
