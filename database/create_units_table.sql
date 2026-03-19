-- Create units table for PIMS
-- This table will store standardized units of measurement for assets and consumables

CREATE TABLE IF NOT EXISTS `units` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `unit_name` varchar(50) NOT NULL COMMENT 'Name of the unit (e.g., piece, kilogram, meter)',
    `unit_code` varchar(20) NOT NULL COMMENT 'Short code for the unit (e.g., pc, kg, m)',
    `unit_type` enum('count','weight','length','volume','area','time','other') NOT NULL DEFAULT 'other' COMMENT 'Type of measurement',
    `description` text DEFAULT NULL COMMENT 'Description of when this unit is used',
    `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT 'Unit status',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Creation timestamp',
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Last update timestamp',
    `created_by` int(11) DEFAULT NULL COMMENT 'User who created this record',
    `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated this record',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_unit_code` (`unit_code`),
    KEY `idx_unit_type` (`unit_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Standardized units of measurement for assets and consumables';

-- Insert common units
INSERT INTO `units` (`unit_name`, `unit_code`, `unit_type`, `description`) VALUES
-- Count units
('piece', 'pc', 'count', 'Individual items'),
('pieces', 'pcs', 'count', 'Multiple individual items'),
('unit', 'unit', 'count', 'Single unit of measurement'),
('units', 'units', 'count', 'Multiple units of measurement'),
('set', 'set', 'count', 'Complete set of items'),
('sets', 'sets', 'count', 'Multiple complete sets'),
('pair', 'pair', 'count', 'Two items together'),
('pairs', 'pairs', 'count', 'Multiple pairs'),
('dozen', 'dozen', 'count', '12 items'),
('dozens', 'dozens', 'count', 'Multiple dozens'),

-- Packaging units
('box', 'box', 'count', 'Box containing items'),
('boxes', 'boxes', 'count', 'Multiple boxes'),
('carton', 'carton', 'count', 'Carton containing items'),
('cartons', 'cartons', 'count', 'Multiple cartons'),
('pack', 'pack', 'count', 'Pack of items'),
('packs', 'packs', 'count', 'Multiple packs'),
('package', 'package', 'count', 'Package containing items'),
('packages', 'packages', 'count', 'Multiple packages'),
('bag', 'bag', 'count', 'Bag containing items'),
('bags', 'bags', 'count', 'Multiple bags'),
('container', 'container', 'count', 'Container holding items'),
('containers', 'containers', 'count', 'Multiple containers'),
('bottle', 'bottle', 'count', 'Bottle containing liquid'),
('bottles', 'bottles', 'count', 'Multiple bottles'),
('ream', 'ream', 'count', 'Ream of paper (500 sheets)'),
('reams', 'reams', 'count', 'Multiple reams'),

-- Weight units
('kilogram', 'kg', 'weight', 'Kilogram (1000 grams)'),
('kilograms', 'kgs', 'weight', 'Multiple kilograms'),
('gram', 'g', 'weight', 'Gram'),
('grams', 'gs', 'weight', 'Multiple grams'),
('ton', 'ton', 'weight', 'Metric ton (1000 kg)'),
('tons', 'tons', 'weight', 'Multiple tons'),

-- Length units
('meter', 'm', 'length', 'Meter'),
('meters', 'ms', 'length', 'Multiple meters'),
('centimeter', 'cm', 'length', 'Centimeter'),
('centimeters', 'cms', 'length', 'Multiple centimeters'),
('kilometer', 'km', 'length', 'Kilometer'),
('kilometers', 'kms', 'length', 'Multiple kilometers'),

-- Volume units
('liter', 'liter', 'volume', 'Liter'),
('liters', 'liters', 'volume', 'Multiple liters'),
('milliliter', 'ml', 'volume', 'Milliliter'),
('milliliters', 'mls', 'volume', 'Multiple milliliters'),
('cubic_meter', 'm3', 'volume', 'Cubic meter'),
('cubic_meters', 'm3s', 'volume', 'Multiple cubic meters'),

-- Area units
('square_meter', 'm2', 'area', 'Square meter'),
('square_meters', 'm2s', 'area', 'Multiple square meters'),
('hectare', 'ha', 'area', 'Hectare'),
('hectares', 'has', 'area', 'Multiple hectares'),

-- Time units
('hour', 'hr', 'time', 'Hour'),
('hours', 'hrs', 'time', 'Multiple hours'),
('day', 'day', 'time', 'Day'),
('days', 'days', 'time', 'Multiple days'),
('month', 'mo', 'time', 'Month'),
('months', 'mos', 'time', 'Multiple months'),
('year', 'yr', 'time', 'Year'),
('years', 'yrs', 'time', 'Multiple years'),

-- Other specialized units
('roll', 'roll', 'other', 'Roll of material'),
('rolls', 'rolls', 'other', 'Multiple rolls'),
('sheet', 'sheet', 'other', 'Sheet of material'),
('sheets', 'sheets', 'other', 'Multiple sheets'),
('foot', 'ft', 'length', 'Foot'),
('feet', 'fts', 'length', 'Multiple feet'),
('inch', 'in', 'length', 'Inch'),
('inches', 'ins', 'length', 'Multiple inches');
