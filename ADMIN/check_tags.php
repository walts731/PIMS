<?php
require_once '../config.php';
global $conn;

$result = $conn->query('SELECT id, inventory_tag FROM asset_items WHERE inventory_tag IS NOT NULL AND inventory_tag != "" LIMIT 5');

if ($result && $result->num_rows > 0) {
    echo "Found tags:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Tag: " . $row['inventory_tag'] . "\n";
    }
} else {
    echo "No inventory tags found in database\n";
}
?>
