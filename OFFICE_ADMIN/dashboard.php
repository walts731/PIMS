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

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/logger.php';

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'office_dashboard', 'Office admin accessed dashboard');

// Get office-specific statistics
$stats = [];
$user_office_id = $_SESSION['office_id'] ?? null;

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // ===== OFFICE-SPECIFIC ASSETS =====
        if ($user_office_id) {
            // Check if status column exists
            $check_item_status = $conn->query("SHOW COLUMNS FROM asset_items LIKE 'status'");
            $item_has_status = $check_item_status && $check_item_status->num_rows > 0;
            
            // Office asset items - Enhanced with comprehensive status breakdown
            $office_assets_query = "SELECT 
                COUNT(*) as total_office_items" .
                ($item_has_status ? ",
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
                SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items,
                SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_assets,
                SUM(CASE WHEN status = 'no_tag' THEN 1 ELSE 0 END) as no_tag_assets,
                SUM(CASE WHEN status = 'pending_tag' THEN 1 ELSE 0 END) as pending_tag_assets,
                SUM(CASE WHEN status = 'red_tagged' THEN 1 ELSE 0 END) as red_tagged_assets" : ",
                0 as available_assets,
                0 as in_use_assets,
                0 as serviceable_items,
                0 as maintenance_assets,
                0 as unserviceable_items,
                0 as disposed_assets,
                0 as no_tag_assets,
                0 as pending_tag_assets,
                0 as red_tagged_assets") . ",
                COALESCE(SUM(value), 0) as total_office_value
                FROM asset_items 
                WHERE office_id = ?";
            $stmt = $conn->prepare($office_assets_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $office_assets_result = $stmt->get_result();
            if ($office_assets_result) {
                $office_asset_data = $office_assets_result->fetch_assoc();
                $stats = array_merge($stats, $office_asset_data);
            }
            
            // ===== OFFICE CONSUMABLES =====
            $consumables_query = "SELECT 
                COUNT(*) as office_consumables_count,
                SUM(quantity) as total_consumable_quantity,
                SUM(quantity * unit_cost) as total_consumable_value,
                SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items
                FROM consumables 
                WHERE office_id = ?";
            $stmt = $conn->prepare($consumables_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $consumables_result = $stmt->get_result();
            if ($consumables_result) {
                $stats = array_merge($stats, $consumables_result->fetch_assoc());
            }
            
            // ===== PENDING REQUESTS =====
            try {
                $requests_query = "SELECT 
                    COUNT(*) as pending_requests,
                    SUM(CASE WHEN request_type = 'consumable' THEN 1 ELSE 0 END) as consumable_requests,
                    SUM(CASE WHEN request_type = 'asset' THEN 1 ELSE 0 END) as asset_requests
                    FROM requests 
                    WHERE office_id = ? AND status = 'pending'";
                $stmt = $conn->prepare($requests_query);
                $stmt->bind_param("i", $user_office_id);
                $stmt->execute();
                $requests_result = $stmt->get_result();
                if ($requests_result) {
                    $stats = array_merge($stats, $requests_result->fetch_assoc());
                }
            } catch (Exception $e) {
                // Table doesn't exist, set defaults
                $stats['pending_requests'] = 0;
                $stats['consumable_requests'] = 0;
                $stats['asset_requests'] = 0;
            }
            
            // ===== BORROW REQUESTS =====
            try {
                // Incoming requests (other offices requesting from this office)
                $incoming_requests_query = "SELECT 
                    COUNT(*) as total_incoming_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_incoming_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_incoming_requests,
                    SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_incoming_requests,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_incoming_requests
                    FROM borrow_requests 
                    WHERE requested_to_office = ?";
                $stmt = $conn->prepare($incoming_requests_query);
                $stmt->bind_param("i", $user_office_id);
                $stmt->execute();
                $incoming_result = $stmt->get_result();
                if ($incoming_result) {
                    $incoming_stats = $incoming_result->fetch_assoc();
                    $stats = array_merge($stats, $incoming_stats);
                }
                
                // Outgoing requests (this office requesting from other offices)
                $outgoing_requests_query = "SELECT 
                    COUNT(*) as total_outgoing_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_outgoing_requests,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_outgoing_requests,
                    SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_outgoing_requests,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_outgoing_requests
                    FROM borrow_requests 
                    WHERE requested_by_office = ?";
                $stmt = $conn->prepare($outgoing_requests_query);
                $stmt->bind_param("i", $user_office_id);
                $stmt->execute();
                $outgoing_result = $stmt->get_result();
                if ($outgoing_result) {
                    $outgoing_stats = $outgoing_result->fetch_assoc();
                    $stats = array_merge($stats, $outgoing_stats);
                }
            } catch (Exception $e) {
                // Table doesn't exist, set defaults
                $stats['total_incoming_requests'] = 0;
                $stats['pending_incoming_requests'] = 0;
                $stats['approved_incoming_requests'] = 0;
                $stats['denied_incoming_requests'] = 0;
                $stats['returned_incoming_requests'] = 0;
                $stats['total_outgoing_requests'] = 0;
                $stats['pending_outgoing_requests'] = 0;
                $stats['approved_outgoing_requests'] = 0;
                $stats['denied_outgoing_requests'] = 0;
                $stats['returned_outgoing_requests'] = 0;
            }
            
            // ===== LOW STOCK ITEMS =====
            $low_stock_query = "SELECT 
                id, description, quantity, reorder_level, unit_cost
                FROM consumables 
                WHERE office_id = ? AND quantity <= reorder_level
                ORDER BY quantity ASC 
                LIMIT 5";
            $stmt = $conn->prepare($low_stock_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $low_stock_result = $stmt->get_result();
            $stats['low_stock_details'] = [];
            if ($low_stock_result) {
                while ($row = $low_stock_result->fetch_assoc()) {
                    $stats['low_stock_details'][] = $row;
                }
            }
        }
        
    } catch (Exception $e) {
        $stats['error'] = "Error fetching office stats: " . $e->getMessage();
        error_log("Office Dashboard Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'total_office_items' => 0, 'serviceable_items' => 0, 'unserviceable_items' => 0,
    'total_office_value' => 0, 'office_consumables_count' => 0, 'total_consumable_quantity' => 0,
    'total_consumable_value' => 0, 'low_stock_items' => 0, 'pending_requests' => 0,
    'consumable_requests' => 0, 'asset_requests' => 0, 'total_forms' => 0,
    'ics_forms' => 0, 'ris_forms' => 0,
    'total_incoming_requests' => 0, 'pending_incoming_requests' => 0, 'approved_incoming_requests' => 0,
    'denied_incoming_requests' => 0, 'returned_incoming_requests' => 0,
    'total_outgoing_requests' => 0, 'pending_outgoing_requests' => 0, 'approved_outgoing_requests' => 0,
    'denied_outgoing_requests' => 0, 'returned_outgoing_requests' => 0,
    'low_stock_details' => []
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
    <title>Office Admin Dashboard - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chart.js datalabels plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
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
            border-left: 4px solid #5CC2F2;
        }
        
        .office-metric-card {
            background: linear-gradient(135deg, #5CC2F2 0%, #C1EAF2 100%);
            color: var(--dark-color);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(92, 194, 242, 0.3);
        }
        
        .office-metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(92, 194, 242, 0.3);
        }
        
        .office-metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #191BA9;
        }
        
        .office-metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
            background: rgba(255, 255, 255, 0.35);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 10px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .chart-card h6 {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #191BA9;
            font-weight: 600;
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid #5CC2F2;
            margin-bottom: 0.5rem;
            background: rgba(92, 194, 242, 0.05);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: rgba(92, 194, 242, 0.1);
            transform: translateX(3px);
        }
        
        .alert-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        
        .alert-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .quick-action-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .quick-action-card:hover {
            border-color: #5CC2F2;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(92, 194, 242, 0.2);
            color: inherit;
            text-decoration: none;
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            color: #5CC2F2;
            margin-bottom: 1rem;
        }
        
        .quick-action-title {
            font-weight: 600;
            color: #191BA9;
            margin-bottom: 0.5rem;
        }
        
        .quick-action-desc {
            font-size: 0.875rem;
            color: #666;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .office-metric-number {
                font-size: 2rem;
            }
        }
        
        .quick-actions-toolbar {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--accent-color);
        }
        
        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: var(--border-radius);
            border: 2px solid transparent;
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            background: white;
            color: var(--dark-color);
        }
        
        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .quick-action-btn.urgent {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-color: #dc3545;
        }
        
        .quick-action-btn.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1517a6 100%);
            color: white;
            border-color: var(--primary-color);
        }
        
        .quick-action-btn.info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-color: #17a2b8;
        }
        
        .quick-action-btn.success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-color: #28a745;
        }
        
        .quick-action-btn.secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
            border-color: #6c757d;
        }
        
        .quick-action-btn.outline {
            background: transparent;
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .quick-action-btn.outline:hover {
            background: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .quick-actions-toolbar .d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .quick-action-btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Office Dashboard';
?>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <?php require_once 'includes/notification_js.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="bi bi-building"></i> Office Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Manage your office assets, consumables, and requests</p>
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
        
        <!-- Office Overview Cards -->
        <div class="row mb-4 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['total_office_items']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-box-seam"></i> Office Assets</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['office_consumables_count']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-archive"></i> Consumables</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['pending_requests']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-send"></i> Pending Requests</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Toolbar -->
        <div class="row mb-4 justify-content-center">
            <div class="col-12">
                <div class="quick-actions-toolbar">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Quick Actions</h5>
                        <div class="toolbar-actions">
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($stats['pending_requests'] > 0): ?>
                            <button class="quick-action-btn urgent" onclick="window.location.href='requests.php'">
                                <i class="bi bi-exclamation-circle"></i>
                                <span>Approve <?php echo $stats['pending_requests']; ?> Requests</span>
                            </button>
                        <?php endif; ?>
                        
                        <button class="quick-action-btn primary" onclick="window.location.href='requests.php?action=new'">
                            <i class="bi bi-plus-circle"></i>
                            <span>New Request</span>
                        </button>
                        
                        <button class="quick-action-btn info" onclick="window.location.href='office_assets.php'">
                            <i class="bi bi-box-seam"></i>
                            <span>Browse Assets</span>
                        </button>
                        
                        <button class="quick-action-btn secondary" onclick="window.location.href='office_consumables.php'">
                            <i class="bi bi-archive"></i>
                            <span>Consumables</span>
                        </button>
                        
                        <button class="quick-action-btn success" onclick="window.location.href='office_reports.php'">
                            <i class="bi bi-graph-up"></i>
                            <span>Reports</span>
                        </button>
                        
                        <button class="quick-action-btn outline" onclick="window.location.href='notifications.php'">
                            <i class="bi bi-bell"></i>
                            <span>Notifications</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Asset Status Chart -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Asset Status</h6>
                    <div class="chart-container">
                        <canvas id="assetStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Consumable Usage Trend -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-graph-up"></i> Consumable Usage (7 Days)</h6>
                    <div class="chart-container">
                        <canvas id="consumableUsageChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Request Status Overview -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-send"></i> Request Status</h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="requestType" id="outgoingRequests" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm" for="outgoingRequests">Outgoing</label>
                            <input type="radio" class="btn-check" name="requestType" id="incomingRequests" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="incomingRequests">Incoming</label>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alerts and Activity Row -->
        <div class="row">
            <!-- Low Stock Alerts -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h6>
                    <?php if (!empty($stats['low_stock_details'])): ?>
                        <?php foreach ($stats['low_stock_details'] as $item): ?>
                            <div class="alert-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['description']); ?></strong>
                                        <div class="small">
                                            Stock: <?php echo $item['quantity']; ?> / Reorder: <?php echo $item['reorder_level']; ?>
                                        </div>
                                    </div>
                                    <i class="bi bi-arrow-up-circle"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                            <div class="mt-2">All consumables are well stocked</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard Charts
document.addEventListener('DOMContentLoaded', function() {
    // Asset Status Chart - Enhanced with comprehensive status breakdown
    const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
    new Chart(assetStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Available', 'In Use', 'Serviceable', 'Maintenance', 'Unserviceable', 'Disposed', 'No Tag', 'Pending Tag', 'Red Tagged'],
            datasets: [{
                data: [
                    <?php echo $stats['available_assets']; ?>,
                    <?php echo $stats['in_use_assets']; ?>,
                    <?php echo $stats['serviceable_items']; ?>,
                    <?php echo $stats['maintenance_assets']; ?>,
                    <?php echo $stats['unserviceable_items']; ?>,
                    <?php echo $stats['disposed_assets']; ?>,
                    <?php echo $stats['no_tag_assets']; ?>,
                    <?php echo $stats['pending_tag_assets']; ?>,
                    <?php echo $stats['red_tagged_assets']; ?>
                ],
                backgroundColor: [
                    '#28a745', // Available - Green
                    '#17a2b8', // In Use - Blue
                    '#20c997', // Serviceable - Teal
                    '#ffc107', // Maintenance - Yellow
                    '#fd7e14', // Unserviceable - Orange
                    '#dc3545', // Disposed - Red
                    '#6c757d', // No Tag - Gray
                    '#e83e8c', // Pending Tag - Pink
                    '#6f42c1'  // Red Tagged - Purple
                ],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '60%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1000,
                easing: 'easeInOutQuart'
            }
        }
    });
    
    // Consumable Usage Chart
    const consumableUsageCtx = document.getElementById('consumableUsageChart').getContext('2d');
    new Chart(consumableUsageCtx, {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Usage',
                data: [12, 19, 8, 15, 22, 18, 25],
                borderColor: '#5CC2F2',
                backgroundColor: 'rgba(92, 194, 242, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    
    // Request Status Chart
    const requestStatusCtx = document.getElementById('requestStatusChart').getContext('2d');
    
    // Store both datasets
    const outgoingData = {
        labels: ['Pending', 'Approved', 'Denied', 'Returned'],
        datasets: [{
            data: [<?php echo $stats['pending_outgoing_requests']; ?>, <?php echo $stats['approved_outgoing_requests']; ?>, <?php echo $stats['denied_outgoing_requests']; ?>, <?php echo $stats['returned_outgoing_requests']; ?>],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6f42c1'],
            borderWidth: 0
        }]
    };
    
    const incomingData = {
        labels: ['Pending', 'Approved', 'Denied', 'Returned'],
        datasets: [{
            data: [<?php echo $stats['pending_incoming_requests']; ?>, <?php echo $stats['approved_incoming_requests']; ?>, <?php echo $stats['denied_incoming_requests']; ?>, <?php echo $stats['returned_incoming_requests']; ?>],
            backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6f42c1'],
            borderWidth: 0
        }]
    };
    
    let requestStatusChart = new Chart(requestStatusCtx, {
        type: 'pie',
        data: outgoingData, // Default to outgoing
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Toggle functionality
    document.querySelectorAll('input[name="requestType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const selectedType = this.id;
            const newData = selectedType === 'outgoingRequests' ? outgoingData : incomingData;
            
            requestStatusChart.data = newData;
            requestStatusChart.update();
        });
    });
});

// Refresh Dashboard
function refreshDashboard() {
    location.reload();
}

// Export Data
function exportData() {
    window.open('export_office_data.php', '_blank');
}

</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Bootstrap-based Notification Script -->
<?php require_once 'includes/notification_script_bootstrap.php'; ?>
<!-- Sidebar Scripts -->
<script src="../assets/js/sidebar.js"></script>
</body>
</html>