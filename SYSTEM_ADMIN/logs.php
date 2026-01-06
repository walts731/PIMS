<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin' && $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

// Check if system_logs table exists
$table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
if ($table_check->num_rows === 0) {
    // Create system_logs table if it doesn't exist
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS `system_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `action` varchar(100) NOT NULL,
            `module` varchar(50) NOT NULL,
            `description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_action` (`action`),
            KEY `idx_module` (`module`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    if ($conn->query($create_table_sql)) {
        $table_created = true;
    } else {
        $table_error = "Failed to create system_logs table: " . $conn->error;
    }
}

// Handle clear logs action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $range = $_POST['range'] ?? 'all';
    $clear_message = '';
    
    // Check if table exists before attempting operations
    $table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
    if ($table_check->num_rows === 0) {
        $clear_message = "Error: system_logs table does not exist. Please run database setup first.";
    } else {
        try {
            // Build WHERE clause based on range
            $where_conditions = [];
            $params = [];
            $types = '';
            
            switch ($range) {
                case 'all':
                    try {
                        // Use DELETE instead of TRUNCATE for clearing all logs
                        $result = $conn->query("DELETE FROM system_logs");
                        if ($result) {
                            $clear_message = "Successfully cleared all system logs.";
                        } else {
                            $clear_message = "Failed to clear logs: " . $conn->error;
                        }
                    } catch (Exception $delete_error) {
                        $clear_message = "Failed to clear logs: " . $delete_error->getMessage();
                    }
                    break;
            case 'older_than_30':
                $where_conditions[] = "timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'older_than_90':
                $where_conditions[] = "timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)";
                break;
            case 'older_than_365':
                $where_conditions[] = "timestamp < DATE_SUB(NOW(), INTERVAL 365 DAY)";
                break;
            default:
                $clear_message = 'Invalid clear range';
        }
        
        // For partial deletions, use direct DELETE
        if (!empty($where_conditions)) {
            try {
                $delete_sql = "DELETE FROM system_logs WHERE " . implode(' AND ', $where_conditions);
                
                $stmt = $conn->prepare($delete_sql);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                if ($stmt->execute()) {
                    $actual_deleted = $stmt->affected_rows;
                    $stmt->close();
                    $clear_message = "Successfully deleted {$actual_deleted} log entries.";
                } else {
                    $clear_message = 'Failed to clear logs';
                }
            } catch (Exception $partial_error) {
                $clear_message = 'Partial deletion failed';
            }
        }
        
        // Redirect back with message
        header('Location: logs.php?message=' . urlencode($clear_message));
        exit();
        
    } catch (Exception $e) {
        error_log("Clear logs error: " . $e->getMessage());
        header('Location: logs.php?message=' . urlencode('Database error occurred'));
        exit();
    }
    }
}

// Function to log system actions
function logSystemAction($user_id, $action, $module, $details = null) {
    global $conn;
    
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO system_logs (user_id, action, module, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $user_id, $action, $module, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log system action: " . $e->getMessage());
        return false;
    }
}

// Get all logs for DataTables
$logs = [];
$total_logs = 0;

try {
    // Get all logs without pagination for DataTables
    $sql = "
        SELECT sl.*, u.first_name, u.last_name, u.username 
        FROM system_logs sl 
        LEFT JOIN users u ON sl.user_id = u.id 
        ORDER BY sl.timestamp DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $total_logs = count($logs);
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error fetching logs: " . $e->getMessage());
}

// Get users for filter dropdown (keeping for potential future use)
$users = [];
try {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, username FROM users ORDER BY first_name, last_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
}

// Get unique actions for filter (keeping for potential future use)
$actions = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT action FROM system_logs ORDER BY action");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $actions[] = $row['action'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching actions: " . $e->getMessage());
}

// Get unique modules for filter (keeping for potential future use)
$modules = [];
try {
    $stmt = $conn->prepare("SELECT DISTINCT module FROM system_logs ORDER BY module");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row['module'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching modules: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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
        
        .log-entry {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .log-entry:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .log-entry.error {
            border-left-color: #dc3545;
        }
        
        .log-entry.warning {
            border-left-color: #ffc107;
        }
        
        .log-entry.success {
            border-left-color: #28a745;
        }
        
        .log-entry.info {
            border-left-color: #17a2b8;
        }
        
        .action-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .filter-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
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
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'System Logs';
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
                            <i class="bi bi-clock-history"></i> System Logs
                        </h1>
                        <p class="text-muted mb-0">Monitor system activities and audit trail</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-danger btn-sm" onclick="clearLogs()">
                                <i class="bi bi-trash"></i> Clear Logs
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="exportLogs()">
                                <i class="bi bi-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Message Display -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle"></i>
                    <?php echo htmlspecialchars($_GET['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Logs Display -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-lg rounded-4">
                        <div class="card-header bg-primary text-white rounded-top-4">
                            <h6 class="mb-0">
                                <i class="bi bi-clock-history"></i> System Logs 
                                <span class="badge bg-light text-dark ms-2"><?php echo number_format($total_logs); ?> Total</span>
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($logs)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-clock-history fs-1 text-muted"></i>
                                    <p class="text-muted mt-3">No logs found</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="logsTable" class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Action</th>
                                                <th>Module</th>
                                                <th>User</th>
                                                <th>Details</th>
                                                <th>Date/Time</th>
                                                <th>IP Address</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($logs as $log): ?>
                                                <?php
                                                $logClass = 'info';
                                                if (strpos($log['action'], 'delete') !== false || strpos($log['action'], 'error') !== false) {
                                                    $logClass = 'error';
                                                } elseif (strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) {
                                                    $logClass = 'warning';
                                                } elseif (strpos($log['action'], 'create') !== false || strpos($log['action'], 'add') !== false || strpos($log['action'], 'login') !== false) {
                                                    $logClass = 'success';
                                                }
                                                ?>
                                                <tr class="log-entry-<?php echo $logClass; ?>">
                                                    <td>
                                                        <span class="action-badge bg-<?php echo $logClass === 'error' ? 'danger' : ($logClass === 'warning' ? 'warning' : ($logClass === 'success' ? 'success' : 'info')); ?> text-white">
                                                            <?php echo htmlspecialchars($log['action']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars(ucfirst($log['module'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        if ($log['user_id']) {
                                                            echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name'] . ' (@' . $log['username'] . ')');
                                                        } else {
                                                            echo 'System';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo !empty($log['details']) ? htmlspecialchars(substr($log['details'], 0, 100)) . (strlen($log['details']) > 100 ? '...' : '') : 'No details'; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M j, Y H:i:s', strtotime($log['timestamp'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails(<?php echo $log['id']; ?>)">
                                                            <i class="bi bi-eye"></i> Details
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DataTables handles pagination automatically -->
        </div>
    </div>
    
    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-labelledby="logDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="logDetailsModalLabel">
                        <i class="bi bi-clock-history"></i> Log Entry Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="logDetailsContent">
                        <!-- Log details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Logs Modal -->
    <div class="modal fade" id="exportLogsModal" tabindex="-1" aria-labelledby="exportLogsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="exportLogsModalLabel">
                        <i class="bi bi-download"></i> Export System Logs
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Choose your export format and options:</p>
                    <div class="mb-3">
                        <label for="exportFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="csv">CSV (Comma Separated Values)</option>
                            <option value="excel">Excel (XLSX)</option>
                            <option value="json">JSON</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exportDateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="exportDateRange">
                            <option value="all">All Logs</option>
                            <option value="today">Today</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="customDateRange" class="mb-3" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="exportDateFrom" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="exportDateFrom">
                            </div>
                            <div class="col-md-6">
                                <label for="exportDateTo" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="exportDateTo">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeUserAgent" checked>
                            <label class="form-check-label" for="includeUserAgent">
                                Include User Agent Information
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="performExport()">
                        <i class="bi bi-download"></i> Export Logs
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear Logs Modal -->
    <div class="modal fade" id="clearLogsModal" tabindex="-1" aria-labelledby="clearLogsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="logs.php">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="clearLogsModalLabel">
                            <i class="bi bi-exclamation-triangle"></i> Clear System Logs
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>
                        <p>Are you sure you want to clear the system logs? This will permanently delete all log entries.</p>
                        <div class="mb-3">
                            <label for="clearLogsRange" class="form-label">Clear Range</label>
                            <select class="form-select" id="clearLogsRange" name="range">
                                <option value="all">All Logs</option>
                                <option value="older_than_30">Older than 30 days</option>
                                <option value="older_than_90">Older than 90 days</option>
                                <option value="older_than_365">Older than 1 year</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmClear" onchange="toggleClearButton()">
                                <label class="form-check-label" for="confirmClear">
                                    I understand that this action cannot be undone
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="clear_logs" value="1" class="btn btn-danger" id="confirmClearBtn" disabled>
                            <i class="bi bi-trash"></i> Clear Logs
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        $(document).ready(function() {
            // Initialize DataTables
            $('#logsTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[4, 'desc']], // Sort by date/time column by default
                columnDefs: [
                    { targets: 0, width: '120px' }, // Action
                    { targets: 1, width: '100px' }, // Module
                    { targets: 2, width: '200px' }, // User
                    { targets: 3, width: '300px' }, // Details
                    { targets: 4, width: '150px' }, // Date/Time
                    { targets: 5, width: '120px' }, // IP Address
                    { targets: 6, width: '80px', orderable: false } // Actions
                ],
                language: {
                    search: "Search logs:",
                    lengthMenu: "Show _MENU_ logs per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ logs",
                    infoEmpty: "Showing 0 to 0 of 0 logs",
                    infoFiltered: "(filtered from _MAX_ total logs)",
                    zeroRecords: "No matching logs found",
                    emptyTable: "No logs available in table",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
            
            // Export date range toggle
            $('#exportDateRange').change(function() {
                if ($(this).val() === 'custom') {
                    $('#customDateRange').show();
                } else {
                    $('#customDateRange').hide();
                }
            });
            
            // Clear logs confirmation toggle
            $('#confirmClear').change(function() {
                $('#confirmClearBtn').prop('disabled', !$(this).is(':checked'));
            });
        });
        
        // View log details
        function viewLogDetails(logId) {
            // Fetch log details via AJAX
            $.ajax({
                url: 'ajax/get_log_details.php',
                method: 'POST',
                data: { log_id: logId },
                success: function(response) {
                    const logData = JSON.parse(response);
                    if (logData.success) {
                        const log = logData.log;
                        const logClass = getLogClass(log.action);
                        
                        const detailsHtml = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Basic Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>ID:</strong></td><td>${log.id}</td></tr>
                                        <tr><td><strong>Action:</strong></td><td><span class="badge bg-${getBadgeColor(logClass)}">${log.action}</span></td></tr>
                                        <tr><td><strong>Module:</strong></td><td><span class="badge bg-secondary">${log.module}</span></td></tr>
                                        <tr><td><strong>Date/Time:</strong></td><td>${formatDateTime(log.timestamp)}</td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">User Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>Name:</strong></td><td>${log.user_id ? log.first_name + ' ' + log.last_name : 'System'}</td></tr>
                                        <tr><td><strong>Username:</strong></td><td>${log.username || 'N/A'}</td></tr>
                                        <tr><td><strong>User ID:</strong></td><td>${log.user_id || 'N/A'}</td></tr>
                                    </table>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6 class="text-primary">Details</h6>
                                    <div class="alert alert-light">
                                        ${log.details || 'No details available'}
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary">Network Information</h6>
                                    <table class="table table-sm">
                                        <tr><td><strong>IP Address:</strong></td><td>${log.ip_address}</td></tr>
                                        <tr><td><strong>User Agent:</strong></td><td><small>${log.user_agent}</small></td></tr>
                                    </table>
                                </div>
                            </div>
                        `;
                        
                        $('#logDetailsContent').html(detailsHtml);
                        $('#logDetailsModal').modal('show');
                    } else {
                        alert('Error loading log details: ' + logData.message);
                    }
                },
                error: function() {
                    alert('Error loading log details. Please try again.');
                }
            });
        }
        
        // Helper functions
        function getLogClass(action) {
            if (action.includes('delete') || action.includes('error')) return 'error';
            if (action.includes('update') || action.includes('edit')) return 'warning';
            if (action.includes('create') || action.includes('add') || action.includes('login')) return 'success';
            return 'info';
        }
        
        function getBadgeColor(logClass) {
            switch(logClass) {
                case 'error': return 'danger';
                case 'warning': return 'warning';
                case 'success': return 'success';
                default: return 'info';
            }
        }
        
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        // Export logs
        function exportLogs() {
            $('#exportLogsModal').modal('show');
        }
        
        function performExport() {
            const format = $('#exportFormat').val();
            const dateRange = $('#exportDateRange').val();
            const includeUserAgent = $('#includeUserAgent').is(':checked');
            const dateFrom = $('#exportDateFrom').val();
            const dateTo = $('#exportDateTo').val();
            
            // Show loading state
            const exportBtn = event.target;
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';
            exportBtn.disabled = true;
            
            // Create export parameters
            const params = new URLSearchParams({
                format: format,
                date_range: dateRange,
                include_user_agent: includeUserAgent ? '1' : '0'
            });
            
            if (dateRange === 'custom' && dateFrom && dateTo) {
                params.append('date_from', dateFrom);
                params.append('date_to', dateTo);
            }
            
            // Download the file
            window.location.href = 'ajax/export_logs.php?' + params.toString();
            
            // Reset button after delay
            setTimeout(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
                $('#exportLogsModal').modal('hide');
            }, 2000);
        }
        
        // Clear logs
        function clearLogs() {
            $('#clearLogsModal').modal('show');
        }
        
        function toggleClearButton() {
            const confirmChecked = $('#confirmClear').is(':checked');
            $('#confirmClearBtn').prop('disabled', !confirmChecked);
        }
    </script>
</body>
</html>
