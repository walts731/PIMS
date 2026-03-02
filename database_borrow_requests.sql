-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create borrow_requests table
CREATE TABLE IF NOT EXISTS `borrow_requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `requested_by` int(11) NOT NULL,
    `requested_by_office` int(11) NOT NULL,
    `requested_to_office` int(11) NOT NULL,
    `asset_id` int(11) NOT NULL,
    `purpose` text NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `status` enum('pending','approved','denied','returned') NOT NULL DEFAULT 'pending',
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` datetime DEFAULT NULL,
    `approval_notes` text DEFAULT NULL,
    `denied_by` int(11) DEFAULT NULL,
    `denied_at` datetime DEFAULT NULL,
    `denial_reason` text DEFAULT NULL,
    `returned_at` datetime DEFAULT NULL,
    `return_condition` enum('excellent','good','fair','poor') DEFAULT NULL,
    `return_notes` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_requested_by` (`requested_by`),
    KEY `idx_requested_by_office` (`requested_by_office`),
    KEY `idx_requested_to_office` (`requested_to_office`),
    KEY `idx_asset_id` (`asset_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_start_date` (`start_date`),
    KEY `idx_end_date` (`end_date`),
    CONSTRAINT `fk_borrow_requests_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrow_requests_requested_by_office` FOREIGN KEY (`requested_by_office`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrow_requests_requested_to_office` FOREIGN KEY (`requested_to_office`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrow_requests_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrow_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_borrow_requests_denied_by` FOREIGN KEY (`denied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Add indexes for better performance
CREATE INDEX `idx_borrow_requests_composite` ON `borrow_requests` (`status`, `requested_to_office`, `created_at`);
CREATE INDEX `idx_borrow_requests_outgoing` ON `borrow_requests` (`status`, `requested_by_office`, `created_at`);

-- Add comments for documentation
ALTER TABLE `borrow_requests` COMMENT = 'Table for managing asset borrow requests between offices';
