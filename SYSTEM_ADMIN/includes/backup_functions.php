<?php
/**
 * Shared backup functions for PIMS
 */

// Fallback PHP-based database backup
if (!function_exists('backupDatabasePHP')) {
    function backupDatabasePHP($filename, $conn, $database) {
        try {
            $handle = fopen($filename, 'w');
            if (!$handle) return false;

            fwrite($handle, "-- PIMS Database Backup Fallback\n");
            fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");

            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) $tables[] = $row[0];

            foreach ($tables as $table) {
                // Table structure
                $result = $conn->query("SHOW CREATE TABLE `$table` text");
                if ($result) {
                    $row = $result->fetch_row();
                    fwrite($handle, "\n\n" . $row[1] . ";\n\n");
                }

                // Table data
                $result = $conn->query("SELECT * FROM `$table` LIMIT 10000"); // Limit for safety
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $fields = array_map(function($val) use ($conn) {
                            return ($val === null) ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
                        }, array_values($row));
                        fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $fields) . ");\n");
                    }
                }
            }
            fclose($handle);
            return true;
        } catch (Exception $e) {
            if (isset($handle)) fclose($handle);
            error_log("backupDatabasePHP error: " . $e->getMessage());
            return false;
        }
    }
}

// Calculate next run time
if (!function_exists('calculateNextRun')) {
    function calculateNextRun($schedule_type, $schedule_day, $schedule_time) {
        $now = new DateTime();
        $time_parts = explode(':', $schedule_time);
        $hour = (int)($time_parts[0] ?? 0);
        $minute = (int)($time_parts[1] ?? 0);
        
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
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $day_index = (int)$schedule_day;
                $day_name = $days[$day_index % 7];
                
                $next_run->modify("next $day_name");
                $next_run->setTime($hour, $minute, 0);
                
                // If today is the day and time hasn't passed, use today
                $today = new DateTime();
                $today->setTime($hour, $minute, 0);
                if ($today->format('N') == ($day_index == 0 ? 7 : $day_index) && $today > $now) {
                    $next_run = $today;
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
}

/**
 * Main function to perform a backup
 * @param mysqli $conn Database connection
 * @param array $config Configuration (name, type, include_database, include_files, created_by, online_backup, cloud_provider)
 * @return array [success => bool, message => string, errors => array, backup_id => int]
 */
function performPIMSBackup($conn, $config) {
    global $host, $username, $password, $database; // From config.php
    
    $backup_name = $config['name'];
    $backup_type = $config['type'] ?? 'full';
    $include_database = $config['include_database'] ?? 1;
    $include_files = $config['include_files'] ?? 1;
    $created_by = $config['created_by'] ?? 0;
    $online_backup = $config['online_backup'] ?? 0;
    $cloud_provider = $config['online_backup'] ? ($config['cloud_provider'] ?? null) : null;
    
    $result = ['success' => false, 'message' => '', 'errors' => [], 'backup_id' => 0];
    
    try {
        $backup_dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($backup_dir)) {
            if (!mkdir($backup_dir, 0755, true)) {
                $result['errors'][] = 'Failed to create backup directory: ' . $backup_dir;
                return $result;
            }
        }
        
        if (!is_writable($backup_dir)) {
            $result['errors'][] = 'Backup directory is not writable: ' . $backup_dir;
            return $result;
        }

        $timestamp = date('Y-m-d_H-i-s');
        $safe_backup_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $backup_name);
        $backup_filename = $safe_backup_name . '_' . $timestamp;
        $backup_path = $backup_dir . DIRECTORY_SEPARATOR . $backup_filename;
        
        // Database Backup
        if ($include_database) {
            $db_backup_file = $backup_path . '_database.sql';
            $mysqldump_paths = ['C:\\xampp\\mysql\\bin\\mysqldump.exe', 'C:\\xampp\\mysql\\bin\\mysqldump', 'mysqldump'];
            $db_success = false;
            
            foreach ($mysqldump_paths as $mysqldump) {
                // Ensure variables from config.php are available
                $cmd = escapeshellarg($mysqldump)
                    . ' --user=' . escapeshellarg($username)
                    . ($password !== '' ? ' --password=' . escapeshellarg($password) : ' --password=')
                    . ' --host=' . escapeshellarg($host)
                    . ' --single-transaction --routines --force --result-file=' . escapeshellarg($db_backup_file)
                    . ' ' . escapeshellarg($database);
                
                $output = [];
                exec($cmd . ' 2>&1', $output, $return_var);
                clearstatcache(true, $db_backup_file);
                
                if (file_exists($db_backup_file) && filesize($db_backup_file) > 100) { // Check for meaningful size
                    $db_success = true;
                    break;
                }
            }
            
            if (!$db_success) {
                $db_success = backupDatabasePHP($db_backup_file, $conn, $database);
            }
            
            if (!$db_success) {
                $result['errors'][] = 'Database backup failed';
            }
        }
        
        // Files Backup
        if ($include_files && empty($result['errors'])) {
            if (!class_exists('ZipArchive')) {
                $result['errors'][] = 'ZipArchive extension not available';
            } else {
                $zip_file = $backup_path . '_files.zip';
                $zip = new ZipArchive();
                $pims_root = dirname(dirname(__DIR__));
                
                if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $directories_to_backup = ['ADMIN', 'OFFICE_ADMIN', 'SYSTEM_ADMIN', 'USER', 'assets'];
                    foreach ($directories_to_backup as $dirName) {
                        $dir_abs = $pims_root . DIRECTORY_SEPARATOR . $dirName;
                        if (is_dir($dir_abs)) {
                            $fileIter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_abs), RecursiveIteratorIterator::LEAVES_ONLY);
                            foreach ($fileIter as $fileInfo) {
                                if (!$fileInfo->isDir()) {
                                    $filePath = $fileInfo->getRealPath();
                                    $relativePath = substr($filePath, strlen($pims_root) + 1);
                                    $zip->addFile($filePath, $relativePath);
                                }
                            }
                        }
                    }
                    $important_files = ['config.php', 'index.php', 'logout.php'];
                    foreach ($important_files as $fname) {
                        $fpath = $pims_root . DIRECTORY_SEPARATOR . $fname;
                        if (file_exists($fpath)) $zip->addFile($fpath, $fname);
                    }
                    $zip->close();
                } else {
                    $result['errors'][] = 'Failed to create files backup archive';
                }
            }
        }
        
        if (empty($result['errors'])) {
            // Save to database
            $stmt = $conn->prepare("
                INSERT INTO backups (name, type, include_files, include_database, file_path, created_by, created_at, online_backup, cloud_provider) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->bind_param("ssiisiis", $backup_name, $backup_type, $include_files, $include_database, $backup_path, $created_by, $online_backup, $cloud_provider);
            $stmt->execute();
            $result['backup_id'] = $stmt->insert_id;
            $stmt->close();
            
            // Log action (if not cron, or handle differently)
            if (function_exists('logSystemAction') && $created_by > 0) {
                logSystemAction($created_by, 'backup_created', 'backup_system', "Backup: $backup_name, Type: $backup_type");
            }
            
            $result['success'] = true;
            $result['message'] = 'Backup created successfully';
            
            // Online Backup Handling
            if ($online_backup && !empty($cloud_provider)) {
                try {
                    if (file_exists(dirname(__DIR__) . '/includes/CloudStorageAPI.php')) {
                        require_once dirname(__DIR__) . '/includes/CloudStorageAPI.php';
                        $cloudAPI = new CloudStorageAPI($cloud_provider);
                        $upload_success = false;
                        $cloud_url = '';
                        
                        if ($include_database && file_exists($db_backup_file)) {
                            $res = $cloudAPI->uploadFile($db_backup_file, basename($db_backup_file));
                            if ($res['success']) {
                                $upload_success = true;
                                $cloud_url = $res['url'];
                            }
                        }
                        
                        if ($upload_success) {
                            $stmt = $conn->prepare("UPDATE backups SET cloud_backup_status = 'completed', cloud_backup_url = ?, cloud_backup_at = NOW() WHERE id = ?");
                            $stmt->bind_param("si", $cloud_url, $result['backup_id']);
                            $stmt->execute();
                            $stmt->close();
                        } else {
                            $stmt = $conn->prepare("UPDATE backups SET cloud_backup_status = 'failed' WHERE id = ?");
                            $stmt->bind_param("i", $result['backup_id']);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                } catch (Exception $ce) {
                    error_log("Online backup error in performPIMSBackup: " . $ce->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        $result['errors'][] = 'Exception: ' . $e->getMessage();
    }
    
    return $result;
}
?>
