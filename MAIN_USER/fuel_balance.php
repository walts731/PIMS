<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_balance', 'Main user accessed fuel balance page');

$fuel_in_records = [];
$fuel_out_records = [];
$error = null;

try {
    // Check which fuel tables exist
    $fuel_tables = ['fuel_in', 'fuel_out', 'fuel_transactions'];
    $existing_tables = [];
    
    foreach ($fuel_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table && $check_table->num_rows > 0) {
            $existing_tables[] = $table;
        }
    }
    
    if (empty($existing_tables)) {
        $error = 'No fuel tables found. Please contact administrator to set up fuel management tables.';
    } else {
        // Get filter parameters - expanded default date range
        $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01', strtotime('-3 months'));
        $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
        $office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;
        
        // Get fuel IN records
        if (in_array('fuel_in', $existing_tables)) {
            // Use correct column names for fuel_in table
            $fuel_in_sql = "SELECT 
                              id,
                              date_time as fuel_date,
                              quantity as fuel_quantity,
                              fuel_type,
                              supplier_name as vehicle_name,
                              '' as plate_number,
                              0 as odometer_reading,
                              remarks as purpose,
                              created_at,
                              created_by
                           FROM fuel_in 
                           WHERE DATE(date_time) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_in_sql .= " AND office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_in_sql .= " ORDER BY date_time DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param($types, ...$params);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            }
        }
        
        // Get fuel OUT records - use exact same logic as fuel_out.php
        if (in_array('fuel_transactions', $existing_tables)) {
            // Use same query as fuel_out.php - gets from fuel_transactions table
            $fuel_out_sql = "SELECT 
                                 ft.id,
                                 DATE(ft.transaction_date) as fuel_date,
                                 ft.quantity as fuel_quantity,
                                 ft.fuel_type,
                                 ft.vehicle_equipment as vehicle_name,
                                 '' as plate_number,
                                 ft.odometer_reading,
                                 ft.purpose,
                                 ft.created_at,
                                 ft.user_id as created_by
                              FROM fuel_transactions ft 
                              WHERE ft.transaction_type = 'OUT'";
            
            // Add date filter
            $fuel_out_sql .= " AND DATE(ft.transaction_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_out_sql .= " AND office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_out_sql .= " ORDER BY ft.created_at DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param($types, ...$params);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            }
        }
        
    }
} catch (Exception $e) {
    $error = 'An error occurred while fetching fuel balance data: ' . $e->getMessage();
}

        // Calculate balance
        $total_fuel_in = array_sum(array_column($fuel_in_records, 'fuel_quantity'));
        $total_fuel_out = array_sum(array_column($fuel_out_records, 'fuel_quantity'));
        $net_balance = $total_fuel_in - $total_fuel_out;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Balance - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Global smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            height: 100vh;
        }
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh;
            margin: 0;
            padding: 2rem;
            border-radius: 0;
            animation: slideUp 0.8s ease-out;
            overflow-y: auto;
            height: 100vh;
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .header-section {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            animation: slideDown 0.8s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .filter-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.6s ease-out 0.4s both;
        }
        .balance-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.8s both;
            text-align: center;
        }
        .balance-positive {
            border-left: 5px solid #28a745;
        }
        .balance-negative {
            border-left: 5px solid #dc3545;
        }
        .balance-neutral {
            border-left: 5px solid #17a2b8;
        }
        .fuel-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
            animation: bounceIn 0.8s ease-out 0.5s both;
        }
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        .balance-icon {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #17a2b8, #138496);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(23, 162, 184, 0.3);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
        }
        .balance-amount {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
        }
        .balance-positive .balance-amount {
            color: #28a745;
        }
        .balance-negative .balance-amount {
            color: #dc3545;
        }
        .balance-neutral .balance-amount {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <?php include 'includes/topbar.php'; ?>
    
    <div class="main-container">
        <!-- Header Section -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-calculator me-3"></i>
                        Fuel Balance Analysis
                    </h1>
                    <p class="mb-0 opacity-75">Net fuel balance and consumption analysis</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="fuel_management.php" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        
        <!-- Balance Summary Cards with Enhanced Real Data Display -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Fuel IN</h6>
                            <h3 class="mb-0 text-success">
                                <?php echo number_format($total_fuel_in, 2); ?>
                                <small>Liters</small>
                            </h3>
                            <small class="text-muted"><?php echo count($fuel_in_records); ?> Transactions</small>
                            <?php if (!empty($fuel_in_records)): ?>
                                <br><small class="text-info">
                                    Latest: <?php echo date('M d', strtotime($fuel_in_records[0]['fuel_date'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Total Fuel OUT</h6>
                            <h3 class="mb-0 text-danger">
                                <?php echo number_format($total_fuel_out, 2); ?>
                                <small>Liters</small>
                            </h3>
                            <small class="text-muted"><?php echo count($fuel_out_records); ?> Transactions</small>
                            <?php if (!empty($fuel_out_records)): ?>
                                <br><small class="text-info">
                                    Latest: <?php echo date('M d', strtotime($fuel_out_records[0]['fuel_date'])); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="stats-card h-100">
                    <div class="d-flex align-items-center">
                        <div class="fuel-icon" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-2">Net Balance</h6>
                            <h3 class="mb-0 text-<?php echo $net_balance > 0 ? 'success' : ($net_balance < 0 ? 'danger' : 'info'); ?>">
                                <?php echo number_format($net_balance, 2); ?>
                                <small>Liters</small>
                            </h3>
                            <small class="text-muted">
                                <?php 
                                if ($net_balance > 0) {
                                    echo 'Surplus';
                                } elseif ($net_balance < 0) {
                                    echo 'Deficit';
                                } else {
                                    echo 'Balanced';
                                }
                                ?>
                            </small>
                            <br><small class="text-warning">
                                Efficiency: <?php echo $total_fuel_in > 0 ? number_format(($total_fuel_out / $total_fuel_in) * 100, 1) : 0; ?>%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real Data Summary Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-table text-primary me-2"></i>
                        Real Data Summary - Period: <?php echo date('M d, Y', strtotime($date_from)); ?> to <?php echo date('M d, Y', strtotime($date_to)); ?>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Metric</th>
                                    <th>Fuel IN</th>
                                    <th>Fuel OUT</th>
                                    <th>Net</th>
                                    <th>Efficiency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Total Liters</strong></td>
                                    <td class="text-success"><?php echo number_format($total_fuel_in, 2); ?> L</td>
                                    <td class="text-danger"><?php echo number_format($total_fuel_out, 2); ?> L</td>
                                    <td class="text-<?php echo $net_balance >= 0 ? 'success' : 'danger'; ?>"><?php echo number_format($net_balance, 2); ?> L</td>
                                    <td><?php echo $total_fuel_in > 0 ? number_format(($total_fuel_out / $total_fuel_in) * 100, 1) : 0; ?>%</td>
                                </tr>
                                <tr>
                                    <td><strong>Transactions</strong></td>
                                    <td><?php echo count($fuel_in_records); ?></td>
                                    <td><?php echo count($fuel_out_records); ?></td>
                                    <td><?php echo count($fuel_in_records) + count($fuel_out_records); ?></td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td><strong>Average per Transaction</strong></td>
                                    <td><?php echo count($fuel_in_records) > 0 ? number_format($total_fuel_in / count($fuel_in_records), 2) : 0; ?> L</td>
                                    <td><?php echo count($fuel_out_records) > 0 ? number_format($total_fuel_out / count($fuel_out_records), 2) : 0; ?> L</td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Balance Card -->
        <div class="balance-card <?php echo $net_balance > 0 ? 'balance-positive' : ($net_balance < 0 ? 'balance-negative' : 'balance-neutral'); ?>">
            <div class="fuel-icon balance-icon">
                <i class="bi bi-calculator"></i>
            </div>
            <h3 class="mb-3">Net Fuel Balance</h3>
            <div class="balance-amount">
                <?php echo number_format($net_balance, 2); ?>
            </div>
            <p class="text-muted mb-3">Liters</p>
            
            <?php if ($net_balance > 0): ?>
                <div class="alert alert-success d-inline-block">
                    <i class="bi bi-arrow-up-circle me-2"></i>
                    <strong>Positive Balance</strong><br>
                    <small>More fuel received than consumed</small>
                </div>
            <?php elseif ($net_balance < 0): ?>
                <div class="alert alert-danger d-inline-block">
                    <i class="bi bi-arrow-down-circle me-2"></i>
                    <strong>Negative Balance</strong><br>
                    <small>More fuel consumed than received</small>
                </div>
            <?php else: ?>
                <div class="alert alert-info d-inline-block">
                    <i class="bi bi-dash-circle me-2"></i>
                    <strong>Balanced</strong><br>
                    <small>Fuel received equals fuel consumed</small>
                </div>
            <?php endif; ?>
        </div>

        <!-- Analysis Details -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-graph-up text-success me-2"></i>
                        Fuel IN Analysis
                    </h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">Daily Average</small>
                            <h5 class="text-success">
                                <?php 
                                $days = max(1, (strtotime($date_to) - strtotime($date_from)) / 86400 + 1);
                                echo number_format($total_fuel_in / $days, 2); 
                                ?>
                                <small>L/day</small>
                            </h5>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Per Transaction</small>
                            <h5 class="text-success">
                                <?php 
                                $avg_in = count($fuel_in_records) > 0 ? $total_fuel_in / count($fuel_in_records) : 0;
                                echo number_format($avg_in, 2); 
                                ?>
                                <small>L</small>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-graph-down text-danger me-2"></i>
                        Fuel OUT Analysis
                    </h5>
                    <div class="row text-center">
                        <div class="col-6">
                            <small class="text-muted">Daily Average</small>
                            <h5 class="text-danger">
                                <?php 
                                echo number_format($total_fuel_out / $days, 2); 
                                ?>
                                <small>L/day</small>
                            </h5>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Per Transaction</small>
                            <h5 class="text-danger">
                                <?php 
                                $avg_out = count($fuel_out_records) > 0 ? $total_fuel_out / count($fuel_out_records) : 0;
                                echo number_format($avg_out, 2); 
                                ?>
                                <small>L</small>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Tables -->
        <div class="row mt-4">
            <!-- Fuel IN Transactions Table -->
            <div class="col-12 mb-4">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-arrow-down-circle text-success me-2"></i>
                        Fuel IN Transactions
                        <span class="badge bg-success ms-2"><?php echo count($fuel_in_records); ?> Records</span>
                    </h5>
                    <?php if (!empty($fuel_in_records)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                        <th><i class="bi bi-truck me-1"></i>Vehicle</th>
                                        <th><i class="bi bi-upc me-1"></i>Plate</th>
                                        <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                                        <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                                        <th><i class="bi bi-chat-text me-1"></i>Purpose</th>
                                        <th><i class="bi bi-speedometer2 me-1"></i>Odometer</th>
                                        <th><i class="bi bi-person me-1"></i>Added By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fuel_in_records as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-success">
                                                    <?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong class="text-success">
                                                    <?php echo number_format($record['fuel_quantity'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white">
                                                    <?php echo htmlspecialchars($record['fuel_type'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['purpose'] ?? ''); ?></td>
                                            <td><?php echo number_format($record['odometer_reading'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($record['created_by'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                            <h6 class="text-muted mt-3">No Fuel IN Records Found</h6>
                            <p class="text-muted">No fuel IN transactions found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fuel OUT Transactions Table -->
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-arrow-up-circle text-danger me-2"></i>
                        Fuel OUT Transactions
                        <span class="badge bg-danger ms-2"><?php echo count($fuel_out_records); ?> Records</span>
                    </h5>
                    <?php if (!empty($fuel_out_records)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                        <th><i class="bi bi-truck me-1"></i>Vehicle</th>
                                        <th><i class="bi bi-upc me-1"></i>Plate</th>
                                        <th><i class="bi bi-droplet me-1"></i>Quantity (L)</th>
                                        <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                                        <th><i class="bi bi-chat-text me-1"></i>Purpose</th>
                                        <th><i class="bi bi-speedometer2 me-1"></i>Odometer</th>
                                        <th><i class="bi bi-person me-1"></i>Added By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fuel_out_records as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-danger">
                                                    <?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong class="text-danger">
                                                    <?php echo number_format($record['fuel_quantity'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white">
                                                    <?php echo htmlspecialchars($record['fuel_type'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['purpose'] ?? ''); ?></td>
                                            <td><?php echo number_format($record['odometer_reading'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($record['created_by'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-arrow-up-circle text-muted" style="font-size: 3rem;"></i>
                            <h6 class="text-muted mt-3">No Fuel OUT Records Found</h6>
                            <p class="text-muted">No fuel OUT transactions found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
