<?php
require_once 'config.php';

echo "Resetting monitor status for testing...\n";

$stmt = $conn->prepare("UPDATE asset_desktop_computers SET monitor_status = 'serviceable' WHERE asset_item_id = 15");
$stmt->execute();

echo "✓ Monitor status reset to serviceable\n";
?>
