<?php
require_once 'NotificationBatcher.php';
require_once 'NotificationBatchProcessors.php';

/**
 * Notification Batch Scheduler
 * Handles scheduled batch processing and cron job functionality
 */
class NotificationBatchScheduler extends NotificationBatchProcessors {
    
    private $scheduler_config;
    private $is_running = false;
    
    public function __construct($office_id, $user_id = null) {
        parent::__construct($office_id, $user_id);
        $this->loadSchedulerConfig();
    }
    
    /**
     * Load scheduler configuration
     */
    private function loadSchedulerConfig() {
        $this->scheduler_config = [
            'max_concurrent_batches' => 5,
            'batch_timeout_seconds' => 300,
            'retry_failed_batches' => true,
            'max_retry_attempts' => 3,
            'cleanup_old_logs_days' => 30,
            'performance_monitoring' => true,
            'auto_scale' => true
        ];
    }
    
    /**
     * Main scheduler runner - can be called via cron
     */
    public function runScheduler($force_run = false) {
        if ($this->is_running && !$force_run) {
            return [
                'status' => 'skipped',
                'message' => 'Scheduler already running',
                'results' => []
            ];
        }
        
        $this->is_running = true;
        $start_time = microtime(true);
        $results = [];
        
        try {
            // Process different types of scheduled tasks
            $results['immediate_batches'] = $this->processImmediateBatches();
            $results['scheduled_batches'] = $this->processScheduledBatches();
            $results['periodic_batches'] = $this->processPeriodicBatches();
            $results['failed_retries'] = $this->retryFailedBatches();
            $results['cleanup'] = $this->cleanupOldRecords();
            $results['monitoring'] = $this->updateMonitoringMetrics();
            
            $total_time = round((microtime(true) - $start_time) * 1000);
            
            $results['summary'] = [
                'total_processing_time_ms' => $total_time,
                'batches_processed' => array_sum([
                    $results['immediate_batches']['processed'],
                    $results['scheduled_batches']['processed'],
                    $results['periodic_batches']['processed'],
                    $results['failed_retries']['processed']
                ]),
                'total_errors' => array_sum([
                    $results['immediate_batches']['errors'],
                    $results['scheduled_batches']['errors'],
                    $results['periodic_batches']['errors'],
                    $results['failed_retries']['errors']
                ])
            ];
            
            // Log scheduler run
            $this->logSchedulerRun($results);
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            error_log("Notification Batch Scheduler Error: " . $e->getMessage());
        } finally {
            $this->is_running = false;
        }
        
        return $results;
    }
    
    /**
     * Process immediate batches (highest priority)
     */
    private function processImmediateBatches() {
        return $this->processBatchesByType('immediate', 10);
    }
    
    /**
     * Process scheduled batches
     */
    private function processScheduledBatches() {
        return $this->processBatchesByType('scheduled', 15);
    }
    
    /**
     * Process periodic batches
     */
    private function processPeriodicBatches() {
        return $this->processBatchesByType('periodic', 20);
    }
    
    /**
     * Process batches by type with limits
     */
    private function processBatchesByType($batch_type, $limit) {
        $sql = "SELECT * FROM notification_batches 
                WHERE status = 'pending' AND batch_type = ?
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY priority_weight DESC, created_at ASC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $batch_type, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        $errors = 0;
        
        while ($batch = $result->fetch_assoc()) {
            try {
                $this->processBatch($batch['id']);
                $processed++;
            } catch (Exception $e) {
                $this->markBatchFailed($batch['id'], $e->getMessage());
                $errors++;
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $processed + $errors
        ];
    }
    
    /**
     * Retry failed batches
     */
    private function retryFailedBatches() {
        if (!$this->scheduler_config['retry_failed_batches']) {
            return ['processed' => 0, 'errors' => 0, 'total' => 0];
        }
        
        $sql = "SELECT nb.*, COUNT(nq.id) as failed_items
                FROM notification_batches nb
                LEFT JOIN notification_queue nq ON nb.id = nq.batch_id AND nq.status = 'failed'
                WHERE nb.status = 'failed' 
                AND nb.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND nb.error_message NOT LIKE '%max_attempts%'
                GROUP BY nb.id
                HAVING failed_items > 0
                ORDER BY nb.priority_weight DESC
                LIMIT 5";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        $errors = 0;
        
        while ($batch = $result->fetch_assoc()) {
            // Check if we should retry this batch
            if ($this->shouldRetryBatch($batch)) {
                try {
                    $this->retryBatch($batch['id']);
                    $processed++;
                } catch (Exception $e) {
                    $errors++;
                }
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $processed + $errors
        ];
    }
    
    /**
     * Check if batch should be retried
     */
    private function shouldRetryBatch($batch) {
        $max_attempts = $this->scheduler_config['max_retry_attempts'];
        
        // Check individual queue items
        $sql = "SELECT COUNT(*) as items_to_retry
                FROM notification_queue 
                WHERE batch_id = ? AND status = 'failed' AND attempts < ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $batch['id'], $max_attempts);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['items_to_retry'] > 0;
    }
    
    /**
     * Retry a failed batch
     */
    private function retryBatch($batch_id) {
        // Reset batch status
        $sql = "UPDATE notification_batches 
                SET status = 'pending', 
                    error_message = NULL,
                    started_at = NULL,
                    completed_at = NULL
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        
        // Reset failed queue items for retry
        $sql = "UPDATE notification_queue 
                SET status = 'pending', 
                    error_message = NULL,
                    processed_at = NULL
                WHERE batch_id = ? AND status = 'failed'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        
        $this->logBatchEvent($batch_id, 'info', "Batch retry initiated");
    }
    
    /**
     * Clean up old records
     */
    private function cleanupOldRecords() {
        $cleaned = 0;
        $days = $this->scheduler_config['cleanup_old_logs_days'];
        
        // Clean old batch logs
        $sql = "DELETE FROM notification_batch_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $cleaned += $stmt->affected_rows;
        
        // Clean old metrics (keep last 90 days)
        $sql = "DELETE FROM notification_batch_metrics 
                WHERE date < DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $cleaned += $stmt->affected_rows;
        
        // Clean old completed batches (keep last 30 days)
        $sql = "DELETE FROM notification_batches 
                WHERE status IN ('completed', 'cancelled') 
                AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $cleaned += $stmt->affected_rows;
        
        return [
            'records_cleaned' => $cleaned,
            'cleanup_days' => $days
        ];
    }
    
    /**
     * Update monitoring metrics
     */
    private function updateMonitoringMetrics() {
        if (!$this->scheduler_config['performance_monitoring']) {
            return ['status' => 'disabled'];
        }
        
        $metrics = [];
        
        // Current queue sizes
        $metrics['pending_batches'] = $this->getPendingBatchCount();
        $metrics['queue_size'] = $this->getQueueSize();
        
        // Recent performance
        $sql = "SELECT 
                    COUNT(*) as total_batches,
                    AVG(processing_time_ms) as avg_processing_time,
                    SUM(processed_notifications) as total_notifications,
                    SUM(failed_notifications) as total_failures
                FROM notification_batches 
                WHERE completed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND status = 'completed'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $metrics['hourly_stats'] = [
            'batches_processed' => $row['total_batches'],
            'avg_processing_time_ms' => round($row['avg_processing_time']),
            'notifications_processed' => $row['total_notifications'],
            'notifications_failed' => $row['total_failures']
        ];
        
        // Check for performance issues
        $metrics['alerts'] = $this->checkPerformanceAlerts($metrics);
        
        return $metrics;
    }
    
    /**
     * Check for performance alerts
     */
    private function checkPerformanceAlerts($metrics) {
        $alerts = [];
        
        // High queue size alert
        if ($metrics['queue_size'] > 1000) {
            $alerts[] = [
                'type' => 'high_queue_size',
                'message' => "Queue size is high: {$metrics['queue_size']} items",
                'severity' => 'warning'
            ];
        }
        
        // High processing time alert
        if (isset($metrics['hourly_stats']['avg_processing_time']) && 
            $metrics['hourly_stats']['avg_processing_time'] > 10000) {
            $alerts[] = [
                'type' => 'slow_processing',
                'message' => "Average processing time is high: {$metrics['hourly_stats']['avg_processing_time']}ms",
                'severity' => 'warning'
            ];
        }
        
        // High failure rate alert
        if (isset($metrics['hourly_stats']['notifications_processed']) && 
            $metrics['hourly_stats']['notifications_processed'] > 0) {
            
            $failure_rate = ($metrics['hourly_stats']['notifications_failed'] / 
                           $metrics['hourly_stats']['notifications_processed']) * 100;
            
            if ($failure_rate > 10) {
                $alerts[] = [
                    'type' => 'high_failure_rate',
                    'message' => "High failure rate: " . round($failure_rate, 2) . "%",
                    'severity' => 'error'
                ];
            }
        }
        
        return $alerts;
    }
    
    /**
     * Log scheduler run
     */
    private function logSchedulerRun($results) {
        $log_data = [
            'scheduler_run' => date('Y-m-d H:i:s'),
            'results' => $results
        ];
        
        $sql = "INSERT INTO notification_batch_logs (batch_id, log_level, message, context_data) 
                VALUES (NULL, 'info', 'Scheduler run completed', ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', json_encode($log_data));
        $stmt->execute();
    }
    
    /**
     * Create scheduled batch for future processing
     */
    public function createScheduledBatch($notifications_data, $schedule_time, $batch_name = null) {
        $batch_name = $batch_name ?? "Scheduled Batch - " . date('Y-m-d H:i:s', strtotime($schedule_time));
        $priority_weight = $this->calculateAveragePriority($notifications_data);
        
        // Create batch
        $sql = "INSERT INTO notification_batches (batch_name, batch_type, priority_weight, scheduled_at, created_by) 
                VALUES (?, 'scheduled', ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sdis', $batch_name, $priority_weight, $schedule_time, $this->user_id);
        $stmt->execute();
        
        $batch_id = $stmt->insert_id;
        
        // Add notifications to queue
        $queued_count = 0;
        foreach ($notifications_data as $notification) {
            $sql = "INSERT INTO notification_queue (batch_id, user_id, title, message, type, priority, related_id, related_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iissssis', 
                $batch_id,
                $notification['user_id'],
                $notification['title'],
                $notification['message'],
                $notification['type'],
                $notification['priority'],
                $notification['related_id'],
                $notification['related_type']
            );
            
            if ($stmt->execute()) {
                $queued_count++;
            }
        }
        
        // Update batch count
        $this->updateBatchCount($batch_id);
        
        return [
            'batch_id' => $batch_id,
            'notifications_queued' => $queued_count,
            'scheduled_for' => $schedule_time
        ];
    }
    
    /**
     * Calculate average priority weight for notifications
     */
    private function calculateAveragePriority($notifications_data) {
        if (empty($notifications_data)) return 2.0;
        
        $total_weight = 0;
        $weights = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        
        foreach ($notifications_data as $notification) {
            $priority = $notification['priority'] ?? 'medium';
            $total_weight += $weights[$priority] ?? 2;
        }
        
        return round($total_weight / count($notifications_data), 2);
    }
    
    /**
     * Get scheduler status
     */
    public function getSchedulerStatus() {
        return [
            'is_running' => $this->is_running,
            'pending_batches' => $this->getPendingBatchCount(),
            'queue_size' => $this->getQueueSize(),
            'configuration' => $this->scheduler_config,
            'last_run' => $this->getLastSchedulerRun()
        ];
    }
    
    /**
     * Get last scheduler run time
     */
    private function getLastSchedulerRun() {
        $sql = "SELECT created_at FROM notification_batch_logs 
                WHERE batch_id IS NULL AND message LIKE 'Scheduler run completed%'
                ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['created_at'];
        }
        
        return null;
    }
}
?>
