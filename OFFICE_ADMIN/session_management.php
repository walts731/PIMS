<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'office_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

header('Content-Type: application/json');

// Create user_sessions table if it doesn't exist
$create_sessions_table = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(255) NOT NULL UNIQUE,
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_type VARCHAR(50),
        browser VARCHAR(50),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        expires_at TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        INDEX idx_user_id (user_id),
        INDEX idx_session_id (session_id),
        INDEX idx_last_activity (last_activity)
    )
";

$conn->query($create_sessions_table);

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_sessions':
            getActiveSessions();
            break;
        case 'revoke_session':
            revokeSession();
            break;
        case 'revoke_all_other':
            revokeAllOtherSessions();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Session management error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function getActiveSessions() {
    global $conn;
    
    // Clean up expired sessions first
    cleanupExpiredSessions();
    
    // Update current session activity
    updateCurrentSession();
    
    // Get all active sessions for this user
    $stmt = $conn->prepare("
        SELECT session_id, ip_address, user_agent, device_type, browser, 
               created_at, last_activity, expires_at
        FROM user_sessions 
        WHERE user_id = ? AND is_active = TRUE AND expires_at > NOW()
        ORDER BY last_activity DESC
    ");
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    $current_session_id = session_id();
    
    while ($row = $result->fetch_assoc()) {
        $session = [
            'session_id' => $row['session_id'],
            'ip_address' => $row['ip_address'],
            'user_agent' => $row['user_agent'],
            'device_type' => $row['device_type'],
            'browser' => $row['browser'],
            'created_at' => $row['created_at'],
            'last_activity' => $row['last_activity'],
            'expires_at' => $row['expires_at'],
            'is_current' => $row['session_id'] === $current_session_id
        ];
        
        // Parse device type and browser from user agent if not stored
        if (!$row['device_type'] || !$row['browser']) {
            $parsed = parseUserAgent($row['user_agent']);
            $session['device_type'] = $parsed['device_type'];
            $session['browser'] = $parsed['browser'];
        }
        
        $sessions[] = $session;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions
    ]);
}

function updateCurrentSession() {
    global $conn;
    
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Parse device info
    $parsed = parseUserAgent($user_agent);
    $device_type = $parsed['device_type'];
    $browser = $parsed['browser'];
    
    // Calculate expiry time (30 days from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Insert or update current session
    $stmt = $conn->prepare("
        INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_type, browser, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        ip_address = VALUES(ip_address),
        user_agent = VALUES(user_agent),
        device_type = VALUES(device_type),
        browser = VALUES(browser),
        last_activity = CURRENT_TIMESTAMP,
        expires_at = VALUES(expires_at),
        is_active = TRUE
    ");
    
    $stmt->bind_param("issssss", 
        $_SESSION['user_id'], 
        $session_id, 
        $ip_address, 
        $user_agent, 
        $device_type, 
        $browser, 
        $expires_at
    );
    
    $stmt->execute();
    $stmt->close();
}

function parseUserAgent($user_agent) {
    $device_type = 'Unknown';
    $browser = 'Unknown';
    
    // Detect device type
    if (preg_match('/Mobile|Android|iPhone|iPad|iPod/', $user_agent)) {
        if (preg_match('/iPad|Tablet/', $user_agent)) {
            $device_type = 'Tablet';
        } else {
            $device_type = 'Mobile';
        }
    } else {
        $device_type = 'Desktop';
    }
    
    // Detect browser
    if (preg_match('/Chrome/', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox/', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari/', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Edge/', $user_agent)) {
        $browser = 'Edge';
    } elseif (preg_match('/Opera/', $user_agent)) {
        $browser = 'Opera';
    }
    
    return [
        'device_type' => $device_type,
        'browser' => $browser
    ];
}

function cleanupExpiredSessions() {
    global $conn;
    
    // Mark expired sessions as inactive
    $stmt = $conn->prepare("
        UPDATE user_sessions 
        SET is_active = FALSE 
        WHERE expires_at <= NOW() OR is_active = TRUE
    ");
    $stmt->execute();
    $stmt->close();
    
    // Delete sessions older than 90 days
    $stmt = $conn->prepare("
        DELETE FROM user_sessions 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $stmt->close();
}

function revokeSession() {
    global $conn;
    
    $session_id = $_POST['session_id'] ?? '';
    
    if (empty($session_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Session ID required']);
        return;
    }
    
    // Don't allow revoking current session
    if ($session_id === session_id()) {
        http_response_code(400);
        echo json_encode(['error' => 'Cannot revoke current session']);
        return;
    }
    
    // Mark session as inactive
    $stmt = $conn->prepare("
        UPDATE user_sessions 
        SET is_active = FALSE 
        WHERE session_id = ? AND user_id = ?
    ");
    
    $stmt->bind_param("si", $session_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Log the action
        logSystemAction($_SESSION['user_id'], 'revoke', 'session', "Revoked session: {$session_id}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Session revoked successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Session not found']);
    }
    
    $stmt->close();
}

function revokeAllOtherSessions() {
    global $conn;
    
    $current_session_id = session_id();
    
    // Mark all other sessions as inactive
    $stmt = $conn->prepare("
        UPDATE user_sessions 
        SET is_active = FALSE 
        WHERE user_id = ? AND session_id != ? AND is_active = TRUE
    ");
    
    $stmt->bind_param("is", $_SESSION['user_id'], $current_session_id);
    $stmt->execute();
    
    $revoked_count = $stmt->affected_rows;
    
    if ($revoked_count > 0) {
        // Log the action
        logSystemAction($_SESSION['user_id'], 'revoke', 'sessions', "Revoked {$revoked_count} other sessions");
        
        echo json_encode([
            'success' => true,
            'revoked_count' => $revoked_count,
            'message' => "Revoked {$revoked_count} other sessions"
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'revoked_count' => 0,
            'message' => 'No other sessions to revoke'
        ]);
    }
    
    $stmt->close();
}
?>
