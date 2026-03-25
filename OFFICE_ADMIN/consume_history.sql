-- Table structure for tracking consumable consumption history
-- This table records every instance when a consumable is consumed/used

CREATE TABLE `consume_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumable_id` int(11) NOT NULL,
  `consumable_description` varchar(255) NOT NULL,
  `quantity_consumed` int(11) NOT NULL DEFAULT 1,
  `remaining_quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(101) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `office_id` int(11) NOT NULL,
  `office_name` varchar(100) NOT NULL,
  `consumed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `purpose` text DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_by_name` varchar(101) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_consumed_at` (`consumed_at`),
  KEY `idx_reference_number` (`reference_number`),
  CONSTRAINT `fk_consume_history_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consume_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consume_history_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better query performance
CREATE INDEX `idx_consumable_date` ON `consume_history` (`consumable_id`, `consumed_at`);
CREATE INDEX `idx_office_date` ON `consume_history` (`office_id`, `consumed_at`);
CREATE INDEX `idx_user_date` ON `consume_history` (`user_id`, `consumed_at`);

-- View for easy consumption reporting
CREATE VIEW `v_consumption_summary` AS
SELECT 
    ch.id,
    ch.consumable_id,
    ch.consumable_description,
    ch.quantity_consumed,
    ch.remaining_quantity,
    ch.user_name,
    ch.user_email,
    ch.office_name,
    ch.consumed_at,
    ch.purpose,
    ch.reference_number,
    c.units as consumable_units,
    c.unit_cost as consumable_unit_cost,
    (ch.quantity_consumed * c.unit_cost) as total_cost
FROM consume_history ch
LEFT JOIN consumables c ON ch.consumable_id = c.id
ORDER BY ch.consumed_at DESC;

-- View for monthly consumption by office
CREATE VIEW `v_monthly_office_consumption` AS
SELECT 
    DATE_FORMAT(ch.consumed_at, '%Y-%m') as month_year,
    ch.office_id,
    ch.office_name,
    COUNT(*) as total_transactions,
    SUM(ch.quantity_consumed) as total_quantity_consumed,
    SUM(ch.quantity_consumed * c.unit_cost) as total_cost
FROM consume_history ch
LEFT JOIN consumables c ON ch.consumable_id = c.id
GROUP BY DATE_FORMAT(ch.consumed_at, '%Y-%m'), ch.office_id, ch.office_name
ORDER BY month_year DESC, ch.office_name;

-- View for consumable usage trends
CREATE VIEW `v_consumable_usage_trends` AS
SELECT 
    ch.consumable_id,
    ch.consumable_description,
    c.units,
    COUNT(*) as consumption_count,
    SUM(ch.quantity_consumed) as total_consumed,
    AVG(ch.quantity_consumed) as avg_consumption_per_transaction,
    MIN(ch.remaining_quantity) as lowest_stock_level,
    MAX(ch.consumed_at) as last_consumed_date
FROM consume_history ch
LEFT JOIN consumables c ON ch.consumable_id = c.id
GROUP BY ch.consumable_id, ch.consumable_description, c.units
ORDER BY total_consumed DESC;
