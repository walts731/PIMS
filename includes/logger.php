<?php
// System Logger Utility
// This file provides centralized logging functionality for the PIMS system

// Function to log system actions (only declare if not already exists)
if (!function_exists('logSystemAction')) {
    function logSystemAction($user_id, $action, $module, $details = null) {
        global $conn;
        
        // Check if connection exists
        if (!$conn) {
            return false;
        }
        
        try {
            // Check if system_logs table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
            if ($table_check->num_rows === 0) {
                // Create table if it doesn't exist
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS `system_logs` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) DEFAULT NULL,
                        `action` varchar(100) NOT NULL,
                        `module` varchar(50) NOT NULL,
                        `description` text DEFAULT NULL,
                        `ip_address` varchar(45) DEFAULT NULL,
                        `user_agent` text DEFAULT NULL,
                        `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `idx_user_id` (`user_id`),
                        KEY `idx_action` (`action`),
                        KEY `idx_module` (`module`),
                        KEY `idx_timestamp` (`timestamp`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ";
                $conn->query($create_table_sql);
            }
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Convert details array to JSON string if it's an array
            if (is_array($details)) {
                $details = json_encode($details);
            } elseif (is_null($details)) {
                $details = '';
            }
            
            $stmt = $conn->prepare("
                INSERT INTO system_logs (user_id, action, module, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssss", $user_id, $action, $module, $details, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log system action: " . $e->getMessage());
            return false;
        }
    }
}

// Function to log user login (only declare if not already exists)
if (!function_exists('logUserLogin')) {
    function logUserLogin($user_id, $username, $success, $failure_reason = null) {
        global $conn;
        
        if (!$conn) {
            return false;
        }
        
        try {
            // Check if login_logs table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'login_logs'");
            if ($table_check->num_rows === 0) {
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS `login_logs` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `user_id` int(11) DEFAULT NULL,
                        `username` varchar(100) NOT NULL,
                        `success` tinyint(1) DEFAULT 0,
                        `ip_address` varchar(45) DEFAULT NULL,
                        `user_agent` text DEFAULT NULL,
                        `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `idx_user_id` (`user_id`),
                        KEY `idx_username` (`username`),
                        KEY `idx_success` (`success`),
                        KEY `idx_attempt_time` (`attempt_time`),
                        KEY `idx_ip_address` (`ip_address`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ";
                $conn->query($create_table_sql);
            }
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $conn->prepare("
                INSERT INTO login_logs (user_id, username, success, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isisi", $user_id, $username, $success, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
            return false;
        }
    }
}

// Function to log security events (only declare if not already exists)
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($event_type, $title, $description, $category, $affected_user_id = null) {
        global $conn;
        
        if (!$conn) {
            return false;
        }
        
        try {
            // Check if security_logs table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'security_logs'");
            if ($table_check->num_rows === 0) {
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS `security_logs` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `event_type` enum('critical','high','medium','low') NOT NULL,
                        `title` varchar(200) NOT NULL,
                        `description` text NOT NULL,
                        `category` varchar(50) NOT NULL,
                        `status` enum('open','investigating','resolved','false_positive') DEFAULT 'open',
                        `affected_user_id` int(11) DEFAULT NULL,
                        `ip_address` varchar(45) DEFAULT NULL,
                        `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        `resolved_by` int(11) DEFAULT NULL,
                        `resolved_at` timestamp NULL DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        KEY `idx_event_type` (`event_type`),
                        KEY `idx_status` (`status`),
                        KEY `idx_category` (`category`),
                        KEY `idx_created_at` (`created_at`),
                        KEY `fk_alert_affected_user` (`affected_user_id`),
                        KEY `fk_alert_resolved_by` (`resolved_by`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ";
                $conn->query($create_table_sql);
            }
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            
            $stmt = $conn->prepare("
                INSERT INTO security_logs (event_type, title, description, category, affected_user_id, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssis", $event_type, $title, $description, $category, $affected_user_id, $ip_address);
            $stmt->execute();
            $stmt->close();
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
            return false;
        }
    }
}
?>
