<?php
require_once 'config.php';

echo "Checking property numbers in asset_items table:\n";
echo "==========================================\n";

$query = "SELECT property_number FROM asset_items WHERE property_number IS NOT NULL AND property_number != '' ORDER BY id DESC LIMIT 10";
$result = $conn->query($query);

if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo $row['property_number'] . "\n";
        }
    } else {
        echo "No property numbers found in asset_items table\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n\nTesting the series extraction query:\n";
echo "=====================================\n";

$current_year = date('Y');
$pattern = $current_year . '-%';

$query = "SELECT MAX(CAST(RIGHT(SUBSTRING_INDEX(SUBSTRING_INDEX(property_number, '-', -2), '-', 1), 2) AS UNSIGNED)) as max_series 
          FROM asset_items 
          WHERE property_number LIKE ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $max_series = $row['max_series'];
        echo "Current max series: " . ($max_series ?: 'NULL') . "\n";
        if ($max_series) {
            $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
            echo "Next series should be: " . $next_series . "\n";
        }
    } else {
        echo "No results found\n";
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error . "\n";
}

$conn->close();
?>
