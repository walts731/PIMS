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

// Function to get security audit data
function getAuditData($conn) {
    $data = [];
    try {
        // User security statistics
        // Correction: use password_changed_at instead of password_changed
        $stmt = $conn->prepare("SELECT COUNT(*) as total_users, SUM(is_active) as active_users, 
                               SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) OR last_login IS NULL THEN 1 ELSE 0 END) as inactive_30_days,
                               SUM(CASE WHEN password_changed_at IS NULL OR password_changed_at < DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as weak_password_users
                               FROM users");
        $stmt->execute();
        $result = $stmt->get_result();
        $user_security = $result->fetch_assoc();
        $data['user_security'] = $user_security;
        $stmt->close();
        
        // Failed login attempts
        $stmt = $conn->prepare("SELECT COUNT(*) as failed_attempts, 
                               SUM(CASE WHEN attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
                               FROM login_logs WHERE success = 0");
        $stmt->execute();
        $result = $stmt->get_result();
        $login_security = $result->fetch_assoc();
        $data['login_security'] = $login_security;
        $stmt->close();
        
        // System security checks
        $data['system_checks'] = [
            'php_version' => PHP_VERSION,
            'https_enabled' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443,
            'error_reporting' => ini_get('display_errors') === '0' || strtolower(ini_get('display_errors')) === 'off',
            'file_uploads' => ini_get('file_uploads') === '1' || strtolower(ini_get('file_uploads')) === 'on',
            'allow_url_fopen' => ini_get('allow_url_fopen') === '1' || strtolower(ini_get('allow_url_fopen')) === 'on',
            'session_timeout' => ini_get('session.gc_maxlifetime'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
        
        // Recent security events
        $stmt = $conn->prepare("SELECT event_type, description, timestamp, severity 
                               FROM security_logs 
                               WHERE timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
                               ORDER BY timestamp DESC LIMIT 10");
        $stmt->execute();
        $result = $stmt->get_result();
        $data['recent_events'] = [];
        while ($row = $result->fetch_assoc()) {
            $data['recent_events'][] = $row;
        }
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error fetching security audit data: " . $e->getMessage());
    }
    return $data;
}

// Security score calculation
function calculateSecurityScore($data) {
    if (empty($data)) return ['score' => 0, 'rating' => 'UNKNOWN', 'class' => 'threat-medium', 'findings' => []];
    
    $score = 100;
    $findings = [];
    
    if (!($data['system_checks']['https_enabled'] ?? true)) {
        $score -= 15;
        $findings[] = ['severity' => 'medium', 'category' => 'Network', 'issue' => 'HTTPS not enabled', 'recommendation' => 'Enable SSL/TLS certificate'];
    }
    
    if (!($data['system_checks']['error_reporting'] ?? true)) {
        $score -= 10;
        $findings[] = ['severity' => 'medium', 'category' => 'Config', 'issue' => 'Error display enabled', 'recommendation' => 'Disable display_errors in production'];
    }
    
    if ($data['system_checks']['allow_url_fopen'] ?? false) {
        $score -= 5;
        $findings[] = ['severity' => 'low', 'category' => 'Config', 'issue' => 'URL fopen enabled', 'recommendation' => 'Disable allow_url_fopen'];
    }
    
    $weak_pass = $data['user_security']['weak_password_users'] ?? 0;
    if ($weak_pass > 0) {
        $deduction = min(20, $weak_pass * 2);
        $score -= $deduction;
        $findings[] = ['severity' => 'medium', 'category' => 'Users', 'issue' => "$weak_pass users with weak/expired passwords", 'recommendation' => 'Enforce password policy'];
    }
    
    $inactive = $data['user_security']['inactive_30_days'] ?? 0;
    if ($inactive > 0) {
        $score -= 5;
        $findings[] = ['severity' => 'low', 'category' => 'Users', 'issue' => "$inactive inactive users detected", 'recommendation' => 'Review inactive accounts'];
    }
    
    $failed_24h = $data['login_security']['last_24h'] ?? 0;
    if (($failed_24h ?? 0) > 10) {
        $score -= 15;
        $findings[] = ['severity' => 'high', 'category' => 'Login', 'issue' => "Excessive failed logins ($failed_24h) in 24h", 'recommendation' => 'Investigate for brute-force'];
    }
    
    $rating = 'SECURE';
    $class = 'threat-low';
    if ($score <= 60) {
        $rating = 'HIGH RISK';
        $class = 'threat-high';
    } elseif ($score <= 85) {
        $rating = 'MEDIUM RISK';
        $class = 'threat-medium';
    }
    
    return [
        'score' => max(0, $score),
        'rating' => $rating,
        'class' => $class,
        'findings' => $findings
    ];
}

// Handle AJAX Scan Request
if (isset($_GET['action']) && $_GET['action'] === 'run_scan') {
    header('Content-Type: application/json');
    $data = getAuditData($conn);
    $analysis = calculateSecurityScore($data);
    
    // Log the scan event
    logSecurityEvent('low', 'Security Scan Performed', 'A manual security scan was performed by ' . $_SESSION['username'], 'Audit', $_SESSION['user_id']);
    
    echo json_encode([
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data,
        'analysis' => $analysis
    ]);
    exit;
}

$audit_data = getAuditData($conn);
$security_analysis = calculateSecurityScore($audit_data);

$page_title = 'Security Audit';
$current_page = 'security_audit.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
    <style>
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #dc3545;
        }
        
        .security-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid #dc3545;
        }
        
        .security-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .threat-level {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .threat-low {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .threat-medium {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #212529;
        }
        
        .threat-high {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .threat-critical {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
        }
        
        .security-metric {
            text-align: center;
            padding: 1rem;
            border-radius: var(--border-radius);
            background: rgba(220, 53, 69, 0.05);
            border: 1px solid rgba(220, 53, 69, 0.1);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: #dc3545;
        }
        
        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .event-item {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            border-left: 4px solid;
            background: rgba(255, 255, 255, 0.5);
        }
        
        .event-critical {
            border-left-color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
        }
        
        .event-high {
            border-left-color: #fd7e14;
            background: rgba(253, 126, 20, 0.05);
        }
        
        .event-medium {
            border-left-color: #ffc107;
            background: rgba(255, 193, 7, 0.05);
        }
        
        .event-low {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }
        
        .check-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.5);
        }
        
        .check-pass {
            color: #28a745;
        }
        
        .check-fail {
            color: #dc3545;
        }
        
        .check-warning {
            color: #ffc107;
        }
        
        .audit-progress {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background: rgba(220, 53, 69, 0.1);
        }
        
        .audit-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #dc3545 0%, #fd7e14 50%, #ffc107 100%);
            transition: width 0.3s ease;
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
    </style>
</head>
<body>
<!-- Main Content Wrapper -->
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar-toggle.php'; ?>
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
            
            <!-- Page Content -->
            <div class="container-fluid p-4">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="mb-2">
                                <i class="bi bi-shield-exclamation text-danger"></i>
                                Security Audit
                            </h2>
                            <p class="text-muted mb-0">Monitor and analyze system security status</p>
                        </div>
                        <div>
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#securityScanModal">
                                <i class="bi bi-shield-check"></i> Run Security Scan
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Security Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="security-card">
                            <div class="security-metric">
                                <div class="metric-value"><?php echo $audit_data['user_security']['total_users'] ?? 0; ?></div>
                                <div class="metric-label">Total Users</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="security-card">
                            <div class="security-metric">
                                <div class="metric-value text-warning"><?php echo $audit_data['user_security']['inactive_30_days'] ?? 0; ?></div>
                                <div class="metric-label">Inactive 30+ Days</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="security-card">
                            <div class="security-metric">
                                <div class="metric-value text-danger"><?php echo $audit_data['login_security']['failed_attempts'] ?? 0; ?></div>
                                <div class="metric-label">Failed Logins</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="security-card">
                            <div class="security-metric">
                                <div class="metric-value text-info"><?php echo count($audit_data['recent_events'] ?? []); ?></div>
                                <div class="metric-label">Recent Events</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Health Score -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-danger text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-speedometer2"></i> Security Health Score</h6>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold">Overall Security Rating</span>
                                            <span class="badge <?php echo $security_analysis['class']; ?> text-uppercase"><?php echo $security_analysis['rating']; ?></span>
                                        </div>
                                        <div class="audit-progress">
                                            <div class="audit-progress-bar" style="width: <?php echo $security_analysis['score']; ?>%;"></div>
                                        </div>
                                        <small class="text-muted">System security score based on current vulnerabilities and threats</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="security-score-display">
                                            <div class="score-number" style="font-size: 3rem; font-weight: 700; color: <?php echo $security_analysis['score'] > 80 ? '#28a745' : ($security_analysis['score'] > 60 ? '#ffc107' : '#dc3545'); ?>;">
                                                <?php echo $security_analysis['score']; ?>%
                                            </div>
                                            <div class="score-label">Security Score</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Security Checks -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-dark text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-shield-check"></i> System Security Checks</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $checks = $audit_data['system_checks'] ?? [];
                                foreach ($checks as $check => $value) {
                                    $status = 'check-pass';
                                    $icon = 'bi-check-circle-fill';
                                    $text = 'Secure';
                                    
                                    if ($check === 'https_enabled' && !$value) {
                                        $status = 'check-fail';
                                        $icon = 'bi-x-circle-fill';
                                        $text = 'HTTPS Not Enabled';
                                    } elseif ($check === 'error_reporting' && !$value) {
                                        $status = 'check-fail';
                                        $icon = 'bi-x-circle-fill';
                                        $text = 'Error Display Enabled';
                                    } elseif ($check === 'allow_url_fopen' && $value) {
                                        $status = 'check-warning';
                                        $icon = 'bi-exclamation-triangle-fill';
                                        $text = 'URL Fopen Enabled';
                                    }
                                    
                                    echo '<div class="check-item">';
                                    echo '<i class="bi ' . $icon . ' ' . $status . ' me-3"></i>';
                                    echo '<div class="flex-fill">';
                                    echo '<div class="fw-bold">' . ucfirst(str_replace('_', ' ', $check)) . '</div>';
                                    echo '<small class="text-muted">' . $text . '</small>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-warning text-dark rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Security Recommendations</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!($audit_data['system_checks']['https_enabled'] ?? true)): ?>
                                <div class="alert alert-warning mb-2" role="alert">
                                    <i class="bi bi-shield-exclamation"></i>
                                    <strong>Enable HTTPS:</strong> Configure SSL certificate for secure connections
                                </div>
                                <?php endif; ?>
                                
                                <div class="alert alert-info mb-2" role="alert">
                                    <i class="bi bi-key"></i>
                                    <strong>Password Policy:</strong> <?php echo ($audit_data['user_security']['weak_password_users'] ?? 0) > 0 ? 'Update password expiration policy' : 'Password policy is adequate'; ?>
                                </div>
                                
                                <div class="alert alert-success mb-2" role="alert">
                                    <i class="bi bi-clock-history"></i>
                                    <strong>Session Timeout:</strong> Current timeout is <?php echo $audit_data['system_checks']['session_timeout'] ?? 'Unknown'; ?> seconds
                                </div>
                                
                                <?php if (($audit_data['user_security']['inactive_30_days'] ?? 0) > 0): ?>
                                <div class="alert alert-danger mb-0" role="alert">
                                    <i class="bi bi-person-x"></i>
                                    <strong>Inactive Users:</strong> Review and deactivate <?php echo $audit_data['user_security']['inactive_30_days'] ?? 0; ?> inactive accounts
                                </div>
                                <?php endif; ?>

                                <?php if (empty($security_analysis['findings']) && ($audit_data['system_checks']['https_enabled'] ?? true)): ?>
                                <div class="alert alert-success mb-0" role="alert">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>All Clear:</strong> No immediate security concerns detected.
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Security Events -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-danger text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Security Events (Last 7 Days)</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $events = $audit_data['recent_events'] ?? [];
                                if (empty($events)) {
                                    echo '<div class="text-center text-muted py-4">';
                                    echo '<i class="bi bi-shield-check" style="font-size: 3rem;"></i>';
                                    echo '<p class="mt-2">No recent security events detected</p>';
                                    echo '</div>';
                                } else {
                                    foreach ($events as $event) {
                                        $severity_class = 'event-' . ($event['severity'] ?? 'low');
                                        echo '<div class="event-item ' . $severity_class . '">';
                                        echo '<div class="d-flex justify-content-between align-items-start">';
                                        echo '<div>';
                                        echo '<div class="fw-bold">' . htmlspecialchars($event['event_type'] ?? 'Unknown Event') . '</div>';
                                        echo '<small class="text-muted">' . htmlspecialchars($event['description'] ?? 'No description') . '</small>';
                                        echo '</div>';
                                        echo '<div class="text-end">';
                                        echo '<span class="badge threat-' . ($event['severity'] ?? 'low') . '">' . ucfirst($event['severity'] ?? 'low') . '</span>';
                                        echo '<br><small class="text-muted">' . date('M j, Y H:i', strtotime($event['timestamp'] ?? 'now')) . '</small>';
                                        echo '</div>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Security Analysis -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-lg rounded-4">
                            <div class="card-header bg-primary text-white rounded-top-4">
                                <h6 class="mb-0"><i class="bi bi-people"></i> User Security Analysis</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="text-center p-3">
                                            <div class="metric-value text-success"><?php echo $audit_data['user_security']['active_users'] ?? 0; ?></div>
                                            <div class="metric-label">Active Users</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3">
                                            <div class="metric-value text-warning"><?php echo $audit_data['user_security']['weak_password_users'] ?? 0; ?></div>
                                            <div class="metric-label">Weak Passwords</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="text-center p-3">
                                            <div class="metric-value text-danger"><?php echo $audit_data['login_security']['last_24h'] ?? 0; ?></div>
                                            <div class="metric-label">Failed Logins (24h)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
        
    <!-- Security Scan Modal -->
    <div class="modal fade" id="securityScanModal" tabindex="-1" aria-labelledby="securityScanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="securityScanModalLabel">
                        <i class="bi bi-shield-check"></i> Security Scan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="scanInitial">
                        <div class="text-center py-4">
                            <i class="bi bi-shield-exclamation text-danger" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Run Comprehensive Security Scan</h5>
                            <p class="text-muted">This will perform a thorough security analysis of your system including:</p>
                            <ul class="list-unstyled text-start">
                                <li><i class="bi bi-check-circle text-success"></i> User authentication security</li>
                                <li><i class="bi bi-check-circle text-success"></i> System configuration checks</li>
                                <li><i class="bi bi-check-circle text-success"></i> File permission analysis</li>
                                <li><i class="bi bi-check-circle text-success"></i> Database security audit</li>
                                <li><i class="bi bi-check-circle text-success"></i> Network vulnerability assessment</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong>Note:</strong> This process will analyze real-time system data.
                            </div>
                        </div>
                    </div>
                    
                    <div id="scanProgress" style="display: none;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-danger mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Scanning...</span>
                            </div>
                            <h5>Scanning System Security...</h5>
                            <p class="text-muted">Please wait while we analyze your system security</p>
                            
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-danger" id="scanProgressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            
                            <div id="scanStatus" class="text-muted small">Initializing scan...</div>
                        </div>
                    </div>
                    
                    <div id="scanResults" style="display: none;">
                        <div class="text-center py-4">
                            <i class="id-scan-icon bi bi-shield-check text-success" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Security Scan Complete</h5>
                            <p class="text-muted">System security analysis has been completed successfully.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card border-danger">
                                        <div class="card-body text-center">
                                            <h4 class="text-danger" id="resCritical">0</h4>
                                            <small class="text-muted">Issues Found</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-primary">
                                        <div class="card-body text-center">
                                            <h4 class="text-primary" id="resScore">0%</h4>
                                            <small class="text-muted">Security Score</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <h4 class="text-info" id="resRating">---</h4>
                                            <small class="text-muted">Risk Rating</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="resAlert" class="alert mt-4">
                                <i class="bi bi-info-circle"></i>
                                <strong id="resSummary">Summary text goes here.</strong>
                            </div>
                            
                            <div class="text-start">
                                <h6>Key Findings & Recommendations:</h6>
                                <ul class="small" id="resFindingsList">
                                    <!-- Findings will be populated here -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="scanCancelButton">Cancel</button>
                    <button type="button" class="btn btn-danger" id="startScanBtn">Start Scan</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    <script>
        
        // Fix modal backdrop issues
        document.addEventListener('DOMContentLoaded', function() {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('show.bs.modal', function () {
                    document.body.classList.add('modal-open');
                });
                
                logoutModal.addEventListener('hidden.bs.modal', function () {
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                });
            }
        });
        
        // Security scan results globally shared for export
        let lastScanResults = null;

        document.addEventListener('DOMContentLoaded', function() {
            const startScanBtn = document.getElementById('startScanBtn');
            const scanCancelButton = document.getElementById('scanCancelButton');
            const scanInitial = document.getElementById('scanInitial');
            const scanProgress = document.getElementById('scanProgress');
            const scanResults = document.getElementById('scanResults');
            const scanProgressBar = document.getElementById('scanProgressBar');
            const scanStatus = document.getElementById('scanStatus');

            startScanBtn.addEventListener('click', function() {
                scanInitial.style.display = 'none';
                scanProgress.style.display = 'block';
                startScanBtn.style.display = 'none';
                scanCancelButton.style.display = 'none';

                let progress = 0;
                const scanSteps = [
                    'Analyzing user authentication...',
                    'Checking system configurations...',
                    'Auditing database logs...',
                    'Verifying security protocols...',
                    'Calculating health score...'
                ];

                const progressInterval = setInterval(() => {
                    if (progress < 90) {
                        progress += (100 / scanSteps.length) / 5;
                        scanProgressBar.style.width = progress + '%';
                        const stepIdx = Math.min(scanSteps.length - 1, Math.floor(progress / (100 / scanSteps.length)));
                        scanStatus.textContent = scanSteps[stepIdx];
                    }
                }, 100);

                // Real Scan call
                fetch('security_audit.php?action=run_scan')
                    .then(response => response.json())
                    .then(result => {
                        clearInterval(progressInterval);
                        lastScanResults = result;
                        
                        // Complete progress bar
                        scanProgressBar.style.width = '100%';
                        scanStatus.textContent = 'Finalizing results...';

                        setTimeout(() => {
                            populateResults(result);
                            scanProgress.style.display = 'none';
                            scanResults.style.display = 'block';
                            scanCancelButton.style.display = 'inline-block';
                            scanCancelButton.textContent = 'Close';
                        }, 500);
                    })
                    .catch(error => {
                        clearInterval(progressInterval);
                        console.error('Scan failed:', error);
                        alert('Security scan failed to complete.');
                        location.reload();
                    });
            });

            function populateResults(result) {
                const analysis = result.analysis;
                document.getElementById('resCritical').textContent = analysis.findings.length;
                document.getElementById('resScore').textContent = analysis.score + '%';
                document.getElementById('resRating').textContent = analysis.rating;
                
                const scoreColor = analysis.score > 80 ? '#28a745' : (analysis.score > 60 ? '#ffc107' : '#dc3545');
                document.getElementById('resScore').style.color = scoreColor;
                
                const alertDiv = document.getElementById('resAlert');
                alertDiv.className = 'alert mt-4 ' + (analysis.score > 80 ? 'alert-success' : (analysis.score > 60 ? 'alert-warning' : 'alert-danger'));
                document.getElementById('resSummary').textContent = 'Security Scan Score: ' + analysis.score + '% (' + analysis.rating + ')';
                
                const list = document.getElementById('resFindingsList');
                list.innerHTML = '';
                if (analysis.findings.length === 0) {
                    list.innerHTML = '<li><i class="bi bi-check-circle text-success"></i> No major security issues detected.</li>';
                } else {
                    analysis.findings.forEach(f => {
                        const icon = f.severity === 'high' ? 'bi-exclamation-octagon text-danger' : (f.severity === 'medium' ? 'bi-exclamation-triangle text-warning' : 'bi-info-circle text-info');
                        list.innerHTML += `<li><i class="bi ${icon}"></i> <strong>${f.issue}:</strong> ${f.recommendation}</li>`;
                    });
                }
                
                const icon = document.querySelector('.id-scan-icon');
                icon.className = 'id-scan-icon bi ' + (analysis.score > 80 ? 'bi-shield-check text-success' : 'bi-shield-exclamation ' + (analysis.score > 60 ? 'text-warning' : 'text-danger'));
            }


            document.getElementById('securityScanModal').addEventListener('hidden.bs.modal', function() {
                location.reload(); // Refresh to update main dashboard with new scan data
            });
        });

    </script>
<?php require_once 'includes/footer.php'; ?>
</body>
</html>
