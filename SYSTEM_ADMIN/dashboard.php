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
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/logger.php';

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'dashboard', 'System admin accessed dashboard');

// Get system statistics
$stats = [];

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // User statistics
        $user_query = "SELECT COUNT(*) as total_users, SUM(is_active) as active_users FROM users";
        $user_result = $conn->query($user_query);
        if ($user_result) {
            $user_stats = $user_result->fetch_assoc();
            $stats['total_users'] = $user_stats['total_users'];
            $stats['active_users'] = $user_stats['active_users'];
            $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];
        }
        
        // Role distribution
        $role_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $role_result = $conn->query($role_query);
        $stats['roles'] = [];
        if ($role_result) {
            while ($row = $role_result->fetch_assoc()) {
                $stats['roles'][$row['role']] = $row['count'];
            }
        }
        
        // Categories statistics - Check if table exists first
        $cat_table_check = $conn->query("SHOW TABLES LIKE 'asset_categories'");
        if ($cat_table_check && $cat_table_check->num_rows > 0) {
            $cat_query = "SELECT COUNT(*) as total_categories, SUM(status = 'active') as active_categories FROM asset_categories";
            $cat_result = $conn->query($cat_query);
            if ($cat_result) {
                $cat_stats = $cat_result->fetch_assoc();
                $stats['total_categories'] = $cat_stats['total_categories'];
                $stats['active_categories'] = $cat_stats['active_categories'];
                $stats['inactive_categories'] = $stats['total_categories'] - $stats['active_categories'];
            }
        } else {
            // Try regular categories table if asset_categories doesn't exist
            $cat_table_check2 = $conn->query("SHOW TABLES LIKE 'categories'");
            if ($cat_table_check2 && $cat_table_check2->num_rows > 0) {
                $cat_query2 = "SELECT COUNT(*) as total_categories FROM categories";
                $cat_result2 = $conn->query($cat_query2);
                if ($cat_result2) {
                    $cat_stats2 = $cat_result2->fetch_assoc();
                    $stats['total_categories'] = $cat_stats2['total_categories'];
                    $stats['active_categories'] = $cat_stats2['total_categories']; // Assume all active if no status column
                    $stats['inactive_categories'] = 0;
                }
            } else {
                $stats['total_categories'] = 0;
                $stats['active_categories'] = 0;
                $stats['inactive_categories'] = 0;
            }
        }
        
        // Offices statistics - Check if table exists first
        $office_table_check = $conn->query("SHOW TABLES LIKE 'offices'");
        if ($office_table_check && $office_table_check->num_rows > 0) {
            $office_query = "SELECT COUNT(*) as total_offices, SUM(status = 'active') as active_offices FROM offices";
            $office_result = $conn->query($office_query);
            if ($office_result) {
                $office_stats = $office_result->fetch_assoc();
                $stats['total_offices'] = $office_stats['total_offices'];
                $stats['active_offices'] = $office_stats['active_offices'];
                $stats['inactive_offices'] = $stats['total_offices'] - $stats['active_offices'];
            }
        } else {
            $stats['total_offices'] = 0;
            $stats['active_offices'] = 0;
            $stats['inactive_offices'] = 0;
        }
        
        // Forms statistics - Check if table exists first
        $form_table_check = $conn->query("SHOW TABLES LIKE 'forms'");
        if ($form_table_check && $form_table_check->num_rows > 0) {
            $form_query = "SELECT COUNT(*) as total_forms, SUM(status = 'active') as active_forms FROM forms";
            $form_result = $conn->query($form_query);
            if ($form_result) {
                $form_stats = $form_result->fetch_assoc();
                $stats['total_forms'] = $form_stats['total_forms'];
                $stats['active_forms'] = $form_stats['active_forms'];
                $stats['inactive_forms'] = $stats['total_forms'] - $stats['active_forms'];
            }
        } else {
            $stats['total_forms'] = 0;
            $stats['active_forms'] = 0;
            $stats['inactive_forms'] = 0;
        }
        
        // Remove backup statistics section - not needed for dashboard
        
        // Recent activity (last 7 days) - Check if table exists first
        $logs_table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
        if ($logs_table_check && $logs_table_check->num_rows > 0) {
            try {
                $activity_query = "SELECT DATE(timestamp) as date, COUNT(*) as count FROM system_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(timestamp) ORDER BY date";
                $activity_result = $conn->query($activity_query);
                $stats['activity_trend'] = [];
                if ($activity_result) {
                    while ($row = $activity_result->fetch_assoc()) {
                        $stats['activity_trend'][] = $row;
                    }
                }
                
                // Recent log entries
                $recent_logs_query = "SELECT sl.action, sl.module, sl.description, u.username, sl.timestamp FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.timestamp DESC LIMIT 10";
                $recent_logs_result = $conn->query($recent_logs_query);
                $stats['recent_logs'] = [];
                if ($recent_logs_result) {
                    while ($row = $recent_logs_result->fetch_assoc()) {
                        // Convert timestamp to created_at for consistency
                        $row['created_at'] = $row['timestamp'];
                        $stats['recent_logs'][] = $row;
                    }
                }
            } catch (Exception $e) {
                error_log("System logs query error: " . $e->getMessage());
                $stats['activity_trend'] = [];
                $stats['recent_logs'] = [];
            }
        } else {
            $stats['activity_trend'] = [];
            $stats['recent_logs'] = [];
        }
        
        // System information
        $stats['system_info'] = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $conn->server_info ?? 'MySQL',
            'system_time' => date('Y-m-d H:i:s'),
            'uptime' => 'N/A', // Would need system-specific implementation
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'disk_usage' => 'N/A', // Would need disk space calculation
            'database_status' => 'Connected'
        ];
        
    } catch (Exception $e) {
        $stats['error'] = "Error fetching system stats: " . $e->getMessage();
        error_log("Dashboard Error: " . $e->getMessage());
    }
}

// Set default values if not set
$defaults = [
    'total_users' => 0, 'active_users' => 0, 'inactive_users' => 0,
    'total_categories' => 0, 'active_categories' => 0, 'inactive_categories' => 0,
    'total_offices' => 0, 'active_offices' => 0, 'inactive_offices' => 0,
    'total_forms' => 0, 'active_forms' => 0, 'inactive_forms' => 0,
    'roles' => [], 'activity_trend' => [], 'recent_logs' => []
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
    <title>System Admin Dashboard - PIMS</title>
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
        
        .user-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-system_admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .role-office_admin {
            background: linear-gradient(135deg, #5CC2F2 0%, #C1EAF2 100%);
            color: var(--dark-color);
        }
        
        .role-user {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Chart containers */
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
        
        /* Chart canvas glass effect */
        .chart-container canvas {
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Chart titles with glass effect */
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
        
        /* Chart status text with glass effect */
        .chart-card .text-center {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            padding: 8px 10px;
            border-radius: var(--border-radius);
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .chart-card .text-center small {
            color: #191BA9;
            font-weight: 500;
        }
        
        /* Security Health Score Styles */
        .security-score-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        
        .security-score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .security-score-circle::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #fff, rgba(255,255,255,0.1));
            border-radius: 50%;
            z-index: -1;
        }
        
        .security-score-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .security-score-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            margin-top: 4px;
        }
        
        /* System Size Styles */
        .system-size-display {
            margin: 15px 0;
        }
        
        .size-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #191BA9;
            line-height: 1;
        }
        
        .size-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 4px;
        }
        
        /* Backup Status Styles */
        .backup-status {
            margin: 15px 0;
        }
        
        .backup-time {
            font-size: 1.8rem;
            font-weight: 600;
            color: #191BA9;
            line-height: 1;
        }
        
        .backup-date {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
        }
        
        /* Cloud Storage Styles */
        .cloud-status {
            margin: 15px 0;
        }
        
        .cloud-provider {
            font-size: 1.1rem;
            font-weight: 600;
            color: #191BA9;
            margin-bottom: 8px;
        }
        
        .cloud-provider i {
            color: #4285f4;
            margin-right: 5px;
        }
        
        .cloud-usage {
            font-size: 0.9rem;
            color: #666;
        }
        
        .usage-text {
            font-weight: 500;
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
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
        
        .metric-card {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Dashboard';
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
                        <i class="bi bi-speedometer2"></i> System Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Complete system overview and management interface</p>
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
        
        <!-- Enhanced System Overview Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number" data-metric="total_users"><?php echo $stats['total_users'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-people"></i> Total Users</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number" data-metric="total_categories"><?php echo $stats['total_categories'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-tags"></i> Categories</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number" data-metric="total_offices"><?php echo $stats['total_offices'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-building"></i> Offices</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card">
                    <div class="metric-number" data-metric="total_forms"><?php echo $stats['total_forms'] ?? 0; ?></div>
                    <div class="metric-label"><i class="bi bi-file-earmark-text"></i> Forms</div>
                </div>
            </div>
        </div>
        
        <!-- Security and System Health Overview -->
        <div class="row mb-4">
            <!-- Security Health Score -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-shield-check"></i> Security Health Score</h6>
                    <div class="text-center">
                        <div class="security-score-container">
                            <div class="security-score-circle">
                                <span class="security-score-number">85</span>
                                <span class="security-score-label">Good</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Size -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-hdd"></i> System Size</h6>
                    <div class="text-center">
                        <div class="system-size-display">
                            <div class="size-number">2.4 GB</div>
                            <div class="size-label">Total Storage</div>
                        </div>
                        <div class="mt-3 text-start">
                            <small class="text-muted d-block"><i class="bi bi-database"></i> Database: 156 MB</small>
                            <small class="text-muted d-block"><i class="bi bi-file-earmark"></i> Files: 2.2 GB</small>
                            <small class="text-muted d-block"><i class="bi bi-images"></i> Images: 48 MB</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Last Backup -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-cloud-arrow-up"></i> Last Backup</h6>
                    <div class="text-center">
                        <div class="backup-status">
                            <div class="backup-time">2 hours ago</div>
                            <div class="backup-date">Jan 6, 2026 - 7:45 PM</div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Successful</span>
                            <div class="mt-2">
                                <small class="text-muted">Size: 245 MB</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cloud Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-cloud"></i> Cloud Storage</h6>
                    <div class="text-center">
                        <div class="cloud-status">
                            <div class="cloud-provider">
                                <i class="bi bi-google"></i> Google Drive
                            </div>
                            <div class="cloud-usage">
                                <span class="usage-text">4.2 GB / 15 GB</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 28%" aria-valuenow="28" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted">28% used</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- User Role Distribution -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart"></i> User Role Distribution</h6>
                    <div class="chart-container">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- System Activity Trend -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-graph-up"></i> 7-Day Activity Trend</h6>
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Categories Overview -->
            <div class="col-lg-4">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-tags"></i> Categories Overview</h6>
                    <div class="chart-container">
                        <canvas id="categoryOverviewChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Module Status Overview -->
        <div class="row mb-4">
            <!-- Categories Status -->
            <div class="col-lg-3">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-tags"></i> Categories Status</h6>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="categoryStatusChart"></canvas>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Active: <?php echo $stats['active_categories'] ?? 0; ?> | Inactive: <?php echo $stats['inactive_categories'] ?? 0; ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Offices Status -->
            <div class="col-lg-3">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-building"></i> Offices Status</h6>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="officeStatusChart"></canvas>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Active: <?php echo $stats['active_offices'] ?? 0; ?> | Inactive: <?php echo $stats['inactive_offices'] ?? 0; ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Forms Status -->
            <div class="col-lg-3">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Forms Status</h6>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="formStatusChart"></canvas>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Active: <?php echo $stats['active_forms'] ?? 0; ?> | Inactive: <?php echo $stats['inactive_forms'] ?? 0; ?></small>
                    </div>
                </div>
            </div>
            
            <!-- User Activity -->
            <div class="col-lg-3">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-people"></i> User Activity</h6>
                    <div class="chart-container" style="height: 200px;">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Active: <?php echo $stats['active_users'] ?? 0; ?> | Inactive: <?php echo $stats['inactive_users'] ?? 0; ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information and Recent Activity -->
        <div class="row mb-4">
            <!-- System Information -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">PHP Version</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['php_version'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Server Software</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['server_software'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Database</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['database_version'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Database Status</small>
                            <div class="fw-semibold <?php echo isset($stats['error']) ? 'text-danger' : 'text-success'; ?>">
                                <i class="bi bi-circle-fill"></i> <?php echo $stats['system_info']['database_status'] ?? 'Unknown'; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Memory Usage</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['memory_usage'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">System Time</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['system_time'] ?? 'Unknown'; ?></div>
                        </div>
                        <div>
                            <small class="text-muted">System Status</small>
                            <div class="fw-semibold text-success">
                                <i class="bi bi-circle-fill"></i> Operational
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Feed -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-info text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent System Activity</h6>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if (!empty($stats['recent_logs'])): ?>
                                <?php foreach ($stats['recent_logs'] as $log): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($log['action'] ?? 'Unknown'); ?></strong>
                                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($log['module'] ?? 'System'); ?></span>
                                                <div class="small text-muted mt-1">
                                                    <?php echo htmlspecialchars(substr($log['description'] ?? 'No description', 0, 100)); ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></div>
                                                <div class="small text-muted"><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No recent activity found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Notifications -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-danger text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-bell"></i> System Notifications</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info alert-sm mb-2" role="alert">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>System Update:</strong> PHP version <?php echo $stats['system_info']['php_version'] ?? 'Unknown'; ?> is current
                                </div>
                                <div class="alert alert-success alert-sm mb-2" role="alert">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>Database:</strong> All connections stable
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning alert-sm mb-2" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Memory Usage:</strong> Consider monitoring memory usage
                                </div>
                                <div class="alert alert-info alert-sm mb-0" role="alert">
                                    <i class="bi bi-clock"></i>
                                    <strong>Last Backup:</strong> 2 days ago
                                </div>
                            </div>
                        </div>
                    </div>
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
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Dashboard functions
        function refreshDashboard() {
            location.reload();
        }
        
        function exportData() {
            // Create CSV export of dashboard data
            const data = {
                timestamp: new Date().toISOString(),
                users: {
                    total: <?php echo $stats['total_users'] ?? 0; ?>,
                    active: <?php echo $stats['active_users'] ?? 0; ?>,
                    inactive: <?php echo $stats['inactive_users'] ?? 0; ?>
                },
                categories: {
                    total: <?php echo $stats['total_categories'] ?? 0; ?>,
                    active: <?php echo $stats['active_categories'] ?? 0; ?>,
                    inactive: <?php echo $stats['inactive_categories'] ?? 0; ?>
                },
                offices: {
                    total: <?php echo $stats['total_offices'] ?? 0; ?>,
                    active: <?php echo $stats['active_offices'] ?? 0; ?>,
                    inactive: <?php echo $stats['inactive_offices'] ?? 0; ?>
                },
                forms: {
                    total: <?php echo $stats['total_forms'] ?? 0; ?>,
                    active: <?php echo $stats['active_forms'] ?? 0; ?>,
                    inactive: <?php echo $stats['inactive_forms'] ?? 0; ?>
                },
                security: {
                    health_score: 85,
                    status: 'Good'
                },
                system: {
                    total_size: '2.4 GB',
                    database_size: '156 MB',
                    files_size: '2.2 GB',
                    images_size: '48 MB'
                },
                backup: {
                    last_backup: '2 hours ago',
                    status: 'Successful',
                    size: '245 MB'
                },
                cloud_storage: {
                    provider: 'Google Drive',
                    used: '4.2 GB',
                    total: '15 GB',
                    percentage: 28
                }
            };
            
            // Convert to CSV and download
            let csv = 'Metric,Value,Details\n';
            csv += `Total Users,${data.users.total},Active: ${data.users.active}, Inactive: ${data.users.inactive}\n`;
            csv += `Total Categories,${data.categories.total},Active: ${data.categories.active}, Inactive: ${data.categories.inactive}\n`;
            csv += `Total Offices,${data.offices.total},Active: ${data.offices.active}, Inactive: ${data.offices.inactive}\n`;
            csv += `Total Forms,${data.forms.total},Active: ${data.forms.active}, Inactive: ${data.forms.inactive}\n`;
            csv += `Security Health Score,${data.security.health_score},${data.security.status}\n`;
            csv += `System Size,${data.system.total_size},Database: ${data.system.database_size}, Files: ${data.system.files_size}, Images: ${data.system.images_size}\n`;
            csv += `Last Backup,${data.backup.last_backup},${data.backup.status} (${data.backup.size})\n`;
            csv += `Cloud Storage,${data.cloud_storage.used}/${data.cloud_storage.total},${data.cloud_storage.provider} (${data.cloud_storage.percentage}% used)\n`;
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `dashboard_export_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function systemSettings() {
            window.location.href = 'system_settings.php';
        }

        function viewLogs() {
            window.location.href = 'logs.php';
        }

        function backupSystem() {
            window.location.href = 'backup.php';
        }

        function securityAudit() {
            window.location.href = 'security_audit.php';
        }
        
        // Initialize all charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Register the datalabels plugin
            Chart.register(ChartDataLabels);
            
            // Chart.js global configuration
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#666';
            
            // Fix modal backdrop issues
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('show.bs.modal', function () {
                    // Ensure proper backdrop
                    document.body.classList.add('modal-open');
                });
                
                logoutModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up backdrop
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
                
                // Ensure cancel button works properly
                const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const modal = bootstrap.Modal.getInstance(logoutModal);
                        if (modal) {
                            modal.hide();
                        }
                    });
                }
            }
            
            // User Role Distribution Pie Chart
            const roleCtx = document.getElementById('roleChart').getContext('2d');
            window.roleChart = new Chart(roleCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['roles'] ?? [])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['roles'] ?? [])); ?>,
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.8)',  // system_admin - red with transparency
                            'rgba(25, 27, 169, 0.8)',   // admin - primary blue with transparency
                            'rgba(92, 194, 242, 0.8)',  // office_admin - light blue with transparency
                            'rgba(40, 167, 69, 0.8)',   // user - green with transparency
                            'rgba(255, 193, 7, 0.8)',    // other roles - yellow with transparency
                            'rgba(108, 117, 125, 0.8)'   // fallback - gray with transparency
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',    // system_admin - solid red border
                            'rgba(25, 27, 169, 1)',    // admin - solid blue border
                            'rgba(92, 194, 242, 1)',  // office_admin - solid light blue border
                            'rgba(40, 167, 69, 1)',   // user - solid green border
                            'rgba(255, 193, 7, 1)',    // other roles - solid yellow border
                            'rgba(108, 117, 125, 1)'   // fallback - solid gray border
                        ],
                        borderWidth: 2,
                        hoverBackgroundColor: [
                            'rgba(220, 53, 69, 0.9)',  // system_admin - slightly less transparent
                            'rgba(25, 27, 169, 0.9)',   // admin - slightly less transparent
                            'rgba(92, 194, 242, 0.9)',  // office_admin - slightly less transparent
                            'rgba(40, 167, 69, 0.9)',   // user - slightly less transparent
                            'rgba(255, 193, 7, 0.9)',    // other roles - slightly less transparent
                            'rgba(108, 117, 125, 0.9)'   // fallback - slightly less transparent
                        ],
                        hoverBorderColor: [
                            'rgba(220, 53, 69, 1)',    // system_admin - solid border
                            'rgba(25, 27, 169, 1)',    // admin - solid border
                            'rgba(92, 194, 242, 1)',  // office_admin - solid border
                            'rgba(40, 167, 69, 1)',   // user - solid border
                            'rgba(255, 193, 7, 1)',    // other roles - solid border
                            'rgba(108, 117, 125, 1)'   // fallback - solid border
                        ],
                        hoverBorderWidth: 3
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
                                },
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return value > 0 ? `${value}\n(${percentage}%)` : ''; // Show number and percentage only if > 0
                            },
                            anchor: 'center',
                            align: 'center',
                            textAlign: 'center'
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
            
            // System Activity Trend Line Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            const activityData = <?php echo json_encode($stats['activity_trend'] ?? []); ?>;
            const last7Days = [];
            const activityCounts = [];
            
            // Generate last 7 days labels
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last7Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                // Find matching data or use 0
                const dateStr = date.toISOString().split('T')[0];
                const dayData = activityData.find(d => d.date === dateStr);
                activityCounts.push(dayData ? parseInt(dayData.count) : 0);
            }
            
            window.activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [{
                        label: 'System Activities',
                        data: activityCounts,
                        borderColor: '#191BA9',
                        backgroundColor: 'rgba(25, 27, 169, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#191BA9',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
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
                            callbacks: {
                                label: function(context) {
                                    return `Activities: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Activities'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Categories Overview Chart
            const categoryOverviewCtx = document.getElementById('categoryOverviewChart').getContext('2d');
            window.categoryOverviewChart = new Chart(categoryOverviewCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active Categories', 'Inactive Categories'],
                    datasets: [{
                        data: [
                            <?php echo $stats['active_categories'] ?? 0; ?>,
                            <?php echo $stats['inactive_categories'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            '#28a745', // active - green
                            '#6c757d'  // inactive - gray
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
                        },
                        datalabels: {
                            display: true,
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return value > 0 ? `${value}\n(${percentage}%)` : ''; // Show number and percentage only if > 0
                            },
                            anchor: 'center',
                            align: 'center',
                            textAlign: 'center'
                        }
                    }
                }
            });
            
            // Categories Status Bar Chart
            const categoryStatusCtx = document.getElementById('categoryStatusChart').getContext('2d');
            new Chart(categoryStatusCtx, {
                type: 'bar',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [
                            <?php echo $stats['active_categories'] ?? 0; ?>,
                            <?php echo $stats['inactive_categories'] ?? 0; ?>
                        ],
                        backgroundColor: ['#28a745', '#6c757d'],
                        borderWidth: 0
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
                            callbacks: {
                                label: function(context) {
                                    return `Categories: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Categories'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Offices Status Bar Chart
            const officeStatusCtx = document.getElementById('officeStatusChart').getContext('2d');
            new Chart(officeStatusCtx, {
                type: 'bar',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [
                            <?php echo $stats['active_offices'] ?? 0; ?>,
                            <?php echo $stats['inactive_offices'] ?? 0; ?>
                        ],
                        backgroundColor: ['#191BA9', '#6c757d'],
                        borderWidth: 0
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
                            callbacks: {
                                label: function(context) {
                                    return `Offices: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Offices'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // Forms Status Bar Chart
            const formStatusCtx = document.getElementById('formStatusChart').getContext('2d');
            new Chart(formStatusCtx, {
                type: 'bar',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [
                            <?php echo $stats['active_forms'] ?? 0; ?>,
                            <?php echo $stats['inactive_forms'] ?? 0; ?>
                        ],
                        backgroundColor: ['#5CC2F2', '#6c757d'],
                        borderWidth: 0
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
                            callbacks: {
                                label: function(context) {
                                    return `Forms: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Forms'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
            // User Activity Bar Chart
            const userActivityCtx = document.getElementById('userActivityChart').getContext('2d');
            new Chart(userActivityCtx, {
                type: 'bar',
                data: {
                    labels: ['Active', 'Inactive'],
                    datasets: [{
                        data: [
                            <?php echo $stats['active_users'] ?? 0; ?>,
                            <?php echo $stats['inactive_users'] ?? 0; ?>
                        ],
                        backgroundColor: ['#dc3545', '#6c757d'],
                        borderWidth: 0
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
                            callbacks: {
                                label: function(context) {
                                    return `Users: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Users'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            console.log('Auto-refreshing dashboard...');
            refreshDashboardData();
        }, 300000);
        
        // Real-time data refresh function
        function refreshDashboardData() {
            fetch('ajax/get_dashboard_data.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboardCharts(data.stats);
                    updateMetricCards(data.stats);
                    updateActivityFeed(data.stats.recent_logs);
                } else {
                    console.error('Failed to refresh dashboard data:', data.error);
                }
            })
            .catch(error => {
                console.error('Error refreshing dashboard:', error);
            });
        }
        
        // Update metric cards with new data
        function updateMetricCards(stats) {
            // Update metric numbers
            const metrics = [
                { id: 'total_users', value: stats.total_users },
                { id: 'total_categories', value: stats.total_categories },
                { id: 'total_offices', value: stats.total_offices },
                { id: 'total_forms', value: stats.total_forms }
            ];
            
            metrics.forEach(metric => {
                const element = document.querySelector(`[data-metric="${metric.id}"]`);
                if (element) {
                    const currentValue = parseInt(element.textContent);
                    const newValue = parseInt(metric.value);
                    
                    if (currentValue !== newValue) {
                        element.style.transition = 'all 0.5s ease';
                        element.style.transform = 'scale(1.2)';
                        element.textContent = newValue;
                        
                        setTimeout(() => {
                            element.style.transform = 'scale(1)';
                        }, 300);
                    }
                }
            });
        }
        
        // Update charts with new data
        function updateDashboardCharts(stats) {
            // Update role distribution chart
            if (window.roleChart && stats.roles) {
                window.roleChart.data.datasets[0].data = Object.values(stats.roles);
                window.roleChart.update();
            }
            
            // Update activity trend chart
            if (window.activityChart && stats.activity_trend) {
                const last7Days = [];
                const activityCounts = [];
                
                for (let i = 6; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    last7Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    
                    const dateStr = date.toISOString().split('T')[0];
                    const dayData = stats.activity_trend.find(d => d.date === dateStr);
                    activityCounts.push(dayData ? parseInt(dayData.count) : 0);
                }
                
                window.activityChart.data.labels = last7Days;
                window.activityChart.data.datasets[0].data = activityCounts;
                window.activityChart.update();
            }
            
        // Update other charts similarly...
            if (window.categoryOverviewChart) {
                window.categoryOverviewChart.data.datasets[0].data = [stats.active_categories, stats.inactive_categories];
                window.categoryOverviewChart.update();
            }
            
            // Update bar charts with datalabels if needed
            const barCharts = ['categoryStatusChart', 'officeStatusChart', 'formStatusChart', 'userActivityChart'];
            barCharts.forEach(chartId => {
                const chartElement = document.getElementById(chartId);
                if (chartElement && chartElement.chart) {
                    // Update data if chart exists and has update method
                    if (chartElement.chart.update) {
                        chartElement.chart.update();
                    }
                }
            });
        }
        
        // Update activity feed
        function updateActivityFeed(recentLogs) {
            const feedContainer = document.querySelector('.activity-feed');
            if (feedContainer && recentLogs) {
                let html = '';
                recentLogs.forEach(log => {
                    html += `
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${log.action || 'Unknown'}</strong>
                                    <span class="badge bg-secondary ms-2">${log.module || 'System'}</span>
                                    <div class="small text-muted mt-1">
                                        ${(log.description || 'No description').substring(0, 100)}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted">${log.username || 'System'}</div>
                                    <div class="small text-muted">${new Date(log.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = `
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-2">No recent activity found</p>
                        </div>
                    `;
                }
                
                feedContainer.innerHTML = html;
            }
        }
    </script>
</body>
</html>
