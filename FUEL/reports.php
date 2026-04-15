<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pims';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'day';
$fuel_type_filter = $_GET['fuel_type'] ?? '';
$oil_type_filter = $_GET['oil_type'] ?? '';
$transaction_type = $_GET['transaction_type'] ?? '';

// Calculate date range based on period
switch($period) {
    case 'day':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    default:
        $start_date = $_GET['start_date'] ?? date('Y-m-d');
        $end_date = $_GET['end_date'] ?? date('Y-m-d');
        break;
}

// Get fuel in data with filters
$show_fuel_in = (empty($transaction_type) || $transaction_type === 'FUEL IN');
$fuel_in_data = [];

if ($show_fuel_in) {
    $fuel_in_sql = "SELECT fi.id, fi.date_time, fi.fuel_type, fi.quantity, fi.unit_price, fi.total_cost, 
                            fi.storage_location, fi.delivery_receipt, fi.supplier_name, fi.received_by, 
                            fi.remarks, fi.created_by, fi.created_at, fi.transaction_id, fi.image,
                            ft.name as fuel_type_name
                     FROM fuel_in fi
                     LEFT JOIN fuel_types ft ON fi.fuel_type = ft.id
                     WHERE DATE(fi.date_time) BETWEEN ? AND ?";

    $fuel_in_params = [$start_date, $end_date];
    $fuel_in_types = "ss";

    if (!empty($fuel_type_filter)) {
        $fuel_in_sql .= " AND ft.name = ?";
        $fuel_in_params[] = $fuel_type_filter;
        $fuel_in_types .= "s";
    }

    $fuel_in_sql .= " ORDER BY fi.date_time DESC";

    $fuel_in_stmt = $conn->prepare($fuel_in_sql);
    $fuel_in_stmt->bind_param($fuel_in_types, ...$fuel_in_params);
    $fuel_in_stmt->execute();
    $fuel_in_result = $fuel_in_stmt->get_result();
    $fuel_in_data = $fuel_in_result ? $fuel_in_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get fuel out data with filters
$show_fuel_out = (empty($transaction_type) || $transaction_type === 'FUEL OUT');
$fuel_out_data = [];

if ($show_fuel_out) {
    $fuel_out_sql = "SELECT fo.id, fo.fo_date, fo.fo_time_in, fo.fo_fuel_no, fo.fo_plate_no, 
                             fo.fo_request, fo.fo_fuel_type, fo.fo_liters, fo.fo_vehicle_type, 
                             fo.fo_receiver, fo.fo_time_out, fo.created_by, fo.created_at, fo.office_name, fo.image,
                             ft.name as fuel_type_name
                      FROM fuel_out fo
                      LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
                      WHERE DATE(fo.fo_date) BETWEEN ? AND ?";

    $fuel_out_params = [$start_date, $end_date];
    $fuel_out_types = "ss";

    if (!empty($fuel_type_filter)) {
        $fuel_out_sql .= " AND ft.name = ?";
        $fuel_out_params[] = $fuel_type_filter;
        $fuel_out_types .= "s";
    }

    $fuel_out_sql .= " ORDER BY fo.fo_date DESC, fo.fo_time_in DESC";

    $fuel_out_stmt = $conn->prepare($fuel_out_sql);
    $fuel_out_stmt->bind_param($fuel_out_types, ...$fuel_out_params);
    $fuel_out_stmt->execute();
    $fuel_out_result = $fuel_out_stmt->get_result();
    $fuel_out_data = $fuel_out_result ? $fuel_out_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Calculate summary statistics
$total_fuel_in = array_sum(array_column($fuel_in_data, 'quantity'));
$total_fuel_out = array_sum(array_column($fuel_out_data, 'fo_liters'));
$total_cost = array_sum(array_column($fuel_in_data, 'total_cost'));

// Get oil types for filter
$oilTypesResult = $conn->query("SELECT id, name FROM oil_types WHERE is_active = 1 ORDER BY name");
$oilTypes = $oilTypesResult ? $oilTypesResult->fetch_all(MYSQLI_ASSOC) : [];

// Get oil in data with filters
$show_oil_in = (empty($transaction_type) || $transaction_type === 'OIL IN');
$oil_in_data = [];

if ($show_oil_in) {
    $oil_in_sql = "SELECT oi.id, oi.date_time, oi.oil_type, oi.quantity, oi.unit_price, oi.total_cost, 
                          oi.storage_location, oi.delivery_receipt, oi.supplier_name, oi.received_by, 
                          oi.remarks, oi.created_by, oi.created_at, oi.transaction_id, oi.image,
                          ot.name as oil_type_name
                   FROM oil_in oi
                   LEFT JOIN oil_types ot ON oi.oil_type = ot.id
                   WHERE DATE(oi.date_time) BETWEEN ? AND ?";

    $oil_in_params = [$start_date, $end_date];
    $oil_in_types = "ss";

    if (!empty($fuel_type_filter)) {
        $oil_in_sql .= " AND ot.name = ?";
        $oil_in_params[] = $fuel_type_filter;
        $oil_in_types .= "s";
    }

    if (!empty($oil_type_filter)) {
        $oil_in_sql .= " AND ot.name = ?";
        $oil_in_params[] = $oil_type_filter;
        $oil_in_types .= "s";
    }

    $oil_in_sql .= " ORDER BY oi.date_time DESC";

    $oil_in_stmt = $conn->prepare($oil_in_sql);
    $oil_in_stmt->bind_param($oil_in_types, ...$oil_in_params);
    $oil_in_stmt->execute();
    $oil_in_result = $oil_in_stmt->get_result();
    $oil_in_data = $oil_in_result ? $oil_in_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get oil out data with filters
$show_oil_out = (empty($transaction_type) || $transaction_type === 'OIL OUT');
$oil_out_data = [];

if ($show_oil_out) {
    $oil_out_sql = "SELECT oo.id, oo.oil_date, oo.oil_time_in, oo.oil_oil_no, oo.oil_plate_no, 
                           oo.oil_request, oo.all_oil_type, oo.oil_liters, oo.oil_vehicle_type, 
                           oo.oil_receiver, oo.oil_time_out, oo.created_by, oo.created_at, oo.office_name, oo.image,
                           ot.name as oil_type_name
                    FROM oil_out oo
                    LEFT JOIN oil_types ot ON oo.all_oil_type = ot.id
                    WHERE DATE(oo.oil_date) BETWEEN ? AND ?";

    $oil_out_params = [$start_date, $end_date];
    $oil_out_types = "ss";

    if (!empty($fuel_type_filter)) {
        $oil_out_sql .= " AND ot.name = ?";
        $oil_out_params[] = $fuel_type_filter;
        $oil_out_types .= "s";
    }

    if (!empty($oil_type_filter)) {
        $oil_out_sql .= " AND ot.name = ?";
        $oil_out_params[] = $oil_type_filter;
        $oil_out_types .= "s";
    }

    $oil_out_sql .= " ORDER BY oo.oil_date DESC, oo.oil_time_in DESC";

    $oil_out_stmt = $conn->prepare($oil_out_sql);
    $oil_out_stmt->bind_param($oil_out_types, ...$oil_out_params);
    $oil_out_stmt->execute();
    $oil_out_result = $oil_out_stmt->get_result();
    $oil_out_data = $oil_out_result ? $oil_out_result->fetch_all(MYSQLI_ASSOC) : [];
}

// Calculate oil summary statistics
$total_oil_in = array_sum(array_column($oil_in_data, 'quantity'));
$total_oil_out = array_sum(array_column($oil_out_data, 'oil_liters'));
$total_oil_cost = array_sum(array_column($oil_in_data, 'total_cost'));

// Create unified all records array
$all_records = [];

// Add Fuel In records
foreach ($fuel_in_data as $record) {
    $all_records[] = [
        'transaction_date' => $record['date_time'],
        'transaction_type' => 'FUEL IN',
        'type_name' => $record['fuel_type_name'],
        'quantity' => $record['quantity'],
        'unit_price' => $record['unit_price'],
        'total_cost' => $record['total_cost'],
        'storage_location' => $record['storage_location'],
        'delivery_receipt' => $record['delivery_receipt'],
        'supplier_name' => $record['supplier_name'],
        'received_by' => $record['received_by'],
        'remarks' => $record['remarks'],
        'created_by' => $record['created_by'],
        'created_at' => $record['created_at'],
        'transaction_id' => $record['transaction_id']
    ];
}

// Add Fuel Out records
foreach ($fuel_out_data as $record) {
    $all_records[] = [
        'transaction_date' => $record['fo_date'] . ' ' . $record['fo_time_in'],
        'transaction_type' => 'FUEL OUT',
        'type_name' => $record['fuel_type_name'],
        'quantity' => $record['fo_liters'],
        'unit_price' => 0,
        'total_cost' => 0,
        'storage_location' => '',
        'delivery_receipt' => $record['fo_fuel_no'],
        'supplier_name' => '',
        'received_by' => $record['fo_receiver'],
        'remarks' => $record['fo_request'],
        'created_by' => $record['created_by'],
        'created_at' => $record['created_at'],
        'transaction_id' => $record['id'],
        'vehicle_info' => $record['fo_plate_no'] . ' - ' . $record['fo_vehicle_type']
    ];
}

// Add Oil In records
foreach ($oil_in_data as $record) {
    $all_records[] = [
        'transaction_date' => $record['date_time'],
        'transaction_type' => 'OIL IN',
        'type_name' => $record['oil_type_name'],
        'quantity' => $record['quantity'],
        'unit_price' => $record['unit_price'],
        'total_cost' => $record['total_cost'],
        'storage_location' => $record['storage_location'],
        'delivery_receipt' => $record['delivery_receipt'],
        'supplier_name' => $record['supplier_name'],
        'received_by' => $record['received_by'],
        'remarks' => $record['remarks'],
        'created_by' => $record['created_by'],
        'created_at' => $record['created_at'],
        'transaction_id' => $record['transaction_id']
    ];
}

// Add Oil Out records
foreach ($oil_out_data as $record) {
    $all_records[] = [
        'transaction_date' => $record['oil_date'] . ' ' . $record['oil_time_in'],
        'transaction_type' => 'OIL OUT',
        'type_name' => $record['oil_type_name'],
        'quantity' => $record['oil_liters'],
        'unit_price' => 0,
        'total_cost' => 0,
        'storage_location' => '',
        'delivery_receipt' => $record['oil_oil_no'],
        'supplier_name' => '',
        'received_by' => $record['oil_receiver'],
        'remarks' => $record['oil_request'],
        'created_by' => $record['created_by'],
        'created_at' => $record['created_at'],
        'transaction_id' => $record['id'],
        'vehicle_info' => $record['oil_plate_no'] . ' - ' . $record['oil_vehicle_type']
    ];
}

// Sort all records by date (newest first)
usort($all_records, function($a, $b) {
    return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
});

// Get fuel types for filter
$fuelTypesResult = $conn->query("SELECT id, name FROM fuel_types WHERE is_active = 1 ORDER BY name");
$fuelTypes = $fuelTypesResult ? $fuelTypesResult->fetch_all(MYSQLI_ASSOC) : [];

// Handle export requests
if (isset($_GET['export']) && $_GET['export'] === 'fuel_in') {
    exportFuelInExcel($fuel_in_data);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'fuel_out') {
    exportFuelOutExcel($fuel_out_data);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'oil_in') {
    exportOilInExcel($oil_in_data);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'oil_out') {
    exportOilOutExcel($oil_out_data);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'all') {
    exportAllExcel($all_records);
    exit;
}

function exportAllExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="all_records_report_' . date('Y-m-d') . ' (2).xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />";
    echo "<style>
        @page { 
            size: 8.5in 11in; 
            margin: 0.5in; 
            orientation: portrait; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 3px; 
            height: 20px; 
            vertical-align: middle; 
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            font-size: 9px; 
        }
        .header-row { 
            font-size: 12px; 
            font-weight: bold; 
            border: none !important;
        }
        .liters-col { 
            background-color: #ffff99; 
            text-align: right; 
        }
        .no-col { 
            text-align: center; 
            width: 30px; 
        }
        .date-col { 
            width: 80px; 
        }
        .time-col { 
            width: 60px; 
        }
        .fuel-no-col { 
            width: 80px; 
        }
        .plate-col { 
            width: 100px; 
        }
        .request-col { 
            width: 120px; 
        }
        .liters-size-col { 
            width: 80px; 
        }
        .vehicle-col { 
            width: 120px; 
        }
        .receiver-col { 
            width: 120px; 
        }
        .timeout-col { 
            width: 60px; 
        }
    </style>";
    echo "<table>";
    echo "<tr><td colspan='10' class='header-row' align='center'>FUEL INVENTORY</td></tr>";
    echo "<tr><td colspan='10' class='header-row' align='center'>PILAR, SORSOGON</td></tr>";
    echo "<tr><td colspan='10' class='header-row' align='center'>LGU</td></tr>";
    echo "<tr><td colspan='10' style='height:15px;'>&nbsp;</td></tr>";
    echo "<tr>
            <th class='no-col'>NO.</th>
            <th class='date-col'>DATE</th>
            <th class='time-col'>TIME-IN</th>
            <th class='fuel-no-col'>FUEL NO.</th>
            <th class='plate-col'>PLATE NO.</th>
            <th class='request-col'>REQUEST</th>
            <th class='liters-size-col'>NO.OF LITERS</th>
            <th class='vehicle-col'>TYPE OF VEHICLE</th>
            <th class='receiver-col'>NAME OF RECEIVER</th>
            <th class='timeout-col'>TIME-OUT</th>
          </tr>";
    
    $row_number = 1;
    foreach ($data as $row) {
        $transaction_date_time = explode(' ', $row['transaction_date']);
        $date = $transaction_date_time[0] ?? '';
        $time_in = $transaction_date_time[1] ?? '';

        $fuel_no = '';
        $plate_no = '';
        $vehicle_type = '';
        $time_out = '';
        
        if ($row['transaction_type'] === 'FUEL OUT') {
            $fuel_no = $row['delivery_receipt'] ?? '';
            $plate_no = $row['vehicle_info'] ? explode(' - ', $row['vehicle_info'])[0] : '';
            $vehicle_type = $row['vehicle_info'] ? explode(' - ', $row['vehicle_info'])[1] ?? '' : '';
        } elseif ($row['transaction_type'] === 'OIL OUT') {
            $fuel_no = $row['delivery_receipt'] ?? '';
            $plate_no = $row['vehicle_info'] ? explode(' - ', $row['vehicle_info'])[0] : '';
            $vehicle_type = $row['vehicle_info'] ? explode(' - ', $row['vehicle_info'])[1] ?? '' : '';
        }
        
        echo "<tr>
                <td class='no-col'>" . $row_number . "</td>
                <td class='date-col'>" . htmlspecialchars($date) . "</td>
                <td class='time-col'>" . htmlspecialchars($time_in) . "</td>
                <td class='fuel-no-col'>" . htmlspecialchars($fuel_no) . "</td>
                <td class='plate-col'>" . htmlspecialchars($plate_no) . "</td>
                <td class='request-col'>" . htmlspecialchars($row['remarks'] ?? '') . "</td>
                <td class='liters-col liters-size-col'>" . number_format($row['quantity'], 2) . "</td>
                <td class='vehicle-col'>" . htmlspecialchars($vehicle_type) . "</td>
                <td class='receiver-col'>" . htmlspecialchars($row['received_by']) . "</td>
                <td class='timeout-col'>" . htmlspecialchars($time_out) . "</td>
              </tr>";
        $row_number++;
    }
    echo "</table>";
}

function exportFuelInExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="fuel_in_report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>Date & Time</th>
            <th>Fuel Type</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total Cost</th>
            <th>Storage Location</th>
            <th>Delivery Receipt</th>
            <th>Supplier Name</th>
            <th>Received By</th>
            <th>Remarks</th>
            <th>Created By</th>
            <th>Transaction ID</th>
            <th>Created At</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['date_time']) . "</td>
                <td>" . htmlspecialchars($row['fuel_type_name']) . "</td>
                <td>" . number_format($row['quantity'], 2) . "</td>
                <td>" . number_format($row['unit_price'], 2) . "</td>
                <td>" . number_format($row['total_cost'], 2) . "</td>
                <td>" . htmlspecialchars($row['storage_location']) . "</td>
                <td>" . htmlspecialchars($row['delivery_receipt']) . "</td>
                <td>" . htmlspecialchars($row['supplier_name']) . "</td>
                <td>" . htmlspecialchars($row['received_by']) . "</td>
                <td>" . htmlspecialchars($row['remarks']) . "</td>
                <td>" . htmlspecialchars($row['created_by']) . "</td>
                <td>" . htmlspecialchars($row['transaction_id']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
              </tr>";
    }
    echo "</table>";
}

function exportFuelOutExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="fuel_out_report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Date</th>
            <th>Time In</th>
            <th>Fuel No</th>
            <th>Plate No</th>
            <th>Request</th>
            <th>Fuel Type</th>
            <th>Liters</th>
            <th>Vehicle Type</th>
            <th>Receiver</th>
            <th>Time Out</th>
            <th>Office Name</th>
            <th>Created By</th>
            <th>Created At</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['id']) . "</td>
                <td>" . htmlspecialchars($row['fo_date']) . "</td>
                <td>" . htmlspecialchars($row['fo_time_in']) . "</td>
                <td>" . htmlspecialchars($row['fo_fuel_no']) . "</td>
                <td>" . htmlspecialchars($row['fo_plate_no']) . "</td>
                <td>" . htmlspecialchars($row['fo_request']) . "</td>
                <td>" . htmlspecialchars($row['fuel_type_name']) . "</td>
                <td>" . number_format($row['fo_liters'], 2) . "</td>
                <td>" . htmlspecialchars($row['fo_vehicle_type']) . "</td>
                <td>" . htmlspecialchars($row['fo_receiver']) . "</td>
                <td>" . htmlspecialchars($row['fo_time_out']) . "</td>
                <td>" . htmlspecialchars($row['office_name']) . "</td>
                <td>" . htmlspecialchars($row['created_by']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
              </tr>";
    }
    echo "</table>";
}

function exportOilInExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="oil_in_report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>Date & Time</th>
            <th>Oil Type</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total Cost</th>
            <th>Storage Location</th>
            <th>Delivery Receipt</th>
            <th>Supplier Name</th>
            <th>Received By</th>
            <th>Remarks</th>
            <th>Created By</th>
            <th>Transaction ID</th>
            <th>Created At</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['date_time']) . "</td>
                <td>" . htmlspecialchars($row['oil_type_name']) . "</td>
                <td>" . number_format($row['quantity'], 2) . "</td>
                <td>" . number_format($row['unit_price'], 2) . "</td>
                <td>" . number_format($row['total_cost'], 2) . "</td>
                <td>" . htmlspecialchars($row['storage_location']) . "</td>
                <td>" . htmlspecialchars($row['delivery_receipt']) . "</td>
                <td>" . htmlspecialchars($row['supplier_name']) . "</td>
                <td>" . htmlspecialchars($row['received_by']) . "</td>
                <td>" . htmlspecialchars($row['remarks']) . "</td>
                <td>" . htmlspecialchars($row['created_by']) . "</td>
                <td>" . htmlspecialchars($row['transaction_id']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
              </tr>";
    }
    echo "</table>";
}

function exportOilOutExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="oil_out_report_' . date('Y-m-d') . '.xls"');
    
    echo "<table border='1'>";
    echo "<tr>
            <th>ID</th>
            <th>Date</th>
            <th>Time In</th>
            <th>Oil No</th>
            <th>Plate No</th>
            <th>Request</th>
            <th>Oil Type</th>
            <th>Liters</th>
            <th>Vehicle Type</th>
            <th>Receiver</th>
            <th>Time Out</th>
            <th>Office Name</th>
            <th>Created By</th>
            <th>Created At</th>
          </tr>";
    
    foreach ($data as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['id']) . "</td>
                <td>" . htmlspecialchars($row['oil_date']) . "</td>
                <td>" . htmlspecialchars($row['oil_time_in']) . "</td>
                <td>" . htmlspecialchars($row['oil_oil_no']) . "</td>
                <td>" . htmlspecialchars($row['oil_plate_no']) . "</td>
                <td>" . htmlspecialchars($row['oil_request']) . "</td>
                <td>" . htmlspecialchars($row['oil_type_name']) . "</td>
                <td>" . number_format($row['oil_liters'], 2) . "</td>
                <td>" . htmlspecialchars($row['oil_vehicle_type']) . "</td>
                <td>" . htmlspecialchars($row['oil_receiver']) . "</td>
                <td>" . htmlspecialchars($row['oil_time_out']) . "</td>
                <td>" . htmlspecialchars($row['office_name']) . "</td>
                <td>" . htmlspecialchars($row['created_by']) . "</td>
                <td>" . htmlspecialchars($row['created_at']) . "</td>
              </tr>";
    }
    echo "</table>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Reports</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 35px 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .sidebar-header h2 {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.9);
            font-weight: 400;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            z-index: 1;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
            border-left-color: #C1EAF2;
        }

        .sidebar-menu .icon {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            background: transparent;
            min-height: 100vh;
            position: relative;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 8px 0;
            margin-bottom: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 300;
        }

        .section {
            background: white;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            padding: 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25px;
            right: 25px;
            height: 2px;
            background: linear-gradient(90deg, #191BA9 0%, #5CC2F2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .section-title {
            font-size: 1.5rem;
            color: #495057;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card.fuel-in {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .stat-card.fuel-out {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }

        .stat-card.oil-in {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        .stat-card.oil-out {
            background: linear-gradient(135deg, #8e44ad 0%, #c0392b 100%);
        }

        .stat-card.oil-cost {
            background: linear-gradient(135deg, #16a085 0%, #27ae60 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filter-container {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(25, 27, 169, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .refresh-btn:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
            transform: scale(1.1);
        }

        .table-container {
            overflow-x: auto;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .chart-container {
            padding: 30px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: #666;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            
            .main-content {
                margin-left: 200px;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .sidebar {
                width: 60px;
            }
            
            .sidebar-header h2,
            .sidebar-header p {
                display: none;
            }
            
            .sidebar-menu .icon {
                margin-right: 0;
            }
            
            .sidebar-menu a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
            }
        }

        /* Navigation Bar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .navbar-toggle {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 20px;
        }

        .navbar-toggle:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .navbar-title {
            font-size: 1.3rem;
            font-weight: 500;
            flex: 1;
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Sidebar Toggle States */
        .sidebar.hidden {
            transform: translateX(-100%);
            margin-left: -250px;
        }

        .main-content.sidebar-hidden {
            margin-left: 0;
        }

        .navbar.sidebar-expanded {
            left: 250px;
        }

        .navbar.sidebar-collapsed {
            left: 0;
        }

        /* Responsive adjustments for navbar */
        @media (max-width: 768px) {
            .sidebar.hidden {
                margin-left: -200px;
            }
            
            .navbar.sidebar-expanded {
                left: 200px;
            }
        }

        @media (max-width: 600px) {
            .sidebar.hidden {
                margin-left: -60px;
            }
            
            .navbar.sidebar-expanded {
                left: 60px;
            }
            
            .navbar-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation Bar -->
        <nav class="navbar sidebar-expanded" id="navbar">
            <button class="navbar-toggle" onclick="toggleSidebar()">
                &#9776;
            </button>
            <div class="navbar-title">
                📈 Reports Management
            </div>
            <div class="navbar-actions">
                <!-- Add actions here if needed -->
            </div>
        </nav>

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Fuel & Oil Management System</h2>
                <p>Management Portal</p>
            </div>
            <nav>
                <ul class="sidebar-menu">
                    <li>
                        <a href="dashboard.php">
                            <span class="icon">📊</span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_in.php">
                            <span class="icon">📥</span>
                            <span>Fuel In</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_out.php">
                            <span class="icon">📤</span>
                            <span>Fuel Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="oil_in.php">
                            <span class="icon">🛢️</span>
                            <span>Oil In</span>
                        </a>
                    </li>
                    <li>
                        <a href="oil.php">
                            <span class="icon">🛢</span>
                            <span>Oil Out</span>
                        </a>
                    </li>
                    <li>
                        <a href="fuel_types.php">
                            <span class="icon">⚡</span>
                            <span>Types</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="active">
                            <span class="icon">📈</span>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?');">
                            <span class="icon">🚪</span>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <header style="margin-top: 60px;">
                <div class="container">
                    <h1>📈 Fuel Reports</h1>
                </div>
            </header>

            <div class="container">
                <!-- Summary Statistics -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Summary Statistics</h2>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card fuel-in">
                            <div class="stat-number"><?php echo number_format($total_fuel_in, 2); ?></div>
                            <div class="stat-label">Fuel In (Liters)</div>
                        </div>
                        <div class="stat-card fuel-out">
                            <div class="stat-number"><?php echo number_format($total_fuel_out, 2); ?></div>
                            <div class="stat-label">Fuel Out (Liters)</div>
                        </div>
                        <div class="stat-card oil-in">
                            <div class="stat-number"><?php echo number_format($total_oil_in, 2); ?></div>
                            <div class="stat-label">Oil In (Liters)</div>
                        </div>
                        <div class="stat-card oil-out">
                            <div class="stat-number"><?php echo number_format($total_oil_out, 2); ?></div>
                            <div class="stat-label">Oil Out (Liters)</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-container">
                        <form method="GET" class="filter-form">
                            <div class="filter-group">
                                <label for="period">Period</label>
                                <select name="period" id="period" onchange="this.form.submit()">
                                    <option value="day" <?php echo $period === 'day' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>This Year</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div class="filter-group" id="custom-date-range" style="<?php echo $period === 'custom' ? 'display: block;' : 'display: none;' ?>">
                                <label for="start_date">Start Date</label>
                                <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="filter-group" id="custom-end-date" style="<?php echo $period === 'custom' ? 'display: block;' : 'display: none;' ?>">
                                <label for="end_date">End Date</label>
                                <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="transaction_type">Transaction Type</label>
                                <select name="transaction_type" id="transaction_type" onchange="this.form.submit()">
                                    <option value="">All Transactions</option>
                                    <option value="FUEL IN" <?php echo $transaction_type === 'FUEL IN' ? 'selected' : ''; ?>>Fuel In</option>
                                    <option value="FUEL OUT" <?php echo $transaction_type === 'FUEL OUT' ? 'selected' : ''; ?>>Fuel Out</option>
                                    <option value="OIL IN" <?php echo $transaction_type === 'OIL IN' ? 'selected' : ''; ?>>Oil In</option>
                                    <option value="OIL OUT" <?php echo $transaction_type === 'OIL OUT' ? 'selected' : ''; ?>>Oil Out</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="fuel_type">Fuel Type</label>
                                <select name="fuel_type" id="fuel_type" onchange="this.form.submit()">
                                    <option value="">All Fuel Types</option>
                                    <?php foreach ($fuelTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['name']); ?>" 
                                                <?php echo $fuel_type_filter === $type['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="oil_type">Oil Type</label>
                                <select name="oil_type" id="oil_type" onchange="this.form.submit()">
                                    <option value="">All Oil Types</option>
                                    <?php foreach ($oilTypes as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['name']); ?>" 
                                                <?php echo $oil_type_filter === $type['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="reports.php" class="btn btn-success">Reset</a>
                        </form>
                    </div>
                </div>

                <!-- All Records -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">All Records</h2>
                        <span class="badge badge-primary"><?php echo count($all_records); ?> Total Records</span>
                        <a href="?export=all&period=<?php echo urlencode($period); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&transaction_type=<?php echo urlencode($transaction_type); ?>&fuel_type=<?php echo urlencode($fuel_type_filter); ?>&oil_type=<?php echo urlencode($oil_type_filter); ?>" class="btn btn-success" style="margin-left: auto;">Export Excel</a>
                    </div>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Transaction Date</th>
                                    <th>Type</th>
                                    <th>Fuel/Oil Type</th>
                                    <th>Quantity (Liters)</th>
                                    <th>Unit Price</th>
                                    <th>Total Cost</th>
                                    <th>Storage/Location</th>
                                    <th>Receipt/No</th>
                                    <th>Supplier/Vehicle</th>
                                    <th>Received By</th>
                                    <th>Remarks</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_records as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                    <td>
                                        <?php if ($row['transaction_type'] === 'FUEL IN'): ?>
                                            <span class="badge badge-success">Fuel In</span>
                                        <?php elseif ($row['transaction_type'] === 'FUEL OUT'): ?>
                                            <span class="badge badge-warning">Fuel Out</span>
                                        <?php elseif ($row['transaction_type'] === 'OIL IN'): ?>
                                            <span class="badge badge-info">Oil In</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Oil Out</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['type_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo number_format($row['quantity'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['unit_price'], 2); ?></td>
                                    <td>₱<?php echo number_format($row['total_cost'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['storage_location'] ?? ($row['vehicle_info'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($row['delivery_receipt'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier_name'] ?? ($row['vehicle_info'] ?? 'N/A')); ?></td>
                                    <td><?php echo htmlspecialchars($row['received_by']); ?></td>
                                    <td><?php echo htmlspecialchars($row['remarks']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($all_records)): ?>
                                <tr>
                                    <td colspan="13" style="text-align: center; padding: 30px; color: #6c757d;">
                                        No records found for the selected filters.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const navbar = document.getElementById('navbar');
            const mainContent = document.getElementById('main-content');
            
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('sidebar-hidden');
            
            if (sidebar.classList.contains('hidden')) {
                navbar.classList.remove('sidebar-expanded');
                navbar.classList.add('sidebar-collapsed');
            } else {
                navbar.classList.remove('sidebar-collapsed');
                navbar.classList.add('sidebar-expanded');
            }
            
            // Save sidebar state to localStorage
            localStorage.setItem('sidebarHidden', sidebar.classList.contains('hidden'));
        }
        
        // Restore sidebar state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarHidden = localStorage.getItem('sidebarHidden') === 'true';
            if (sidebarHidden) {
                toggleSidebar();
            }
        });

        // Handle period change
        document.getElementById('period').addEventListener('change', function() {
            const customDateRange = document.getElementById('custom-date-range');
            const customEndDate = document.getElementById('custom-end-date');
            
            if (this.value === 'custom') {
                customDateRange.style.display = 'block';
                customEndDate.style.display = 'block';
            } else {
                customDateRange.style.display = 'none';
                customEndDate.style.display = 'none';
                this.form.submit();
            }
        });

        // Auto-submit on date change for custom period
        document.getElementById('start_date').addEventListener('change', function() {
            if (document.getElementById('period').value === 'custom') {
                this.form.submit();
            }
        });

        document.getElementById('end_date').addEventListener('change', function() {
            if (document.getElementById('period').value === 'custom') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
