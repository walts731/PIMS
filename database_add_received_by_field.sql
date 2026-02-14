-- Add received_by field to consumable_release_history table
ALTER TABLE `consumable_release_history` 
ADD COLUMN `received_by` INT NULL AFTER `released_by`,
ADD INDEX `idx_received_by` (`received_by`);

-- Add foreign key constraint if users table exists
-- ALTER TABLE `consumable_release_history` 
-- ADD CONSTRAINT `fk_release_history_received_by` 
-- FOREIGN KEY (`received_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
