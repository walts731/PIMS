-- Create table for desktop computer specific fields
CREATE TABLE IF NOT EXISTS `asset_desktop_computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `monitor_name` varchar(255) DEFAULT NULL,
  `monitor_model` varchar(255) DEFAULT NULL,
  `monitor_serial_number` varchar(255) DEFAULT NULL,
  `ups_name` varchar(255) DEFAULT NULL,
  `ups_model` varchar(255) DEFAULT NULL,
  `ups_serial_number` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asset_item` (`asset_item_id`),
  CONSTRAINT `fk_desktop_computers_asset_item` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
