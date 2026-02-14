<?php
// System functions for PIMS

// Determine the correct path to config.php based on current working directory
$cwd = getcwd();
$config_path = '';

// Check if we're in the root directory (contains config.php)
if (file_exists($cwd . '/config.php')) {
    $config_path = 'config.php';
} elseif (file_exists($cwd . '/../config.php')) {
    // We're one level down (like ADMIN or SYSTEM_ADMIN)
    $config_path = '../config.php';
} elseif (file_exists($cwd . '/../../config.php')) {
    // We're two levels down (like ADMIN/ajax or SYSTEM_ADMIN/ajax)
    $config_path = '../../config.php';
} elseif (file_exists($cwd . '/../../../config.php')) {
    // We're three levels down (unlikely but just in case)
    $config_path = '../../../config.php';
} else {
    // Fallback: try to find config.php by going up until we find it
    $levels_up = 0;
    $test_path = '';
    while ($levels_up <= 5) {
        $test_path = str_repeat('../', $levels_up) . 'config.php';
        if (file_exists($cwd . '/' . $test_path)) {
            $config_path = $test_path;
            break;
        }
        $levels_up++;
    }
    
    // If still not found, try absolute path
    if (empty($config_path)) {
        $absolute_path = dirname(__DIR__) . '/config.php';
        if (file_exists($absolute_path)) {
            $config_path = $absolute_path;
        }
    }
}

if (empty($config_path)) {
    // If we're in an AJAX context, don't output HTML error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Config file not found']);
        exit();
    } else {
        die('Error: config.php not found. Please check your file structure.');
    }
}

require_once $config_path;

/**
 * Log system actions for audit trail
 */
function logSystemAction($user_id, $action, $module, $description = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, module, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->bind_param("isssss", $user_id, $action, $module, $description, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log system action: " . $e->getMessage());
        return false;
    }
}

/**
 * Log security events
 */
function logSecurityEvent($event_type, $description, $severity = 'low', $user_id = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO security_logs (event_type, description, severity, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->bind_param("ssssss", $event_type, $description, $severity, $user_id, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system setting value
 */
function getSystemSetting($setting_name, $default = null) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
        $stmt->bind_param("s", $setting_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['setting_value'];
        }
        
        return $default;
    } catch (Exception $e) {
        error_log("Failed to get system setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Update system setting
 */
function updateSystemSetting($setting_name, $setting_value) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_name = ?");
        $stmt->bind_param("ss", $setting_value, $setting_name);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to update system setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Send system email notification
 */
function sendSystemEmail($to, $subject, $message, $is_html = true) {
    try {
        // Include PHPMailer
        require_once '../SYSTEM_ADMIN/PHPMailer/PHPMailer-7.0.0/src/PHPMailer.php';
        require_once '../SYSTEM_ADMIN/PHPMailer/PHPMailer-7.0.0/src/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Get email settings
        $system_email = getSystemSetting('system_email', 'noreply@pims.com');
        
        // Use PHP mail() function
        $mail->isMail();
        
        $mail->setFrom($system_email, 'PIMS System');
        $mail->addAddress($to);
        
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        if ($is_html) {
            $mail->isHTML(true);
        }
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send email: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password, $min_length = 8) {
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "Password must be at least $min_length characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * Check if user account is locked
 */
function isUserLocked($username) {
    global $conn;
    
    try {
        $max_attempts = getSystemSetting('max_login_attempts', 5);
        
        $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_logs WHERE username = ? AND success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['attempts'] >= $max_attempts;
    } catch (Exception $e) {
        error_log("Failed to check user lock status: " . $e->getMessage());
        return false;
    }
}

/**
 * Log login attempt
 */
function logLoginAttempt($username, $success, $failure_reason = '') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO login_logs (username, ip_address, user_agent, success, failure_reason) VALUES (?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->bind_param("sssis", $username, $ip_address, $user_agent, $success, $failure_reason);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Check session timeout and logout if expired
 */
function checkSessionTimeout() {
    // Get session timeout setting from database (default: 3600 seconds = 1 hour)
    $session_timeout = getSystemSetting('session_timeout', 3600);
    
    // Check if user is logged in and login_time is set
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['login_time'])) {
        $login_time = $_SESSION['login_time'];
        $current_time = time();
        $elapsed_time = $current_time - $login_time;
        
        // Check if session has expired
        if ($elapsed_time > $session_timeout) {
            // Log session timeout
            $user_id = $_SESSION['user_id'] ?? null;
            $user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $user_email = $_SESSION['email'] ?? '';
            
            logSystemAction($user_id, 'session_timeout', 'authentication', "Session expired for user: {$user_name} ({$user_email}) after {$elapsed_time} seconds");
            logSecurityEvent('session_timeout', "Session timeout for user: {$user_name} ({$user_email})", 'medium', $user_id);
            
            // Destroy session
            session_destroy();
            
            // Redirect to login page with timeout message
            header('Location: ../index.php?timeout=1');
            exit();
        }
        
        // Update last activity time (optional - for future idle timeout feature)
        $_SESSION['last_activity'] = $current_time;
    }
}

/**
 * Extend session timeout (refresh login time)
 */
function extendSessionTimeout() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Generate next tag number based on tag_formats configuration
 */
function generateNextTag($tag_type) {
    global $conn;
    
    // Get tag configuration
    $stmt = $conn->prepare("SELECT * FROM tag_formats WHERE tag_type = ? AND status = 'active'");
    $stmt->bind_param("s", $tag_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null; // No configuration found
    }
    
    $config = $result->fetch_assoc();
    $stmt->close();
    
    // Get current number and increment it
    $current_number = $config['current_number'];
    $next_number = $current_number + 1;
    
    // Build the tag from components
    $components = json_decode($config['format_components'], true);
    // Handle double-encoded JSON
    if (is_string($components)) {
        $components = json_decode($components, true);
    }
    
    if (!is_array($components) || empty($components)) {
        return null; // Invalid components
    }
    
    $parts = [];
    
    foreach ($components as $component) {
        switch ($component['type']) {
            case 'text':
                $parts[] = $component['value'] ?? '';
                break;
            case 'digits':
                $component_digits = $component['digits'] ?? $config['digits'];
                $number = str_pad($next_number, $component_digits, '0', STR_PAD_LEFT);
                $parts[] = $number;
                break;
            case 'month':
                $parts[] = date('m');
                break;
            case 'year':
                $parts[] = date('Y');
                break;
        }
    }
    
    $generated_tag = implode($config['separator'], $parts);
    
    // Check for duplicate in the appropriate table
    $duplicate_check_table = '';
    $duplicate_check_column = '';
    
    switch ($tag_type) {
        case 'ris_no':
            $duplicate_check_table = 'ris_forms';
            $duplicate_check_column = 'ris_no';
            break;
        case 'sai_no':
            $duplicate_check_table = 'ris_forms';
            $duplicate_check_column = 'sai_no';
            break;
        case 'code':
            $duplicate_check_table = 'ris_forms';
            $duplicate_check_column = 'code';
            break;
        default:
            // For other tag types, don't check duplicates
            break;
    }
    
    // If we have a table to check, verify uniqueness
    if (!empty($duplicate_check_table) && !empty($duplicate_check_column)) {
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM $duplicate_check_table WHERE $duplicate_check_column = ?");
        $check_stmt->bind_param("s", $generated_tag);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $count = $check_result->fetch_assoc()['count'];
        $check_stmt->close();
        
        // If duplicate exists, increment and try again
        if ($count > 0) {
            // Find the next available number
            $max_stmt = $conn->prepare("SELECT MAX(SUBSTRING_INDEX($duplicate_check_column, '{$config['separator']}', -1)) as max_num FROM $duplicate_check_table WHERE $duplicate_check_column LIKE ?");
            $like_pattern = implode($config['separator'], array_slice(explode($config['separator'], $generated_tag), 0, -1)) . $config['separator'] . '%';
            $max_stmt->bind_param("s", $like_pattern);
            $max_stmt->execute();
            $max_result = $max_stmt->get_result();
            $max_num = $max_result->fetch_assoc()['max_num'];
            $max_stmt->close();
            
            $next_number = intval($max_num) + 1;
            
            // Regenerate the tag with the new number
            $parts = [];
            foreach ($components as $component) {
                switch ($component['type']) {
                    case 'text':
                        $parts[] = $component['value'] ?? '';
                        break;
                    case 'digits':
                        $component_digits = $component['digits'] ?? $config['digits'];
                        $number = str_pad($next_number, $component_digits, '0', STR_PAD_LEFT);
                        $parts[] = $number;
                        break;
                    case 'month':
                        $parts[] = date('m');
                        break;
                    case 'year':
                        $parts[] = date('Y');
                        break;
                }
            }
            $generated_tag = implode($config['separator'], $parts);
        }
    }
    
    // Update the counter in database with the final number
    $update_stmt = $conn->prepare("UPDATE tag_formats SET current_number = ? WHERE tag_type = ?");
    $update_stmt->bind_param("is", $next_number, $tag_type);
    $update_stmt->execute();
    $update_stmt->close();
    
    return $generated_tag;
}

/**
 * Get next tag preview without incrementing the counter
 */
function getNextTagPreview($tag_type) {
    global $conn;
    
    // Get tag configuration
    $stmt = $conn->prepare("SELECT * FROM tag_formats WHERE tag_type = ? AND status = 'active'");
    $stmt->bind_param("s", $tag_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null; // No configuration found
    }
    
    $config = $result->fetch_assoc();
    $stmt->close();
    
    // Get current number for preview (don't increment)
    $current_number = $config['current_number'];
    $next_number = $current_number + 1;
    
    // Build the tag from components
    $components = json_decode($config['format_components'], true);
    // Handle double-encoded JSON
    if (is_string($components)) {
        $components = json_decode($components, true);
    }
    
    if (!is_array($components) || empty($components)) {
        return null; // Invalid components
    }
    
    $parts = [];
    
    foreach ($components as $component) {
        switch ($component['type']) {
            case 'text':
                $parts[] = $component['value'] ?? '';
                break;
            case 'digits':
                $component_digits = $component['digits'] ?? $config['digits'];
                $number = str_pad($next_number, $component_digits, '0', STR_PAD_LEFT);
                $parts[] = $number;
                break;
            case 'month':
                $parts[] = date('m');
                break;
            case 'year':
                $parts[] = date('Y');
                break;
        }
    }
    
    return implode($config['separator'], $parts);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generateNextTag') {
    header('Content-Type: application/json');
    
    $tag_type = $_POST['tag_type'] ?? '';
    
    if (empty($tag_type)) {
        echo json_encode(['success' => false, 'error' => 'Tag type is required']);
        exit;
    }
    
    $tag_number = generateNextTag($tag_type);
    
    if ($tag_number !== null) {
        echo json_encode(['success' => true, 'tag_number' => $tag_number]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to generate tag number']);
    }
    exit;
}
?>
