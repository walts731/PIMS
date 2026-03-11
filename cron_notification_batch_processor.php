#!/usr/bin/env php
<?php
/**
 * Notification Batch Processor Cron Job
 * Run this script every 5 minutes to process notification batches
 * 
 * Usage:
 * php cron_notification_batch_processor.php [--office_id=ID] [--force] [--verbose]
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Include required files
require_once 'config.php';
require_once 'includes/logger.php';
require_once 'OFFICE_ADMIN/includes/NotificationBatchScheduler.php';

// Parse command line arguments
$options = getopt('', ['office_id:', 'force', 'verbose', 'help']);

if (isset($options['help'])) {
    echo "Notification Batch Processor Cron Job\n";
    echo "Usage: php cron_notification_batch_processor.php [options]\n\n";
    echo "Options:\n";
    echo "  --office_id=ID    Process only for specific office ID\n";
    echo "  --force           Force run even if scheduler is already running\n";
    echo "  --verbose         Enable verbose output\n";
    echo "  --help            Show this help message\n\n";
    exit(0);
}

$office_id = isset($options['office_id']) ? (int)$options['office_id'] : null;
$force_run = isset($options['force']);
$verbose = isset($options['verbose']);

// Logging function
function logMessage($message, $level = 'info') {
    global $verbose;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_line = "[{$timestamp}] [{$level}] {$message}\n";
    
    if ($verbose) {
        echo $log_line;
    }
    
    // Also log to system log
    error_log("PIMS Notification Batch Processor: {$message}");
}

// Get offices to process
if ($office_id) {
    $offices = [['id' => $office_id]];
    logMessage("Processing single office: {$office_id}");
} else {
    // Get all active offices
    $sql = "SELECT DISTINCT office FROM users WHERE role = 'office_admin' AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $offices = [];
    while ($row = $result->fetch_assoc()) {
        $offices[] = ['id' => $row['office']];
    }
    
    logMessage("Found " . count($offices) . " offices to process");
}

$total_results = [
    'offices_processed' => 0,
    'total_batches_processed' => 0,
    'total_errors' => 0,
    'start_time' => microtime(true)
];

// Process each office
foreach ($offices as $office) {
    try {
        logMessage("Starting batch processing for office {$office['id']}", 'info');
        
        // Create scheduler instance
        $scheduler = new NotificationBatchScheduler($office['id']);
        
        // Run the scheduler
        $results = $scheduler->runScheduler($force_run);
        
        if (isset($results['error'])) {
            logMessage("Error processing office {$office['id']}: {$results['error']}", 'error');
            $total_results['total_errors']++;
            continue;
        }
        
        $total_results['offices_processed']++;
        $total_results['total_batches_processed'] += $results['summary']['batches_processed'];
        $total_results['total_errors'] += $results['summary']['total_errors'];
        
        if ($verbose) {
            logMessage("Office {$office['id']} results:", 'info');
            logMessage("  - Batches processed: {$results['summary']['batches_processed']}", 'info');
            logMessage("  - Errors: {$results['summary']['total_errors']}", 'info');
            logMessage("  - Processing time: {$results['summary']['total_processing_time_ms']}ms", 'info');
        }
        
    } catch (Exception $e) {
        logMessage("Exception processing office {$office['id']}: " . $e->getMessage(), 'error');
        $total_results['total_errors']++;
    }
}

// Calculate total processing time
$total_time = round((microtime(true) - $total_results['start_time']) * 1000);

// Final summary
logMessage("Batch processing completed", 'info');
logMessage("Offices processed: {$total_results['offices_processed']}", 'info');
logMessage("Total batches processed: {$total_results['total_batches_processed']}", 'info');
logMessage("Total errors: {$total_results['total_errors']}", 'info');
logMessage("Total processing time: {$total_time}ms", 'info');

// Exit with appropriate code
if ($total_results['total_errors'] > 0) {
    exit(1); // Error exit code
} else {
    exit(0); // Success exit code
}
?>
