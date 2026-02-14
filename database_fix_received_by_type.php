-- Modify received_by column to be VARCHAR instead of INT
ALTER TABLE `consumable_release_history` 
MODIFY COLUMN `received_by` VARCHAR(255) NULL AFTER `released_by`;
