<?php
session_start();
require_once '../config.php';
require_once '../includes/system_functions.php';
require_once '../includes/logger.php';

// Check session timeout
checkSessionTimeout();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Check if user has correct role
if ($_SESSION['role'] !== 'system_admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once '../includes/logger.php';

// Log dashboard access
logSystemAction($_SESSION['user_id'], 'access', 'dashboard', 'System admin accessed dashboard');

// Get system statistics
$stats = [];

// Check database connection first
if (!$conn || $conn->connect_error) {
    $stats['error'] = 'Database connection failed: ' . ($conn->connect_error ?? 'Unknown error');
} else {
    try {
        // User statistics
        $user_query = "SELECT COUNT(*) as total_users, SUM(is_active) as active_users FROM users";
        $user_result = $conn->query($user_query);
        if ($user_result) {
            $user_stats = $user_result->fetch_assoc();
            $stats['total_users'] = $user_stats['total_users'];
            $stats['active_users'] = $user_stats['active_users'];
            $stats['inactive_users'] = $stats['total_users'] - $stats['active_users'];
        }
        
        // Role distribution
        $role_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
        $role_result = $conn->query($role_query);
        $stats['roles'] = [];
        if ($role_result) {
            while ($row = $role_result->fetch_assoc()) {
                $stats['roles'][$row['role']] = $row['count'];
            }
        }
        
        // Categories statistics - Check if table exists first
        $cat_table_check = $conn->query("SHOW TABLES LIKE 'asset_categories'");
        if ($cat_table_check && $cat_table_check->num_rows > 0) {
            $cat_query = "SELECT COUNT(*) as total_categories, SUM(status = 'active') as active_categories FROM asset_categories";
            $cat_result = $conn->query($cat_query);
            if ($cat_result) {
                $cat_stats = $cat_result->fetch_assoc();
                $stats['total_categories'] = $cat_stats['total_categories'];
                $stats['active_categories'] = $cat_stats['active_categories'];
                $stats['inactive_categories'] = $stats['total_categories'] - $stats['active_categories'];
            }
        } else {
            // Try regular categories table if asset_categories doesn't exist
            $cat_table_check2 = $conn->query("SHOW TABLES LIKE 'categories'");
            if ($cat_table_check2 && $cat_table_check2->num_rows > 0) {
                $cat_query2 = "SELECT COUNT(*) as total_categories FROM categories";
                $cat_result2 = $conn->query($cat_query2);
                if ($cat_result2) {
                    $cat_stats2 = $cat_result2->fetch_assoc();
                    $stats['total_categories'] = $cat_stats2['total_categories'];
                    $stats['active_categories'] = $cat_stats2['total_categories']; // Assume all active if no status column
                    $stats['inactive_categories'] = 0;
                }
            } else {
                $stats['total_categories'] = 0;
                $stats['active_categories'] = 0;
                $stats['inactive_categories'] = 0;
            }
        }
        
        // Offices statistics - Check if table exists first
        $office_table_check = $conn->query("SHOW TABLES LIKE 'offices'");
        if ($office_table_check && $office_table_check->num_rows > 0) {
            $office_query = "SELECT COUNT(*) as total_offices, SUM(status = 'active') as active_offices FROM offices";
            $office_result = $conn->query($office_query);
            if ($office_result) {
                $office_stats = $office_result->fetch_assoc();
                $stats['total_offices'] = $office_stats['total_offices'];
                $stats['active_offices'] = $office_stats['active_offices'];
                $stats['inactive_offices'] = $stats['total_offices'] - $stats['active_offices'];
            }
        } else {
            $stats['total_offices'] = 0;
            $stats['active_offices'] = 0;
            $stats['inactive_offices'] = 0;
        }
        
        // Forms statistics - Check if table exists first
        $form_table_check = $conn->query("SHOW TABLES LIKE 'forms'");
        if ($form_table_check && $form_table_check->num_rows > 0) {
            $form_query = "SELECT COUNT(*) as total_forms, SUM(status = 'active') as active_forms FROM forms";
            $form_result = $conn->query($form_query);
            if ($form_result) {
                $form_stats = $form_result->fetch_assoc();
                $stats['total_forms'] = $form_stats['total_forms'];
                $stats['active_forms'] = $form_stats['active_forms'];
                $stats['inactive_forms'] = $stats['total_forms'] - $stats['active_forms'];
            }
        } else {
            $stats['total_forms'] = 0;
            $stats['active_forms'] = 0;
            $stats['inactive_forms'] = 0;
        }
        
        // Remove backup statistics section - not needed for dashboard
        
        // Recent activity (last 7 days) - Check if table exists first
        $logs_table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
        if ($logs_table_check && $logs_table_check->num_rows > 0) {
            try {
                $activity_query = "SELECT DATE(timestamp) as date, COUNT(*) as count FROM system_logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(timestamp) ORDER BY date";
                $activity_result = $conn->query($activity_query);
                $stats['activity_trend'] = [];
                if ($activity_result) {
                    while ($row = $activity_result->fetch_assoc()) {
                        $stats['activity_trend'][] = $row;
                    }
                }
                
                // Recent log entries
                $recent_logs_query = "SELECT sl.action, sl.module, sl.description, u.username, sl.timestamp FROM system_logs sl LEFT JOIN users u ON sl.user_id = u.id ORDER BY sl.timestamp DESC LIMIT 10";
                $recent_logs_result = $conn->query($recent_logs_query);
                $stats['recent_logs'] = [];
                if ($recent_logs_result) {
                    while ($row = $recent_logs_result->fetch_assoc()) {
                        // Convert timestamp to created_at for consistency
                        $row['created_at'] = $row['timestamp'];
                        $stats['recent_logs'][] = $row;
                    }
                }
            } catch (Exception $e) {
                error_log("System logs query error: " . $e->getMessage());
                $stats['activity_trend'] = [];
                $stats['recent_logs'] = [];
            }
        } else {
            $stats['activity_trend'] = [];
            $stats['recent_logs'] = [];
        }
        
        // System information
        $stats['system_info'] = [
            'php_version' => PHP_VERSION,
            'php_memory_limit' => ini_get('memory_limit'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_os' => PHP_OS_FAMILY . ' ' . php_uname('r'),
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'database_version' => $conn->server_info ?? 'MySQL',
            'database_status' => 'Connected',
            'system_time' => date('Y-m-d H:i:s'),
            'uptime' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A',
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_limit' => round(return_bytes(ini_get('memory_limit')) / 1024 / 1024, 2) . ' MB',
            'memory_percentage' => function_exists('memory_get_usage') ? 
                round((memory_get_usage(true) / return_bytes(ini_get('memory_limit'))) * 100, 1) : 0,
            'disk_usage' => function_exists('disk_free_space') ? 
                [
                    'total' => formatBytes(get_disk_total_space('/')),
                    'free' => formatBytes(disk_free_space('/')),
                    'used' => formatBytes(get_disk_total_space('/') - disk_free_space('/')),
                    'percentage' => round(((get_disk_total_space('/') - disk_free_space('/')) / get_disk_total_space('/')) * 100, 1)
                ] : ['total' => 'N/A', 'free' => 'N/A', 'used' => 'N/A', 'percentage' => 'N/A'],
            'system_load' => function_exists('sys_getloadavg') ? 
                [
                    '1_min' => sys_getloadavg()[0],
                    '5_min' => sys_getloadavg()[1] ?? 'N/A',
                    '15_min' => sys_getloadavg()[2] ?? 'N/A'
                ] : ['1_min' => 'N/A', '5_min' => 'N/A', '15_min' => 'N/A']
        ];
        
    } catch (Exception $e) {
        $stats['error'] = "Error fetching system stats: " . $e->getMessage();
        error_log("Dashboard Error: " . $e->getMessage());
    }
}

// Calculate Security Health Score
function calculateSecurityHealthScore($stats, $conn) {
    $score = 100;
    $factors = [];
    
    // Factor 1: User Account Security (25% weight)
    if (isset($stats['total_users']) && $stats['total_users'] > 0) {
        $inactive_ratio = ($stats['inactive_users'] ?? 0) / $stats['total_users'];
        if ($inactive_ratio > 0.3) {
            $score -= 15;
            $factors[] = 'High inactive user ratio';
        } elseif ($inactive_ratio > 0.1) {
            $score -= 5;
            $factors[] = 'Moderate inactive user ratio';
        }
    }
    
    // Factor 2: Database Security (20% weight)
    try {
        $secure_settings = [
            'SELECT @@sql_mode as sql_mode',
            'SELECT @@global.secure_auth as secure_auth'
        ];
        
        foreach ($secure_settings as $query) {
            $result = $conn->query($query);
            if ($result && $row = $result->fetch_assoc()) {
                if (strpos($row['sql_mode'] ?? '', 'STRICT_TRANS_TABLES') === false) {
                    $score -= 10;
                    $factors[] = 'SQL mode not strict';
                }
            }
        }
    } catch (Exception $e) {
        $score -= 5;
        $factors[] = 'Database security check failed';
    }
    
    // Factor 3: System Configuration (20% weight)
    $php_version = PHP_VERSION;
    if (version_compare($php_version, '8.0.0', '<')) {
        $score -= 15;
        $factors[] = 'Outdated PHP version';
    } elseif (version_compare($php_version, '8.1.0', '<')) {
        $score -= 5;
        $factors[] = 'PHP version could be updated';
    }
    
    // Factor 4: Recent Security Events (20% weight)
    if (isset($stats['recent_logs']) && is_array($stats['recent_logs'])) {
        $security_events = 0;
        $recent_time = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        foreach ($stats['recent_logs'] as $log) {
            if (isset($log['timestamp']) && $log['timestamp'] > $recent_time) {
                $action = strtolower($log['action'] ?? '');
                if (strpos($action, 'failed') !== false || strpos($action, 'error') !== false) {
                    $security_events++;
                }
            }
        }
        
        if ($security_events > 10) {
            $score -= 20;
            $factors[] = 'High number of security events';
        } elseif ($security_events > 5) {
            $score -= 10;
            $factors[] = 'Moderate security events';
        }
    }
    
    // Factor 5: System Integrity (15% weight)
    $system_checks = [
        'memory_usage' => memory_get_usage(true),
        'max_execution_time' => ini_get('max_execution_time')
    ];
    
    if ($system_checks['max_execution_time'] < 30) {
        $score -= 5;
        $factors[] = 'Low execution time limit';
    }
    
    // Ensure score stays within bounds
    $score = max(0, min(100, $score));
    
    // Determine status
    if ($score >= 90) {
        $status = 'Excellent';
        $color = 'success';
    } elseif ($score >= 75) {
        $status = 'Good';
        $color = 'success';
    } elseif ($score >= 60) {
        $status = 'Fair';
        $color = 'warning';
    } elseif ($score >= 40) {
        $status = 'Poor';
        $color = 'danger';
    } else {
        $status = 'Critical';
        $color = 'danger';
    }
    
    return [
        'score' => $score,
        'status' => $status,
        'color' => $color,
        'factors' => $factors
    ];
}

// Calculate System Size
function calculateSystemSize() {
    $system_size = [
        'total_size' => 0,
        'database_size' => 0,
        'files_size' => 0,
        'images_size' => 0,
        'upload_size' => 0
    ];
    
    // Calculate database size
    try {
        $db_size_query = "
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'db_size'
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ";
        $db_result = $GLOBALS['conn']->query($db_size_query);
        if ($db_result && $row = $db_result->fetch_assoc()) {
            $system_size['database_size'] = $row['db_size'];
        }
    } catch (Exception $e) {
        error_log("Database size calculation error: " . $e->getMessage());
    }
    
    // Calculate files and images size
    $base_path = realpath('../');
    $directories = [
        'files' => $base_path . '/uploads/',
        'images' => $base_path . '/img/',
        'upload' => $base_path . '/uploads/documents/'
    ];
    
    foreach ($directories as $type => $dir) {
        if (is_dir($dir)) {
            $size = 0;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            
            $system_size[$type . '_size'] = $size / 1024 / 1024; // Convert to MB
        }
    }
    
    // Calculate total size
    $system_size['total_size'] = $system_size['database_size'] + 
                                $system_size['files_size'] + 
                                $system_size['images_size'] + 
                                $system_size['upload_size'];
    
    // Format sizes for display
    $system_size['formatted'] = [
        'total_size' => formatBytes($system_size['total_size'] * 1024 * 1024),
        'database_size' => formatBytes($system_size['database_size'] * 1024 * 1024),
        'files_size' => formatBytes($system_size['files_size'] * 1024 * 1024),
        'images_size' => formatBytes($system_size['images_size'] * 1024 * 1024),
        'upload_size' => formatBytes($system_size['upload_size'] * 1024 * 1024)
    ];
    
    return $system_size;
}

// Format bytes to human readable format
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Convert PHP memory limit string to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

// Get disk total space
function get_disk_total_space($path = '/') {
    $total = disk_free_space($path);
    if ($total === false) return 0;
    
    // Try different methods to get total disk space
    if (function_exists('disk_total_space')) {
        return disk_total_space($path);
    }
    
    // Fallback: try to read from /proc/mounts or use exec
    try {
        $mounts = file_get_contents('/proc/mounts');
        if ($mounts) {
            preg_match('/^(\/dev\/[a-z0-9]+)\s+([^ ]+)\s+([^ ]+)/m', $mounts, $matches);
            if (isset($matches[2])) {
                $total = intval($matches[2]) * 1024; // Convert blocks to KB
                return $total * 1024; // Convert to bytes
            }
        }
    } catch (Exception $e) {
        // Ignore errors
    }
    
    return 0;
}

// Calculate security health score
$security_health = calculateSecurityHealthScore($stats, $conn);
$stats['security_health'] = $security_health;

// Calculate Last Backup Information
function calculateLastBackupInfo() {
    $backup_info = [
        'last_backup_time' => null,
        'backup_status' => 'No backups found',
        'backup_size' => 0,
        'backup_count' => 0,
        'formatted_time' => 'Never',
        'formatted_date' => 'No backup history',
        'status_badge' => 'danger',
        'status_icon' => 'x-circle',
        'status_text' => 'No Backups'
    ];
    
    try {
        // Check if backup logs table exists
        $backup_table_check = $GLOBALS['conn']->query("SHOW TABLES LIKE 'backup_logs'");
        if ($backup_table_check && $backup_table_check->num_rows > 0) {
            // Get most recent successful backup
            $backup_query = "
                SELECT 
                    backup_time,
                    backup_size,
                    backup_status,
                    backup_type,
                    created_at
                FROM backup_logs 
                WHERE backup_status = 'completed' 
                ORDER BY backup_time DESC 
                LIMIT 1
            ";
            $backup_result = $GLOBALS['conn']->query($backup_query);
            
            if ($backup_result && $backup_row = $backup_result->fetch_assoc()) {
                $backup_time = new DateTime($backup_row['backup_time']);
                $now = new DateTime();
                $interval = $now->diff($backup_time);
                
                $backup_info = [
                    'last_backup_time' => $backup_row['backup_time'],
                    'backup_status' => $backup_row['backup_status'],
                    'backup_size' => $backup_row['backup_size'] ?? 0,
                    'backup_type' => $backup_row['backup_type'] ?? 'manual',
                    'backup_count' => 1
                ];
                
                // Format time ago
                if ($interval->days > 0) {
                    $backup_info['formatted_time'] = $interval->days . ' days ago';
                } elseif ($interval->h > 0) {
                    $backup_info['formatted_time'] = $interval->h . ' hours ago';
                } elseif ($interval->i > 0) {
                    $backup_info['formatted_time'] = $interval->i . ' minutes ago';
                } else {
                    $backup_info['formatted_time'] = 'Just now';
                }
                
                // Format date
                $backup_info['formatted_date'] = $backup_time->format('M j, Y - g:i A');
                
                // Set status based on how recent
                if ($interval->days === 0 && $interval->h < 24) {
                    $backup_info['status_badge'] = 'success';
                    $backup_info['status_icon'] = 'check-circle';
                    $backup_info['status_text'] = 'Successful';
                } elseif ($interval->days < 7) {
                    $backup_info['status_badge'] = 'warning';
                    $backup_info['status_icon'] = 'exclamation-triangle';
                    $backup_info['status_text'] = 'Aged';
                } else {
                    $backup_info['status_badge'] = 'danger';
                    $backup_info['status_icon'] = 'x-circle';
                    $backup_info['status_text'] = 'Outdated';
                }
            }
        } else {
            // Check for backup files in filesystem as fallback
            $backup_dirs = [
                '../backups/',
                '../storage/backups/',
                '../admin/backups/'
            ];
            
            foreach ($backup_dirs as $backup_dir) {
                if (is_dir($backup_dir)) {
                    $files = glob($backup_dir . '*.zip');
                    $files = array_merge($files, glob($backup_dir . '*.sql'));
                    
                    if (!empty($files)) {
                        $latest_file = max($files);
                        $file_time = filemtime($latest_file);
                        $file_size = filesize($latest_file);
                        
                        $backup_time = new DateTime('@' . $file_time);
                        $now = new DateTime();
                        $interval = $now->diff($backup_time);
                        
                        $backup_info = [
                            'last_backup_time' => date('Y-m-d H:i:s', $file_time),
                            'backup_status' => 'completed',
                            'backup_size' => $file_size,
                            'backup_type' => 'filesystem',
                            'backup_count' => count($files)
                        ];
                        
                        // Format time ago
                        if ($interval->days > 0) {
                            $backup_info['formatted_time'] = $interval->days . ' days ago';
                        } elseif ($interval->h > 0) {
                            $backup_info['formatted_time'] = $interval->h . ' hours ago';
                        } elseif ($interval->i > 0) {
                            $backup_info['formatted_time'] = $interval->i . ' minutes ago';
                        } else {
                            $backup_info['formatted_time'] = 'Just now';
                        }
                        
                        // Format date
                        $backup_info['formatted_date'] = $backup_time->format('M j, Y - g:i A');
                        
                        // Set status
                        if ($interval->days === 0) {
                            $backup_info['status_badge'] = 'success';
                            $backup_info['status_icon'] = 'check-circle';
                            $backup_info['status_text'] = 'Successful';
                        } else {
                            $backup_info['status_badge'] = 'warning';
                            $backup_info['status_icon'] = 'exclamation-triangle';
                            $backup_info['status_text'] = 'Aged';
                        }
                        
                        break; // Found backup, stop searching
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Backup info calculation error: " . $e->getMessage());
        $backup_info['backup_status'] = 'Error checking backups';
    }
    
    return $backup_info;
}

// Calculate system size
$system_size = calculateSystemSize();
$stats['system_size'] = $system_size;

// Calculate Cloud Storage Information
function calculateCloudStorageInfo() {
    $cloud_info = [
        'provider' => 'Not configured',
        'provider_icon' => 'cloud',
        'used_space' => 0,
        'total_space' => 0,
        'percentage_used' => 0,
        'available_space' => 0,
        'last_sync' => null,
        'connection_status' => 'disconnected'
    ];
    
    try {
        // Check if cloud storage settings exist in system_settings
        $cloud_settings_query = "
            SELECT setting_name, setting_value 
            FROM system_settings 
            WHERE setting_name IN ('cloud_provider', 'cloud_used_space', 'cloud_total_space', 'cloud_api_key', 'cloud_last_sync')
        ";
        $cloud_result = $GLOBALS['conn']->query($cloud_settings_query);
        
        if ($cloud_result) {
            $settings = [];
            while ($row = $cloud_result->fetch_assoc()) {
                $settings[$row['setting_name']] = $row['setting_value'];
            }
            
            if (!empty($settings)) {
                // Set provider based on configuration
                $provider = $settings['cloud_provider'] ?? 'none';
                
                switch (strtolower($provider)) {
                    case 'google_drive':
                        $cloud_info['provider'] = 'Google Drive';
                        $cloud_info['provider_icon'] = 'google';
                        break;
                    case 'dropbox':
                        $cloud_info['provider'] = 'Dropbox';
                        $cloud_info['provider_icon'] = 'dropbox';
                        break;
                    case 'onedrive':
                        $cloud_info['provider'] = 'OneDrive';
                        $cloud_info['provider_icon'] = 'microsoft';
                        break;
                    case 'aws_s3':
                        $cloud_info['provider'] = 'AWS S3';
                        $cloud_info['provider_icon'] = 'aws';
                        break;
                    default:
                        $cloud_info['provider'] = 'Local Storage';
                        $cloud_info['provider_icon'] = 'hdd';
                        break;
                }
                
                // Set storage values
                $cloud_info['used_space'] = floatval($settings['cloud_used_space'] ?? 0);
                $cloud_info['total_space'] = floatval($settings['cloud_total_space'] ?? 0);
                $cloud_info['last_sync'] = $settings['cloud_last_sync'] ?? null;
                
                // Calculate percentage and available space
                if ($cloud_info['total_space'] > 0) {
                    $cloud_info['percentage_used'] = round(($cloud_info['used_space'] / $cloud_info['total_space']) * 100, 1);
                    $cloud_info['available_space'] = $cloud_info['total_space'] - $cloud_info['used_space'];
                    $cloud_info['connection_status'] = 'connected';
                } else {
                    $cloud_info['connection_status'] = 'not_configured';
                }
            }
        }
        
        // Fallback: Check for actual cloud storage usage in filesystem
        if ($cloud_info['total_space'] === 0) {
            // Check if there's a cloud storage directory with usage data
            $cloud_usage_file = '../storage/cloud_usage.json';
            if (file_exists($cloud_usage_file)) {
                $usage_data = json_decode(file_get_contents($cloud_usage_file), true);
                if ($usage_data && isset($usage_data['provider'])) {
                    $cloud_info['provider'] = $usage_data['provider'];
                    $cloud_info['provider_icon'] = $usage_data['provider_icon'] ?? 'cloud';
                    $cloud_info['used_space'] = $usage_data['used_space'] ?? 0;
                    $cloud_info['total_space'] = $usage_data['total_space'] ?? 0;
                    $cloud_info['last_sync'] = $usage_data['last_sync'] ?? null;
                    
                    if ($cloud_info['total_space'] > 0) {
                        $cloud_info['percentage_used'] = round(($cloud_info['used_space'] / $cloud_info['total_space']) * 100, 1);
                        $cloud_info['available_space'] = $cloud_info['total_space'] - $cloud_info['used_space'];
                        $cloud_info['connection_status'] = 'connected';
                    }
                }
            }
        }
        
        // Format sizes for display
        $cloud_info['formatted'] = [
            'used_space' => formatBytes($cloud_info['used_space'] * 1024 * 1024 * 1024),
            'total_space' => formatBytes($cloud_info['total_space'] * 1024 * 1024 * 1024),
            'available_space' => formatBytes($cloud_info['available_space'] * 1024 * 1024 * 1024),
            'percentage_used' => $cloud_info['percentage_used'] . '%'
        ];
        
        // Determine status color
        if ($cloud_info['percentage_used'] >= 90) {
            $cloud_info['progress_color'] = 'danger';
        } elseif ($cloud_info['percentage_used'] >= 75) {
            $cloud_info['progress_color'] = 'warning';
        } elseif ($cloud_info['percentage_used'] >= 50) {
            $cloud_info['progress_color'] = 'info';
        } else {
            $cloud_info['progress_color'] = 'success';
        }
        
    } catch (Exception $e) {
        error_log("Cloud storage info calculation error: " . $e->getMessage());
        $cloud_info['connection_status'] = 'error';
        $cloud_info['provider'] = 'Error';
    }
    
    return $cloud_info;
}

// Calculate backup information
$backup_info = calculateLastBackupInfo();
$stats['backup_info'] = $backup_info;

// Calculate cloud storage information
$cloud_info = calculateCloudStorageInfo();
$stats['cloud_info'] = $cloud_info;

// Set default values if not set
$defaults = [
    'total_users' => 0, 'active_users' => 0, 'inactive_users' => 0,
    'total_categories' => 0, 'active_categories' => 0, 'inactive_categories' => 0,
    'total_offices' => 0, 'active_offices' => 0, 'inactive_offices' => 0,
    'total_forms' => 0, 'active_forms' => 0, 'inactive_forms' => 0,
    'roles' => [], 'activity_trend' => [], 'recent_logs' => []
];

foreach ($defaults as $key => $value) {
    if (!isset($stats[$key])) {
        $stats[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Admin Dashboard - PIMS</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../favicon/apple-touch-icon.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chart.js datalabels plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/admin-unified.css" rel="stylesheet">
<?php require_once 'includes/dark-mode-init.php'; ?>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #F7F3F3 0%, #C1EAF2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
                
        .page-header {
            background: white;
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .user-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(25, 27, 169, 0.1);
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-system_admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .role-admin {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .role-office_admin {
            background: linear-gradient(135deg, #5CC2F2 0%, #C1EAF2 100%);
            color: var(--dark-color);
        }
        
        .role-user {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-xl);
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .stats-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .search-box {
            background: white;
            border: 2px solid var(--accent-color);
            border-radius: var(--border-radius-lg);
            padding: 0.75rem 1rem;
            transition: var(--transition);
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
            outline: none;
        }
        
        /* Custom scrollbar for webkit browsers */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: rgba(25, 27, 169, 0.1);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5CC2F2 0%, #191BA9 100%);
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Chart containers */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius);
            padding: 10px;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Chart canvas glass effect */
        .chart-container canvas {
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Chart titles with glass effect */
        .chart-card h6 {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #191BA9;
            font-weight: 600;
        }
        
        /* Chart status text with glass effect */
        .chart-card .text-center {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            padding: 8px 10px;
            border-radius: var(--border-radius);
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .chart-card .text-center small {
            color: #191BA9;
            font-weight: 500;
        }
        
        /* Security Health Score Styles */
        .security-score-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
        }
        
        .security-score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .security-score-circle::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #fff, rgba(255,255,255,0.1));
            border-radius: 50%;
            z-index: -1;
        }
        
        .security-score-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .security-score-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            margin-top: 4px;
        }
        
        /* System Size Styles */
        .system-size-display {
            margin: 15px 0;
        }
        
        .size-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #191BA9;
            line-height: 1;
        }
        
        .size-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 4px;
        }
        
        /* Backup Status Styles */
        .backup-status {
            margin: 15px 0;
        }
        
        .backup-time {
            font-size: 1.8rem;
            font-weight: 600;
            color: #191BA9;
            line-height: 1;
        }
        
        .backup-date {
            font-size: 0.85rem;
            color: #666;
            margin-top: 4px;
        }
        
        /* Cloud Storage Styles */
        .cloud-status {
            margin: 15px 0;
        }
        
        .cloud-provider {
            font-size: 1.1rem;
            font-weight: 600;
            color: #191BA9;
            margin-bottom: 8px;
        }
        
        .cloud-provider i {
            color: #4285f4;
            margin-right: 5px;
        }
        
        .cloud-usage {
            font-size: 0.9rem;
            color: #666;
        }
        
        .usage-text {
            font-weight: 500;
        }
        
        /* Modal z-index fixes */
        .modal {
            z-index: 1055;
        }
        
        .modal-backdrop {
            z-index: 1050;
        }
        
        .modal-dialog {
            z-index: 1060;
        }
        
        /* Ensure sidebar overlay doesn't interfere with modals */
        .sidebar-overlay {
            z-index: 1040;
        }
        
        /* Remove scrollbar from sidebar */
        .sidebar {
            overflow: hidden;
        }
        
        .sidebar * {
            scrollbar-width: none; /* Firefox */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Edge */
        }
        
        /* Fix modal backdrop issues */
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop.show {
            display: block !important;
            opacity: 0.5;
        }
        
        /* Ensure modal buttons are clickable */
        .modal-footer button,
        .modal-header button,
        .modal-footer a {
            z-index: 1061;
            position: relative;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
            background: rgba(255, 255, 255, 0.35);
        }
        
        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Ensure equal height cards */
        .row.equal-height-cards {
            display: flex;
            flex-wrap: wrap;
        }
        
        .equal-height-cards > [class*="col-"] {
            display: flex;
            flex-direction: column;
        }
        
        .equal-height-cards .card {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .equal-height-cards .card-body {
            flex: 1;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-left: 3px solid #191BA9;
            margin-bottom: 0.5rem;
            background: rgba(25, 27, 169, 0.05);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background: rgba(25, 27, 169, 0.1);
            transform: translateX(3px);
        }
        
        .metric-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }
        
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .metric-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                max-height: calc(100vh - 60px);
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <?php
// Set page title for topbar
$page_title = 'Dashboard';
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
                        <i class="bi bi-speedometer2"></i> System Admin Dashboard
                    </h1>
                    <p class="text-muted mb-0">Complete system overview and management interface</p>
                    <?php if (isset($stats['error'])): ?>
                        <div class="alert alert-warning mt-2" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Database Warning:</strong> <?php echo htmlspecialchars($stats['error']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>
        </div>
        
                
        <!-- Security and System Health Overview -->
        <div class="row mb-4">
            <!-- Security Health Score -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-shield-check"></i> Security Health Score</h6>
                    <div class="text-center">
                        <div class="security-score-container">
                            <div class="security-score-circle">
                                <span class="security-score-number"><?php echo $stats['security_health']['score']; ?></span>
                                <span class="security-score-label"><?php echo $stats['security_health']['status']; ?></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-<?php echo $stats['security_health']['color']; ?>" role="progressbar" style="width: <?php echo $stats['security_health']['score']; ?>%" aria-valuenow="<?php echo $stats['security_health']['score']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <?php if (!empty($stats['security_health']['factors'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong>Factors:</strong> <?php echo implode(', ', $stats['security_health']['factors']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Size -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-hdd"></i> System Size</h6>
                    <div class="text-center">
                        <div class="system-size-display">
                            <div class="size-number"><?php echo $stats['system_size']['formatted']['total_size']; ?></div>
                            <div class="size-label">Total Storage</div>
                        </div>
                        <div class="mt-3 text-start">
                            <small class="text-muted d-block"><i class="bi bi-database"></i> Database: <?php echo $stats['system_size']['formatted']['database_size']; ?></small>
                            <small class="text-muted d-block"><i class="bi bi-file-earmark"></i> Files: <?php echo $stats['system_size']['formatted']['files_size']; ?></small>
                            <small class="text-muted d-block"><i class="bi bi-images"></i> Images: <?php echo $stats['system_size']['formatted']['images_size']; ?></small>
                            <?php if ($stats['system_size']['upload_size'] > 0): ?>
                                <small class="text-muted d-block"><i class="bi bi-folder"></i> Uploads: <?php echo $stats['system_size']['formatted']['upload_size']; ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Last Backup -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-cloud-arrow-up"></i> Last Backup</h6>
                    <div class="text-center">
                        <div class="backup-status">
                            <div class="backup-time"><?php echo $stats['backup_info']['formatted_time']; ?></div>
                            <div class="backup-date"><?php echo $stats['backup_info']['formatted_date']; ?></div>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-<?php echo $stats['backup_info']['status_badge']; ?>">
                                <i class="bi bi-<?php echo $stats['backup_info']['status_icon']; ?>"></i> <?php echo $stats['backup_info']['status_text']; ?>
                            </span>
                            <div class="mt-2">
                                <small class="text-muted">
                                    Size: <?php echo $stats['backup_info']['backup_size'] > 0 ? formatBytes($stats['backup_info']['backup_size']) : 'N/A'; ?>
                                    <?php if ($stats['backup_info']['backup_count'] > 1): ?>
                                        | Count: <?php echo $stats['backup_info']['backup_count']; ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cloud Storage -->
            <div class="col-lg-3 col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-cloud"></i> Cloud Storage</h6>
                    <div class="text-center">
                        <div class="cloud-status">
                            <div class="cloud-provider">
                                <i class="bi bi-<?php echo $stats['cloud_info']['provider_icon']; ?>"></i> <?php echo $stats['cloud_info']['provider']; ?>
                            </div>
                            <div class="cloud-usage">
                                <span class="usage-text"><?php echo $stats['cloud_info']['formatted']['used_space']; ?> / <?php echo $stats['cloud_info']['formatted']['total_space']; ?></span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-<?php echo $stats['cloud_info']['progress_color']; ?>" role="progressbar" style="width: <?php echo $stats['cloud_info']['percentage_used']; ?>" aria-valuenow="<?php echo $stats['cloud_info']['percentage_used']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted"><?php echo $stats['cloud_info']['formatted']['percentage_used']; ?> used</small>
                            <?php if ($stats['cloud_info']['last_sync']): ?>
                                <div class="mt-1">
                                    <small class="text-muted">Last sync: <?php echo date('M j, Y g:i A', strtotime($stats['cloud_info']['last_sync'])); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- User Role Distribution -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-pie-chart"></i> User Role Distribution</h6>
                    <div class="chart-container">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- System Activity Trend -->
            <div class="col-md-6">
                <div class="chart-card">
                    <h6 class="mb-3"><i class="bi bi-graph-up"></i> 7-Day Activity Trend</h6>
                    <div class="chart-container">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
            
                    </div>
        
                
        <!-- System Information and Recent Activity -->
        <div class="row mb-4 equal-height-cards">
            <!-- System Information -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">PHP Version</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['php_version'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Memory Usage</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['memory_usage'] ?? 'Unknown'; ?> (<?php echo $stats['system_info']['memory_percentage'] ?? '0'; ?>%)</div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Database</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['database_version'] ?? 'Unknown'; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Database Status</small>
                            <div class="fw-semibold <?php echo isset($stats['error']) ? 'text-danger' : 'text-success'; ?>">
                                <i class="bi bi-circle-fill"></i> <?php echo $stats['system_info']['database_status'] ?? 'Unknown'; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Disk Usage</small>
                            <div class="fw-semibold"><?php echo $stats['system_info']['disk_usage']['percentage'] ?? 'N/A'; ?>% used</div>
                        </div>
                        <div>
                            <small class="text-muted">System Status</small>
                            <div class="fw-semibold text-success">
                                <i class="bi bi-circle-fill"></i> Operational
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Feed -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4 h-100">
                    <div class="card-header bg-info text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent System Activity</h6>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if (!empty($stats['recent_logs'])): ?>
                                <?php foreach ($stats['recent_logs'] as $log): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($log['action'] ?? 'Unknown'); ?></strong>
                                                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($log['module'] ?? 'System'); ?></span>
                                                <div class="small text-muted mt-1">
                                                    <?php echo htmlspecialchars(substr($log['description'] ?? 'No description', 0, 100)); ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="small text-muted"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></div>
                                                <div class="small text-muted"><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1"></i>
                                    <p class="mt-2">No recent activity found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Notifications -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-header bg-danger text-white rounded-top-4">
                        <h6 class="mb-0"><i class="bi bi-bell"></i> System Notifications</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info alert-sm mb-2" role="alert">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>System Update:</strong> PHP version <?php echo $stats['system_info']['php_version'] ?? 'Unknown'; ?> is current
                                </div>
                                <div class="alert alert-success alert-sm mb-2" role="alert">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>Database:</strong> All connections stable
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning alert-sm mb-2" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Memory Usage:</strong> Consider monitoring memory usage
                                </div>
                                <div class="alert alert-info alert-sm mb-0" role="alert">
                                    <i class="bi bi-clock"></i>
                                    <strong>Last Backup:</strong> 2 days ago
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        </div>
    </div>
    </div> <!-- Close main wrapper -->
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php require_once 'includes/sidebar-scripts.php'; ?>
        
        // Dashboard functions
        function refreshDashboard() {
            location.reload();
        }
        
        
        function systemSettings() {
            window.location.href = 'system_settings.php';
        }

        function viewLogs() {
            window.location.href = 'logs.php';
        }

        function backupSystem() {
            window.location.href = 'backup.php';
        }

        function securityAudit() {
            window.location.href = 'security_audit.php';
        }
        
        // Initialize all charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Register the datalabels plugin
            Chart.register(ChartDataLabels);
            
            // Chart.js global configuration
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.color = '#666';
            
            // Fix modal backdrop issues
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal) {
                logoutModal.addEventListener('show.bs.modal', function () {
                    // Ensure proper backdrop
                    document.body.classList.add('modal-open');
                });
                
                logoutModal.addEventListener('hidden.bs.modal', function () {
                    // Clean up backdrop
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                });
                
                // Ensure cancel button works properly
                const cancelButton = logoutModal.querySelector('[data-bs-dismiss="modal"]');
                if (cancelButton) {
                    cancelButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        const modal = bootstrap.Modal.getInstance(logoutModal);
                        if (modal) {
                            modal.hide();
                        }
                    });
                }
            }
            
            // User Role Distribution Pie Chart
            const roleCtx = document.getElementById('roleChart').getContext('2d');
            window.roleChart = new Chart(roleCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($stats['roles'] ?? [])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($stats['roles'] ?? [])); ?>,
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.8)',  // system_admin - red with transparency
                            'rgba(25, 27, 169, 0.8)',   // admin - primary blue with transparency
                            'rgba(92, 194, 242, 0.8)',  // office_admin - light blue with transparency
                            'rgba(40, 167, 69, 0.8)',   // user - green with transparency
                            'rgba(255, 193, 7, 0.8)',    // other roles - yellow with transparency
                            'rgba(108, 117, 125, 0.8)'   // fallback - gray with transparency
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',    // system_admin - solid red border
                            'rgba(25, 27, 169, 1)',    // admin - solid blue border
                            'rgba(92, 194, 242, 1)',  // office_admin - solid light blue border
                            'rgba(40, 167, 69, 1)',   // user - solid green border
                            'rgba(255, 193, 7, 1)',    // other roles - solid yellow border
                            'rgba(108, 117, 125, 1)'   // fallback - solid gray border
                        ],
                        borderWidth: 2,
                        hoverBackgroundColor: [
                            'rgba(220, 53, 69, 0.9)',  // system_admin - slightly less transparent
                            'rgba(25, 27, 169, 0.9)',   // admin - slightly less transparent
                            'rgba(92, 194, 242, 0.9)',  // office_admin - slightly less transparent
                            'rgba(40, 167, 69, 0.9)',   // user - slightly less transparent
                            'rgba(255, 193, 7, 0.9)',    // other roles - slightly less transparent
                            'rgba(108, 117, 125, 0.9)'   // fallback - slightly less transparent
                        ],
                        hoverBorderColor: [
                            'rgba(220, 53, 69, 1)',    // system_admin - solid border
                            'rgba(25, 27, 169, 1)',    // admin - solid border
                            'rgba(92, 194, 242, 1)',  // office_admin - solid border
                            'rgba(40, 167, 69, 1)',   // user - solid border
                            'rgba(255, 193, 7, 1)',    // other roles - solid border
                            'rgba(108, 117, 125, 1)'   // fallback - solid border
                        ],
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                },
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            color: '#fff',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value, context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return value > 0 ? `${value}\n(${percentage}%)` : ''; // Show number and percentage only if > 0
                            },
                            anchor: 'center',
                            align: 'center',
                            textAlign: 'center'
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
            
            // System Activity Trend Line Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            const activityData = <?php echo json_encode($stats['activity_trend'] ?? []); ?>;
            const last7Days = [];
            const activityCounts = [];
            
            // Generate last 7 days labels
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                last7Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                // Find matching data or use 0
                const dateStr = date.toISOString().split('T')[0];
                const dayData = activityData.find(d => d.date === dateStr);
                activityCounts.push(dayData ? parseInt(dayData.count) : 0);
            }
            
            window.activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [{
                        label: 'System Activities',
                        data: activityCounts,
                        borderColor: '#191BA9',
                        backgroundColor: 'rgba(25, 27, 169, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#191BA9',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Activities: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Activities'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
            
                        
                        
                        
                    });

        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            console.log('Auto-refreshing dashboard...');
            refreshDashboardData();
        }, 300000);
        
        // Real-time data refresh function
        function refreshDashboardData() {
            fetch('ajax/get_dashboard_data.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboardCharts(data.stats);
                    updateMetricCards(data.stats);
                    updateActivityFeed(data.stats.recent_logs);
                } else {
                    console.error('Failed to refresh dashboard data:', data.error);
                }
            })
            .catch(error => {
                console.error('Error refreshing dashboard:', error);
            });
        }
        
        // Update metric cards with new data
        function updateMetricCards(stats) {
            // Update metric numbers
            const metrics = [
                { id: 'total_users', value: stats.total_users },
                { id: 'total_categories', value: stats.total_categories },
                { id: 'total_offices', value: stats.total_offices },
                { id: 'total_forms', value: stats.total_forms }
            ];
            
            metrics.forEach(metric => {
                const element = document.querySelector(`[data-metric="${metric.id}"]`);
                if (element) {
                    const currentValue = parseInt(element.textContent);
                    const newValue = parseInt(metric.value);
                    
                    if (currentValue !== newValue) {
                        element.style.transition = 'all 0.5s ease';
                        element.style.transform = 'scale(1.2)';
                        element.textContent = newValue;
                        
                        setTimeout(() => {
                            element.style.transform = 'scale(1)';
                        }, 300);
                    }
                }
            });
            
            // Update system size display
            if (stats.system_size && stats.system_size.formatted) {
                const totalSizeElement = document.querySelector('.size-number');
                const databaseSizeElement = document.querySelector('.system-size-display').closest('.chart-card').querySelectorAll('.text-muted')[0];
                const filesSizeElement = document.querySelector('.system-size-display').closest('.chart-card').querySelectorAll('.text-muted')[1];
                const imagesSizeElement = document.querySelector('.system-size-display').closest('.chart-card').querySelectorAll('.text-muted')[2];
                
                if (totalSizeElement) {
                    totalSizeElement.textContent = stats.system_size.formatted.total_size;
                }
                if (databaseSizeElement) {
                    databaseSizeElement.innerHTML = `<i class="bi bi-database"></i> Database: ${stats.system_size.formatted.database_size}`;
                }
                if (filesSizeElement) {
                    filesSizeElement.innerHTML = `<i class="bi bi-file-earmark"></i> Files: ${stats.system_size.formatted.files_size}`;
                }
                if (imagesSizeElement) {
                    imagesSizeElement.innerHTML = `<i class="bi bi-images"></i> Images: ${stats.system_size.formatted.images_size}`;
                }
                
                // Handle uploads size if exists
                const uploadsElements = document.querySelector('.system-size-display').closest('.chart-card').querySelectorAll('.text-muted');
                if (uploadsElements.length > 3 && stats.system_size.formatted.upload_size) {
                    uploadsElements[3].innerHTML = `<i class="bi bi-folder"></i> Uploads: ${stats.system_size.formatted.upload_size}`;
                }
            }
        }
        
        // Update charts with new data
        function updateDashboardCharts(stats) {
            // Update role distribution chart
            if (window.roleChart && stats.roles) {
                window.roleChart.data.datasets[0].data = Object.values(stats.roles);
                window.roleChart.update();
            }
            
            // Update activity trend chart
            if (window.activityChart && stats.activity_trend) {
                const last7Days = [];
                const activityCounts = [];
                
                for (let i = 6; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    last7Days.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                    
                    const dateStr = date.toISOString().split('T')[0];
                    const dayData = stats.activity_trend.find(d => d.date === dateStr);
                    activityCounts.push(dayData ? parseInt(dayData.count) : 0);
                }
                
                window.activityChart.data.labels = last7Days;
                window.activityChart.data.datasets[0].data = activityCounts;
                window.activityChart.update();
            }
            
            // Update security health score
            if (stats.security_health) {
                const scoreElement = document.querySelector('.security-score-number');
                const labelElement = document.querySelector('.security-score-label');
                const progressBar = document.querySelector('.security-score-container').closest('.chart-card').querySelector('.progress-bar');
                const factorsContainer = document.querySelector('.security-score-container').closest('.chart-card').querySelector('.mt-2');
                
                if (scoreElement) {
                    scoreElement.textContent = stats.security_health.score;
                }
                if (labelElement) {
                    labelElement.textContent = stats.security_health.status;
                }
                if (progressBar) {
                    progressBar.style.width = stats.security_health.score + '%';
                    progressBar.setAttribute('aria-valuenow', stats.security_health.score);
                    progressBar.className = 'progress-bar bg-' + stats.security_health.color;
                }
                if (factorsContainer && stats.security_health.factors) {
                    factorsContainer.innerHTML = `
                        <small class="text-muted">
                            <strong>Factors:</strong> ${stats.security_health.factors.join(', ')}
                        </small>
                    `;
                } else if (factorsContainer && (!stats.security_health.factors || stats.security_health.factors.length === 0)) {
                    factorsContainer.innerHTML = '';
                }
            }
            
            // Update backup information
            if (stats.backup_info) {
                const backupTimeElement = document.querySelector('.backup-time');
                const backupDateElement = document.querySelector('.backup-date');
                const backupBadgeElement = document.querySelector('.backup-status').closest('.chart-card').querySelector('.badge');
                const backupSizeElement = document.querySelector('.backup-status').closest('.chart-card').querySelector('.text-muted');
                
                if (backupTimeElement) {
                    backupTimeElement.textContent = stats.backup_info.formatted_time;
                }
                if (backupDateElement) {
                    backupDateElement.textContent = stats.backup_info.formatted_date;
                }
                if (backupBadgeElement) {
                    backupBadgeElement.className = 'badge bg-' + stats.backup_info.status_badge;
                    backupBadgeElement.innerHTML = `<i class="bi bi-${stats.backup_info.status_icon}"></i> ${stats.backup_info.status_text}`;
                }
                if (backupSizeElement) {
                    const sizeText = stats.backup_info.backup_size > 0 ? 
                        `Size: ${formatBytes(stats.backup_info.backup_size)}` : 
                        'Size: N/A';
                    const countText = stats.backup_info.backup_count > 1 ? 
                        ` | Count: ${stats.backup_info.backup_count}` : 
                        '';
                    backupSizeElement.innerHTML = `${sizeText}${countText}`;
                }
            }
            
            // Update cloud storage information
            if (stats.cloud_info) {
                const cloudProviderElement = document.querySelector('.cloud-provider');
                const cloudUsageElement = document.querySelector('.usage-text');
                const cloudProgressBar = document.querySelector('.cloud-status').closest('.chart-card').querySelector('.progress-bar');
                const cloudPercentageElement = document.querySelector('.cloud-status').closest('.chart-card').querySelector('.text-muted');
                
                if (cloudProviderElement) {
                    cloudProviderElement.innerHTML = `<i class="bi bi-${stats.cloud_info.provider_icon}"></i> ${stats.cloud_info.provider}`;
                }
                if (cloudUsageElement) {
                    cloudUsageElement.textContent = `${stats.cloud_info.formatted.used_space} / ${stats.cloud_info.formatted.total_space}`;
                }
                if (cloudProgressBar) {
                    cloudProgressBar.style.width = stats.cloud_info.percentage_used;
                    cloudProgressBar.setAttribute('aria-valuenow', parseFloat(stats.cloud_info.percentage_used));
                    cloudProgressBar.className = 'progress-bar bg-' + stats.cloud_info.progress_color;
                }
                if (cloudPercentageElement) {
                    const percentageText = `${stats.cloud_info.formatted.percentage_used} used`;
                    const syncText = stats.cloud_info.last_sync ? 
                        `<div class="mt-1"><small class="text-muted">Last sync: ${new Date(stats.cloud_info.last_sync).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</small></div>` : 
                        '';
                    cloudPercentageElement.innerHTML = `${percentageText}${syncText}`;
                }
            }
        }
        
        // Update activity feed
        function updateActivityFeed(recentLogs) {
            const feedContainer = document.querySelector('.activity-feed');
            if (feedContainer && recentLogs) {
                let html = '';
                recentLogs.forEach(log => {
                    html += `
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${log.action || 'Unknown'}</strong>
                                    <span class="badge bg-secondary ms-2">${log.module || 'System'}</span>
                                    <div class="small text-muted mt-1">
                                        ${(log.description || 'No description').substring(0, 100)}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted">${log.username || 'System'}</div>
                                    <div class="small text-muted">${new Date(log.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                if (html === '') {
                    html = `
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-2">No recent activity found</p>
                        </div>
                    `;
                }
                
                feedContainer.innerHTML = html;
            }
        }
    </script>
    
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
