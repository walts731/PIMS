<?php
require_once 'config.php';

echo "asset_computers table structure:\n";
$result = mysqli_query($conn, 'DESCRIBE asset_computers');
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Key'] . "\n";
}

echo "\n\nasset_desktop_computers table structure:\n";
$result = mysqli_query($conn, 'DESCRIBE asset_desktop_computers');
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Key'] . "\n";
}
?>
