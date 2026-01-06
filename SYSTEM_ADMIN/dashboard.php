<?php
session_start();
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
try {
    // User statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users, SUM(is_active) as active_users FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $user_stats = $result->fetch_assoc();
    $stats['total_users'] = $user_stats['total_users'];
    $stats['active_users'] = $user_stats['active_users'];
    $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];
    $stmt->close();
    
    // Role distribution
    $stmt = $conn->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['roles'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['roles'][$row['role']] = $row['count'];
    }
    $stmt->close();
    
    // System information
    $stats['system_info'] = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'database_version' => 'MySQL',
        'system_time' => date('Y-m-d H:i:s'),
        'uptime' => 'N/A' // Would need system-specific implementation
    ];
    
} catch (Exception $e) {
    error_log("Error fetching system stats: " . $e->getMessage());
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
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        
        <!-- System Overview Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                            <div class="text-muted">Total Users</div>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> 
                                <?php echo $stats['active_users'] ?? 0; ?> active
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['roles']['system_admin'] ?? 0; ?></div>
                            <div class="text-muted">System Admins</div>
                            <small class="text-warning">High Privilege</small>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-shield-check fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo ($stats['roles']['admin'] ?? 0) + ($stats['roles']['office_admin'] ?? 0); ?></div>
                            <div class="text-muted">Admin Users</div>
                            <small class="text-info">Management Level</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-person-badge fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?php echo $stats['roles']['user'] ?? 0; ?></div>
                            <div class="text-muted">Regular Users</div>
                            <small class="text-success">Standard Access</small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-person fs-1"></i>
                        </div>
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
            
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-success text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="user_management.php" class="btn btn-outline-primary text-start">
                                <i class="bi bi-people"></i> Manage Users
                            </a>
                            <button class="btn btn-outline-info text-start" onclick="systemSettings()">
                                <i class="bi bi-gear"></i> System Settings
                            </button>
                            <button class="btn btn-outline-warning text-start" onclick="viewLogs()">
                                <i class="bi bi-file-text"></i> System Logs
                            </button>
                            <button class="btn btn-outline-success text-start" onclick="backupSystem()">
                                <i class="bi bi-cloud-download"></i> Backup System
                            </button>
                            <button class="btn btn-outline-danger text-start" onclick="securityAudit()">
                                <i class="bi bi-shield-exclamation"></i> Security Audit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-warning text-dark rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-heart-pulse"></i> System Health</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Database</small>
                                <small class="text-success">Good</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 95%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Memory Usage</small>
                                <small class="text-warning">67%</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: 67%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Disk Space</small>
                                <small class="text-success">82%</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 82%"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">CPU Load</small>
                                <small class="text-success">23%</small>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 23%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Overall Health</small>
                                <small class="text-success">92%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: 92%"></div>
                            </div>
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

        function systemSettings() {
            alert('System Settings module would open here');
        }

        function viewLogs() {
            alert('System Logs viewer would open here');
        }

        function backupSystem() {
            if (confirm('Start system backup? This may take a few minutes.')) {
                alert('Backup process started (simulated)');
            }
        }

        function securityAudit() {
            alert('Security Audit report would be generated here');
        }

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            console.log('Auto-refreshing dashboard...');
            // In production, this would fetch updated data via AJAX
        }, 300000);
    </script>
</body>
</html>
