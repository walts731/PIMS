<?php
require_once 'config.php';

try {
    $sql = "ALTER TABLE asset_items MODIFY COLUMN image TEXT DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "Success: image column changed to TEXT type. Multiple images can now be stored as JSON.";
    } else {
        echo "Error: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
$conn->close();
?>
