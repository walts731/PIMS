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
if (!in_array($_SESSION['role'], ['admin', 'system_admin', 'fuel'])) {
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
            margin: 1rem 1rem 1rem 5rem;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-height: calc(100vh - 76px);
            overflow-y: auto;
            overflow-x: hidden;
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin: 0.5rem;
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
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
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once '../ADMIN/includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-speedometer2"></i> Fuel Management System
                    </h1>
                    <p class="text-muted mb-0">Manage fuel tanks, transactions, and inventory levels</p>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary btn-sm" onclick="refreshFuelData()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportFuelData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                    <a href="../index.php" class="btn btn-outline-secondary btn-sm ms-2">
                        <i class="bi bi-house-door"></i> Home
                    </a>
                </div>
            </div>
        </div>

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
                    <a href="?page=inventory" class="module-card">
                        <div class="module-icon blue">
                            <i class="bi bi-fuel-pump"></i>
                        </div>
                        <div class="module-title">Fuel Inventory</div>
                        <div class="module-desc text-muted">Stock levels</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?page=fuelin" class="module-card">
                        <div class="module-icon success">
                            <i class="bi bi-arrow-down-circle"></i>
                        </div>
                        <div class="module-title">Fuel In</div>
                        <div class="module-desc text-muted">Add fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?page=fuelout" class="module-card">
                        <div class="module-icon danger">
                            <i class="bi bi-arrow-up-circle"></i>
                        </div>
                        <div class="module-title">Fuel Out</div>
                        <div class="module-desc text-muted">Dispense fuel</div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="?page=reports" class="module-card">
                        <div class="module-icon purple">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                        <div class="module-title">Reports</div>
                        <div class="module-desc text-muted">Analytics</div>
                    </a>
                </div>
            </div>

            <!-- Page Content Based on Selection -->
            <?php
            $current_page = $_GET['page'] ?? 'dashboard';
            
            switch($current_page) {
                case 'inventory':
                    include 'pages/inventory.php';
                    break;
                case 'fuelin':
                    include 'pages/fuel_in.php';
                    break;
                case 'fuelout':
                    include 'pages/fuel_out.php';
                    break;
                case 'reports':
                    include 'pages/reports.php';
                    break;
                default:
                    // Default dashboard view
                    include 'pages/dashboard_overview.php';
            }
            ?>
        </div>
    </div>

    <!-- Modal for Add/Edit Fuel -->
    <div class="modal fade" id="fuelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Fuel Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
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
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 10
        });
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            const activePage = '<?php echo $current_page; ?>';
            if (activePage === 'dashboard' || activePage === 'inventory') {
                refreshFuelData();
            }
        }, 30000);
    });

    function refreshFuelData() {
        location.reload();
    }

    function exportFuelData() {
        const activePage = '<?php echo $current_page; ?>';
        
        switch(activePage) {
            case 'inventory':
                window.open('pages/export_fuel_report.php?export=1&type=inventory', '_blank');
                break;
            case 'fuelin':
                window.open('pages/export_fuel_report.php?export=1&type=fuel_in', '_blank');
                break;
            case 'fuelout':
                window.open('pages/export_fuel_report.php?export=1&type=fuel_out', '_blank');
                break;
            case 'reports':
                window.open('pages/export_fuel_report.php?export=1', '_blank');
                break;
            default:
                window.open('pages/export_fuel_report.php?export=1', '_blank');
        }
    }

    function showFuelModal(type, id = null) {
        const modal = new bootstrap.Modal(document.getElementById('fuelModal'));
        
        // Load modal content based on type
        fetch(`pages/modal_content.php?type=${type}&id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                document.querySelector('#fuelModal .modal-body').innerHTML = html;
                modal.show();
            })
            .catch(error => {
                console.error('Error loading modal content:', error);
                alert('Error loading modal content: ' + error.message);
            });
    }
    </script>
</body>
</html>
