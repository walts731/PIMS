DROP TABLE IF EXISTS `backups`;
CREATE TABLE `backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('full','database','files') NOT NULL,
  `include_files` tinyint(1) DEFAULT 0,
  `include_database` tinyint(1) DEFAULT 0,
  `file_path` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `online_backup` tinyint(1) DEFAULT 0,
  `cloud_provider` varchar(50) DEFAULT NULL,
  `cloud_backup_url` varchar(500) DEFAULT NULL,
  `cloud_backup_status` enum('pending','uploading','completed','failed') DEFAULT NULL,
  `cloud_backup_error` text DEFAULT NULL,
  `cloud_backup_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `backups` VALUES("8","Daily Backup","full","1","1","../backups/Daily Backup_2026-01-05_13-41-33","1","2026-01-05 20:41:33","0","","","","","");
INSERT INTO `backups` VALUES("9","Daily Backup","full","1","1","../backups/Daily Backup_2026-01-05_14-05-54","1","2026-01-05 21:05:54","0","","","","","");


DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('in','out') NOT NULL,
  `quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `online_backup_configs`;
CREATE TABLE `online_backup_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) NOT NULL,
  `config_name` varchar(100) NOT NULL,
  `api_key` text DEFAULT NULL,
  `api_secret` text DEFAULT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `bucket_name` varchar(200) DEFAULT NULL,
  `folder_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `online_backup_configs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `online_backup_configs` VALUES("1","google_drive","Google Drive Backup","822354506716-8on8lknqkudd71ae2v935aq7hkvib4kq.apps.googleusercontent.com","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","","","","","1","1","2026-01-04 21:52:31","2026-01-04 23:01:36");
INSERT INTO `online_backup_configs` VALUES("2","dropbox","Dropbox Backup","","","","","","","0","1","2026-01-04 21:52:31","2026-01-04 21:52:31");
INSERT INTO `online_backup_configs` VALUES("3","onedrive","OneDrive Backup","","","","","","","0","1","2026-01-04 21:52:31","2026-01-04 21:52:31");


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `permissions` VALUES("1","users.create","Create new users","users","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("2","users.read","View users list","users","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("3","users.update","Edit user information","users","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("4","users.delete","Delete users","users","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("5","users.activate","Activate/deactivate users","users","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("6","inventory.create","Add new products","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("7","inventory.read","View products list","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("8","inventory.update","Edit product information","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("9","inventory.delete","Delete products","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("10","inventory.transaction.in","Add stock (IN transactions)","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("11","inventory.transaction.out","Remove stock (OUT transactions)","inventory","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("12","categories.create","Create new categories","categories","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("13","categories.read","View categories list","categories","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("14","categories.update","Edit category information","categories","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("15","categories.delete","Delete categories","categories","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("16","reports.view","View system reports","reports","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("17","reports.export","Export reports","reports","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("18","system.settings","Access system settings","system","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("19","system.logs","View system logs","system","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("20","system.backup","Create system backup","system","2026-01-04 09:09:27");
INSERT INTO `permissions` VALUES("21","system.audit","Access security audit","system","2026-01-04 09:09:27");


DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` enum('system_admin','admin','office_admin','user') NOT NULL,
  `permission_id` int(11) NOT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_read` tinyint(1) DEFAULT 1,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `role_permissions` VALUES("1","system_admin","12","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("2","system_admin","15","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("3","system_admin","13","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("4","system_admin","14","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("5","system_admin","6","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("6","system_admin","9","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("7","system_admin","7","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("8","system_admin","10","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("9","system_admin","11","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("10","system_admin","8","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("11","system_admin","17","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("12","system_admin","16","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("13","system_admin","21","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("14","system_admin","20","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("15","system_admin","19","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("16","system_admin","18","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("17","system_admin","5","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("18","system_admin","1","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("19","system_admin","4","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("20","system_admin","2","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("21","system_admin","3","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("32","admin","12","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("33","admin","15","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("34","admin","13","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("35","admin","14","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("36","admin","6","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("37","admin","9","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("38","admin","7","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("39","admin","10","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("40","admin","11","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("41","admin","8","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("42","admin","17","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("43","admin","16","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("44","admin","21","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("45","admin","20","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("46","admin","19","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("47","admin","18","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("48","admin","5","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("49","admin","1","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("50","admin","4","1","1","1","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("51","admin","2","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("52","admin","3","1","1","1","1","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("63","office_admin","12","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("64","office_admin","15","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("65","office_admin","13","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("66","office_admin","14","1","0","1","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("67","office_admin","6","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("68","office_admin","9","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("69","office_admin","7","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("70","office_admin","10","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("71","office_admin","11","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("72","office_admin","8","1","0","1","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("73","office_admin","17","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("74","office_admin","16","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("75","office_admin","21","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("76","office_admin","20","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("77","office_admin","19","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("78","office_admin","18","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("79","office_admin","5","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("80","office_admin","1","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("81","office_admin","4","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("82","office_admin","2","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("83","office_admin","3","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("94","user","12","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("95","user","15","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("96","user","13","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("97","user","14","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("98","user","6","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("99","user","9","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("100","user","7","0","1","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("101","user","10","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("102","user","11","1","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("103","user","8","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("104","user","17","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("105","user","16","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("106","user","21","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("107","user","20","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("108","user","19","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("109","user","18","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("110","user","5","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("111","user","1","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("112","user","4","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("113","user","2","0","0","0","0","2026-01-04 09:09:27");
INSERT INTO `role_permissions` VALUES("114","user","3","0","0","0","0","2026-01-04 09:09:27");


DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user_id` (`user_id`),
  KEY `idx_logs_action` (`action`),
  KEY `idx_logs_module` (`module`),
  KEY `idx_logs_created_at` (`created_at`),
  KEY `idx_logs_user_action` (`user_id`,`action`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_logs` VALUES("7","1","logout","authentication","User logged out: System Administrator (admin@pims.com) with role: system_admin","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:07:59");
INSERT INTO `system_logs` VALUES("8","1","login_success","authentication","User logged in: System Administrator (admin@pims.com) with role: system_admin","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:11:06");
INSERT INTO `system_logs` VALUES("9","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:29:34");
INSERT INTO `system_logs` VALUES("10","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:29:48");
INSERT INTO `system_logs` VALUES("11","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:31:16");
INSERT INTO `system_logs` VALUES("12","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:38:58");
INSERT INTO `system_logs` VALUES("13","1","backup_deleted","backup_system","Deleted backup: Daily Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:39:04");
INSERT INTO `system_logs` VALUES("14","1","online_backup_created","online_backup","Backup: Daily Backup, Storage: local","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:41:36");
INSERT INTO `system_logs` VALUES("15","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes, Online: Yes (google_drive)","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:53:26");
INSERT INTO `system_logs` VALUES("16","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes, Online: Yes (google_drive)","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:53:59");
INSERT INTO `system_logs` VALUES("17","1","online_backup_completed","backup_system","Online backup completed: google_drive","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:54:01");
INSERT INTO `system_logs` VALUES("18","1","backup_deleted","backup_system","Deleted backup: Daily Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 21:54:15");
INSERT INTO `system_logs` VALUES("19","1","backup_created","backup_system","Backup: online Backup, Type: full, Files: Yes, Database: Yes, Online: Yes (google_drive)","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 22:02:36");
INSERT INTO `system_logs` VALUES("20","1","cloud_config_updated","cloud_storage","Updated google_drive configuration","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 23:01:36");
INSERT INTO `system_logs` VALUES("21","1","backup_deleted","backup_system","Deleted backup: online Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 23:05:57");
INSERT INTO `system_logs` VALUES("22","1","backup_created","backup_system","Backup: cloud backup, Type: full, Files: Yes, Database: Yes, Online: Yes (google_drive)","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 23:10:56");
INSERT INTO `system_logs` VALUES("23","1","login_success","authentication","User logged in: System Administrator (admin@pims.com) with role: system_admin","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-04 23:17:48");
INSERT INTO `system_logs` VALUES("24","1","login_success","authentication","User logged in: System Administrator (admin@pims.com) with role: system_admin","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 13:13:35");
INSERT INTO `system_logs` VALUES("25","1","login_success","authentication","User logged in: System Administrator (admin@pims.com) with role: system_admin","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 20:16:55");
INSERT INTO `system_logs` VALUES("26","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 20:37:42");
INSERT INTO `system_logs` VALUES("27","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 20:41:33");
INSERT INTO `system_logs` VALUES("28","1","backup_created","backup_system","Backup: Daily Backup, Type: full, Files: Yes, Database: Yes","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 21:05:54");
INSERT INTO `system_logs` VALUES("29","1","backup_deleted","backup_system","Deleted backup: Daily Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 21:06:01");
INSERT INTO `system_logs` VALUES("30","1","backup_deleted","backup_system","Deleted backup: Daily Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 21:29:25");
INSERT INTO `system_logs` VALUES("31","1","backup_deleted","backup_system","Deleted backup: cloud backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 21:29:28");
INSERT INTO `system_logs` VALUES("32","1","backup_deleted","backup_system","Deleted backup: Daily Backup","::1","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36","2026-01-05 21:29:31");


DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('system_admin','admin','office_admin','user') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES("1","system_admin","admin@pims.com","","","$2y$10$sTwhCxd.JawevaKAgnfMaO1p.PJ34C9ROfU4nbTkmuHHdDOzcq/nm","system_admin","System","Administrator","1","2026-01-03 21:00:37","2026-01-04 21:21:03");
INSERT INTO `users` VALUES("2","wjll2022-2920-98466@bicol-u.edu.ph","wjll2022-2920-98466@bicol-u.edu.ph","","","$2y$10$0mPC7iEVtjGUOVHLqGdmNe5whIhEuPVQfmdliPsnSdupq20au5cl2","admin","Walton","loneza","1","2026-01-04 06:34:21","2026-01-04 06:34:21");
INSERT INTO `users` VALUES("4","notlawsfinds@gmail.com","notlawsfinds@gmail.com","","","$2y$10$ekzQ67QhSp7H3QhmLyjbxeUwgXPw4d35vEm0mlbQX98WGDJvVRkry","office_admin","Joshua ","Escano","1","2026-01-04 06:44:32","2026-01-04 06:44:32");
INSERT INTO `users` VALUES("5","waltonloneza@gmail.com","waltonloneza@gmail.com","","","$2y$10$/pK71fdth8E2iJQVIMq5E.MaRIPI.4P7hzzwdgYWsHJlvhT866Sbi","admin","Walton","loneza","1","2026-01-04 06:49:38","2026-01-04 06:49:38");
INSERT INTO `users` VALUES("11","waltielappy@gmail.com","waltielappy@gmail.com","","","$2y$10$hO1CH2GRcHTr81fLfLGokOk6kTlm9zja8X4ipgsq3Pb1ffMFS5bmu","user","Elton John","Moises","0","2026-01-04 08:39:40","2026-01-04 08:45:06");


