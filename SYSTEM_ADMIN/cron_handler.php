<?php
/**
 * PIMS Cron Handler
 * This script runs scheduled backups and other maintenance tasks.
 * Should be executed via Windows Task Scheduler or as a background process.
 */

// Define directory constants for absolute pathing
define('CRON_DIR', __DIR__);
define('PIMS_ROOT', dirname(__DIR__));

// Set execution time limit (backups can take time)
set_time_limit(1800); // 30 minutes
ini_set('memory_limit', '512M');

// Load configurations
require_once PIMS_ROOT . '/config.php';
require_once PIMS_ROOT . '/includes/system_functions.php';
require_once CRON_DIR . '/includes/backup_functions.php';

// Log start
$log_dir = CRON_DIR . '/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
$log_file = $log_dir . '/cron_' . date('Y-m') . '.log';

function cronLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

cronLog("Cron started.");

// 1. Check for scheduled backups
try {
    // Use NOW() to match database time
    $stmt = $conn->prepare("SELECT * FROM scheduled_backups WHERE enabled = 1 AND next_run <= NOW()");
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($tasks) > 0) {
        cronLog("Found " . count($tasks) . " pending backup tasks.");
        
        foreach ($tasks as $task) {
            cronLog("Running task: " . $task['name'] . " (ID: " . $task['id'] . ")");
            
            // Prepare config for performPIMSBackup
            $backupConfig = [
                'name' => 'Auto: ' . $task['name'],
                'type' => $task['backup_type'],
                'include_database' => $task['include_database'],
                'include_files' => $task['include_files'],
                'created_by' => $task['created_by'],
                'online_backup' => $task['online_backup'],
                'cloud_provider' => $task['cloud_provider']
            ];
            
            $backupResult = performPIMSBackup($conn, $backupConfig);
            
            if ($backupResult['success']) {
                cronLog("Task " . $task['id'] . " completed successfully.");
                $status = 'success';
            } else {
                cronLog("Task " . $task['id'] . " failed: " . implode(', ', $backupResult['errors']));
                $status = 'failed';
            }
            
            // Calculate next run
            $new_next_run = calculateNextRun($task['schedule_type'], $task['schedule_day'], $task['schedule_time']);
            
            // Update scheduled_backups table
            $upd = $conn->prepare("UPDATE scheduled_backups SET last_run = NOW(), next_run = ? WHERE id = ?");
            $upd->bind_param("si", $new_next_run, $task['id']);
            $upd->execute();
            $upd->close();
            
            cronLog("Next run for task " . $task['id'] . " scheduled for $new_next_run");
        }
    } else {
        cronLog("No pending tasks found.");
    }
} catch (Exception $e) {
    cronLog("CRON ERROR: " . $e->getMessage());
}

cronLog("Cron finished.");
echo "Cron execution completed at " . date('Y-m-d H:i:s') . "\n";
?>
