<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';
require_once 'includes/notification_functions.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    header('Location: ../index.php');
    exit();
}

// Test notification creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_type = $_POST['test_type'] ?? 'info';
    $title = $_POST['title'] ?? 'Test Notification';
    $message = $_POST['message'] ?? 'This is a test notification from the office admin notification system.';
    
    $notification_id = createOfficeNotification(
        $_SESSION['user_id'], 
        $title, 
        $message, 
        $test_type
    );
    
    if ($notification_id) {
        $success_message = "Test notification created successfully! ID: {$notification_id}";
    } else {
        $error_message = "Failed to create test notification";
    }
}

// Get current notification count
$unread_sql = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_sql);
$unread_stmt->bind_param('i', $_SESSION['user_id']);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_count = $unread_row['unread_count'];

// Get recent notifications
$recent_sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param('i', $_SESSION['user_id']);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();

$recent_notifications = [];
while ($row = $recent_result->fetch_assoc()) {
    $recent_notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications - PIMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .notification-item {
            border-left: 4px solid #5CC2F2;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(92, 194, 242, 0.05);
            border-radius: 0 10px 10px 0;
        }
        .notification-item.unread {
            background: rgba(92, 194, 242, 0.1);
            border-left-color: #191BA9;
        }
    </style>
</head>
<body>
    <?php
    $page_title = 'Test Notifications';
    ?>
    <div class="main-wrapper" id="mainWrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="test-card">
                <h1><i class="bi bi-bell"></i> Office Admin Notification Test</h1>
                <p class="text-muted">Test the notification system for office administrators</p>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo $unread_count; ?></h3>
                                <p class="mb-0">Unread Notifications</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <h5>Create Test Notification</h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Notification Type</label>
                                        <select name="test_type" class="form-select">
                                            <option value="info">Info</option>
                                            <option value="success">Success</option>
                                            <option value="warning">Warning</option>
                                            <option value="error">Error</option>
                                            <option value="system">System</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="title" class="form-control" value="Test Notification" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea name="message" class="form-control" rows="3" required>This is a test notification from the office admin notification system.</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Create Test Notification
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-clock-history"></i> Recent Notifications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_notifications)): ?>
                            <p class="text-muted">No notifications found.</p>
                        <?php else: ?>
                            <?php foreach ($recent_notifications as $notification): ?>
                                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> 
                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                <span class="badge bg-<?php echo getBadgeColor($notification['type']); ?> ms-2">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="notifications.php" class="btn btn-outline-primary">
                        <i class="bi bi-list"></i> View All Notifications
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-speedometer2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once 'includes/sidebar-scripts.php'; ?>
</body>
</html>

<?php
function getBadgeColor($type) {
    switch ($type) {
        case 'success': return 'success';
        case 'warning': return 'warning';
        case 'error': return 'danger';
        case 'system': return 'secondary';
        case 'info': 
        default: return 'info';
    }
}
?>
