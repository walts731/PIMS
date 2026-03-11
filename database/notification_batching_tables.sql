-- Notification Batching System Tables for PIMS
-- Created for OFFICE_ADMIN notification batching functionality

-- Table for storing notification batches
CREATE TABLE `notification_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_name` varchar(255) NOT NULL,
  `batch_type` enum('immediate','scheduled','periodic') NOT NULL DEFAULT 'immediate',
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `total_notifications` int(11) NOT NULL DEFAULT 0,
  `processed_notifications` int(11) NOT NULL DEFAULT 0,
  `failed_notifications` int(11) NOT NULL DEFAULT 0,
  `priority_weight` decimal(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Higher priority batches processed first',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `error_message` text DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL COMMENT 'Total processing time in milliseconds',
  PRIMARY KEY (`id`),
  KEY `idx_batch_status` (`status`),
  KEY `idx_batch_type` (`batch_type`),
  KEY `idx_priority_weight` (`priority_weight` DESC),
  KEY `idx_scheduled_at` (`scheduled_at`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing individual notification queue items
CREATE TABLE `notification_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','system') NOT NULL DEFAULT 'info',
  `priority` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','skipped') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `priority_score` int(11) GENERATED ALWAYS AS (
    CASE priority
      WHEN 'critical' THEN 1000
      WHEN 'high' THEN 750
      WHEN 'medium' THEN 500
      WHEN 'low' THEN 250
    END
  ) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_user_status` (`user_id`, `status`),
  KEY `idx_priority_score` (`priority_score` DESC),
  KEY `idx_status_priority` (`status`, `priority_score` DESC),
  CONSTRAINT `fk_notification_queue_batch` FOREIGN KEY (`batch_id`) REFERENCES `notification_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for batch configuration and rules
CREATE TABLE `notification_batch_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(255) NOT NULL,
  `notification_type` varchar(50) NOT NULL COMMENT 'Type of notifications this rule applies to',
  `batch_size` int(11) NOT NULL DEFAULT 50 COMMENT 'Maximum notifications per batch',
  `batch_interval_minutes` int(11) NOT NULL DEFAULT 15 COMMENT 'Minutes between batches',
  `max_batch_per_hour` int(11) NOT NULL DEFAULT 10 COMMENT 'Maximum batches per hour',
  `priority_threshold` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium' COMMENT 'Minimum priority to trigger immediate batching',
  `enable_batching` tinyint(1) NOT NULL DEFAULT 1,
  `office_id` int(11) DEFAULT NULL COMMENT 'NULL means applies to all offices',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notification_type` (`notification_type`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_enable_batching` (`enable_batching`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for batch processing logs
CREATE TABLE `notification_batch_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `log_level` enum('debug','info','warning','error','critical') NOT NULL DEFAULT 'info',
  `message` text NOT NULL,
  `context_data` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_batch_id` (`batch_id`),
  KEY `idx_log_level` (`log_level`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_batch_logs_batch` FOREIGN KEY (`batch_id`) REFERENCES `notification_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for batch statistics and metrics
CREATE TABLE `notification_batch_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `total_batches` int(11) NOT NULL DEFAULT 0,
  `successful_batches` int(11) NOT NULL DEFAULT 0,
  `failed_batches` int(11) NOT NULL DEFAULT 0,
  `total_notifications` int(11) NOT NULL DEFAULT 0,
  `successful_notifications` int(11) NOT NULL DEFAULT 0,
  `failed_notifications` int(11) NOT NULL DEFAULT 0,
  `average_batch_size` decimal(10,2) DEFAULT NULL,
  `average_processing_time_ms` int(11) DEFAULT NULL,
  `peak_hour_batches` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_date_office` (`date`, `office_id`),
  KEY `idx_date` (`date`),
  KEY `idx_office_id` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default batch rules
INSERT INTO `notification_batch_rules` (`rule_name`, `notification_type`, `batch_size`, `batch_interval_minutes`, `max_batch_per_hour`, `priority_threshold`, `created_by`) VALUES
('Default Info Notifications', 'info', 100, 30, 5, 'low', 1),
('Default Success Notifications', 'success', 100, 30, 5, 'low', 1),
('Default Warning Notifications', 'warning', 50, 15, 10, 'medium', 1),
('Default Error Notifications', 'error', 25, 10, 15, 'high', 1),
('Default Critical Notifications', 'critical', 10, 5, 20, 'critical', 1),
('Low Stock Alerts', 'low_stock', 20, 10, 12, 'high', 1),
('New Request Notifications', 'new_request', 30, 15, 8, 'medium', 1),
('Asset Maintenance Notifications', 'maintenance', 25, 20, 6, 'high', 1),
('Consumption Notifications', 'consumption', 50, 30, 4, 'low', 1),
('System Notifications', 'system', 15, 5, 20, 'high', 1);

-- Create indexes for better performance
CREATE INDEX `idx_notifications_batch_processing` ON `notifications` (`user_id`, `is_read`, `priority`, `created_at`);
CREATE INDEX `idx_notifications_user_type_priority` ON `notifications` (`user_id`, `type`, `priority`, `created_at`);
