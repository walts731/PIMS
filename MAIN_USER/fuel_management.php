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

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_management', 'Main user accessed fuel management front-end');

$fuel_in_records = [];
$fuel_out_records = [];
$fuel_inventory_records = [];
$offices = [];
$error = null;

try {
    // Get offices for dropdown
    $office_sql = "SELECT id, office_name FROM offices ORDER BY office_name";
    $office_result = $conn->query($office_sql);
    if ($office_result) {
        while ($row = $office_result->fetch_assoc()) {
            $offices[] = $row;
        }
    }
    
    // Check which fuel tables exist
    $fuel_tables = ['fuel_in', 'fuel_out', 'fuel_transactions', 'fuel_inventory'];
    $existing_tables = [];
    
    foreach ($fuel_tables as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table && $check_table->num_rows > 0) {
            $existing_tables[] = $table;
        }
    }
    
    // Get fuel inventory data
    if (in_array('fuel_inventory', $existing_tables)) {
        $inventory_sql = "SELECT 
                            id,
                            tank_number,
                            fuel_type,
                            capacity,
                            current_level,
                            location,
                            status,
                            last_updated,
                            created_at,
                            updated_by
                         FROM fuel_inventory 
                         ORDER BY fuel_type, tank_number";
        $inventory_result = $conn->query($inventory_sql);
        if ($inventory_result) {
            while ($row = $inventory_result->fetch_assoc()) {
                $fuel_inventory_records[] = $row;
            }
        }
    }
    
    // Get total fuel IN from entire database (like admin)
    $total_fuel_in_all = 0;
    $total_fuel_out_all = 0;
    
    if (in_array('fuel_in', $existing_tables)) {
        $total_in_query = "SELECT SUM(quantity) as total FROM fuel_in";
        $total_in_result = $conn->query($total_in_query);
        if ($total_in_result && $row = $total_in_result->fetch_assoc()) {
            $total_fuel_in_all = $row['total'] ?? 0;
        }
    }
    
    if (empty($total_fuel_in_all) && in_array('fuel_transactions', $existing_tables)) {
        $total_in_trans_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'IN'";
        $total_in_trans_result = $conn->query($total_in_trans_query);
        if ($total_in_trans_result && $row = $total_in_trans_result->fetch_assoc()) {
            $total_fuel_in_all = $row['total'] ?? 0;
        }
    }
    
    if (in_array('fuel_out', $existing_tables)) {
        $total_out_query = "SELECT SUM(fo_liters) as total FROM fuel_out";
        $total_out_result = $conn->query($total_out_query);
        if ($total_out_result && $row = $total_out_result->fetch_assoc()) {
            $total_fuel_out_all = $row['total'] ?? 0;
        }
    }
    
    if (empty($total_fuel_out_all) && in_array('fuel_transactions', $existing_tables)) {
        $total_out_trans_query = "SELECT SUM(quantity) as total FROM fuel_transactions WHERE transaction_type = 'OUT'";
        $total_out_trans_result = $conn->query($total_out_trans_query);
        if ($total_out_trans_result && $row = $total_out_trans_result->fetch_assoc()) {
            $total_fuel_out_all = $row['total'] ?? 0;
        }
    }
    
    if (empty($existing_tables)) {
        $error = 'No fuel tables found. Please contact administrator to set up fuel management tables.';
    } else {
        // Get filter parameters
        $date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : date('Y-m-01');
        $date_to = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : date('Y-m-d');
        $office_filter = isset($_GET['office']) ? (int)$_GET['office'] : 0;
        
        // Get fuel IN records
        if (in_array('fuel_in', $existing_tables)) {
            $fuel_in_sql = "SELECT 
                              id,
                              date_time as fuel_date,
                              quantity as fuel_quantity,
                              fuel_type,
                              vehicle_name,
                              plate_number,
                              office_id,
                              supplier,
                              created_at,
                              created_by
                           FROM fuel_in 
                           WHERE DATE(date_time) BETWEEN ? AND ?
                           ORDER BY date_time DESC";
            
            $fuel_in_stmt = $conn->prepare($fuel_in_sql);
            if ($fuel_in_stmt) {
                $fuel_in_stmt->bind_param('ss', $date_from, $date_to);
                $fuel_in_stmt->execute();
                $fuel_in_result = $fuel_in_stmt->get_result();
                while ($row = $fuel_in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $fuel_in_stmt->close();
            }
        }
        
        // Get fuel OUT records
        if (in_array('fuel_out', $existing_tables)) {
            $fuel_out_sql = "SELECT 
                               id,
                               fo_date as fuel_date,
                               fo_liters as fuel_quantity,
                               fo_fuel_type as fuel_type,
                               fo_vehicle_type as vehicle_name,
                               fo_plate_no as plate_number,
                               0 as odometer_reading,
                               fo_request as purpose,
                               created_at,
                               created_by
                            FROM fuel_out 
                            WHERE fo_date BETWEEN ? AND ?
                            ORDER BY fo_date DESC";
            
            $fuel_out_stmt = $conn->prepare($fuel_out_sql);
            if ($fuel_out_stmt) {
                $fuel_out_stmt->bind_param('ss', $date_from, $date_to);
                $fuel_out_stmt->execute();
                $fuel_out_result = $fuel_out_stmt->get_result();
                while ($row = $fuel_out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $fuel_out_stmt->close();
            }
        }
        
        // If no fuel_in/fuel_out tables, get from fuel_transactions
        if (empty($fuel_in_records) && empty($fuel_out_records) && in_array('fuel_transactions', $existing_tables)) {
            // Get IN transactions
            $in_sql = "SELECT 
                          id,
                          DATE(transaction_date) as fuel_date,
                          quantity as fuel_quantity,
                          fuel_type,
                          supplier as vehicle_name,
                          '' as plate_number,
                          0 as odometer_reading,
                          notes as purpose,
                          created_at,
                          user_id as created_by
                       FROM fuel_transactions 
                       WHERE transaction_type = 'IN' 
                       AND DATE(transaction_date) BETWEEN ? AND ?
                       ORDER BY transaction_date DESC";
            
            $in_stmt = $conn->prepare($in_sql);
            if ($in_stmt) {
                $in_stmt->bind_param('ss', $date_from, $date_to);
                $in_stmt->execute();
                $in_result = $in_stmt->get_result();
                while ($row = $in_result->fetch_assoc()) {
                    $fuel_in_records[] = $row;
                }
                $in_stmt->close();
            }
            
            // Get OUT transactions
            $out_sql = "SELECT 
                           id,
                           DATE(transaction_date) as fuel_date,
                           quantity as fuel_quantity,
                           fuel_type,
                           vehicle_equipment as vehicle_name,
                           '' as plate_number,
                           odometer_reading,
                           purpose,
                           created_at,
                           user_id as created_by
                        FROM fuel_transactions 
                        WHERE transaction_type = 'OUT' 
                        AND DATE(transaction_date) BETWEEN ? AND ?
                        ORDER BY transaction_date DESC";
            
            $out_stmt = $conn->prepare($out_sql);
            if ($out_stmt) {
                $out_stmt->bind_param('ss', $date_from, $date_to);
                $out_stmt->execute();
                $out_result = $out_stmt->get_result();
                while ($row = $out_result->fetch_assoc()) {
                    $fuel_out_records[] = $row;
                }
                $out_stmt->close();
            }
        }
    }
} catch (Exception $e) {
    $error = 'Error loading fuel records: ' . $e->getMessage();
    error_log('Main User Fuel Management Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .header-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            margin-bottom: 2rem;
            animation: slideDown 0.6s ease-out 0.2s both;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            animation: slideUp 0.6s ease-out 0.4s both;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            animation: slideUp 0.6s ease-out 0.6s both;
        }
        .filter-section h5 {
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .filter-section .form-label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        .filter-section .form-control {
            font-size: 0.875rem;
            padding: 0.5rem;
        }
        .filter-section .btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
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
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        .fuel-table tbody tr {
            animation: slideUp 0.4s ease-out;
            transition: all 0.3s ease;
        }
        .fuel-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .badge-fuel-in {
            background: linear-gradient(135deg, #28a745, #20c997);
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        .badge-fuel-out {
            background: linear-gradient(135deg, #dc3545, #c82333);
            padding: 0.5rem 1rem;
            border-radius: 20px;
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
        .fuel-in-icon {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        .fuel-out-icon {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        .clickable-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .clickable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        .alert {
            animation: slideDown 0.5s ease-out;
        }
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        .scroll-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        .scroll-to-bottom {
            position: fixed;
            top: 100px;
            right: 30px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .scroll-to-bottom.show {
            opacity: 1;
            visibility: visible;
        }
        .scroll-to-bottom:hover {
            transform: translateY(3px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        .scroll-indicator {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
            z-index: 1001;
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
                        Fuel Management System
                    </h1>
                    <p class="mb-0 opacity-75">Track and monitor fuel IN and fuel OUT transactions</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light btn-lg">
                        <i class="bi bi-house-door me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

            <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <a href="fuel_in.php" class="text-decoration-none">
                    <div class="stats-card h-100 clickable-card">
                        <div class="d-flex align-items-center">
                            <div class="fuel-icon fuel-in-icon me-3">
                                <i class="bi bi-arrow-down-circle"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Total Fuel IN</h6>
                                <h3 class="mb-0 text-success">
                                    <?php 
                                    echo number_format($total_fuel_in_all, 2); ?>
                                    <small>Liters</small>
                                </h3>
                                <small class="text-muted">All Time Total</small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="fuel_out.php" class="text-decoration-none">
                    <div class="stats-card h-100 clickable-card">
                        <div class="d-flex align-items-center">
                            <div class="fuel-icon fuel-out-icon me-3">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Total Fuel OUT</h6>
                                <h3 class="mb-0 text-danger">
                                    <?php 
                                    echo number_format($total_fuel_out_all, 2); ?>
                                    <small>Liters</small>
                                </h3>
                                <small class="text-muted">All Time Total</small>
                                <!-- Debug Info (remove in production) -->
                                <?php if (isset($_GET['debug'])): ?>
                                <br><small class="text-info">Debug: Tables=<?php echo implode(',', $existing_tables); ?> Total=<?php echo $total_fuel_out_all; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="fuel_balance.php" class="text-decoration-none">
                    <div class="stats-card h-100 clickable-card">
                        <div class="d-flex align-items-center">
                            <div class="fuel-icon me-3" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                <i class="bi bi-calculator"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Net Fuel Balance</h6>
                                <h3 class="mb-0 text-primary">
                                    <?php 
                                    $net_fuel = $total_fuel_in_all - $total_fuel_out_all;
                                    echo number_format($net_fuel, 2); 
                                    ?>
                                    <small>Liters</small>
                                </h3>
                                <small class="text-muted">
                                    <?php 
                                    if ($net_fuel > 0) {
                                        echo 'Positive Balance';
                                    } elseif ($net_fuel < 0) {
                                        echo 'Negative Balance';
                                    } else {
                                        echo 'Balanced';
                                    }
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3 mb-3">
                <a href="fuel_transactions.php" class="text-decoration-none">
                    <div class="stats-card h-100 clickable-card">
                        <div class="d-flex align-items-center">
                            <div class="fuel-icon me-3" style="background: linear-gradient(135deg, #6f42c1, #0d6efd);">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-2">Total Transactions</h6>
                                <h3 class="mb-0 text-info">
                                    <?php 
                                    $total_transactions = count($fuel_in_records) + count($fuel_out_records);
                                    echo $total_transactions; ?>
                                    <small>Records</small>
                                </h3>
                                <small class="text-muted">
                                    <?php 
                                    $total_current_fuel = array_sum(array_column($fuel_inventory_records, 'current_level'));
                                    echo number_format($total_current_fuel, 2); ?> L Available
                                </small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <h5 class="mb-3">
                    <i class="bi bi-funnel me-2"></i>
                    Filter Transactions
                </h5>
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="office" class="form-label fw-semibold">Office</label>
                        <select class="form-select" id="office" name="office">
                            <option value="0" <?php echo $office_filter === 0 ? 'selected' : ''; ?>>All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?php echo (int)$office['id']; ?>" <?php echo $office_filter === (int)$office['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($office['office_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label fw-semibold">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label fw-semibold">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-gradient flex-fill">
                                <i class="bi bi-funnel me-1"></i>
                                Filter
                            </button>
                            <a href="fuel_management.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Fuel Inventory Section -->
            <div class="table-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">
                        <i class="bi bi-droplet-half text-info me-2"></i>
                        Fuel Inventory Status
                        <span class="badge bg-info text-white ms-2">
                            <?php echo count($fuel_inventory_records); ?> Tanks
                        </span>
                    </h4>
                </div>
                
                <?php if (!empty($fuel_inventory_records)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover fuel-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-hash me-1"></i>Tank Number</th>
                                    <th><i class="bi bi-fuel-pump me-1"></i>Fuel Type</th>
                                    <th><i class="bi bi-database me-1"></i>Capacity (L)</th>
                                    <th><i class="bi bi-speedometer2 me-1"></i>Current Level (L)</th>
                                    <th><i class="bi bi-percent me-1"></i>Fill Level</th>
                                    <th><i class="bi bi-geo-alt me-1"></i>Location</th>
                                    <th><i class="bi bi-toggle-on me-1"></i>Status</th>
                                    <th><i class="bi bi-clock me-1"></i>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_inventory_records as $tank): 
                                    $fill_percentage = ($tank['capacity'] > 0) ? ($tank['current_level'] / $tank['capacity']) * 100 : 0;
                                    $status_color = $tank['status'] === 'active' ? 'success' : ($tank['status'] === 'maintenance' ? 'warning' : 'secondary');
                                    $fuel_type_color = $tank['fuel_type'] === 'diesel' ? 'warning' : ($tank['fuel_type'] === 'gasoline' ? 'info' : 'primary');
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($tank['tank_number']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $fuel_type_color; ?> text-white">
                                                <?php echo strtoupper(htmlspecialchars($tank['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($tank['capacity'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <strong class="text-<?php echo $fill_percentage > 30 ? 'success' : ($fill_percentage > 15 ? 'warning' : 'danger'); ?>">
                                                <?php echo number_format($tank['current_level'], 2); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress me-2" style="width: 100px; height: 20px;">
                                                    <div class="progress-bar bg-<?php echo $fill_percentage > 30 ? 'success' : ($fill_percentage > 15 ? 'warning' : 'danger'); ?>" 
                                                         style="width: <?php echo min(100, $fill_percentage); ?>%">
                                                    </div>
                                                </div>
                                                <small><?php echo number_format($fill_percentage, 1); ?>%</small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($tank['location'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $status_color; ?> text-white">
                                                <?php echo ucfirst(htmlspecialchars($tank['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y H:i', strtotime($tank['last_updated'])); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Inventory Summary -->
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-info">Total Capacity</h6>
                                    <h5 class="card-text">
                                        <?php echo number_format(array_sum(array_column($fuel_inventory_records, 'capacity')), 2); ?> L
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-success">Current Fuel</h6>
                                    <h5 class="card-text">
                                        <?php echo number_format(array_sum(array_column($fuel_inventory_records, 'current_level')), 2); ?> L
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-warning">Available Space</h6>
                                    <h5 class="card-text">
                                        <?php 
                                        $total_capacity = array_sum(array_column($fuel_inventory_records, 'capacity'));
                                        $total_current = array_sum(array_column($fuel_inventory_records, 'current_level'));
                                        echo number_format($total_capacity - $total_current, 2); ?> L
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-primary">Average Fill</h6>
                                    <h5 class="card-text">
                                        <?php 
                                        $avg_fill = $total_capacity > 0 ? ($total_current / $total_capacity) * 100 : 0;
                                        echo number_format($avg_fill, 1); ?>%
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-droplet-half text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Fuel Inventory Data</h5>
                        <p class="text-muted">No fuel tanks found in the inventory system.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Empty State Message -->
            <?php if (empty($fuel_in_records) && empty($fuel_out_records)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-fuel-pump text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">No Fuel Records Found</h4>
                    <p class="text-muted">No fuel transactions found for the selected period and filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fuel IN Section -->
    <div class="container-fluid mt-4">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="bi bi-arrow-down-circle text-success me-2"></i>
                    Fuel IN Records
                    <span class="badge badge-fuel-in ms-2">
                        <?php echo count($fuel_in_records); ?> Transactions
                    </span>
                </h4>
            </div>
            
            <?php if (!empty($fuel_in_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover fuel-table">
                        <thead>
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
                <div class="text-center py-5">
                    <i class="bi bi-arrow-down-circle text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Fuel IN Records Found</h5>
                    <p class="text-muted">No fuel IN transactions found for the selected period.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fuel Transactions Table Section -->
    <div class="container-fluid mt-4">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <i class="bi bi-list-ul text-primary me-2"></i>
                    All Fuel Transactions
                    <span class="badge bg-primary text-white ms-2">
                        <?php 
                        $total_transactions = count($fuel_in_records) + count($fuel_out_records);
                        echo $total_transactions; ?> 
                        Records
                    </span>
                </h4>
            </div>
            
            <?php if (!empty($fuel_in_records) || !empty($fuel_out_records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover fuel-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                <th><i class="bi bi-arrow-up-down me-1"></i>Type</th>
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
                            <?php 
                            // Combine and sort all transactions
                            $all_transactions = [];
                            
                            // Add fuel IN records
                            foreach ($fuel_in_records as $record) {
                                $all_transactions[] = array_merge($record, ['transaction_type' => 'IN', 'type_color' => 'success']);
                            }
                            
                            // Add fuel OUT records
                            foreach ($fuel_out_records as $record) {
                                $all_transactions[] = array_merge($record, ['transaction_type' => 'OUT', 'type_color' => 'danger']);
                            }
                            
                            // Sort by date
                            usort($all_transactions, function($a, $b) {
                                return strtotime($b['fuel_date']) - strtotime($a['fuel_date']);
                            });
                            
                            foreach ($all_transactions as $record): 
                            ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('M d, Y', strtotime($record['fuel_date'])); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $record['type_color']; ?> text-white">
                                            <?php echo $record['transaction_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-<?php echo $record['type_color']; ?>">
                                            <?php echo htmlspecialchars($record['vehicle_name'] ?? 'Unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['plate_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <strong class="text-<?php echo $record['type_color']; ?>">
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
                <div class="text-center py-5">
                    <i class="bi bi-list-ul text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Fuel Transactions Found</h5>
                    <p class="text-muted">No fuel transactions found for the selected period and filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['system_admin', 'admin', 'main_user'], true)) {
    header('Location: ../index.php');
    exit();
}

logSystemAction($_SESSION['user_id'], 'access', 'main_user_fuel_management', 'Main user accessed fuel management');

$fuel_records = [];
$vehicles = [];
$error = null;

// Filter parameters
$vehicle_filter = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if (!$conn || $conn->connect_error) {
    $error = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Get all vehicles
        $vehicle_query = "SELECT id, vehicle_name, plate_number FROM vehicles ORDER BY vehicle_name ASC";
        $vehicle_result = $conn->query($vehicle_query);
        if ($vehicle_result) {
            while ($row = $vehicle_result->fetch_assoc()) {
                $vehicles[] = $row;
            }
        }
        
        // Build fuel records query with filters
        $fuel_sql = "SELECT 
                        fr.id,
                        fr.liters,
                        fr.cost,
                        fr.cost_per_liter,
                        fr.date_filled,
                        fr.odometer_reading,
                        fr.notes,
                        fr.vehicle_id,
                        v.vehicle_name,
                        v.plate_number,
                        u.username as created_by_user
                    FROM fuel_records fr
                    LEFT JOIN vehicles v ON fr.vehicle_id = v.id
                    LEFT JOIN users u ON fr.created_by = u.id";
        
        $params = [];
        $types = '';
        $where_clauses = [];
        
        if ($vehicle_filter > 0) {
            $where_clauses[] = "fr.vehicle_id = ?";
            $params[] = $vehicle_filter;
            $types .= 'i';
        }
        
        if ($start_date !== '') {
            $where_clauses[] = "DATE(fr.date_filled) >= ?";
            $params[] = $start_date;
            $types .= 's';
        }
        
        if ($end_date !== '') {
            $where_clauses[] = "DATE(fr.date_filled) <= ?";
            $params[] = $end_date;
            $types .= 's';
        }
        
        if (!empty($where_clauses)) {
            $fuel_sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $fuel_sql .= " ORDER BY fr.date_filled DESC";
        
        $stmt = $conn->prepare($fuel_sql);
        if (!$stmt) {
            $error = 'Failed to prepare query.';
        } else {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $fuel_records[] = $row;
            }
            $stmt->close();
        }
        
    } catch (Exception $e) {
        $error = 'Error loading fuel data: ' . $e->getMessage();
        error_log('Main User Fuel Management Error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="../ADMIN/dashboard.css" rel="stylesheet">
    <style>
        .fuel-stat {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .fuel-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #191BA9;
            display: block;
        }
        
        .fuel-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .fuel-item {
            border-left: 3px solid #28a745;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        
        .fuel-info {
            margin-bottom: 0.5rem;
        }
        
        .fuel-vehicle {
            font-weight: 600;
            color: #191BA9;
        }
        
        .fuel-details {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .fuel-amount {
            font-weight: 600;
            color: #28a745;
        }
        
        .fuel-cost {
            font-weight: 600;
            color: #dc3545;
        }
        
        .fuel-date {
            font-size: 0.875rem;
            color: #6c757d;
            text-align: right;
        }
        
        .fuel-list {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php $page_title = 'Fuel Management'; ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>

        <div class="main-content">
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-1" style="font-weight: 700; color: #191BA9;">
                            <i class="bi bi-fuel-pump me-2"></i>Fuel Management
                        </h1>
                        <p class="text-muted mb-0">Manage fuel consumption and vehicle refueling records.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                            <a class="btn btn-outline-success btn-sm" href="fuel_management.php">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </a>
                            <div class="d-inline-block" style="min-width: 200px;">
                                <select class="form-select form-select-sm" id="vehicleFilter">
                                    <option value="0" <?php echo $vehicle_filter === 0 ? 'selected' : ''; ?>>All Vehicles</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo (int)$vehicle['id']; ?>" <?php echo $vehicle_filter === (int)$vehicle['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['vehicle_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vehicle</th>
                                <th>Liters</th>
                                <th>Cost</th>
                                <th>Cost/Liter</th>
                                <th>Odometer</th>
                                <th>Added By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$error && !empty($fuel_records)): ?>
                                <?php foreach ($fuel_records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['date_filled'] ?? ''); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($record['vehicle_name'] ?? ''); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($record['plate_number'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo number_format((float)($record['liters'] ?? 0), 2); ?></td>
                                        <td>₱<?php echo number_format((float)($record['cost'] ?? 0), 2); ?></td>
                                        <td>₱<?php echo number_format((float)($record['cost_per_liter'] ?? 0), 2); ?></td>
                                        <td><?php echo htmlspecialchars($record['odometer_reading'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($record['created_by_user'] ?? ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewFuelRecord(<?php echo (int)$record['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No fuel records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const vehicleFilter = document.getElementById('vehicleFilter');

            function applyFilters() {
                const currentUrl = new URL(window.location.href);
                
                const vehicleValue = parseInt(vehicleFilter.value || '0', 10);
                if (vehicleValue > 0) {
                    currentUrl.searchParams.set('vehicle_id', String(vehicleValue));
                } else {
                    currentUrl.searchParams.delete('vehicle_id');
                }

                window.location.href = currentUrl.toString();
            }

            if (vehicleFilter) {
                vehicleFilter.addEventListener('change', applyFilters);
            }

            function viewFuelRecord(recordId) {
                // This could open a modal with record details
                console.log('View fuel record:', recordId);
            }
        });

        // Scroll functionality
        const scrollToTopBtn = document.getElementById('scrollToTop');
        const scrollToBottomBtn = document.getElementById('scrollToBottom');
        const scrollIndicator = document.getElementById('scrollIndicator');
        
        // Show/hide scroll buttons based on scroll position
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollPercent = (scrollTop / scrollHeight) * 100;
            
            // Update scroll indicator
            scrollIndicator.style.transform = `scaleX(${scrollPercent / 100})`;
            
            // Show scroll to top button when scrolled down
            if (scrollTop > 200) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
            
            // Show scroll to bottom button when not at bottom
            if (scrollTop < scrollHeight - 200) {
                scrollToBottomBtn.classList.add('show');
            } else {
                scrollToBottomBtn.classList.remove('show');
            }
        });
        
        // Scroll to top functionality
        scrollToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Scroll to bottom functionality
        scrollToBottomBtn.addEventListener('click', function() {
            window.scrollTo({
                top: document.documentElement.scrollHeight,
                behavior: 'smooth'
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Home key to scroll to top
            if (e.key === 'Home' && !e.ctrlKey && !e.altKey && !e.shiftKey) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
            
            // End key to scroll to bottom
            if (e.key === 'End' && !e.ctrlKey && !e.altKey && !e.shiftKey) {
                e.preventDefault();
                window.scrollTo({
                    top: document.documentElement.scrollHeight,
                    behavior: 'smooth'
                });
            }
        });
        
        // Initialize scroll buttons on page load
        window.addEventListener('load', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > 200) {
                scrollToTopBtn.classList.add('show');
            }
            if (scrollTop < document.documentElement.scrollHeight - 200) {
                scrollToBottomBtn.classList.add('show');
            }
        });
    </script>

    <!-- Scroll Buttons -->
    <div class="scroll-to-top" id="scrollToTop" title="Scroll to Top">
        <i class="bi bi-arrow-up"></i>
    </div>
    
    <div class="scroll-to-bottom" id="scrollToBottom" title="Scroll to Bottom">
        <i class="bi bi-arrow-down"></i>
    </div>
    
    <!-- Scroll Progress Indicator -->
    <div class="scroll-indicator" id="scrollIndicator"></div>
</body>
</html>
