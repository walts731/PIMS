-- Infrastructure Database Setup
-- Create infrastructure table for managing infrastructure and building assets

CREATE TABLE IF NOT EXISTS `infrastructure` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `classification` varchar(100) NOT NULL COMMENT 'Classification/Type of infrastructure',
    `item_description` text NOT NULL COMMENT 'Detailed description of the infrastructure item',
    `nature_occupancy` varchar(100) DEFAULT NULL COMMENT 'Nature of occupancy',
    `location` varchar(200) NOT NULL COMMENT 'Location of the infrastructure',
    `date_constructed` date NOT NULL COMMENT 'Date when infrastructure was constructed',
    `property_no` varchar(100) DEFAULT NULL COMMENT 'Property number or other reference',
    `acquisition_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acquisition cost of the infrastructure',
    `market_value` decimal(15,2) DEFAULT 0.00 COMMENT 'Market or appraisal value',
    `date_appraisal` date DEFAULT NULL COMMENT 'Date of appraisal',
    `remarks` text DEFAULT NULL COMMENT 'Additional remarks or notes',
    `additional_images` text DEFAULT NULL COMMENT 'JSON array of additional image filenames',
    `created_by` int(11) NOT NULL COMMENT 'User ID who created the record',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_classification` (`classification`),
    KEY `idx_location` (`location`),
    KEY `idx_date_constructed` (`date_constructed`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Infrastructure and building assets management';

-- Insert sample infrastructure data
INSERT INTO `infrastructure` (`classification`, `item_description`, `nature_occupancy`, `location`, `date_constructed`, `property_no`, `acquisition_cost`, `market_value`, `date_appraisal`, `remarks`, `created_by`) VALUES
('Building', 'Main Municipal Hall', 'Government Office', 'Pilar Town Proper', '2010-05-15', 'PROP-001', 5000000.00, 7500000.00, '2024-01-15', 'Main government building housing various municipal offices', 1),
('Building', 'Public Market Building', 'Commercial', 'Pilar Town Proper', '2015-08-20', 'PROP-002', 3500000.00, 4200000.00, '2024-02-10', 'Public market with 50 stalls for local vendors', 1),
('Road', 'National Highway - Pilar Section', 'Transportation', 'Pilar', '2012-03-10', 'ROAD-001', 8000000.00, 9500000.00, '2024-01-20', '15 km national highway section passing through Pilar', 1),
('Bridge', 'Pilar River Bridge', 'Transportation', 'Barangay San Antonio', '2018-11-25', 'BRIDGE-001', 2500000.00, 3000000.00, '2024-03-05', 'Concrete bridge connecting San Antonio to town proper', 1),
('Building', 'Public Elementary School', 'Educational', 'Barangay San Isidro', '2016-06-30', 'PROP-003', 4200000.00, 5500000.00, '2024-02-15', 'Elementary school with 20 classrooms and facilities', 1);
