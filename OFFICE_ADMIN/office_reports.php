<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';
require_once 'includes/lgu_compliance_functions.php';

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

// Set page title for topbar
$page_title = 'Office Reports';

// Initialize LGU Compliance
$office_id = $_SESSION['office_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$lgu_compliance = new LGUCompliance($office_id, $user_id);

// Initialize report data with LGU compliance features
$report_data = [
    'request_stats' => [
        'total_requests' => 0,
        'pending_requests' => 0,
        'approved_requests' => 0,
        'denied_requests' => 0,
        'completed_requests' => 0
    ],
    'consumable_stats' => [
        'total_consumables' => 0,
        'low_stock_items' => 0,
        'out_of_stock' => 0,
        'total_value' => 0,
        'monthly_usage' => 0
    ],
    'asset_stats' => [
        'total_assets' => 0,
        'available_assets' => 0,
        'in_maintenance' => 0,
        'disposed_assets' => 0,
        'total_asset_value' => 0
    ],
    'recent_activities' => [],
    'monthly_data' => [],
    'top_consumables' => [],
    'request_trends' => [],
    'data_integrity_issues' => [],
    'document_references' => [],
    'signatories' => [],
    'fiscal_year' => [],
    'report_history' => []
];

if ($office_id && $conn) {
    try {
        // Request Statistics - Enhanced with comprehensive status breakdown
        $request_query = "SELECT 
                            COUNT(*) as total_requests,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
                            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_requests,
                            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned_requests,
                            SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed_requests,
                            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_requests
                         FROM borrow_requests 
                         WHERE (requested_to_office = ? OR requested_by_office = ?) 
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $conn->prepare($request_query);
        $stmt->bind_param("ii", $office_id, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $report_data['request_stats'] = $result->fetch_assoc();
        }
        
        // Consumable Statistics
        $consumable_query = "SELECT 
                               COUNT(*) as total_consumables,
                               SUM(CASE WHEN quantity <= reorder_level AND quantity > 0 THEN 1 ELSE 0 END) as low_stock_items,
                               SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                               SUM(quantity * unit_cost) as total_value
                            FROM consumables 
                            WHERE office_id = ?";
        $stmt = $conn->prepare($consumable_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $consumable_data = $result->fetch_assoc();
            $report_data['consumable_stats'] = array_merge($report_data['consumable_stats'], $consumable_data);
        }
        
        // Monthly Consumable Usage
        $usage_query = "SELECT SUM(quantity_released) as monthly_usage 
                       FROM consumable_release_history 
                       WHERE to_office_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $conn->prepare($usage_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $usage_data = $result->fetch_assoc();
            $report_data['consumable_stats']['monthly_usage'] = $usage_data['monthly_usage'] ?? 0;
        }
        
        // Asset Statistics - Enhanced with detailed status breakdown
        $asset_query = "SELECT 
                          COUNT(*) as total_assets,
                          SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_assets,
                          SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) as in_use_assets,
                          SUM(CASE WHEN status = 'serviceable' THEN 1 ELSE 0 END) as serviceable_assets,
                          SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_assets,
                          SUM(CASE WHEN status = 'unserviceable' THEN 1 ELSE 0 END) as unserviceable_assets,
                          SUM(CASE WHEN status = 'disposed' THEN 1 ELSE 0 END) as disposed_assets,
                          SUM(CASE WHEN status = 'no_tag' THEN 1 ELSE 0 END) as no_tag_assets,
                          SUM(CASE WHEN status = 'pending_tag' THEN 1 ELSE 0 END) as pending_tag_assets,
                          SUM(CASE WHEN status = 'red_tagged' THEN 1 ELSE 0 END) as red_tagged_assets,
                          SUM(COALESCE(value, 0)) as total_asset_value
                       FROM asset_items 
                       WHERE office_id = ?";
        $stmt = $conn->prepare($asset_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $asset_data = $result->fetch_assoc();
            $report_data['asset_stats'] = array_merge($report_data['asset_stats'], $asset_data);
        }
        
        // Recent Activities
        $activity_query = "(SELECT 
                            'Borrow Request' COLLATE utf8mb4_unicode_ci as activity_type,
                            CONCAT('Request for asset #', asset_id, ' - ', status) COLLATE utf8mb4_unicode_ci as description,
                            created_at
                          FROM borrow_requests 
                          WHERE requested_to_office = ? OR requested_by_office = ?
                          ORDER BY created_at DESC 
                          LIMIT 5)
                          UNION
                          (SELECT 
                            'Consumable Release' COLLATE utf8mb4_unicode_ci as activity_type,
                            CONCAT('Released ', quantity_released, ' units of ', description) COLLATE utf8mb4_unicode_ci as description,
                            created_at
                          FROM consumable_release_history 
                          WHERE to_office_id = ? OR from_office_id = ?
                          ORDER BY created_at DESC 
                          LIMIT 5)
                          ORDER BY created_at DESC 
                          LIMIT 10";
        $stmt = $conn->prepare($activity_query);
        $stmt->bind_param("iiii", $office_id, $office_id, $office_id, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data['recent_activities'][] = $row;
        }
        
        // Monthly Data for Charts (Last 6 months) - Enhanced with both incoming and outgoing requests
        $monthly_query = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as total_requests,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_count,
                            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as completed_count
                         FROM borrow_requests 
                         WHERE (requested_to_office = ? OR requested_by_office = ?) 
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                         ORDER BY month";
        $stmt = $conn->prepare($monthly_query);
        $stmt->bind_param("ii", $office_id, $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data['monthly_data'][] = $row;
        }
        
        // Top Consumables by Usage
        $top_consumables_query = "SELECT 
                                    ch.consumable_description,
                                    SUM(ch.quantity_consumed) as total_used,
                                    c.unit
                                 FROM consume_history ch
                                 LEFT JOIN consumables c ON ch.consumable_id = c.id
                                 WHERE ch.office_id = ?
                                 GROUP BY ch.consumable_id, ch.consumable_description, c.unit
                                 ORDER BY total_used DESC
                                 LIMIT 5";
        $stmt = $conn->prepare($top_consumables_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report_data['top_consumables'][] = $row;
        }
        
        // Request Trends (Last 7 days)
        $trends_query = "SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as requests_count,
                            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
                         FROM borrow_requests 
                         WHERE requested_by_office = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                         GROUP BY DATE(created_at)
                         ORDER BY date";
        
        $stmt = $conn->prepare($trends_query);
        $stmt->bind_param("i", $office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $report_data['request_trends'][] = $row;
        }
        
        // ===== LGU COMPLIANCE FEATURES =====
        
        // Load data integrity issues
        $report_data['data_integrity_issues'] = $lgu_compliance->checkDataIntegrity();
        
        // Load document references
        $report_data['document_references'] = $lgu_compliance->getDocumentReferences();
        
        // Load authorized signatories
        $signatories_data = $lgu_compliance->getSignatories();
        $report_data['signatories'] = [];
        foreach ($signatories_data as $signatory) {
            $report_data['signatories'][$signatory['signatory_type']] = $signatory;
        }
        
        // Load fiscal year dates
        $report_data['fiscal_year'] = $lgu_compliance->getFiscalYearDates();
        
        // Load report history
        $report_data['report_history'] = $lgu_compliance->getReportHistory();
        
        // Log report access for audit trail
        $report_id = $lgu_compliance->generateReportId('dashboard');
        $lgu_compliance->logReportActivity($report_id, 'dashboard', 'viewed', [
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d')
        ]);
        
    } catch (Exception $e) {
        error_log("Error generating office reports: " . $e->getMessage());
    }
}

// Format currency values
$report_data['consumable_stats']['total_value'] = number_format($report_data['consumable_stats']['total_value'], 2);
$report_data['asset_stats']['total_asset_value'] = number_format($report_data['asset_stats']['total_asset_value'], 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Reports - PIMS</title>
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
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            margin-bottom: 1.5rem;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .chart-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #5CC2F2;
            margin-bottom: 0.75rem;
            background: rgba(92, 194, 242, 0.05);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: rgba(92, 194, 242, 0.1);
            transform: translateX(3px);
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #666;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(92, 194, 242, 0.05);
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            transition: var(--transition);
        }
        
        .top-item:hover {
            background: rgba(92, 194, 242, 0.1);
        }
        
        .top-item-name {
            font-weight: 500;
            color: #333;
        }
        
        .top-item-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .export-btn {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius-lg);
            padding: 0.5rem 1.5rem;
            transition: var(--transition);
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(25, 27, 169, 0.3);
        }
        
        .filter-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }
        
        .date-range-input {
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            transition: var(--transition);
        }
        
        .date-range-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        .alert-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .alert-high { background: #dc3545; }
        .alert-medium { background: #ffc107; }
        .alert-low { background: #28a745; }
    </style>
</head>
<body>
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
                        <i class="bi bi-graph-up"></i> Office Reports
                    </h1>
                    <p class="text-muted mb-0">Comprehensive analytics and insights for your office</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn export-btn text-white dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-download"></i> Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportLGUReport('inventory')">Inventory Report (LGU)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportLGUReport('asset')">Asset Report (LGU)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportLGUReport('consumable')">Consumable Report (LGU)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportLGUReport('borrow_request')">Borrow Request Report (LGU)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="exportAdminStyleReport('inventory')">Inventory Report (Admin Style)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportAdminStyleReport('asset')">Asset Report (Admin Style)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportAdminStyleReport('consumable')">Consumable Report (Admin Style)</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportAdminStyleReport('borrow_request')">Borrow Request Report (Admin Style)</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline-success btn-sm" onclick="showDocumentModal()">
                            <i class="bi bi-file-earmark-plus"></i> Add Reference
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="showScheduleModal()">
                            <i class="bi bi-calendar-plus"></i> Schedule
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="dateFrom" class="form-label">From Date</label>
                    <input type="date" class="form-control date-range-input" id="dateFrom" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-4">
                    <label for="dateTo" class="form-label">To Date</label>
                    <input type="date" class="form-control date-range-input" id="dateTo" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Overview Statistics -->
        <div class="row mb-4 justify-content-center">
            <!-- Request Statistics -->
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stats-number"><?php echo $report_data['request_stats']['total_requests']; ?></div>
                            <div class="stats-label">Total Requests</div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 
                                <?php echo $report_data['request_stats']['approved_requests']; ?> approved
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-send fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Consumable Statistics -->
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stats-number"><?php echo $report_data['consumable_stats']['total_consumables']; ?></div>
                            <div class="stats-label">Consumables</div>
                            <small class="text-warning">
                                <i class="bi bi-exclamation-triangle"></i> 
                                <?php echo $report_data['consumable_stats']['low_stock_items']; ?> low stock
                            </small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-archive fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Asset Statistics -->
            <div class="col-lg-4 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="stats-number"><?php echo $report_data['asset_stats']['total_assets']; ?></div>
                            <div class="stats-label">Office Assets</div>
                            <small class="text-info">
                                <i class="bi bi-check-circle"></i> 
                                <?php echo $report_data['asset_stats']['available_assets']; ?> available
                            </small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Request Status Chart -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart"></i> Request Status Distribution</h6>
                    <div class="chart-container">
                        <canvas id="requestStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Trends Chart -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-graph-up"></i> Monthly Request Trends</h6>
                    <div class="chart-container">
                        <canvas id="monthlyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Asset Status Chart -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-bar-chart"></i> Asset Status Overview</h6>
                    <div class="chart-container">
                        <canvas id="assetStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Detailed Analytics Row -->
        <div class="row mb-4">
            <!-- Top Consumables -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-trophy"></i> Top Consumables by Usage</h6>
                    <div class="chart-container">
                        <canvas id="topConsumablesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- 7-Day Request Trends -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-calendar-week"></i> 7-Day Request Activity</h6>
                    <div class="chart-container">
                        <canvas id="weeklyTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- LGU Compliance Section -->
        <div class="row mb-4">
            <!-- Data Integrity Alerts -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-shield-exclamation"></i> Data Integrity Alerts</h6>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (!empty($report_data['data_integrity_issues'])): ?>
                            <?php foreach ($report_data['data_integrity_issues'] as $issue): ?>
                                <div class="alert alert-<?php echo $issue['severity'] === 'critical' ? 'danger' : ($issue['severity'] === 'high' ? 'warning' : 'info'); ?> alert-sm mb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($issue['description']); ?></strong>
                                            <div class="small">
                                                <?php echo htmlspecialchars($issue['issue_type']); ?> - 
                                                Expected: <?php echo htmlspecialchars($issue['expected']); ?>, 
                                                Actual: <?php echo htmlspecialchars($issue['actual']); ?>
                                            </div>
                                        </div>
                                        <span class="badge bg-<?php echo $issue['severity'] === 'critical' ? 'danger' : ($issue['severity'] === 'high' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($issue['severity']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-check-circle" style="font-size: 2rem; color: #28a745;"></i>
                                <div class="mt-2">No data integrity issues detected</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Document References -->
            <div class="col-lg-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Recent Document References</h6>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php if (!empty($report_data['document_references'])): ?>
                            <?php foreach (array_slice($report_data['document_references'], 0, 5) as $doc): ?>
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                                    <div>
                                        <strong><?php echo htmlspecialchars($doc['document_type']); ?> #<?php echo htmlspecialchars($doc['document_number']); ?></strong>
                                        <div class="small text-muted">
                                            <?php echo date('M d, Y', strtotime($doc['document_date'])); ?>
                                            <?php if ($doc['reference_amount']): ?>
                                                - ₱<?php echo number_format($doc['reference_amount'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($doc['document_type']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-file-earmark" style="font-size: 2rem;"></i>
                                <div class="mt-2">No document references found</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities & Alerts -->
        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-8">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-clock-history"></i> Recent Office Activities</h6>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($report_data['recent_activities'])): ?>
                            <?php foreach ($report_data['recent_activities'] as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($activity['activity_type']); ?></strong>
                                            <div class="text-muted small mt-1"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo date('M j, H:i', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-clock" style="font-size: 3rem;"></i>
                                <div class="mt-2">No recent activities recorded</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats & Alerts -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-speedometer2"></i> Quick Stats</h6>
                    
                    <!-- Request Metrics -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Pending Requests</span>
                            <span class="badge bg-warning"><?php echo $report_data['request_stats']['pending_requests']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <?php 
                            $pending_percentage = $report_data['request_stats']['total_requests'] > 0 ? 
                                ($report_data['request_stats']['pending_requests'] / $report_data['request_stats']['total_requests']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-warning" style="width: <?php echo $pending_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Approved Requests</span>
                            <span class="badge bg-success"><?php echo $report_data['request_stats']['approved_requests']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <?php 
                            $approved_percentage = $report_data['request_stats']['total_requests'] > 0 ? 
                                ($report_data['request_stats']['approved_requests'] / $report_data['request_stats']['total_requests']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $approved_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Consumable Alerts -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Low Stock Items</span>
                            <span class="badge bg-danger"><?php echo $report_data['consumable_stats']['low_stock_items']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <?php 
                            $lowstock_percentage = $report_data['consumable_stats']['total_consumables'] > 0 ? 
                                ($report_data['consumable_stats']['low_stock_items'] / $report_data['consumable_stats']['total_consumables']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-danger" style="width: <?php echo $lowstock_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small">Out of Stock</span>
                            <span class="badge bg-dark"><?php echo $report_data['consumable_stats']['out_of_stock']; ?></span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <?php 
                            $outofstock_percentage = $report_data['consumable_stats']['total_consumables'] > 0 ? 
                                ($report_data['consumable_stats']['out_of_stock'] / $report_data['consumable_stats']['total_consumables']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-dark" style="width: <?php echo $outofstock_percentage; ?>%"></div>
                        </div>
                    </div>
                    
                    <!-- Monthly Usage -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small">Monthly Consumable Usage</span>
                            <strong><?php echo $report_data['consumable_stats']['monthly_usage']; ?> units</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Request Status Chart - Enhanced with comprehensive status breakdown
            const requestStatusCtx = document.getElementById('requestStatusChart').getContext('2d');
            new Chart(requestStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Denied', 'Returned', 'Borrowed', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo $report_data['request_stats']['pending_requests']; ?>,
                            <?php echo $report_data['request_stats']['approved_requests']; ?>,
                            <?php echo $report_data['request_stats']['denied_requests']; ?>,
                            <?php echo $report_data['request_stats']['returned_requests']; ?>,
                            <?php echo $report_data['request_stats']['borrowed_requests']; ?>,
                            <?php echo $report_data['request_stats']['cancelled_requests']; ?>
                        ],
                        backgroundColor: [
                            '#ffc107', // Pending - Yellow
                            '#28a745', // Approved - Green
                            '#dc3545', // Denied - Red
                            '#6f42c1', // Returned - Purple
                            '#17a2b8', // Borrowed - Blue
                            '#6c757d'  // Cancelled - Gray
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
                                    size: 12
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
            
            // Monthly Trends Chart - Enhanced with multiple datasets
            const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
            const monthlyLabels = <?php echo json_encode(array_column($report_data['monthly_data'], 'month')); ?>;
            const monthlyTotal = <?php echo json_encode(array_column($report_data['monthly_data'], 'total_requests')); ?>;
            const monthlyApproved = <?php echo json_encode(array_column($report_data['monthly_data'], 'approved_count')); ?>;
            const monthlyPending = <?php echo json_encode(array_column($report_data['monthly_data'], 'pending_count')); ?>;
            const monthlyDenied = <?php echo json_encode(array_column($report_data['monthly_data'], 'denied_count')); ?>;
            const monthlyCompleted = <?php echo json_encode(array_column($report_data['monthly_data'], 'completed_count')); ?>;
            
            new Chart(monthlyTrendsCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels.map(month => {
                        const date = new Date(month + '-01');
                        return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Total Requests',
                        data: monthlyTotal,
                        borderColor: '#5CC2F2',
                        backgroundColor: 'rgba(92, 194, 242, 0.1)',
                        tension: 0.4,
                        borderWidth: 2
                    }, {
                        label: 'Approved',
                        data: monthlyApproved,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        borderWidth: 2
                    }, {
                        label: 'Pending',
                        data: monthlyPending,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4,
                        borderWidth: 2
                    }, {
                        label: 'Denied',
                        data: monthlyDenied,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.4,
                        borderWidth: 2
                    }, {
                        label: 'Completed',
                        data: monthlyCompleted,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        tension: 0.4,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15
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
                                    return context.dataset.label + ': ' + context.parsed.y + ' requests';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    if (Math.floor(value) === value) {
                                        return value;
                                    }
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
            
            // Asset Status Chart - Enhanced with comprehensive status breakdown
            const assetStatusCtx = document.getElementById('assetStatusChart').getContext('2d');
            new Chart(assetStatusCtx, {
                type: 'bar',
                data: {
                    labels: ['Available', 'In Use', 'Serviceable', 'Maintenance', 'Unserviceable', 'Disposed', 'No Tag', 'Pending Tag', 'Red Tagged'],
                    datasets: [{
                        label: 'Assets',
                        data: [
                            <?php echo $report_data['asset_stats']['available_assets']; ?>,
                            <?php echo $report_data['asset_stats']['in_use_assets']; ?>,
                            <?php echo $report_data['asset_stats']['serviceable_assets']; ?>,
                            <?php echo $report_data['asset_stats']['maintenance_assets']; ?>,
                            <?php echo $report_data['asset_stats']['unserviceable_assets']; ?>,
                            <?php echo $report_data['asset_stats']['disposed_assets']; ?>,
                            <?php echo $report_data['asset_stats']['no_tag_assets']; ?>,
                            <?php echo $report_data['asset_stats']['pending_tag_assets']; ?>,
                            <?php echo $report_data['asset_stats']['red_tagged_assets']; ?>
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
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#ddd',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                    return context.label + ': ' + context.parsed.y + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    if (Math.floor(value) === value) {
                                        return value;
                                    }
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Number of Assets'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
            
            // Top Consumables Chart
            const topConsumablesCtx = document.getElementById('topConsumablesChart').getContext('2d');
            const topConsumableNames = <?php echo json_encode(array_column($report_data['top_consumables'], 'consumable_description')); ?>;
            const topConsumableValues = <?php echo json_encode(array_column($report_data['top_consumables'], 'total_used')); ?>;
            
            new Chart(topConsumablesCtx, {
                type: 'bar',
                data: {
                    labels: topConsumableNames,
                    datasets: [{
                        label: 'Usage',
                        data: topConsumableValues,
                        backgroundColor: '#5CC2F2',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Weekly Trends Chart
            const weeklyTrendsCtx = document.getElementById('weeklyTrendsChart').getContext('2d');
            const weeklyLabels = <?php echo json_encode(array_column($report_data['request_trends'], 'date')); ?>;
            const weeklyRequests = <?php echo json_encode(array_column($report_data['request_trends'], 'requests_count')); ?>;
            const weeklyApproved = <?php echo json_encode(array_column($report_data['request_trends'], 'approved_count')); ?>;
            
            new Chart(weeklyTrendsCtx, {
                type: 'bar',
                data: {
                    labels: weeklyLabels.map(date => {
                        const d = new Date(date);
                        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Total Requests',
                        data: weeklyRequests,
                        backgroundColor: '#5CC2F2',
                        borderWidth: 0
                    }, {
                        label: 'Approved',
                        data: weeklyApproved,
                        backgroundColor: '#28a745',
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
        
        // Export Report Function
        function exportReport() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            // Create export URL with date parameters
            const exportUrl = `export_office_report.php?date_from=${dateFrom}&date_to=${dateTo}`;
            window.open(exportUrl, '_blank');
        }
        
        // Refresh Data Function
        function refreshData() {
            location.reload();
        }
        
        // Apply Filters Function
        function applyFilters() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            // Reload page with date filters
            const url = new URL(window.location);
            url.searchParams.set('date_from', dateFrom);
            url.searchParams.set('date_to', dateTo);
            window.location.href = url.toString();
        }
        
        // LGU Compliance Functions
        function exportLGUReport(reportType) {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            const url = `api/lgu_compliance_reports.php?action=export_lgu_report&report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;
            window.open(url, '_blank');
        }
        
        function exportAdminStyleReport(reportType) {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            const url = `api/lgu_admin_style_reports.php?action=export_admin_style_report&report_type=${reportType}&date_from=${dateFrom}&date_to=${dateTo}`;
            window.open(url, '_blank');
        }
        
        function showDocumentModal() {
            // Create and show document reference modal
            const modalHtml = `
                <div class="modal fade" id="documentModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Document Reference</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="documentForm">
                                    <div class="mb-3">
                                        <label class="form-label">Document Type</label>
                                        <select class="form-select" name="document_type" required>
                                            <option value="">Select Type</option>
                                            <option value="RIS">Requisition and Issue Slip (RIS)</option>
                                            <option value="PO">Purchase Order (PO)</option>
                                            <option value="PAR">Property Acknowledgment Receipt (PAR)</option>
                                            <option value="ICS">Inventory Custody Slip (ICS)</option>
                                            <option value="JEV">Journal Entry Voucher (JEV)</option>
                                            <option value="DV">Disbursement Voucher (DV)</option>
                                            <option value="OR">Official Receipt (OR)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Document Number</label>
                                        <input type="text" class="form-control" name="document_number" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Document Date</label>
                                        <input type="date" class="form-control" name="document_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Reference Amount (Optional)</label>
                                        <input type="number" class="form-control" name="reference_amount" step="0.01">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Supplier Name (Optional)</label>
                                        <input type="text" class="form-control" name="supplier_name">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveDocumentReference()">Save</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('documentModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body and show
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('documentModal'));
            modal.show();
        }
        
        function saveDocumentReference() {
            const form = document.getElementById('documentForm');
            const formData = new FormData(form);
            
            fetch('api/lgu_compliance_reports.php?action=add_document_reference', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Document reference added successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('documentModal')).hide();
                    refreshData();
                } else {
                    showNotification(data.message || 'Error adding document reference', 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error.message, 'error');
            });
        }
        
        function showScheduleModal() {
            // Create and show schedule modal
            const modalHtml = `
                <div class="modal fade" id="scheduleModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Schedule Report</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="scheduleForm">
                                    <div class="mb-3">
                                        <label class="form-label">Schedule Name</label>
                                        <input type="text" class="form-control" name="schedule_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Report Type</label>
                                        <select class="form-select" name="report_type" required>
                                            <option value="">Select Type</option>
                                            <option value="inventory">Inventory Report</option>
                                            <option value="asset">Asset Report</option>
                                            <option value="consumable">Consumable Report</option>
                                            <option value="borrow_request">Borrow Request Report</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Frequency</label>
                                        <select class="form-select" name="frequency" required onchange="updateScheduleOptions()">
                                            <option value="">Select Frequency</option>
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="annually">Annually</option>
                                        </select>
                                    </div>
                                    <div class="mb-3" id="scheduleDayContainer" style="display: none;">
                                        <label class="form-label">Schedule Day</label>
                                        <select class="form-select" name="schedule_day">
                                            <option value="1">1st</option>
                                            <option value="15">15th</option>
                                            <option value="30">30th</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Schedule Time</label>
                                        <input type="time" class="form-control" name="schedule_time" value="08:00" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Recipients (Email addresses, comma-separated)</label>
                                        <textarea class="form-control" name="recipients" rows="3" placeholder="email1@example.com, email2@example.com"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="saveSchedule()">Save Schedule</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('scheduleModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body and show
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            modal.show();
        }
        
        function updateScheduleOptions() {
            const frequency = document.querySelector('select[name="frequency"]').value;
            const dayContainer = document.getElementById('scheduleDayContainer');
            
            if (frequency === 'monthly' || frequency === 'quarterly' || frequency === 'annually') {
                dayContainer.style.display = 'block';
            } else {
                dayContainer.style.display = 'none';
            }
        }
        
        function saveSchedule() {
            const form = document.getElementById('scheduleForm');
            const formData = new FormData(form);
            
            // Handle recipients array
            const recipientsText = formData.get('recipients');
            const recipients = recipientsText ? recipientsText.split(',').map(r => r.trim()) : [];
            formData.delete('recipients');
            recipients.forEach(recipient => {
                formData.append('recipients[]', recipient);
            });
            
            fetch('api/lgu_compliance_reports.php?action=schedule_report', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Report scheduled successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('scheduleModal')).hide();
                } else {
                    showNotification(data.message || 'Error scheduling report', 'error');
                }
            })
            .catch(error => {
                showNotification('Error: ' + error.message, 'error');
            });
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
