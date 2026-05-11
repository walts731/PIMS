<?php
require_once 'c:/xampp/htdocs/PIMS/config.php';
$stmt = $conn->query("SELECT * FROM notifications ORDER BY id DESC LIMIT 5");
$notifications = [];
while ($row = $stmt->fetch_assoc()) {
    $notifications[] = $row;
}
echo json_encode($notifications, JSON_PRETTY_PRINT);
?>
