<?php
require_once '../config.php';
require_once '../includes/logger.php';

/**
 * Notification Batching System for PIMS Office Admin
 * Handles queuing, processing, and managing notification batches
 */
class NotificationBatcher {
    private $conn;
    private $office_id;
    private $user_id;
    private $batch_rules = [];
    
    public function __construct($office_id, $user_id = null) {
        global $conn;
        $this->conn = $conn;
        $this->office_id = $office_id;
        $this->user_id = $user_id;
        $this->loadBatchRules();
    }
    
    /**
     * Load batch rules from database
     */
    private function loadBatchRules() {
        $sql = "SELECT * FROM notification_batch_rules 
                WHERE (office_id = ? OR office_id IS NULL) AND enable_batching = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $this->office_id, $this->office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->batch_rules[$row['notification_type']] = $row;
        }
    }
    
    /**
     * Add notification to batch queue
     */
    public function queueNotification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null, $priority = 'medium', $batch_type = null) {
        // Determine if this should be batched or sent immediately
        $rule = $this->getBatchRule($type, $priority);
        
        if (!$rule || $this->shouldSendImmediately($priority, $rule)) {
            // Send immediately for high priority or when batching is disabled
            return $this->createImmediateNotification($user_id, $title, $message, $type, $related_id, $related_type, $priority);
        }
        
        // Add to batch queue
        return $this->addToBatchQueue($user_id, $title, $message, $type, $related_id, $related_type, $priority, $batch_type);
    }
    
    /**
     * Get batch rule for notification type
     */
    private function getBatchRule($type, $priority) {
        // Try specific type first
        if (isset($this->batch_rules[$type])) {
            return $this->batch_rules[$type];
        }
        
        // Try generic type based on priority
        $generic_type = $priority . '_notifications';
        if (isset($this->batch_rules[$generic_type])) {
            return $this->batch_rules[$generic_type];
        }
        
        // Fallback to default
        return $this->batch_rules['info'] ?? null;
    }
    
    /**
     * Determine if notification should be sent immediately
     */
    private function shouldSendImmediately($priority, $rule) {
        $priority_levels = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        $threshold_levels = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
        
        return $priority_levels[$priority] >= $threshold_levels[$rule['priority_threshold']];
    }
    
    /**
     * Create immediate notification (bypass batching)
     */
    private function createImmediateNotification($user_id, $title, $message, $type, $related_id, $related_type, $priority) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, priority, related_id, related_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssis', $user_id, $title, $message, $type, $priority, $related_id, $related_type);
        $stmt->execute();
        
        $notification_id = $stmt->insert_id;
        
        // Log the immediate notification
        $this->logBatchEvent(null, 'info', "Immediate notification created: {$title}", [
            'notification_id' => $notification_id,
            'user_id' => $user_id,
            'type' => $type,
            'priority' => $priority
        ]);
        
        return $notification_id;
    }
    
    /**
     * Add notification to batch queue
     */
    private function addToBatchQueue($user_id, $title, $message, $type, $related_id, $related_type, $priority, $batch_type) {
        $batch_type = $batch_type ?? 'immediate';
        
        // Find or create appropriate batch
        $batch_id = $this->findOrCreateBatch($type, $priority, $batch_type);
        
        if (!$batch_id) {
            // Fallback to immediate notification if batch creation fails
            return $this->createImmediateNotification($user_id, $title, $message, $type, $related_id, $related_type, $priority);
        }
        
        // Add to queue
        $sql = "INSERT INTO notification_queue (batch_id, user_id, title, message, type, priority, related_id, related_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iissssis', $batch_id, $user_id, $title, $message, $type, $priority, $related_id, $related_type);
        $stmt->execute();
        
        $queue_id = $stmt->insert_id;
        
        // Update batch count
        $this->updateBatchCount($batch_id);
        
        // Log the queuing
        $this->logBatchEvent($batch_id, 'info', "Notification queued: {$title}", [
            'queue_id' => $queue_id,
            'user_id' => $user_id,
            'type' => $type,
            'priority' => $priority
        ]);
        
        return $queue_id;
    }
    
    /**
     * Find existing batch or create new one
     */
    private function findOrCreateBatch($type, $priority, $batch_type) {
        $rule = $this->getBatchRule($type, $priority);
        
        if ($batch_type === 'immediate') {
            // Look for recent pending batch of same type
            $sql = "SELECT id FROM notification_batches 
                    WHERE status = 'pending' AND batch_type = 'immediate'
                    AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                    AND total_notifications < ?
                    ORDER BY priority_weight DESC, created_at ASC
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ii', $rule['batch_interval_minutes'], $rule['batch_size']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                return $row['id'];
            }
        }
        
        // Create new batch
        return $this->createBatch($type, $priority, $batch_type, $rule);
    }
    
    /**
     * Create new batch
     */
    private function createBatch($type, $priority, $batch_type, $rule) {
        $batch_name = $this->generateBatchName($type, $priority);
        $priority_weight = $this->calculatePriorityWeight($priority);
        
        $sql = "INSERT INTO notification_batches (batch_name, batch_type, priority_weight, created_by) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssdi', $batch_name, $batch_type, $priority_weight, $this->user_id);
        $stmt->execute();
        
        $batch_id = $stmt->insert_id;
        
        // Log batch creation
        $this->logBatchEvent($batch_id, 'info', "Batch created: {$batch_name}", [
            'type' => $type,
            'priority' => $priority,
            'batch_type' => $batch_type,
            'rule' => $rule['rule_name']
        ]);
        
        return $batch_id;
    }
    
    /**
     * Generate batch name
     */
    private function generateBatchName($type, $priority) {
        $timestamp = date('Y-m-d H:i:s');
        $type_label = ucfirst($type);
        $priority_label = ucfirst($priority);
        return "{$type_label} {$priority_label} Notifications - {$timestamp}";
    }
    
    /**
     * Calculate priority weight for batch ordering
     */
    private function calculatePriorityWeight($priority) {
        $weights = [
            'critical' => 4.0,
            'high' => 3.0,
            'medium' => 2.0,
            'low' => 1.0
        ];
        
        return $weights[$priority] ?? 2.0;
    }
    
    /**
     * Update batch notification count
     */
    private function updateBatchCount($batch_id) {
        $sql = "UPDATE notification_batches 
                SET total_notifications = (
                    SELECT COUNT(*) FROM notification_queue 
                    WHERE batch_id = ?
                )
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $batch_id, $batch_id);
        $stmt->execute();
    }
    
    /**
     * Process pending batches
     */
    public function processPendingBatches($max_batches = 10) {
        $processed = 0;
        $errors = 0;
        
        // Get pending batches ordered by priority
        $sql = "SELECT * FROM notification_batches 
                WHERE status = 'pending' 
                AND (scheduled_at IS NULL OR scheduled_at <= NOW())
                ORDER BY priority_weight DESC, created_at ASC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $max_batches);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($batch = $result->fetch_assoc()) {
            try {
                $this->processBatch($batch['id']);
                $processed++;
            } catch (Exception $e) {
                $this->markBatchFailed($batch['id'], $e->getMessage());
                $errors++;
                $this->logBatchEvent($batch['id'], 'error', "Batch processing failed: " . $e->getMessage());
            }
        }
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => $processed + $errors
        ];
    }
    
    /**
     * Process individual batch
     */
    public function processBatch($batch_id) {
        $start_time = microtime(true);
        
        // Mark batch as processing
        $this->markBatchProcessing($batch_id);
        
        // Get queue items for this batch
        $sql = "SELECT * FROM notification_queue 
                WHERE batch_id = ? AND status = 'pending'
                ORDER BY priority_score DESC, created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $successful = 0;
        $failed = 0;
        
        while ($queue_item = $result->fetch_assoc()) {
            try {
                if ($this->processQueueItem($queue_item)) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                $this->markQueueItemFailed($queue_item['id'], $e->getMessage());
                $failed++;
            }
        }
        
        $processing_time = round((microtime(true) - $start_time) * 1000);
        
        // Mark batch as completed
        $this->markBatchCompleted($batch_id, $successful, $failed, $processing_time);
        
        // Update metrics
        $this->updateBatchMetrics($batch_id, $successful, $failed, $processing_time);
        
        return [
            'successful' => $successful,
            'failed' => $failed,
            'processing_time_ms' => $processing_time
        ];
    }
    
    /**
     * Process individual queue item
     */
    private function processQueueItem($queue_item) {
        // Mark as processing
        $sql = "UPDATE notification_queue 
                SET status = 'processing', attempts = attempts + 1, last_attempt_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $queue_item['id']);
        $stmt->execute();
        
        // Create the actual notification
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, priority, related_id, related_type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($notification_sql);
        $stmt->bind_param('issssis', 
            $queue_item['user_id'], 
            $queue_item['title'], 
            $queue_item['message'], 
            $queue_item['type'], 
            $queue_item['priority'], 
            $queue_item['related_id'], 
            $queue_item['related_type']
        );
        
        if ($stmt->execute()) {
            // Mark as completed
            $this->markQueueItemCompleted($queue_item['id']);
            return true;
        } else {
            throw new Exception("Failed to create notification: " . $stmt->error);
        }
    }
    
    /**
     * Mark batch as processing
     */
    private function markBatchProcessing($batch_id) {
        $sql = "UPDATE notification_batches 
                SET status = 'processing', started_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        
        $this->logBatchEvent($batch_id, 'info', "Batch processing started");
    }
    
    /**
     * Mark batch as completed
     */
    private function markBatchCompleted($batch_id, $successful, $failed, $processing_time) {
        $sql = "UPDATE notification_batches 
                SET status = 'completed', 
                    completed_at = NOW(),
                    processed_notifications = ?,
                    failed_notifications = ?,
                    processing_time_ms = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiii', $successful, $failed, $processing_time, $batch_id);
        $stmt->execute();
        
        $this->logBatchEvent($batch_id, 'info', "Batch completed", [
            'successful' => $successful,
            'failed' => $failed,
            'processing_time_ms' => $processing_time
        ]);
    }
    
    /**
     * Mark batch as failed
     */
    private function markBatchFailed($batch_id, $error_message) {
        $sql = "UPDATE notification_batches 
                SET status = 'failed', 
                    completed_at = NOW(),
                    error_message = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $error_message, $batch_id);
        $stmt->execute();
    }
    
    /**
     * Mark queue item as completed
     */
    private function markQueueItemCompleted($queue_id) {
        $sql = "UPDATE notification_queue 
                SET status = 'completed', processed_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $queue_id);
        $stmt->execute();
    }
    
    /**
     * Mark queue item as failed
     */
    private function markQueueItemFailed($queue_id, $error_message) {
        $sql = "UPDATE notification_queue 
                SET status = 'failed', 
                    error_message = ?,
                    processed_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $error_message, $queue_id);
        $stmt->execute();
    }
    
    /**
     * Log batch event
     */
    private function logBatchEvent($batch_id, $level, $message, $context = null) {
        $sql = "INSERT INTO notification_batch_logs (batch_id, log_level, message, context_data) 
                VALUES (?, ?, ?, ?)";
        
        $context_json = $context ? json_encode($context) : null;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isss', $batch_id, $level, $message, $context_json);
        $stmt->execute();
    }
    
    /**
     * Update batch metrics
     */
    private function updateBatchMetrics($batch_id, $successful, $failed, $processing_time) {
        $today = date('Y-m-d');
        
        // Check if metrics exist for today
        $sql = "SELECT id FROM notification_batch_metrics 
                WHERE date = ? AND office_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $today, $this->office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update existing metrics
            $sql = "UPDATE notification_batch_metrics 
                    SET total_batches = total_batches + 1,
                        successful_batches = successful_batches + 1,
                        total_notifications = total_notifications + ?,
                        successful_notifications = successful_notifications + ?,
                        failed_notifications = failed_notifications + ?,
                        average_processing_time_ms = ?
                    WHERE id = ?";
            
            $total_notifications = $successful + $failed;
            $avg_time = $processing_time; // Simplified - should calculate rolling average
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iiiiii', $total_notifications, $successful, $failed, $avg_time, $row['id']);
            $stmt->execute();
        } else {
            // Create new metrics record
            $sql = "INSERT INTO notification_batch_metrics 
                    (date, office_id, total_batches, successful_batches, total_notifications, 
                     successful_notifications, failed_notifications, average_processing_time_ms) 
                    VALUES (?, ?, 1, 1, ?, ?, ?, ?)";
            
            $total_notifications = $successful + $failed;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('siiiii', $today, $this->office_id, $total_notifications, $successful, $failed, $processing_time);
            $stmt->execute();
        }
    }
    
    /**
     * Get batch statistics
     */
    public function getBatchStatistics($days = 7) {
        $sql = "SELECT * FROM notification_batch_metrics 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND office_id = ?
                ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $days, $this->office_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statistics = [];
        while ($row = $result->fetch_assoc()) {
            $statistics[] = $row;
        }
        
        return $statistics;
    }
    
    /**
     * Get pending batch count
     */
    public function getPendingBatchCount() {
        $sql = "SELECT COUNT(*) as count FROM notification_batches 
                WHERE status = 'pending'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }
    
    /**
     * Get queue size
     */
    public function getQueueSize() {
        $sql = "SELECT COUNT(*) as size FROM notification_queue 
                WHERE status = 'pending'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['size'];
    }
}
?>
