<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['system_admin', 'admin'])) {
    header('Location: ../index.php');
    exit();
}

// Log notifications page access
logSystemAction($_SESSION['user_id'], 'notifications_accessed', 'notifications', 'Accessed notifications page');

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
                   WHEN n.related_type = 'asset' THEN CONCAT('/ADMIN/view_asset_item.php?id=', n.related_id)
                   WHEN n.related_type = 'employee' THEN CONCAT('/ADMIN/employees.php#edit-', n.related_id)
                   WHEN n.related_type = 'red_tag' THEN CONCAT('/ADMIN/red_tags.php?id=', n.related_id)
                   WHEN n.related_type = 'disposed_items' THEN '/ADMIN/disposed_items.php'
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
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css?v=<?php echo time(); ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    
    <link href="assets/css/admin-unified.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--light-color) 0%, var(--light-accent) 100%);
            min-height: 100vh;
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
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .notification-card.unread {
            border-left-color: var(--primary-color);
            background: linear-gradient(to right, rgba(var(--primary-rgb), 0.05), transparent);
        }
        
        .notification-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .notification-type.info { background: #d1ecf1; color: #0c5460; }
        .notification-type.success { background: #d4edda; color: #155724; }
        .notification-type.warning { background: #fff3cd; color: #856404; }
        .notification-type.error { background: #f8d7da; color: #721c24; }
        .notification-type.system { background: #e2e3e5; color: #383d41; }
        
        .filter-tabs .nav-link {
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            transition: var(--transition);
        }
        
        .filter-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-tabs .nav-link:hover {
            color: var(--primary-color);
        }
    </style>
<?php require_once 'includes/dark-mode-init.php'; ?>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Notifications';
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
                        <i class="bi bi-bell"></i> Notifications
                    </h1>
                    <p class="text-muted mb-0">Manage your system notifications and alerts</p>
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
                                    <i class="bi bi-bell-fill" style="font-size: 2rem; color: var(--primary-color);"></i>
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
                                    <span class="notification-type-badge <?php echo $notification['type']; ?>">
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
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    <?php require_once 'includes/footer.php'; ?>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js?v=<?php echo time(); ?>"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
    
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
                        location.reload();
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
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                });
            }
        }
        
        function clearAllNotifications() {
            // Show Bootstrap modal confirmation
            const modal = new bootstrap.Modal(document.getElementById('clearNotificationsModal'));
            modal.show();
        }
        
        function confirmClearAllNotifications() {
            // Get all notification IDs and delete them one by one
            const notificationCards = document.querySelectorAll('.notification-card');
            const deletePromises = [];
            
            notificationCards.forEach(card => {
                const notificationId = card.dataset.id;
                deletePromises.push(
                    fetch('notifications_handler.php?action=delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `notification_id=${notificationId}`
                    })
                );
            });
            
            Promise.all(deletePromises)
                .then(() => {
                    // Hide modal and reload page
                    const modal = bootstrap.Modal.getInstance(document.getElementById('clearNotificationsModal'));
                    modal.hide();
                    location.reload();
                })
                .catch(error => {
                    console.error('Error clearing notifications:', error);
                });
        }
    </script>
    
    <!-- Clear All Notifications Confirmation Modal -->
    <div class="modal fade" id="clearNotificationsModal" tabindex="-1" aria-labelledby="clearNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearNotificationsModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Clear All Notifications
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone.
                    </div>
                    <p>Are you sure you want to delete all notifications? This will permanently remove all notifications from the system.</p>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmClearAllNotifications()">
                            <i class="bi bi-trash-lg"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
