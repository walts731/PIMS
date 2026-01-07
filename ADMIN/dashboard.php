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

// Check if user has correct role (admin or system_admin)
if (!in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'admin_dashboard', 'Admin accessed dashboard');

// Get dashboard statistics
$stats = [];

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Assets Summary
        $assets_query = "SELECT 
            COUNT(*) as total_assets,
            SUM(quantity) as total_quantity,
            SUM(quantity * unit_cost) as total_value,
            COUNT(DISTINCT office_id) as offices_with_assets
            FROM assets";
        $assets_result = $conn->query($assets_query);
        if ($assets_result) {
            $asset_stats = $assets_result->fetch_assoc();
            $stats['total_assets'] = $asset_stats['total_assets'];
            $stats['total_quantity'] = $asset_stats['total_quantity'];
            $stats['total_value'] = $asset_stats['total_value'] ?? 0;
            $stats['offices_with_assets'] = $asset_stats['offices_with_assets'];
        }

        // Asset Items Summary
        $asset_items_query = "SELECT 
            COUNT(*) as total_items,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_items,
            SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_items,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_items,
            SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_items,
            SUM(value) as total_item_value
            FROM asset_items";
        $asset_items_result = $conn->query($asset_items_query);
        if ($asset_items_result) {
            $item_stats = $asset_items_result->fetch_assoc();
            $stats['total_items'] = $item_stats['total_items'];
            $stats['available_items'] = $item_stats['available_items'];
            $stats['in_use_items'] = $item_stats['in_use_items'];
            $stats['maintenance_items'] = $item_stats['maintenance_items'];
            $stats['disposed_items'] = $item_stats['disposed_items'];
            $stats['total_item_value'] = $item_stats['total_item_value'] ?? 0;
        }

        // Asset Categories Summary
        $categories_query = "SELECT 
            ac.category_code as code,
            ac.category_name as name,
            COUNT(a.id) as asset_count,
            SUM(a.quantity) as total_quantity,
            SUM(a.quantity * a.unit_cost) as total_value
            FROM asset_categories ac
            LEFT JOIN assets a ON ac.id = a.asset_categories_id
            GROUP BY ac.id, ac.category_code, ac.category_name
            ORDER BY total_value DESC";
        $categories_result = $conn->query($categories_query);
        $stats['categories'] = [];
        if ($categories_result) {
            while ($row = $categories_result->fetch_assoc()) {
                $stats['categories'][] = $row;
            }
        }

        // Consumables Summary
        $consumables_query = "SELECT 
            COUNT(*) as total_consumables,
            SUM(quantity) as total_quantity,
            SUM(quantity * unit_cost) as total_value,
            COUNT(DISTINCT office_id) as offices_with_consumables
            FROM consumables";
        $consumables_result = $conn->query($consumables_query);
        if ($consumables_result) {
            $consumable_stats = $consumables_result->fetch_assoc();
            $stats['total_consumables'] = $consumable_stats['total_consumables'];
            $stats['total_consumable_quantity'] = $consumable_stats['total_quantity'];
            $stats['total_consumable_value'] = $consumable_stats['total_value'] ?? 0;
            $stats['offices_with_consumables'] = $consumable_stats['offices_with_consumables'];
        }

        // Office Distribution
        $office_query = "SELECT 
            o.office_name,
            COUNT(a.id) as asset_count,
            COUNT(ai.id) as item_count,
            SUM(a.quantity * a.unit_cost) as asset_value,
            SUM(ai.value) as item_value
            FROM offices o
            LEFT JOIN assets a ON o.id = a.office_id
            LEFT JOIN asset_items ai ON o.id = ai.office_id
            GROUP BY o.id, o.office_name
            ORDER BY asset_value DESC
            LIMIT 10";
        $office_result = $conn->query($office_query);
        $stats['office_distribution'] = [];
        if ($office_result) {
            while ($row = $office_result->fetch_assoc()) {
                $stats['office_distribution'][] = $row;
            }
        }

        // Recent Asset Items
        $recent_items_query = "SELECT 
            ai.id, ai.description, ai.status, ai.acquisition_date,
            a.description as asset_description,
            o.office_name,
            CONCAT(e.firstname, ' ', e.lastname) as employee_name
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON ai.office_id = o.id
            LEFT JOIN employees e ON ai.employee_id = e.id
            ORDER BY ai.last_updated DESC
            LIMIT 10";
        $recent_items_result = $conn->query($recent_items_query);
        $stats['recent_items'] = [];
        if ($recent_items_result) {
            while ($row = $recent_items_result->fetch_assoc()) {
                $stats['recent_items'][] = $row;
            }
        }

        // Low Stock Alerts (for consumables)
        $low_stock_query = "SELECT 
            c.id, c.description, c.quantity, c.reorder_level,
            o.office_name
            FROM consumables c
            LEFT JOIN offices o ON c.office_id = o.id
            WHERE c.quantity <= c.reorder_level
            ORDER BY c.quantity ASC
            LIMIT 5";
        $low_stock_result = $conn->query($low_stock_query);
        $stats['low_stock_alerts'] = [];
        if ($low_stock_result) {
            while ($row = $low_stock_result->fetch_assoc()) {
                $stats['low_stock_alerts'][] = $row;
            }
        }

        // Maintenance Items
        $maintenance_query = "SELECT 
            ai.id, ai.description, ai.status, ai.last_updated,
            a.description as asset_description,
            o.office_name
            FROM asset_items ai
            LEFT JOIN assets a ON ai.asset_id = a.id
            LEFT JOIN offices o ON ai.office_id = o.id
            WHERE ai.status = 'maintenance'
            ORDER BY ai.last_updated DESC
            LIMIT 5";
        $maintenance_result = $conn->query($maintenance_query);
        $stats['maintenance_items'] = [];
        if ($maintenance_result) {
            while ($row = $maintenance_result->fetch_assoc()) {
                $stats['maintenance_items'][] = $row;
            }
        }

        // System Information
        $stats['system_info'] = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $conn->server_info ?? 'MySQL',
            'system_time' => date('Y-m-d H:i:s'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'database_status' => 'Connected'
        ];

    } catch (Exception $e) {
        $stats['error'] = "Error fetching dashboard stats: " . $e->getMessage();
        error_log("Admin Dashboard Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'total_assets' => 0, 'total_quantity' => 0, 'total_value' => 0, 'offices_with_assets' => 0,
    'total_items' => 0, 'available_items' => 0, 'in_use_items' => 0, 'maintenance_items' => 0, 'disposed_items' => 0, 'total_item_value' => 0,
    'total_consumables' => 0, 'total_consumable_quantity' => 0, 'total_consumable_value' => 0, 'offices_with_consumables' => 0,
    'categories' => [], 'office_distribution' => [], 'recent_items' => [], 'low_stock_alerts' => [], 'maintenance_items' => []
];

foreach ($defaults as $key => $value) {
    if (!isset($stats[$key])) {
        $stats[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .metric-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .chart-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .status-available {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-in_use {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .status-maintenance {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
        }
        
        .status-disposed {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid #191BA9;
            margin-bottom: 0.5rem;
            background: rgba(25, 27, 169, 0.05);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: rgba(25, 27, 169, 0.1);
            transform: translateX(3px);
        }
        
        .alert-card {
            background: white;
            border-left: 4px solid #ffc107;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 27, 169, 0.05);
        }
        
        .text-value {
            font-weight: 600;
            color: #191BA9;
        }
    </style>
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Admin Dashboard';
    ?>
    <!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-speedometer2"></i> Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Assets and consumables management overview</p>
                    <?php if (isset($stats['error'])): ?>
                        <div class="alert alert-warning mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Database Warning:</strong> <?php echo htmlspecialchars($stats['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success btn-sm ms-2" onclick="exportData()">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Assets Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['total_assets']; ?></div>
                    <div class="metric-label"><i class="bi bi-box"></i> Total Assets</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['total_items']; ?></div>
                    <div class="metric-label"><i class="bi bi-collection"></i> Asset Items</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo number_format($stats['total_value'] + $stats['total_item_value'], 2); ?></div>
                    <div class="metric-label"><i class="bi bi-currency-dollar"></i> Total Value</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number"><?php echo $stats['offices_with_assets']; ?></div>
                    <div class="metric-label"><i class="bi bi-building"></i> Offices</div>
                </div>
            </div>
        </div>
        
        <!-- Consumables Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="metric-number"><?php echo $stats['total_consumables']; ?></div>
                    <div class="metric-label"><i class="bi bi-archive"></i> Consumables</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="metric-number"><?php echo $stats['total_consumable_quantity']; ?></div>
                    <div class="metric-label"><i class="bi bi-stack"></i> Total Quantity</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="metric-number"><?php echo number_format($stats['total_consumable_value'], 2); ?></div>
                    <div class="metric-label"><i class="bi bi-currency-dollar"></i> Consumable Value</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div class="metric-number"><?php echo count($stats['low_stock_alerts']); ?></div>
                    <div class="metric-label"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Asset Status Distribution -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Asset Items Status</h6>
                    <div class="chart-container">
                        <canvas id="assetStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Asset Categories Value -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-bar-chart"></i> Categories by Value</h6>
                    <div class="chart-container">
                        <canvas id="categoryValueChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Office Distribution -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-building"></i> Office Distribution</h6>
                    <div class="chart-container">
                        <canvas id="officeDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Information Rows -->
        <div class="row mb-4">
            <!-- Recent Asset Items -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> Recent Asset Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Office</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['recent_items'])): ?>
                                    <?php foreach ($stats['recent_items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($item['description']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['asset_description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <small><?php echo date('M j, H:i', strtotime($item['last_updated'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No recent items found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alerts -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h6>
                    <?php if (!empty($stats['low_stock_alerts'])): ?>
                        <?php foreach ($stats['low_stock_alerts'] as $alert): ?>
                            <div class="alert-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($alert['description']); ?></strong>
                                        <div class="small text-muted">
                                            <?php echo htmlspecialchars($alert['office_name'] ?? 'Main Office'); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-value"><?php echo $alert['quantity']; ?></div>
                                        <small class="text-muted">of <?php echo $alert['reorder_level']; ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mt-2">No low stock alerts</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Items -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-tools"></i> Items Under Maintenance</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Asset</th>
                                    <th>Office</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['maintenance_items'])): ?>
                                    <?php foreach ($stats['maintenance_items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($item['description']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['asset_description'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($item['office_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <small><?php echo date('M j, H:i', strtotime($item['last_updated'])); ?></small>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewItem(<?php echo $item['id']; ?>)">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No items under maintenance</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Asset Categories Table -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-tags"></i> Asset Categories Overview</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Category Name</th>
                                    <th>Asset Count</th>
                                    <th>Total Quantity</th>
                                    <th>Total Value</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['categories'])): ?>
                                    <?php foreach ($stats['categories'] as $category): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?php echo htmlspecialchars($category['code']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo $category['asset_count']; ?></td>
                                            <td><?php echo $category['total_quantity']; ?></td>
                                            <td class="text-value"><?php echo number_format($category['total_value'], 2); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewCategory('<?php echo $category['code']; ?>')">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No categories found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        // Dashboard functions
        function refreshDashboard() {
            location.reload();
        }
        
        function exportData() {
            // Create CSV export of dashboard data
            const data = {
                timestamp: new Date().toISOString(),
                assets: {
                    total: <?php echo $stats['total_assets']; ?>,
                    items: <?php echo $stats['total_items']; ?>,
                    available: <?php echo $stats['available_items']; ?>,
                    in_use: <?php echo $stats['in_use_items']; ?>,
                    maintenance: <?php echo $stats['maintenance_items']; ?>,
                    disposed: <?php echo $stats['disposed_items']; ?>,
                    total_value: <?php echo $stats['total_value'] + $stats['total_item_value']; ?>
                },
                consumables: {
                    total: <?php echo $stats['total_consumables']; ?>,
                    quantity: <?php echo $stats['total_consumable_quantity']; ?>,
                    value: <?php echo $stats['total_consumable_value']; ?>,
                    low_stock_alerts: <?php echo count($stats['low_stock_alerts']); ?>
                }
            };
            
            // Convert to CSV and download
            let csv = 'Metric,Value,Details\n';
            csv += `Total Assets,${data.assets.total},Items: ${data.assets.items}\n`;
            csv += `Available Items,${data.assets.available},\n`;
            csv += `In Use Items,${data.assets.in_use},\n`;
            csv += `Maintenance Items,${data.assets.maintenance},\n`;
            csv += `Disposed Items,${data.assets.disposed},\n`;
            csv += `Total Asset Value,${data.assets.total_value},PHP\n`;
            csv += `Total Consumables,${data.consumables.total},Quantity: ${data.consumables.quantity}\n`;
            csv += `Consumable Value,${data.consumables.value},PHP\n`;
            csv += `Low Stock Alerts,${data.consumables.low_stock_alerts},\n`;
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `admin_dashboard_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        function viewItem(itemId) {
            window.location.href = `asset_items.php?action=view&id=${itemId}`;
        }
        
        function viewCategory(categoryCode) {
            // Redirect to category-specific page or show category details
            window.location.href = `asset_categories.php?code=${categoryCode}`;
        }
        
        // Initialize all charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js global configuration
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#666';
            
            // Asset Status Distribution Pie Chart
            const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
            new Chart(assetStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'In Use', 'Maintenance', 'Disposed'],
                    datasets: [{
                        data: [
                            <?php echo $stats['available_items']; ?>,
                            <?php echo $stats['in_use_items']; ?>,
                            <?php echo $stats['maintenance_items']; ?>,
                            <?php echo $stats['disposed_items']; ?>
                        ],
                        backgroundColor: [
                            '#28a745', // available - green
                            '#191BA9', // in_use - blue
                            '#ffc107', // maintenance - yellow
                            '#dc3545'  // disposed - red
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Categories Value Bar Chart
            const categoryValueCtx = document.getElementById('categoryValueChart').getContext('2d');
            const categoryData = <?php echo json_encode(array_slice($stats['categories'], 0, 5)); ?>;
            new Chart(categoryValueCtx, {
                type: 'bar',
                data: {
                    labels: categoryData.map(c => c.name),
                    datasets: [{
                        label: 'Total Value',
                        data: categoryData.map(c => c.total_value),
                        backgroundColor: '#191BA9',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Value (PHP)'
                            }
                        }
                    }
                }
            });
            
            // Office Distribution Chart
            const officeDistCtx = document.getElementById('officeDistributionChart').getContext('2d');
            const officeData = <?php echo json_encode(array_slice($stats['office_distribution'], 0, 5)); ?>;
            new Chart(officeDistCtx, {
                type: 'bar',
                data: {
                    labels: officeData.map(o => o.office_name),
                    datasets: [{
                        label: 'Asset Value',
                        data: officeData.map(o => o.asset_value),
                        backgroundColor: '#5CC2F2',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Value (PHP)'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>