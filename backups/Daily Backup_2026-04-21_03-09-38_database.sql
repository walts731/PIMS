-- PIMS Database Backup
-- Generated: 2026-04-21 03:09:40
-- Database: pims

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for `asset_buildings`
DROP TABLE IF EXISTS `asset_buildings`;
CREATE TABLE `asset_buildings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `building_type` enum('office','warehouse','factory','residential','commercial','other') DEFAULT 'other',
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Philippines',
  `total_floor_area` decimal(10,2) DEFAULT NULL,
  `number_of_floors` int(11) DEFAULT NULL,
  `year_built` int(11) DEFAULT NULL,
  `year_renovated` int(11) DEFAULT NULL,
  `construction_type` enum('concrete','wood','steel','mixed') DEFAULT 'concrete',
  `roof_type` varchar(50) DEFAULT NULL,
  `electrical_capacity` varchar(50) DEFAULT NULL,
  `water_supply` enum('municipal','well','mixed') DEFAULT 'municipal',
  `sewage_system` enum('municipal','septic_tank','mixed') DEFAULT 'municipal',
  `fire_safety_system` tinyint(1) DEFAULT 0,
  `security_system` tinyint(1) DEFAULT 0,
  `air_conditioning` tinyint(1) DEFAULT 0,
  `elevator_count` int(11) DEFAULT 0,
  `parking_spaces` int(11) DEFAULT 0,
  `property_tax_number` varchar(50) DEFAULT NULL,
  `land_title_number` varchar(50) DEFAULT NULL,
  `zoning_classification` varchar(100) DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_building_type` (`building_type`),
  KEY `idx_address` (`city`,`state`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_buildings_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_buildings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_buildings_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_buildings`

-- Table structure for `asset_categories`
DROP TABLE IF EXISTS `asset_categories`;
CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `useful_life_years` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  UNIQUE KEY `category_code` (`category_code`),
  KEY `idx_category_code` (`category_code`),
  KEY `idx_status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `asset_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_categories_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_categories`
INSERT INTO `asset_categories` VALUES("1","FF","07-010","Furniture & Fixture","10.00","7","active","2026-01-06 14:08:57","2026-03-11 14:25:00","1","1");
INSERT INTO `asset_categories` VALUES("2","ITS","05-030","Information, Communication and Technology Equipment","33.33","3","active","2026-01-06 14:08:57","2026-03-11 14:19:36","1","1");
INSERT INTO `asset_categories` VALUES("4","MACH","05-010","Machinery & Equipment","15.00","10","active","2026-01-06 14:08:57","2026-03-11 14:17:58","1","1");
INSERT INTO `asset_categories` VALUES("6","LND","01-010","Land","0.00","0","active","2026-01-06 14:08:57","2026-03-11 13:46:47","1","1");
INSERT INTO `asset_categories` VALUES("7","SW","060","Software","33.33","3","active","2026-01-06 14:08:57","2026-02-17 13:39:45","1","1");
INSERT INTO `asset_categories` VALUES("8","OEQ","05-020","Office Equipment","20.00","5","active","2026-01-06 14:08:57","2026-03-11 14:18:39","1","1");
INSERT INTO `asset_categories` VALUES("9","AFFE","05-040","Agricultural, Fishery, and Forestry Equipment","0.46","3","active","2026-03-05 13:48:00","2026-04-20 10:13:33","1","1");
INSERT INTO `asset_categories` VALUES("10","COME","05-070","Communication Equipment","0.00","0","active","2026-03-05 13:48:45","2026-03-11 13:59:54","1","1");
INSERT INTO `asset_categories` VALUES("11","CONSHE","05-080","Construction and Heavy Equipment","0.00","0","active","2026-03-05 13:49:17","2026-03-11 14:21:25","1","1");
INSERT INTO `asset_categories` VALUES("12","MSE","05-100","Military, Police and Security Equipment","0.00","0","active","2026-03-05 13:49:53","2026-03-11 14:22:50","1","1");
INSERT INTO `asset_categories` VALUES("13","DRRM","05-090","Disaster Risk Reduction Management Equipment","0.00","0","active","2026-03-05 13:50:33","2026-03-11 14:22:33","1","1");
INSERT INTO `asset_categories` VALUES("14","TSE","05-140","Technical and Scientific Equipment","0.00","0","active","2026-03-05 13:51:04","2026-03-11 14:23:37","1","1");
INSERT INTO `asset_categories` VALUES("15","SPE","140","Sports Equipment","0.00","0","active","2026-03-05 13:51:48","2026-03-05 13:51:48","1",NULL);
INSERT INTO `asset_categories` VALUES("16","OME","05-990","Other Machinery and Equipment","0.00","0","active","2026-03-05 13:53:11","2026-03-11 14:24:12","1","1");
INSERT INTO `asset_categories` VALUES("17","SEA","03-070","Sea Port System","0.00","0","active","2026-03-05 13:54:07","2026-03-11 14:27:58","1","1");
INSERT INTO `asset_categories` VALUES("18","WC","06-040","Water Craft","0.00","0","active","2026-03-05 13:55:04","2026-03-11 14:10:53","1","1");
INSERT INTO `asset_categories` VALUES("19","PTR","180","Plants & Trees","0.00","0","active","2026-03-05 13:55:26","2026-03-05 13:55:26","1",NULL);
INSERT INTO `asset_categories` VALUES("20","PPM","190","Park, Plaza & Mun.","0.00","0","active","2026-03-05 13:55:56","2026-03-05 13:55:56","1",NULL);
INSERT INTO `asset_categories` VALUES("21","MEDEQ","05-110","Medical/Hospital Equipment","0.00","0","active","2026-03-05 13:56:20","2026-03-11 14:29:04","1","1");
INSERT INTO `asset_categories` VALUES("22","POWER SUPPLY","03-051","Power Supply System","0.00","0","active","2026-03-05 13:57:00","2026-03-11 14:29:41","1","1");
INSERT INTO `asset_categories` VALUES("23","Land Imp","02-990","","0.00","0","active","2026-03-11 14:02:18","2026-03-11 14:02:18","1",NULL);
INSERT INTO `asset_categories` VALUES("24","RN","03-010","Road Network","0.00","0","active","2026-03-11 14:05:13","2026-03-11 14:05:13","1",NULL);
INSERT INTO `asset_categories` VALUES("25","WS","03-040","Water System","0.00","0","active","2026-03-11 14:11:24","2026-03-11 14:11:24","1",NULL);
INSERT INTO `asset_categories` VALUES("26","OInfra","03-990","Other Infrastructure Assets","0.00","0","active","2026-03-11 14:12:46","2026-03-11 14:12:57","1","1");
INSERT INTO `asset_categories` VALUES("27","Buildings","04-010","Office Buildings","0.00","0","active","2026-03-11 14:13:53","2026-03-11 14:13:53","1",NULL);
INSERT INTO `asset_categories` VALUES("28","School Bldg","04-020","School Buildings","0.00","0","active","2026-03-11 14:14:23","2026-03-11 14:14:23","1",NULL);
INSERT INTO `asset_categories` VALUES("29","HHC","04-030","Hospitals and Health Centers","0.00","0","active","2026-03-11 14:15:11","2026-03-11 14:15:11","1",NULL);
INSERT INTO `asset_categories` VALUES("30","MKT","04-040","Market","0.00","0","active","2026-03-11 14:15:42","2026-03-11 14:15:42","1",NULL);
INSERT INTO `asset_categories` VALUES("31","SLH","04-050","Slaughterhouse","0.00","0","active","2026-03-11 14:16:13","2026-03-11 14:16:13","1",NULL);
INSERT INTO `asset_categories` VALUES("32","Ostruct","04-990","Other Structures","0.00","0","active","2026-03-11 14:16:44","2026-03-11 14:16:44","1",NULL);
INSERT INTO `asset_categories` VALUES("34","SE","05-130","Sports Equipment","0.00","0","active","2026-03-11 14:23:20","2026-03-11 14:23:20","1",NULL);
INSERT INTO `asset_categories` VALUES("35","MV","06-010","Motor Vehicles","0.00","0","active","2026-03-11 14:24:36","2026-03-11 14:24:36","1",NULL);
INSERT INTO `asset_categories` VALUES("36","PP&MUN","03-090","PARK, PLAZAS & MONUMENTS","0.00","0","active","2026-03-11 14:27:20","2026-03-11 14:27:20","1",NULL);
INSERT INTO `asset_categories` VALUES("37","P&T","01-020","PLANTS & TREES","0.00","0","active","2026-03-11 14:28:34","2026-03-11 14:28:34","1",NULL);

-- Table structure for `asset_category_tables`
DROP TABLE IF EXISTS `asset_category_tables`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `asset_category_tables` AS select `ac`.`id` AS `category_id`,`ac`.`category_name` AS `category_name`,`ac`.`category_code` AS `category_code`,case `ac`.`category_code` when 'FF' then 'asset_furniture' when 'CE' then 'asset_computers' when 'VH' then 'asset_vehicles' when 'ME' then 'asset_machinery' when 'BI' then 'asset_buildings' when 'LD' then 'asset_land' when 'SW' then 'asset_software' when 'OE' then 'asset_office_equipment' else NULL end AS `specific_table_name` from `asset_categories` `ac` where `ac`.`status` = 'active';

-- Dumping data for `asset_category_tables`
INSERT INTO `asset_category_tables` VALUES("1","FF","07-010",NULL);
INSERT INTO `asset_category_tables` VALUES("2","ITS","05-030",NULL);
INSERT INTO `asset_category_tables` VALUES("4","MACH","05-010",NULL);
INSERT INTO `asset_category_tables` VALUES("6","LND","01-010",NULL);
INSERT INTO `asset_category_tables` VALUES("7","SW","060",NULL);
INSERT INTO `asset_category_tables` VALUES("8","OEQ","05-020",NULL);
INSERT INTO `asset_category_tables` VALUES("9","AFFE","05-040",NULL);
INSERT INTO `asset_category_tables` VALUES("10","COME","05-070",NULL);
INSERT INTO `asset_category_tables` VALUES("11","CONSHE","05-080",NULL);
INSERT INTO `asset_category_tables` VALUES("12","MSE","05-100",NULL);
INSERT INTO `asset_category_tables` VALUES("13","DRRM","05-090",NULL);
INSERT INTO `asset_category_tables` VALUES("14","TSE","05-140",NULL);
INSERT INTO `asset_category_tables` VALUES("15","SPE","140",NULL);
INSERT INTO `asset_category_tables` VALUES("16","OME","05-990",NULL);
INSERT INTO `asset_category_tables` VALUES("17","SEA","03-070",NULL);
INSERT INTO `asset_category_tables` VALUES("18","WC","06-040",NULL);
INSERT INTO `asset_category_tables` VALUES("19","PTR","180",NULL);
INSERT INTO `asset_category_tables` VALUES("20","PPM","190",NULL);
INSERT INTO `asset_category_tables` VALUES("21","MEDEQ","05-110",NULL);
INSERT INTO `asset_category_tables` VALUES("22","POWER SUPPLY","03-051",NULL);
INSERT INTO `asset_category_tables` VALUES("23","Land Imp","02-990",NULL);
INSERT INTO `asset_category_tables` VALUES("24","RN","03-010",NULL);
INSERT INTO `asset_category_tables` VALUES("25","WS","03-040",NULL);
INSERT INTO `asset_category_tables` VALUES("26","OInfra","03-990",NULL);
INSERT INTO `asset_category_tables` VALUES("27","Buildings","04-010",NULL);
INSERT INTO `asset_category_tables` VALUES("28","School Bldg","04-020",NULL);
INSERT INTO `asset_category_tables` VALUES("29","HHC","04-030",NULL);
INSERT INTO `asset_category_tables` VALUES("30","MKT","04-040",NULL);
INSERT INTO `asset_category_tables` VALUES("31","SLH","04-050",NULL);
INSERT INTO `asset_category_tables` VALUES("32","Ostruct","04-990",NULL);
INSERT INTO `asset_category_tables` VALUES("34","SE","05-130",NULL);
INSERT INTO `asset_category_tables` VALUES("35","MV","06-010",NULL);
INSERT INTO `asset_category_tables` VALUES("36","PP&MUN","03-090",NULL);
INSERT INTO `asset_category_tables` VALUES("37","P&T","01-020",NULL);

-- Table structure for `asset_computers`
DROP TABLE IF EXISTS `asset_computers`;
CREATE TABLE `asset_computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `processor` varchar(100) DEFAULT NULL,
  `ram_capacity` text DEFAULT NULL,
  `storage_type` enum('hdd','ssd','hybrid') DEFAULT 'hdd',
  `storage_capacity` text DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `graphics_card` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `ip_address` varchar(15) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `warranty_provider` varchar(100) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `assigned_to` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_asset_id` (`asset_item_id`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_mac_address` (`mac_address`),
  KEY `idx_warranty_expiry` (`warranty_expiry`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `asset_computers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_computers_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_computers`
INSERT INTO `asset_computers` VALUES("1","1","Intel® Core™ i5-13420H (Up to 4.6GHz, 8 Cores)","","ssd","512GB M.2 NVMe™ PCIe","OptiPlex 5090 SFF","","Windows Server 2022 (Standard Edition)",NULL,NULL,"SGH3420XYZ",NULL,NULL,NULL,NULL,"good",NULL,NULL,NULL,"2026-04-07 14:25:45","2026-04-07 14:25:45","5",NULL);
INSERT INTO `asset_computers` VALUES("2","2","AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)","","ssd","512GB M.2 NVMe™ PCIe","AMD RYZEN 7","IRIS X","Windows Server 2022 (Standard Edition)",NULL,NULL,"SGH3420XYZ",NULL,NULL,NULL,NULL,"good",NULL,NULL,NULL,"2026-04-07 14:31:44","2026-04-07 14:31:44","5",NULL);
INSERT INTO `asset_computers` VALUES("3","3","AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)","","hdd","512GB M.2 NVMe™ PCIe","AMD Ryzen 7 5800H","IRIS X","Windows® 11 Home",NULL,NULL,"R4N0CV098765",NULL,NULL,NULL,NULL,"good",NULL,NULL,NULL,"2026-04-07 14:38:53","2026-04-07 14:38:53","5",NULL);
INSERT INTO `asset_computers` VALUES("4","28","Apple M3 Chip (8-core CPU, 10-core GPU)","","ssd","","QN90C","IRIS X","Linux",NULL,NULL,"SGH3420XYZ",NULL,NULL,NULL,NULL,"good",NULL,NULL,NULL,"2026-04-13 16:49:14","2026-04-13 16:49:14","5",NULL);

-- Table structure for `asset_desktop_computers`
DROP TABLE IF EXISTS `asset_desktop_computers`;
CREATE TABLE `asset_desktop_computers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `monitor_name` varchar(255) DEFAULT NULL,
  `monitor_model` varchar(255) DEFAULT NULL,
  `monitor_serial_number` varchar(255) DEFAULT NULL,
  `monitor_status` enum('serviceable','unserviceable','red_tagged','no_tag','disposed') DEFAULT 'serviceable',
  `ups_name` varchar(255) DEFAULT NULL,
  `ups_model` varchar(255) DEFAULT NULL,
  `ups_serial_number` varchar(255) DEFAULT NULL,
  `ups_status` enum('serviceable','unserviceable','red_tagged','no_tag','disposed') DEFAULT 'serviceable',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `monitor_value` decimal(10,2) DEFAULT NULL,
  `ups_value` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asset_item` (`asset_item_id`),
  KEY `idx_monitor_status` (`monitor_status`),
  KEY `idx_ups_status` (`ups_status`),
  CONSTRAINT `fk_desktop_computers_asset_item` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_desktop_computers`
INSERT INTO `asset_desktop_computers` VALUES("1","1",NULL,NULL,NULL,"serviceable",NULL,NULL,NULL,"serviceable","5",NULL,"2026-04-07 14:25:46","2026-04-07 14:25:46",NULL,NULL);
INSERT INTO `asset_desktop_computers` VALUES("2","2",NULL,NULL,NULL,"serviceable",NULL,NULL,NULL,"serviceable","5",NULL,"2026-04-07 14:31:44","2026-04-07 14:31:44",NULL,NULL);
INSERT INTO `asset_desktop_computers` VALUES("3","3",NULL,NULL,NULL,"serviceable",NULL,NULL,NULL,"serviceable","5",NULL,"2026-04-07 14:38:53","2026-04-07 14:38:53",NULL,NULL);
INSERT INTO `asset_desktop_computers` VALUES("4","28",NULL,NULL,NULL,"serviceable",NULL,NULL,NULL,"serviceable","5",NULL,"2026-04-13 16:49:14","2026-04-13 16:49:14",NULL,NULL);

-- Table structure for `asset_furniture`
DROP TABLE IF EXISTS `asset_furniture`;
CREATE TABLE `asset_furniture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `furniture_type` enum('desk','chair','cabinet','shelf','table','sofa','bed','other') DEFAULT 'other',
  `material` enum('wood','metal','plastic','glass','leather','fabric','composite') DEFAULT 'wood',
  `color` varchar(30) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `weight_capacity` int(11) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model_number` varchar(50) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `location_building` varchar(100) DEFAULT NULL,
  `location_floor` varchar(20) DEFAULT NULL,
  `location_room` varchar(50) DEFAULT NULL,
  `assembly_required` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_furniture_type` (`furniture_type`),
  KEY `idx_location` (`location_building`,`location_floor`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_furniture_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_furniture_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_furniture_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_furniture`

-- Table structure for `asset_item_history`
DROP TABLE IF EXISTS `asset_item_history`;
CREATE TABLE `asset_item_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_asset_item_history_item_id` (`item_id`),
  KEY `idx_asset_item_history_created_at` (`created_at`),
  CONSTRAINT `asset_item_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_item_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_item_history`
INSERT INTO `asset_item_history` VALUES("1","1","PAR Created","Created via PAR form OMMP-2026-04-0001 - Entity: LGU PILAR, Quantity: 2, Unit: units, Amount: ₱56,000.00",NULL,NULL,"5","2026-04-07 11:08:45");
INSERT INTO `asset_item_history` VALUES("2","2","PAR Created","Created via PAR form OMMP-2026-04-0001 - Entity: LGU PILAR, Quantity: 2, Unit: units, Amount: ₱56,000.00",NULL,NULL,"5","2026-04-07 11:08:45");
INSERT INTO `asset_item_history` VALUES("4","1","QR Code Generated","QR code generated for asset item: qr_asset_1_1775543145.png",NULL,NULL,"5","2026-04-07 14:25:45");
INSERT INTO `asset_item_history` VALUES("5","1","Computer Specs Updated","Computer Equipment specs saved - Processor: Intel® Core™ i5-13420H (Up to 4.6GHz, 8 Cores), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe ssd, Model: OptiPlex 5090 SFF, Graphics: Not specified, OS: Windows Server 2022 (Standard Edition), Serial: SGH3420XYZ, Brand: Not specified, Warranty: Not specified",NULL,NULL,"5","2026-04-07 14:25:45");
INSERT INTO `asset_item_history` VALUES("6","1","Desktop Computer Specs Updated","Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable",NULL,NULL,"5","2026-04-07 14:25:46");
INSERT INTO `asset_item_history` VALUES("7","1","Tag Created","Created tag for item ID 1: Property No: 2026-07-05-030-0101-01, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-032 (Carter Cook), Images: asset_1_0_1775543144.webp",NULL,NULL,"5","2026-04-07 14:25:46");
INSERT INTO `asset_item_history` VALUES("8","2","QR Code Generated","QR code generated for asset item: qr_asset_2_1775543504.png",NULL,NULL,"5","2026-04-07 14:31:44");
INSERT INTO `asset_item_history` VALUES("9","2","Computer Specs Updated","Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe ssd, Model: AMD RYZEN 7, Graphics: IRIS X, OS: Windows Server 2022 (Standard Edition), Serial: SGH3420XYZ, Brand: Lenovo, Warranty: 2 years",NULL,NULL,"5","2026-04-07 14:31:44");
INSERT INTO `asset_item_history` VALUES("10","2","Desktop Computer Specs Updated","Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable",NULL,NULL,"5","2026-04-07 14:31:44");
INSERT INTO `asset_item_history` VALUES("11","2","Tag Created","Created tag for item ID 2: Property No: 2026-07-05-030-0102-01, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-023 (Evelyn Campbell), Images: asset_2_0_1775543503.avif",NULL,NULL,"5","2026-04-07 14:31:44");
INSERT INTO `asset_item_history` VALUES("12","3","QR Code Generated","QR code generated for asset item: qr_asset_3_1775543933.png",NULL,NULL,"5","2026-04-07 14:38:53");
INSERT INTO `asset_item_history` VALUES("13","3","Computer Specs Updated","Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe hdd, Model: AMD Ryzen 7 5800H, Graphics: IRIS X, OS: Windows® 11 Home, Serial: R4N0CV098765, Brand: Not specified, Warranty: Not specified",NULL,NULL,"5","2026-04-07 14:38:53");
INSERT INTO `asset_item_history` VALUES("14","3","Desktop Computer Specs Updated","Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable",NULL,NULL,"5","2026-04-07 14:38:53");
INSERT INTO `asset_item_history` VALUES("15","3","Tag Created","Created tag for item ID 3: Property No: 2026-04-05-030-0101-02, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-027 (Aiden Collins), Images: asset_3_0_1775543932.png",NULL,NULL,"5","2026-04-07 14:38:53");
INSERT INTO `asset_item_history` VALUES("16","6","QR Code Generated","QR code generated for asset item: qr_asset_6_1775616318.png",NULL,NULL,"5","2026-04-08 10:45:18");
INSERT INTO `asset_item_history` VALUES("17","6","Tag Created","Created tag for item ID 6: Property No: 2026-07-06-010-0101-07, Inventory Tag: , Date Counted: 2026-04-08, Category: 06-010 - MV, Person Accountable: 2026-001-01-011239 (Walton Loneza), Images: asset_6_0_1775616317.webp",NULL,NULL,"5","2026-04-08 10:45:19");
INSERT INTO `asset_item_history` VALUES("19","7","QR Code Generated","QR code generated for asset item: qr_asset_7_1775620142.png",NULL,NULL,"5","2026-04-08 11:49:02");
INSERT INTO `asset_item_history` VALUES("20","7","Tag Created","Created tag for item ID 7: Property No: 2026-07-06-010-0102-07, Inventory Tag: , Date Counted: 2026-04-08, Category: 06-010 - MV, Person Accountable: 2026-001-01-011239 (Walton Loneza), Images: asset_7_0_1775620141.webp",NULL,NULL,"5","2026-04-08 11:49:02");
INSERT INTO `asset_item_history` VALUES("21","11","QR Code Generated","QR code generated for asset item: qr_asset_11_1775735815.png",NULL,NULL,"5","2026-04-09 19:56:55");
INSERT INTO `asset_item_history` VALUES("22","11","Tag Created","Created tag for item ID 11: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: EMP-2026-023 (Evelyn Campbell), No images",NULL,NULL,"5","2026-04-09 19:56:55");
INSERT INTO `asset_item_history` VALUES("23","12","QR Code Generated","QR code generated for asset item: qr_asset_12_1775736739.png",NULL,NULL,"5","2026-04-09 20:12:19");
INSERT INTO `asset_item_history` VALUES("24","12","Tag Created","Created tag for item ID 12: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:12:19");
INSERT INTO `asset_item_history` VALUES("25","13","QR Code Generated","QR code generated for asset item: qr_asset_13_1775737172.png",NULL,NULL,"5","2026-04-09 20:19:32");
INSERT INTO `asset_item_history` VALUES("26","13","Tag Created","Created tag for item ID 13: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:19:32");
INSERT INTO `asset_item_history` VALUES("27","14","QR Code Generated","QR code generated for asset item: qr_asset_14_1775737720.png",NULL,NULL,"5","2026-04-09 20:28:40");
INSERT INTO `asset_item_history` VALUES("28","14","Tag Created","Created tag for item ID 14: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:28:40");
INSERT INTO `asset_item_history` VALUES("29","15","QR Code Generated","QR code generated for asset item: qr_asset_15_1775738302.png",NULL,NULL,"5","2026-04-09 20:38:22");
INSERT INTO `asset_item_history` VALUES("30","15","Tag Created","Created tag for item ID 15: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:38:22");
INSERT INTO `asset_item_history` VALUES("31","16","QR Code Generated","QR code generated for asset item: qr_asset_16_1775738600.png",NULL,NULL,"5","2026-04-09 20:43:20");
INSERT INTO `asset_item_history` VALUES("32","16","Tag Created","Created tag for item ID 16: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:43:20");
INSERT INTO `asset_item_history` VALUES("33","17","QR Code Generated","QR code generated for asset item: qr_asset_17_1775738766.png",NULL,NULL,"5","2026-04-09 20:46:06");
INSERT INTO `asset_item_history` VALUES("34","17","Tag Created","Created tag for item ID 17: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-09 20:46:06");
INSERT INTO `asset_item_history` VALUES("35","24","QR Code Generated","QR code generated for asset item: qr_asset_24_1775782938.png",NULL,NULL,"5","2026-04-10 09:02:18");
INSERT INTO `asset_item_history` VALUES("36","24","Tag Created","Created tag for item ID 24: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-10 09:02:18");
INSERT INTO `asset_item_history` VALUES("37","18","QR Code Generated","QR code generated for asset item: qr_asset_18_1775783025.png",NULL,NULL,"5","2026-04-10 09:03:45");
INSERT INTO `asset_item_history` VALUES("38","18","Tag Created","Created tag for item ID 18: Property No: 2026-07-06-010-0101-07, Inventory Tag: , Date Counted: 2026-04-10, Category: 06-010 - MV, Person Accountable: EMP-2026-023 (Evelyn Campbell), No images",NULL,NULL,"5","2026-04-10 09:03:45");
INSERT INTO `asset_item_history` VALUES("39","23","QR Code Generated","QR code generated for asset item: qr_asset_23_1775784289.png",NULL,NULL,"5","2026-04-10 09:24:49");
INSERT INTO `asset_item_history` VALUES("40","23","Tag Created","Created tag for item ID 23: Property No: 2026-07-01-010-0101-22, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-10 09:24:49");
INSERT INTO `asset_item_history` VALUES("41","25","QR Code Generated","QR code generated for asset item: qr_asset_25_1775784864.png",NULL,NULL,"5","2026-04-10 09:34:24");
INSERT INTO `asset_item_history` VALUES("42","25","Tag Created","Created tag for item ID 25: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images",NULL,NULL,"5","2026-04-10 09:34:24");
INSERT INTO `asset_item_history` VALUES("43","26","ICS Created","Created via ICS form OMMI-2026-I-01 - Entity: OSB, Item No: 2026-04-07-010-0101-01\\r\\n2026-04-07-010-0102-01, Quantity: 1, Unit: , Unit Cost: ₱18,500.00",NULL,NULL,"5","2026-04-10 10:11:04");
INSERT INTO `asset_item_history` VALUES("44","27","ICS Created","Created via ICS form OMMI-2026-I-01 - Entity: OSB, Item No: 2026-04-07-010-0101-01\\r\\n2026-04-07-010-0102-01, Quantity: 1, Unit: , Unit Cost: ₱18,500.00",NULL,NULL,"5","2026-04-10 10:11:04");
INSERT INTO `asset_item_history` VALUES("45","3","status_change","Status changed via IIRUP Form: IIRUP-2026-5796","serviceable","unserviceable","5","2026-04-10 10:27:00");
INSERT INTO `asset_item_history` VALUES("46","2","ITR Transfer","Transferred via ITR form ITR-0010 - From: Evelyn Campbell, To: Aiden Collins, Transfer Type: Reassignment, End User: Alexander G. Adams/OVM","Employee ID: 20 (Evelyn Campbell)","Employee ID: 24 (Aiden Collins)","5","2026-04-10 10:38:32");
INSERT INTO `asset_item_history` VALUES("47","28","QR Code Generated","QR code generated for asset item: qr_asset_28_1776070154.png",NULL,NULL,"5","2026-04-13 16:49:14");
INSERT INTO `asset_item_history` VALUES("48","28","Computer Specs Updated","Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: Not specified, Storage: Not specified ssd, Model: QN90C, Graphics: IRIS X, OS: Linux, Serial: SGH3420XYZ, Brand: Lenovo, Warranty: 2 years",NULL,NULL,"5","2026-04-13 16:49:14");
INSERT INTO `asset_item_history` VALUES("49","28","Desktop Computer Specs Updated","Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable",NULL,NULL,"5","2026-04-13 16:49:14");
INSERT INTO `asset_item_history` VALUES("50","28","Tag Created","Created tag for item ID 28: Property No: 2026-07-05-030-0101-02, Inventory Tag: , Date Counted: 2026-04-13, Category: 05-030 - ITS, Person Accountable: EMP-2026-044 (Dylan Bennett), Images: asset_28_0_1776070153.webp, asset_28_1_1776070153.avif",NULL,NULL,"5","2026-04-13 16:49:14");

-- Table structure for `asset_item_improvements`
DROP TABLE IF EXISTS `asset_item_improvements`;
CREATE TABLE `asset_item_improvements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `improvement_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `amount` decimal(15,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_item_improvements`
INSERT INTO `asset_item_improvements` VALUES("1","1","2026-04-09","Added Graphics Card","1","60000.00","SERVICEABLE","2026-04-09 11:11:00");
INSERT INTO `asset_item_improvements` VALUES("2","23","2026-04-10","Brgy. Health Center Lot","1","850000.00","serviceable","2026-04-10 09:46:37");
INSERT INTO `asset_item_improvements` VALUES("3","23","2026-04-10","Added building","1","1200000.00","","2026-04-10 09:46:37");
INSERT INTO `asset_item_improvements` VALUES("4","23","2026-04-10","Brgy. Health Center Lot","1","850000.00","serviceable","2026-04-10 09:49:10");
INSERT INTO `asset_item_improvements` VALUES("5","23","2026-04-10","Added building","1","1200000.00","","2026-04-10 09:49:10");
INSERT INTO `asset_item_improvements` VALUES("6","23","2026-04-10","Brgy. Health Center Lot","1","1200000.00","","2026-04-10 09:52:18");
INSERT INTO `asset_item_improvements` VALUES("7","23","2026-04-10","Added building","1","2000000.00","serviceable","2026-04-10 09:52:18");

-- Table structure for `asset_items`
DROP TABLE IF EXISTS `asset_items`;
CREATE TABLE `asset_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `asset_subcategory_id` int(11) DEFAULT NULL,
  `asset_category_id` int(11) DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `end_user` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `ics_id` int(11) DEFAULT NULL,
  `par_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `property_no` varchar(100) DEFAULT NULL,
  `ics_par_no` varchar(100) DEFAULT NULL,
  `inventory_tag` varchar(100) DEFAULT NULL,
  `date_counted` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `redtag_image` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT 'serviceable',
  `disposal_reason` text DEFAULT NULL,
  `disposal_date` date DEFAULT NULL,
  `value` decimal(10,2) DEFAULT 0.00,
  `acquisition_date` date DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `office_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asset_item_asset` (`asset_id`),
  KEY `idx_asset_item_status` (`status`),
  KEY `idx_asset_item_office` (`office_id`),
  KEY `fk_asset_items_ics` (`ics_id`),
  KEY `idx_par_id` (`par_id`),
  KEY `idx_asset_items_property_no` (`property_no`),
  KEY `idx_asset_items_employee_id` (`employee_id`),
  KEY `idx_asset_items_category_id` (`category_id`),
  KEY `idx_asset_subcategory_id` (`asset_subcategory_id`),
  KEY `idx_ics_par_no` (`ics_par_no`),
  KEY `idx_asset_items_office_status` (`office_id`,`status`),
  CONSTRAINT `asset_items_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_items_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_asset_items_asset_subcategory` FOREIGN KEY (`asset_subcategory_id`) REFERENCES `asset_sub_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_asset_items_category` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_asset_items_ics` FOREIGN KEY (`ics_id`) REFERENCES `ics_forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_asset_items_par_id` FOREIGN KEY (`par_id`) REFERENCES `par_forms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_items`
INSERT INTO `asset_items` VALUES("1","1","2","2","29","Elton John Moises",NULL,NULL,"1","Laptop AMD Ryzen","OptiPlex 5090 SFF","SGH3420XYZ","units","1","2026-07-05-030-0101-01","OMMP-2026-04-0001",NULL,"2026-04-07","[\"asset_1_0_1775543144.webp\"]",NULL,"qr_asset_1_1775543145.png","serviceable",NULL,NULL,"116000.00","2026-04-07","4","OMM","2026-04-07 11:08:45","2026-04-14 13:55:19");
INSERT INTO `asset_items` VALUES("2","1","2","2","24","Alexander G. Adams/OVM",NULL,NULL,"1","Laptop AMD Ryzen","AMD RYZEN 7","SGH3420XYZ","units","1","2026-07-05-030-0102-01","OMMP-2026-04-0001",NULL,"2026-04-07","[\"asset_2_0_1775543503.avif\"]",NULL,"qr_asset_2_1775543504.png","serviceable",NULL,NULL,"56000.00","2026-04-07","4","OMM","2026-04-07 11:08:45","2026-04-11 19:54:38");
INSERT INTO `asset_items` VALUES("3","2","2","2","24","Elton John Moises",NULL,NULL,NULL,"ASUS Vivobook 16","AMD Ryzen 7 5800H","R4N0CV098765",NULL,"1","2026-04-05-030-0101-02","OVMI2026-0001",NULL,"2026-04-07","[\"asset_3_0_1775543932.png\"]",NULL,"qr_asset_3_1775543933.png","unserviceable",NULL,NULL,"42900.00","2026-01-23","5","OVM","2026-04-07 14:35:06","2026-04-10 10:27:00");
INSERT INTO `asset_items` VALUES("4","2",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"ASUS Vivobook 16",NULL,NULL,NULL,"1","2026-04-05-030-0102-02",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"42900.00","2026-01-23","5",NULL,"2026-04-07 14:35:06","2026-04-07 14:35:06");
INSERT INTO `asset_items` VALUES("5","2",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"ASUS Vivobook 16",NULL,NULL,NULL,"1","2026-04-05-030-0103-02",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"42900.00","2026-01-23","5",NULL,"2026-04-07 14:35:06","2026-04-07 14:35:06");
INSERT INTO `asset_items` VALUES("6","3",NULL,"35","51","Walton Loneza",NULL,NULL,NULL,"Isuzu GIGA Dump Truck","CXZ77","ABC-1234 / 6WG1-123456",NULL,"1","2026-07-06-010-0101-07","MotorpoolP-2026-0001",NULL,"2026-04-08","[\"asset_6_0_1775616317.webp\"]",NULL,"qr_asset_6_1775616318.png","serviceable",NULL,NULL,"4850000.00","2026-02-18","14","Motorpool","2026-04-07 15:04:28","2026-04-08 10:45:18");
INSERT INTO `asset_items` VALUES("7","3",NULL,"35","51","Elton John Moises",NULL,NULL,NULL,"Isuzu GIGA Dump Truck","CXZ77","ABC-5467/ 6WG1-123456",NULL,"0","2026-07-06-010-0102-07","MotorpoolP-2026-0001",NULL,"2026-04-08","[\"asset_7_0_1775620141.webp\"]",NULL,"qr_asset_7_1775620142.png","borrowed",NULL,NULL,"4850000.00","2026-02-18","14","Motorpool","2026-04-07 15:04:28","2026-04-10 21:02:56");
INSERT INTO `asset_items` VALUES("8","4",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Evolis Primacy 2",NULL,NULL,NULL,"1","2026-04-05-030-0301-01",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"48500.00","2026-04-08","4",NULL,"2026-04-08 09:01:28","2026-04-08 09:01:28");
INSERT INTO `asset_items` VALUES("9","5",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Office Table – Wooden",NULL,NULL,NULL,"1","2026-04-07-010-0101-02",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"23999.00","2026-04-08","5",NULL,"2026-04-08 09:36:05","2026-04-08 09:36:05");
INSERT INTO `asset_items` VALUES("10","5",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Office Table – Wooden",NULL,NULL,NULL,"1","2026-04-07-010-0102-02",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"23999.00","2026-04-08","5",NULL,"2026-04-08 09:36:05","2026-04-08 09:36:05");
INSERT INTO `asset_items` VALUES("11","6",NULL,"6","20","Elton John Moises",NULL,NULL,NULL,"Public Market Lot",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP2026-0001",NULL,"2026-04-09","NULL",NULL,"qr_asset_11_1775735815.png","serviceable",NULL,NULL,"8200000.00","2026-04-09","4","OMM","2026-04-09 19:51:48","2026-04-09 19:56:55");
INSERT INTO `asset_items` VALUES("12","7",NULL,"6","0","",NULL,NULL,NULL,"Proposed Sanitary Landfill",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-0007",NULL,"2026-04-09","NULL",NULL,"qr_asset_12_1775736739.png","serviceable",NULL,NULL,"4500000.00","2026-04-09","4","OMM","2026-04-09 20:01:09","2026-04-09 20:12:19");
INSERT INTO `asset_items` VALUES("13","8",NULL,"6","0","",NULL,NULL,NULL,"Municipal Hall Site",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-0007",NULL,"2026-04-09","NULL",NULL,"qr_asset_13_1775737172.png","serviceable",NULL,NULL,"15500000.00","2026-04-09","4","OMM","2026-04-09 20:13:48","2026-04-09 20:19:32");
INSERT INTO `asset_items` VALUES("14","9",NULL,"6","0","",NULL,NULL,NULL,"Main Public Market Lot",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-0008",NULL,"2026-04-09","NULL",NULL,"qr_asset_14_1775737720.png","serviceable",NULL,NULL,"4500000.00","2026-04-09","4","OMM","2026-04-09 20:25:38","2026-04-09 20:28:40");
INSERT INTO `asset_items` VALUES("15","10",NULL,"6","0","",NULL,NULL,NULL,"Market Extension A",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-0009",NULL,"2026-04-09","NULL",NULL,"qr_asset_15_1775738302.png","serviceable",NULL,NULL,"1200000.00","2026-04-09","4","OMM","2026-04-09 20:37:42","2026-04-09 20:38:22");
INSERT INTO `asset_items` VALUES("16","11",NULL,"6","0","",NULL,NULL,NULL,"Parking & Terminal Area",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-00010",NULL,"2026-04-09","NULL",NULL,"qr_asset_16_1775738600.png","serviceable",NULL,NULL,"2800000.00","2026-04-09","4","OMM","2026-04-09 20:42:51","2026-04-09 20:43:20");
INSERT INTO `asset_items` VALUES("17","12",NULL,"6","0","",NULL,NULL,NULL,"Municipal Public Market Lot",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-00011",NULL,"2026-04-09","NULL",NULL,"qr_asset_17_1775738766.png","serviceable",NULL,NULL,"4500000.00","2026-04-09","4","OMM","2026-04-09 20:45:41","2026-04-09 20:46:06");
INSERT INTO `asset_items` VALUES("18","13",NULL,"35","20","Roberto Cruz",NULL,NULL,NULL,"Hino 500 Compactor","QN90C","SGH3420XYZ",NULL,"1","2026-07-06-010-0101-07","OMMP-2026-00013",NULL,"2026-04-10","NULL",NULL,"qr_asset_18_1775783025.png","serviceable",NULL,NULL,"4200000.00","2026-04-10","14","Motorpool","2026-04-10 08:57:05","2026-04-10 09:03:45");
INSERT INTO `asset_items` VALUES("19","13",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Hino 500 Compactor",NULL,NULL,NULL,"1","2026-07-06-010-0102-07",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"4200000.00","2026-04-10","14",NULL,"2026-04-10 08:57:05","2026-04-10 08:57:05");
INSERT INTO `asset_items` VALUES("20","13",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Hino 500 Compactor",NULL,NULL,NULL,"1","2026-07-06-010-0103-07",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"4200000.00","2026-04-10","14",NULL,"2026-04-10 08:57:05","2026-04-10 08:57:05");
INSERT INTO `asset_items` VALUES("21","14",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Toyota Hilux (Service)",NULL,NULL,NULL,"1","2026-07-06-010-0101-07",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"1450000.00","2026-04-10","14",NULL,"2026-04-10 08:57:53","2026-04-10 08:57:53");
INSERT INTO `asset_items` VALUES("22","14",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Toyota Hilux (Service)",NULL,NULL,NULL,"1","2026-07-06-010-0102-07",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"1450000.00","2026-04-10","14",NULL,"2026-04-10 08:57:53","2026-04-10 08:57:53");
INSERT INTO `asset_items` VALUES("23","15",NULL,"6","0","",NULL,NULL,NULL,"Brgy. Health Center Lot",NULL,NULL,NULL,"1","2026-07-01-010-0101-22","OMMP-2026-00014",NULL,"2026-04-10","NULL",NULL,"qr_asset_23_1775784289.png","serviceable",NULL,NULL,"2000000.00","2026-04-10","11","OMH","2026-04-10 08:59:29","2026-04-10 09:52:18");
INSERT INTO `asset_items` VALUES("24","16",NULL,"6","0","",NULL,NULL,NULL,"Municipal Hall Lot",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-00012",NULL,"2026-04-10","NULL",NULL,"qr_asset_24_1775782938.png","serviceable",NULL,NULL,"12500000.00","2026-04-10","4","OMM","2026-04-10 09:00:29","2026-04-10 09:02:18");
INSERT INTO `asset_items` VALUES("25","17",NULL,"6","0","",NULL,NULL,NULL,"Evacuation Center Site",NULL,NULL,NULL,"1","2026-07-01-010-0101-01","OMMP-2026-00016",NULL,"2026-04-10","NULL",NULL,"qr_asset_25_1775784864.png","serviceable",NULL,NULL,"14000000.00","2020-02-12","4","OMM","2026-04-10 09:32:44","2026-04-10 09:34:24");
INSERT INTO `asset_items` VALUES("26","18","3",NULL,NULL,NULL,"1","1",NULL,"Executive Desk",NULL,NULL,"","1","2026-04-07-010-0101-01",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"18500.00","2026-04-10","12",NULL,"2026-04-10 10:11:04","2026-04-10 10:11:04");
INSERT INTO `asset_items` VALUES("27","18","3",NULL,NULL,NULL,"1","1",NULL,"Executive Desk",NULL,NULL,"","1","2026-04-07-010-0102-01",NULL,NULL,NULL,NULL,NULL,NULL,"no_tag",NULL,NULL,"18500.00","2026-04-10","12",NULL,"2026-04-10 10:11:04","2026-04-10 10:11:04");
INSERT INTO `asset_items` VALUES("28","19","2","2","41","Elton John Moises",NULL,NULL,NULL,"Lenovo ThinkPad E14","QN90C","SGH3420XYZ",NULL,"1","2026-07-05-030-0101-02","OVMP-2026-0001",NULL,"2026-04-13","[\"asset_28_0_1776070153.webp\",\"asset_28_1_1776070153.avif\"]",NULL,"qr_asset_28_1776070154.png","serviceable",NULL,NULL,"57999.00","2026-04-13","5","OVM","2026-04-13 16:48:29","2026-04-13 16:49:14");

-- Table structure for `asset_land`
DROP TABLE IF EXISTS `asset_land`;
CREATE TABLE `asset_land` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `land_type` enum('commercial','residential','agricultural','industrial','mixed') DEFAULT 'commercial',
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Philippines',
  `lot_area` decimal(12,2) DEFAULT NULL,
  `frontage` decimal(10,2) DEFAULT NULL,
  `depth` decimal(10,2) DEFAULT NULL,
  `shape` enum('regular','irregular') DEFAULT 'regular',
  `topography` enum('flat','sloping','hilly','mountainous') DEFAULT 'flat',
  `zoning_classification` varchar(100) DEFAULT NULL,
  `land_classification` varchar(100) DEFAULT NULL,
  `tax_declaration_number` varchar(50) DEFAULT NULL,
  `land_title_number` varchar(50) DEFAULT NULL,
  `survey_number` varchar(50) DEFAULT NULL,
  `corner_lot` tinyint(1) DEFAULT 0,
  `road_access` enum('paved','gravel','dirt','none') DEFAULT 'paved',
  `utilities_available` enum('full','partial','none') DEFAULT 'partial',
  `flood_prone` tinyint(1) DEFAULT 0,
  `encumbrances` text DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_land_type` (`land_type`),
  KEY `idx_address` (`city`,`state`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_land_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_land_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_land_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_land`

-- Table structure for `asset_land_info`
DROP TABLE IF EXISTS `asset_land_info`;
CREATE TABLE `asset_land_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `area_size` varchar(255) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `tax_declaration` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_item_id` (`asset_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_land_info`
INSERT INTO `asset_land_info` VALUES("1","25","Lot 442-B","3,500 sqm","Town Proper","TD-065-2026-001","2026-04-10 06:34:24","2026-04-10 06:34:24","5",NULL);

-- Table structure for `asset_machinery`
DROP TABLE IF EXISTS `asset_machinery`;
CREATE TABLE `asset_machinery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `machine_type` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model_number` varchar(50) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `capacity` varchar(50) DEFAULT NULL,
  `power_requirements` varchar(100) DEFAULT NULL,
  `voltage` int(11) DEFAULT NULL,
  `operating_weight` decimal(10,2) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_interval_days` int(11) DEFAULT 90,
  `operator_required` tinyint(1) DEFAULT 1,
  `safety_certification` varchar(100) DEFAULT NULL,
  `certification_expiry` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `location_building` varchar(100) DEFAULT NULL,
  `location_area` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_next_maintenance` (`next_maintenance_date`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_machinery_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_machinery_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_machinery_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_machinery`

-- Table structure for `asset_office_equipment`
DROP TABLE IF EXISTS `asset_office_equipment`;
CREATE TABLE `asset_office_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `equipment_type` enum('printer','scanner','photocopier','fax','telephone','projector','shredder','other') DEFAULT 'other',
  `brand` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `connectivity` enum('usb','network','wireless','bluetooth','multi') DEFAULT 'usb',
  `network_ip` varchar(15) DEFAULT NULL,
  `functions` text DEFAULT NULL,
  `paper_size` varchar(20) DEFAULT NULL,
  `print_speed_ppm` int(11) DEFAULT NULL,
  `scan_resolution` varchar(20) DEFAULT NULL,
  `color_capability` tinyint(1) DEFAULT 0,
  `power_consumption` varchar(20) DEFAULT NULL,
  `warranty_provider` varchar(100) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `location_building` varchar(100) DEFAULT NULL,
  `location_floor` varchar(20) DEFAULT NULL,
  `location_room` varchar(50) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_equipment_type` (`equipment_type`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_warranty_expiry` (`warranty_expiry`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_office_equipment_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_office_equipment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_office_equipment_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_office_equipment`

-- Table structure for `asset_software`
DROP TABLE IF EXISTS `asset_software`;
CREATE TABLE `asset_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `software_name` varchar(100) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `license_type` enum('perpetual','subscription','open_source','freemium') DEFAULT 'perpetual',
  `license_key` varchar(200) DEFAULT NULL,
  `number_of_licenses` int(11) DEFAULT 1,
  `platform` enum('windows','mac','linux','web','mobile','multi_platform') DEFAULT 'windows',
  `installation_date` date DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `renewal_cost` decimal(10,2) DEFAULT NULL,
  `vendor` varchar(100) DEFAULT NULL,
  `support_contact` varchar(100) DEFAULT NULL,
  `activation_method` enum('key','online','usb_dongle','account') DEFAULT 'key',
  `server_based` tinyint(1) DEFAULT 0,
  `concurrent_users` int(11) DEFAULT NULL,
  `hardware_requirements` text DEFAULT NULL,
  `installation_path` varchar(200) DEFAULT NULL,
  `assigned_department` varchar(100) DEFAULT NULL,
  `condition_status` enum('active','inactive','deprecated') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_software_name` (`software_name`),
  KEY `idx_license_expiry` (`license_expiry`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_software_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_software_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_software_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_software`

-- Table structure for `asset_sub_categories`
DROP TABLE IF EXISTS `asset_sub_categories`;
CREATE TABLE `asset_sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sub_category_name` varchar(255) NOT NULL,
  `sub_category_code` varchar(10) NOT NULL,
  `asset_categories_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `useful_life` int(11) DEFAULT NULL COMMENT 'Useful life in years',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asset_categories_id` (`asset_categories_id`),
  KEY `idx_status` (`status`),
  KEY `fk_sub_categories_created_by` (`created_by`),
  KEY `fk_sub_categories_updated_by` (`updated_by`),
  CONSTRAINT `fk_sub_categories_asset_categories` FOREIGN KEY (`asset_categories_id`) REFERENCES `asset_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_categories_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `asset_sub_categories`
INSERT INTO `asset_sub_categories` VALUES("1","ID PRINTER","03","2","active","3","1","1","2026-02-13 21:23:51","2026-03-11 14:40:08");
INSERT INTO `asset_sub_categories` VALUES("2","COMPUTER DESKTOP","02","2","active","4","1","1","2026-02-13 21:23:51","2026-03-11 14:38:57");
INSERT INTO `asset_sub_categories` VALUES("3","LAPTOP","01","2","active","3","1","1","2026-02-13 21:23:51","2026-03-11 14:38:27");
INSERT INTO `asset_sub_categories` VALUES("11","CARD PRINTER","04","2","active","3","1","1","2026-03-11 14:43:11","2026-03-11 14:48:11");
INSERT INTO `asset_sub_categories` VALUES("12","NOTEBOOK","05","2","active","3","1","1","2026-03-11 14:44:55","2026-03-11 14:48:15");
INSERT INTO `asset_sub_categories` VALUES("13","ADVERTISING MACHINE KIOSK","06","2","active","4","1","1","2026-03-11 14:45:39","2026-03-11 14:48:18");
INSERT INTO `asset_sub_categories` VALUES("14","SMART TV","07","2","active","3","1","1","2026-03-11 14:46:01","2026-03-11 14:48:22");
INSERT INTO `asset_sub_categories` VALUES("18","NITROGEN TANK","01","9","active","0","1","1","2026-03-11 15:14:51","2026-03-11 15:15:06");
INSERT INTO `asset_sub_categories` VALUES("19","HAND TRACTOR","02","9","active","0","1","1","2026-03-11 15:15:26","2026-03-11 15:15:34");
INSERT INTO `asset_sub_categories` VALUES("20","WHEELS TRACTOR","03","9","active","0","1",NULL,"2026-03-11 15:22:38","2026-03-11 15:22:38");
INSERT INTO `asset_sub_categories` VALUES("21","Truck","01","35","active","10","1",NULL,"2026-04-07 14:58:32","2026-04-07 14:58:32");
INSERT INTO `asset_sub_categories` VALUES("22","Office Desk","01","1","active","10","1",NULL,"2026-04-07 14:58:58","2026-04-07 14:58:58");
INSERT INTO `asset_sub_categories` VALUES("23","Schools","01","27","active","15","1",NULL,"2026-04-07 14:59:47","2026-04-07 14:59:47");
INSERT INTO `asset_sub_categories` VALUES("24","Router","01","10","active","5","1",NULL,"2026-04-07 15:00:03","2026-04-07 15:00:03");
INSERT INTO `asset_sub_categories` VALUES("25","Excavator","01","11","active","10","1",NULL,"2026-04-07 15:00:23","2026-04-07 15:00:23");
INSERT INTO `asset_sub_categories` VALUES("26","Drill","01","4","active","5","1",NULL,"2026-04-07 15:00:53","2026-04-07 15:00:53");
INSERT INTO `asset_sub_categories` VALUES("27","Wheel Chair","01","21","active","5","1",NULL,"2026-04-07 15:01:13","2026-04-07 15:01:13");
INSERT INTO `asset_sub_categories` VALUES("28","Boat","01","18","active","15","1",NULL,"2026-04-07 15:01:47","2026-04-07 15:01:47");
INSERT INTO `asset_sub_categories` VALUES("29","MARKET","01","6","active","0","1",NULL,"2026-04-09 19:49:30","2026-04-09 19:49:30");

-- Table structure for `asset_vehicles`
DROP TABLE IF EXISTS `asset_vehicles`;
CREATE TABLE `asset_vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `engine_number` varchar(50) DEFAULT NULL,
  `chassis_number` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `year_manufactured` int(11) DEFAULT NULL,
  `fuel_type` enum('gasoline','diesel','electric','hybrid','lpg') DEFAULT 'gasoline',
  `transmission_type` enum('manual','automatic','cvt') DEFAULT 'manual',
  `registration_date` date DEFAULT NULL,
  `registration_expiry` date DEFAULT NULL,
  `insurance_provider` varchar(100) DEFAULT NULL,
  `insurance_policy_number` varchar(50) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT 0,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_id` (`asset_item_id`),
  KEY `idx_plate_number` (`plate_number`),
  KEY `idx_registration_expiry` (`registration_expiry`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `asset_vehicles_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asset_vehicles_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `asset_vehicles_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `asset_vehicles`
INSERT INTO `asset_vehicles` VALUES("2","7","ABC4567","1NR-FE123456","JACNKR770J4567890","White","CXZ77","Isuzu","0","gasoline","manual",NULL,NULL,NULL,NULL,NULL,"0",NULL,NULL,"good",NULL,"2026-04-08 11:49:02","2026-04-08 11:49:02","5",NULL);
INSERT INTO `asset_vehicles` VALUES("3","18","ABC-156","1NR-FE123456","JACNKR770J4567890","BLUE","QN90C","Hino","0","gasoline","manual",NULL,NULL,NULL,NULL,NULL,"0",NULL,NULL,"good",NULL,"2026-04-10 09:03:45","2026-04-10 09:03:45","5",NULL);

-- Table structure for `assets`
DROP TABLE IF EXISTS `assets`;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_categories_id` int(11) DEFAULT NULL,
  `asset_subcategory_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asset_category` (`asset_categories_id`),
  KEY `idx_asset_office` (`office_id`),
  KEY `idx_asset_description` (`description`),
  KEY `idx_asset_subcategory_id` (`asset_subcategory_id`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`asset_categories_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_assets_asset_subcategory` FOREIGN KEY (`asset_subcategory_id`) REFERENCES `asset_sub_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `assets`
INSERT INTO `assets` VALUES("1","2","2","Laptop AMD Ryzen","units","2","28000.00","4","2026-04-07 11:08:45","2026-04-07 14:31:44");
INSERT INTO `assets` VALUES("2","2","2","ASUS Vivobook 16","units","3","42900.00","5","2026-04-07 14:35:06","2026-04-07 14:38:53");
INSERT INTO `assets` VALUES("3","35",NULL,"Isuzu GIGA Dump Truck","units","2","4850000.00","14","2026-04-07 15:04:28","2026-04-08 11:49:02");
INSERT INTO `assets` VALUES("4","2","1","Evolis Primacy 2","units","1","48500.00","4","2026-04-08 09:01:28","2026-04-08 09:01:28");
INSERT INTO `assets` VALUES("5","1","22","Office Table – Wooden","units","2","23999.00","5","2026-04-08 09:36:05","2026-04-08 09:36:05");
INSERT INTO `assets` VALUES("6","6",NULL,"Public Market Lot","units","1","8200000.00","4","2026-04-09 19:51:48","2026-04-09 19:56:55");
INSERT INTO `assets` VALUES("7","6",NULL,"Proposed Sanitary Landfill","lot","1","4500000.00","4","2026-04-09 20:01:09","2026-04-09 20:12:19");
INSERT INTO `assets` VALUES("8","6",NULL,"Municipal Hall Site","lot","1","15500000.00","4","2026-04-09 20:13:48","2026-04-09 20:19:32");
INSERT INTO `assets` VALUES("9","6",NULL,"Main Public Market Lot","lot","1","4500000.00","4","2026-04-09 20:25:38","2026-04-09 20:28:40");
INSERT INTO `assets` VALUES("10","6",NULL,"Market Extension A","lot","1","1200000.00","4","2026-04-09 20:37:42","2026-04-09 20:38:22");
INSERT INTO `assets` VALUES("11","6",NULL,"Parking & Terminal Area","lot","1","2800000.00","4","2026-04-09 20:42:51","2026-04-09 20:43:20");
INSERT INTO `assets` VALUES("12","6",NULL,"Municipal Public Market Lot","lot","1","4500000.00","4","2026-04-09 20:45:41","2026-04-09 20:46:06");
INSERT INTO `assets` VALUES("13","35",NULL,"Hino 500 Compactor","units","3","4200000.00","14","2026-04-10 08:57:05","2026-04-10 09:03:45");
INSERT INTO `assets` VALUES("14","35","21","Toyota Hilux (Service)","units","2","1450000.00","14","2026-04-10 08:57:53","2026-04-10 08:57:53");
INSERT INTO `assets` VALUES("15","6",NULL,"Brgy. Health Center Lot","lot","1","850000.00","11","2026-04-10 08:59:29","2026-04-10 09:24:49");
INSERT INTO `assets` VALUES("16","6",NULL,"Municipal Hall Lot","lot","1","12500000.00","4","2026-04-10 09:00:29","2026-04-10 09:02:18");
INSERT INTO `assets` VALUES("17","6",NULL,"Evacuation Center Site","lot","1","14000000.00","4","2026-04-10 09:32:44","2026-04-10 09:34:24");
INSERT INTO `assets` VALUES("18","1","3","Executive Desk","","2","18500.00","12","2026-04-10 10:11:04","2026-04-10 10:11:04");
INSERT INTO `assets` VALUES("19","2","2","Lenovo ThinkPad E14","units","1","57999.00","5","2026-04-13 16:48:29","2026-04-13 16:49:14");

-- Table structure for `backup_execution_logs`
DROP TABLE IF EXISTS `backup_execution_logs`;
CREATE TABLE `backup_execution_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scheduled_backup_id` int(11) NOT NULL,
  `execution_status` enum('running','completed','failed') NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `backup_id` int(11) DEFAULT NULL COMMENT 'ID of the created backup if successful',
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `backup_execution_logs`

-- Table structure for `backups`
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `backups`
INSERT INTO `backups` VALUES("8","Daily Backup","full","1","1","../backups/Daily Backup_2026-01-05_13-41-33","1","2026-01-05 20:41:33","0",NULL,NULL,NULL,NULL,NULL);
INSERT INTO `backups` VALUES("9","Daily Backup","full","1","1","../backups/Daily Backup_2026-01-05_14-05-54","1","2026-01-05 21:05:54","0",NULL,NULL,NULL,NULL,NULL);
INSERT INTO `backups` VALUES("10","online Backup","full","1","1","../backups/online Backup_2026-01-05_14-29-41","1","2026-01-05 21:29:41","1","0","https://drive.google.com/file/d/1qrf12E9fs98ak_we_UXsTnEyGt4z5pcR/view","completed",NULL,"2026-01-05 22:13:59");
INSERT INTO `backups` VALUES("11","Daily Backup","full","1","1","../backups/Daily Backup_2026-01-05_14-46-20","1","2026-01-05 21:46:21","0",NULL,NULL,NULL,NULL,NULL);

-- Table structure for `borrow_form_submissions`
DROP TABLE IF EXISTS `borrow_form_submissions`;
CREATE TABLE `borrow_form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_name` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `date_borrowed` date NOT NULL,
  `schedule_return` date DEFAULT NULL,
  `releasing_officer` varchar(255) NOT NULL,
  `approved_by` varchar(255) NOT NULL,
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `status` enum('approved','returned') NOT NULL DEFAULT 'approved',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_date_borrowed` (`date_borrowed`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_guest_name` (`guest_name`),
  KEY `idx_barangay` (`barangay`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `borrow_form_submissions`
INSERT INTO `borrow_form_submissions` VALUES("3","Walton Loneza","Marifosque","09107171456","2026-04-10",NULL,"Joshua Escano","Elton John Moises","[{\"asset_id\":7,\"quantity\":1,\"remarks\":\"\",\"description\":\"Isuzu GIGA Dump Truck\"}]","returned","2026-04-10 21:02:56","2026-04-11 13:25:06");
INSERT INTO `borrow_form_submissions` VALUES("4","Walton Loneza","Centro Occidental","09107171456","2026-04-11",NULL,"Joshua Escano","Elton John Moises","[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"},{\"asset_item_id\":2,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0102-01\",\"remarks\":\"\",\"category\":\"ITS\"}]","returned","2026-04-11 19:35:32","2026-04-11 19:54:38");
INSERT INTO `borrow_form_submissions` VALUES("5","Walton Loneza","Centro Occidental","09107171456","2026-04-14",NULL,"Joshua Escano","Elton John Moises","[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"}]","returned","2026-04-14 09:41:43","2026-04-14 13:44:38");
INSERT INTO `borrow_form_submissions` VALUES("6","Walton Loneza","Centro Occidental","09107171456","2026-04-14",NULL,"Joshua Escano","Elton John Moises","[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"}]","returned","2026-04-14 13:55:13","2026-04-14 13:55:19");

-- Table structure for `borrow_requests`
DROP TABLE IF EXISTS `borrow_requests`;
CREATE TABLE `borrow_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requested_by` int(11) NOT NULL,
  `requested_by_office` int(11) NOT NULL,
  `requested_to_office` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 1,
  `quantity_approved` int(11) DEFAULT NULL,
  `purpose` text NOT NULL,
  `urgency_level` enum('normal','urgent','emergency') NOT NULL DEFAULT 'normal',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','approved','denied','returned','borrowed','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `denied_by` int(11) DEFAULT NULL,
  `denied_at` datetime DEFAULT NULL,
  `denial_reason` text DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `return_condition` enum('excellent','good','fair','poor') DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `return_photo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_requested_by_office` (`requested_by_office`),
  KEY `idx_requested_to_office` (`requested_to_office`),
  KEY `idx_asset_id` (`asset_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `fk_borrow_requests_approved_by` (`approved_by`),
  KEY `fk_borrow_requests_denied_by` (`denied_by`),
  KEY `idx_borrow_requests_composite` (`status`,`requested_to_office`,`created_at`),
  KEY `idx_borrow_requests_outgoing` (`status`,`requested_by_office`,`created_at`),
  KEY `idx_borrow_requests_office_date` (`requested_by_office`,`created_at`),
  CONSTRAINT `fk_borrow_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_borrow_requests_asset_id` FOREIGN KEY (`asset_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrow_requests_denied_by` FOREIGN KEY (`denied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_borrow_requests_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrow_requests_requested_by_office` FOREIGN KEY (`requested_by_office`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_borrow_requests_requested_to_office` FOREIGN KEY (`requested_to_office`) REFERENCES `offices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Table for managing asset borrow requests between offices';

-- Dumping data for `borrow_requests`

-- Table structure for `consumable_add_history`
DROP TABLE IF EXISTS `consumable_add_history`;
CREATE TABLE `consumable_add_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumable_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `units` varchar(50) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `office_id` int(11) NOT NULL,
  `to_office_id` int(11) DEFAULT NULL,
  `added_by` int(11) NOT NULL,
  `add_date` datetime DEFAULT current_timestamp(),
  `source` varchar(50) DEFAULT 'manual',
  `notes` text DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumable_id` (`consumable_id`),
  KEY `office_id` (`office_id`),
  KEY `added_by` (`added_by`),
  CONSTRAINT `consumable_add_history_ibfk_1` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`),
  CONSTRAINT `consumable_add_history_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `consumable_add_history_ibfk_3` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `consumable_add_history`
INSERT INTO `consumable_add_history` VALUES("1","1","Air Freshner (Spray)","6","bottles","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("2","2","Albatross 50gms","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("3","3","Arch File A4 Blue","20","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("4","4","Arch File Long Blue","15","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("5","5","Ballpen Black","6","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("6","6","Battery AA","20","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("7","7","Battery AAA","20","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("8","8","Binder Clip 25 mm","15","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("9","9","Binder Clip 41 mm","15","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("10","10","Bookpaper A4","40","ream","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("11","11","Bookpaper Long","70","ream","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("12","12","Brother Ink BT5000 BMCY","15","set","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("13","13","Brother ink D60 Black","20","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("14","14","Brown Envelope Long","80","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("15","15","Brown Plastic Envelope Long","30","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("16","16","Bulb  Watts (LED) 12 watts","15","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("17","17","Canon Ink G1010 CYM","10","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("18","18","Canon ink G1010black","10","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("19","19","Clear book (long)","20","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("20","20","Computer Keyboard","4","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("21","21","Cork board 60cmx90cm","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("22","22","Data File Box Long","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("23","23","Dishwashing Liquid","12","liter","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("24","24","Doormat Rubberized","6","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("25","25","Dust Pan","6","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("26","26","Extension wire (10m)","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("27","27","Extension wire (20m)","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("28","28","Fastener long (plastic)","5","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("29","29","Fastener plastic small","5","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("30","30","Floormop with spinner","3","sets","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("31","31","Folder Long White","100","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("32","32","Frame 8x13","110","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("33","33","Gina Cloth","10","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("34","34","Glass Cleaner","10","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("35","35","Glue 130g","10","bottles","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("36","36","Highlighter (Yellow)","5","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("37","37","HP 336X High Yield","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("38","38","Insect Repellant (Spray) Big","4","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("39","39","Interfolded Tissue Paper","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("40","40","Long Expanded Folder (Blue)","60","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("41","41","Long Expanded Folder (Green)","200","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("42","42","Mailing Envelope Long","3","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("43","43","Mouse Pad","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("44","44","Muriatic acid Liter","3","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("45","45","paper clip 28mm","20","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("46","46","Paper Clip 33mm","20","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("47","47","Paper Clip 50mm","20","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("48","48","Paper Puncher","1","pc.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("49","49","Pencil (Mongol 2) 12\'s","4","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("50","50","Pencil Sharpener (Heavy Duty)","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("51","51","Permanent Marker (Pilot) Black","1","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("52","52","Plastic pail","6","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("53","53","Puncher (HD)","2","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("54","54","Push Pin","4","bxs","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("55","55","Record Book (300lvs)","30","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("56","56","Record Book (500lvs)","35","pcs.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("57","57","Rubber band (small)","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("58","58","Scissors (Stainless)","12","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("59","59","Scotchbrite sponge","48","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("60","60","Signpen 0.5 Black","10","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("61","61","Signpen energel black (0.7)","6","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("62","62","Signpen energel blue (0.7)","4","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("63","63","Soap (Detergent)","5","kl.","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("64","64","Soft Broom","4","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("65","65","Special Paper Long Cream (180 gsm)","20","pack","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("66","66","Special Paper Long Cream (90gsm)","20","pcks","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("67","67","Spiral 1\"","15","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("68","68","Sponges wiper with long handle","4","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("69","69","Staple wire #35","10","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("70","70","Steel Rack","2","unit","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("71","71","Sticker paper long matte","20","pcks","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("72","72","Tape (Packing Tape) 3\"","6","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("73","73","Tape (Scotch) 1\"","10","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("74","74","Tape (Scotch) 2\"","20","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("75","75","Tape dispenser","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("76","76","Tape -Double Side 2\"","10","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("77","77","Tape -Double Sided 1\"","10","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("78","78","Thumbtacks","4","box","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("79","79","Tissue paper","100","roll","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("80","80","Toilet bowl brush","8","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("81","81","Toilet bowl Cleaner(500ml)","10","btls","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("82","82","Trash Bag (Black)  Large","20","pack","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("83","83","Trash Bag (Black)  Medium","20","pack","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("84","84","Trash Bin with Swing Lid Black","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("85","85","USB64 GB","20","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("86","86","Vellum Paper Long (Cream)","3","reams","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("87","87","Vellum Paper Long (White)","4","pack","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("88","88","White board 1/4","2","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("89","89","White Board Marker Black (Pilot)","10","pc","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("90","90","Yellow paper","12","pad","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("91","91","Zonrox","10","liter","0.00","0.00","3","12","5","2026-04-10 07:23:36","ris_form","Added via RIS form #Unknown","N/A");
INSERT INTO `consumable_add_history` VALUES("92","183","Ballpen Black","50","boxes","75.00","3750.00","3","3","5","2026-04-13 15:01:03","new_consumable","New consumable added to inventory","J&F suppliers");
INSERT INTO `consumable_add_history` VALUES("93","5","Ballpen Black","75","boxes","75.00","5625.00","3",NULL,"5","2026-04-13 15:07:07","stock_addition","Stock added to existing consumable. New WAC: ₱75.00","J&F suppliers");
INSERT INTO `consumable_add_history` VALUES("94","5","Ballpen Black","100","boxes","60.00","6000.00","3",NULL,"5","2026-04-13 15:24:52","stock_addition","Stock added to existing consumable. New WAC: ₱60.00","J&F suppliers");

-- Table structure for `consumable_balance`
DROP TABLE IF EXISTS `consumable_balance`;
CREATE TABLE `consumable_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumable_id` int(11) NOT NULL,
  `consumable_description` varchar(255) NOT NULL,
  `office_id` int(11) NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `for_office_id` int(11) DEFAULT NULL,
  `total_borrowed` int(11) DEFAULT 0,
  `total_deducted` int(11) DEFAULT 0,
  `current_balance` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_consumable_office` (`consumable_id`,`office_id`,`for_office_id`),
  KEY `idx_for_office` (`for_office_id`),
  KEY `office_id` (`office_id`),
  CONSTRAINT `consumable_balance_ibfk_1` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumable_balance_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumable_balance_ibfk_3` FOREIGN KEY (`for_office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `consumable_balance`
INSERT INTO `consumable_balance` VALUES("3","183","Ballpen Black","12","0","3","0","1","1","2026-04-13 15:24:08","2026-04-13 15:24:08");

-- Table structure for `consumable_release_history`
DROP TABLE IF EXISTS `consumable_release_history`;
CREATE TABLE `consumable_release_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumable_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_released` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(12,2) NOT NULL,
  `from_office_id` int(11) NOT NULL,
  `to_office_id` int(11) NOT NULL,
  `released_by` int(11) NOT NULL,
  `received_by` varchar(255) DEFAULT NULL,
  `release_option` enum('deduct','release') NOT NULL DEFAULT 'release',
  `release_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `released_by` (`released_by`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_release_date` (`release_date`),
  KEY `idx_from_office` (`from_office_id`),
  KEY `idx_to_office` (`to_office_id`),
  KEY `idx_received_by` (`received_by`),
  KEY `idx_release_option` (`release_option`),
  CONSTRAINT `consumable_release_history_ibfk_1` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`),
  CONSTRAINT `consumable_release_history_ibfk_2` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `consumable_release_history_ibfk_3` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `consumable_release_history_ibfk_4` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `consumable_release_history`
INSERT INTO `consumable_release_history` VALUES("1","1","Air Freshner (Spray)","3.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("2","2","Albatross 50gms","5.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("3","3","Arch File A4 Blue","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("4","4","Arch File Long Blue","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("5","5","Ballpen Black","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("6","6","Battery AA","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("7","7","Battery AAA","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("8","8","Binder Clip 25 mm","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("9","9","Binder Clip 41 mm","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("10","10","Bookpaper A4","40.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("11","11","Bookpaper Long","70.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("12","12","Brother Ink BT5000 BMCY","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("13","13","Brother ink D60 Black","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("14","14","Brown Envelope Long","80.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("15","15","Brown Plastic Envelope Long","30.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("16","16","Bulb  Watts (LED) 12 watts","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("17","17","Canon Ink G1010 CYM","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("18","18","Canon ink G1010black","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("19","19","Clear book (long)","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("20","20","Computer Keyboard","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("21","21","Cork board 60cmx90cm","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("22","22","Data File Box Long","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("23","23","Dishwashing Liquid","12.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("24","24","Doormat Rubberized","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("25","25","Dust Pan","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("26","26","Extension wire (10m)","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("27","27","Extension wire (20m)","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("28","28","Fastener long (plastic)","5.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("29","29","Fastener plastic small","5.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("30","30","Floormop with spinner","3.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("31","31","Folder Long White","100.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("32","32","Frame 8x13","110.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("33","33","Gina Cloth","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("34","34","Glass Cleaner","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("35","35","Glue 130g","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("36","36","Highlighter (Yellow)","5.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("37","37","HP 336X High Yield","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("38","38","Insect Repellant (Spray) Big","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("39","39","Interfolded Tissue Paper","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("40","40","Long Expanded Folder (Blue)","60.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("41","41","Long Expanded Folder (Green)","200.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("42","42","Mailing Envelope Long","3.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("43","43","Mouse Pad","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("44","44","Muriatic acid Liter","3.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("45","45","paper clip 28mm","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("46","46","Paper Clip 33mm","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("47","47","Paper Clip 50mm","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("48","48","Paper Puncher","1.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("49","49","Pencil (Mongol 2) 12\'s","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("50","50","Pencil Sharpener (Heavy Duty)","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("51","51","Permanent Marker (Pilot) Black","1.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("52","52","Plastic pail","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("53","53","Puncher (HD)","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("54","54","Push Pin","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("55","55","Record Book (300lvs)","30.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("56","56","Record Book (500lvs)","35.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("57","57","Rubber band (small)","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("58","58","Scissors (Stainless)","12.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("59","59","Scotchbrite sponge","48.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("60","60","Signpen 0.5 Black","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("61","61","Signpen energel black (0.7)","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("62","62","Signpen energel blue (0.7)","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("63","63","Soap (Detergent)","5.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("64","64","Soft Broom","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("65","65","Special Paper Long Cream (180 gsm)","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("66","66","Special Paper Long Cream (90gsm)","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("67","67","Spiral 1\"","15.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("68","68","Sponges wiper with long handle","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("69","69","Staple wire #35","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("70","70","Steel Rack","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("71","71","Sticker paper long matte","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("72","72","Tape (Packing Tape) 3\"","6.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("73","73","Tape (Scotch) 1\"","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("74","74","Tape (Scotch) 2\"","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("75","76","Tape -Double Side 2\"","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("76","77","Tape -Double Sided 1\"","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("77","75","Tape dispenser","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("78","78","Thumbtacks","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("79","79","Tissue paper","100.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history` VALUES("80","80","Toilet bowl brush","8.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("81","81","Toilet bowl Cleaner(500ml)","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("82","82","Trash Bag (Black)  Large","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("83","83","Trash Bag (Black)  Medium","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("84","84","Trash Bin with Swing Lid Black","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("85","85","USB64 GB","20.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("86","86","Vellum Paper Long (Cream)","3.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("87","87","Vellum Paper Long (White)","4.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("88","88","White board 1/4","2.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("89","89","White Board Marker Black (Pilot)","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("90","90","Yellow paper","12.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history` VALUES("91","91","Zonrox","10.00","0.00","0.00","3","12","5","BENJAMIN THOMPSON","deduct","2026-04-13 14:57:32","","2026-04-13 14:57:32");

-- Table structure for `consumable_release_history_view`
DROP TABLE IF EXISTS `consumable_release_history_view`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `consumable_release_history_view` AS select `h`.`id` AS `id`,`h`.`consumable_id` AS `consumable_id`,`h`.`description` AS `description`,`h`.`quantity_released` AS `quantity_released`,`h`.`unit_cost` AS `unit_cost`,`h`.`total_value` AS `total_value`,`h`.`from_office_id` AS `from_office_id`,`fo`.`office_name` AS `from_office_name`,`h`.`to_office_id` AS `to_office_id`,`to_off`.`office_name` AS `to_office_name`,`h`.`released_by` AS `released_by`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `released_by_name`,`h`.`release_date` AS `release_date`,`h`.`notes` AS `notes`,`h`.`created_at` AS `created_at` from (((`consumable_release_history` `h` left join `offices` `fo` on(`h`.`from_office_id` = `fo`.`id`)) left join `offices` `to_off` on(`h`.`to_office_id` = `to_off`.`id`)) left join `users` `u` on(`h`.`released_by` = `u`.`id`));

-- Dumping data for `consumable_release_history_view`
INSERT INTO `consumable_release_history_view` VALUES("1","1","Air Freshner (Spray)","3.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("2","2","Albatross 50gms","5.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("3","3","Arch File A4 Blue","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("4","4","Arch File Long Blue","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("5","5","Ballpen Black","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("6","6","Battery AA","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("7","7","Battery AAA","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("8","8","Binder Clip 25 mm","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("9","9","Binder Clip 41 mm","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("10","10","Bookpaper A4","40.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("11","11","Bookpaper Long","70.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("12","12","Brother Ink BT5000 BMCY","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("13","13","Brother ink D60 Black","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("14","14","Brown Envelope Long","80.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("15","15","Brown Plastic Envelope Long","30.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("16","16","Bulb  Watts (LED) 12 watts","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("17","17","Canon Ink G1010 CYM","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("18","18","Canon ink G1010black","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("19","19","Clear book (long)","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("20","20","Computer Keyboard","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("21","21","Cork board 60cmx90cm","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("22","22","Data File Box Long","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("23","23","Dishwashing Liquid","12.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("24","24","Doormat Rubberized","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("25","25","Dust Pan","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("26","26","Extension wire (10m)","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("27","27","Extension wire (20m)","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("28","28","Fastener long (plastic)","5.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("29","29","Fastener plastic small","5.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("30","30","Floormop with spinner","3.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("31","31","Folder Long White","100.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("32","32","Frame 8x13","110.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("33","33","Gina Cloth","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("34","34","Glass Cleaner","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("35","35","Glue 130g","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("36","36","Highlighter (Yellow)","5.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("37","37","HP 336X High Yield","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("38","38","Insect Repellant (Spray) Big","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("39","39","Interfolded Tissue Paper","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("40","40","Long Expanded Folder (Blue)","60.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("41","41","Long Expanded Folder (Green)","200.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("42","42","Mailing Envelope Long","3.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("43","43","Mouse Pad","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("44","44","Muriatic acid Liter","3.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("45","45","paper clip 28mm","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("46","46","Paper Clip 33mm","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("47","47","Paper Clip 50mm","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("48","48","Paper Puncher","1.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("49","49","Pencil (Mongol 2) 12\'s","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("50","50","Pencil Sharpener (Heavy Duty)","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("51","51","Permanent Marker (Pilot) Black","1.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("52","52","Plastic pail","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("53","53","Puncher (HD)","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("54","54","Push Pin","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("55","55","Record Book (300lvs)","30.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("56","56","Record Book (500lvs)","35.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("57","57","Rubber band (small)","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("58","58","Scissors (Stainless)","12.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("59","59","Scotchbrite sponge","48.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("60","60","Signpen 0.5 Black","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("61","61","Signpen energel black (0.7)","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("62","62","Signpen energel blue (0.7)","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("63","63","Soap (Detergent)","5.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("64","64","Soft Broom","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("65","65","Special Paper Long Cream (180 gsm)","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("66","66","Special Paper Long Cream (90gsm)","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("67","67","Spiral 1\"","15.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("68","68","Sponges wiper with long handle","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("69","69","Staple wire #35","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("70","70","Steel Rack","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("71","71","Sticker paper long matte","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("72","72","Tape (Packing Tape) 3\"","6.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("73","73","Tape (Scotch) 1\"","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("74","74","Tape (Scotch) 2\"","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("75","76","Tape -Double Side 2\"","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("76","77","Tape -Double Sided 1\"","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("77","75","Tape dispenser","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("78","78","Thumbtacks","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("79","79","Tissue paper","100.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:31","","2026-04-13 14:57:31");
INSERT INTO `consumable_release_history_view` VALUES("80","80","Toilet bowl brush","8.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("81","81","Toilet bowl Cleaner(500ml)","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("82","82","Trash Bag (Black)  Large","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("83","83","Trash Bag (Black)  Medium","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("84","84","Trash Bin with Swing Lid Black","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("85","85","USB64 GB","20.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("86","86","Vellum Paper Long (Cream)","3.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("87","87","Vellum Paper Long (White)","4.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("88","88","White board 1/4","2.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("89","89","White Board Marker Black (Pilot)","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("90","90","Yellow paper","12.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");
INSERT INTO `consumable_release_history_view` VALUES("91","91","Zonrox","10.00","0.00","0.00","3","Supply Office","12","OSB","5","Walton","Loneza","Walton Loneza","2026-04-13 14:57:32","","2026-04-13 14:57:32");

-- Table structure for `consumables`
DROP TABLE IF EXISTS `consumables`;
CREATE TABLE `consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `units` varchar(50) NOT NULL DEFAULT 'pieces',
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 10,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `for_office_id` int(11) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `office_id` (`office_id`),
  KEY `idx_consumables_for_office` (`for_office_id`),
  KEY `idx_consumables_office_reorder` (`office_id`,`reorder_level`),
  CONSTRAINT `consumables_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `consumables_ibfk_2` FOREIGN KEY (`for_office_id`) REFERENCES `offices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `consumables`
INSERT INTO `consumables` VALUES("1","Air Freshner (Spray)","3","bottles","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("2","Albatross 50gms","5","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("3","Arch File A4 Blue","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("4","Arch File Long Blue","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("5","Ballpen Black","0","box","60.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 15:25:09","12",NULL);
INSERT INTO `consumables` VALUES("6","Battery AA","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("7","Battery AAA","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("8","Binder Clip 25 mm","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("9","Binder Clip 41 mm","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("10","Bookpaper A4","0","ream","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("11","Bookpaper Long","0","ream","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("12","Brother Ink BT5000 BMCY","0","set","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("13","Brother ink D60 Black","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("14","Brown Envelope Long","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("15","Brown Plastic Envelope Long","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("16","Bulb  Watts (LED) 12 watts","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("17","Canon Ink G1010 CYM","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("18","Canon ink G1010black","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("19","Clear book (long)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("20","Computer Keyboard","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("21","Cork board 60cmx90cm","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("22","Data File Box Long","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("23","Dishwashing Liquid","0","liter","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("24","Doormat Rubberized","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("25","Dust Pan","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("26","Extension wire (10m)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("27","Extension wire (20m)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("28","Fastener long (plastic)","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("29","Fastener plastic small","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("30","Floormop with spinner","0","sets","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("31","Folder Long White","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("32","Frame 8x13","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("33","Gina Cloth","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("34","Glass Cleaner","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("35","Glue 130g","0","bottles","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("36","Highlighter (Yellow)","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("37","HP 336X High Yield","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("38","Insect Repellant (Spray) Big","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("39","Interfolded Tissue Paper","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("40","Long Expanded Folder (Blue)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("41","Long Expanded Folder (Green)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("42","Mailing Envelope Long","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("43","Mouse Pad","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("44","Muriatic acid Liter","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("45","paper clip 28mm","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("46","Paper Clip 33mm","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("47","Paper Clip 50mm","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("48","Paper Puncher","0","pc.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("49","Pencil (Mongol 2) 12\'s","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("50","Pencil Sharpener (Heavy Duty)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("51","Permanent Marker (Pilot) Black","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("52","Plastic pail","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("53","Puncher (HD)","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("54","Push Pin","0","bxs","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("55","Record Book (300lvs)","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("56","Record Book (500lvs)","0","pcs.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("57","Rubber band (small)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("58","Scissors (Stainless)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("59","Scotchbrite sponge","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("60","Signpen 0.5 Black","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("61","Signpen energel black (0.7)","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("62","Signpen energel blue (0.7)","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("63","Soap (Detergent)","0","kl.","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("64","Soft Broom","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("65","Special Paper Long Cream (180 gsm)","0","pack","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("66","Special Paper Long Cream (90gsm)","0","pcks","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("67","Spiral 1\"","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("68","Sponges wiper with long handle","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("69","Staple wire #35","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("70","Steel Rack","0","unit","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("71","Sticker paper long matte","0","pcks","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("72","Tape (Packing Tape) 3\"","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("73","Tape (Scotch) 1\"","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("74","Tape (Scotch) 2\"","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("75","Tape dispenser","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("76","Tape -Double Side 2\"","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("77","Tape -Double Sided 1\"","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("78","Thumbtacks","0","box","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("79","Tissue paper","0","roll","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:31","12",NULL);
INSERT INTO `consumables` VALUES("80","Toilet bowl brush","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("81","Toilet bowl Cleaner(500ml)","0","btls","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("82","Trash Bag (Black)  Large","0","pack","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("83","Trash Bag (Black)  Medium","0","pack","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("84","Trash Bin with Swing Lid Black","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("85","USB64 GB","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("86","Vellum Paper Long (Cream)","0","reams","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("87","Vellum Paper Long (White)","0","pack","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("88","White board 1/4","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("89","White Board Marker Black (Pilot)","0","pc","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("90","Yellow paper","0","pad","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("91","Zonrox","0","liter","0.00","10","pcs","3","2026-04-10 10:23:36","2026-04-13 14:57:32","12",NULL);
INSERT INTO `consumables` VALUES("92","Air Freshner (Spray)","3","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("93","Albatross 50gms","5","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("94","Arch File A4 Blue","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("95","Arch File Long Blue","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("96","Ballpen Black","226","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 15:25:09",NULL,NULL);
INSERT INTO `consumables` VALUES("97","Battery AA","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("98","Battery AAA","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("99","Binder Clip 25 mm","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("100","Binder Clip 41 mm","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("101","Bookpaper A4","40","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("102","Bookpaper Long","70","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("103","Brother Ink BT5000 BMCY","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("104","Brother ink D60 Black","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("105","Brown Envelope Long","80","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("106","Brown Plastic Envelope Long","30","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("107","Bulb  Watts (LED) 12 watts","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("108","Canon Ink G1010 CYM","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("109","Canon ink G1010black","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("110","Clear book (long)","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("111","Computer Keyboard","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("112","Cork board 60cmx90cm","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("113","Data File Box Long","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("114","Dishwashing Liquid","12","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("115","Doormat Rubberized","6","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("116","Dust Pan","6","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("117","Extension wire (10m)","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("118","Extension wire (20m)","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("119","Fastener long (plastic)","5","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("120","Fastener plastic small","5","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("121","Floormop with spinner","3","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("122","Folder Long White","100","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("123","Frame 8x13","110","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("124","Gina Cloth","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("125","Glass Cleaner","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("126","Glue 130g","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("127","Highlighter (Yellow)","5","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("128","HP 336X High Yield","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("129","Insect Repellant (Spray) Big","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("130","Interfolded Tissue Paper","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("131","Long Expanded Folder (Blue)","60","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("132","Long Expanded Folder (Green)","200","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("133","Mailing Envelope Long","3","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("134","Mouse Pad","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("135","Muriatic acid Liter","3","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("136","paper clip 28mm","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("137","Paper Clip 33mm","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("138","Paper Clip 50mm","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("139","Paper Puncher","1","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("140","Pencil (Mongol 2) 12\'s","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("141","Pencil Sharpener (Heavy Duty)","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("142","Permanent Marker (Pilot) Black","1","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("143","Plastic pail","6","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("144","Puncher (HD)","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("145","Push Pin","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("146","Record Book (300lvs)","30","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("147","Record Book (500lvs)","35","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("148","Rubber band (small)","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("149","Scissors (Stainless)","12","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("150","Scotchbrite sponge","48","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("151","Signpen 0.5 Black","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("152","Signpen energel black (0.7)","6","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("153","Signpen energel blue (0.7)","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("154","Soap (Detergent)","5","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("155","Soft Broom","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("156","Special Paper Long Cream (180 gsm)","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("157","Special Paper Long Cream (90gsm)","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("158","Spiral 1\"","15","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("159","Sponges wiper with long handle","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("160","Staple wire #35","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("161","Steel Rack","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("162","Sticker paper long matte","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("163","Tape (Packing Tape) 3\"","6","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("164","Tape (Scotch) 1\"","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("165","Tape (Scotch) 2\"","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("166","Tape -Double Side 2\"","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("167","Tape -Double Sided 1\"","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("168","Tape dispenser","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("169","Thumbtacks","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("170","Tissue paper","100","pieces","0.00","10","pcs","12","2026-04-13 14:57:31","2026-04-13 14:57:31",NULL,NULL);
INSERT INTO `consumables` VALUES("171","Toilet bowl brush","8","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("172","Toilet bowl Cleaner(500ml)","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("173","Trash Bag (Black)  Large","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("174","Trash Bag (Black)  Medium","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("175","Trash Bin with Swing Lid Black","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("176","USB64 GB","20","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("177","Vellum Paper Long (Cream)","3","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("178","Vellum Paper Long (White)","4","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("179","White board 1/4","2","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("180","White Board Marker Black (Pilot)","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("181","Yellow paper","12","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("182","Zonrox","10","pieces","0.00","10","pcs","12","2026-04-13 14:57:32","2026-04-13 14:57:32",NULL,NULL);
INSERT INTO `consumables` VALUES("183","Ballpen Black","4","boxes","75.00","10","pcs","3","2026-04-13 15:01:03","2026-04-13 15:24:08","3","J&F suppliers");

-- Table structure for `consume_history`
DROP TABLE IF EXISTS `consume_history`;
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
  KEY `idx_consumable_date` (`consumable_id`,`consumed_at`),
  KEY `idx_office_date` (`office_id`,`consumed_at`),
  KEY `idx_user_date` (`user_id`,`consumed_at`),
  CONSTRAINT `fk_consume_history_consumable` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consume_history_office` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consume_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `consume_history`

-- Table structure for `data_integrity_checks`
DROP TABLE IF EXISTS `data_integrity_checks`;
CREATE TABLE `data_integrity_checks` (
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
  KEY `idx_office_status` (`office_id`,`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_table_record` (`table_name`,`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Data integrity and discrepancy tracking';

-- Dumping data for `data_integrity_checks`

-- Table structure for `document_references`
DROP TABLE IF EXISTS `document_references`;
CREATE TABLE `document_references` (
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
  UNIQUE KEY `unique_document` (`document_type`,`document_number`),
  KEY `idx_office_document` (`office_id`,`document_type`),
  KEY `idx_document_date` (`document_date`),
  KEY `idx_document_refs_office_type` (`office_id`,`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Document reference numbers for LGU compliance';

-- Dumping data for `document_references`

-- Table structure for `employees`
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_no` varchar(20) DEFAULT NULL,
  `firstname` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `designation` text DEFAULT NULL,
  `employment_status` enum('permanent','contractual','job_order','resigned','retired') DEFAULT 'permanent',
  `clearance_status` enum('cleared','uncleared') DEFAULT 'uncleared',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_no` (`employee_no`),
  KEY `office_id` (`office_id`),
  KEY `idx_employees_employment_status` (`employment_status`),
  KEY `idx_employees_clearance_status` (`clearance_status`),
  KEY `idx_employees_employee_no` (`employee_no`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `employees`
INSERT INTO `employees` VALUES("1","EMP-2026-004","Liam","James","Walker","l.walker@example.com","555-0104",NULL,"1","Manager","Operations","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("2","EMP-2026-005","Noah","Alexander","Do","n.do@example.com","555-0105",NULL,"2","Developer","Backend","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("3","EMP-2026-006","Oliver","William","Young","o.young@example.com","555-0106",NULL,"3","Analyst","Finance","contractual","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("4","EMP-2026-007","Elijah","Benjamin","King","e.king@example.com","555-0107",NULL,"1","Specialist","HR","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("5","EMP-2026-008","James","Lucas","Wright","j.wright@example.com","555-0108",NULL,"2","Lead","QA","job_order","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("6","EMP-2026-009","William","Henry","Lopez","w.lopez@example.com","555-0109",NULL,"4","Consultant","IT","contractual","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("7","EMP-2026-010","Benjamin","Mason","Hill","b.hill@example.com","555-0110",NULL,"3","Associate","Marketing","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("8","EMP-2026-011","Lucas","Ethan","Scott","l.scott@example.com","555-0111",NULL,"2","Architect","Solutions","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("9","EMP-2026-012","Henry","Michael","Green","h.green@example.com","555-0112",NULL,"1","Director","Sales","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("10","EMP-2026-013","Alexander","Graham","Adams","a.adams@example.com","555-0113","","5","Coordinator","[\"Full Stack Developer\",\"Software developer\"]","job_order","","2026-04-07 10:13:21","2026-04-07 10:56:41");
INSERT INTO `employees` VALUES("11","EMP-2026-014","Emma","Rose","Baker","e.baker@example.com","555-0114",NULL,"2","Engineer","Cloud","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("12","EMP-2026-015","Olivia","Grace","Gonzalez","o.gonzalez@example.com","555-0115",NULL,"3","Designer","UI/UX","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("13","EMP-2026-016","Ava","Marie","Nelson","a.nelson@example.com","555-0116",NULL,"4","Manager","Product","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("14","EMP-2026-017","Isabella","Ann","Carter","i.carter@example.com","555-0117",NULL,"1","Analyst","Security","resigned","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("15","EMP-2026-018","Sophia","Elizabeth","Mitchell","s.mitchell@example.com","555-0118",NULL,"2","Developer","Mobile","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("16","EMP-2026-019","Mia","Lynn","Perez","m.perez@example.com","555-0119",NULL,"3","Writer","Content","contractual","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("17","EMP-2026-020","Charlotte","Jane","Roberts","c.roberts@example.com","555-0120",NULL,"1","Lead","Customer Success","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("18","EMP-2026-021","Amelia","Claire","Turner","a.turner@example.com","555-0121",NULL,"5","Specialist","Legal","job_order","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("19","EMP-2026-022","Harper","Sloane","Phillips","h.phillips@example.com","555-0122",NULL,"2","Admin","Database","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("20","EMP-2026-023","Evelyn","Paige","Campbell","e.campbell@example.com","555-0123",NULL,"4","Scrum Master","Agile","permanent","uncleared","2026-04-07 10:13:21","2026-04-07 14:31:43");
INSERT INTO `employees` VALUES("21","EMP-2026-024","Jack","Thomas","Parker","j.parker@example.com","555-0124",NULL,"1","Executive","Account","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("22","EMP-2026-025","Jackson","Ryan","Evans","j.evans@example.com","555-0125",NULL,"2","Tester","Automation","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("23","EMP-2026-026","Sebastian","Cole","Edwards","s.edwards@example.com","555-0126",NULL,"3","Analyst","Business","retired","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("24","EMP-2026-027","Aiden","Finn","Collins","a.collins@example.com","555-0127",NULL,"5","Manager","Support","permanent","uncleared","2026-04-07 10:13:21","2026-04-07 14:38:52");
INSERT INTO `employees` VALUES("25","EMP-2026-028","Matthew","Luke","Stewart","m.stewart@example.com","555-0128",NULL,"1","Clerk","Inventory","job_order","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("26","EMP-2026-029","Samuel","Grant","Morris","s.morris@example.com","555-0129",NULL,"2","Scientist","Data","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("27","EMP-2026-030","David","Alan","Murphy","d.murphy@example.com","555-0130",NULL,"3","Consultant","Strategy","contractual","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("28","EMP-2026-031","Joseph","Caleb","Rivera","j.rivera@example.com","555-0131",NULL,"4","Lead","Frontend","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("29","EMP-2026-032","Carter","Joel","Cook","c.cook@example.com","555-0132",NULL,"1","Manager","Purchasing","permanent","uncleared","2026-04-07 10:13:21","2026-04-07 14:25:44");
INSERT INTO `employees` VALUES("30","EMP-2026-033","Owen","Reid","Rogers","o.rogers@example.com","555-0133",NULL,"2","Engineer","DevOps","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("31","EMP-2026-034","Wyatt","Silas","Morgan","w.morgan@example.com","555-0134",NULL,"3","Associate","Public Relations","job_order","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("32","EMP-2026-035","John","Paul","Peterson","j.peterson@example.com","555-0135",NULL,"5","Chief","Executive","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("33","EMP-2026-036","Leo","Jude","Cooper","l.cooper@example.com","555-0136",NULL,"1","Manager","Facility","resigned","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("34","EMP-2026-037","Luke","Miles","Reed","l.reed@example.com","555-0137",NULL,"2","Technician","Support","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("35","EMP-2026-038","Julian","Beau","Bailey","j.bailey@example.com","555-0138",NULL,"4","Coordinator","Events","contractual","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("36","EMP-2026-039","Isaac","Zane","Bell","i.bell@example.com","555-0139",NULL,"3","Analyst","Risk","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("37","EMP-2026-040","Levi","Axel","Gomez","l.gomez@example.com","555-0140",NULL,"2","Specialist","SEO","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("38","EMP-2026-041","Daniel","Ivan","Kelly","d.kelly@example.com","555-0141",NULL,"1","Manager","Payroll","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("39","EMP-2026-042","Gabriel","Max","Sanders","g.sanders@example.com","555-0142",NULL,"5","Officer","Compliance","job_order","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("40","EMP-2026-043","Anthony","Theo","Price","a.price@example.com","555-0143",NULL,"2","Lead","Security","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("41","EMP-2026-044","Dylan","Otis","Bennett","d.bennett@example.com","555-0144",NULL,"3","Artist","Graphic","permanent","uncleared","2026-04-07 10:13:21","2026-04-13 16:49:13");
INSERT INTO `employees` VALUES("42","EMP-2026-045","Grayson","Leo","Wood","g.wood@example.com","555-0145",NULL,"4","Director","Engineering","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("43","EMP-2026-046","Christopher","Kai","Barnes","c.barnes@example.com","555-0146",NULL,"1","Specialist","Training","resigned","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("44","EMP-2026-047","Joshua","Ezra","Ross","j.ross@example.com","555-0147",NULL,"2","Analyst","Systems","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("45","EMP-2026-048","Nathan","Jace","Henderson","n.henderson@example.com","555-0148",NULL,"3","Manager","Regional","retired","","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("46","EMP-2026-049","Andrew","Gael","Coleman","a.coleman@example.com","555-0149",NULL,"5","Supervisor","Production","job_order","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("47","EMP-2026-050","Thomas","Arlo","Jenkins","t.jenkins@example.com","555-0150",NULL,"4","Lead","R&D","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("48","EMP-2026-051","Charles","Hugo","Perry","c.perry@example.com","555-0151",NULL,"1","Associate","Legal","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("49","EMP-2026-052","Caleb","Felix","Powell","c.powell@example.com","555-0152",NULL,"2","Engineer","Network","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("50","EMP-2026-053","Ryan","Oscar","Long","r.long@example.com","555-0153",NULL,"3","Representative","Sales","permanent","cleared","2026-04-07 10:13:21","2026-04-07 10:53:08");
INSERT INTO `employees` VALUES("51","2026-001-01-011239","Walton","Lisaba","Loneza","wjll2022-2920-98466@bicol-u.edu.ph","9107171456",NULL,"4","Computer Programmer","[\"Full Stack Developer\",\"Web Developer\"]","permanent","uncleared","2026-04-08 09:54:42","2026-04-08 10:45:17");

-- Table structure for `failed_login_attempts`
DROP TABLE IF EXISTS `failed_login_attempts`;
CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`),
  KEY `idx_is_blocked` (`is_blocked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `failed_login_attempts`

-- Table structure for `fiscal_year_settings`
DROP TABLE IF EXISTS `fiscal_year_settings`;
CREATE TABLE `fiscal_year_settings` (
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
  UNIQUE KEY `unique_office_fiscal` (`office_id`,`fiscal_year`),
  KEY `idx_fiscal_dates` (`start_date`,`end_date`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Fiscal year configuration per office';

-- Dumping data for `fiscal_year_settings`
INSERT INTO `fiscal_year_settings` VALUES("1","1","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("2","2","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("3","3","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("4","4","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("5","5","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("6","6","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("7","11","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("8","12","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");
INSERT INTO `fiscal_year_settings` VALUES("9","13","2026","2026-01-01","2026-12-31","1","1","2026-03-30 15:59:53","2026-03-30 15:59:53");

-- Table structure for `forms`
DROP TABLE IF EXISTS `forms`;
CREATE TABLE `forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_code` varchar(50) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `form_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `header_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_code` (`form_code`),
  UNIQUE KEY `uc_forms_code` (`code`),
  KEY `idx_form_code` (`form_code`),
  KEY `idx_status` (`status`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_code` (`code`),
  CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `forms_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for `forms`
INSERT INTO `forms` VALUES("1","PAR","07","Property Acknowledgement Receipt","Form for acknowledging receipt of government property","1773111297_Screenshot 2026-03-10 105440.png","active","1","1","2026-01-06 18:17:58","2026-03-10 10:54:57");
INSERT INTO `forms` VALUES("2","ICS","04","Inventory Custodian Slip","Form for transferring accountability of property","1767703470_Screenshot 2026-01-06 194414.png","active","1","1","2026-01-06 18:17:58","2026-02-14 00:10:20");
INSERT INTO `forms` VALUES("3","RIS","03","Requisition and Issue Slip","Form for requesting and issuing supplies","1767705532_RIS HEADER.png","active","1","1","2026-01-06 18:17:58","2026-02-13 23:29:04");
INSERT INTO `forms` VALUES("6","PTR","9","Property Transfer Receipt","For transferring assets on person accountable.","1773111417_Screenshot 2026-03-10 105646.png","active","1","1","2026-01-06 18:23:41","2026-03-10 10:56:57");
INSERT INTO `forms` VALUES("7","IIRUP","05","Inventory and Inspection Report of Unserviceable Property","for dropping unserviceable items from the inventory records and determines how they will be disposed","1773111175_Screenshot 2026-03-10 105233.png","active","1","1","2026-01-06 18:50:01","2026-03-10 10:52:55");

-- Table structure for `fuel_in`
DROP TABLE IF EXISTS `fuel_in`;
CREATE TABLE `fuel_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_time` datetime NOT NULL DEFAULT current_timestamp(),
  `fuel_type` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `delivery_receipt` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `received_by` blob DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fuel_in_date` (`date_time`),
  KEY `idx_fuel_in_type` (`fuel_type`),
  CONSTRAINT `fuel_in_ibfk_1` FOREIGN KEY (`fuel_type`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `fuel_in`

-- Table structure for `fuel_out`
DROP TABLE IF EXISTS `fuel_out`;
CREATE TABLE `fuel_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fo_date` date NOT NULL,
  `fo_time_in` time NOT NULL,
  `fo_fuel_no` varchar(20) DEFAULT NULL,
  `fo_plate_no` varchar(20) DEFAULT NULL,
  `fo_request` varchar(200) DEFAULT NULL,
  `fo_fuel_type` int(11) NOT NULL,
  `fo_liters` decimal(10,2) NOT NULL,
  `fo_vehicle_type` varchar(50) DEFAULT NULL,
  `fo_receiver` varchar(100) DEFAULT NULL,
  `fo_time_out` time DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fuel_out_date` (`fo_date`),
  KEY `idx_fuel_out_type` (`fo_fuel_type`),
  CONSTRAINT `fuel_out_ibfk_1` FOREIGN KEY (`fo_fuel_type`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `fuel_out`

-- Table structure for `fuel_types`
DROP TABLE IF EXISTS `fuel_types`;
CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fuel_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for `fuel_types`
INSERT INTO `fuel_types` VALUES("1","Diesel","1","2026-02-07 10:41:49");
INSERT INTO `fuel_types` VALUES("2","Gasoline","1","2026-02-07 10:41:49");

-- Table structure for `fund_allocation_summary`
DROP TABLE IF EXISTS `fund_allocation_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fund_allocation_summary` AS select `fa`.`id` AS `id`,`fa`.`fund_id` AS `fund_id`,`fa`.`office_id` AS `office_id`,`o`.`office_name` AS `office_name`,`f`.`fund_code` AS `fund_code`,`f`.`fund_name` AS `fund_name`,`f`.`fund_cluster` AS `fund_cluster`,`fa`.`allocated_amount` AS `allocated_amount`,`fa`.`utilized_amount` AS `utilized_amount`,`fa`.`remaining_balance` AS `remaining_balance`,`fa`.`allocation_date` AS `allocation_date`,`fa`.`status` AS `status`,round(`fa`.`utilized_amount` / `fa`.`allocated_amount` * 100,2) AS `utilization_percentage`,`fa`.`created_at` AS `created_at`,`fa`.`updated_at` AS `updated_at` from ((`fund_allocations` `fa` join `funds` `f` on(`fa`.`fund_id` = `f`.`id`)) join `offices` `o` on(`fa`.`office_id` = `o`.`id`));

