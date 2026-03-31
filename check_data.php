<?php
require_once 'config.php';
$result = $conn->query("SELECT * FROM system_log ORDER BY created_at DESC LIMIT 10");
while($row = $result->fetch_assoc()) {
    echo "[" . $row['created_at'] . "] " . $row['action'] . " - " . $row['details'] . "\n";
}
echo "\n--- CONSUMABLES (FIRST 5) ---\n";
$result = $conn->query("SELECT * FROM consumables LIMIT 5");
while($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
