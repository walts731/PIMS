<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'office_admin') {
    echo "Access denied. Please log in as office admin.";
    exit();
}

// Include required files
require_once '../config.php';

// Set page title
$page_title = 'Notifications';

// Get current filter parameters
$type_filter = $_GET['type'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get notifications for current user
$user_id = $_SESSION['user_id'];

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>$page_title - PIMS</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: 'Inter', sans-serif; background: #f8f9fa; }";
echo ".container { max-width: 1200px; margin: 0 auto; padding: 20px; }";
echo ".card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; padding: 20px; }";
echo ".priority-critical { border-left: 4px solid #dc3545; }";
echo ".priority-high { border-left: 4px solid #fd7e14; }";
echo ".priority-medium { border-left: 4px solid #ffc107; }";
echo ".priority-low { border-left: 4px solid #28a745; }";
echo ".badge-critical { background: #dc3545; color: white; }";
echo ".badge-high { background: #fd7e14; color: white; }";
echo ".badge-medium { background: #ffc107; color: black; }";
echo ".badge-low { background: #28a745; color: white; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>Notifications</h1>";

try {
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

    // Build parameter types for count query
    $count_param_types = '';
    $count_param_values = [];

    $count_param_types .= 'i';
    $count_param_values[] = $user_id;

    if ($type_filter !== 'all') {
        $count_param_types .= 's';
        $count_param_values[] = $type_filter;
    }

    if ($priority_filter !== 'all') {
        $count_param_types .= 's';
        $count_param_values[] = $priority_filter;
    }

    if (!empty($search)) {
        $count_param_types .= 'ss';
        $count_param_values[] = "%$search%";
        $count_param_values[] = "%$search%";
    }

    if (!empty($count_param_values)) {
        $count_stmt->bind_param($count_param_types, ...$count_param_values);
    }
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_notifications = $count_result->fetch_assoc()['total'];

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

    // Build parameter types and values
    $param_types = '';
    $param_values = [];

    $param_types .= 'i';
    $param_values[] = $user_id;

    if ($type_filter !== 'all') {
        $param_types .= 's';
        $param_values[] = $type_filter;
    }

    if ($priority_filter !== 'all') {
        $param_types .= 's';
        $param_values[] = $priority_filter;
    }

    if (!empty($search)) {
        $param_types .= 'ss';
        $param_values[] = "%$search%";
        $param_values[] = "%$search%";
    }

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

    echo "<div class='card'>";
    echo "<h5>Total Notifications: $total_notifications | Unread: $unread_count</h5>";
    echo "</div>";

    // Priority filters
    echo "<div class='card'>";
    echo "<h6>Priority Filters:</h6>";
    echo "<a href='?priority=all' class='btn btn-sm " . ($priority_filter === 'all' ? 'btn-primary' : 'btn-outline-primary') . "'>All</a> ";
    echo "<a href='?priority=critical' class='btn btn-sm " . ($priority_filter === 'critical' ? 'btn-danger' : 'btn-outline-danger') . "'>Critical</a> ";
    echo "<a href='?priority=high' class='btn btn-sm " . ($priority_filter === 'high' ? 'btn-warning' : 'btn-outline-warning') . "'>High</a> ";
    echo "<a href='?priority=medium' class='btn btn-sm " . ($priority_filter === 'medium' ? 'btn-secondary' : 'btn-outline-secondary') . "'>Medium</a> ";
    echo "<a href='?priority=low' class='btn btn-sm " . ($priority_filter === 'low' ? 'btn-success' : 'btn-outline-success') . "'>Low</a>";
    echo "</div>";

    // Display notifications
    if (!empty($notifications)) {
        foreach ($notifications as $notification) {
            echo "<div class='card priority-" . $notification['priority'] . "'>";
            echo "<div class='d-flex justify-content-between align-items-start'>";
            echo "<div>";
            echo "<h6>" . htmlspecialchars($notification['title']) . "</h6>";
            echo "<p>" . htmlspecialchars($notification['message']) . "</p>";
            echo "<small class='text-muted'><i class='bi bi-clock'></i> " . $notification['created_at'] . "</small>";
            echo "</div>";
            echo "<div>";
            echo "<span class='badge badge-" . $notification['priority'] . "'>" . strtoupper($notification['priority']) . "</span>";
            if ($notification['is_read'] == 0) {
                echo "<span class='badge bg-primary ms-2'>NEW</span>";
            }
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
    } else {
        echo "<div class='card'>";
        echo "<p>No notifications found.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>Error:</h5>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";

$conn->close();
?>
