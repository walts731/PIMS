<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

echo "<h2>Notifications Page Debug - Step by Step</h2>";

// Step 1: Check authentication
echo "<h3>Step 1: Authentication Check</h3>";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'office_admin') {
    echo "<p>❌ Authentication failed - would redirect to login</p>";
    exit();
} else {
    echo "<p>✅ Authentication passed</p>";
    echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>";
    echo "<p>Role: " . htmlspecialchars($_SESSION['role']) . "</p>";
}

// Step 2: Include required files
echo "<h3>Step 2: Including Required Files</h3>";
try {
    require_once '../config.php';
    echo "<p>✅ config.php loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ config.php failed: " . $e->getMessage() . "</p>";
    exit();
}

try {
    require_once '../includes/logger.php';
    echo "<p>✅ logger.php loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ logger.php failed: " . $e->getMessage() . "</p>";
    exit();
}

// Step 3: Database connection
echo "<h3>Step 3: Database Connection</h3>";
try {
    global $conn;
    if ($conn) {
        echo "<p>✅ Database connection established</p>";
    } else {
        echo "<p>❌ Database connection failed</p>";
        exit();
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
    exit();
}

// Step 4: Check notifications table structure
echo "<h3>Step 4: Check Notifications Table</h3>";
try {
    $sql = "DESCRIBE notifications";
    $result = $conn->query($sql);
    echo "<p>✅ Notifications table accessible</p>";
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Type'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Null'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Key'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($row['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p>❌ Notifications table error: " . $e->getMessage() . "</p>";
    exit();
}

// Step 5: Test notification query
echo "<h3>Step 5: Test Notification Query</h3>";
try {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo "<p>✅ Query successful - Found " . $row['total'] . " notifications</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Query error: " . $e->getMessage() . "</p>";
    exit();
}

// Step 6: Test the actual notifications query with priority
echo "<h3>Step 6: Test Priority Query</h3>";
try {
    $type_filter = 'all';
    $priority_filter = 'all';
    $search = '';
    $page = 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
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
    
    echo "<p>SQL Query: " . htmlspecialchars($sql) . "</p>";
    
    $stmt = $conn->prepare($sql);
    
    // Build parameter types and values
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

    echo "<p>Parameter types: " . htmlspecialchars($param_types ?? '') . "</p>";
    echo "<p>Parameter count: " . count($param_values) . "</p>";
    
    $stmt->bind_param($param_types, ...$param_values);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    echo "<p>✅ Priority query successful - Found " . count($notifications) . " notifications</p>";
    
    if (!empty($notifications)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Priority</th><th>Status</th></tr>";
        
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($notification['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($notification['title'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($notification['type'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($notification['priority'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($notification['status'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Priority query error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
    exit();
}

echo "<h3>✅ All tests passed! The notifications system should work.</h3>";
echo "<p><a href='notifications.php'>Try accessing the notifications page again</a></p>";

$conn->close();
?>
