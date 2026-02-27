-- Add status fields to asset_desktop_computers table for individual component tracking
ALTER TABLE `asset_desktop_computers` 
ADD COLUMN `monitor_status` ENUM('serviceable', 'unserviceable', 'red_tagged', 'no_tag') DEFAULT 'serviceable' AFTER `monitor_serial_number`,
ADD COLUMN `ups_status` ENUM('serviceable', 'unserviceable', 'red_tagged', 'no_tag') DEFAULT 'serviceable' AFTER `ups_serial_number`;

-- Add indexes for better performance
ALTER TABLE `asset_desktop_computers` 
ADD INDEX `idx_monitor_status` (`monitor_status`),
ADD INDEX `idx_ups_status` (`ups_status`);
