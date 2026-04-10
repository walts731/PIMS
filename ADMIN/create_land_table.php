<?php
require_once '../config.php';

$sql = "CREATE TABLE IF NOT EXISTS asset_land_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_item_id INT NOT NULL UNIQUE,
    lot_number VARCHAR(255),
    area_size VARCHAR(255),
    location TEXT,
    tax_declaration VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    updated_by INT
)";

if ($conn->query($sql)) {
    echo "Table asset_land_info created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
