<?php
require_once 'c:/xampp/htdocs/PIMS/config.php';
$stmt = $conn->query("SELECT id, username, role FROM users");
$users = [];
while ($row = $stmt->fetch_assoc()) {
    $users[] = $row;
}
echo json_encode($users, JSON_PRETTY_PRINT);
?>
