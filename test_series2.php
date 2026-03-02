<?php
require_once 'config.php';

echo "Checking property_no field in asset_items table:\n";
echo "=============================================\n";

$query = "SELECT property_no FROM asset_items WHERE property_no IS NOT NULL AND property_no != '' ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo $row['property_no'] . "\n";
        }
    } else {
        echo "No property_no found in asset_items table\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n\nChecking if the specific property number exists:\n";
echo "==============================================\n";

$specific_number = "2026-07-05-070-0905-01";
$query = "SELECT property_no FROM asset_items WHERE property_no = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('s', $specific_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        echo "Found: $specific_number\n";
    } else {
        echo "Not found: $specific_number\n";
    }
    $stmt->close();
}

echo "\n\nLet's check all tables that might contain property numbers:\n";
echo "====================================================\n";

$tables = ['asset_items', 'par_items', 'assets'];
foreach ($tables as $table) {
    echo "\nChecking table: $table\n";
    $columns = $conn->query("SHOW COLUMNS FROM $table");
    if ($columns) {
        while ($col = $columns->fetch_assoc()) {
            if (strpos($col['Field'], 'property') !== false) {
                echo "  Found column: " . $col['Field'] . "\n";
                $check = $conn->query("SELECT " . $col['Field'] . " FROM $table WHERE " . $col['Field'] . " LIKE '%2026-07-05-070-0905-01%' LIMIT 1");
                if ($check && $check->num_rows > 0) {
                    echo "    -> Contains the property number!\n";
                }
            }
        }
    }
}

$conn->close();
?>
