<?php
include '../config.php';
$res = $conn->query("DESCRIBE asset_items");
while($row = $res->fetch_assoc()) echo $row['Field'] . "\n";
?>
