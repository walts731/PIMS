<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Log notifications page access
logSystemAction($_SESSION['user_id'], 'notifications_accessed', 'notifications', 'Office admin accessed notifications page');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE conditions
$where_conditions = ["n.user_id = ?"];
$params = [$_SESSION['user_id']];
$types = 'i';

if ($filter_type !== 'all') {
    $where_conditions[] = "n.type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(n.title LIKE ? OR n.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM notifications n $where_clause";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_notifications = $total_row['total'];
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$sql = "SELECT n.*, 
               CASE 
                   WHEN n.related_type = 'asset' THEN CONCAT('office_assets.php#edit-', n.related_id)
                   WHEN n.related_type = 'consumable' THEN CONCAT('office_consumables.php#edit-', n.related_id)
                   WHEN n.related_type = 'request' THEN CONCAT('requests.php#view-', n.related_id)
                   ELSE '#'
               END as action_url
        FROM notifications n 
        $where_clause
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get unread count
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param('i', $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_count = $unread_row['unread_count'];

// Function to format time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="../assets/css/theme-custom.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
        }
        
        .sidebar-logo {
            height: 40px;
            width: auto;
            margin-right: 0.5rem;
        }
        
        .sidebar-title {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
        }
        
        .sidebar-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-header {
            padding: 1rem 1.5rem 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .menu-item {
            margin: 0.25rem 0;
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
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
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
            font-size: 1.2rem;
            margin-right: 1rem;
        }
        
        .notification-type-icon.info {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .notification-type-icon.success {
            background: #e8f5e8;
            color: #4caf50;
        }
        
        .notification-type-icon.warning {
            background: #fff3e0;
            color: #ff9800;
        }
        
        .notification-type-icon.error {
            background: #ffebee;
            color: #f44336;
        }
        
        .notification-type-icon.system {
            background: #f3e5f5;
            color: #9c27b0;
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.2s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #191BA9;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filter-tabs .nav-link {
            border: none;
            background: transparent;
            color: #666;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }
        
        .filter-tabs .nav-link.active {
            background: #5CC2F2;
            color: white;
        }
        
        .filter-tabs .nav-link:hover {
            color: #5CC2F2;
        }
        
        .pagination .page-link {
            border: none;
            color: #666;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }
        
        .pagination .page-link.active {
            background: #5CC2F2;
            color: white;
        }
        
        .pagination .page-link:hover {
            background: #f0f0f0;
            color: #5CC2F2;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Notifications';
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
                        <i class="bi bi-bell"></i> Office Notifications
                    </h1>
                    <p class="text-muted mb-0">Manage your office notifications and alerts</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button class="btn btn-success" onclick="markAllAsRead()">
                            <i class="bi bi-check2-all"></i> Mark All Read
                        </button>
                        <button class="btn btn-danger" onclick="clearAllNotifications()">
                            <i class="bi bi-trash"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_notifications; ?></div>
                    <div class="stats-label">Total Notifications</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $unread_count; ?></div>
                    <div class="stats-label">Unread</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_notifications - $unread_count; ?></div>
                    <div class="stats-label">Read</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_pages; ?></div>
                    <div class="stats-label">Pages</div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <ul class="nav filter-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'all' ? 'active' : ''; ?>" 
                                   href="?filter=all">All</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'info' ? 'active' : ''; ?>" 
                                   href="?filter=info">Info</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'success' ? 'active' : ''; ?>" 
                                   href="?filter=success">Success</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'warning' ? 'active' : ''; ?>" 
                                   href="?filter=warning">Warning</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'error' ? 'active' : ''; ?>" 
                                   href="?filter=error">Error</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter_type === 'system' ? 'active' : ''; ?>" 
                                   href="?filter=system">System</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" class="d-flex">
                            <input type="hidden" name="filter" value="<?php echo $filter_type; ?>">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Search notifications..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash" style="font-size: 4rem; color: #6c757d;"></i>
                    <h4 class="mt-3 text-muted">No notifications found</h4>
                    <p class="text-muted">
                        <?php if (!empty($search)): ?>
                            No notifications match your search criteria.
                        <?php else: ?>
                            You don't have any notifications yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                         data-id="<?php echo $notification['id']; ?>">
                        <div class="row align-items-start">
                            <div class="col-md-1">
                                <div class="text-center">
                                    <i class="bi bi-bell-fill" style="font-size: 2rem; color: #5CC2F2;"></i>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <h5 class="mb-2"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="d-flex align-items-center gap-3">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> 
                                        <?php echo getTimeAgo($notification['created_at']); ?>
                                    </small>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($notification['type']); ?>
                                    </span>
                                    <?php if ($notification['related_type']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-link"></i> 
                                            Related to: <?php echo ucfirst($notification['related_type']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="btn-group-vertical" role="group">
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn btn-sm btn-success mb-1" 
                                                onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                            <i class="bi bi-check"></i> Mark as Read
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($notification['action_url'] && $notification['action_url'] !== '#'): ?>
                                        <a href="<?php echo $notification['action_url']; ?>" 
                                           class="btn btn-sm btn-primary mb-1">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Notifications pagination">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter_type; ?>&search=<?php echo urlencode($search); ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
    <!-- Sidebar Scripts -->
    <script src="../assets/js/sidebar.js"></script>
    
    <script>
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
                    updateNotificationBadge();
                    const card = document.querySelector(`[data-id="${notificationId}"]`);
                    if (card) {
                        card.classList.remove('unread');
                    }
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
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
                        updateNotificationBadge();
                        const card = document.querySelector(`[data-id="${notificationId}"]`);
                        if (card) {
                            card.remove();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error deleting notification:', error);
                });
            }
        }
        
        function markAllAsRead() {
            if (confirm('Mark all notifications as read?')) {
                fetch('notifications_handler.php?action=mark_all_read', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateNotificationBadge();
                        document.querySelectorAll('.notification-card.unread').forEach(card => {
                            card.classList.remove('unread');
                        });
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                });
            }
        }
        
        function clearAllNotifications() {
            if (confirm('Are you sure you want to delete all notifications? This action cannot be undone.')) {
                fetch('notifications_handler.php?action=clear_all', {
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
                    console.error('Error clearing all notifications:', error);
                });
            }
        }
        
        function updateNotificationBadge() {
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                fetch('notifications_handler.php?action=get_count', {
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    const count = data.unread_count || 0;
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error updating notification badge:', error);
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationBadge();
        });
    </script>
</body>
</html>
