<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../login.php');
    exit();
}

// Include required files
require_once '../includes/config.php';
require_once '../includes/logger.php';

// Set page title
$page_title = 'Notifications';

// Get current filter and search parameters
$type_filter = $_GET['type'] ?? 'all';
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

if (!empty($search)) {
    $where_conditions[] = "(n.title LIKE ? OR n.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM notifications n $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_notifications = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$sql = "SELECT n.*, 
         CASE 
             WHEN n.is_read = 0 THEN 'unread'
             ELSE 'read'
         END as status
         FROM notifications n 
         $where_clause 
         ORDER BY n.created_at DESC 
         LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$unread_sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->execute([$user_id]);
$unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['count'];
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
    
    <style>
        :root {
            --primary-color: #5CC2F2;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-lg: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar:not(.show) {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            border-radius: 8px;
        }
        
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
        }
        
        .menu-item {
            margin-bottom: 0.5rem;
        }
        
        .menu-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .menu-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
        }
        
        .menu-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .menu-link i {
            margin-right: 0.75rem;
            font-size: 1.1rem;
        }
        
        .menu-text {
            font-weight: 500;
        }
        
        /* Main Content Layout */
        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            width: calc(100% - 250px);
        }
        
        /* Topbar Styles */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            height: 60px;
            background: white;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            z-index: 999;
        }
        
        .main-content {
            margin-top: 60px;
            padding-top: 2rem;
        }
        
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid #5CC2F2;
        }
        
        .notification-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-card.unread {
            border-left-color: #5CC2F2;
            background-color: #f8fcff;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .notification-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .notification-type-info { background-color: #e3f2fd; color: #1976d2; }
        .notification-type-success { background-color: #e8f5e8; color: #2e7d32; }
        .notification-type-warning { background-color: #fff3e0; color: #f57c00; }
        .notification-type-error { background-color: #ffebee; color: #c62828; }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .filter-tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .filter-tab:hover {
            background-color: rgba(92, 194, 242, 0.1);
        }
        
        .filter-tab.active {
            border-bottom-color: #5CC2F2;
            color: #5CC2F2;
        }
        
        .search-box {
            max-width: 400px;
            margin-bottom: 2rem;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 2rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #5CC2F2;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(92, 194, 242, 0.1);
        }
        
        .sidebar-toggle.active {
            background-color: #5CC2F2;
            color: white;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            display: none;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                padding: 1rem;
            }
            
            .notification-card {
                padding: 1rem;
            }
            
            .filter-tabs {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .filter-tab {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
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
                <a href="?type=all" class="filter-tab <?php echo $type_filter === 'all' ? 'active' : ''; ?>">
                    All (<?php echo $total_notifications; ?>)
                </a>
                <a href="?type=info" class="filter-tab <?php echo $type_filter === 'info' ? 'active' : ''; ?>">
                    Info
                </a>
                <a href="?type=success" class="filter-tab <?php echo $type_filter === 'success' ? 'active' : ''; ?>">
                    Success
                </a>
                <a href="?type=warning" class="filter-tab <?php echo $type_filter === 'warning' ? 'active' : ''; ?>">
                    Warning
                </a>
                <a href="?type=error" class="filter-tab <?php echo $type_filter === 'error' ? 'active' : ''; ?>">
                    Error
                </a>
            </div>
            
            <!-- Search -->
            <div class="search-box">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                    <input type="text" name="search" class="form-control" placeholder="Search notifications..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <?php if (!empty($search) || $type_filter !== 'all'): ?>
                        <a href="notifications.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Notifications List -->
            <?php if (!empty($notifications)): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['status']; ?>" data-id="<?php echo $notification['id']; ?>">
                        <div class="d-flex align-items-start">
                            <div class="notification-type-icon notification-type-<?php echo $notification['type']; ?>">
                                <i class="bi bi-<?php echo getNotificationIcon($notification['type']); ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="badge bg-primary ms-2">New</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="text-muted mb-2">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
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
