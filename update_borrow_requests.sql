-- Update existing borrow_requests table to add quantity field
-- Run this script to update your database structure if borrow_requests table already exists

ALTER TABLE `borrow_requests` 
ADD COLUMN `quantity_requested` int(11) NOT NULL DEFAULT 1 AFTER `asset_id`;

-- Add index for better performance
ALTER TABLE `borrow_requests` 
ADD INDEX `idx_quantity_requested` (`quantity_requested`);
