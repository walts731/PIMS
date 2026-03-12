<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../login.php');
    exit();
}

// Include required files
require_once '../config.php';
require_once '../includes/logger.php';
require_once 'includes/NotificationBatchScheduler.php';

// Set page title
$page_title = 'Notification Batch Management';

// Get current filter and parameters
$action = $_GET['action'] ?? 'dashboard';
$batch_id = $_GET['batch_id'] ?? null;
$office_id = $_SESSION['office_id'];
$user_id = $_SESSION['user_id'];

// Initialize scheduler
$scheduler = new NotificationBatchScheduler($office_id, $user_id);

// Handle actions
$message = '';
$message_type = '';

if ($_POST) {
    try {
        switch ($_POST['action']) {
            case 'process_batch':
                if ($batch_id) {
                    $result = $scheduler->processBatch($batch_id);
                    $message = "Batch processed successfully: {$result['successful']} notifications sent, {$result['failed']} failed";
                    $message_type = 'success';
                }
                break;
                
            case 'run_scheduler':
                $results = $scheduler->runScheduler(true);
                $message = "Scheduler run completed: {$results['summary']['batches_processed']} batches processed";
                $message_type = 'success';
                break;
                
            case 'create_scheduled_batch':
                $schedule_time = $_POST['schedule_time'];
                $batch_name = $_POST['batch_name'] ?? null;
                
                // Get notifications data from form (simplified example)
                $notifications_data = [
                    [
                        'user_id' => $user_id,
                        'title' => $_POST['title'],
                        'message' => $_POST['message'],
                        'type' => $_POST['type'],
                        'priority' => $_POST['priority'],
                        'related_id' => $_POST['related_id'] ?? null,
                        'related_type' => $_POST['related_type'] ?? null
                    ]
                ];
                
                $result = $scheduler->createScheduledBatch($notifications_data, $schedule_time, $batch_name);
                $message = "Scheduled batch created: {$result['notifications_queued']} notifications queued for {$result['scheduled_for']}";
                $message_type = 'success';
                break;
                
            case 'cancel_batch':
                if ($batch_id) {
                    $sql = "UPDATE notification_batches SET status = 'cancelled' WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('i', $batch_id);
                    $stmt->execute();
                    $message = "Batch cancelled successfully";
                    $message_type = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get data based on action
switch ($action) {
    case 'dashboard':
        $stats = $scheduler->getSchedulerStatus();
        $recent_batches = getRecentBatches($office_id);
        $metrics = $scheduler->getBatchStatistics(7);
        break;
        
    case 'batches':
        $batches = getBatches($office_id, $_GET);
        break;
        
    case 'batch_detail':
        if ($batch_id) {
            $batch = getBatchDetail($batch_id);
            $queue_items = getBatchQueueItems($batch_id);
        }
        break;
        
    case 'rules':
        $rules = getBatchRules($office_id);
        break;
        
    case 'logs':
        $logs = getBatchLogs($batch_id);
        break;
        
    case 'metrics':
        $metrics = $scheduler->getBatchStatistics(30);
        break;
}

// Helper functions
function getRecentBatches($office_id) {
    global $conn;
    
    $sql = "SELECT nb.*, u.username as created_by_name
            FROM notification_batches nb
            LEFT JOIN users u ON nb.created_by = u.id
            WHERE nb.created_by IN (SELECT id FROM users WHERE office = ?)
            ORDER BY nb.created_at DESC
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    
    return $batches;
}

function getBatches($office_id, $filters) {
    global $conn;
    
    $status_filter = $filters['status'] ?? 'all';
    $type_filter = $filters['type'] ?? 'all';
    $page = max(1, intval($filters['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    $where_conditions = ["nb.created_by IN (SELECT id FROM users WHERE office = ?)"];
    $params = [$office_id];
    
    if ($status_filter !== 'all') {
        $where_conditions[] = "nb.status = ?";
        $params[] = $status_filter;
    }
    
    if ($type_filter !== 'all') {
        $where_conditions[] = "nb.batch_type = ?";
        $params[] = $type_filter;
    }
    
    $where_clause = "WHERE " . implode(' AND ', $where_conditions);
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM notification_batches nb $where_clause";
    $count_stmt = $conn->prepare($count_sql);
    
    if (!empty($params)) {
        $types = str_repeat('i', count($params));
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_batches = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_batches / $per_page);
    
    // Get batches
    $sql = "SELECT nb.*, u.username as created_by_name
            FROM notification_batches nb
            LEFT JOIN users u ON nb.created_by = u.id
            $where_clause
            ORDER BY nb.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $per_page;
    $params[] = $offset;
    $types = str_repeat('i', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $batches = [];
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row;
    }
    
    return [
        'batches' => $batches,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_batches
        ]
    ];
}

function getBatchDetail($batch_id) {
    global $conn;
    
    $sql = "SELECT nb.*, u.username as created_by_name
            FROM notification_batches nb
            LEFT JOIN users u ON nb.created_by = u.id
            WHERE nb.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $batch_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

function getBatchQueueItems($batch_id) {
    global $conn;
    
    $sql = "SELECT nq.*, u.username as user_name
            FROM notification_queue nq
            LEFT JOIN users u ON nq.user_id = u.id
            WHERE nq.batch_id = ?
            ORDER BY nq.priority_score DESC, nq.created_at ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $batch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getBatchRules($office_id) {
    global $conn;
    
    $sql = "SELECT nbr.*, u.username as created_by_name
            FROM notification_batch_rules nbr
            LEFT JOIN users u ON nbr.created_by = u.id
            WHERE nbr.office_id = ? OR nbr.office_id IS NULL
            ORDER BY nbr.notification_type, nbr.office_id DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $office_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rules = [];
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
    }
    
    return $rules;
}

function getBatchLogs($batch_id = null) {
    global $conn;
    
    $where_clause = $batch_id ? "WHERE batch_id = ?" : "WHERE batch_id IS NULL";
    $params = $batch_id ? [$batch_id] : [];
    
    $sql = "SELECT nbl.* 
            FROM notification_batch_logs nbl
            $where_clause
            ORDER BY nbl.created_at DESC
            LIMIT 100";
    
    $stmt = $conn->prepare($sql);
    
    if ($params) {
        $stmt->bind_param('i', $params[0]);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    return $logs;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
    <link href="dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">
    
    <style>
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .status-pending { background: #ffc107; color: #212529; }
        .status-processing { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-failed { background: #dc3545; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        
        .priority-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .priority-critical { background: #dc3545; color: white; }
        .priority-high { background: #fd7e14; color: white; }
        .priority-medium { background: #ffc107; color: #212529; }
        .priority-low { background: #28a745; color: white; }
        
        .batch-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            transition: var(--transition);
            border-left: 4px solid #e0e0e0;
        }
        
        .batch-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .batch-card.priority-critical { border-left-color: #dc3545; }
        .batch-card.priority-high { border-left-color: #fd7e14; }
        .batch-card.priority-medium { border-left-color: #ffc107; }
        .batch-card.priority-low { border-left-color: #28a745; }
        
        .metric-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--dark-color);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .nav-tabs .nav-link:hover {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .nav-tabs .nav-link.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }
        
        .log-entry {
            padding: 0.75rem;
            border-left: 3px solid #e0e0e0;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            border-radius: 0 0.25rem 0.25rem 0;
        }
        
        .log-entry.debug { border-left-color: #6c757d; }
        .log-entry.info { border-left-color: #17a2b8; }
        .log-entry.warning { border-left-color: #ffc107; }
        .log-entry.error { border-left-color: #dc3545; }
        .log-entry.critical { border-left-color: #721c24; }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Topbar -->
            <?php require_once 'includes/topbar.php'; ?>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2">Notification Batch Management</h2>
                            <p class="text-muted mb-0">Manage and monitor notification batching system</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary" onclick="runScheduler()">
                                <i class="bi bi-play-circle"></i> Run Scheduler
                            </button>
                            <a href="?action=rules" class="btn btn-outline-secondary">
                                <i class="bi bi-gear"></i> Rules
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Message Alert -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'batches' ? 'active' : ''; ?>" href="?action=batches">
                            <i class="bi bi-stack"></i> Batches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'rules' ? 'active' : ''; ?>" href="?action=rules">
                            <i class="bi bi-gear"></i> Rules
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'logs' ? 'active' : ''; ?>" href="?action=logs">
                            <i class="bi bi-file-text"></i> Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $action === 'metrics' ? 'active' : ''; ?>" href="?action=metrics">
                            <i class="bi bi-graph-up"></i> Metrics
                        </a>
                    </li>
                </ul>
                
                <!-- Content based on action -->
                <?php switch ($action): case 'dashboard': ?>
                    <!-- Dashboard Content -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $stats['pending_batches']; ?></div>
                                <div class="metric-label">Pending Batches</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $stats['queue_size']; ?></div>
                                <div class="metric-label">Queue Size</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo $stats['is_running'] ? 'Running' : 'Idle'; ?></div>
                                <div class="metric-label">Scheduler Status</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-value"><?php echo count($recent_batches); ?></div>
                                <div class="metric-label">Recent Batches</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Batches -->
                    <h4 class="mb-3">Recent Batches</h4>
                    <?php if (!empty($recent_batches)): ?>
                        <?php foreach ($recent_batches as $batch): ?>
                            <div class="batch-card priority-<?php echo $batch['priority_weight'] > 3 ? 'high' : ($batch['priority_weight'] > 2 ? 'medium' : 'low'); ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($batch['batch_name']); ?></h6>
                                        <p class="text-muted mb-2"><?php echo htmlspecialchars($batch['total_notifications']); ?> notifications</p>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?php echo date('M j, Y H:i', strtotime($batch['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge status-<?php echo $batch['status']; ?>">
                                            <?php echo $batch['status']; ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo $batch['batch_type']; ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted">No recent batches found</p>
                        </div>
                    <?php endif; ?>
                    
                <?php break; case 'batches': ?>
                    <!-- Batches List -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Notification Batches</h4>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='?action=batches&status='+this.value">
                                <option value="all" <?php echo ($_GET['status'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($_GET['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo ($_GET['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                            <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href='?action=batches&type='+this.value">
                                <option value="all" <?php echo ($_GET['type'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Types</option>
                                <option value="immediate" <?php echo ($_GET['type'] ?? '') === 'immediate' ? 'selected' : ''; ?>>Immediate</option>
                                <option value="scheduled" <?php echo ($_GET['type'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                <option value="periodic" <?php echo ($_GET['type'] ?? '') === 'periodic' ? 'selected' : ''; ?>>Periodic</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (!empty($batches['batches'])): ?>
                        <?php foreach ($batches['batches'] as $batch): ?>
                            <div class="batch-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <h6 class="mb-1">
                                                    <a href="?action=batch_detail&batch_id=<?php echo $batch['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($batch['batch_name']); ?>
                                                    </a>
                                                </h6>
                                                <p class="text-muted mb-2">
                                                    <?php echo $batch['total_notifications']; ?> notifications
                                                    <?php if ($batch['processed_notifications'] > 0): ?>
                                                        (<?php echo $batch['processed_notifications']; ?> processed)
                                                    <?php endif; ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($batch['created_by_name']); ?>
                                                    <i class="bi bi-clock ms-2"></i> <?php echo date('M j, Y H:i', strtotime($batch['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge status-<?php echo $batch['status']; ?>">
                                            <?php echo $batch['status']; ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo $batch['batch_type']; ?></small>
                                        <div class="mt-2">
                                            <?php if ($batch['status'] === 'pending'): ?>
                                                <button class="btn btn-sm btn-primary" onclick="processBatch(<?php echo $batch['id']; ?>)">
                                                    <i class="bi bi-play"></i> Process
                                                </button>
                                            <?php endif; ?>
                                            <?php if (in_array($batch['status'], ['pending', 'processing'])): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="cancelBatch(<?php echo $batch['id']; ?>)">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if ($batches['pagination']['total_pages'] > 1): ?>
                            <nav aria-label="Batches pagination">
                                <ul class="pagination justify-content-center">
                                    <!-- Previous -->
                                    <?php if ($batches['pagination']['current_page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?action=batches&page=<?php echo $batches['pagination']['current_page'] - 1; ?>">
                                                <i class="bi bi-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="bi bi-chevron-left"></i> Previous</span>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page numbers -->
                                    <?php for ($i = 1; $i <= $batches['pagination']['total_pages']; $i++): ?>
                                        <?php if ($i == $batches['pagination']['current_page']): ?>
                                            <li class="page-item active">
                                                <span class="page-link"><?php echo $i; ?></span>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?action=batches&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <!-- Next -->
                                    <?php if ($batches['pagination']['current_page'] < $batches['pagination']['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?action=batches&page=<?php echo $batches['pagination']['current_page'] + 1; ?>">
                                                Next <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">Next <i class="bi bi-chevron-right"></i></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-stack" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted">No batches found</p>
                        </div>
                    <?php endif; ?>
                    
                <?php break; case 'batch_detail': ?>
                    <!-- Batch Detail -->
                    <?php if ($batch): ?>
                        <div class="batch-card">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($batch['batch_name']); ?></h4>
                                    <p class="text-muted">
                                        Created by <?php echo htmlspecialchars($batch['created_by_name']); ?> on 
                                        <?php echo date('M j, Y H:i:s', strtotime($batch['created_at'])); ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="status-badge status-<?php echo $batch['status']; ?>">
                                        <?php echo $batch['status']; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo $batch['batch_type']; ?></small>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <strong>Total Notifications:</strong><br>
                                    <?php echo $batch['total_notifications']; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Processed:</strong><br>
                                    <?php echo $batch['processed_notifications']; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Failed:</strong><br>
                                    <?php echo $batch['failed_notifications']; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Processing Time:</strong><br>
                                    <?php echo $batch['processing_time_ms'] ? $batch['processing_time_ms'] . 'ms' : 'N/A'; ?>
                                </div>
                            </div>
                            
                            <?php if ($batch['error_message']): ?>
                                <div class="alert alert-danger mt-3">
                                    <strong>Error:</strong> <?php echo htmlspecialchars($batch['error_message']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <?php if ($batch['status'] === 'pending'): ?>
                                    <button class="btn btn-primary" onclick="processBatch(<?php echo $batch['id']; ?>)">
                                        <i class="bi bi-play"></i> Process Batch
                                    </button>
                                <?php endif; ?>
                                <?php if (in_array($batch['status'], ['pending', 'processing'])): ?>
                                    <button class="btn btn-outline-danger" onclick="cancelBatch(<?php echo $batch['id']; ?>)">
                                        <i class="bi bi-x-circle"></i> Cancel Batch
                                    </button>
                                <?php endif; ?>
                                <a href="?action=logs&batch_id=<?php echo $batch['id']; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-file-text"></i> View Logs
                                </a>
                            </div>
                        </div>
                        
                        <!-- Queue Items -->
                        <h5 class="mt-4 mb-3">Queue Items</h5>
                        <?php if (!empty($queue_items)): ?>
                            <?php foreach ($queue_items as $item): ?>
                                <div class="batch-card">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                                            <p class="text-muted mb-2"><?php echo htmlspecialchars($item['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($item['user_name']); ?>
                                                <i class="bi bi-clock ms-2"></i> <?php echo date('M j, Y H:i', strtotime($item['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="status-badge status-<?php echo $item['status']; ?>">
                                                <?php echo $item['status']; ?>
                                            </span>
                                            <br>
                                            <span class="priority-badge priority-<?php echo $item['priority']; ?>">
                                                <?php echo $item['priority']; ?>
                                            </span>
                                            <?php if ($item['attempts'] > 1): ?>
                                                <br>
                                                <small class="text-muted"><?php echo $item['attempts']; ?> attempts</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($item['error_message']): ?>
                                        <div class="alert alert-danger mt-2 mb-0">
                                            <small><?php echo htmlspecialchars($item['error_message']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted">No queue items found</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">Batch not found</div>
                    <?php endif; ?>
                    
                <?php break; case 'rules': ?>
                    <!-- Batch Rules -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>Batch Processing Rules</h4>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRuleModal">
                            <i class="bi bi-plus"></i> Add Rule
                        </button>
                    </div>
                    
                    <?php if (!empty($rules)): ?>
                        <?php foreach ($rules as $rule): ?>
                            <div class="batch-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($rule['rule_name']); ?></h6>
                                        <p class="text-muted mb-2">
                                            Type: <strong><?php echo $rule['notification_type']; ?></strong>
                                            <?php if ($rule['office_id']): ?>
                                                (Office Specific)
                                            <?php else: ?>
                                                (Global)
                                            <?php endif; ?>
                                        </p>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <small>Batch Size: <strong><?php echo $rule['batch_size']; ?></strong></small>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Interval: <strong><?php echo $rule['batch_interval_minutes']; ?> min</strong></small>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Max/Hour: <strong><?php echo $rule['max_batch_per_hour']; ?></strong></small>
                                            </div>
                                            <div class="col-md-3">
                                                <small>Threshold: <strong><?php echo $rule['priority_threshold']; ?></strong></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   <?php echo $rule['enable_batching'] ? 'checked' : ''; ?>
                                                   onchange="toggleRule(<?php echo $rule['id']; ?>, this.checked)">
                                            <label class="form-check-label">Enabled</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-gear" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted">No batch rules found</p>
                        </div>
                    <?php endif; ?>
                    
                <?php break; case 'logs': ?>
                    <!-- Logs -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4>System Logs</h4>
                        <button class="btn btn-outline-secondary" onclick="refreshLogs()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                    
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <div class="log-entry <?php echo $log['log_level']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($log['message']); ?></strong>
                                        <?php if ($log['context_data']): ?>
                                            <pre class="mt-2 mb-0"><small><?php echo htmlspecialchars($log['context_data']); ?></small></pre>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-text" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted">No logs found</p>
                        </div>
                    <?php endif; ?>
                    
                <?php break; case 'metrics': ?>
                    <!-- Metrics -->
                    <h4 class="mb-3">Performance Metrics (Last 30 Days)</h4>
                    <?php if (!empty($metrics)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Batches</th>
                                        <th>Success Rate</th>
                                        <th>Total Notifications</th>
                                        <th>Avg Processing Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($metrics as $metric): ?>
                                        <tr>
                                            <td><?php echo date('M j, Y', strtotime($metric['date'])); ?></td>
                                            <td><?php echo $metric['total_batches']; ?></td>
                                            <td>
                                                <?php if ($metric['total_batches'] > 0): ?>
                                                    <?php echo round(($metric['successful_batches'] / $metric['total_batches']) * 100, 1); ?>%
                                                <?php else: ?>
                                                    0%
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $metric['total_notifications']; ?></td>
                                            <td><?php echo $metric['average_processing_time_ms'] ? $metric['average_processing_time_ms'] . 'ms' : 'N/A'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-graph-up" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted">No metrics data available</p>
                        </div>
                    <?php endif; ?>
                    
                <?php endswitch; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Rule Modal -->
    <div class="modal fade" id="addRuleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Batch Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_rule">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Rule Name</label>
                                    <input type="text" class="form-control" name="rule_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Notification Type</label>
                                    <select class="form-select" name="notification_type" required>
                                        <option value="info">Info</option>
                                        <option value="success">Success</option>
                                        <option value="warning">Warning</option>
                                        <option value="error">Error</option>
                                        <option value="system">System</option>
                                        <option value="low_stock">Low Stock</option>
                                        <option value="new_request">New Request</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="consumption">Consumption</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Batch Size</label>
                                    <input type="number" class="form-control" name="batch_size" value="50" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Interval (minutes)</label>
                                    <input type="number" class="form-control" name="batch_interval_minutes" value="15" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Max Batches/Hour</label>
                                    <input type="number" class="form-control" name="max_batch_per_hour" value="10" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Priority Threshold</label>
                                    <select class="form-select" name="priority_threshold" required>
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Rule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
        function runScheduler() {
            if (confirm('Are you sure you want to run the scheduler manually?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="run_scheduler">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function processBatch(batchId) {
            if (confirm('Are you sure you want to process this batch?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="process_batch"><input type="hidden" name="batch_id" value="' + batchId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function cancelBatch(batchId) {
            if (confirm('Are you sure you want to cancel this batch?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="cancel_batch"><input type="hidden" name="batch_id" value="' + batchId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function toggleRule(ruleId, enabled) {
            // This would typically make an AJAX call to update the rule
            console.log('Toggle rule', ruleId, enabled);
            location.reload();
        }
        
        function refreshLogs() {
            location.reload();
        }
    </script>
</body>
</html>
