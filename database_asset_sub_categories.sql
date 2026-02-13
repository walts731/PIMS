-- Create asset_sub_categories table
CREATE TABLE IF NOT EXISTS `asset_sub_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sub_category_name` varchar(255) NOT NULL,
    `sub_category_code` varchar(10) NOT NULL,
    `asset_categories_id` int(11) NOT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `useful_life` int(11) DEFAULT NULL COMMENT 'Useful life in years',
    `created_by` int(11) DEFAULT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sub_category_code` (`sub_category_code`),
    KEY `idx_asset_categories_id` (`asset_categories_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_sub_categories_asset_categories` FOREIGN KEY (`asset_categories_id`) REFERENCES `asset_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sub_categories_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure basic categories exist first
INSERT IGNORE INTO `asset_categories` (`id`, `category_code`, `category_name`, `status`, `created_by`) VALUES
(1, 'EQ', 'Equipment', 'active', 1),
(2, 'FR', 'Furniture', 'active', 1),
(3, 'VH', 'Vehicles', 'active', 1);

-- Insert sample sub categories (only if parent categories exist)
INSERT IGNORE INTO `asset_sub_categories` (`sub_category_name`, `sub_category_code`, `asset_categories_id`, `status`, `useful_life`, `created_by`) VALUES
('Desktop Computers', 'DC', 1, 'active', 5, 1),
('Laptop Computers', 'LC', 1, 'active', 3, 1),
('Computer Monitors', 'CM', 1, 'active', 7, 1),
('Printers', 'PR', 1, 'active', 5, 1),
('Office Chairs', 'OC', 2, 'active', 10, 1),
('Office Desks', 'OD', 2, 'active', 15, 1),
('Filing Cabinets', 'FC', 2, 'active', 20, 1),
('Vehicles', 'VH', 3, 'active', 10, 1),
('Motorcycles', 'MC', 3, 'active', 8, 1);
