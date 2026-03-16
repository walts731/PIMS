<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role (admin, system_admin, or fuel)
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel', 'main_user'])) {
    header('Location: ../index.php');
    exit();
}

// Log fuel page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_dashboard', 'User accessed fuel dashboard');

// Initialize variables
$fuel_inventory = [];
$fuel_transactions = [];
$stats = [
    'total_fuel_in' => 0,
    'total_fuel_out' => 0,
    'current_balance' => 0,
    'total_transactions' => 0,
    'active_tanks' => 0
];
$error = null;

try {
    // Check if fuel tables exist
    $tables_exist = false;
    $check_tables = $conn->query("SHOW TABLES LIKE 'fuel_transactions'");
    if ($check_tables && $check_tables->num_rows > 0) {
        $tables_exist = true;
    }
    
    if ($tables_exist) {
        // Get fuel inventory data
        $inventory_sql = "SELECT 
                            id,
                            tank_number,
                            fuel_type,
                            capacity,
                            current_level,
                            location,
                            status,
                            last_updated,
                            created_at
                         FROM fuel_inventory 
                         ORDER BY fuel_type, tank_number";
        $inventory_result = $conn->query($inventory_sql);
        if ($inventory_result) {
            while ($row = $inventory_result->fetch_assoc()) {
                $fuel_inventory[] = $row;
            }
        }
        
        // Get fuel statistics
        $stats_sql = "SELECT 
                        transaction_type,
                        SUM(quantity) as total_quantity,
                        COUNT(*) as transaction_count
                      FROM fuel_transactions 
                      GROUP BY transaction_type";
        $stats_result = $conn->query($stats_sql);
        if ($stats_result) {
            while ($row = $stats_result->fetch_assoc()) {
                if ($row['transaction_type'] === 'IN') {
                    $stats['total_fuel_in'] = $row['total_quantity'];
                } elseif ($row['transaction_type'] === 'OUT') {
                    $stats['total_fuel_out'] = $row['total_quantity'];
                }
                $stats['total_transactions'] += $row['transaction_count'];
            }
        }
        
        $stats['current_balance'] = $stats['total_fuel_in'] - $stats['total_fuel_out'];
        $stats['active_tanks'] = count(array_filter($fuel_inventory, function($tank) {
            return $tank['status'] === 'active';
        }));
        
        // Get recent transactions
        $recent_sql = "SELECT 
                         id,
                         transaction_type,
                         transaction_date,
                         quantity,
                         fuel_type,
                         vehicle_equipment,
                         purpose,
                         created_at,
                         user_id
                       FROM fuel_transactions 
                       ORDER BY transaction_date DESC 
                       LIMIT 10";
        $recent_result = $conn->query($recent_sql);
        if ($recent_result) {
            while ($row = $recent_result->fetch_assoc()) {
                $fuel_transactions[] = $row;
            }
        }
    } else {
        $error = 'Fuel management tables not found. Please contact administrator to set up the fuel management system.';
    }
} catch (Exception $e) {
    $error = 'Error loading fuel data: ' . $e->getMessage();
    error_log('Fuel Dashboard Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Dashboard - PIMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    
    <style>
        .fuel-dashboard {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin: 1rem;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-card.success::before { background: linear-gradient(90deg, #28a745, #20c997); }
        .stat-card.danger::before { background: linear-gradient(90deg, #dc3545, #c82333); }
        .stat-card.info::before { background: linear-gradient(90deg, #17a2b8, #138496); }
        .stat-card.warning::before { background: linear-gradient(90deg, #ffc107, #e0a800); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); color: white; }
        .stat-icon.danger { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #e0a800); color: white; }
        
        .module-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: none;
            height: 100%;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .module-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            color: white;
        }
        
        .module-icon.blue { background: linear-gradient(135deg, #007bff, #0056b3); }
        .module-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
        .module-icon.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .module-icon.purple { background: linear-gradient(135deg, #6f42c1, #563d7c); }
        .module-icon.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        
        .tank-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .tank-status.active { background: #28a745; }
        .tank-status.inactive { background: #dc3545; }
        .tank-status.maintenance { background: #ffc107; }
        
        .fuel-level-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .fuel-level-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .transaction-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 20px 20px 0 0;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-top: 2rem;
        }
        
        .fuel-table {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .fuel-table thead {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }
        
        .fuel-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
            transition: all 0.3s ease;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out;
        }
        
        .btn-fuel {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-fuel:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
    </style>
</head>
<body class="fuel-dashboard">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Dashboard';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content animate-slide-up">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-speedometer2 me-3"></i>
                            Fuel Dashboard
                        </h1>
                        <p class="mb-0 opacity-75">Manage fuel tanks, transactions, and inventory levels</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-outline-light btn-sm" onclick="refreshFuelData()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <button class="btn btn-outline-light btn-sm ms-2" onclick="exportFuelData()">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">
                            <i class="bi bi-house-door me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Fuel In</h6>
                        <h3 class="mb-0 text-success">
                            <?php echo number_format($stats['total_fuel_in'], 2); ?>
                            <small class="text-muted">Liters</small>
                        </h3>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card danger">
                        <div class="stat-icon danger">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total Fuel Out</h6>
                        <h3 class="mb-0 text-danger">
                            <?php echo number_format($stats['total_fuel_out'], 2); ?>
                            <small class="text-muted">Liters</small>
                        </h3>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="stat-icon info">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <h6 class="text-muted mb-2">Current Balance</h6>
                        <h3 class="mb-0 text-info">
                            <?php echo number_format($stats['current_balance'], 2); ?>
                            <small class="text-muted">Liters</small>
                        </h3>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <h6 class="text-muted mb-2">Active Tanks</h6>
                        <h3 class="mb-0 text-warning">
                            <?php echo $stats['active_tanks']; ?>
                            <small class="text-muted">Tanks</small>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Quick Access Module Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <a href="?tab=inventory" class="module-card">
                        <div class="module-icon blue">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <div class="module-title">Fuel Inventory</div>
                        <div class="module-desc text-muted">Stock levels</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?tab=fuelin" class="module-card">
                        <div class="module-icon success">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div class="module-title">Fuel In</div>
                        <div class="module-desc text-muted">Add fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?tab=fuelout" class="module-card">
                        <div class="module-icon danger">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div class="module-title">Fuel Out</div>
                        <div class="module-desc text-muted">Dispense fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?tab=reports" class="module-card">
                        <div class="module-icon purple">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <div class="module-title">Reports</div>
                        <div class="module-desc text-muted">Analytics</div>
                    </a>
                </div>
            </div>

            <!-- Fuel Management Tabs -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fuel Management</h5>
                </div>
                <div class="card-body">
                    <!-- Navigation Tabs -->
                    <ul class="nav nav-tabs" id="fuelTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                                <i class="bi bi-fuel-pump me-2"></i>Inventory
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fuelin-tab" data-bs-toggle="tab" data-bs-target="#fuelin" type="button" role="tab">
                                <i class="bi bi-arrow-down-circle me-2"></i>Fuel In
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fuelout-tab" data-bs-toggle="tab" data-bs-target="#fuelout" type="button" role="tab">
                                <i class="bi bi-arrow-up-circle me-2"></i>Fuel Out
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                <i class="bi bi-file-earmark-bar-graph me-2"></i>Reports
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="fuelTabContent">
                        <!-- Inventory Tab -->
                        <div class="tab-pane fade show active" id="inventory" role="tabpanel">
                            <?php if (!empty($fuel_inventory)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover fuel-table" id="inventoryTable">
                                        <thead>
                                            <tr>
                                                <th>Tank Number</th>
                                                <th>Fuel Type</th>
                                                <th>Capacity</th>
                                                <th>Current Level</th>
                                                <th>Status</th>
                                                <th>Location</th>
                                                <th>Last Updated</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fuel_inventory as $tank): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($tank['tank_number']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-info text-white">
                                                            <?php echo htmlspecialchars(ucfirst($tank['fuel_type'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($tank['capacity'], 2); ?> L</td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo number_format($tank['current_level'], 2); ?> L</strong>
                                                            <div class="fuel-level-bar">
                                                                <div class="fuel-level-fill" style="width: <?php echo min(100, ($tank['current_level'] / $tank['capacity']) * 100); ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="tank-status <?php echo htmlspecialchars($tank['status']); ?>"></span>
                                                        <?php echo htmlspecialchars(ucfirst($tank['status'])); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($tank['location']); ?></td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($tank['last_updated'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-fuel-pump text-muted" style="font-size: 4rem;"></i>
                                    <h5 class="text-muted mt-3">No Fuel Tanks Found</h5>
                                    <p class="text-muted">No fuel tanks have been set up in the system yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Fuel In Tab -->
                        <div class="tab-pane fade" id="fuelin" role="tabpanel">
                            <div class="text-center py-5">
                                <i class="bi bi-arrow-down-circle text-success" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Fuel In Management</h5>
                                <p class="text-muted">Add and track fuel deliveries and refueling operations.</p>
                                <button class="btn btn-success" onclick="showAddFuelInModal()">
                                    <i class="bi bi-plus-circle me-2"></i>Add Fuel In
                                </button>
                            </div>
                        </div>
                        
                        <!-- Fuel Out Tab -->
                        <div class="tab-pane fade" id="fuelout" role="tabpanel">
                            <div class="text-center py-5">
                                <i class="bi bi-arrow-up-circle text-danger" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Fuel Out Management</h5>
                                <p class="text-muted">Record fuel dispensing and vehicle refueling.</p>
                                <button class="btn btn-danger" onclick="showAddFuelOutModal()">
                                    <i class="bi bi-plus-circle me-2"></i>Add Fuel Out
                                </button>
                            </div>
                        </div>
                        
                        <!-- Reports Tab -->
                        <div class="tab-pane fade" id="reports" role="tabpanel">
                            <div class="text-center py-5">
                                <i class="bi bi-file-earmark-bar-graph text-primary" style="font-size: 4rem;"></i>
                                <h5 class="text-muted mt-3">Fuel Reports</h5>
                                <p class="text-muted">Generate comprehensive reports and analytics.</p>
                                <div class="mt-3">
                                    <button class="btn btn-primary me-2" onclick="generateReport('summary')">
                                        <i class="bi bi-file-earmark-text me-2"></i>Summary Report
                                    </button>
                                    <button class="btn btn-outline-primary me-2" onclick="generateReport('transactions')">
                                        <i class="bi bi-list-check me-2"></i>Transaction Report
                                    </button>
                                    <button class="btn btn-outline-primary" onclick="generateReport('inventory')">
                                        <i class="bi bi-fuel-pump me-2"></i>Inventory Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <?php if (!empty($fuel_transactions)): ?>
                <div class="table-container">
                    <h5 class="mb-3">
                        <i class="bi bi-clock-history me-2"></i>Recent Transactions
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover fuel-table" id="recentTransactionsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Fuel Type</th>
                                    <th>Vehicle/Equipment</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fuel_transactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($transaction['transaction_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                                <?php echo $transaction['transaction_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($transaction['quantity'], 2); ?> L</td>
                                        <td><?php echo htmlspecialchars(ucfirst($transaction['fuel_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['vehicle_equipment']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['purpose']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for Add Fuel Transaction -->
    <div class="modal fade" id="fuelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fuelModalTitle">Fuel Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fuelModalBody">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize DataTables
        $('#inventoryTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']]
        });
        
        $('#recentTransactionsTable').DataTable({
            responsive: true,
            pageLength: 5,
            order: [[0, 'desc']]
        });
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            const activeTab = document.querySelector('#fuelTabs .nav-link.active');
            if (activeTab && activeTab.id === 'inventory-tab') {
                refreshFuelData();
            }
        }, 30000);
    });

    function refreshFuelData() {
        location.reload();
    }

    function exportFuelData() {
        const activeTab = document.querySelector('#fuelTabs .nav-link.active');
        if (!activeTab) {
            alert('No active tab found');
            return;
        }
        
        const activeTabId = activeTab.id;
        
        switch(activeTabId) {
            case 'inventory-tab':
                window.open('fuel_tabs/export_fuel_report.php?export=1&type=inventory', '_blank');
                break;
            case 'fuelin-tab':
                window.open('fuel_tabs/export_fuel_report.php?export=1&type=fuel_in', '_blank');
                break;
            case 'fuelout-tab':
                window.open('fuel_tabs/export_fuel_report.php?export=1&type=fuel_out', '_blank');
                break;
            case 'reports-tab':
                window.open('fuel_tabs/export_fuel_report.php?export=1', '_blank');
                break;
            default:
                alert('Export functionality not available for this tab');
        }
    }

    function showAddFuelInModal() {
        const modal = new bootstrap.Modal(document.getElementById('fuelModal'));
        document.getElementById('fuelModalTitle').textContent = 'Add Fuel In';
        
        // Load modal content
        fetch('fuel_tabs/fuel_in_form.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('fuelModalBody').innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error('Error loading fuel in form:', error);
                alert('Error loading form. Please try again.');
            });
    }

    function showAddFuelOutModal() {
        const modal = new bootstrap.Modal(document.getElementById('fuelModal'));
        document.getElementById('fuelModalTitle').textContent = 'Add Fuel Out';
        
        // Load modal content
        fetch('fuel_tabs/fuel_out_form.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('fuelModalBody').innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error('Error loading fuel out form:', error);
                alert('Error loading form. Please try again.');
            });
    }

    function generateReport(type) {
        window.open(`fuel_tabs/generate_report.php?type=${type}`, '_blank');
    }
    </script>
</body>
</html>
