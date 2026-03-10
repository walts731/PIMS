<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Fix for localhost SSL issues and CORS
if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Cookie');
    header('Access-Control-Allow-Credentials: true');
}

// Check if user is logged in and is office admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'office_admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - No session found']);
    exit();
}

header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

// Debug logging
error_log("Office Admin Notifications handler called: action=$action, user_id=" . $_SESSION['user_id']);

try {
    switch ($action) {
        case 'get_notifications':
            getNotifications();
            break;
        case 'mark_read':
            markAsRead();
            break;
        case 'mark_all_read':
            markAllAsRead();
            break;
        case 'get_count':
            getUnreadCount();
            break;
        case 'delete':
            deleteNotification();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    error_log("Office Admin Notifications handler error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getNotifications() {
    global $conn;
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "SELECT n.*, 
                   CASE 
                       WHEN n.related_type = 'asset' THEN CONCAT('office_assets.php#edit-', n.related_id)
                       WHEN n.related_type = 'consumable' THEN CONCAT('office_consumables.php#edit-', n.related_id)
                       WHEN n.related_type = 'request' THEN CONCAT('requests.php#view-', n.related_id)
                       ELSE '#'
                   END as action_url
            FROM notifications n 
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $_SESSION['user_id'], $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'read_at' => $row['read_at'],
            'action_url' => $row['action_url'],
            'time_ago' => getTimeAgo($row['created_at'])
        ];
    }
    
    echo json_encode([
        'notifications' => $notifications,
        'has_more' => count($notifications) === $limit
    ]);
}

function markAsRead() {
    global $conn;
    
    $notification_id = $_POST['notification_id'] ?? 0;
    
    if (!$notification_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID required']);
        return;
    }
    
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
    }
}

function markAllAsRead() {
    global $conn;
    
    $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'marked_count' => $stmt->affected_rows
    ]);
}

function getUnreadCount() {
    global $conn;
    
    $sql = "SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode(['unread_count' => (int)$row['unread_count']]);
}

function deleteNotification() {
    global $conn;
    
    $notification_id = $_POST['notification_id'] ?? 0;
    
    if (!$notification_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Notification ID required']);
        return;
    }
    
    $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $notification_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Notification not found']);
    }
}

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

// Function to create notifications for office events
function createOfficeNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
    $stmt->execute();
    
    return $stmt->insert_id;
}

// Function to create notifications for low stock consumables
function createLowStockNotification($office_admin_id, $consumable_id, $consumable_name, $current_stock, $reorder_level) {
    $title = "Low Stock Alert";
    $message = "Consumable '{$consumable_name}' is running low on stock. Current: {$current_stock}, Reorder at: {$reorder_level}";
    return createOfficeNotification($office_admin_id, $title, $message, 'warning', $consumable_id, 'consumable');
}

// Function to create notifications for new requests
function createNewRequestNotification($office_admin_id, $request_id, $request_type, $requester_name) {
    $title = "New {$request_type} Request";
    $message = "New {$request_type} request received from {$requester_name}";
    return createOfficeNotification($office_admin_id, $title, $message, 'info', $request_id, 'request');
}

// Function to create notifications for asset maintenance
function createMaintenanceNotification($office_admin_id, $asset_id, $asset_name) {
    $title = "Asset Maintenance Due";
    $message = "Asset '{$asset_name}' is due for maintenance";
    return createOfficeNotification($office_admin_id, $title, $message, 'warning', $asset_id, 'asset');
}

// Function to create notifications for borrow requests
function createBorrowRequestNotification($office_admin_id, $borrow_request_id, $requester_office, $asset_name) {
    $title = "New Borrow Request";
    $message = "Borrow request received from {$requester_office} for '{$asset_name}'";
    return createOfficeNotification($office_admin_id, $title, $message, 'info', $borrow_request_id, 'request');
}
?>
