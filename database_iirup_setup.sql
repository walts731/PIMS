-- IIRUP (Individual Item Request for User Property) Database Setup
-- This script creates the necessary tables for the IIRUP forms system

-- Create iirup_forms table
CREATE TABLE IF NOT EXISTS `iirup_forms` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `form_number` varchar(50) NOT NULL,
    `as_of_year` int(4) NOT NULL,
    `accountable_officer` varchar(255) NOT NULL,
    `designation` varchar(255) NOT NULL,
    `department_office` varchar(255) NOT NULL,
    `accountable_officer_name` varchar(255) DEFAULT NULL,
    `accountable_officer_designation` varchar(255) DEFAULT NULL,
    `authorized_official_name` varchar(255) DEFAULT NULL,
    `authorized_official_designation` varchar(255) DEFAULT NULL,
    `inspection_officer_name` varchar(255) DEFAULT NULL,
    `witness_name` varchar(255) DEFAULT NULL,
    `status` enum('draft','submitted','approved','rejected','processed') DEFAULT 'draft',
    `total_items` int(11) DEFAULT 0,
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `form_number` (`form_number`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_as_of_year` (`as_of_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create iirup_items table
CREATE TABLE IF NOT EXISTS `iirup_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `form_id` int(11) NOT NULL,
    `date_acquired` date DEFAULT NULL,
    `particulars` text NOT NULL,
    `property_no` varchar(100) DEFAULT NULL,
    `quantity` decimal(10,2) DEFAULT 0.00,
    `unit_cost` decimal(15,2) DEFAULT 0.00,
    `total_cost` decimal(15,2) DEFAULT 0.00,
    `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
    `impairment_losses` decimal(15,2) DEFAULT 0.00,
    `carrying_amount` decimal(15,2) DEFAULT 0.00,
    `inventory_remarks` text DEFAULT NULL,
    `disposal_sale` decimal(15,2) DEFAULT 0.00,
    `disposal_transfer` decimal(15,2) DEFAULT 0.00,
    `disposal_destruction` decimal(15,2) DEFAULT 0.00,
    `disposal_others` text DEFAULT NULL,
    `disposal_total` decimal(15,2) DEFAULT 0.00,
    `appraised_value` decimal(15,2) DEFAULT 0.00,
    `total` decimal(15,2) DEFAULT 0.00,
    `or_no` varchar(100) DEFAULT NULL,
    `amount` decimal(15,2) DEFAULT 0.00,
    `dept_office` varchar(255) DEFAULT NULL,
    `control_no` varchar(100) DEFAULT NULL,
    `date_received` date DEFAULT NULL,
    `item_order` int(11) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `form_id` (`form_id`),
    KEY `idx_property_no` (`property_no`),
    KEY `idx_dept_office` (`dept_office`),
    KEY `idx_item_order` (`item_order`),
    CONSTRAINT `iirup_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `iirup_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- INSERT INTO `iirup_forms` (
--     `form_number`, `as_of_year`, `accountable_officer`, `designation`, `department_office`,
--     `accountable_officer_name`, `accountable_officer_designation`, `authorized_official_name`,
--     `authorized_official_designation`, `inspection_officer_name`, `witness_name`,
--     `status`, `total_items`, `created_by`
-- ) VALUES (
--     'IIRUP-2024-0001', 2024, 'Juan Dela Cruz', 'Office Head', 'Administration',
--     'Juan Dela Cruz', 'Office Head', 'Maria Santos', 'Department Head', 
--     'Pedro Reyes', 'Ana Lopez', 'draft', 1, 1
-- );

-- Comments:
-- 1. The iirup_forms table stores the main form information including officer details and signatures
-- 2. The iirup_items table stores individual items for each form with all the IIRUP fields
-- 3. Foreign key constraint ensures data integrity between forms and items
-- 4. Status field tracks the workflow: draft -> submitted -> approved/rejected -> processed
-- 5. Item order maintains the sequence of items as entered in the form
-- 6. All monetary fields use decimal(15,2) for proper financial calculations
-- 7. Created_by and updated_by track user actions for audit trail
