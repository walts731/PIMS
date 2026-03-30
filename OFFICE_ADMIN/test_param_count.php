<?php
// Test parameter counting
echo "<h2>Parameter Count Test</h2>";

// Count placeholders in SQL
$sql = "SELECT id, description, model, serial_number, property_no, status, value, office_name, end_user, 'asset' as type, (CASE WHEN description LIKE ? THEN 10 WHEN model LIKE ? THEN 8 WHEN serial_number LIKE ? THEN 9 WHEN property_no LIKE ? THEN 7 WHEN end_user LIKE ? THEN 6 ELSE 1 END) as relevance FROM asset_items WHERE office_id = ? AND (description LIKE ? OR model LIKE ? OR serial_number LIKE ? OR property_no LIKE ? OR end_user LIKE ?) ORDER BY relevance DESC, description ASC LIMIT ?";

$placeholder_count = substr_count($sql, '?');
echo "SQL placeholders: $placeholder_count<br>";

// Test parameter array
$params = ['%laptop%', '%laptop%', '%laptop%', '%laptop%', '%laptop%', 5, '%laptop%', '%laptop%', '%laptop%', '%laptop%', 10];
echo "Parameter count: " . count($params) . "<br>";

// Test type string
$types = 'ssssssssssi';
echo "Type string length: " . strlen($types) . "<br>";

echo "<h3>Match Check:</h3>";
echo "Placeholders == Parameters: " . ($placeholder_count == count($params) ? "✅ Yes" : "❌ No") . "<br>";
echo "Parameters == Type Length: " . (count($params) == strlen($types) ? "✅ Yes" : "❌ No") . "<br>";

echo "<h3>Parameter Details:</h3>";
foreach ($params as $i => $param) {
    echo "[$i] " . (is_string($param) ? "'$param'" : $param) . " (type: " . gettype($param) . ")<br>";
}
?>
