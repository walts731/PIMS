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
            
            // Office asset items
            $office_assets_query = "SELECT 
                COUNT(*) as total_office_items" .
                ($item_has_status ? ",
                SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_items,
                SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_items" : ",
                0 as serviceable_items,
                0 as unserviceable_items") . ",
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
            
            // ===== OFFICE FORMS =====
            $forms_query = "SELECT 
                COUNT(*) as total_forms,
                SUM(CASE WHEN form_type = 'PAR' THEN 1 ELSE 0 END) as par_forms,
                SUM(CASE WHEN form_type = 'ICS' THEN 1 ELSE 0 END) as ics_forms,
                SUM(CASE WHEN form_type = 'RIS' THEN 1 ELSE 0 END) as ris_forms
                FROM office_forms 
                WHERE office_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $conn->prepare($forms_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $forms_result = $stmt->get_result();
            if ($forms_result) {
                $stats = array_merge($stats, $forms_result->fetch_assoc());
            }
            
            // ===== RECENT ACTIVITY =====
            $recent_activity_query = "SELECT 
                activity_type, description, created_at
                FROM office_activity_log 
                WHERE office_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5";
            $stmt = $conn->prepare($recent_activity_query);
            $stmt->bind_param("i", $user_office_id);
            $stmt->execute();
            $recent_result = $stmt->get_result();
            $stats['recent_activity'] = [];
            if ($recent_result) {
                while ($row = $recent_result->fetch_assoc()) {
                    $stats['recent_activity'][] = $row;
                }
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
    'par_forms' => 0, 'ics_forms' => 0, 'ris_forms' => 0,
    'recent_activity' => [], 'low_stock_details' => []
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
    <link href="dashboard.css" rel="stylesheet">
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
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['total_office_items']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-box-seam"></i> Office Assets</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['office_consumables_count']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-archive"></i> Consumables</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number"><?php echo $stats['pending_requests']; ?></div>
                    <div class="office-metric-label"><i class="bi bi-send"></i> Pending Requests</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="office-metric-card">
                    <div class="office-metric-number">₱<?php echo number_format($stats['total_office_value'], 0); ?></div>
                    <div class="office-metric-label"><i class="bi bi-currency-dollar"></i> Asset Value</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3">Quick Actions</h5>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="request_consumable.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-archive"></i>
                    </div>
                    <div class="quick-action-title">Request Consumables</div>
                    <div class="quick-action-desc">Submit new consumable request</div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="create_par.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="quick-action-title">Create PAR Form</div>
                    <div class="quick-action-desc">Property Acknowledgment Receipt</div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="office_assets.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="quick-action-title">View Assets</div>
                    <div class="quick-action-desc">Browse office assets</div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="office_reports.php" class="quick-action-card">
                    <div class="quick-action-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="quick-action-title">Generate Report</div>
                    <div class="quick-action-desc">Office inventory reports</div>
                </a>
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
                    <h6 class="mb-3"><i class="bi bi-send"></i> Request Status</h6>
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
            
            <!-- Recent Activity -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> Recent Activity</h6>
                    <div class="activity-feed">
                        <?php if (!empty($stats['recent_activity'])): ?>
                            <?php foreach ($stats['recent_activity'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        </div>
                                        <small class="text-muted"><?php echo date('M j, H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-clock" style="font-size: 2rem;"></i>
                                <div class="mt-2">No recent activity</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard Charts
document.addEventListener('DOMContentLoaded', function() {
    // Asset Status Chart
    const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
    new Chart(assetStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Serviceable', 'Unserviceable'],
            datasets: [{
                data: [<?php echo $stats['serviceable_items']; ?>, <?php echo $stats['unserviceable_items']; ?>],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0
            }]
        },
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
    new Chart(requestStatusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [<?php echo $stats['pending_requests']; ?>, 8, 2],
                backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                borderWidth: 0
            }]
        },
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
<!-- Sidebar Scripts -->
<script src="../assets/js/sidebar.js"></script>
</body>
</html>