<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get infrastructure data
$infrastructure_data = [];
$sql = "SELECT * FROM infrastructure ORDER BY date_constructed DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $infrastructure_data[] = $row;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="infrastructure_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'Classification/Type',
    'Item Description',
    'Nature Occupancy',
    'Location',
    'Date Constructed',
    'Property No./Other Reference',
    'Acquisition Cost',
    'Market/Appraisal Value',
    'Date of Appraisal',
    'Remarks',
    'Created At'
];

fputcsv($output, $headers);

// CSV data
foreach ($infrastructure_data as $item) {
    $row = [
        $item['classification'],
        $item['item_description'],
        $item['nature_occupancy'] ?? '',
        $item['location'],
        date('m/d/Y', strtotime($item['date_constructed'])),
        $item['property_no'] ?? '',
        number_format($item['acquisition_cost'], 2),
        number_format($item['market_value'], 2),
        $item['date_appraisal'] ? date('m/d/Y', strtotime($item['date_appraisal'])) : '',
        $item['remarks'] ?? '',
        date('m/d/Y h:i A', strtotime($item['created_at']))
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
