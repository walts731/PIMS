<?php
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

// Get parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$fuel_type_filter = $_GET['fuel_type'] ?? '';
$tank_filter = $_GET['tank'] ?? '';

// Build query
$where_conditions = ["transaction_date BETWEEN '$start_date' AND '$end_date'"];
$params = [];
$types = '';

if ($fuel_type_filter) {
    $where_conditions[] = "fuel_type = ?";
    $params[] = $fuel_type_filter;
    $types .= 's';
}

if ($tank_filter) {
    $where_conditions[] = "tank_number = ?";
    $params[] = $tank_filter;
    $types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get transactions
$transactions_query = "SELECT ft.*, u.first_name, u.last_name, e.firstname, e.lastname as employee_lastname
                      FROM fuel_transactions ft 
                      LEFT JOIN users u ON ft.user_id = u.id 
                      LEFT JOIN employees e ON ft.employee_id = e.id
                      WHERE $where_clause
                      ORDER BY ft.transaction_date DESC, ft.created_at DESC";

$stmt = $conn->prepare($transactions_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions_result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fuel_report_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fwrite($output, "\xEF\xBB\xBF");

// CSV headers
$headers = [
    'Transaction ID',
    'Date',
    'Time',
    'Transaction Type',
    'Fuel Type',
    'Quantity (Liters)',
    'Employee/Recipient',
    'Purpose/Source',
    'Supplier/Vehicle',
    'Odometer',
    'Recorded By',
    'Notes'
];

fputcsv($output, $headers);

// Data rows
while ($transaction = $transactions_result->fetch_assoc()) {
    $row = [
        $transaction['id'],
        date('Y-m-d', strtotime($transaction['transaction_date'])),
        date('H:i', strtotime($transaction['transaction_date'])),
        $transaction['transaction_type'],
        ucfirst($transaction['fuel_type']),
        number_format($transaction['quantity'], 2),
        $transaction['transaction_type'] == 'IN' 
            ? ($transaction['source'] ?? 'N/A')
            : (
                $transaction['employee_id'] 
                    ? ($transaction['firstname'] . ' ' . $transaction['employee_lastname'])
                    : ($transaction['recipient_name'] ?? 'N/A')
            ),
        $transaction['transaction_type'] == 'IN' 
            ? ($transaction['supplier'] ?? 'N/A')
            : ($transaction['purpose'] ?? 'N/A'),
        $transaction['transaction_type'] == 'IN' 
            ? 'N/A'
            : ($transaction['vehicle_equipment'] ?? 'N/A'),
        $transaction['odometer_reading'] ? number_format($transaction['odometer_reading']) . ' ' . ($transaction['odometer_unit'] ?? '') : 'N/A',
        $transaction['first_name'] . ' ' . $transaction['last_name'],
        $transaction['notes'] ?? ''
    ];
    
    fputcsv($output, $row);
}

// Summary section
fputcsv($output, []);
fputcsv($output, ['SUMMARY REPORT']);
fputcsv($output, ['Report Period:', $start_date . ' to ' . $end_date]);
fputcsv($output, ['Fuel Type Filter:', $fuel_type_filter ?: 'All']);
fputcsv($output, []);

// Calculate summary
$summary_query = "SELECT 
                    fuel_type,
                    transaction_type,
                    SUM(quantity) as total_quantity,
                    COUNT(*) as transaction_count
                 FROM fuel_transactions 
                 WHERE $where_clause
                 GROUP BY fuel_type, transaction_type
                 ORDER BY fuel_type, transaction_type";

$stmt = $conn->prepare($summary_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$summary_result = $stmt->get_result();

fputcsv($output, ['FUEL TYPE SUMMARY']);
fputcsv($output, ['Fuel Type', 'Transaction Type', 'Total Quantity (L)', 'Number of Transactions']);

while ($row = $summary_result->fetch_assoc()) {
    fputcsv($output, [
        ucfirst($row['fuel_type']),
        $row['transaction_type'],
        number_format($row['total_quantity'], 2),
        $row['transaction_count']
    ]);
}

fputcsv($output, []);

// Net change summary
$net_summary_query = "SELECT 
                        fuel_type,
                        SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE 0 END) as total_in,
                        SUM(CASE WHEN transaction_type = 'OUT' THEN quantity ELSE 0 END) as total_out,
                        SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE 0 END) - 
                        SUM(CASE WHEN transaction_type = 'OUT' THEN quantity ELSE 0 END) as net_change
                      FROM fuel_transactions 
                      WHERE $where_clause
                      GROUP BY fuel_type
                      ORDER BY fuel_type";

$stmt = $conn->prepare($net_summary_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$net_summary_result = $stmt->get_result();

fputcsv($output, ['NET CHANGE SUMMARY']);
fputcsv($output, ['Fuel Type', 'Total In (L)', 'Total Out (L)', 'Net Change (L)']);

while ($row = $net_summary_result->fetch_assoc()) {
    fputcsv($output, [
        ucfirst($row['fuel_type']),
        number_format($row['total_in'], 2),
        number_format($row['total_out'], 2),
        number_format($row['net_change'], 2)
    ]);
}

fputcsv($output, []);
fputcsv($output, ['REPORT GENERATED ON:', date('Y-m-d H:i:s')]);
fputcsv($output, ['GENERATED BY:', $_SESSION['first_name'] . ' ' . $_SESSION['last_name']]);

// Close output stream
fclose($output);
exit();
?>
