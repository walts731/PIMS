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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get form data
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $steps = $_POST['steps'] ?? '';
    $current_page = $_POST['current_page'] ?? '';
    $user_agent = $_POST['user_agent'] ?? '';
    
    // Validate required fields
    if (empty($issue_type) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Issue type and description are required']);
        exit();
    }
    
    // Sanitize input
    $issue_type = htmlspecialchars($issue_type);
    $description = htmlspecialchars($description);
    $steps = htmlspecialchars($steps);
    $current_page = htmlspecialchars($current_page);
    $user_agent = htmlspecialchars($user_agent);
    
    // Insert issue into database (create issues table if it doesn't exist)
    $create_issues_table = "
        CREATE TABLE IF NOT EXISTS user_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            issue_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            steps TEXT,
            current_page VARCHAR(255),
            user_agent TEXT,
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        )
    ";
    
    $conn->query($create_issues_table);
    
    // Determine priority based on issue type
    $priority = 'medium';
    switch ($issue_type) {
        case 'bug':
            $priority = 'high';
            break;
        case 'performance':
            $priority = 'medium';
            break;
        case 'ui':
            $priority = 'low';
            break;
        case 'feature':
            $priority = 'medium';
            break;
    }
    
    // Insert the issue
    $stmt = $conn->prepare("
        INSERT INTO user_issues (user_id, issue_type, description, steps, current_page, user_agent, priority) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issssss", 
        $_SESSION['user_id'], 
        $issue_type, 
        $description, 
        $steps, 
        $current_page, 
        $user_agent, 
        $priority
    );
    
    if ($stmt->execute()) {
        $issue_id = $stmt->insert_id;
        
        // Log the issue report
        logSystemAction($_SESSION['user_id'], 'report', 'issue', "User reported {$issue_type} issue #{$issue_id}");
        
        // Send notification email to admin (if email is configured)
        if (function_exists('sendAdminNotification')) {
            $subject = "New Issue Report: {$issue_type} #{$issue_id}";
            $message = "
                A new issue has been reported by {$_SESSION['first_name']} {$_SESSION['last_name']} ({$_SESSION['email']}):
                
                Issue Type: {$issue_type}
                Priority: {$priority}
                Description: {$description}
                
                Steps to Reproduce: {$steps}
                
                Current Page: {$current_page}
                User Agent: {$user_agent}
                
                Please review and take appropriate action.
            ";
            
            sendAdminNotification($subject, $message);
        }
        
        echo json_encode([
            'success' => true,
            'issue_id' => $issue_id,
            'message' => 'Issue reported successfully. We\'ll look into it!'
        ]);
    } else {
        throw new Exception('Failed to save issue to database');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Issue reporting error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to report issue: ' . $e->getMessage()]);
}
?>
