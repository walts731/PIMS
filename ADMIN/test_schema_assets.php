<?php
require '../config.php';
$res = $conn->query("DESCRIBE assets");
$fields = [];
while($r = $res->fetch_assoc()) $fields[] = $r['Field'];
echo implode(", ", $fields);
?>
