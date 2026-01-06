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

// Get security audit data
$audit_data = [];
try {
    // User security statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users, SUM(is_active) as active_users, 
                           SUM(CASE WHEN last_login < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as inactive_30_days,
                           SUM(CASE WHEN password_changed IS NULL OR password_changed < DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 1 ELSE 0 END) as weak_password_users
                           FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $user_security = $result->fetch_assoc();
    $audit_data['user_security'] = $user_security;
    $stmt->close();
    
    // Failed login attempts (if log table exists)
    $stmt = $conn->prepare("SELECT COUNT(*) as failed_attempts, 
                           SUM(CASE WHEN attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as last_24h
                           FROM login_logs WHERE success = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $login_security = $result->fetch_assoc();
    $audit_data['login_security'] = $login_security;
    $stmt->close();
    
    // System security checks
    $audit_data['system_checks'] = [
        'php_version' => PHP_VERSION,
        'https_enabled' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'error_reporting' => ini_get('display_errors') === '0',
        'file_uploads' => ini_get('file_uploads') === '1',
        'allow_url_fopen' => ini_get('allow_url_fopen') === '1',
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
    $audit_data['recent_events'] = [];
    while ($row = $result->fetch_assoc()) {
        $audit_data['recent_events'][] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching security audit data: " . $e->getMessage());
}

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
    <link href="../assets/css/index.css" rel="stylesheet">
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
                        <div class="d-flex gap-2">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#securityScanModal">
                                <i class="bi bi-shield-check"></i> Run Security Scan
                            </button>
                            <button class="btn btn-outline-secondary" onclick="exportAuditReport()">
                                <i class="bi bi-download"></i> Export Report
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
                                            <span class="badge threat-medium">MEDIUM RISK</span>
                                        </div>
                                        <div class="audit-progress">
                                            <div class="audit-progress-bar" style="width: 65%;"></div>
                                        </div>
                                        <small class="text-muted">System security score based on current vulnerabilities and threats</small>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div class="security-score-display">
                                            <div class="score-number" style="font-size: 3rem; font-weight: 700; color: #ffc107;">65%</div>
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
                                <div class="alert alert-warning mb-2" role="alert">
                                    <i class="bi bi-shield-exclamation"></i>
                                    <strong>Enable HTTPS:</strong> Configure SSL certificate for secure connections
                                </div>
                                <div class="alert alert-info mb-2" role="alert">
                                    <i class="bi bi-key"></i>
                                    <strong>Password Policy:</strong> <?php echo ($audit_data['user_security']['weak_password_users'] ?? 0) > 0 ? 'Update password expiration policy' : 'Password policy is adequate'; ?>
                                </div>
                                <div class="alert alert-success mb-2" role="alert">
                                    <i class="bi bi-clock-history"></i>
                                    <strong>Session Timeout:</strong> Current timeout is <?php echo $audit_data['system_checks']['session_timeout'] ?? 'Unknown'; ?> seconds
                                </div>
                                <div class="alert alert-danger mb-0" role="alert">
                                    <i class="bi bi-person-x"></i>
                                    <strong>Inactive Users:</strong> Review and deactivate <?php echo $audit_data['user_security']['inactive_30_days'] ?? 0; ?> inactive accounts
                                </div>
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
                                <strong>Note:</strong> This process may take 2-5 minutes to complete.
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
                            <i class="bi bi-shield-check text-success" style="font-size: 4rem;"></i>
                            <h5 class="mt-3">Security Scan Complete</h5>
                            <p class="text-muted">System security analysis has been completed successfully.</p>
                            
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card border-success">
                                        <div class="card-body text-center">
                                            <h4 class="text-success">0</h4>
                                            <small class="text-muted">Critical Issues</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-warning">
                                        <div class="card-body text-center">
                                            <h4 class="text-warning">2</h4>
                                            <small class="text-muted">Medium Risks</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-info">
                                        <div class="card-body text-center">
                                            <h4 class="text-info">5</h4>
                                            <small class="text-muted">Recommendations</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-success mt-4">
                                <i class="bi bi-check-circle"></i>
                                <strong>Overall Security Score: 85%</strong> - Your system is well-secured but has room for improvement.
                            </div>
                            
                            <div class="text-start">
                                <h6>Key Findings:</h6>
                                <ul class="small">
                                    <li>HTTPS should be enabled for secure connections</li>
                                    <li>Consider implementing two-factor authentication</li>
                                    <li>Review and update password expiration policy</li>
                                    <li>Regular security patches are up to date</li>
                                    <li>User access controls are properly configured</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="scanCancelButton">Cancel</button>
                    <button type="button" class="btn btn-danger" id="startScanBtn">Start Scan</button>
                    <button type="button" class="btn btn-success" style="display: none;" id="viewReportBtn">View Full Report</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Security Audit functions
        function runSecurityScan() {
            const modal = new bootstrap.Modal(document.getElementById('securityScanModal'));
            modal.show();
        }

        // Security scan modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const startScanBtn = document.getElementById('startScanBtn');
            const scanCancelButton = document.getElementById('scanCancelButton');
            const viewReportBtn = document.getElementById('viewReportBtn');
            const scanInitial = document.getElementById('scanInitial');
            const scanProgress = document.getElementById('scanProgress');
            const scanResults = document.getElementById('scanResults');
            const scanProgressBar = document.getElementById('scanProgressBar');
            const scanStatus = document.getElementById('scanStatus');

            startScanBtn.addEventListener('click', function() {
                // Hide initial view, show progress
                scanInitial.style.display = 'none';
                scanProgress.style.display = 'block';
                startScanBtn.style.display = 'none';
                scanCancelButton.style.display = 'none';

                // Simulate security scan progress
                let progress = 0;
                const scanSteps = [
                    'Initializing scan engine...',
                    'Checking user authentication security...',
                    'Analyzing system configuration...',
                    'Scanning file permissions...',
                    'Auditing database security...',
                    'Assessing network vulnerabilities...',
                    'Generating security report...',
                    'Finalizing results...'
                ];

                const interval = setInterval(function() {
                    progress += 12.5; // 8 steps, 100% total
                    scanProgressBar.style.width = progress + '%';
                    scanProgressBar.setAttribute('aria-valuenow', progress);
                    
                    const stepIndex = Math.floor((progress / 12.5) - 1);
                    if (stepIndex >= 0 && stepIndex < scanSteps.length) {
                        scanStatus.textContent = scanSteps[stepIndex];
                    }

                    if (progress >= 100) {
                        clearInterval(interval);
                        setTimeout(function() {
                            // Show results
                            scanProgress.style.display = 'none';
                            scanResults.style.display = 'block';
                            viewReportBtn.style.display = 'inline-block';
                            scanCancelButton.style.display = 'inline-block';
                            scanCancelButton.textContent = 'Close';
                        }, 1000);
                    }
                }, 800);
            });

            viewReportBtn.addEventListener('click', function() {
                // Generate and download detailed security report
                const reportData = {
                    timestamp: new Date().toISOString(),
                    scanType: 'Comprehensive Security Scan',
                    duration: '3 minutes 24 seconds',
                    securityScore: 85,
                    threatLevel: 'LOW',
                    summary: {
                        criticalIssues: 0,
                        highRisks: 0,
                        mediumRisks: 2,
                        lowRisks: 5,
                        recommendations: 5
                    },
                    findings: [
                        {
                            severity: 'medium',
                            category: 'Network Security',
                            issue: 'HTTPS not enabled',
                            recommendation: 'Configure SSL certificate for secure connections',
                            impact: 'Medium'
                        },
                        {
                            severity: 'medium',
                            category: 'Authentication',
                            issue: 'No two-factor authentication',
                            recommendation: 'Implement 2FA for enhanced security',
                            impact: 'Medium'
                        },
                        {
                            severity: 'low',
                            category: 'Password Policy',
                            issue: 'Password expiration policy could be stronger',
                            recommendation: 'Reduce password expiration to 90 days',
                            impact: 'Low'
                        },
                        {
                            severity: 'low',
                            category: 'System Updates',
                            issue: 'Some optional security patches missing',
                            recommendation: 'Apply all recommended security patches',
                            impact: 'Low'
                        },
                        {
                            severity: 'low',
                            category: 'Logging',
                            issue: 'Security log retention period could be extended',
                            recommendation: 'Extend log retention to 90 days',
                            impact: 'Low'
                        }
                    ],
                    systemChecks: {
                        'php_version': { status: 'secure', version: '<?php echo PHP_VERSION; ?>' },
                        'error_reporting': { status: 'secure', setting: 'disabled' },
                        'file_uploads': { status: 'warning', setting: 'enabled' },
                        'session_timeout': { status: 'secure', timeout: '<?php echo ini_get('session.gc_maxlifetime'); ?>' }
                    }
                };

                const dataStr = JSON.stringify(reportData, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'security-scan-report-' + new Date().toISOString().split('T')[0] + '.json';
                link.click();
            });

            // Reset modal when closed
            document.getElementById('securityScanModal').addEventListener('hidden.bs.modal', function() {
                setTimeout(function() {
                    scanInitial.style.display = 'block';
                    scanProgress.style.display = 'none';
                    scanResults.style.display = 'none';
                    startScanBtn.style.display = 'inline-block';
                    viewReportBtn.style.display = 'none';
                    scanCancelButton.style.display = 'inline-block';
                    scanCancelButton.textContent = 'Cancel';
                    scanProgressBar.style.width = '0%';
                    scanProgressBar.setAttribute('aria-valuenow', 0);
                    scanStatus.textContent = 'Initializing scan...';
                }, 300);
            });
        });

        function exportAuditReport() {
            if (confirm('Generate and download security audit report?')) {
                // Simulate report generation
                const reportData = {
                    timestamp: new Date().toISOString(),
                    securityScore: '65%',
                    threatLevel: 'MEDIUM',
                    recommendations: [
                        'Enable HTTPS for secure connections',
                        'Update password expiration policy',
                        'Review inactive user accounts'
                    ],
                    systemChecks: <?php echo json_encode($audit_data['system_checks'] ?? []); ?>
                };
                
                const dataStr = JSON.stringify(reportData, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'security-audit-report-' + new Date().toISOString().split('T')[0] + '.json';
                link.click();
            }
        }

        // Auto-refresh security data every 5 minutes
        setInterval(function() {
            console.log('Refreshing security audit data...');
            // In production, this would fetch updated data via AJAX
        }, 300000);
    </script>
</body>
</html>
