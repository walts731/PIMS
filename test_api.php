<?php
require_once 'config.php';

echo "Testing the FIXED series extraction query:\n";
echo "==========================================\n";

$current_year = date('Y');
$pattern = $current_year . '-%';

$query = "SELECT MAX(CAST(RIGHT(SUBSTRING_INDEX(SUBSTRING_INDEX(property_no, '-', -2), '-', 1), 2) AS UNSIGNED)) as max_series 
          FROM asset_items 
          WHERE property_no LIKE ?";

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
        } else {
            echo "No series found, will default to 01\n";
        }
    } else {
        echo "No results found\n";
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error . "\n";
}

echo "\n\nLet's also test the API directly:\n";
echo "=================================\n";

// Simulate the API call
$_GET['test'] = true; // Just to indicate this is a test

// Get next series number for current year
$next_series = '01';
$current_year = date('Y');

// Query to get the maximum series number for the current year
$query = "SELECT MAX(CAST(RIGHT(SUBSTRING_INDEX(SUBSTRING_INDEX(property_no, '-', -2), '-', 1), 2) AS UNSIGNED)) as max_series 
          FROM asset_items 
          WHERE property_no LIKE ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $pattern = $current_year . '-%';
    $stmt->bind_param('s', $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $max_series = $row['max_series'];
        if ($max_series) {
            $next_series = str_pad($max_series + 1, 2, '0', STR_PAD_LEFT);
        }
    }
    $stmt->close();
}

echo "API Response:\n";
echo json_encode([
    'success' => true,
    'next_series' => $next_series,
    'current_year' => $current_year
]);

$conn->close();
?>
