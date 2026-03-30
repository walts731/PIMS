-- LGU Compliance Database Tables
-- Created for COA & GPPB Compliance

-- 1. Document Reference Numbers Table
CREATE TABLE IF NOT EXISTS `document_references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` enum('RIS','PO','PAR','ICS','JEV','DV','OR') NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `document_date` date NOT NULL,
  `reference_amount` decimal(15,2) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `office_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_document` (`document_type`, `document_number`),
  KEY `idx_office_document` (`office_id`, `document_type`),
  KEY `idx_document_date` (`document_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Document reference numbers for LGU compliance';

-- 2. Report Audit Trail Table
CREATE TABLE IF NOT EXISTS `report_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(50) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `action` enum('generated','viewed','exported','printed','approved','modified','deleted') NOT NULL,
  `user_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_user_action` (`user_id`, `action`),
  KEY `idx_office_date` (`office_id`, `action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for all report activities';

-- 3. Report Scheduling Table
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_name` varchar(255) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','annually') NOT NULL,
  `schedule_day` int(11) DEFAULT NULL,
  `schedule_time` time NOT NULL DEFAULT '08:00:00',
  `recipients` json NOT NULL,
  `office_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_active` (`office_id`, `is_active`),
  KEY `idx_next_run` (`next_run`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Scheduled report generation';

-- 4. Signatory Authorities Table
CREATE TABLE IF NOT EXISTS `signatory_authorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) NOT NULL,
  `signatory_type` enum('prepared','noted','approved','certified') NOT NULL,
  `employee_id` int(11) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_type` (`office_id`, `signatory_type`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_effective_dates` (`effective_date`, `expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Authorized signatories for LGU documents';

-- 5. Data Integrity Checks Table
CREATE TABLE IF NOT EXISTS `data_integrity_checks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `check_type` enum('quantity_mismatch','value_discrepancy','duplicate_reference','missing_document','status_inconsistency') NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `field_name` varchar(50) DEFAULT NULL,
  `expected_value` varchar(255) DEFAULT NULL,
  `actual_value` varchar(255) DEFAULT NULL,
  `discrepancy_amount` decimal(15,2) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `status` enum('open','investigating','resolved','false_positive') NOT NULL DEFAULT 'open',
  `office_id` int(11) NOT NULL,
  `detected_by` int(11) DEFAULT NULL,
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_office_status` (`office_id`, `status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_table_record` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Data integrity and discrepancy tracking';

-- 6. Fiscal Year Settings Table
CREATE TABLE IF NOT EXISTS `fiscal_year_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_id` int(11) NOT NULL,
  `fiscal_year` int(4) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_office_fiscal` (`office_id`, `fiscal_year`),
  KEY `idx_fiscal_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fiscal year configuration per office';

-- 7. Report Templates Table
CREATE TABLE IF NOT EXISTS `report_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `template_content` longtext NOT NULL,
  `header_content` text DEFAULT NULL,
  `footer_content` text DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_type` (`office_id`, `report_type`),
  KEY `idx_default_active` (`is_default`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Custom report templates for LGU compliance';

-- 8. Report Generation History Table
CREATE TABLE IF NOT EXISTS `report_generation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(50) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `generation_method` enum('manual','scheduled','api') NOT NULL,
  `office_id` int(11) NOT NULL,
  `generated_by` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `record_count` int DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `generation_time` decimal(10,3) DEFAULT NULL,
  `status` enum('generating','completed','failed','cancelled') NOT NULL DEFAULT 'generating',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_office_date` (`office_id`, `created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='History of all report generation attempts';

-- Insert default fiscal year settings (January-December)
INSERT IGNORE INTO `fiscal_year_settings` 
(`office_id`, `fiscal_year`, `start_date`, `end_date`, `created_by`) 
SELECT 
    o.id, 
    YEAR(CURRENT_DATE) as fiscal_year,
    CONCAT(YEAR(CURRENT_DATE), '-01-01') as start_date,
    CONCAT(YEAR(CURRENT_DATE), '-12-31') as end_date,
    1 as created_by
FROM offices o 
WHERE o.id IS NOT NULL;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_asset_items_office_status` ON `asset_items` (`office_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_consumables_office_reorder` ON `consumables` (`office_id`, `reorder_level`);
CREATE INDEX IF NOT EXISTS `idx_borrow_requests_office_date` ON `borrow_requests` (`requested_by_office`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_document_refs_office_type` ON `document_references` (`office_id`, `document_type`);
