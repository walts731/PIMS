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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_management', 'Main user accessed fuel management page');

$fuel_in_records = [];
$fuel_out_records = [];
$offices = [];
$error = null;

try {
    $fuel_tables = ['fuel_in', 'fuel_out', 'fuel_types'];
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
        $period_filter = isset($_GET['period']) ? trim((string)$_GET['period']) : 'month';
        $office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;

        // Calculate date range based on period filter
        switch ($period_filter) {
            case 'today':
                $date_from = date('Y-m-d');
                $date_to = date('Y-m-d');
                break;
            case 'week':
                $date_from = date('Y-m-d', strtotime('-7 days'));
                $date_to = date('Y-m-d');
                break;
            case 'month':
                $date_from = date('Y-m-d', strtotime('-30 days'));
                $date_to = date('Y-m-d');
                break;
            case 'year':
                $date_from = date('Y-01-01');
                $date_to = date('Y-m-d');
                break;
            default:
                $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01', strtotime('-3 months'));
                $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
                break;
        }

        $office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;
        
        // Fuel type name mapping (fuel_in uses integer IDs)
        $fuel_type_names = [
            1 => 'Diesel',
            2 => 'Gasoline',
            3 => 'Premium'
        ];
        
        // Get fuel IN records from fuel_in table
        if (in_array('fuel_in', $existing_tables)) {
            $fuel_in_sql = "SELECT 
                              fi.id,
                              DATE(fi.date_time) as fuel_date,
                              fi.quantity as fuel_quantity,
                              fi.fuel_type,
                              fi.supplier_name as vehicle_name,
                              fi.delivery_receipt as plate_number,
                              fi.received_by,
                              fi.storage_location as purpose,
                              fi.unit_price,
                              fi.created_at
                           FROM fuel_in fi
                           WHERE DATE(fi.date_time) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_in_sql .= " AND fi.office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_in_sql .= " ORDER BY fi.date_time DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param($types, ...$params);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    // Map fuel_type integer to name
                    $row['fuel_type_name'] = $fuel_type_names[$row['fuel_type']] ?? 'Unknown';
                    $row['office_name'] = 'Main Office'; // Default office since office_id may not exist
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            }
        }
        
        // Get fuel OUT records from fuel_out table
        if (in_array('fuel_out', $existing_tables) && in_array('fuel_types', $existing_tables)) {
            $fuel_out_sql = "SELECT 
                               fo.id,
                               DATE(fo.fo_date) as fuel_date,
                               fo.fo_time_in,
                               fo.fo_liters as fuel_quantity,
                               ft.name as fuel_type_name,
                               fo.fo_receiver as vehicle_name,
                               fo.fo_plate_no as plate_number,
                               fo.fo_request as purpose,
                               fo.created_at
                            FROM fuel_out fo
                            LEFT JOIN fuel_types ft ON fo.fo_fuel_type = ft.id
                            WHERE DATE(fo.fo_date) BETWEEN ? AND ?";
            
            $params = [$date_from, $date_to];
            $types = "ss";
            
            if ($office_filter > 0) {
                $fuel_out_sql .= " AND fo.office_id = ?";
                $params[] = $office_filter;
                $types .= "i";
            }
            
            $fuel_out_sql .= " ORDER BY fo.created_at DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param($types, ...$params);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $row['office_name'] = 'Main Office'; // Default office since office_id may not exist
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            }
        }
        
    }
} catch (Exception $e) {
    $error = 'An error occurred while fetching fuel management data: ' . $e->getMessage();
}

// Calculate balance
$total_fuel_in = array_sum(array_column($fuel_in_records, 'fuel_quantity'));
$total_fuel_out = array_sum(array_column($fuel_out_records, 'fuel_quantity'));
$net_balance = $total_fuel_in - $total_fuel_out;

// Calculate balance by fuel type
$balance_by_type = [];

// Group fuel IN by type
foreach ($fuel_in_records as $record) {
    $fuel_type = $record['fuel_type_name'] ?? 'Unknown';
    if (!isset($balance_by_type[$fuel_type])) {
        $balance_by_type[$fuel_type] = ['in' => 0, 'out' => 0, 'net' => 0];
    }
    $balance_by_type[$fuel_type]['in'] += $record['fuel_quantity'];
}

// Group fuel OUT by type
foreach ($fuel_out_records as $record) {
    $fuel_type = $record['fuel_type_name'] ?? 'Unknown';
    if (!isset($balance_by_type[$fuel_type])) {
        $balance_by_type[$fuel_type] = ['in' => 0, 'out' => 0, 'net' => 0];
    }
    $balance_by_type[$fuel_type]['out'] += $record['fuel_quantity'];
}

// Calculate net balance for each type
foreach ($balance_by_type as $fuel_type => $data) {
    $balance_by_type[$fuel_type]['net'] = $data['in'] - $data['out'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - PIMS</title>
    
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
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
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
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.8s both;
        }
        .fuel-table {
            border-radius: 10px;
            overflow: hidden;
        }
        .fuel-table thead {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            color: white;
        }
        .fuel-table tbody tr {
            animation: slideUp 0.4s ease-out;
            transition: all 0.3s ease;
        }
        .fuel-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .fuel-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
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
        .btn-gradient {
            background: linear-gradient(135deg, #6f42c1, #0d6efd);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
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
        .balance-amount {
            font-size: 2.5rem;
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
                        <i class="bi bi-fuel-pump me-3"></i>
                        Fuel Management Dashboard
                    </h1>
                    <p class="mb-0 opacity-75">Complete fuel management with balance analysis</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light btn-lg">
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

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row align-items-end">
                <div class="col-md-12">
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                        <div>
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-range me-1"></i>Time Period
                            </label>
                            <div class="btn-group" role="group">
                                <a href="?period=today" class="btn btn-<?php echo $period_filter === 'today' ? 'info' : 'outline-info'; ?>">
                                    <i class="bi bi-calendar-day me-1"></i>Today
                                </a>
                                <a href="?period=week" class="btn btn-<?php echo $period_filter === 'week' ? 'info' : 'outline-info'; ?>">
                                    <i class="bi bi-calendar-week me-1"></i>Week
                                </a>
                                <a href="?period=month" class="btn btn-<?php echo $period_filter === 'month' ? 'info' : 'outline-info'; ?>">
                                    <i class="bi bi-calendar-month me-1"></i>Month
                                </a>
                                <a href="?period=year" class="btn btn-<?php echo $period_filter === 'year' ? 'info' : 'outline-info'; ?>">
                                    <i class="bi bi-calendar-year me-1"></i>Year
                                </a>
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1"></i>Office
                            </label>
                            <div class="form-control">
                                All Office
                            </div>
                        </div>
                        <div class="ms-auto">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-calendar-range me-1"></i>Custom Date Range
                            </label>
                            <div class="d-flex gap-1">
                                <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" 
                                       value="<?php echo htmlspecialchars($date_from); ?>">
                                <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" 
                                       value="<?php echo htmlspecialchars($date_to); ?>">
                                <button type="submit" class="btn btn-gradient btn-sm">
                                    <i class="bi bi-funnel me-1"></i>
                                    Apply
                                </button>
                                <a href="fuel_management.php?period=month" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="text-muted">
                        <small>
                            <i class="bi bi-info-circle me-1"></i>
                            Showing data from <strong><?php echo date('M d, Y', strtotime($date_from)); ?></strong> 
                            to <strong><?php echo date('M d, Y', strtotime($date_to)); ?></strong>
                            <?php if ($period_filter !== 'custom' && isset($_GET['period'])): ?>
                                <span class="badge bg-info ms-2"><?php echo ucfirst($period_filter); ?></span>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance Summary Cards -->
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance by Fuel Type -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-pie-chart text-primary me-2"></i>
                        Balance by Fuel Type
                    </h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Fuel Type</th>
                                    <th class="text-end">Fuel IN</th>
                                    <th class="text-end">Fuel OUT</th>
                                    <th class="text-end">Net Balance</th>
                                    <th class="text-end">Efficiency</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($balance_by_type as $fuel_type => $data): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($fuel_type); ?></strong></td>
                                        <td class="text-success text-end"><?php echo number_format($data['in'], 2); ?> L</td>
                                        <td class="text-danger text-end"><?php echo number_format($data['out'], 2); ?> L</td>
                                        <td class="text-<?php echo $data['net'] >= 0 ? 'success' : 'danger'; ?> text-end">
                                            <strong><?php echo number_format($data['net'], 2); ?> L</strong>
                                        </td>
                                        <td class="text-end"><?php echo $data['in'] > 0 ? number_format(($data['out'] / $data['in']) * 100, 1) : 0; ?>%</td>
                                        <td class="text-center">
                                            <?php if ($data['net'] > 0): ?>
                                                <span class="badge bg-success">Surplus</span>
                                            <?php elseif ($data['net'] < 0): ?>
                                                <span class="badge bg-danger">Deficit</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Balanced</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-lightning text-primary me-2"></i>
                        Quick Actions
                    </h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="fuel_in.php" class="btn btn-success w-100">
                                <i class="bi bi-arrow-down-circle me-2"></i>
                                Fuel IN Records
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="fuel_out.php" class="btn btn-danger w-100">
                                <i class="bi bi-arrow-up-circle me-2"></i>
                                Fuel OUT Records
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="fuel_transactions.php" class="btn btn-info w-100">
                                <i class="bi bi-list-ul me-2"></i>
                                All Transactions
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="fuel_balance.php" class="btn btn-primary w-100">
                                <i class="bi bi-calculator me-2"></i>
                                Balance Analysis
                            </a>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3 mb-2">
                            <a href="office_fuel_out_analysis.php" class="btn btn-warning w-100">
                                <i class="bi bi-building me-2"></i>
                                Office Fuel Out Analysis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions Preview -->
        <div class="row">
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="bi bi-clock-history text-primary me-2"></i>
                        Recent Transactions
                        <span class="badge bg-info ms-2">
                            <?php echo count($fuel_in_records) + count($fuel_out_records); ?> Total
                        </span>
                    </h5>
                    
                    <?php if (!empty($fuel_in_records) || !empty($fuel_out_records)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Vehicle/Supplier</th>
                                        <th>Quantity (L)</th>
                                        <th>Fuel Type</th>
                                        <th>Office</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Combine and sort all transactions
                                    $all_transactions = [];
                                    
                                    // Add fuel IN records
                                    foreach ($fuel_in_records as $record) {
                                        $all_transactions[] = array_merge($record, [
                                            'transaction_type' => 'IN', 
                                            'type_color' => 'success',
                                            'record_time' => $record['date_time'] ?? $record['created_at']
                                        ]);
                                    }
                                    
                                    // Add fuel OUT records
                                    foreach ($fuel_out_records as $record) {
                                        $all_transactions[] = array_merge($record, [
                                            'transaction_type' => 'OUT', 
                                            'type_color' => 'danger',
                                            'record_time' => $record['fo_time_in'] ?? $record['created_at']
                                        ]);
                                    }
                                    
                                    // Sort by date and time
                                    usort($all_transactions, function($a, $b) {
                                        $dateTimeA = $a['fuel_date'] . ' ' . ($a['record_time'] ?? '00:00:00');
                                        $dateTimeB = $b['fuel_date'] . ' ' . ($b['record_time'] ?? '00:00:00');
                                        return strtotime($dateTimeB) - strtotime($dateTimeA);
                                    });
                                    
                                    // Show only last 10 transactions
                                    $recent_transactions = array_slice($all_transactions, 0, 10);
                                    
                                    foreach ($recent_transactions as $record): 
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('F j, Y', strtotime($record['fuel_date'])); ?></strong>
                                                <?php if (!empty($record['record_time'])): ?>
                                                    <br><small class="text-muted"><?php echo date('g:i A', strtotime($record['record_time'])); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $record['type_color']; ?> text-white">
                                                    <?php echo $record['transaction_type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-<?php echo $record['type_color']; ?>">
                                                    <?php echo htmlspecialchars($record['vehicle_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-<?php echo $record['type_color']; ?>">
                                                    <?php echo number_format($record['fuel_quantity'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white">
                                                    <?php echo htmlspecialchars($record['fuel_type_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($record['office_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="fuel_transactions.php" class="btn btn-gradient">
                                <i class="bi bi-list-ul me-2"></i>
                                View All Transactions
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-clock-history text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No Fuel Transactions Found</h5>
                            <p class="text-muted">No fuel transactions found for the selected period and filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
