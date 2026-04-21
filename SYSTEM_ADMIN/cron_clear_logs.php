<?php
/**
 * Cron Job Script for Monthly Log Clearing
 * This script should be scheduled to run on the first day of each month
 * 
 * Cron setup example:
 * 0 0 1 * * /usr/bin/php /path/to/PIMS/SYSTEM_ADMIN/cron_clear_logs.php
 */

// Include required files
require_once '../config.php';
require_once '../includes/logger.php';

// Prevent web access (only allow CLI execution)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('This script can only be executed from the command line.');
}

// Log the cron job start
echo "[" . date('Y-m-d H:i:s') . "] Starting monthly log clearing cron job...\n";

try {
    // Check if system_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
    if ($table_check->num_rows === 0) {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: system_logs table does not exist.\n";
        exit(1);
    }

    // Get count before clearing
    $result = $conn->query("SELECT COUNT(*) as total FROM system_logs");
    $total_before = $result->fetch_assoc()['total'];
    
    echo "[" . date('Y-m-d H:i:s') . "] Total logs before clearing: $total_before\n";

    // Clear logs older than 30 days (keep last month's logs)
    $sql = "DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute()) {
        $deleted_count = $stmt->affected_rows;
        $stmt->close();
        
        echo "[" . date('Y-m-d H:i:s') . "] Successfully deleted $deleted_count log entries (older than 30 days).\n";
        
        // Get count after clearing
        $result = $conn->query("SELECT COUNT(*) as total FROM system_logs");
        $total_after = $result->fetch_assoc()['total'];
        
        echo "[" . date('Y-m-d H:i:s') . "] Total logs after clearing: $total_after\n";
        
        // Log this action to system logs
        logSystemAction(
            'cron_monthly_log_cleanup',
            'system_logs',
            "Monthly cron job cleared $deleted_count log entries older than 30 days. Remaining logs: $total_after",
            null,
            '127.0.0.1',
            'Cron Job'
        );
        
        echo "[" . date('Y-m-d H:i:s') . "] Monthly log clearing completed successfully.\n";
        
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: Failed to clear logs - " . $conn->error . "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Cron job finished.\n";
exit(0);
?>
