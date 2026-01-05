<?php
/**
 * Cron Job Script for Running Scheduled Backups
 * 
 * This script should be run via cron job at regular intervals (e.g., every hour)
 * Example cron entry:
 * 0 * * * * /usr/bin/php /path/to/your/project/cron_run_scheduled_backups.php
 */

require_once 'config.php';
require_once 'SYSTEM_ADMIN/backup.php';
require_once 'SYSTEM_ADMIN/includes/cloud_api.php';

echo "Running scheduled backups check...\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get scheduled backups that need to run
    $stmt = $conn->prepare("
        SELECT * FROM scheduled_backups 
        WHERE is_active = TRUE 
        AND next_run <= NOW()
        AND (last_run IS NULL OR last_run < next_run)
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $backups_to_run = [];
    while ($row = $result->fetch_assoc()) {
        $backups_to_run[] = $row;
    }
    $stmt->close();
    
    if (empty($backups_to_run)) {
        echo "No scheduled backups need to run at this time.\n";
        exit;
    }
    
    echo "Found " . count($backups_to_run) . " scheduled backup(s) to run:\n\n";
    
    foreach ($backups_to_run as $schedule) {
        echo "Processing: " . $schedule['name'] . "\n";
        echo "  Type: " . $schedule['backup_type'] . "\n";
        echo "  Schedule: " . $schedule['schedule_type'] . "\n";
        
        // Create execution log
        $log_stmt = $conn->prepare("
            INSERT INTO backup_execution_logs 
            (scheduled_backup_id, execution_status) 
            VALUES (?, 'running')
        ");
        $log_stmt->bind_param("i", $schedule['id']);
        $log_stmt->execute();
        $log_id = $log_stmt->insert_id;
        $log_stmt->close();
        
        try {
            // Create backup
            $backup_name = $schedule['name'] . ' - ' . date('Y-m-d_H-i-s');
            $timestamp = date('Y-m-d_H-i-s');
            $backup_filename = $backup_name . '_' . $timestamp;
            $backup_dir = '../backups';
            $backup_path = $backup_dir . '/' . $backup_filename;
            
            // Ensure backup directory exists
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $backup_success = true;
            $created_backup_id = null;
            
            // Database backup
            if ($schedule['include_database']) {
                $db_backup_file = $backup_path . '_database.sql';
                
                // Try mysqldump first
                global $host, $username, $password, $database;
                $command = "mysqldump --user=" . escapeshellarg($username) . 
                          " --password=" . escapeshellarg($password) . 
                          " --host=" . escapeshellarg($host) . 
                          " " . escapeshellarg($database) . 
                          " > " . escapeshellarg($db_backup_file);
                
                exec($command . ' 2>&1', $output, $return_var);
                
                if ($return_var !== 0 || !file_exists($db_backup_file) || filesize($db_backup_file) === 0) {
                    // Fallback to PHP backup
                    $backup_success = backupDatabasePHP($db_backup_file, $conn, $database);
                }
                
                if (!$backup_success) {
                    throw new Exception("Database backup failed");
                }
            }
            
            // Files backup
            if ($schedule['include_files'] && $backup_success) {
                if (class_exists('ZipArchive')) {
                    $files_zip = $backup_path . '_files.zip';
                    $zip = new ZipArchive();
                    
                    if ($zip->open($files_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
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
                        
                        $important_files = ['config.php', 'index.php', 'change_password.php', 'forgot_password.php', 'logout.php', 'reset_password.php'];
                        foreach ($important_files as $file) {
                            if (file_exists('../' . $file)) {
                                $zip->addFile('../' . $file, $file);
                            }
                        }
                        
                        $zip->close();
                    } else {
                        throw new Exception("Failed to create files backup archive");
                    }
                } else {
                    throw new Exception("ZipArchive extension not available");
                }
            }
            
            // Save backup record
            if ($backup_success) {
                $backup_stmt = $conn->prepare("
                    INSERT INTO backups 
                    (name, type, include_files, include_database, file_path, created_by, created_at, online_backup, cloud_provider) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                ");
                $backup_stmt->bind_param("ssiisisi", 
                    $backup_name, 
                    $schedule['backup_type'], 
                    $schedule['include_files'], 
                    $schedule['include_database'], 
                    $backup_path, 
                    $schedule['created_by'], 
                    $schedule['online_backup'], 
                    $schedule['cloud_provider']
                );
                $backup_stmt->execute();
                $created_backup_id = $backup_stmt->insert_id;
                $backup_stmt->close();
            }
            
            // Handle cloud upload
            if ($schedule['online_backup'] && $backup_success && !empty($schedule['cloud_provider'])) {
                $cloudAPI = new CloudStorageAPI($schedule['cloud_provider']);
                $cloud_success = true;
                $cloud_url = '';
                
                // Upload database
                if ($schedule['include_database']) {
                    $db_file = $backup_path . '_database.sql';
                    if (file_exists($db_file)) {
                        $result = $cloudAPI->uploadFile($db_file, basename($db_file));
                        if (!$result['success']) {
                            $cloud_success = false;
                        } else {
                            $cloud_url = $result['url'];
                        }
                    }
                }
                
                // Upload files
                if ($schedule['include_files'] && $cloud_success) {
                    $files_zip = $backup_path . '_files.zip';
                    if (file_exists($files_zip)) {
                        $result = $cloudAPI->uploadFile($files_zip, basename($files_zip));
                        if (!$result['success']) {
                            $cloud_success = false;
                        } else {
                            $cloud_url = $result['url'];
                        }
                    }
                }
                
                // Update backup with cloud status
                if ($cloud_success) {
                    $update_stmt = $conn->prepare("
                        UPDATE backups 
                        SET cloud_backup_status = 'completed', 
                            cloud_backup_url = ?, 
                            cloud_backup_at = NOW() 
                        WHERE id = ?
                    ");
                    $update_stmt->bind_param("si", $cloud_url, $created_backup_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            // Update scheduled backup
            $next_run = calculateNextRun($schedule['schedule_type'], $schedule['schedule_day'], $schedule['schedule_time']);
            $update_stmt = $conn->prepare("
                UPDATE scheduled_backups 
                SET last_run = NOW(), next_run = ? 
                WHERE id = ?
            ");
            $update_stmt->bind_param("si", $next_run, $schedule['id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Update execution log
            $log_update_stmt = $conn->prepare("
                UPDATE backup_execution_logs 
                SET execution_status = 'completed', 
                    completed_at = NOW(), 
                    backup_id = ? 
                WHERE id = ?
            ");
            $log_update_stmt->bind_param("ii", $created_backup_id, $log_id);
            $log_update_stmt->execute();
            $log_update_stmt->close();
            
            echo "  ✓ Backup completed successfully!\n";
            echo "  Next run: " . $next_run . "\n\n";
            
        } catch (Exception $e) {
            echo "  ✗ Backup failed: " . $e->getMessage() . "\n";
            
            // Update execution log with error
            $log_update_stmt = $conn->prepare("
                UPDATE backup_execution_logs 
                SET execution_status = 'failed', 
                    completed_at = NOW(), 
                    error_message = ? 
                WHERE id = ?
            ");
            $error_msg = $e->getMessage();
            $log_update_stmt->bind_param("si", $error_msg, $log_id);
            $log_update_stmt->execute();
            $log_update_stmt->close();
            
            // Update next run time (retry in 1 hour)
            $retry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update_stmt = $conn->prepare("
                UPDATE scheduled_backups 
                SET next_run = ? 
                WHERE id = ?
            ");
            $update_stmt->bind_param("si", $retry_time, $schedule['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    
    echo "Scheduled backup check completed.\n";
    
} catch (Exception $e) {
    echo "Error in scheduled backup process: " . $e->getMessage() . "\n";
}

$conn->close();
?>
