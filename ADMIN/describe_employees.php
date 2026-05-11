<?php
require_once 'c:/xampp/htdocs/PIMS/config.php';
$res = $conn->query('DESCRIBE employees');
while($row = $res->fetch_assoc()) {
    echo json_encode($row).PHP_EOL;
}
?>
