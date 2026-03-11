<?php
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

// Set page title
$page_title = 'Notifications';

// Get current filter and search parameters
$type_filter = $_GET['type'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get notifications for current user
$office_id = $_SESSION['office_id'];
$user_id = $_SESSION['user_id'];

// Build query
$where_conditions = ["n.user_id = ?"];
$params = [$user_id];

if ($type_filter !== 'all') {
    $where_conditions[] = "n.type = ?";
    $params[] = $type_filter;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "n.priority = ?";
    $params[] = $priority_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(n.title LIKE ? OR n.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM notifications n $where_clause";
$count_stmt = $conn->prepare($count_sql);

// Build parameter types and values for count query
$count_param_types = '';
$count_param_values = [];

// Add user_id parameter
$count_param_types .= 'i';
$count_param_values[] = $user_id;

// Add type filter if exists
if ($type_filter !== 'all') {
    $count_param_types .= 's';
    $count_param_values[] = $type_filter;
}

// Add priority filter if exists
if ($priority_filter !== 'all') {
    $count_param_types .= 's';
    $count_param_values[] = $priority_filter;
}

// Add search parameters if exists
if (!empty($search)) {
    $count_param_types .= 'ss';
    $count_param_values[] = "%$search%";
    $count_param_values[] = "%$search%";
}

// Bind parameters for count query
if (!empty($count_param_values)) {
    $count_stmt->bind_param($count_param_types, ...$count_param_values);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_notifications = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$sql = "SELECT n.*, 
         CASE 
             WHEN n.is_read = 0 THEN 'unread'
             ELSE 'read'
         END as status
         FROM notifications n 
         $where_clause 
         ORDER BY 
            CASE n.priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END ASC,
            n.created_at DESC 
         LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Bind parameters dynamically based on what we have
$param_types = '';
$param_values = [];

// Add user_id parameter
$param_types .= 'i';
$param_values[] = $user_id;

// Add type filter if exists
if ($type_filter !== 'all') {
    $param_types .= 's';
    $param_values[] = $type_filter;
}

// Add priority filter if exists
if ($priority_filter !== 'all') {
    $param_types .= 's';
    $param_values[] = $priority_filter;
}

// Add search parameters if exists
if (!empty($search)) {
    $param_types .= 'ss';
    $param_values[] = "%$search%";
    $param_values[] = "%$search%";
}

// Add pagination parameters
$param_types .= 'ii';
$param_values[] = $per_page;
$param_values[] = $offset;

$stmt->bind_param($param_types, ...$param_values);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param('i', $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - PIMS<!-- Bootstrap CSS -->
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
        
        .notification-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            transition: var(--transition);
            border-left: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .notification-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--secondary-gradient);
            opacity: 0;
            transition: var(--transition);
        }
        
        .notification-card.unread {
            border-left-color: var(--secondary-color);
            background: linear-gradient(135deg, #f8fcff 0%, #ffffff 100%);
        }
        
        .notification-card.unread::before {
            opacity: 1;
        }
        
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .notification-type-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.3rem;
            box-shadow: var(--shadow-sm);
        }
        
        .notification-type-info { 
            background: var(--primary-gradient); 
            color: white; 
        }
        .notification-type-success { 
            background: var(--success-color); 
            color: white; 
        }
        .notification-type-warning { 
            background: var(--warning-color); 
            color: white; 
        }
        .notification-type-error { 
            background: var(--danger-color); 
            color: white; 
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .filter-tab {
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            color: var(--dark-color);
            text-decoration: none;
            position: relative;
        }
        
        .filter-tab:hover {
            background: rgba(25, 27, 169, 0.05);
            color: var(--primary-color);
        }
        
        .filter-tab.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .search-box {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .search-box .form-control {
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .search-box .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.15);
        }
        
        .search-box .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .search-box .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .search-box .btn-outline-secondary {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .search-box .btn-outline-secondary:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .pagination .page-link {
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            color: var(--dark-color);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .pagination .page-link:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border-color: var(--primary-color);
            color: white;
        }
        
        /* Sidebar logo fix */
        .sidebar-logo {
            width: 40px !important;
            height: 40px !important;
            max-width: 40px !important;
            max-height: 40px !important;
            object-fit: contain !important;
            border-radius: var(--border-radius) !important;
        }
        
        /* Priority Filters */
        .priority-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            flex-wrap: wrap;
        }
        
        .priority-tab {
            padding: 0.75rem 1rem;
            background: transparent;
            border: 2px solid #e0e0e0;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            color: var(--dark-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .priority-tab:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .priority-tab.active {
            color: white;
            border-color: transparent;
            box-shadow: var(--shadow-sm);
        }
        
        .priority-tab.priority-critical {
            border-color: #dc3545;
            color: #dc3545;
        }
        
        .priority-tab.priority-critical.active {
            background: #dc3545;
        }
        
        .priority-tab.priority-high {
            border-color: #fd7e14;
            color: #fd7e14;
        }
        
        .priority-tab.priority-high.active {
            background: #fd7e14;
        }
        
        .priority-tab.priority-medium {
            border-color: #ffc107;
            color: #ffc107;
        }
        
        .priority-tab.priority-medium.active {
            background: #ffc107;
        }
        
        .priority-tab.priority-low {
            border-color: #28a745;
            color: #28a745;
        }
        
        .priority-tab.priority-low.active {
            background: #28a745;
        }
        
        /* Priority Badges */
        .priority-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .priority-badge.priority-critical {
            background: #dc3545;
            color: white;
        }
        
        .priority-badge.priority-high {
            background: #fd7e14;
            color: white;
        }
        
        .priority-badge.priority-medium {
            background: #ffc107;
            color: #212529;
        }
        
        .priority-badge.priority-low {
            background: #28a745;
            color: white;
        }
        
        /* Priority Card Borders */
        .notification-card.priority-critical {
            border-left-color: #dc3545;
            border-left-width: 5px;
        }
        
        .notification-card.priority-high {
            border-left-color: #fd7e14;
            border-left-width: 4px;
        }
        
        .notification-card.priority-medium {
            border-left-color: #ffc107;
            border-left-width: 3px;
        }
        
        .notification-card.priority-low {
            border-left-color: #28a745;
            border-left-width: 3px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 1rem;
            }
            
            .notification-card {
                padding: 1rem;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
                gap: 0.25rem;
                padding: 0.25rem;
            }
            
            .filter-tab {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
            
            .priority-filters {
                gap: 0.25rem;
                padding: 0.25rem;
            }
            
            .priority-tab {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .search-box {
                padding: 1rem;
            }
            
            .notification-type-icon {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .priority-badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.4rem;
            }
        }
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
                        <h2 class="mb-2">Notifications</h2>
                        <p class="text-muted mb-0">
                            <?php echo $unread_count; ?> unread notification(s)
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($unread_count > 0): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="markAllAsRead()">
                                <i class="bi bi-check2-all"></i> Mark All as Read
                            </button>
                        <?php endif; ?>
                        <?php if ($total_notifications > 0): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="clearAllNotifications()">
                                <i class="bi bi-trash3"></i> Clear All
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filter-tabs">
                <a href="?type=all&priority=<?php echo htmlspecialchars($priority_filter); ?>" class="filter-tab <?php echo $type_filter === 'all' ? 'active' : ''; ?>">
                    All Types (<?php echo $total_notifications; ?>)
                </a>
                <a href="?type=info&priority=<?php echo htmlspecialchars($priority_filter); ?>" class="filter-tab <?php echo $type_filter === 'info' ? 'active' : ''; ?>">
                    Info
                </a>
                <a href="?type=success&priority=<?php echo htmlspecialchars($priority_filter); ?>" class="filter-tab <?php echo $type_filter === 'success' ? 'active' : ''; ?>">
                    Success
                </a>
                <a href="?type=warning&priority=<?php echo htmlspecialchars($priority_filter); ?>" class="filter-tab <?php echo $type_filter === 'warning' ? 'active' : ''; ?>">
                    Warning
                </a>
                <a href="?type=error&priority=<?php echo htmlspecialchars($priority_filter); ?>" class="filter-tab <?php echo $type_filter === 'error' ? 'active' : ''; ?>">
                    Error
                </a>
            </div>
            
            <!-- Priority Filters -->
            <div class="priority-filters">
                <a href="?priority=all&type=<?php echo htmlspecialchars($type_filter); ?>" class="priority-tab <?php echo $priority_filter === 'all' ? 'active' : ''; ?>">
                    <i class="bi bi-flag"></i> All Priorities
                </a>
                <a href="?priority=critical&type=<?php echo htmlspecialchars($type_filter); ?>" class="priority-tab priority-critical <?php echo $priority_filter === 'critical' ? 'active' : ''; ?>">
                    <i class="bi bi-exclamation-octagon-fill"></i> Critical
                </a>
                <a href="?priority=high&type=<?php echo htmlspecialchars($type_filter); ?>" class="priority-tab priority-high <?php echo $priority_filter === 'high' ? 'active' : ''; ?>">
                    <i class="bi bi-exclamation-triangle-fill"></i> High
                </a>
                <a href="?priority=medium&type=<?php echo htmlspecialchars($type_filter); ?>" class="priority-tab priority-medium <?php echo $priority_filter === 'medium' ? 'active' : ''; ?>">
                    <i class="bi bi-dash-circle-fill"></i> Medium
                </a>
                <a href="?priority=low&type=<?php echo htmlspecialchars($type_filter); ?>" class="priority-tab priority-low <?php echo $priority_filter === 'low' ? 'active' : ''; ?>">
                    <i class="bi bi-info-circle-fill"></i> Low
                </a>
            </div>
            
            <!-- Search -->
            <div class="search-box">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                    <input type="hidden" name="priority" value="<?php echo htmlspecialchars($priority_filter); ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <?php if (!empty($search) || $type_filter !== 'all' || $priority_filter !== 'all'): ?>
                        <a href="notifications.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Notifications List -->
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['status']; ?> priority-<?php echo $notification['priority']; ?>" data-id="<?php echo $notification['id']; ?>">
                        <div class="d-flex align-items-start">
                            <div class="notification-type-icon notification-type-<?php echo $notification['type']; ?>">
                                <i class="bi bi-<?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-primary ms-2">New</span>
                                            <?php endif; ?>
                                            <span class="priority-badge priority-<?php echo $notification['priority']; ?> ms-2">
                                                <?php echo strtoupper($notification['priority']); ?>
                                            </span>
                                        </h5>
                                        <p class="text-muted mb-2">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        <?php echo getTimeAgo($notification['created_at']); ?>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!$notification['is_read']): ?>
                                            <button class="btn btn-outline-primary btn-sm" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                                <i class="bi bi-check2"></i> Mark Read
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                            <i class="bi bi-trash3"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Notifications pagination">
                        <ul class="pagination">
                            <?php
                            $current_url = $_SERVER['REQUEST_URI'];
                            $url_parts = parse_url($current_url);
                            parse_str($url_parts['query'] ?? '', $query_params);
                            unset($query_params['page']);
                            $base_url = $url_parts['path'] . '?' . http_build_query($query_params);
                            ?>
                            
                            <!-- Previous -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . '&page=' . ($page - 1); ?>">
                                        <i class="bi bi-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bi bi-chevron-left"></i> Previous</span>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Page numbers -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1) {
                                echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=1">1</a></li>';
                                if ($start_page > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
                                }
                            }
                            
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . $total_pages . '">' . $total_pages . '</a></li>';
                            }
                            ?>
                            
                            <!-- Next -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $base_url . '&page=' . ($page + 1); ?>">
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
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
                    <h4 class="mt-3">No notifications found</h4>
                    <p class="text-muted">
                        <?php if (!empty($search)): ?>
                            No notifications match your search criteria.
                        <?php else: ?>
                            You don't have any notifications yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap-based Notification Script -->
    <?php require_once 'includes/notification_script_bootstrap.php'; ?>
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
        function getTimeAgo($datetime) {
            const now = new Date();
            const date = new Date($datetime);
            const seconds = Math.floor((now - date) / 1000);
            
            if (seconds < 60) return 'just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
            if (seconds < 2592000) return Math.floor(seconds / 86400) + ' days ago';
            return date.toLocaleDateString();
        }
        
        function getNotificationIcon(type) {
            const icons = {
                'info': 'info-circle',
                'success': 'check-circle',
                'warning': 'exclamation-triangle',
                'error': 'x-circle',
                'system': 'gear',
                'asset': 'box',
                'request': 'arrow-left-right',
                'consumable': 'box-seam'
            };
            return icons[type] || 'bell';
        }
        
        function markAsRead(notificationId) {
            fetch('notifications_handler.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update notification badge if it exists
                    updateNotificationBadge();
                    // Remove unread styling
                    const card = document.querySelector(`[data-id="${notificationId}"]`);
                    if (card) {
                        card.classList.remove('unread');
                        const badge = card.querySelector('.badge');
                        if (badge) badge.remove();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        function deleteNotification(notificationId) {
            if (!confirm('Are you sure you want to delete this notification?')) {
                return;
            }
            
            fetch('notifications_handler.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update notification badge if it exists
                    updateNotificationBadge();
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error deleting notification:', error);
            });
        }
        
        function markAllAsRead() {
            if (!confirm('Mark all notifications as read?')) {
                return;
            }
            
            fetch('notifications_handler.php?action=mark_all_read', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge();
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
        }
        
        function clearAllNotifications() {
            if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
                return;
            }
            
            // Get all notification IDs
            const notificationCards = document.querySelectorAll('.notification-card');
            const notificationIds = Array.from(notificationCards).map(card => card.dataset.id);
            
            // Delete all notifications
            const deletePromises = notificationIds.map(id => 
                fetch('notifications_handler.php?action=delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${id}`
                })
            );
            
            Promise.all(deletePromises)
                .then(() => {
                    updateNotificationBadge();
                    location.reload();
                })
                .catch(error => {
                    console.error('Error clearing notifications:', error);
                });
        }
    </script>
</body>
</html>

<?php
function getTimeAgo($datetime) {
    $now = new DateTime();
    $date = new DateTime($datetime);
    $interval = $now->diff($date);
    
    if ($interval->y > 0) return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    if ($interval->m > 0) return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    if ($interval->d > 0) return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    if ($interval->h > 0) return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
    if ($interval->i > 0) return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

function getNotificationIcon($type) {
    $icons = [
        'info' => 'info-circle',
        'success' => 'check-circle',
        'warning' => 'exclamation-triangle',
        'error' => 'x-circle',
        'system' => 'gear',
        'asset' => 'box',
        'request' => 'arrow-left-right',
        'consumable' => 'box-seam'
    ];
    return $icons[$type] ?? 'bell';
}
?>
