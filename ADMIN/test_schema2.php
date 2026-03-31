<?php
require '../config.php';
$res = $conn->query("DESCRIBE ics_items");
while($r = $res->fetch_assoc()) echo $r['Field'].' | '.$r['Type'].' | '.$r['Null'].' | '.$r['Default']."\n";
?>
