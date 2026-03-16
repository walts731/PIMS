<?php
session_start();
require_once '../../config.php';
require_once '../../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../index.php');
    exit();
}

// Check if user has correct role
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
    header('Location: ../../index.php');
    exit();
}

$export = $_GET['export'] ?? '';
$type = $_GET['type'] ?? '';
$report_type = $_GET['report_type'] ?? 'summary';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$fuel_type_filter = $_GET['fuel_type'] ?? '';

if ($export !== '1') {
    header('Location: ../dashboard.php?page=reports');
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="fuel_report_' . $type . '_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Open output stream
$output = fopen('php://output', 'w');

try {
    switch ($type) {
        case 'inventory':
            // CSV headers
            fputcsv($output, [
                'Tank Number',
                'Fuel Type',
                'Capacity (L)',
                'Current Level (L)',
                'Fill Percentage',
                'Location',
                'Status',
                'Last Updated'
            ]);
            
            // Get inventory data
            $inventory_sql = "SELECT 
                                tank_number,
                                fuel_type,
                                capacity,
                                current_level,
                                location,
                                status,
                                last_updated
                             FROM fuel_inventory 
                             ORDER BY fuel_type, tank_number";
            
            $result = $conn->query($inventory_sql);
            while ($row = $result->fetch_assoc()) {
                $fill_percentage = ($row['current_level'] / $row['capacity']) * 100;
                fputcsv($output, [
                    $row['tank_number'],
                    ucfirst($row['fuel_type']),
                    $row['capacity'],
                    $row['current_level'],
                    number_format($fill_percentage, 2) . '%',
                    $row['location'],
                    ucfirst($row['status']),
                    date('Y-m-d H:i:s', strtotime($row['last_updated']))
                ]);
            }
            break;
            
        case 'fuel_in':
            // CSV headers
            fputcsv($output, [
                'Transaction ID',
                'Date',
                'Fuel Type',
                'Quantity (L)',
                'Supplier',
                'Vehicle/Equipment',
                'Purpose',
                'Tank Number',
                'Odometer',
                'User ID',
                'Created At'
            ]);
            
            // Get fuel IN data
            $fuel_in_sql = "SELECT 
                              id,
                              transaction_date,
                              fuel_type,
                              quantity,
                              supplier,
                              vehicle_equipment,
                              purpose,
                              tank_number,
                              odometer_reading,
                              user_id,
                              created_at
                           FROM fuel_transactions 
                           WHERE transaction_type = 'IN' 
                           ORDER BY transaction_date DESC";
            
            $result = $conn->query($fuel_in_sql);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
                    ucfirst($row['fuel_type']),
                    $row['quantity'],
                    $row['supplier'] ?? '',
                    $row['vehicle_equipment'] ?? '',
                    $row['purpose'],
                    $row['tank_number'] ?? '',
                    $row['odometer_reading'] ?? '',
                    $row['user_id'],
                    date('Y-m-d H:i:s', strtotime($row['created_at']))
                ]);
            }
            break;
            
        case 'fuel_out':
            // CSV headers
            fputcsv($output, [
                'Transaction ID',
                'Date',
                'Fuel Type',
                'Quantity (L)',
                'Vehicle/Equipment',
                'Driver Name',
                'Department',
                'Purpose',
                'Tank Number',
                'Odometer',
                'User ID',
                'Created At'
            ]);
            
            // Get fuel OUT data
            $fuel_out_sql = "SELECT 
                               id,
                               transaction_date,
                               fuel_type,
                               quantity,
                               vehicle_equipment,
                               driver_name,
                               department,
                               purpose,
                               tank_number,
                               odometer_reading,
                               user_id,
                               created_at
                            FROM fuel_transactions 
                            WHERE transaction_type = 'OUT' 
                            ORDER BY transaction_date DESC";
            
            $result = $conn->query($fuel_out_sql);
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
                    ucfirst($row['fuel_type']),
                    $row['quantity'],
                    $row['vehicle_equipment'],
                    $row['driver_name'] ?? '',
                    $row['department'] ?? '',
                    $row['purpose'],
                    $row['tank_number'] ?? '',
                    $row['odometer_reading'] ?? '',
                    $row['user_id'],
                    date('Y-m-d H:i:s', strtotime($row['created_at']))
                ]);
            }
            break;
            
        default:
            // Summary report based on report_type
            if ($report_type === 'summary') {
                // CSV headers
                fputcsv($output, [
                    'Report Type',
                    'Fuel Type',
                    'Transaction Type',
                    'Total Quantity (L)',
                    'Transaction Count',
                    'Average Quantity (L)',
                    'Min Quantity (L)',
                    'Max Quantity (L)',
                    'Period Start',
                    'Period End'
                ]);
                
                // Get summary data
                $summary_sql = "SELECT 
                                  transaction_type,
                                  fuel_type,
                                  SUM(quantity) as total_quantity,
                                  COUNT(*) as transaction_count,
                                  AVG(quantity) as avg_quantity,
                                  MIN(quantity) as min_quantity,
                                  MAX(quantity) as max_quantity
                               FROM fuel_transactions 
                               WHERE DATE(transaction_date) BETWEEN ? AND ?";
                
                $params = [$date_from, $date_to];
                $types = 'ss';
                
                if (!empty($fuel_type_filter)) {
                    $summary_sql .= " AND fuel_type = ?";
                    $params[] = $fuel_type_filter;
                    $types .= 's';
                }
                
                $summary_sql .= " GROUP BY transaction_type, fuel_type ORDER BY fuel_type, transaction_type";
                
                $stmt = $conn->prepare($summary_sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        'Summary',
                        ucfirst($row['fuel_type']),
                        $row['transaction_type'],
                        $row['total_quantity'],
                        $row['transaction_count'],
                        number_format($row['avg_quantity'], 2),
                        $row['min_quantity'],
                        $row['max_quantity'],
                        $date_from,
                        $date_to
                    ]);
                }
                $stmt->close();
            } elseif ($report_type === 'transactions') {
                // CSV headers
                fputcsv($output, [
                    'Transaction ID',
                    'Date',
                    'Type',
                    'Fuel Type',
                    'Quantity (L)',
                    'Supplier',
                    'Vehicle/Equipment',
                    'Driver Name',
                    'Department',
                    'Purpose',
                    'Tank Number',
                    'Odometer',
                    'User ID',
                    'Created At'
                ]);
                
                // Get detailed transactions
                $transactions_sql = "SELECT 
                                       id,
                                       transaction_date,
                                       transaction_type,
                                       fuel_type,
                                       quantity,
                                       supplier,
                                       vehicle_equipment,
                                       driver_name,
                                       department,
                                       purpose,
                                       tank_number,
                                       odometer_reading,
                                       user_id,
                                       created_at
                                    FROM fuel_transactions 
                                    WHERE DATE(transaction_date) BETWEEN ? AND ?";
                
                $params = [$date_from, $date_to];
                $types = 'ss';
                
                if (!empty($fuel_type_filter)) {
                    $transactions_sql .= " AND fuel_type = ?";
                    $params[] = $fuel_type_filter;
                    $types .= 's';
                }
                
                $transactions_sql .= " ORDER BY transaction_date DESC";
                
                $stmt = $conn->prepare($transactions_sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    fputcsv($output, [
                        $row['id'],
                        date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
                        $row['transaction_type'],
                        ucfirst($row['fuel_type']),
                        $row['quantity'],
                        $row['supplier'] ?? '',
                        $row['vehicle_equipment'] ?? '',
                        $row['driver_name'] ?? '',
                        $row['department'] ?? '',
                        $row['purpose'],
                        $row['tank_number'] ?? '',
                        $row['odometer_reading'] ?? '',
                        $row['user_id'],
                        date('Y-m-d H:i:s', strtotime($row['created_at']))
                    ]);
                }
                $stmt->close();
            }
            break;
    }
    
    // Log export action
    logSystemAction($_SESSION['user_id'], 'export', 'fuel_report', "Exported {$type} fuel report");
    
} catch (Exception $e) {
    // Write error to CSV
    fputcsv($output, ['Error', $e->getMessage()]);
    error_log('Export Error: ' . $e->getMessage());
}

fclose($output);
exit();
?>
