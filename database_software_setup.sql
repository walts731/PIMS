-- Software Database Setup
-- Create software table for managing software licenses and installations

CREATE TABLE IF NOT EXISTS `software` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `software_name` varchar(200) NOT NULL COMMENT 'Name of the software',
    `category` varchar(100) NOT NULL COMMENT 'Software category (OS, Office, Antivirus, etc.)',
    `description` text DEFAULT NULL COMMENT 'Description of the software',
    `vendor` varchar(100) NOT NULL COMMENT 'Software vendor/developer',
    `version` varchar(50) DEFAULT NULL COMMENT 'Software version',
    `license_type` varchar(50) NOT NULL COMMENT 'Type of license (Perpetual, Subscription, etc.)',
    `license_key` text DEFAULT NULL COMMENT 'License key or serial number',
    `purchase_date` date NOT NULL COMMENT 'Date of purchase',
    `purchase_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Purchase cost of the software',
    `renewal_date` date DEFAULT NULL COMMENT 'License renewal date',
    `renewal_cost` decimal(15,2) DEFAULT 0.00 COMMENT 'Cost of license renewal',
    `status` enum('active','inactive','expired','pending') NOT NULL DEFAULT 'active' COMMENT 'Current status of the software',
    `assigned_to` varchar(200) DEFAULT NULL COMMENT 'Person or department assigned to',
    `installation_date` date DEFAULT NULL COMMENT 'Date of installation',
    `notes` text DEFAULT NULL COMMENT 'Additional notes about the software',
    `files` text DEFAULT NULL COMMENT 'JSON array of uploaded files (license docs, installation files)',
    `created_by` int(11) NOT NULL COMMENT 'User ID who created the record',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_software_name` (`software_name`),
    KEY `idx_category` (`category`),
    KEY `idx_vendor` (`vendor`),
    KEY `idx_license_type` (`license_type`),
    KEY `idx_status` (`status`),
    KEY `idx_purchase_date` (`purchase_date`),
    KEY `idx_renewal_date` (`renewal_date`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Software licenses and installations management';

-- Insert sample software data
INSERT INTO `software` (`software_name`, `category`, `description`, `vendor`, `version`, `license_type`, `license_key`, `purchase_date`, `purchase_cost`, `renewal_date`, `renewal_cost`, `status`, `assigned_to`, `installation_date`, `notes`, `created_by`) VALUES
('Microsoft Office 365', 'Office Suite', 'Productivity suite with Word, Excel, PowerPoint, Outlook', 'Microsoft Corporation', '2023', 'Annual Subscription', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2024-01-15', 3500.00, '2025-01-15', 3500.00, 'active', 'Administration Office', '2024-01-20', 'Annual subscription for 10 users', 1),
('Windows 11 Pro', 'Operating System', 'Professional operating system for workstations', 'Microsoft Corporation', '23H2', 'Perpetual', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2023-06-10', 8500.00, NULL, 0.00, 'active', 'IT Department', '2023-06-15', 'Volume license for 5 computers', 1),
('Kaspersky Endpoint Security', 'Antivirus', 'Business antivirus and endpoint protection', 'Kaspersky Lab', '12.2', 'Annual Subscription', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2024-02-01', 2800.00, '2025-02-01', 2800.00, 'active', 'IT Department', '2024-02-05', 'Protects 15 endpoints', 1),
('Adobe Creative Cloud', 'Design Software', 'Creative suite for graphic design and video editing', 'Adobe Inc.', '2024', 'Annual Subscription', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2023-12-01', 4200.00, '2024-12-01', 4200.00, 'active', 'Marketing Office', '2023-12-10', 'All apps plan for 2 users', 1),
('MySQL Community Server', 'Database', 'Open-source relational database management system', 'Oracle Corporation', '8.0', 'Open Source', NULL, '2023-09-15', 0.00, NULL, 0.00, 'active', 'IT Department', '2023-09-20', 'Free community edition for web applications', 1),
('QuickBooks Desktop', 'Accounting', 'Accounting software for small business financial management', 'Intuit Inc.', '2023', 'Perpetual', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2023-11-10', 15000.00, NULL, 0.00, 'active', 'Accounting Office', '2023-11-15', 'Single user license with payroll module', 1),
('Visual Studio Professional', 'Development Tools', 'Integrated development environment for software development', 'Microsoft Corporation', '2022', 'Perpetual', 'XXXXX-XXXXX-XXXXX-XXXXX-XXXXX', '2023-08-20', 7500.00, NULL, 0.00, 'active', 'IT Department', '2023-08-25', 'Professional edition for 3 developers', 1),
('Google Workspace', 'Office Suite', 'Cloud-based productivity and collaboration tools', 'Google LLC', '2024', 'Monthly Subscription', NULL, '2024-01-01', 1200.00, NULL, 0.00, 'active', 'All Departments', '2024-01-05', 'Business Standard plan for 20 users', 1);
