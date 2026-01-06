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
require_once 'includes/cloud_api.php';
require_once '../includes/logger.php';

// Log backup page access
logSystemAction($_SESSION['user_id'], 'access', 'backup', 'System admin accessed backup page');

// PHP-based database backup function
if (!function_exists('backupDatabasePHP')) {
    function backupDatabasePHP($filename, $conn, $database) {
        try {
            $tables = array();
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sql = "";
            foreach ($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                
                $createTable = $conn->query("SHOW CREATE TABLE `$table`");
                $row = $createTable->fetch_row();
                $sql .= $row[1] . ";\n\n";
                
                $result = $conn->query("SELECT * FROM `$table`");
                $numColumns = $result->field_count;
                
                while ($row = $result->fetch_row()) {
                    $sql .= "INSERT INTO `$table` VALUES(";
                    for ($i = 0; $i < $numColumns; $i++) {
                        $row[$i] = $row[$i];
                        $row[$i] = addslashes($row[$i]);
                        $row[$i] = str_replace("\n", "\\n", $row[$i]);
                        if (isset($row[$i])) {
                            $sql .= '"' . $row[$i] . '"';
                        } else {
                            $sql .= '""';
                        }
                        if ($i < $numColumns - 1) {
                            $sql .= ',';
                        }
                    }
                    $sql .= ");\n";
                }
                $sql .= "\n\n";
            }
            
            return file_put_contents($filename, $sql) !== false;
            
        } catch (Exception $e) {
            error_log("PHP database backup error: " . $e->getMessage());
            return false;
        }
    }
}

// Simulate cloud upload function (replace with actual implementation)
if (!function_exists('simulateCloudUpload')) {
    function simulateCloudUpload($backup_data, $provider) {
        // Simulate upload delay
        sleep(2);
        
        // Simulate 80% success rate for demo
        return rand(1, 100) <= 80;
    }
}

// Handle scheduled backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_scheduled_backup'])) {
    $schedule_name = trim($_POST['schedule_name']);
    $backup_type = $_POST['backup_type'];
    $schedule_type = $_POST['schedule_type'];
    $schedule_day = $_POST['schedule_day'] ?? null;
    $schedule_time = $_POST['schedule_time'];
    $include_files = isset($_POST['include_files']);
    $include_database = isset($_POST['include_database']);
    $online_backup = isset($_POST['online_backup']);
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    
    if (empty($schedule_name)) {
        $errors[] = 'Schedule name is required';
    }
    
    if ($schedule_type === 'weekly' && (empty($schedule_day) || $schedule_day < 1 || $schedule_day > 7)) {
        $errors[] = 'Valid day of week (1-7) is required for weekly schedule';
    }
    
    if ($schedule_type === 'monthly' && (empty($schedule_day) || $schedule_day < 1 || $schedule_day > 31)) {
        $errors[] = 'Valid day of month (1-31) is required for monthly schedule';
    }
    
    if (empty($errors)) {
        try {
            // Calculate next run time
            $next_run = calculateNextRun($schedule_type, $schedule_day, $schedule_time);
            
            // Insert scheduled backup
            $stmt = $conn->prepare("
                INSERT INTO scheduled_backups 
                (name, backup_type, schedule_type, schedule_day, schedule_time, include_files, include_database, online_backup, cloud_provider, next_run, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssisiiissi", $schedule_name, $backup_type, $schedule_type, $schedule_day, $schedule_time, $include_files, $include_database, $online_backup, $cloud_provider, $next_run, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'scheduled_backup_created', 'backup_system', 
                "Schedule: $schedule_name, Type: $backup_type, Frequency: $schedule_type");
            
            $success_message = 'Scheduled backup created successfully!';
            
        } catch (Exception $e) {
            error_log("Scheduled backup creation error: " . $e->getMessage());
            $errors[] = 'Failed to create scheduled backup: ' . $e->getMessage();
        }
    }
}

// Handle scheduled backup update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_scheduled_backup'])) {
    $schedule_id = $_POST['schedule_id'];
    $schedule_name = trim($_POST['schedule_name']);
    $backup_type = $_POST['backup_type'];
    $schedule_type = $_POST['schedule_type'];
    $schedule_day = $_POST['schedule_day'] ?? null;
    $schedule_time = $_POST['schedule_time'];
    $include_files = isset($_POST['include_files']);
    $include_database = isset($_POST['include_database']);
    $online_backup = isset($_POST['online_backup']);
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    
    if (empty($schedule_name)) {
        $errors[] = 'Schedule name is required';
    }
    
    if ($schedule_type === 'weekly' && (empty($schedule_day) || $schedule_day < 1 || $schedule_day > 7)) {
        $errors[] = 'Valid day of week (1-7) is required for weekly schedule';
    }
    
    if ($schedule_type === 'monthly' && (empty($schedule_day) || $schedule_day < 1 || $schedule_day > 31)) {
        $errors[] = 'Valid day of month (1-31) is required for monthly schedule';
    }
    
    if (empty($errors)) {
        try {
            // Calculate next run time
            $next_run = calculateNextRun($schedule_type, $schedule_day, $schedule_time);
            
            // Update scheduled backup
            $stmt = $conn->prepare("
                UPDATE scheduled_backups 
                SET name = ?, backup_type = ?, schedule_type = ?, schedule_day = ?, schedule_time = ?, 
                    include_files = ?, include_database = ?, online_backup = ?, cloud_provider = ?, 
                    next_run = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->bind_param("sssisiiissii", $schedule_name, $backup_type, $schedule_type, $schedule_day, $schedule_time, $include_files, $include_database, $online_backup, $cloud_provider, $next_run, $schedule_id);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'scheduled_backup_updated', 'backup_system', 
                "Schedule: $schedule_name, Type: $backup_type, Frequency: $schedule_type");
            
            $success_message = 'Scheduled backup updated successfully!';
            
        } catch (Exception $e) {
            error_log("Scheduled backup update error: " . $e->getMessage());
            $errors[] = 'Failed to update scheduled backup: ' . $e->getMessage();
        }
    }
}

// Handle scheduled backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_scheduled_backup'])) {
    $schedule_id = $_POST['schedule_id'];
    
    try {
        // Get schedule info
        $stmt = $conn->prepare("SELECT * FROM scheduled_backups WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        $stmt->close();
        
        if ($schedule) {
            // Delete scheduled backup
            $stmt = $conn->prepare("DELETE FROM scheduled_backups WHERE id = ?");
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'scheduled_backup_deleted', 'backup_system', 
                "Deleted scheduled backup: {$schedule['name']}");
            
            $success_message = 'Scheduled backup deleted successfully!';
        }
        
    } catch (Exception $e) {
        error_log("Scheduled backup deletion error: " . $e->getMessage());
        $error_message = 'Failed to delete scheduled backup';
    }
}

// Function to calculate next run time
function calculateNextRun($schedule_type, $schedule_day, $schedule_time) {
    $now = new DateTime();
    $time_parts = explode(':', $schedule_time);
    $hour = (int)$time_parts[0];
    $minute = (int)$time_parts[1];
    
    switch ($schedule_type) {
        case 'daily':
            $next_run = new DateTime();
            $next_run->setTime($hour, $minute, 0);
            if ($next_run <= $now) {
                $next_run->modify('+1 day');
            }
            break;
            
        case 'weekly':
            $next_run = new DateTime();
            $next_run->modify('next ' . date('l', strtotime("Sunday +{$schedule_day} days")));
            $next_run->setTime($hour, $minute, 0);
            if ($next_run <= $now) {
                $next_run->modify('+1 week');
            }
            break;
            
        case 'monthly':
            $next_run = new DateTime();
            $next_run->setDate($now->format('Y'), $now->format('m'), $schedule_day);
            $next_run->setTime($hour, $minute, 0);
            if ($next_run <= $now) {
                $next_run->modify('+1 month');
            }
            break;
            
        default:
            $next_run = $now;
            break;
    }
    
    return $next_run->format('Y-m-d H:i:s');
}

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $backup_name = trim($_POST['backup_name']);
    $backup_type = $_POST['backup_type'];
    $include_files = isset($_POST['include_files']);
    $include_database = isset($_POST['include_database']);
    $online_backup = isset($_POST['online_backup']);
    $cloud_provider = $online_backup ? $_POST['cloud_provider'] : null;
    
    $errors = [];
    
    if (empty($backup_name)) {
        $errors[] = 'Backup name is required';
    }
    
    if (empty($errors)) {
        try {
            // Check if backups table exists
            $table_check = $conn->prepare("DESCRIBE backups");
            $table_check->execute();
            $table_check->close();
            
            // Create backup directory if it doesn't exist
            $backup_dir = '../backups';
            if (!is_dir($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    $errors[] = 'Failed to create backup directory';
                }
            }
            
            if (empty($errors)) {
                $timestamp = date('Y-m-d_H-i-s');
                $backup_filename = $backup_name . '_' . $timestamp;
                $backup_path = $backup_dir . '/' . $backup_filename;
                
                // Create backup log entry
                $backup_details = "Backup: $backup_name, Type: $backup_type";
                if ($include_files) $backup_details .= ", Files: Yes";
                if ($include_database) $backup_details .= ", Database: Yes";
                if ($online_backup) $backup_details .= ", Online: Yes ($cloud_provider)";
                
                if ($include_database) {
                    // Database backup with better error handling
                    $db_backup_file = $backup_path . '_database.sql';
                    
                    // Try different mysqldump approaches
                    $commands = [
                        "mysqldump --user=" . escapeshellarg($username) . " --password=" . escapeshellarg($password) . " --host=" . escapeshellarg($host) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file),
                        "mysqldump -u" . escapeshellarg($username) . " -p" . escapeshellarg($password) . " -h" . escapeshellarg($host) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file),
                        "mysqldump -u" . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($db_backup_file)
                    ];
                    
                    $backup_success = false;
                    foreach ($commands as $command) {
                        exec($command . ' 2>&1', $output, $return_var);
                        if ($return_var === 0 && file_exists($db_backup_file) && filesize($db_backup_file) > 0) {
                            $backup_success = true;
                            break;
                        }
                    }
                    
                    if (!$backup_success) {
                        // Fallback to PHP-based backup
                        $backup_success = backupDatabasePHP($db_backup_file, $conn, $database);
                    }
                    
                    if (!$backup_success) {
                        $errors[] = 'Database backup failed - mysqldump not available or permission denied';
                    }
                }
                
                if ($include_files && empty($errors)) {
                    // Check if ZipArchive is available
                    if (!class_exists('ZipArchive')) {
                        $errors[] = 'ZipArchive extension not available for file backup';
                    } else {
                        // Files backup (create zip)
                        $zip_file = $backup_path . '_files.zip';
                        $zip = new ZipArchive();
                        
                        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                            // Add important directories
                            $directories_to_backup = ['ADMIN', 'OFFICE_ADMIN', 'SYSTEM_ADMIN', 'USER', 'assets'];
                            
                            foreach ($directories_to_backup as $dir) {
                                if (is_dir('../' . $dir)) {
                                    $files = new RecursiveIteratorIterator(
                                        new RecursiveDirectoryIterator('../' . $dir),
                                        RecursiveIteratorIterator::LEAVES_ONLY
                                    );
                                    
                                    foreach ($files as $name => $file) {
                                        if (!$file->isDir()) {
                                            $filePath = $file->getRealPath();
                                            $relativePath = substr($filePath, strlen(realpath('../')) + 1);
                                            $zip->addFile($filePath, $relativePath);
                                        }
                                    }
                                }
                            }
                            
                            // Add important files
                            $important_files = ['config.php', 'index.php', 'change_password.php', 'forgot_password.php', 'logout.php', 'reset_password.php'];
                            foreach ($important_files as $file) {
                                if (file_exists('../' . $file)) {
                                    $zip->addFile('../' . $file, $file);
                                }
                            }
                            
                            $zip->close();
                        } else {
                            $errors[] = 'Failed to create files backup archive';
                        }
                    }
                }
                
                if (empty($errors)) {
                    // Save backup record to database
                    $stmt = $conn->prepare("
                        INSERT INTO backups (name, type, include_files, include_database, file_path, created_by, created_at, online_backup, cloud_provider) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                    ");
                    $stmt->bind_param("ssiisisi", $backup_name, $backup_type, $include_files, $include_database, $backup_path, $_SESSION['user_id'], $online_backup, $cloud_provider);
                    $stmt->execute();
                    $backup_id = $stmt->insert_id;
                    $stmt->close();
                    
                    logSystemAction($_SESSION['user_id'], 'backup_created', 'backup_system', $backup_details);
                    $success_message = 'Backup created successfully!';
                    
                    // Handle online backup upload if enabled
                    if ($online_backup && !empty($cloud_provider) && empty($errors)) {
                        $_SESSION['pending_online_backup'] = [
                            'backup_id' => $backup_id,
                            'cloud_provider' => $cloud_provider,
                            'backup_path' => $backup_path,
                            'include_database' => $include_database,
                            'include_files' => $include_files
                        ];
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Backup creation error: " . $e->getMessage());
            $errors[] = 'Backup creation failed: ' . $e->getMessage();
        }
    }
}

// Handle online backup upload
if (isset($_SESSION['pending_online_backup']) && !empty($_SESSION['pending_online_backup'])) {
    $pending_backup = $_SESSION['pending_online_backup'];
    
    try {
        // Update backup status to uploading
        $stmt = $conn->prepare("UPDATE backups SET cloud_backup_status = 'uploading' WHERE id = ?");
        $stmt->bind_param("i", $pending_backup['backup_id']);
        $stmt->execute();
        $stmt->close();
        
        // Initialize cloud API
        $cloudAPI = new CloudStorageAPI($pending_backup['cloud_provider']);
        $upload_success = false;
        $cloud_url = '';
        $error_message = '';
        
        // Upload database file if included
        if ($pending_backup['include_database']) {
            $db_file = $pending_backup['backup_path'] . '_database.sql';
            if (file_exists($db_file)) {
                $result = $cloudAPI->uploadFile($db_file, basename($db_file));
                
                if ($result['success']) {
                    $upload_success = true;
                    $cloud_url = $result['url'];
                    
                    // Log successful upload
                    logSystemAction($_SESSION['user_id'], 'database_cloud_upload', 'backup_system', 
                        "Database uploaded to {$pending_backup['cloud_provider']}: {$result['file_id']}");
                } elseif (isset($result['auth_required'])) {
                    // Redirect to OAuth flow
                    $_SESSION['cloud_oauth_state'] = base64_encode(json_encode([
                        'backup_id' => $pending_backup['backup_id'],
                        'cloud_provider' => $pending_backup['cloud_provider'],
                        'backup_path' => $pending_backup['backup_path'],
                        'include_database' => $pending_backup['include_database'],
                        'include_files' => $pending_backup['include_files']
                    ]));
                    
                    header('Location: ' . $result['auth_url']);
                    exit();
                } else {
                    $error_message = 'Database upload failed';
                }
            }
        }
        
        // Upload files zip if included
        if ($pending_backup['include_files'] && $upload_success) {
            $files_zip = $pending_backup['backup_path'] . '_files.zip';
            if (file_exists($files_zip)) {
                $result = $cloudAPI->uploadFile($files_zip, basename($files_zip));
                
                if ($result['success']) {
                    $cloud_url = $result['url']; // Update with files URL or keep database URL
                    
                    // Log successful upload
                    logSystemAction($_SESSION['user_id'], 'files_cloud_upload', 'backup_system', 
                        "Files uploaded to {$pending_backup['cloud_provider']}: {$result['file_id']}");
                } else {
                    $upload_success = false;
                    $error_message = 'Files upload failed';
                }
            }
        }
        
        // Update backup record with cloud backup results
        if ($upload_success) {
            $stmt = $conn->prepare("
                UPDATE backups 
                SET cloud_backup_status = 'completed', 
                    cloud_backup_url = ?, 
                    cloud_backup_at = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $cloud_url, $pending_backup['backup_id']);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'online_backup_completed', 'backup_system', 
                "Online backup completed: {$pending_backup['cloud_provider']}");
        } else {
            $stmt = $conn->prepare("
                UPDATE backups 
                SET cloud_backup_status = 'failed', 
                    cloud_backup_error = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $error_message, $pending_backup['backup_id']);
            $stmt->execute();
            $stmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Online backup upload error: " . $e->getMessage());
        $stmt = $conn->prepare("
            UPDATE backups 
            SET cloud_backup_status = 'failed', 
                cloud_backup_error = ? 
            WHERE id = ?
        ");
        $error_msg = $e->getMessage();
        $stmt->bind_param("si", $error_msg, $pending_backup['backup_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Clear pending backup
    unset($_SESSION['pending_online_backup']);
}

// Handle OAuth callback state
if (isset($_SESSION['cloud_oauth_state']) && !empty($_SESSION['cloud_oauth_state'])) {
    $oauth_state = json_decode(base64_decode($_SESSION['cloud_oauth_state']), true);
    if ($oauth_state) {
        $_SESSION['pending_online_backup'] = $oauth_state;
        unset($_SESSION['cloud_oauth_state']);
    }
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $backup_id = $_POST['backup_id'];
    
    try {
        // Get backup info
        $stmt = $conn->prepare("SELECT * FROM backups WHERE id = ?");
        $stmt->bind_param("i", $backup_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $backup = $result->fetch_assoc();
        $stmt->close();
        
        if ($backup) {
            // Delete backup files
            if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                unlink($backup['file_path'] . '_database.sql');
            }
            if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                unlink($backup['file_path'] . '_files.zip');
            }
            
            // Delete database record
            $stmt = $conn->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->bind_param("i", $backup_id);
            $stmt->execute();
            $stmt->close();
            
            logSystemAction($_SESSION['user_id'], 'backup_deleted', 'backup_system', "Deleted backup: {$backup['name']}");
            
            $success_message = 'Backup deleted successfully!';
        }
        
    } catch (Exception $e) {
        error_log("Backup deletion error: " . $e->getMessage());
        $error_message = 'Failed to delete backup';
    }
}

// Get existing backups
$backups = [];
try {
    $stmt = $conn->prepare("
        SELECT b.*, u.first_name, u.last_name, u.username 
        FROM backups b 
        LEFT JOIN users u ON b.created_by = u.id 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $backups[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching backups: " . $e->getMessage());
}

// Get scheduled backups
$scheduled_backups = [];
try {
    $stmt = $conn->prepare("
        SELECT s.*, u.first_name, u.last_name, u.username 
        FROM scheduled_backups s 
        LEFT JOIN users u ON s.created_by = u.id 
        WHERE s.is_active = TRUE
        ORDER BY s.next_run ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $scheduled_backups[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching scheduled backups: " . $e->getMessage());
}

// Get cloud providers for dropdown
$cloud_providers = [];
try {
    $stmt = $conn->prepare("SELECT provider, api_key FROM online_backup_configs WHERE is_active = TRUE");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $cloud_providers[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching cloud providers: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup System - PIMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/index.css" rel="stylesheet">
    <link href="../assets/css/theme-custom.css" rel="stylesheet">
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
        
        .backup-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #191BA9;
        }
        
        .backup-item {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #191BA9;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .backup-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #191BA9;
        }
        
        .backup-item .btn {
            min-width: 70px;
            transition: all 0.2s ease;
        }
        
        .backup-item .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .backup-item .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .backup-item .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        
        .backup-item .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .backup-item .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #191BA9;
            box-shadow: 0 0 0 0.2rem rgba(25, 27, 169, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(25, 27, 169, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }
        
        .backup-type-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .backup-type-full {
            background: linear-gradient(135deg, #191BA9 0%, #5CC2F2 100%);
            color: white;
        }
        
        .backup-type-database {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .backup-type-files {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #191BA9;
        }
        
        .form-check-input:checked {
            background-color: #191BA9;
            border-color: #191BA9;
        }
        
        .online-backup-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .cloud-provider-icon {
            width: 20px;
            height: 20px;
            margin-right: 0.25rem;
        }
    </style>
</head>
<body>
    <?php
    // Set page title for topbar
    $page_title = 'Backup System';
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
                            <i class="bi bi-cloud-download"></i> Backup System
                        </h1>
                        <p class="text-muted mb-0">Create and manage system backups</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                            <i class="bi bi-plus-circle"></i> Create Backup
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($backups); ?></div>
                        <div class="text-muted">Total Backups</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $total_size = 0;
                            foreach ($backups as $backup) {
                                if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                                    $total_size += filesize($backup['file_path'] . '_database.sql');
                                }
                                if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                                    $total_size += filesize($backup['file_path'] . '_files.zip');
                                }
                            }
                            echo round($total_size / 1024 / 1024, 2) . ' MB';
                            ?>
                        </div>
                        <div class="text-muted">Total Size</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $recent_backups = array_filter($backups, function($backup) {
                                return strtotime($backup['created_at']) > strtotime('-7 days');
                            });
                            echo count($recent_backups);
                            ?>
                        </div>
                        <div class="text-muted">Last 7 Days</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            $latest_backup = !empty($backups) ? $backups[0]['created_at'] : 'Never';
                            echo !empty($backups) ? date('M j', strtotime($latest_backup)) : 'Never';
                            ?>
                        </div>
                        <div class="text-muted">Last Backup</div>
                    </div>
                </div>
            </div>
            
            <!-- Scheduled Backups -->
            <div class="backup-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0"><i class="bi bi-clock"></i> Scheduled Backups</h4>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createScheduledBackupModal">
                        <i class="bi bi-plus-circle"></i> Schedule Backup
                    </button>
                </div>
                
                <?php if (empty($scheduled_backups)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clock fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No scheduled backups found. Create your first scheduled backup to automate your backups.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduled_backups as $schedule): ?>
                        <div class="backup-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($schedule['name']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?> •
                                            <i class="bi bi-calendar"></i> Next: <?php echo date('M j, Y H:i', strtotime($schedule['next_run'])); ?>
                                        </small>
                                    </p>
                                    <div>
                                        <span class="backup-type-badge backup-type-<?php echo $schedule['backup_type']; ?>">
                                            <?php echo htmlspecialchars($schedule['backup_type']); ?>
                                        </span>
                                        <span class="badge bg-secondary ms-2">
                                            <i class="bi bi-repeat"></i> <?php echo htmlspecialchars(ucwords($schedule['schedule_type'])); ?>
                                        </span>
                                        <?php if ($schedule['include_database']): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-database"></i> Database
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($schedule['include_files']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-folder"></i> Files
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($schedule['online_backup']): ?>
                                            <span class="badge bg-primary ms-2">
                                                <i class="bi bi-cloud"></i> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $schedule['cloud_provider']))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1">
                                        <strong>Frequency:</strong><br>
                                        <small class="text-muted">
                                            <?php 
                                            if ($schedule['schedule_type'] === 'daily') {
                                                echo 'Daily at ' . date('g:i A', strtotime($schedule['schedule_time']));
                                            } elseif ($schedule['schedule_type'] === 'weekly') {
                                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                echo $days[$schedule['schedule_day'] - 1] . 's at ' . date('g:i A', strtotime($schedule['schedule_time']));
                                            } elseif ($schedule['schedule_type'] === 'monthly') {
                                                echo 'Day ' . $schedule['schedule_day'] . ' at ' . date('g:i A', strtotime($schedule['schedule_time']));
                                            }
                                            ?>
                                        </small>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editScheduledBackup(<?php echo $schedule['id']; ?>)"
                                                title="Edit Schedule">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeleteScheduledBackup(<?php echo $schedule['id']; ?>, '<?php echo htmlspecialchars($schedule['name']); ?>')"
                                                title="Delete Schedule">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Backup List -->
            <div class="backup-card">
                <h4 class="mb-4"><i class="bi bi-clock-history"></i> Backup History</h4>
                
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cloud-download fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No backups found. Create your first backup to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($backup['name']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <small>
                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($backup['first_name'] . ' ' . $backup['last_name']); ?> •
                                            <i class="bi bi-calendar"></i> <?php echo date('M j, Y H:i:s', strtotime($backup['created_at'])); ?>
                                        </small>
                                    </p>
                                    <div>
                                        <span class="backup-type-badge backup-type-<?php echo $backup['type']; ?>">
                                            <?php echo htmlspecialchars($backup['type']); ?>
                                        </span>
                                        <?php if ($backup['include_database']): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="bi bi-database"></i> Database
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($backup['include_files']): ?>
                                            <span class="badge bg-info ms-2">
                                                <i class="bi bi-folder"></i> Files
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($backup['online_backup']): ?>
                                            <span class="badge bg-primary ms-2">
                                                <i class="bi bi-cloud"></i> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $backup['cloud_provider']))); ?>
                                            </span>
                                            <?php if (!empty($backup['cloud_backup_status'])): ?>
                                                <?php
                                                $status_class = [
                                                    'pending' => 'secondary',
                                                    'uploading' => 'warning',
                                                    'completed' => 'success',
                                                    'failed' => 'danger'
                                                ][$backup['cloud_backup_status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?> ms-2">
                                                    <i class="bi bi-<?php echo $backup['cloud_backup_status'] === 'completed' ? 'check-circle' : ($backup['cloud_backup_status'] === 'failed' ? 'x-circle' : 'clock'); ?>"></i>
                                                    <?php echo htmlspecialchars(ucwords($backup['cloud_backup_status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-1">
                                        <strong>Size:</strong> 
                                        <?php 
                                        $backup_size = 0;
                                        if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')) {
                                            $backup_size += filesize($backup['file_path'] . '_database.sql');
                                        }
                                        if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')) {
                                            $backup_size += filesize($backup['file_path'] . '_files.zip');
                                        }
                                        echo $backup_size > 0 ? round($backup_size / 1024 / 1024, 2) . ' MB' : 'Unknown';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex flex-wrap gap-1 justify-content-md-end">
                                        <?php if ($backup['include_database'] && file_exists($backup['file_path'] . '_database.sql')): ?>
                                            <a href="<?php echo htmlspecialchars($backup['file_path'] . '_database.sql'); ?>" 
                                               class="btn btn-sm btn-success" download title="Download Database">
                                                <i class="bi bi-database"></i> DB
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($backup['include_files'] && file_exists($backup['file_path'] . '_files.zip')): ?>
                                            <a href="<?php echo htmlspecialchars($backup['file_path'] . '_files.zip'); ?>" 
                                               class="btn btn-sm btn-info" download title="Download Files">
                                                <i class="bi bi-folder"></i> Files
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($backup['online_backup'] && !empty($backup['cloud_backup_url']) && $backup['cloud_backup_status'] === 'completed'): ?>
                                            <a href="<?php echo htmlspecialchars($backup['cloud_backup_url']); ?>" 
                                               target="_blank" class="btn btn-sm btn-primary" title="View in Cloud">
                                                <i class="bi bi-cloud"></i> Cloud
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeleteBackup(<?php echo $backup['id']; ?>, '<?php echo htmlspecialchars($backup['name']); ?>')"
                                                title="Delete Backup">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createBackupModalLabel">
                            <i class="bi bi-cloud-download"></i> Create Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="backup_name" class="form-label">Backup Name</label>
                            <input type="text" class="form-control" id="backup_name" name="backup_name" 
                                   placeholder="e.g., Daily Backup" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="backup_type" class="form-label">Backup Type</label>
                            <select class="form-select" id="backup_type" name="backup_type" required>
                                <option value="full">Full Backup</option>
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Include in Backup</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_database" name="include_database" checked>
                                <label class="form-check-label" for="include_database">
                                    <i class="bi bi-database"></i> Database Backup
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_files" name="include_files" checked>
                                <label class="form-check-label" for="include_files">
                                    <i class="bi bi-folder"></i> System Files
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="online_backup" name="online_backup">
                                <label class="form-check-label" for="online_backup">
                                    <i class="bi bi-cloud-upload"></i> Upload to Cloud Storage
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="cloud_provider_section" style="display: none;">
                            <label for="cloud_provider" class="form-label">Cloud Storage Provider</label>
                            <select class="form-select" id="cloud_provider" name="cloud_provider">
                                <option value="">Select Provider</option>
                                <option value="google_drive">Google Drive</option>
                                <option value="dropbox">Dropbox</option>
                                <option value="onedrive">OneDrive</option>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> You'll need to configure API credentials for the selected provider.
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> Full backups include both database and system files. Database backups contain all data, while file backups contain the application files.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_backup" class="btn btn-primary">
                            <i class="bi bi-cloud-download"></i> Create Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteBackupModal" tabindex="-1" aria-labelledby="deleteBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php" id="deleteBackupForm">
                    <input type="hidden" name="backup_id" id="delete_backup_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteBackupModalLabel">
                            <i class="bi bi-exclamation-triangle"></i> Delete Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Warning:</strong> This action cannot be undone!
                        </div>
                        <p>Are you sure you want to delete the backup "<span id="delete_backup_name"></span>"?</p>
                        <p class="text-muted">This will permanently remove all backup files and records.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_backup" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php require_once 'includes/logout-modal.php'; ?>
    <?php require_once 'includes/change-password-modal.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <script>
        // Sidebar functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainWrapper = document.getElementById('mainWrapper');
        const navbar = document.querySelector('.navbar');

        if (sidebarToggle && sidebar && sidebarOverlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
                mainWrapper.classList.toggle('sidebar-active');
                if (navbar) navbar.classList.toggle('sidebar-active');
                sidebarToggle.classList.toggle('sidebar-active');
            });

            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                mainWrapper.classList.remove('sidebar-active');
                if (navbar) navbar.classList.remove('sidebar-active');
                sidebarToggle.classList.remove('sidebar-active');
            });
        }
        
        // Delete backup confirmation
        function confirmDeleteBackup(backupId, backupName) {
            document.getElementById('delete_backup_id').value = backupId;
            document.getElementById('delete_backup_name').textContent = backupName;
            new bootstrap.Modal(document.getElementById('deleteBackupModal')).show();
        }
        
        // Update backup type checkboxes
        document.getElementById('backup_type')?.addEventListener('change', function() {
            const includeDatabase = document.getElementById('include_database');
            const includeFiles = document.getElementById('include_files');
            
            if (this.value === 'database') {
                includeDatabase.checked = true;
                includeFiles.checked = false;
                includeFiles.disabled = true;
            } else if (this.value === 'files') {
                includeDatabase.checked = false;
                includeFiles.checked = true;
                includeDatabase.disabled = true;
            } else {
                includeDatabase.checked = true;
                includeFiles.checked = true;
                includeDatabase.disabled = false;
                includeFiles.disabled = false;
            }
        });
        
        // Handle online backup checkbox
        document.getElementById('online_backup')?.addEventListener('change', function() {
            const cloudProviderSection = document.getElementById('cloud_provider_section');
            const cloudProvider = document.getElementById('cloud_provider');
            
            if (this.checked) {
                cloudProviderSection.style.display = 'block';
                cloudProvider.setAttribute('required', 'required');
            } else {
                cloudProviderSection.style.display = 'none';
                cloudProvider.removeAttribute('required');
            }
        });
        
        // Form validation for online backup
        document.querySelector('#createBackupModal form')?.addEventListener('submit', function(e) {
            const onlineBackup = document.getElementById('online_backup');
            const cloudProvider = document.getElementById('cloud_provider');
            
            if (onlineBackup.checked && !cloudProvider.value) {
                e.preventDefault();
                alert('Please select a cloud storage provider when online backup is enabled.');
                cloudProvider.focus();
            }
        });
        
        // Toggle schedule options based on frequency
        function toggleScheduleOptions() {
            const scheduleType = document.getElementById('schedule_type');
            const scheduleDaySection = document.getElementById('schedule_day_section');
            const scheduleDayLabel = document.getElementById('schedule_day_label');
            const scheduleDay = document.getElementById('schedule_day');
            
            if (scheduleType.value === 'daily') {
                scheduleDaySection.style.display = 'none';
            } else if (scheduleType.value === 'weekly') {
                scheduleDaySection.style.display = 'block';
                scheduleDayLabel.textContent = 'Day of Week';
                
                // Populate days of week
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                scheduleDay.innerHTML = '';
                days.forEach((day, index) => {
                    const option = document.createElement('option');
                    option.value = index + 1;
                    option.textContent = day;
                    scheduleDay.appendChild(option);
                });
            } else if (scheduleType.value === 'monthly') {
                scheduleDaySection.style.display = 'block';
                scheduleDayLabel.textContent = 'Day of Month';
                
                // Populate days of month
                scheduleDay.innerHTML = '';
                for (let i = 1; i <= 31; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i + (i === 1 || i === 21 || i === 31 ? 'st' : i === 2 || i === 22 ? 'nd' : i === 3 || i === 23 ? 'rd' : 'th');
                    scheduleDay.appendChild(option);
                }
            }
        }
        
        // Toggle cloud provider section for scheduled backups
        document.getElementById('online_backup').addEventListener('change', function() {
            const cloudSection = document.getElementById('cloud_provider_section_schedule');
            cloudSection.style.display = this.checked ? 'block' : 'none';
        });
        
        // Edit scheduled backup
        function editScheduledBackup(scheduleId) {
            // Find the schedule data from the page
            const schedules = <?php echo json_encode($scheduled_backups); ?>;
            const schedule = schedules.find(s => s.id == scheduleId);
            
            if (schedule) {
                // Populate the edit form
                document.getElementById('edit_schedule_id').value = schedule.id;
                document.getElementById('edit_schedule_name').value = schedule.name;
                document.getElementById('edit_backup_type').value = schedule.backup_type;
                document.getElementById('edit_schedule_type').value = schedule.schedule_type;
                document.getElementById('edit_schedule_time').value = schedule.schedule_time;
                document.getElementById('edit_include_database').checked = schedule.include_database == 1;
                document.getElementById('edit_include_files').checked = schedule.include_files == 1;
                document.getElementById('edit_online_backup').checked = schedule.online_backup == 1;
                
                // Set cloud provider if online backup is enabled
                if (schedule.online_backup == 1 && schedule.cloud_provider) {
                    document.getElementById('edit_cloud_provider').value = schedule.cloud_provider;
                    document.getElementById('edit_cloud_provider_section').style.display = 'block';
                } else {
                    document.getElementById('edit_cloud_provider_section').style.display = 'none';
                }
                
                // Set schedule day and show appropriate section
                if (schedule.schedule_type !== 'daily') {
                    document.getElementById('edit_schedule_day_section').style.display = 'block';
                    document.getElementById('edit_schedule_day').value = schedule.schedule_day || '';
                    
                    if (schedule.schedule_type === 'weekly') {
                        document.getElementById('edit_schedule_day_label').textContent = 'Day of Week';
                        populateEditScheduleDays('weekly');
                    } else if (schedule.schedule_type === 'monthly') {
                        document.getElementById('edit_schedule_day_label').textContent = 'Day of Month';
                        populateEditScheduleDays('monthly');
                    }
                } else {
                    document.getElementById('edit_schedule_day_section').style.display = 'none';
                }
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('editScheduledBackupModal'));
                modal.show();
            }
        }
        
        // Populate edit schedule days
        function populateEditScheduleDays(type) {
            const scheduleDay = document.getElementById('edit_schedule_day');
            scheduleDay.innerHTML = '';
            
            if (type === 'weekly') {
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                days.forEach((day, index) => {
                    const option = document.createElement('option');
                    option.value = index + 1;
                    option.textContent = day;
                    scheduleDay.appendChild(option);
                });
            } else if (type === 'monthly') {
                for (let i = 1; i <= 31; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = i + (i === 1 || i === 21 || i === 31 ? 'st' : i === 2 || i === 22 ? 'nd' : i === 3 || i === 23 ? 'rd' : 'th');
                    scheduleDay.appendChild(option);
                }
            }
        }
        
        // Toggle edit schedule options
        function toggleEditScheduleOptions() {
            const scheduleType = document.getElementById('edit_schedule_type');
            const scheduleDaySection = document.getElementById('edit_schedule_day_section');
            const scheduleDayLabel = document.getElementById('edit_schedule_day_label');
            
            if (scheduleType.value === 'daily') {
                scheduleDaySection.style.display = 'none';
            } else if (scheduleType.value === 'weekly') {
                scheduleDaySection.style.display = 'block';
                scheduleDayLabel.textContent = 'Day of Week';
                populateEditScheduleDays('weekly');
            } else if (scheduleType.value === 'monthly') {
                scheduleDaySection.style.display = 'block';
                scheduleDayLabel.textContent = 'Day of Month';
                populateEditScheduleDays('monthly');
            }
        }
        
        // Toggle cloud provider section for edit modal
        document.getElementById('edit_online_backup').addEventListener('change', function() {
            const cloudSection = document.getElementById('edit_cloud_provider_section');
            cloudSection.style.display = this.checked ? 'block' : 'none';
        });
        
        // Confirm delete scheduled backup
        function confirmDeleteScheduledBackup(scheduleId, scheduleName) {
            document.getElementById('delete_schedule_id').value = scheduleId;
            document.getElementById('delete_schedule_name').textContent = scheduleName;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteScheduledBackupModal'));
            modal.show();
        }
        
        // Initialize schedule options on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleScheduleOptions();
        });
    </script>
    
    <!-- Create Scheduled Backup Modal -->
    <div class="modal fade" id="createScheduledBackupModal" tabindex="-1" aria-labelledby="createScheduledBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createScheduledBackupModalLabel">
                            <i class="bi bi-clock"></i> Schedule Automated Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="schedule_name" class="form-label">Schedule Name</label>
                            <input type="text" class="form-control" id="schedule_name" name="schedule_name" required 
                                   placeholder="e.g., Weekly Database Backup">
                        </div>
                        
                        <div class="mb-3">
                            <label for="backup_type" class="form-label">Backup Type</label>
                            <select class="form-select" id="backup_type" name="backup_type" required>
                                <option value="full">Full Backup (Database + Files)</option>
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="schedule_type" class="form-label">Schedule Frequency</label>
                            <select class="form-select" id="schedule_type" name="schedule_type" required onchange="toggleScheduleOptions()">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="schedule_day_section" style="display: none;">
                            <label for="schedule_day" class="form-label">
                                <span id="schedule_day_label">Day</span>
                            </label>
                            <select class="form-select" id="schedule_day" name="schedule_day">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="schedule_time" class="form-label">Execution Time</label>
                            <input type="time" class="form-control" id="schedule_time" name="schedule_time" value="02:00" required>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Recommended: Early morning hours (2:00 AM - 4:00 AM) for minimal disruption
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Backup Contents</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_database" name="include_database" checked>
                                <label class="form-check-label" for="include_database">
                                    <i class="bi bi-database"></i> Database
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_files" name="include_files" checked>
                                <label class="form-check-label" for="include_files">
                                    <i class="bi bi-folder"></i> System Files
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="online_backup" name="online_backup">
                                <label class="form-check-label" for="online_backup">
                                    <i class="bi bi-cloud-upload"></i> Upload to Cloud Storage
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="cloud_provider_section_schedule" style="display: none;">
                            <label for="cloud_provider_schedule" class="form-label">Cloud Storage Provider</label>
                            <select class="form-select" id="cloud_provider_schedule" name="cloud_provider">
                                <option value="">Select Provider</option>
                                <?php foreach ($cloud_providers as $provider): ?>
                                    <option value="<?php echo $provider['provider']; ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $provider['provider']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> Automated backups will be uploaded to this cloud provider.
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle"></i>
                            <strong>Automated Backup Information:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Backups will be created automatically based on your schedule</li>
                                <li>System will calculate the next run time automatically</li>
                                <li>Failed backups will be logged for troubleshooting</li>
                                <li>Consider storage space when scheduling frequent backups</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_scheduled_backup" class="btn btn-primary">
                            <i class="bi bi-clock"></i> Schedule Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit Scheduled Backup Modal -->
    <div class="modal fade" id="editScheduledBackupModal" tabindex="-1" aria-labelledby="editScheduledBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php" id="editScheduledBackupForm">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="editScheduledBackupModalLabel">
                            <i class="bi bi-pencil"></i> Edit Scheduled Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_schedule_name" class="form-label">Schedule Name</label>
                            <input type="text" class="form-control" id="edit_schedule_name" name="schedule_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_backup_type" class="form-label">Backup Type</label>
                            <select class="form-select" id="edit_backup_type" name="backup_type" required>
                                <option value="full">Full Backup (Database + Files)</option>
                                <option value="database">Database Only</option>
                                <option value="files">Files Only</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_schedule_type" class="form-label">Schedule Frequency</label>
                            <select class="form-select" id="edit_schedule_type" name="schedule_type" required onchange="toggleEditScheduleOptions()">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="edit_schedule_day_section" style="display: none;">
                            <label for="edit_schedule_day" class="form-label">
                                <span id="edit_schedule_day_label">Day</span>
                            </label>
                            <select class="form-select" id="edit_schedule_day" name="schedule_day">
                                <!-- Will be populated by JavaScript -->
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_schedule_time" class="form-label">Execution Time</label>
                            <input type="time" class="form-control" id="edit_schedule_time" name="schedule_time" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Backup Contents</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_include_database" name="include_database">
                                <label class="form-check-label" for="edit_include_database">
                                    <i class="bi bi-database"></i> Database
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_include_files" name="include_files">
                                <label class="form-check-label" for="edit_include_files">
                                    <i class="bi bi-folder"></i> System Files
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_online_backup" name="online_backup">
                                <label class="form-check-label" for="edit_online_backup">
                                    <i class="bi bi-cloud-upload"></i> Upload to Cloud Storage
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="edit_cloud_provider_section" style="display: none;">
                            <label for="edit_cloud_provider" class="form-label">Cloud Storage Provider</label>
                            <select class="form-select" id="edit_cloud_provider" name="cloud_provider">
                                <option value="">Select Provider</option>
                                <?php foreach ($cloud_providers as $provider): ?>
                                    <option value="<?php echo $provider['provider']; ?>">
                                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $provider['provider']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_scheduled_backup" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Update Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Scheduled Backup Modal -->
    <div class="modal fade" id="deleteScheduledBackupModal" tabindex="-1" aria-labelledby="deleteScheduledBackupModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="backup.php" id="deleteScheduledBackupForm">
                    <input type="hidden" name="schedule_id" id="delete_schedule_id">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteScheduledBackupModalLabel">
                            <i class="bi bi-exclamation-triangle"></i> Delete Scheduled Backup
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Warning:</strong> This action cannot be undone. All future scheduled backups will be cancelled.
                        </div>
                        <p>Are you sure you want to delete the scheduled backup <strong id="delete_schedule_name"></strong>?</p>
                        <p class="text-muted">This will remove the schedule and stop any future automated backups.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_scheduled_backup" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
