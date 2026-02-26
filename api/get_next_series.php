<?php
// API endpoint to get next available series number for property numbers
require_once '../config.php';

header('Content-Type: application/json');

// Get next series number for current year
$next_series = '01';
$current_year = date('Y');

// Query to get the maximum series number for the current year
// Property number format: YYYY-FORMTYPE-FUND-CATEGORY-SUBCATEGORY+SERIES-OFFICE
// We need to extract the SERIES part (last 2 digits of the subcategory+series combination)
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

// Return JSON response
echo json_encode([
    'success' => true,
    'next_series' => $next_series,
    'current_year' => $current_year
]);
?>
