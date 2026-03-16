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

// Log reports page access
logSystemAction($_SESSION['user_id'], 'access', 'fuel_reports_page', 'User accessed fuel reports page');

// Get report parameters
$report_type = $_GET['report_type'] ?? 'summary';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$fuel_type_filter = $_GET['fuel_type'] ?? '';

// Initialize report data
$report_data = [];
$summary_stats = [];

try {
    switch ($report_type) {
        case 'summary':
            // Get summary statistics
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
            
            $summary_sql .= " GROUP BY transaction_type, fuel_type ORDER BY total_quantity DESC";
            
            $stmt = $conn->prepare($summary_sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt->close();
            
            // Calculate overall stats
            $total_in = 0;
            $total_out = 0;
            $total_transactions = 0;
            
            foreach ($report_data as $row) {
                if ($row['transaction_type'] === 'IN') {
                    $total_in += $row['total_quantity'];
                } else {
                    $total_out += $row['total_quantity'];
                }
                $total_transactions += $row['transaction_count'];
            }
            
            $summary_stats = [
                'total_fuel_in' => $total_in,
                'total_fuel_out' => $total_out,
                'net_balance' => $total_in - $total_out,
                'total_transactions' => $total_transactions,
                'avg_transaction_size' => $total_transactions > 0 ? ($total_in + $total_out) / $total_transactions : 0
            ];
            break;
            
        case 'transactions':
            // Get detailed transactions
            $transactions_sql = "SELECT 
                                   id,
                                   transaction_type,
                                   transaction_date,
                                   quantity,
                                   fuel_type,
                                   supplier,
                                   vehicle_equipment,
                                   purpose,
                                   tank_number,
                                   driver_name,
                                   department,
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
                $report_data[] = $row;
            }
            $stmt->close();
            break;
            
        case 'inventory':
            // Get inventory status
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
                $report_data[] = $row;
            }
            break;
    }
} catch (Exception $e) {
    error_log('Reports Error: ' . $e->getMessage());
    $error = 'Error generating report: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Reports - PIMS</title>
    
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
        .reports-page {
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
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            margin: -2rem -2rem 2rem -2rem;
            border-radius: 20px 20px 0 0;
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
        
        .stat-card.success::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #28a745, #20c997);
        }
        
        .stat-card.danger::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .stat-card.info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #17a2b8, #138496);
        }
        
        .stat-card.warning::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #e0a800);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-icon.success { background: linear-gradient(135deg, #28a745, #20c997); }
        .stat-icon.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .stat-icon.info { background: linear-gradient(135deg, #17a2b8, #138496); }
        .stat-icon.warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
        
        .filter-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .btn-reports {
            background: linear-gradient(135deg, #6f42c1, #563d7c);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-reports:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(111, 66, 193, 0.3);
            color: white;
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
<body class="reports-page">
    <?php
    // Set page title for topbar
    $page_title = 'Fuel Reports';
    ?>
    <!-- Main Content Wrapper -->
    <div class="fuel-main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once '../MAIN_USER/includes/topbar.php'; ?>
    
        <!-- Main Content -->
        <div class="main-content animate-slide-up">
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="bi bi-file-earmark-bar-graph me-3"></i>
                            Fuel Reports
                        </h1>
                        <p class="mb-0 opacity-75">Generate comprehensive reports and analytics</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn btn-outline-light btn-sm" onclick="refreshPage()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                        <button class="btn btn-outline-light btn-sm ms-2" onclick="exportReport()">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <a href="dashboard.php" class="btn btn-light btn-sm ms-2">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Report Filters -->
            <div class="filter-card">
                <h5 class="mb-4">
                    <i class="bi bi-funnel me-2 text-primary"></i>
                    Report Filters
                </h5>
                <form method="GET" action="reports.php">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                                <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                                <option value="transactions" <?php echo $report_type === 'transactions' ? 'selected' : ''; ?>>Detailed Transactions</option>
                                <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Inventory Status</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from; ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to; ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-2">
                            <label for="fuel_type" class="form-label">Fuel Type</label>
                            <select class="form-select" id="fuel_type" name="fuel_type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="diesel" <?php echo $fuel_type_filter === 'diesel' ? 'selected' : ''; ?>>Diesel</option>
                                <option value="gasoline" <?php echo $fuel_type_filter === 'gasoline' ? 'selected' : ''; ?>>Gasoline</option>
                                <option value="premium" <?php echo $fuel_type_filter === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="kerosene" <?php echo $fuel_type_filter === 'kerosene' ? 'selected' : ''; ?>>Kerosene</option>
                                <option value="lpg" <?php echo $fuel_type_filter === 'lpg' ? 'selected' : ''; ?>>LPG</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary me-2" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="printReport()">
                                <i class="bi bi-printer me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if ($report_type === 'summary'): ?>
                <!-- Summary Report -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="bi bi-arrow-down-circle"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total Fuel IN</h6>
                            <h4 class="mb-0 text-success"><?php echo number_format($summary_stats['total_fuel_in'], 2); ?> L</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card danger">
                            <div class="stat-icon danger">
                                <i class="bi bi-arrow-up-circle"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total Fuel OUT</h6>
                            <h4 class="mb-0 text-danger"><?php echo number_format($summary_stats['total_fuel_out'], 2); ?> L</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card info">
                            <div class="stat-icon info">
                                <i class="bi bi-calculator"></i>
                            </div>
                            <h6 class="text-muted mb-2">Net Balance</h6>
                            <h4 class="mb-0 text-info"><?php echo number_format($summary_stats['net_balance'], 2); ?> L</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card warning">
                            <div class="stat-icon warning">
                                <i class="bi bi-receipt"></i>
                            </div>
                            <h6 class="text-muted mb-2">Total Transactions</h6>
                            <h4 class="mb-0 text-warning"><?php echo $summary_stats['total_transactions']; ?></h4>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Summary by Fuel Type</h5>
                        <span class="badge bg-primary text-white"><?php echo count($report_data); ?> Records</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Fuel Type</th>
                                    <th>Transaction Type</th>
                                    <th>Total Quantity (L)</th>
                                    <th>Transaction Count</th>
                                    <th>Avg Quantity (L)</th>
                                    <th>Min Quantity (L)</th>
                                    <th>Max Quantity (L)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-info text-white">
                                                <?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                                <?php echo $row['transaction_type']; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($row['total_quantity'], 2); ?></strong></td>
                                        <td><?php echo $row['transaction_count']; ?></td>
                                        <td><?php echo number_format($row['avg_quantity'], 2); ?></td>
                                        <td><?php echo number_format($row['min_quantity'], 2); ?></td>
                                        <td><?php echo number_format($row['max_quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($report_type === 'transactions'): ?>
                <!-- Detailed Transactions Report -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            Detailed Transactions
                            <span class="badge bg-primary text-white ms-2"><?php echo count($report_data); ?></span>
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="exportTransactions()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Fuel Type</th>
                                    <th>Quantity (L)</th>
                                    <th>Supplier</th>
                                    <th>Vehicle/Equipment</th>
                                    <th>Tank</th>
                                    <th>Driver</th>
                                    <th>Purpose</th>
                                    <th>User</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['transaction_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['transaction_type'] === 'IN' ? 'success' : 'danger'; ?> text-white">
                                                <?php echo $row['transaction_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?></td>
                                        <td><?php echo number_format($row['quantity'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['supplier'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['vehicle_equipment'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['tank_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['driver_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                        <td><?php echo $row['user_id']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($report_type === 'inventory'): ?>
                <!-- Inventory Status Report -->
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            Current Inventory Status
                            <span class="badge bg-primary text-white ms-2"><?php echo count($report_data); ?></span>
                        </h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="exportInventory()">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Tank Number</th>
                                    <th>Fuel Type</th>
                                    <th>Capacity (L)</th>
                                    <th>Current Level (L)</th>
                                    <th>Fill %</th>
                                    <th>Available (L)</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['tank_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(ucfirst($row['fuel_type'])); ?></td>
                                        <td><?php echo number_format($row['capacity'], 2); ?></td>
                                        <td><?php echo number_format($row['current_level'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $percentage = ($row['current_level'] / $row['capacity']) * 100;
                                            $badge_class = 'success';
                                            if ($percentage < 20) $badge_class = 'danger';
                                            elseif ($percentage < 50) $badge_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?> text-white">
                                                <?php echo number_format($percentage, 1); ?>%
                                            </span>
                                        </td>
                                        <td><?php echo number_format($row['current_level'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] === 'active' ? 'success' : 'secondary'; ?> text-white">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($row['last_updated'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
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
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    function refreshPage() {
        location.reload();
    }

    function exportReport() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.open('pages/export_fuel_report.php?' + params.toString(), '_blank');
    }

    function exportTransactions() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        params.set('type', 'transactions');
        window.open('pages/export_fuel_report.php?' + params.toString(), '_blank');
    }

    function exportInventory() {
        window.open('pages/export_fuel_report.php?export=1&type=inventory', '_blank');
    }

    function printReport() {
        window.print();
    }

    function resetFilters() {
        window.location.href = 'reports.php';
    }
    </script>

    <style>
    @media print {
        .btn, .page-header, .filter-card {
            display: none !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
        
        .table {
            font-size: 12px;
        }
    }
    </style>
</body>
</html>
