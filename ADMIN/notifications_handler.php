<?php
session_start();
require_once '../config.php';
require_once '../includes/logger.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - No session found']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Debug logging
error_log("Notifications handler called: action=$action, user_id=" . $_SESSION['user_id']);

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
    error_log("Notifications handler error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getNotifications() {
    global $conn;
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "SELECT n.*, 
                   CASE 
                       WHEN n.related_type = 'asset' THEN CONCAT('/ADMIN/view_asset_item.php?id=', n.related_id)
                       WHEN n.related_type = 'employee' THEN CONCAT('/ADMIN/employees.php#edit-', n.related_id)
                       WHEN n.related_type = 'red_tag' THEN CONCAT('/ADMIN/red_tags.php?id=', n.related_id)
                       WHEN n.related_type = 'disposed_items' THEN '/ADMIN/disposed_items.php'
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

// Function to create notifications (to be used by other parts of the system)
function createNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
    global $conn;
    
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issssi', $user_id, $title, $message, $type, $related_id, $related_type);
    $stmt->execute();
    
    return $stmt->insert_id;
}
?>
