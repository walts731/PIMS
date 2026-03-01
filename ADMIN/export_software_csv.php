<?php
session_start();
require_once '../config.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Get software data
$software_data = [];
$sql = "SELECT * FROM software ORDER BY software_name ASC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $software_data[] = $row;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="software_export_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'Software Name',
    'Category',
    'Description',
    'Vendor',
    'Version',
    'License Type',
    'License Key',
    'Purchase Date',
    'Purchase Cost',
    'Renewal Date',
    'Renewal Cost',
    'Status',
    'Assigned To',
    'Installation Date',
    'Notes',
    'Created At'
];

fputcsv($output, $headers);

// CSV data
foreach ($software_data as $software) {
    $row = [
        $software['software_name'],
        $software['category'],
        $software['description'] ?? '',
        $software['vendor'],
        $software['version'] ?? '',
        $software['license_type'],
        $software['license_key'] ?? '',
        date('m/d/Y', strtotime($software['purchase_date'])),
        number_format($software['purchase_cost'], 2),
        $software['renewal_date'] ? date('m/d/Y', strtotime($software['renewal_date'])) : '',
        number_format($software['renewal_cost'], 2),
        ucfirst($software['status']),
        $software['assigned_to'] ?? '',
        $software['installation_date'] ? date('m/d/Y', strtotime($software['installation_date'])) : '',
        $software['notes'] ?? '',
        date('m/d/Y h:i A', strtotime($software['created_at']))
    ];
    
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
