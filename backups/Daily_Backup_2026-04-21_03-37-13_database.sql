-- MariaDB dump 10.19  Distrib 10.6.15-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pims
-- ------------------------------------------------------
-- Server version	10.6.15-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `asset_buildings`
--

DROP TABLE IF EXISTS `asset_buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_buildings`
--

LOCK TABLES `asset_buildings` WRITE;
/*!40000 ALTER TABLE `asset_buildings` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_buildings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_categories`
--

LOCK TABLES `asset_categories` WRITE;
/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
INSERT INTO `asset_categories` VALUES (1,'FF','07-010','Furniture & Fixture',10.00,7,'active','2026-01-06 06:08:57','2026-03-11 06:25:00',1,1),(2,'ITS','05-030','Information, Communication and Technology Equipment',33.33,3,'active','2026-01-06 06:08:57','2026-03-11 06:19:36',1,1),(4,'MACH','05-010','Machinery & Equipment',15.00,10,'active','2026-01-06 06:08:57','2026-03-11 06:17:58',1,1),(6,'LND','01-010','Land',0.00,0,'active','2026-01-06 06:08:57','2026-03-11 05:46:47',1,1),(7,'SW','060','Software',33.33,3,'active','2026-01-06 06:08:57','2026-02-17 05:39:45',1,1),(8,'OEQ','05-020','Office Equipment',20.00,5,'active','2026-01-06 06:08:57','2026-03-11 06:18:39',1,1),(9,'AFFE','05-040','Agricultural, Fishery, and Forestry Equipment',0.46,3,'active','2026-03-05 05:48:00','2026-04-20 02:13:33',1,1),(10,'COME','05-070','Communication Equipment',0.00,0,'active','2026-03-05 05:48:45','2026-03-11 05:59:54',1,1),(11,'CONSHE','05-080','Construction and Heavy Equipment',0.00,0,'active','2026-03-05 05:49:17','2026-03-11 06:21:25',1,1),(12,'MSE','05-100','Military, Police and Security Equipment',0.00,0,'active','2026-03-05 05:49:53','2026-03-11 06:22:50',1,1),(13,'DRRM','05-090','Disaster Risk Reduction Management Equipment',0.00,0,'active','2026-03-05 05:50:33','2026-03-11 06:22:33',1,1),(14,'TSE','05-140','Technical and Scientific Equipment',0.00,0,'active','2026-03-05 05:51:04','2026-03-11 06:23:37',1,1),(15,'SPE','140','Sports Equipment',0.00,0,'active','2026-03-05 05:51:48','2026-03-05 05:51:48',1,NULL),(16,'OME','05-990','Other Machinery and Equipment',0.00,0,'active','2026-03-05 05:53:11','2026-03-11 06:24:12',1,1),(17,'SEA','03-070','Sea Port System',0.00,0,'active','2026-03-05 05:54:07','2026-03-11 06:27:58',1,1),(18,'WC','06-040','Water Craft',0.00,0,'active','2026-03-05 05:55:04','2026-03-11 06:10:53',1,1),(19,'PTR','180','Plants & Trees',0.00,0,'active','2026-03-05 05:55:26','2026-03-05 05:55:26',1,NULL),(20,'PPM','190','Park, Plaza & Mun.',0.00,0,'active','2026-03-05 05:55:56','2026-03-05 05:55:56',1,NULL),(21,'MEDEQ','05-110','Medical/Hospital Equipment',0.00,0,'active','2026-03-05 05:56:20','2026-03-11 06:29:04',1,1),(22,'POWER SUPPLY','03-051','Power Supply System',0.00,0,'active','2026-03-05 05:57:00','2026-03-11 06:29:41',1,1),(23,'Land Imp','02-990','',0.00,0,'active','2026-03-11 06:02:18','2026-03-11 06:02:18',1,NULL),(24,'RN','03-010','Road Network',0.00,0,'active','2026-03-11 06:05:13','2026-03-11 06:05:13',1,NULL),(25,'WS','03-040','Water System',0.00,0,'active','2026-03-11 06:11:24','2026-03-11 06:11:24',1,NULL),(26,'OInfra','03-990','Other Infrastructure Assets',0.00,0,'active','2026-03-11 06:12:46','2026-03-11 06:12:57',1,1),(27,'Buildings','04-010','Office Buildings',0.00,0,'active','2026-03-11 06:13:53','2026-03-11 06:13:53',1,NULL),(28,'School Bldg','04-020','School Buildings',0.00,0,'active','2026-03-11 06:14:23','2026-03-11 06:14:23',1,NULL),(29,'HHC','04-030','Hospitals and Health Centers',0.00,0,'active','2026-03-11 06:15:11','2026-03-11 06:15:11',1,NULL),(30,'MKT','04-040','Market',0.00,0,'active','2026-03-11 06:15:42','2026-03-11 06:15:42',1,NULL),(31,'SLH','04-050','Slaughterhouse',0.00,0,'active','2026-03-11 06:16:13','2026-03-11 06:16:13',1,NULL),(32,'Ostruct','04-990','Other Structures',0.00,0,'active','2026-03-11 06:16:44','2026-03-11 06:16:44',1,NULL),(34,'SE','05-130','Sports Equipment',0.00,0,'active','2026-03-11 06:23:20','2026-03-11 06:23:20',1,NULL),(35,'MV','06-010','Motor Vehicles',0.00,0,'active','2026-03-11 06:24:36','2026-03-11 06:24:36',1,NULL),(36,'PP&MUN','03-090','PARK, PLAZAS & MONUMENTS',0.00,0,'active','2026-03-11 06:27:20','2026-03-11 06:27:20',1,NULL),(37,'P&T','01-020','PLANTS & TREES',0.00,0,'active','2026-03-11 06:28:34','2026-03-11 06:28:34',1,NULL);
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `asset_category_tables`
--

DROP TABLE IF EXISTS `asset_category_tables`;
/*!50001 DROP VIEW IF EXISTS `asset_category_tables`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `asset_category_tables` AS SELECT
 1 AS `category_id`,
  1 AS `category_name`,
  1 AS `category_code`,
  1 AS `specific_table_name` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `asset_computers`
--

DROP TABLE IF EXISTS `asset_computers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_computers`
--

LOCK TABLES `asset_computers` WRITE;
/*!40000 ALTER TABLE `asset_computers` DISABLE KEYS */;
INSERT INTO `asset_computers` VALUES (1,1,'Intel® Core™ i5-13420H (Up to 4.6GHz, 8 Cores)','','ssd','512GB M.2 NVMe™ PCIe','OptiPlex 5090 SFF','','Windows Server 2022 (Standard Edition)',NULL,NULL,'SGH3420XYZ',NULL,NULL,NULL,NULL,'good',NULL,NULL,NULL,'2026-04-07 06:25:45','2026-04-07 06:25:45',5,NULL),(2,2,'AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)','','ssd','512GB M.2 NVMe™ PCIe','AMD RYZEN 7','IRIS X','Windows Server 2022 (Standard Edition)',NULL,NULL,'SGH3420XYZ',NULL,NULL,NULL,NULL,'good',NULL,NULL,NULL,'2026-04-07 06:31:44','2026-04-07 06:31:44',5,NULL),(3,3,'AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)','','hdd','512GB M.2 NVMe™ PCIe','AMD Ryzen 7 5800H','IRIS X','Windows® 11 Home',NULL,NULL,'R4N0CV098765',NULL,NULL,NULL,NULL,'good',NULL,NULL,NULL,'2026-04-07 06:38:53','2026-04-07 06:38:53',5,NULL),(4,28,'Apple M3 Chip (8-core CPU, 10-core GPU)','','ssd','','QN90C','IRIS X','Linux',NULL,NULL,'SGH3420XYZ',NULL,NULL,NULL,NULL,'good',NULL,NULL,NULL,'2026-04-13 08:49:14','2026-04-13 08:49:14',5,NULL);
/*!40000 ALTER TABLE `asset_computers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_desktop_computers`
--

DROP TABLE IF EXISTS `asset_desktop_computers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_desktop_computers`
--

LOCK TABLES `asset_desktop_computers` WRITE;
/*!40000 ALTER TABLE `asset_desktop_computers` DISABLE KEYS */;
INSERT INTO `asset_desktop_computers` VALUES (1,1,NULL,NULL,NULL,'serviceable',NULL,NULL,NULL,'serviceable',5,NULL,'2026-04-07 06:25:46','2026-04-07 06:25:46',NULL,NULL),(2,2,NULL,NULL,NULL,'serviceable',NULL,NULL,NULL,'serviceable',5,NULL,'2026-04-07 06:31:44','2026-04-07 06:31:44',NULL,NULL),(3,3,NULL,NULL,NULL,'serviceable',NULL,NULL,NULL,'serviceable',5,NULL,'2026-04-07 06:38:53','2026-04-07 06:38:53',NULL,NULL),(4,28,NULL,NULL,NULL,'serviceable',NULL,NULL,NULL,'serviceable',5,NULL,'2026-04-13 08:49:14','2026-04-13 08:49:14',NULL,NULL);
/*!40000 ALTER TABLE `asset_desktop_computers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_furniture`
--

DROP TABLE IF EXISTS `asset_furniture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_furniture`
--

LOCK TABLES `asset_furniture` WRITE;
/*!40000 ALTER TABLE `asset_furniture` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_furniture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_item_history`
--

DROP TABLE IF EXISTS `asset_item_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_item_history`
--

LOCK TABLES `asset_item_history` WRITE;
/*!40000 ALTER TABLE `asset_item_history` DISABLE KEYS */;
INSERT INTO `asset_item_history` VALUES (1,1,'PAR Created','Created via PAR form OMMP-2026-04-0001 - Entity: LGU PILAR, Quantity: 2, Unit: units, Amount: ₱56,000.00',NULL,NULL,5,'2026-04-07 03:08:45'),(2,2,'PAR Created','Created via PAR form OMMP-2026-04-0001 - Entity: LGU PILAR, Quantity: 2, Unit: units, Amount: ₱56,000.00',NULL,NULL,5,'2026-04-07 03:08:45'),(4,1,'QR Code Generated','QR code generated for asset item: qr_asset_1_1775543145.png',NULL,NULL,5,'2026-04-07 06:25:45'),(5,1,'Computer Specs Updated','Computer Equipment specs saved - Processor: Intel® Core™ i5-13420H (Up to 4.6GHz, 8 Cores), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe ssd, Model: OptiPlex 5090 SFF, Graphics: Not specified, OS: Windows Server 2022 (Standard Edition), Serial: SGH3420XYZ, Brand: Not specified, Warranty: Not specified',NULL,NULL,5,'2026-04-07 06:25:45'),(6,1,'Desktop Computer Specs Updated','Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable',NULL,NULL,5,'2026-04-07 06:25:46'),(7,1,'Tag Created','Created tag for item ID 1: Property No: 2026-07-05-030-0101-01, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-032 (Carter Cook), Images: asset_1_0_1775543144.webp',NULL,NULL,5,'2026-04-07 06:25:46'),(8,2,'QR Code Generated','QR code generated for asset item: qr_asset_2_1775543504.png',NULL,NULL,5,'2026-04-07 06:31:44'),(9,2,'Computer Specs Updated','Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe ssd, Model: AMD RYZEN 7, Graphics: IRIS X, OS: Windows Server 2022 (Standard Edition), Serial: SGH3420XYZ, Brand: Lenovo, Warranty: 2 years',NULL,NULL,5,'2026-04-07 06:31:44'),(10,2,'Desktop Computer Specs Updated','Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable',NULL,NULL,5,'2026-04-07 06:31:44'),(11,2,'Tag Created','Created tag for item ID 2: Property No: 2026-07-05-030-0102-01, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-023 (Evelyn Campbell), Images: asset_2_0_1775543503.avif',NULL,NULL,5,'2026-04-07 06:31:44'),(12,3,'QR Code Generated','QR code generated for asset item: qr_asset_3_1775543933.png',NULL,NULL,5,'2026-04-07 06:38:53'),(13,3,'Computer Specs Updated','Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: Not specified, Storage: 512GB M.2 NVMe™ PCIe hdd, Model: AMD Ryzen 7 5800H, Graphics: IRIS X, OS: Windows® 11 Home, Serial: R4N0CV098765, Brand: Not specified, Warranty: Not specified',NULL,NULL,5,'2026-04-07 06:38:53'),(14,3,'Desktop Computer Specs Updated','Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable',NULL,NULL,5,'2026-04-07 06:38:53'),(15,3,'Tag Created','Created tag for item ID 3: Property No: 2026-04-05-030-0101-02, Inventory Tag: , Date Counted: 2026-04-07, Category: 05-030 - ITS, Person Accountable: EMP-2026-027 (Aiden Collins), Images: asset_3_0_1775543932.png',NULL,NULL,5,'2026-04-07 06:38:53'),(16,6,'QR Code Generated','QR code generated for asset item: qr_asset_6_1775616318.png',NULL,NULL,5,'2026-04-08 02:45:18'),(17,6,'Tag Created','Created tag for item ID 6: Property No: 2026-07-06-010-0101-07, Inventory Tag: , Date Counted: 2026-04-08, Category: 06-010 - MV, Person Accountable: 2026-001-01-011239 (Walton Loneza), Images: asset_6_0_1775616317.webp',NULL,NULL,5,'2026-04-08 02:45:19'),(19,7,'QR Code Generated','QR code generated for asset item: qr_asset_7_1775620142.png',NULL,NULL,5,'2026-04-08 03:49:02'),(20,7,'Tag Created','Created tag for item ID 7: Property No: 2026-07-06-010-0102-07, Inventory Tag: , Date Counted: 2026-04-08, Category: 06-010 - MV, Person Accountable: 2026-001-01-011239 (Walton Loneza), Images: asset_7_0_1775620141.webp',NULL,NULL,5,'2026-04-08 03:49:02'),(21,11,'QR Code Generated','QR code generated for asset item: qr_asset_11_1775735815.png',NULL,NULL,5,'2026-04-09 11:56:55'),(22,11,'Tag Created','Created tag for item ID 11: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: EMP-2026-023 (Evelyn Campbell), No images',NULL,NULL,5,'2026-04-09 11:56:55'),(23,12,'QR Code Generated','QR code generated for asset item: qr_asset_12_1775736739.png',NULL,NULL,5,'2026-04-09 12:12:19'),(24,12,'Tag Created','Created tag for item ID 12: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:12:19'),(25,13,'QR Code Generated','QR code generated for asset item: qr_asset_13_1775737172.png',NULL,NULL,5,'2026-04-09 12:19:32'),(26,13,'Tag Created','Created tag for item ID 13: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:19:32'),(27,14,'QR Code Generated','QR code generated for asset item: qr_asset_14_1775737720.png',NULL,NULL,5,'2026-04-09 12:28:40'),(28,14,'Tag Created','Created tag for item ID 14: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:28:40'),(29,15,'QR Code Generated','QR code generated for asset item: qr_asset_15_1775738302.png',NULL,NULL,5,'2026-04-09 12:38:22'),(30,15,'Tag Created','Created tag for item ID 15: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:38:22'),(31,16,'QR Code Generated','QR code generated for asset item: qr_asset_16_1775738600.png',NULL,NULL,5,'2026-04-09 12:43:20'),(32,16,'Tag Created','Created tag for item ID 16: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:43:20'),(33,17,'QR Code Generated','QR code generated for asset item: qr_asset_17_1775738766.png',NULL,NULL,5,'2026-04-09 12:46:06'),(34,17,'Tag Created','Created tag for item ID 17: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-09, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-09 12:46:06'),(35,24,'QR Code Generated','QR code generated for asset item: qr_asset_24_1775782938.png',NULL,NULL,5,'2026-04-10 01:02:18'),(36,24,'Tag Created','Created tag for item ID 24: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-10 01:02:18'),(37,18,'QR Code Generated','QR code generated for asset item: qr_asset_18_1775783025.png',NULL,NULL,5,'2026-04-10 01:03:45'),(38,18,'Tag Created','Created tag for item ID 18: Property No: 2026-07-06-010-0101-07, Inventory Tag: , Date Counted: 2026-04-10, Category: 06-010 - MV, Person Accountable: EMP-2026-023 (Evelyn Campbell), No images',NULL,NULL,5,'2026-04-10 01:03:45'),(39,23,'QR Code Generated','QR code generated for asset item: qr_asset_23_1775784289.png',NULL,NULL,5,'2026-04-10 01:24:49'),(40,23,'Tag Created','Created tag for item ID 23: Property No: 2026-07-01-010-0101-22, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-10 01:24:49'),(41,25,'QR Code Generated','QR code generated for asset item: qr_asset_25_1775784864.png',NULL,NULL,5,'2026-04-10 01:34:24'),(42,25,'Tag Created','Created tag for item ID 25: Property No: 2026-07-01-010-0101-01, Inventory Tag: , Date Counted: 2026-04-10, Category: 01-010 - LND, Person Accountable: Unknown (Unknown), No images',NULL,NULL,5,'2026-04-10 01:34:24'),(43,26,'ICS Created','Created via ICS form OMMI-2026-I-01 - Entity: OSB, Item No: 2026-04-07-010-0101-01\r\n2026-04-07-010-0102-01, Quantity: 1, Unit: , Unit Cost: ₱18,500.00',NULL,NULL,5,'2026-04-10 02:11:04'),(44,27,'ICS Created','Created via ICS form OMMI-2026-I-01 - Entity: OSB, Item No: 2026-04-07-010-0101-01\r\n2026-04-07-010-0102-01, Quantity: 1, Unit: , Unit Cost: ₱18,500.00',NULL,NULL,5,'2026-04-10 02:11:04'),(45,3,'status_change','Status changed via IIRUP Form: IIRUP-2026-5796','serviceable','unserviceable',5,'2026-04-10 02:27:00'),(46,2,'ITR Transfer','Transferred via ITR form ITR-0010 - From: Evelyn Campbell, To: Aiden Collins, Transfer Type: Reassignment, End User: Alexander G. Adams/OVM','Employee ID: 20 (Evelyn Campbell)','Employee ID: 24 (Aiden Collins)',5,'2026-04-10 02:38:32'),(47,28,'QR Code Generated','QR code generated for asset item: qr_asset_28_1776070154.png',NULL,NULL,5,'2026-04-13 08:49:14'),(48,28,'Computer Specs Updated','Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: Not specified, Storage: Not specified ssd, Model: QN90C, Graphics: IRIS X, OS: Linux, Serial: SGH3420XYZ, Brand: Lenovo, Warranty: 2 years',NULL,NULL,5,'2026-04-13 08:49:14'),(49,28,'Desktop Computer Specs Updated','Desktop Computer specs saved - Monitor: Not specified Not specified (No serial) - Status: serviceable, UPS: Not specified Not specified (No serial) - Status: serviceable',NULL,NULL,5,'2026-04-13 08:49:14'),(50,28,'Tag Created','Created tag for item ID 28: Property No: 2026-07-05-030-0101-02, Inventory Tag: , Date Counted: 2026-04-13, Category: 05-030 - ITS, Person Accountable: EMP-2026-044 (Dylan Bennett), Images: asset_28_0_1776070153.webp, asset_28_1_1776070153.avif',NULL,NULL,5,'2026-04-13 08:49:14');
/*!40000 ALTER TABLE `asset_item_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_item_improvements`
--

DROP TABLE IF EXISTS `asset_item_improvements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_item_improvements`
--

LOCK TABLES `asset_item_improvements` WRITE;
/*!40000 ALTER TABLE `asset_item_improvements` DISABLE KEYS */;
INSERT INTO `asset_item_improvements` VALUES (1,1,'2026-04-09','Added Graphics Card',1,60000.00,'SERVICEABLE','2026-04-09 03:11:00'),(2,23,'2026-04-10','Brgy. Health Center Lot',1,850000.00,'serviceable','2026-04-10 01:46:37'),(3,23,'2026-04-10','Added building',1,1200000.00,'','2026-04-10 01:46:37'),(4,23,'2026-04-10','Brgy. Health Center Lot',1,850000.00,'serviceable','2026-04-10 01:49:10'),(5,23,'2026-04-10','Added building',1,1200000.00,'','2026-04-10 01:49:10'),(6,23,'2026-04-10','Brgy. Health Center Lot',1,1200000.00,'','2026-04-10 01:52:18'),(7,23,'2026-04-10','Added building',1,2000000.00,'serviceable','2026-04-10 01:52:18');
/*!40000 ALTER TABLE `asset_item_improvements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_items`
--

DROP TABLE IF EXISTS `asset_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_items`
--

LOCK TABLES `asset_items` WRITE;
/*!40000 ALTER TABLE `asset_items` DISABLE KEYS */;
INSERT INTO `asset_items` VALUES (1,1,2,2,29,'Elton John Moises',NULL,NULL,1,'Laptop AMD Ryzen','OptiPlex 5090 SFF','SGH3420XYZ','units',1,'2026-07-05-030-0101-01','OMMP-2026-04-0001',NULL,'2026-04-07','[\"asset_1_0_1775543144.webp\"]',NULL,'qr_asset_1_1775543145.png','serviceable',NULL,NULL,116000.00,'2026-04-07',4,'OMM','2026-04-07 03:08:45','2026-04-14 05:55:19'),(2,1,2,2,24,'Alexander G. Adams/OVM',NULL,NULL,1,'Laptop AMD Ryzen','AMD RYZEN 7','SGH3420XYZ','units',1,'2026-07-05-030-0102-01','OMMP-2026-04-0001',NULL,'2026-04-07','[\"asset_2_0_1775543503.avif\"]',NULL,'qr_asset_2_1775543504.png','serviceable',NULL,NULL,56000.00,'2026-04-07',4,'OMM','2026-04-07 03:08:45','2026-04-11 11:54:38'),(3,2,2,2,24,'Elton John Moises',NULL,NULL,NULL,'ASUS Vivobook 16','AMD Ryzen 7 5800H','R4N0CV098765',NULL,1,'2026-04-05-030-0101-02','OVMI2026-0001',NULL,'2026-04-07','[\"asset_3_0_1775543932.png\"]',NULL,'qr_asset_3_1775543933.png','unserviceable',NULL,NULL,42900.00,'2026-01-23',5,'OVM','2026-04-07 06:35:06','2026-04-10 02:27:00'),(4,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASUS Vivobook 16',NULL,NULL,NULL,1,'2026-04-05-030-0102-02',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,42900.00,'2026-01-23',5,NULL,'2026-04-07 06:35:06','2026-04-07 06:35:06'),(5,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ASUS Vivobook 16',NULL,NULL,NULL,1,'2026-04-05-030-0103-02',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,42900.00,'2026-01-23',5,NULL,'2026-04-07 06:35:06','2026-04-07 06:35:06'),(6,3,NULL,35,51,'Walton Loneza',NULL,NULL,NULL,'Isuzu GIGA Dump Truck','CXZ77','ABC-1234 / 6WG1-123456',NULL,1,'2026-07-06-010-0101-07','MotorpoolP-2026-0001',NULL,'2026-04-08','[\"asset_6_0_1775616317.webp\"]',NULL,'qr_asset_6_1775616318.png','serviceable',NULL,NULL,4850000.00,'2026-02-18',14,'Motorpool','2026-04-07 07:04:28','2026-04-08 02:45:18'),(7,3,NULL,35,51,'Elton John Moises',NULL,NULL,NULL,'Isuzu GIGA Dump Truck','CXZ77','ABC-5467/ 6WG1-123456',NULL,0,'2026-07-06-010-0102-07','MotorpoolP-2026-0001',NULL,'2026-04-08','[\"asset_7_0_1775620141.webp\"]',NULL,'qr_asset_7_1775620142.png','borrowed',NULL,NULL,4850000.00,'2026-02-18',14,'Motorpool','2026-04-07 07:04:28','2026-04-10 13:02:56'),(8,4,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Evolis Primacy 2',NULL,NULL,NULL,1,'2026-04-05-030-0301-01',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,48500.00,'2026-04-08',4,NULL,'2026-04-08 01:01:28','2026-04-08 01:01:28'),(9,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Office Table – Wooden',NULL,NULL,NULL,1,'2026-04-07-010-0101-02',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,23999.00,'2026-04-08',5,NULL,'2026-04-08 01:36:05','2026-04-08 01:36:05'),(10,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Office Table – Wooden',NULL,NULL,NULL,1,'2026-04-07-010-0102-02',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,23999.00,'2026-04-08',5,NULL,'2026-04-08 01:36:05','2026-04-08 01:36:05'),(11,6,NULL,6,20,'Elton John Moises',NULL,NULL,NULL,'Public Market Lot',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP2026-0001',NULL,'2026-04-09','NULL',NULL,'qr_asset_11_1775735815.png','serviceable',NULL,NULL,8200000.00,'2026-04-09',4,'OMM','2026-04-09 11:51:48','2026-04-09 11:56:55'),(12,7,NULL,6,0,'',NULL,NULL,NULL,'Proposed Sanitary Landfill',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-0007',NULL,'2026-04-09','NULL',NULL,'qr_asset_12_1775736739.png','serviceable',NULL,NULL,4500000.00,'2026-04-09',4,'OMM','2026-04-09 12:01:09','2026-04-09 12:12:19'),(13,8,NULL,6,0,'',NULL,NULL,NULL,'Municipal Hall Site',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-0007',NULL,'2026-04-09','NULL',NULL,'qr_asset_13_1775737172.png','serviceable',NULL,NULL,15500000.00,'2026-04-09',4,'OMM','2026-04-09 12:13:48','2026-04-09 12:19:32'),(14,9,NULL,6,0,'',NULL,NULL,NULL,'Main Public Market Lot',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-0008',NULL,'2026-04-09','NULL',NULL,'qr_asset_14_1775737720.png','serviceable',NULL,NULL,4500000.00,'2026-04-09',4,'OMM','2026-04-09 12:25:38','2026-04-09 12:28:40'),(15,10,NULL,6,0,'',NULL,NULL,NULL,'Market Extension A',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-0009',NULL,'2026-04-09','NULL',NULL,'qr_asset_15_1775738302.png','serviceable',NULL,NULL,1200000.00,'2026-04-09',4,'OMM','2026-04-09 12:37:42','2026-04-09 12:38:22'),(16,11,NULL,6,0,'',NULL,NULL,NULL,'Parking & Terminal Area',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-00010',NULL,'2026-04-09','NULL',NULL,'qr_asset_16_1775738600.png','serviceable',NULL,NULL,2800000.00,'2026-04-09',4,'OMM','2026-04-09 12:42:51','2026-04-09 12:43:20'),(17,12,NULL,6,0,'',NULL,NULL,NULL,'Municipal Public Market Lot',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-00011',NULL,'2026-04-09','NULL',NULL,'qr_asset_17_1775738766.png','serviceable',NULL,NULL,4500000.00,'2026-04-09',4,'OMM','2026-04-09 12:45:41','2026-04-09 12:46:06'),(18,13,NULL,35,20,'Roberto Cruz',NULL,NULL,NULL,'Hino 500 Compactor','QN90C','SGH3420XYZ',NULL,1,'2026-07-06-010-0101-07','OMMP-2026-00013',NULL,'2026-04-10','NULL',NULL,'qr_asset_18_1775783025.png','serviceable',NULL,NULL,4200000.00,'2026-04-10',14,'Motorpool','2026-04-10 00:57:05','2026-04-10 01:03:45'),(19,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Hino 500 Compactor',NULL,NULL,NULL,1,'2026-07-06-010-0102-07',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,4200000.00,'2026-04-10',14,NULL,'2026-04-10 00:57:05','2026-04-10 00:57:05'),(20,13,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Hino 500 Compactor',NULL,NULL,NULL,1,'2026-07-06-010-0103-07',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,4200000.00,'2026-04-10',14,NULL,'2026-04-10 00:57:05','2026-04-10 00:57:05'),(21,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Toyota Hilux (Service)',NULL,NULL,NULL,1,'2026-07-06-010-0101-07',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,1450000.00,'2026-04-10',14,NULL,'2026-04-10 00:57:53','2026-04-10 00:57:53'),(22,14,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Toyota Hilux (Service)',NULL,NULL,NULL,1,'2026-07-06-010-0102-07',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,1450000.00,'2026-04-10',14,NULL,'2026-04-10 00:57:53','2026-04-10 00:57:53'),(23,15,NULL,6,0,'',NULL,NULL,NULL,'Brgy. Health Center Lot',NULL,NULL,NULL,1,'2026-07-01-010-0101-22','OMMP-2026-00014',NULL,'2026-04-10','NULL',NULL,'qr_asset_23_1775784289.png','serviceable',NULL,NULL,2000000.00,'2026-04-10',11,'OMH','2026-04-10 00:59:29','2026-04-10 01:52:18'),(24,16,NULL,6,0,'',NULL,NULL,NULL,'Municipal Hall Lot',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-00012',NULL,'2026-04-10','NULL',NULL,'qr_asset_24_1775782938.png','serviceable',NULL,NULL,12500000.00,'2026-04-10',4,'OMM','2026-04-10 01:00:29','2026-04-10 01:02:18'),(25,17,NULL,6,0,'',NULL,NULL,NULL,'Evacuation Center Site',NULL,NULL,NULL,1,'2026-07-01-010-0101-01','OMMP-2026-00016',NULL,'2026-04-10','NULL',NULL,'qr_asset_25_1775784864.png','serviceable',NULL,NULL,14000000.00,'2020-02-12',4,'OMM','2026-04-10 01:32:44','2026-04-10 01:34:24'),(26,18,3,NULL,NULL,NULL,1,1,NULL,'Executive Desk',NULL,NULL,'',1,'2026-04-07-010-0101-01',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,18500.00,'2026-04-10',12,NULL,'2026-04-10 02:11:04','2026-04-10 02:11:04'),(27,18,3,NULL,NULL,NULL,1,1,NULL,'Executive Desk',NULL,NULL,'',1,'2026-04-07-010-0102-01',NULL,NULL,NULL,NULL,NULL,NULL,'no_tag',NULL,NULL,18500.00,'2026-04-10',12,NULL,'2026-04-10 02:11:04','2026-04-10 02:11:04'),(28,19,2,2,41,'Elton John Moises',NULL,NULL,NULL,'Lenovo ThinkPad E14','QN90C','SGH3420XYZ',NULL,1,'2026-07-05-030-0101-02','OVMP-2026-0001',NULL,'2026-04-13','[\"asset_28_0_1776070153.webp\",\"asset_28_1_1776070153.avif\"]',NULL,'qr_asset_28_1776070154.png','serviceable',NULL,NULL,57999.00,'2026-04-13',5,'OVM','2026-04-13 08:48:29','2026-04-13 08:49:14');
/*!40000 ALTER TABLE `asset_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_asset_assign_employee` AFTER INSERT ON `asset_items` FOR EACH ROW BEGIN
            IF NEW.employee_id IS NOT NULL THEN
                UPDATE employees 
                SET clearance_status = 'uncleared' 
                WHERE id = NEW.employee_id;
            END IF;
        END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_asset_update_employee` AFTER UPDATE ON `asset_items` FOR EACH ROW BEGIN
            IF NEW.employee_id IS NOT NULL AND (OLD.employee_id IS NULL OR OLD.employee_id != NEW.employee_id) THEN
                UPDATE employees 
                SET clearance_status = 'uncleared' 
                WHERE id = NEW.employee_id;
            END IF;
        END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `asset_land`
--

DROP TABLE IF EXISTS `asset_land`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_land`
--

LOCK TABLES `asset_land` WRITE;
/*!40000 ALTER TABLE `asset_land` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_land` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_land_info`
--

DROP TABLE IF EXISTS `asset_land_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_land_info`
--

LOCK TABLES `asset_land_info` WRITE;
/*!40000 ALTER TABLE `asset_land_info` DISABLE KEYS */;
INSERT INTO `asset_land_info` VALUES (1,25,'Lot 442-B','3,500 sqm','Town Proper','TD-065-2026-001','2026-04-10 06:34:24','2026-04-10 06:34:24',5,NULL);
/*!40000 ALTER TABLE `asset_land_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_machinery`
--

DROP TABLE IF EXISTS `asset_machinery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_machinery`
--

LOCK TABLES `asset_machinery` WRITE;
/*!40000 ALTER TABLE `asset_machinery` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_machinery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_office_equipment`
--

DROP TABLE IF EXISTS `asset_office_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_office_equipment`
--

LOCK TABLES `asset_office_equipment` WRITE;
/*!40000 ALTER TABLE `asset_office_equipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_office_equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_software`
--

DROP TABLE IF EXISTS `asset_software`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_software`
--

LOCK TABLES `asset_software` WRITE;
/*!40000 ALTER TABLE `asset_software` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_software` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_sub_categories`
--

DROP TABLE IF EXISTS `asset_sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_sub_categories`
--

LOCK TABLES `asset_sub_categories` WRITE;
/*!40000 ALTER TABLE `asset_sub_categories` DISABLE KEYS */;
INSERT INTO `asset_sub_categories` VALUES (1,'ID PRINTER','03',2,'active',3,1,1,'2026-02-13 13:23:51','2026-03-11 06:40:08'),(2,'COMPUTER DESKTOP','02',2,'active',4,1,1,'2026-02-13 13:23:51','2026-03-11 06:38:57'),(3,'LAPTOP','01',2,'active',3,1,1,'2026-02-13 13:23:51','2026-03-11 06:38:27'),(11,'CARD PRINTER','04',2,'active',3,1,1,'2026-03-11 06:43:11','2026-03-11 06:48:11'),(12,'NOTEBOOK','05',2,'active',3,1,1,'2026-03-11 06:44:55','2026-03-11 06:48:15'),(13,'ADVERTISING MACHINE KIOSK','06',2,'active',4,1,1,'2026-03-11 06:45:39','2026-03-11 06:48:18'),(14,'SMART TV','07',2,'active',3,1,1,'2026-03-11 06:46:01','2026-03-11 06:48:22'),(18,'NITROGEN TANK','01',9,'active',0,1,1,'2026-03-11 07:14:51','2026-03-11 07:15:06'),(19,'HAND TRACTOR','02',9,'active',0,1,1,'2026-03-11 07:15:26','2026-03-11 07:15:34'),(20,'WHEELS TRACTOR','03',9,'active',0,1,NULL,'2026-03-11 07:22:38','2026-03-11 07:22:38'),(21,'Truck','01',35,'active',10,1,NULL,'2026-04-07 06:58:32','2026-04-07 06:58:32'),(22,'Office Desk','01',1,'active',10,1,NULL,'2026-04-07 06:58:58','2026-04-07 06:58:58'),(23,'Schools','01',27,'active',15,1,NULL,'2026-04-07 06:59:47','2026-04-07 06:59:47'),(24,'Router','01',10,'active',5,1,NULL,'2026-04-07 07:00:03','2026-04-07 07:00:03'),(25,'Excavator','01',11,'active',10,1,NULL,'2026-04-07 07:00:23','2026-04-07 07:00:23'),(26,'Drill','01',4,'active',5,1,NULL,'2026-04-07 07:00:53','2026-04-07 07:00:53'),(27,'Wheel Chair','01',21,'active',5,1,NULL,'2026-04-07 07:01:13','2026-04-07 07:01:13'),(28,'Boat','01',18,'active',15,1,NULL,'2026-04-07 07:01:47','2026-04-07 07:01:47'),(29,'MARKET','01',6,'active',0,1,NULL,'2026-04-09 11:49:30','2026-04-09 11:49:30');
/*!40000 ALTER TABLE `asset_sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_vehicles`
--

DROP TABLE IF EXISTS `asset_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_vehicles`
--

LOCK TABLES `asset_vehicles` WRITE;
/*!40000 ALTER TABLE `asset_vehicles` DISABLE KEYS */;
INSERT INTO `asset_vehicles` VALUES (2,7,'ABC4567','1NR-FE123456','JACNKR770J4567890','White','CXZ77','Isuzu',0,'gasoline','manual',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'good',NULL,'2026-04-08 03:49:02','2026-04-08 03:49:02',5,NULL),(3,18,'ABC-156','1NR-FE123456','JACNKR770J4567890','BLUE','QN90C','Hino',0,'gasoline','manual',NULL,NULL,NULL,NULL,NULL,0,NULL,NULL,'good',NULL,'2026-04-10 01:03:45','2026-04-10 01:03:45',5,NULL);
/*!40000 ALTER TABLE `asset_vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,2,2,'Laptop AMD Ryzen','units',2,28000.00,4,'2026-04-07 03:08:45','2026-04-07 06:31:44'),(2,2,2,'ASUS Vivobook 16','units',3,42900.00,5,'2026-04-07 06:35:06','2026-04-07 06:38:53'),(3,35,NULL,'Isuzu GIGA Dump Truck','units',2,4850000.00,14,'2026-04-07 07:04:28','2026-04-08 03:49:02'),(4,2,1,'Evolis Primacy 2','units',1,48500.00,4,'2026-04-08 01:01:28','2026-04-08 01:01:28'),(5,1,22,'Office Table – Wooden','units',2,23999.00,5,'2026-04-08 01:36:05','2026-04-08 01:36:05'),(6,6,NULL,'Public Market Lot','units',1,8200000.00,4,'2026-04-09 11:51:48','2026-04-09 11:56:55'),(7,6,NULL,'Proposed Sanitary Landfill','lot',1,4500000.00,4,'2026-04-09 12:01:09','2026-04-09 12:12:19'),(8,6,NULL,'Municipal Hall Site','lot',1,15500000.00,4,'2026-04-09 12:13:48','2026-04-09 12:19:32'),(9,6,NULL,'Main Public Market Lot','lot',1,4500000.00,4,'2026-04-09 12:25:38','2026-04-09 12:28:40'),(10,6,NULL,'Market Extension A','lot',1,1200000.00,4,'2026-04-09 12:37:42','2026-04-09 12:38:22'),(11,6,NULL,'Parking & Terminal Area','lot',1,2800000.00,4,'2026-04-09 12:42:51','2026-04-09 12:43:20'),(12,6,NULL,'Municipal Public Market Lot','lot',1,4500000.00,4,'2026-04-09 12:45:41','2026-04-09 12:46:06'),(13,35,NULL,'Hino 500 Compactor','units',3,4200000.00,14,'2026-04-10 00:57:05','2026-04-10 01:03:45'),(14,35,21,'Toyota Hilux (Service)','units',2,1450000.00,14,'2026-04-10 00:57:53','2026-04-10 00:57:53'),(15,6,NULL,'Brgy. Health Center Lot','lot',1,850000.00,11,'2026-04-10 00:59:29','2026-04-10 01:24:49'),(16,6,NULL,'Municipal Hall Lot','lot',1,12500000.00,4,'2026-04-10 01:00:29','2026-04-10 01:02:18'),(17,6,NULL,'Evacuation Center Site','lot',1,14000000.00,4,'2026-04-10 01:32:44','2026-04-10 01:34:24'),(18,1,3,'Executive Desk','',2,18500.00,12,'2026-04-10 02:11:04','2026-04-10 02:11:04'),(19,2,2,'Lenovo ThinkPad E14','units',1,57999.00,5,'2026-04-13 08:48:29','2026-04-13 08:49:14');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_execution_logs`
--

DROP TABLE IF EXISTS `backup_execution_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_execution_logs`
--

LOCK TABLES `backup_execution_logs` WRITE;
/*!40000 ALTER TABLE `backup_execution_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_execution_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backups`
--

DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backups`
--

LOCK TABLES `backups` WRITE;
/*!40000 ALTER TABLE `backups` DISABLE KEYS */;
INSERT INTO `backups` VALUES (8,'Daily Backup','full',1,1,'../backups/Daily Backup_2026-01-05_13-41-33',1,'2026-01-05 12:41:33',0,NULL,NULL,NULL,NULL,NULL),(9,'Daily Backup','full',1,1,'../backups/Daily Backup_2026-01-05_14-05-54',1,'2026-01-05 13:05:54',0,NULL,NULL,NULL,NULL,NULL),(10,'online Backup','full',1,1,'../backups/online Backup_2026-01-05_14-29-41',1,'2026-01-05 13:29:41',1,'0','https://drive.google.com/file/d/1qrf12E9fs98ak_we_UXsTnEyGt4z5pcR/view','completed',NULL,'2026-01-05 14:13:59'),(11,'Daily Backup','full',1,1,'../backups/Daily Backup_2026-01-05_14-46-20',1,'2026-01-05 13:46:21',0,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `borrow_form_submissions`
--

DROP TABLE IF EXISTS `borrow_form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrow_form_submissions`
--

LOCK TABLES `borrow_form_submissions` WRITE;
/*!40000 ALTER TABLE `borrow_form_submissions` DISABLE KEYS */;
INSERT INTO `borrow_form_submissions` VALUES (3,'Walton Loneza','Marifosque','09107171456','2026-04-10',NULL,'Joshua Escano','Elton John Moises','[{\"asset_id\":7,\"quantity\":1,\"remarks\":\"\",\"description\":\"Isuzu GIGA Dump Truck\"}]','returned','2026-04-10 13:02:56','2026-04-11 05:25:06'),(4,'Walton Loneza','Centro Occidental','09107171456','2026-04-11',NULL,'Joshua Escano','Elton John Moises','[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"},{\"asset_item_id\":2,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0102-01\",\"remarks\":\"\",\"category\":\"ITS\"}]','returned','2026-04-11 11:35:32','2026-04-11 11:54:38'),(5,'Walton Loneza','Centro Occidental','09107171456','2026-04-14',NULL,'Joshua Escano','Elton John Moises','[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"}]','returned','2026-04-14 01:41:43','2026-04-14 05:44:38'),(6,'Walton Loneza','Centro Occidental','09107171456','2026-04-14',NULL,'Joshua Escano','Elton John Moises','[{\"asset_item_id\":1,\"description\":\"Laptop AMD Ryzen\",\"property_no\":\"2026-07-05-030-0101-01\",\"remarks\":\"\",\"category\":\"ITS\"}]','returned','2026-04-14 05:55:13','2026-04-14 05:55:19');
/*!40000 ALTER TABLE `borrow_form_submissions` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `borrow_form_submissions_before_update` BEFORE UPDATE ON `borrow_form_submissions` FOR EACH ROW BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `borrow_requests`
--

DROP TABLE IF EXISTS `borrow_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `borrow_requests`
--

LOCK TABLES `borrow_requests` WRITE;
/*!40000 ALTER TABLE `borrow_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `borrow_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumable_add_history`
--

DROP TABLE IF EXISTS `consumable_add_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumable_add_history`
--

LOCK TABLES `consumable_add_history` WRITE;
/*!40000 ALTER TABLE `consumable_add_history` DISABLE KEYS */;
INSERT INTO `consumable_add_history` VALUES (1,1,'Air Freshner (Spray)',6,'bottles',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(2,2,'Albatross 50gms',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(3,3,'Arch File A4 Blue',20,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(4,4,'Arch File Long Blue',15,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(5,5,'Ballpen Black',6,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(6,6,'Battery AA',20,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(7,7,'Battery AAA',20,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(8,8,'Binder Clip 25 mm',15,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(9,9,'Binder Clip 41 mm',15,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(10,10,'Bookpaper A4',40,'ream',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(11,11,'Bookpaper Long',70,'ream',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(12,12,'Brother Ink BT5000 BMCY',15,'set',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(13,13,'Brother ink D60 Black',20,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(14,14,'Brown Envelope Long',80,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(15,15,'Brown Plastic Envelope Long',30,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(16,16,'Bulb  Watts (LED) 12 watts',15,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(17,17,'Canon Ink G1010 CYM',10,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(18,18,'Canon ink G1010black',10,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(19,19,'Clear book (long)',20,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(20,20,'Computer Keyboard',4,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(21,21,'Cork board 60cmx90cm',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(22,22,'Data File Box Long',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(23,23,'Dishwashing Liquid',12,'liter',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(24,24,'Doormat Rubberized',6,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(25,25,'Dust Pan',6,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(26,26,'Extension wire (10m)',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(27,27,'Extension wire (20m)',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(28,28,'Fastener long (plastic)',5,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(29,29,'Fastener plastic small',5,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(30,30,'Floormop with spinner',3,'sets',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(31,31,'Folder Long White',100,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(32,32,'Frame 8x13',110,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(33,33,'Gina Cloth',10,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(34,34,'Glass Cleaner',10,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(35,35,'Glue 130g',10,'bottles',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(36,36,'Highlighter (Yellow)',5,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(37,37,'HP 336X High Yield',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(38,38,'Insect Repellant (Spray) Big',4,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(39,39,'Interfolded Tissue Paper',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(40,40,'Long Expanded Folder (Blue)',60,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(41,41,'Long Expanded Folder (Green)',200,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(42,42,'Mailing Envelope Long',3,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(43,43,'Mouse Pad',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(44,44,'Muriatic acid Liter',3,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(45,45,'paper clip 28mm',20,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(46,46,'Paper Clip 33mm',20,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(47,47,'Paper Clip 50mm',20,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(48,48,'Paper Puncher',1,'pc.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(49,49,'Pencil (Mongol 2) 12\'s',4,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(50,50,'Pencil Sharpener (Heavy Duty)',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(51,51,'Permanent Marker (Pilot) Black',1,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(52,52,'Plastic pail',6,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(53,53,'Puncher (HD)',2,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(54,54,'Push Pin',4,'bxs',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(55,55,'Record Book (300lvs)',30,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(56,56,'Record Book (500lvs)',35,'pcs.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(57,57,'Rubber band (small)',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(58,58,'Scissors (Stainless)',12,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(59,59,'Scotchbrite sponge',48,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(60,60,'Signpen 0.5 Black',10,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(61,61,'Signpen energel black (0.7)',6,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(62,62,'Signpen energel blue (0.7)',4,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(63,63,'Soap (Detergent)',5,'kl.',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(64,64,'Soft Broom',4,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(65,65,'Special Paper Long Cream (180 gsm)',20,'pack',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(66,66,'Special Paper Long Cream (90gsm)',20,'pcks',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(67,67,'Spiral 1\"',15,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(68,68,'Sponges wiper with long handle',4,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(69,69,'Staple wire #35',10,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(70,70,'Steel Rack',2,'unit',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(71,71,'Sticker paper long matte',20,'pcks',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(72,72,'Tape (Packing Tape) 3\"',6,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(73,73,'Tape (Scotch) 1\"',10,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(74,74,'Tape (Scotch) 2\"',20,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(75,75,'Tape dispenser',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(76,76,'Tape -Double Side 2\"',10,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(77,77,'Tape -Double Sided 1\"',10,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(78,78,'Thumbtacks',4,'box',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(79,79,'Tissue paper',100,'roll',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(80,80,'Toilet bowl brush',8,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(81,81,'Toilet bowl Cleaner(500ml)',10,'btls',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(82,82,'Trash Bag (Black)  Large',20,'pack',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(83,83,'Trash Bag (Black)  Medium',20,'pack',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(84,84,'Trash Bin with Swing Lid Black',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(85,85,'USB64 GB',20,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(86,86,'Vellum Paper Long (Cream)',3,'reams',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(87,87,'Vellum Paper Long (White)',4,'pack',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(88,88,'White board 1/4',2,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(89,89,'White Board Marker Black (Pilot)',10,'pc',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(90,90,'Yellow paper',12,'pad',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(91,91,'Zonrox',10,'liter',0.00,0.00,3,12,5,'2026-04-10 07:23:36','ris_form','Added via RIS form #Unknown','N/A'),(92,183,'Ballpen Black',50,'boxes',75.00,3750.00,3,3,5,'2026-04-13 15:01:03','new_consumable','New consumable added to inventory','J&F suppliers'),(93,5,'Ballpen Black',75,'boxes',75.00,5625.00,3,NULL,5,'2026-04-13 15:07:07','stock_addition','Stock added to existing consumable. New WAC: ₱75.00','J&F suppliers'),(94,5,'Ballpen Black',100,'boxes',60.00,6000.00,3,NULL,5,'2026-04-13 15:24:52','stock_addition','Stock added to existing consumable. New WAC: ₱60.00','J&F suppliers');
/*!40000 ALTER TABLE `consumable_add_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumable_balance`
--

DROP TABLE IF EXISTS `consumable_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumable_balance`
--

LOCK TABLES `consumable_balance` WRITE;
/*!40000 ALTER TABLE `consumable_balance` DISABLE KEYS */;
INSERT INTO `consumable_balance` VALUES (3,183,'Ballpen Black',12,'0',3,0,1,1,'2026-04-13 07:24:08','2026-04-13 07:24:08');
/*!40000 ALTER TABLE `consumable_balance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumable_release_history`
--

DROP TABLE IF EXISTS `consumable_release_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumable_release_history`
--

LOCK TABLES `consumable_release_history` WRITE;
/*!40000 ALTER TABLE `consumable_release_history` DISABLE KEYS */;
INSERT INTO `consumable_release_history` VALUES (1,1,'Air Freshner (Spray)',3.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(2,2,'Albatross 50gms',5.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(3,3,'Arch File A4 Blue',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(4,4,'Arch File Long Blue',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(5,5,'Ballpen Black',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(6,6,'Battery AA',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(7,7,'Battery AAA',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(8,8,'Binder Clip 25 mm',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(9,9,'Binder Clip 41 mm',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(10,10,'Bookpaper A4',40.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(11,11,'Bookpaper Long',70.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(12,12,'Brother Ink BT5000 BMCY',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(13,13,'Brother ink D60 Black',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(14,14,'Brown Envelope Long',80.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(15,15,'Brown Plastic Envelope Long',30.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(16,16,'Bulb  Watts (LED) 12 watts',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(17,17,'Canon Ink G1010 CYM',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(18,18,'Canon ink G1010black',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(19,19,'Clear book (long)',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(20,20,'Computer Keyboard',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(21,21,'Cork board 60cmx90cm',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(22,22,'Data File Box Long',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(23,23,'Dishwashing Liquid',12.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(24,24,'Doormat Rubberized',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(25,25,'Dust Pan',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(26,26,'Extension wire (10m)',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(27,27,'Extension wire (20m)',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(28,28,'Fastener long (plastic)',5.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(29,29,'Fastener plastic small',5.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(30,30,'Floormop with spinner',3.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(31,31,'Folder Long White',100.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(32,32,'Frame 8x13',110.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(33,33,'Gina Cloth',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(34,34,'Glass Cleaner',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(35,35,'Glue 130g',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(36,36,'Highlighter (Yellow)',5.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(37,37,'HP 336X High Yield',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(38,38,'Insect Repellant (Spray) Big',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(39,39,'Interfolded Tissue Paper',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(40,40,'Long Expanded Folder (Blue)',60.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(41,41,'Long Expanded Folder (Green)',200.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(42,42,'Mailing Envelope Long',3.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(43,43,'Mouse Pad',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(44,44,'Muriatic acid Liter',3.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(45,45,'paper clip 28mm',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(46,46,'Paper Clip 33mm',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(47,47,'Paper Clip 50mm',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(48,48,'Paper Puncher',1.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(49,49,'Pencil (Mongol 2) 12\'s',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(50,50,'Pencil Sharpener (Heavy Duty)',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(51,51,'Permanent Marker (Pilot) Black',1.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(52,52,'Plastic pail',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(53,53,'Puncher (HD)',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(54,54,'Push Pin',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(55,55,'Record Book (300lvs)',30.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(56,56,'Record Book (500lvs)',35.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(57,57,'Rubber band (small)',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(58,58,'Scissors (Stainless)',12.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(59,59,'Scotchbrite sponge',48.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(60,60,'Signpen 0.5 Black',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(61,61,'Signpen energel black (0.7)',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(62,62,'Signpen energel blue (0.7)',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(63,63,'Soap (Detergent)',5.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(64,64,'Soft Broom',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(65,65,'Special Paper Long Cream (180 gsm)',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(66,66,'Special Paper Long Cream (90gsm)',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(67,67,'Spiral 1\"',15.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(68,68,'Sponges wiper with long handle',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(69,69,'Staple wire #35',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(70,70,'Steel Rack',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(71,71,'Sticker paper long matte',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(72,72,'Tape (Packing Tape) 3\"',6.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(73,73,'Tape (Scotch) 1\"',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(74,74,'Tape (Scotch) 2\"',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(75,76,'Tape -Double Side 2\"',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(76,77,'Tape -Double Sided 1\"',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(77,75,'Tape dispenser',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(78,78,'Thumbtacks',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(79,79,'Tissue paper',100.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:31','','2026-04-13 06:57:31'),(80,80,'Toilet bowl brush',8.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(81,81,'Toilet bowl Cleaner(500ml)',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(82,82,'Trash Bag (Black)  Large',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(83,83,'Trash Bag (Black)  Medium',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(84,84,'Trash Bin with Swing Lid Black',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(85,85,'USB64 GB',20.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(86,86,'Vellum Paper Long (Cream)',3.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(87,87,'Vellum Paper Long (White)',4.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(88,88,'White board 1/4',2.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(89,89,'White Board Marker Black (Pilot)',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(90,90,'Yellow paper',12.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32'),(91,91,'Zonrox',10.00,0.00,0.00,3,12,5,'BENJAMIN THOMPSON','deduct','2026-04-13 06:57:32','','2026-04-13 06:57:32');
/*!40000 ALTER TABLE `consumable_release_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `consumable_release_history_view`
--

DROP TABLE IF EXISTS `consumable_release_history_view`;
/*!50001 DROP VIEW IF EXISTS `consumable_release_history_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `consumable_release_history_view` AS SELECT
 1 AS `id`,
  1 AS `consumable_id`,
  1 AS `description`,
  1 AS `quantity_released`,
  1 AS `unit_cost`,
  1 AS `total_value`,
  1 AS `from_office_id`,
  1 AS `from_office_name`,
  1 AS `to_office_id`,
  1 AS `to_office_name`,
  1 AS `released_by`,
  1 AS `first_name`,
  1 AS `last_name`,
  1 AS `released_by_name`,
  1 AS `release_date`,
  1 AS `notes`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `consumables`
--

DROP TABLE IF EXISTS `consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumables`
--

LOCK TABLES `consumables` WRITE;
/*!40000 ALTER TABLE `consumables` DISABLE KEYS */;
INSERT INTO `consumables` VALUES (1,'Air Freshner (Spray)',3,'bottles',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(2,'Albatross 50gms',5,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(3,'Arch File A4 Blue',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(4,'Arch File Long Blue',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(5,'Ballpen Black',0,'box',60.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 07:25:09',12,NULL),(6,'Battery AA',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(7,'Battery AAA',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(8,'Binder Clip 25 mm',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(9,'Binder Clip 41 mm',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(10,'Bookpaper A4',0,'ream',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(11,'Bookpaper Long',0,'ream',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(12,'Brother Ink BT5000 BMCY',0,'set',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(13,'Brother ink D60 Black',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(14,'Brown Envelope Long',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(15,'Brown Plastic Envelope Long',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(16,'Bulb  Watts (LED) 12 watts',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(17,'Canon Ink G1010 CYM',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(18,'Canon ink G1010black',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(19,'Clear book (long)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(20,'Computer Keyboard',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(21,'Cork board 60cmx90cm',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(22,'Data File Box Long',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(23,'Dishwashing Liquid',0,'liter',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(24,'Doormat Rubberized',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(25,'Dust Pan',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(26,'Extension wire (10m)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(27,'Extension wire (20m)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(28,'Fastener long (plastic)',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(29,'Fastener plastic small',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(30,'Floormop with spinner',0,'sets',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(31,'Folder Long White',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(32,'Frame 8x13',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(33,'Gina Cloth',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(34,'Glass Cleaner',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(35,'Glue 130g',0,'bottles',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(36,'Highlighter (Yellow)',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(37,'HP 336X High Yield',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(38,'Insect Repellant (Spray) Big',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(39,'Interfolded Tissue Paper',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(40,'Long Expanded Folder (Blue)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(41,'Long Expanded Folder (Green)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(42,'Mailing Envelope Long',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(43,'Mouse Pad',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(44,'Muriatic acid Liter',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(45,'paper clip 28mm',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(46,'Paper Clip 33mm',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(47,'Paper Clip 50mm',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(48,'Paper Puncher',0,'pc.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(49,'Pencil (Mongol 2) 12\'s',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(50,'Pencil Sharpener (Heavy Duty)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(51,'Permanent Marker (Pilot) Black',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(52,'Plastic pail',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(53,'Puncher (HD)',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(54,'Push Pin',0,'bxs',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(55,'Record Book (300lvs)',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(56,'Record Book (500lvs)',0,'pcs.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(57,'Rubber band (small)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(58,'Scissors (Stainless)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(59,'Scotchbrite sponge',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(60,'Signpen 0.5 Black',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(61,'Signpen energel black (0.7)',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(62,'Signpen energel blue (0.7)',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(63,'Soap (Detergent)',0,'kl.',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(64,'Soft Broom',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(65,'Special Paper Long Cream (180 gsm)',0,'pack',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(66,'Special Paper Long Cream (90gsm)',0,'pcks',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(67,'Spiral 1\"',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(68,'Sponges wiper with long handle',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(69,'Staple wire #35',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(70,'Steel Rack',0,'unit',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(71,'Sticker paper long matte',0,'pcks',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(72,'Tape (Packing Tape) 3\"',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(73,'Tape (Scotch) 1\"',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(74,'Tape (Scotch) 2\"',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(75,'Tape dispenser',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(76,'Tape -Double Side 2\"',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(77,'Tape -Double Sided 1\"',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(78,'Thumbtacks',0,'box',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(79,'Tissue paper',0,'roll',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:31',12,NULL),(80,'Toilet bowl brush',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(81,'Toilet bowl Cleaner(500ml)',0,'btls',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(82,'Trash Bag (Black)  Large',0,'pack',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(83,'Trash Bag (Black)  Medium',0,'pack',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(84,'Trash Bin with Swing Lid Black',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(85,'USB64 GB',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(86,'Vellum Paper Long (Cream)',0,'reams',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(87,'Vellum Paper Long (White)',0,'pack',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(88,'White board 1/4',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(89,'White Board Marker Black (Pilot)',0,'pc',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(90,'Yellow paper',0,'pad',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(91,'Zonrox',0,'liter',0.00,10,'pcs',3,'2026-04-10 02:23:36','2026-04-13 06:57:32',12,NULL),(92,'Air Freshner (Spray)',3,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(93,'Albatross 50gms',5,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(94,'Arch File A4 Blue',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(95,'Arch File Long Blue',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(96,'Ballpen Black',226,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 07:25:09',NULL,NULL),(97,'Battery AA',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(98,'Battery AAA',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(99,'Binder Clip 25 mm',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(100,'Binder Clip 41 mm',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(101,'Bookpaper A4',40,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(102,'Bookpaper Long',70,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(103,'Brother Ink BT5000 BMCY',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(104,'Brother ink D60 Black',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(105,'Brown Envelope Long',80,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(106,'Brown Plastic Envelope Long',30,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(107,'Bulb  Watts (LED) 12 watts',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(108,'Canon Ink G1010 CYM',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(109,'Canon ink G1010black',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(110,'Clear book (long)',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(111,'Computer Keyboard',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(112,'Cork board 60cmx90cm',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(113,'Data File Box Long',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(114,'Dishwashing Liquid',12,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(115,'Doormat Rubberized',6,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(116,'Dust Pan',6,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(117,'Extension wire (10m)',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(118,'Extension wire (20m)',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(119,'Fastener long (plastic)',5,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(120,'Fastener plastic small',5,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(121,'Floormop with spinner',3,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(122,'Folder Long White',100,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(123,'Frame 8x13',110,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(124,'Gina Cloth',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(125,'Glass Cleaner',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(126,'Glue 130g',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(127,'Highlighter (Yellow)',5,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(128,'HP 336X High Yield',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(129,'Insect Repellant (Spray) Big',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(130,'Interfolded Tissue Paper',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(131,'Long Expanded Folder (Blue)',60,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(132,'Long Expanded Folder (Green)',200,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(133,'Mailing Envelope Long',3,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(134,'Mouse Pad',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(135,'Muriatic acid Liter',3,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(136,'paper clip 28mm',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(137,'Paper Clip 33mm',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(138,'Paper Clip 50mm',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(139,'Paper Puncher',1,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(140,'Pencil (Mongol 2) 12\'s',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(141,'Pencil Sharpener (Heavy Duty)',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(142,'Permanent Marker (Pilot) Black',1,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(143,'Plastic pail',6,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(144,'Puncher (HD)',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(145,'Push Pin',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(146,'Record Book (300lvs)',30,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(147,'Record Book (500lvs)',35,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(148,'Rubber band (small)',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(149,'Scissors (Stainless)',12,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(150,'Scotchbrite sponge',48,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(151,'Signpen 0.5 Black',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(152,'Signpen energel black (0.7)',6,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(153,'Signpen energel blue (0.7)',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(154,'Soap (Detergent)',5,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(155,'Soft Broom',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(156,'Special Paper Long Cream (180 gsm)',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(157,'Special Paper Long Cream (90gsm)',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(158,'Spiral 1\"',15,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(159,'Sponges wiper with long handle',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(160,'Staple wire #35',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(161,'Steel Rack',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(162,'Sticker paper long matte',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(163,'Tape (Packing Tape) 3\"',6,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(164,'Tape (Scotch) 1\"',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(165,'Tape (Scotch) 2\"',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(166,'Tape -Double Side 2\"',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(167,'Tape -Double Sided 1\"',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(168,'Tape dispenser',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(169,'Thumbtacks',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(170,'Tissue paper',100,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:31','2026-04-13 06:57:31',NULL,NULL),(171,'Toilet bowl brush',8,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(172,'Toilet bowl Cleaner(500ml)',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(173,'Trash Bag (Black)  Large',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(174,'Trash Bag (Black)  Medium',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(175,'Trash Bin with Swing Lid Black',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(176,'USB64 GB',20,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(177,'Vellum Paper Long (Cream)',3,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(178,'Vellum Paper Long (White)',4,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(179,'White board 1/4',2,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(180,'White Board Marker Black (Pilot)',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(181,'Yellow paper',12,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(182,'Zonrox',10,'pieces',0.00,10,'pcs',12,'2026-04-13 06:57:32','2026-04-13 06:57:32',NULL,NULL),(183,'Ballpen Black',4,'boxes',75.00,10,'pcs',3,'2026-04-13 07:01:03','2026-04-13 07:24:08',3,'J&F suppliers');
/*!40000 ALTER TABLE `consumables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consume_history`
--

DROP TABLE IF EXISTS `consume_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consume_history`
--

LOCK TABLES `consume_history` WRITE;
/*!40000 ALTER TABLE `consume_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `consume_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_integrity_checks`
--

DROP TABLE IF EXISTS `data_integrity_checks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_integrity_checks`
--

LOCK TABLES `data_integrity_checks` WRITE;
/*!40000 ALTER TABLE `data_integrity_checks` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_integrity_checks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_references`
--

DROP TABLE IF EXISTS `document_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_references`
--

LOCK TABLES `document_references` WRITE;
/*!40000 ALTER TABLE `document_references` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_references` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'EMP-2026-004','Liam','James','Walker','l.walker@example.com','555-0104',NULL,1,'Manager','Operations','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(2,'EMP-2026-005','Noah','Alexander','Do','n.do@example.com','555-0105',NULL,2,'Developer','Backend','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(3,'EMP-2026-006','Oliver','William','Young','o.young@example.com','555-0106',NULL,3,'Analyst','Finance','contractual','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(4,'EMP-2026-007','Elijah','Benjamin','King','e.king@example.com','555-0107',NULL,1,'Specialist','HR','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(5,'EMP-2026-008','James','Lucas','Wright','j.wright@example.com','555-0108',NULL,2,'Lead','QA','job_order','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(6,'EMP-2026-009','William','Henry','Lopez','w.lopez@example.com','555-0109',NULL,4,'Consultant','IT','contractual','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(7,'EMP-2026-010','Benjamin','Mason','Hill','b.hill@example.com','555-0110',NULL,3,'Associate','Marketing','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(8,'EMP-2026-011','Lucas','Ethan','Scott','l.scott@example.com','555-0111',NULL,2,'Architect','Solutions','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(9,'EMP-2026-012','Henry','Michael','Green','h.green@example.com','555-0112',NULL,1,'Director','Sales','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(10,'EMP-2026-013','Alexander','Graham','Adams','a.adams@example.com','555-0113','',5,'Coordinator','[\"Full Stack Developer\",\"Software developer\"]','job_order','','2026-04-07 02:13:21','2026-04-07 02:56:41'),(11,'EMP-2026-014','Emma','Rose','Baker','e.baker@example.com','555-0114',NULL,2,'Engineer','Cloud','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(12,'EMP-2026-015','Olivia','Grace','Gonzalez','o.gonzalez@example.com','555-0115',NULL,3,'Designer','UI/UX','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(13,'EMP-2026-016','Ava','Marie','Nelson','a.nelson@example.com','555-0116',NULL,4,'Manager','Product','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(14,'EMP-2026-017','Isabella','Ann','Carter','i.carter@example.com','555-0117',NULL,1,'Analyst','Security','resigned','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(15,'EMP-2026-018','Sophia','Elizabeth','Mitchell','s.mitchell@example.com','555-0118',NULL,2,'Developer','Mobile','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(16,'EMP-2026-019','Mia','Lynn','Perez','m.perez@example.com','555-0119',NULL,3,'Writer','Content','contractual','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(17,'EMP-2026-020','Charlotte','Jane','Roberts','c.roberts@example.com','555-0120',NULL,1,'Lead','Customer Success','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(18,'EMP-2026-021','Amelia','Claire','Turner','a.turner@example.com','555-0121',NULL,5,'Specialist','Legal','job_order','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(19,'EMP-2026-022','Harper','Sloane','Phillips','h.phillips@example.com','555-0122',NULL,2,'Admin','Database','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(20,'EMP-2026-023','Evelyn','Paige','Campbell','e.campbell@example.com','555-0123',NULL,4,'Scrum Master','Agile','permanent','uncleared','2026-04-07 02:13:21','2026-04-07 06:31:43'),(21,'EMP-2026-024','Jack','Thomas','Parker','j.parker@example.com','555-0124',NULL,1,'Executive','Account','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(22,'EMP-2026-025','Jackson','Ryan','Evans','j.evans@example.com','555-0125',NULL,2,'Tester','Automation','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(23,'EMP-2026-026','Sebastian','Cole','Edwards','s.edwards@example.com','555-0126',NULL,3,'Analyst','Business','retired','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(24,'EMP-2026-027','Aiden','Finn','Collins','a.collins@example.com','555-0127',NULL,5,'Manager','Support','permanent','uncleared','2026-04-07 02:13:21','2026-04-07 06:38:52'),(25,'EMP-2026-028','Matthew','Luke','Stewart','m.stewart@example.com','555-0128',NULL,1,'Clerk','Inventory','job_order','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(26,'EMP-2026-029','Samuel','Grant','Morris','s.morris@example.com','555-0129',NULL,2,'Scientist','Data','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(27,'EMP-2026-030','David','Alan','Murphy','d.murphy@example.com','555-0130',NULL,3,'Consultant','Strategy','contractual','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(28,'EMP-2026-031','Joseph','Caleb','Rivera','j.rivera@example.com','555-0131',NULL,4,'Lead','Frontend','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(29,'EMP-2026-032','Carter','Joel','Cook','c.cook@example.com','555-0132',NULL,1,'Manager','Purchasing','permanent','uncleared','2026-04-07 02:13:21','2026-04-07 06:25:44'),(30,'EMP-2026-033','Owen','Reid','Rogers','o.rogers@example.com','555-0133',NULL,2,'Engineer','DevOps','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(31,'EMP-2026-034','Wyatt','Silas','Morgan','w.morgan@example.com','555-0134',NULL,3,'Associate','Public Relations','job_order','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(32,'EMP-2026-035','John','Paul','Peterson','j.peterson@example.com','555-0135',NULL,5,'Chief','Executive','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(33,'EMP-2026-036','Leo','Jude','Cooper','l.cooper@example.com','555-0136',NULL,1,'Manager','Facility','resigned','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(34,'EMP-2026-037','Luke','Miles','Reed','l.reed@example.com','555-0137',NULL,2,'Technician','Support','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(35,'EMP-2026-038','Julian','Beau','Bailey','j.bailey@example.com','555-0138',NULL,4,'Coordinator','Events','contractual','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(36,'EMP-2026-039','Isaac','Zane','Bell','i.bell@example.com','555-0139',NULL,3,'Analyst','Risk','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(37,'EMP-2026-040','Levi','Axel','Gomez','l.gomez@example.com','555-0140',NULL,2,'Specialist','SEO','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(38,'EMP-2026-041','Daniel','Ivan','Kelly','d.kelly@example.com','555-0141',NULL,1,'Manager','Payroll','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(39,'EMP-2026-042','Gabriel','Max','Sanders','g.sanders@example.com','555-0142',NULL,5,'Officer','Compliance','job_order','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(40,'EMP-2026-043','Anthony','Theo','Price','a.price@example.com','555-0143',NULL,2,'Lead','Security','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(41,'EMP-2026-044','Dylan','Otis','Bennett','d.bennett@example.com','555-0144',NULL,3,'Artist','Graphic','permanent','uncleared','2026-04-07 02:13:21','2026-04-13 08:49:13'),(42,'EMP-2026-045','Grayson','Leo','Wood','g.wood@example.com','555-0145',NULL,4,'Director','Engineering','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(43,'EMP-2026-046','Christopher','Kai','Barnes','c.barnes@example.com','555-0146',NULL,1,'Specialist','Training','resigned','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(44,'EMP-2026-047','Joshua','Ezra','Ross','j.ross@example.com','555-0147',NULL,2,'Analyst','Systems','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(45,'EMP-2026-048','Nathan','Jace','Henderson','n.henderson@example.com','555-0148',NULL,3,'Manager','Regional','retired','','2026-04-07 02:13:21','2026-04-07 02:53:08'),(46,'EMP-2026-049','Andrew','Gael','Coleman','a.coleman@example.com','555-0149',NULL,5,'Supervisor','Production','job_order','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(47,'EMP-2026-050','Thomas','Arlo','Jenkins','t.jenkins@example.com','555-0150',NULL,4,'Lead','R&D','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(48,'EMP-2026-051','Charles','Hugo','Perry','c.perry@example.com','555-0151',NULL,1,'Associate','Legal','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(49,'EMP-2026-052','Caleb','Felix','Powell','c.powell@example.com','555-0152',NULL,2,'Engineer','Network','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(50,'EMP-2026-053','Ryan','Oscar','Long','r.long@example.com','555-0153',NULL,3,'Representative','Sales','permanent','cleared','2026-04-07 02:13:21','2026-04-07 02:53:08'),(51,'2026-001-01-011239','Walton','Lisaba','Loneza','wjll2022-2920-98466@bicol-u.edu.ph','9107171456',NULL,4,'Computer Programmer','[\"Full Stack Developer\",\"Web Developer\"]','permanent','uncleared','2026-04-08 01:54:42','2026-04-08 02:45:17');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_login_attempts`
--

DROP TABLE IF EXISTS `failed_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_login_attempts`
--

LOCK TABLES `failed_login_attempts` WRITE;
/*!40000 ALTER TABLE `failed_login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fiscal_year_settings`
--

DROP TABLE IF EXISTS `fiscal_year_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fiscal_year_settings`
--

LOCK TABLES `fiscal_year_settings` WRITE;
/*!40000 ALTER TABLE `fiscal_year_settings` DISABLE KEYS */;
INSERT INTO `fiscal_year_settings` VALUES (1,1,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(2,2,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(3,3,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(4,4,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(5,5,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(6,6,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(7,11,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(8,12,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53'),(9,13,2026,'2026-01-01','2026-12-31',1,1,'2026-03-30 07:59:53','2026-03-30 07:59:53');
/*!40000 ALTER TABLE `fiscal_year_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (1,'PAR','07','Property Acknowledgement Receipt','Form for acknowledging receipt of government property','1773111297_Screenshot 2026-03-10 105440.png','active',1,1,'2026-01-06 10:17:58','2026-03-10 02:54:57'),(2,'ICS','04','Inventory Custodian Slip','Form for transferring accountability of property','1767703470_Screenshot 2026-01-06 194414.png','active',1,1,'2026-01-06 10:17:58','2026-02-13 16:10:20'),(3,'RIS','03','Requisition and Issue Slip','Form for requesting and issuing supplies','1767705532_RIS HEADER.png','active',1,1,'2026-01-06 10:17:58','2026-02-13 15:29:04'),(6,'PTR','9','Property Transfer Receipt','For transferring assets on person accountable.','1773111417_Screenshot 2026-03-10 105646.png','active',1,1,'2026-01-06 10:23:41','2026-03-10 02:56:57'),(7,'IIRUP','05','Inventory and Inspection Report of Unserviceable Property','for dropping unserviceable items from the inventory records and determines how they will be disposed','1773111175_Screenshot 2026-03-10 105233.png','active',1,1,'2026-01-06 10:50:01','2026-03-10 02:52:55');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `prevent_deletion` BEFORE DELETE ON `forms` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Deleting data from this table is prohibited' */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `fuel_in`
--

DROP TABLE IF EXISTS `fuel_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_in`
--

LOCK TABLES `fuel_in` WRITE;
/*!40000 ALTER TABLE `fuel_in` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_out`
--

DROP TABLE IF EXISTS `fuel_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_out`
--

LOCK TABLES `fuel_out` WRITE;
/*!40000 ALTER TABLE `fuel_out` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_out` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_types`
--

DROP TABLE IF EXISTS `fuel_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fuel_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_types`
--

LOCK TABLES `fuel_types` WRITE;
/*!40000 ALTER TABLE `fuel_types` DISABLE KEYS */;
INSERT INTO `fuel_types` VALUES (1,'Diesel',1,'2026-02-07 02:41:49'),(2,'Gasoline',1,'2026-02-07 02:41:49');
/*!40000 ALTER TABLE `fuel_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `fund_allocation_summary`
--

DROP TABLE IF EXISTS `fund_allocation_summary`;

-- failed on view `fund_allocation_summary`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fund_allocation_summary` AS select `fa`.`id` AS `id`,`fa`.`fund_id` AS `fund_id`,`fa`.`office_id` AS `office_id`,`o`.`office_name` AS `office_name`,`f`.`fund_code` AS `fund_code`,`f`.`fund_name` AS `fund_name`,`f`.`fund_cluster` AS `fund_cluster`,`fa`.`allocated_amount` AS `allocated_amount`,`fa`.`utilized_amount` AS `utilized_amount`,`fa`.`remaining_balance` AS `remaining_balance`,`fa`.`allocation_date` AS `allocation_date`,`fa`.`status` AS `status`,round(`fa`.`utilized_amount` / `fa`.`allocated_amount` * 100,2) AS `utilization_percentage`,`fa`.`created_at` AS `created_at`,`fa`.`updated_at` AS `updated_at` from ((`fund_allocations` `fa` join `funds` `f` on(`fa`.`fund_id` = `f`.`id`)) join `offices` `o` on(`fa`.`office_id` = `o`.`id`))


--
-- Temporary table structure for view `fund_summary`
--

DROP TABLE IF EXISTS `fund_summary`;

-- failed on view `fund_summary`: CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fund_summary` AS select `f`.`id` AS `id`,`f`.`fund_code` AS `fund_code`,`f`.`fund_name` AS `fund_name`,`f`.`fund_cluster` AS `fund_cluster`,`f`.`description` AS `description`,`f`.`department` AS `department`,`f`.`budget_year` AS `budget_year`,`f`.`initial_amount` AS `initial_amount`,`f`.`current_balance` AS `current_balance`,`f`.`status` AS `status`,`f`.`start_date` AS `start_date`,`f`.`end_date` AS `end_date`,count(`ft`.`id`) AS `transaction_count`,coalesce(sum(case when `ft`.`transaction_type` = 'expenditure' then `ft`.`amount` else 0 end),0) AS `total_expenditures`,coalesce(sum(case when `ft`.`transaction_type` = 'allocation' then `ft`.`amount` else 0 end),0) AS `total_allocations`,`f`.`created_at` AS `created_at`,`f`.`updated_at` AS `updated_at` from (`funds` `f` left join `fund_transactions` `ft` on(`f`.`id` = `ft`.`fund_id`)) group by `f`.`id`


--
-- Table structure for table `funds`
--

DROP TABLE IF EXISTS `funds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fund_code` varchar(50) NOT NULL,
  `fund_name` varchar(255) NOT NULL,
  `fund_cluster` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `budget_year` int(11) NOT NULL,
  `initial_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fund_code` (`fund_code`),
  KEY `idx_fund_code` (`fund_code`),
  KEY `idx_fund_cluster` (`fund_cluster`),
  KEY `idx_budget_year` (`budget_year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funds`
--

LOCK TABLES `funds` WRITE;
/*!40000 ALTER TABLE `funds` DISABLE KEYS */;
INSERT INTO `funds` VALUES (1,'05','General Fund','GF','General fund for municipal operations','General Administration',2025,5000000.00,5000000.00,'active','2025-01-01','2025-12-31','2026-02-14 04:27:24','2026-02-14 04:38:26',NULL,1),(2,'03','Special Education Fund 2025','SEF','Special education fund for school operations','Education',2025,2000000.00,2000000.00,'active','2025-01-01','2025-12-31','2026-02-14 04:27:24','2026-02-14 04:40:07',NULL,1),(3,'02','Local Government Development Fund 2025','LGGF','Local government development fund','Development',2025,3000000.00,3000000.00,'active','2025-01-01','2025-12-31','2026-02-14 04:27:24','2026-02-14 04:39:58',NULL,1),(4,'04','Trust Fund 2025','Trust Fund','Trust fund for specific purposes','Finance',2025,1500000.00,1500000.00,'active','2025-01-01','2025-12-31','2026-02-14 04:27:24','2026-02-14 04:40:13',NULL,1),(5,'01','Infrastructure Fund 2025','INFRA','Infrastructure development fund','Engineering',2025,8000000.00,8000000.00,'active','2025-01-01','2025-12-31','2026-02-14 04:27:24','2026-02-14 04:38:47',NULL,1);
/*!40000 ALTER TABLE `funds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ics_form`
--

DROP TABLE IF EXISTS `ics_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ics_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `header_image` varchar(255) DEFAULT NULL,
  `entity_name` varchar(200) DEFAULT NULL,
  `fund_cluster` varchar(100) DEFAULT NULL,
  `ics_no` varchar(50) DEFAULT NULL,
  `received_from_name` varchar(200) DEFAULT NULL,
  `received_from_position` varchar(200) DEFAULT NULL,
  `received_by_name` varchar(200) DEFAULT NULL,
  `received_by_position` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_ics_no` (`ics_no`),
  CONSTRAINT `ics_form_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ics_form_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ics_form`
--

LOCK TABLES `ics_form` WRITE;
/*!40000 ALTER TABLE `ics_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `ics_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ics_forms`
--

DROP TABLE IF EXISTS `ics_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ics_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_name` varchar(255) NOT NULL,
  `fund_cluster` varchar(100) NOT NULL,
  `ics_no` varchar(50) NOT NULL,
  `received_from` varchar(255) NOT NULL,
  `received_from_position` varchar(255) NOT NULL,
  `received_from_date` date DEFAULT NULL,
  `received_by` varchar(255) NOT NULL,
  `received_by_position` varchar(255) NOT NULL,
  `received_by_date` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ics_no` (`ics_no`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ics_forms`
--

LOCK TABLES `ics_forms` WRITE;
/*!40000 ALTER TABLE `ics_forms` DISABLE KEYS */;
INSERT INTO `ics_forms` VALUES (1,'OSB','','OMMI-2026-I-01','WALTON LONEZA','OFFICER',NULL,'BENJAMIN THOMPSON','CLERK',NULL,5,5,'2026-04-10 02:11:04','2026-04-10 02:11:04');
/*!40000 ALTER TABLE `ics_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ics_items`
--

DROP TABLE IF EXISTS `ics_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ics_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL DEFAULT 0,
  `ics_id` int(11) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `ics_no` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `useful_life` varchar(50) NOT NULL DEFAULT '',
  `item_no` varchar(100) DEFAULT NULL,
  `estimated_useful_life` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `idx_ics_id` (`ics_id`),
  KEY `idx_asset_id` (`asset_id`),
  KEY `form_id` (`form_id`),
  CONSTRAINT `ics_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `ics_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ics_items`
--

LOCK TABLES `ics_items` WRITE;
/*!40000 ALTER TABLE `ics_items` DISABLE KEYS */;
INSERT INTO `ics_items` VALUES (1,1,1,NULL,NULL,2.00,'',18500.00,37000.00,'Executive Desk','10','2026-04-07-010-0101-01\r\n2026-04-07-010-0102-01',NULL,'2026-04-10 02:11:04');
/*!40000 ALTER TABLE `ics_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iirup_forms`
--

DROP TABLE IF EXISTS `iirup_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iirup_forms` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_number` (`form_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_as_of_year` (`as_of_year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iirup_forms`
--

LOCK TABLES `iirup_forms` WRITE;
/*!40000 ALTER TABLE `iirup_forms` DISABLE KEYS */;
INSERT INTO `iirup_forms` VALUES (1,'IIRUP-2026-5796',2026,'Aiden Collins','SUPPLY OFFICE','OVM','Test Name','Test Designation','Test Auth','Test Auth Designation','Test Inspector','Test Witness','draft',1,5,5,'2026-04-10 02:27:00','2026-04-10 02:27:00');
/*!40000 ALTER TABLE `iirup_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `iirup_items`
--

DROP TABLE IF EXISTS `iirup_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `iirup_items` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `form_id` (`form_id`),
  KEY `idx_property_no` (`property_no`),
  KEY `idx_dept_office` (`dept_office`),
  KEY `idx_item_order` (`item_order`),
  CONSTRAINT `iirup_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `iirup_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `iirup_items`
--

LOCK TABLES `iirup_items` WRITE;
/*!40000 ALTER TABLE `iirup_items` DISABLE KEYS */;
INSERT INTO `iirup_items` VALUES (1,1,'2026-01-23','ASUS Vivobook 16','2026-04-05-030-0101-02',1.00,42900.00,42900.00,0.00,0.00,0.00,'',0.00,0.00,0.00,'',0.00,0.00,0.00,'',0.00,'OVM','',NULL,1,'2026-04-10 02:27:00','2026-04-10 02:27:00');
/*!40000 ALTER TABLE `iirup_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `infrastructure`
--

DROP TABLE IF EXISTS `infrastructure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `infrastructure` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_classification` (`classification`),
  KEY `idx_location` (`location`),
  KEY `idx_date_constructed` (`date_constructed`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Infrastructure and building assets management';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `infrastructure`
--

LOCK TABLES `infrastructure` WRITE;
/*!40000 ALTER TABLE `infrastructure` DISABLE KEYS */;
INSERT INTO `infrastructure` VALUES (1,'Building','Main Municipal Hall','Government Office','Town Proper','2010-05-15','PROP-001',5000000.00,7500000.00,'2024-01-15','Main government building housing various municipal offices','[]',1,'2026-03-01 05:55:12',5,'2026-03-09 01:45:26'),(2,'Building','Public Market Building','Commercial','Town Proper','2015-08-20','PROP-002',3500000.00,4200000.00,'2024-02-10','Public market with 50 stalls for local vendors','[]',1,'2026-03-01 05:55:12',5,'2026-03-09 01:42:44'),(3,'Road','National Highway ','Transportation','Pilar','2012-03-10','ROAD-001',8000000.00,9500000.00,'2024-01-20','15 km national highway section passing through Pilar','[]',1,'2026-03-01 05:55:12',5,'2026-03-09 01:43:05'),(4,'Bridge',' River Bridge','Transportation','Barangay ','2018-11-25','BRIDGE-001',2500000.00,3000000.00,'2024-03-05','Concrete bridge connecting San Antonio to town proper','[]',1,'2026-03-01 05:55:12',5,'2026-03-09 01:42:17'),(5,'Building','Public Elementary School','Educational','Barangay ','2016-06-30','PROP-003',4200000.00,5500000.00,'2024-02-15','Elementary school with 20 classrooms and facilities','[]',1,'2026-03-01 05:55:12',5,'2026-03-09 01:42:30');
/*!40000 ALTER TABLE `infrastructure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itr_forms`
--

DROP TABLE IF EXISTS `itr_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itr_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_name` varchar(255) NOT NULL,
  `fund_cluster` varchar(100) NOT NULL,
  `itr_no` varchar(50) NOT NULL,
  `from_office` varchar(255) NOT NULL,
  `to_office` varchar(255) NOT NULL,
  `transfer_date` date DEFAULT NULL,
  `transfer_type` enum('Donation','Reassignment','Relocate','Others') DEFAULT 'Reassignment',
  `transfer_type_others` varchar(100) DEFAULT NULL,
  `end_user` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `requested_by` varchar(100) NOT NULL,
  `requested_by_position` varchar(100) NOT NULL,
  `requested_date` date DEFAULT NULL,
  `approved_by` varchar(100) NOT NULL,
  `approved_by_position` varchar(100) NOT NULL,
  `approved_date` date DEFAULT NULL,
  `released_by` varchar(100) NOT NULL,
  `released_by_position` varchar(100) NOT NULL,
  `released_date` date DEFAULT NULL,
  `received_by` varchar(100) NOT NULL,
  `received_by_position` varchar(100) NOT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('draft','submitted','approved','released','received','cancelled') DEFAULT 'draft',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `itr_no` (`itr_no`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_itr_no` (`itr_no`),
  KEY `idx_entity_name` (`entity_name`),
  KEY `idx_from_office` (`from_office`),
  KEY `idx_to_office` (`to_office`),
  KEY `idx_status` (`status`),
  KEY `idx_transfer_date` (`transfer_date`),
  CONSTRAINT `itr_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `itr_forms_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itr_forms`
--

LOCK TABLES `itr_forms` WRITE;
/*!40000 ALTER TABLE `itr_forms` DISABLE KEYS */;
INSERT INTO `itr_forms` VALUES (1,'','','ITR-0010','20','24','2026-04-10','Reassignment','','Alexander G. Adams/OVM','','waltonloneza@gmail.com','User','2026-04-10','ELTON ESCANO','MAYOR',NULL,'Daniel Atlas','SUPPLY OFFICER',NULL,'Aiden F.  Collins','OVM',NULL,'draft',56000.00,5,5,'2026-04-10 02:38:32','2026-04-10 02:38:32');
/*!40000 ALTER TABLE `itr_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `itr_items`
--

DROP TABLE IF EXISTS `itr_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `itr_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `item_no` int(11) NOT NULL,
  `date_acquired` date DEFAULT NULL,
  `ics_par_no` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `condition_of_inventory` varchar(50) DEFAULT 'serviceable',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_item_no` (`item_no`),
  KEY `idx_description` (`description`(255)),
  CONSTRAINT `itr_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `itr_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `itr_items`
--

LOCK TABLES `itr_items` WRITE;
/*!40000 ALTER TABLE `itr_items` DISABLE KEYS */;
INSERT INTO `itr_items` VALUES (1,1,1,'2026-04-07','2026-07-05-030-0102-01','2',1.00,'0',56000.00,56000.00,'serviceable','','2026-04-10 02:38:32','2026-04-10 02:38:32');
/*!40000 ALTER TABLE `itr_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `itr_items_after_insert` AFTER INSERT ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = NEW.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `itr_items_after_update` AFTER UPDATE ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = NEW.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `itr_items_after_delete` AFTER DELETE ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = OLD.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `itr_summary`
--

DROP TABLE IF EXISTS `itr_summary`;
/*!50001 DROP VIEW IF EXISTS `itr_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `itr_summary` AS SELECT
 1 AS `id`,
  1 AS `entity_name`,
  1 AS `fund_cluster`,
  1 AS `itr_no`,
  1 AS `from_office`,
  1 AS `to_office`,
  1 AS `transfer_date`,
  1 AS `transfer_type`,
  1 AS `end_user`,
  1 AS `purpose`,
  1 AS `status`,
  1 AS `total_amount`,
  1 AS `item_count`,
  1 AS `created_by`,
  1 AS `first_name`,
  1 AS `last_name`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `lend_consumables`
--

DROP TABLE IF EXISTS `lend_consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lend_consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumable_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity_lent` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_value` decimal(10,2) NOT NULL,
  `from_office_id` int(11) NOT NULL,
  `to_office_id` int(11) NOT NULL,
  `lent_by` int(11) NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `date_lent` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `status` enum('lent','returned','overdue') NOT NULL DEFAULT 'lent',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `from_office_id` (`from_office_id`),
  KEY `to_office_id` (`to_office_id`),
  KEY `lent_by` (`lent_by`),
  KEY `idx_consumable_id` (`consumable_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date_lent` (`date_lent`),
  CONSTRAINT `lend_consumables_ibfk_1` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`),
  CONSTRAINT `lend_consumables_ibfk_2` FOREIGN KEY (`from_office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `lend_consumables_ibfk_3` FOREIGN KEY (`to_office_id`) REFERENCES `offices` (`id`),
  CONSTRAINT `lend_consumables_ibfk_4` FOREIGN KEY (`lent_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lend_consumables`
--

LOCK TABLES `lend_consumables` WRITE;
/*!40000 ALTER TABLE `lend_consumables` DISABLE KEYS */;
/*!40000 ALTER TABLE `lend_consumables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `failure_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `ip_address` (`ip_address`),
  KEY `success` (`success`),
  KEY `attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_logs`
--

LOCK TABLES `login_logs` WRITE;
/*!40000 ALTER TABLE `login_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `system_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `asset_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `employee_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  CONSTRAINT `notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','system') DEFAULT 'info',
  `priority` enum('critical','high','medium','low') DEFAULT 'medium',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `offices`
--

DROP TABLE IF EXISTS `offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `office_name` varchar(100) NOT NULL,
  `office_code` varchar(10) NOT NULL,
  `address` text DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Philippines',
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `office_name` (`office_name`),
  UNIQUE KEY `office_code` (`office_code`),
  KEY `idx_office_code` (`office_code`),
  KEY `idx_status` (`status`),
  KEY `updated_by` (`updated_by`),
  KEY `created_by` (`created_by`),
  KEY `fk_offices_branch` (`branch`),
  CONSTRAINT `fk_offices_branch` FOREIGN KEY (`branch`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offices_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `offices`
--

LOCK TABLES `offices` WRITE;
/*!40000 ALTER TABLE `offices` DISABLE KEYS */;
INSERT INTO `offices` VALUES (1,'SB-SEC','04','Calongay, Pilar, Sorsogon','Sorsogon','1471','Philippines','0981-017-8391','pilarsor.sb-sec@gmail.com',7,'active','2026-01-06 07:30:05','2026-03-11 08:07:09',1,1,NULL),(2,'OMBO','05','Calongay, Pilar, Sorsogon','Sorsogon','4714','Philippines','0981-017-8391','pilarsor.ombo@gmail.com',10,'active','2026-01-06 07:30:05','2026-03-11 08:07:09',1,1,NULL),(3,'Supply Office','00','Calongay, Pilar, Sorsogon','Sorsogon','1471','Philippines','0981-017-8391','pilarsor.mto@gmail.com',10,'active','2026-01-06 07:30:05','2026-03-11 08:07:09',1,1,NULL),(4,'OMM','01','Calongay, Pilar, Sorsogon','Pilar','4714','Philippines','0981-017-8391','pilarsor.mayor@gmail.com',35,'active','2026-01-06 07:30:05','2026-03-11 08:07:09',1,1,NULL),(5,'OVM','02','Calongay, Pilar, Sorsogon','Sorsogon','4714','Philippines','0981-017-8391','pilarsor.ovm@gmail.com',45,'active','2026-01-06 07:30:05','2026-03-11 08:07:09',1,1,NULL),(6,'OMAC','06','Calongay, Pilar, Sorsogon','Sorsogon','4714','Philippines','0981-017-8391','pilarsor.omac@gmail.com',5,'active','2026-01-06 07:33:52','2026-03-11 08:11:10',1,1,11),(11,'OMH','22','Caloñgay Pilar, Sorsogon','Albay','4714','Philippines','','',0,'active','2026-03-10 00:55:22','2026-03-11 08:07:09',1,NULL,NULL),(12,'OSB','03','Caloñgay Pilar, Sorsogon','Albay','4714','Philippines','','',0,'active','2026-03-10 00:56:48','2026-03-11 08:07:09',1,NULL,NULL),(13,'Lying in','025','Caloñgay Pilar, Sorsogon','Albay','4714','Philippines','','',0,'active','2026-03-11 08:12:50','2026-04-20 03:05:41',1,1,11),(14,'Motorpool','07','Caloñgay Pilar, Sorsogon','Albay','4714','Philippines','','',0,'active','2026-04-07 07:03:45','2026-04-07 07:03:45',1,NULL,NULL),(15,'Head Office','050',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,NULL),(16,'North District','051',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(17,'South District','052',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(18,'East District','053',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(19,'West District','054',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(20,'Central District','055',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(21,'Finance Office','056',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(22,'HR Department','057',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(23,'IT Department','058',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(24,'Operations','059',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(25,'Maintenance','060',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(26,'Security','061',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(27,'Records Division','062',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(28,'Legal Department','063',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(29,'Procurement','064',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(30,'Audit Office','065',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'inactive','2026-04-20 03:22:12','2026-04-20 03:25:02',1,1,15),(31,'Planning Division','066',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'inactive','2026-04-20 03:22:12','2026-04-20 03:22:41',1,1,15),(32,'Public Relations','067',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(33,'Medical Clinic','068',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(34,'Training Center','069',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(35,'Warehouse','070',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(36,'Transportation','071',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15),(37,'Catering Services','072',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'inactive','2026-04-20 03:22:12','2026-04-20 03:25:07',1,1,15),(38,'Facilities Management','073',NULL,NULL,NULL,'Philippines',NULL,NULL,0,'active','2026-04-20 03:22:12','2026-04-20 03:22:12',1,NULL,15);
/*!40000 ALTER TABLE `offices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oil_in`
--

DROP TABLE IF EXISTS `oil_in`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oil_in` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_time` datetime NOT NULL,
  `oil_type` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `storage_location` varchar(255) DEFAULT NULL,
  `delivery_receipt` varchar(255) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oil_in`
--

LOCK TABLES `oil_in` WRITE;
/*!40000 ALTER TABLE `oil_in` DISABLE KEYS */;
/*!40000 ALTER TABLE `oil_in` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oil_out`
--

DROP TABLE IF EXISTS `oil_out`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oil_out` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oil_date` date NOT NULL,
  `oil_time_in` time DEFAULT NULL,
  `oil_oil_no` varchar(50) DEFAULT NULL,
  `oil_plate_no` varchar(50) DEFAULT NULL,
  `oil_request` varchar(255) DEFAULT NULL,
  `all_oil_type` int(11) NOT NULL,
  `oil_liters` decimal(10,2) NOT NULL,
  `oil_vehicle_type` varchar(100) DEFAULT NULL,
  `oil_receiver` varchar(100) DEFAULT NULL,
  `oil_time_out` time DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `office_name` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oil_out`
--

LOCK TABLES `oil_out` WRITE;
/*!40000 ALTER TABLE `oil_out` DISABLE KEYS */;
/*!40000 ALTER TABLE `oil_out` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oil_types`
--

DROP TABLE IF EXISTS `oil_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oil_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oil_types`
--

LOCK TABLES `oil_types` WRITE;
/*!40000 ALTER TABLE `oil_types` DISABLE KEYS */;
INSERT INTO `oil_types` VALUES (1,'Engine Oil',1,'2026-03-26 02:18:33'),(2,'Hydraulic Oil',1,'2026-03-26 02:18:56'),(3,'motor oil',1,'2026-03-30 02:23:00'),(5,'oil ',1,'2026-03-30 05:23:25');
/*!40000 ALTER TABLE `oil_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `online_backup_configs`
--

DROP TABLE IF EXISTS `online_backup_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `online_backup_configs`
--

LOCK TABLES `online_backup_configs` WRITE;
/*!40000 ALTER TABLE `online_backup_configs` DISABLE KEYS */;
INSERT INTO `online_backup_configs` VALUES (1,'google_drive','Google Drive Backup','822354506716-8on8lknqkudd71ae2v935aq7hkvib4kq.apps.googleusercontent.com','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','ya29.a0Aa7pCA_l51pZ0iktGhYIs71GN9o2QiGcsand7k9CxDQQpyBQtRjgc7NMToZUlZnMFC9nSmeFQrc7zGywdTGWMPBDy9iBOEpf_QFf3eJOUcJZNUfePIGPhO7sZb3DotyyEx2airAPPBUTE-RATZ8DGzG2PkyO3RKPCyk9iPKl588ACXk9gqQvLeS7vheLMBYlHRxe5V4aCgYKAe4SARESFQHGX2MimGU0dLbDFtN0I3aLKjKrYw0206','1//0evabxpw2eEOKCgYIARAAGA4SNwF-L9IrMe3TQwU8ekRvEgkTEhkFCqiF1gTHfJAjgWmoOswjzBmrsnJj2HPUfXOctbCgDhxvpc8','','backup',1,1,'2026-01-04 13:52:31','2026-01-05 13:59:23'),(2,'dropbox','Dropbox Backup',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-01-04 13:52:31','2026-01-04 13:52:31'),(3,'onedrive','OneDrive Backup',NULL,NULL,NULL,NULL,NULL,NULL,0,1,'2026-01-04 13:52:31','2026-01-04 13:52:31');
/*!40000 ALTER TABLE `online_backup_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `par_form`
--

DROP TABLE IF EXISTS `par_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `par_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `received_by_name` varchar(200) DEFAULT NULL,
  `issued_by_name` varchar(200) DEFAULT NULL,
  `position_office_left` varchar(200) DEFAULT NULL,
  `position_office_right` varchar(200) DEFAULT NULL,
  `header_image` varchar(255) DEFAULT NULL,
  `entity_name` varchar(200) DEFAULT NULL,
  `fund_cluster` varchar(100) DEFAULT NULL,
  `par_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_received_left` date DEFAULT NULL,
  `date_received_right` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_office_id` (`office_id`),
  KEY `idx_par_no` (`par_no`),
  CONSTRAINT `par_form_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `par_form_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `par_form`
--

LOCK TABLES `par_form` WRITE;
/*!40000 ALTER TABLE `par_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `par_form` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `par_forms`
--

DROP TABLE IF EXISTS `par_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `par_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_name` varchar(255) NOT NULL,
  `fund_cluster` varchar(100) NOT NULL,
  `par_no` varchar(50) NOT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `received_by_name` varchar(255) DEFAULT NULL,
  `received_by_position` varchar(255) DEFAULT NULL,
  `received_by_date` date DEFAULT NULL,
  `issued_by_name` varchar(255) DEFAULT NULL,
  `issued_by_position` varchar(255) DEFAULT NULL,
  `issued_by_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `par_no` (`par_no`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `par_forms`
--

LOCK TABLES `par_forms` WRITE;
/*!40000 ALTER TABLE `par_forms` DISABLE KEYS */;
INSERT INTO `par_forms` VALUES (1,'LGU PILAR','','OMMP-2026-04-0001','01','BENJAMIN THOMPSON','PROPERTY CUSTODIAN',NULL,'DANIEL ATLAS','CLERK',NULL,NULL,5,5,'2026-04-07 03:08:45','2026-04-07 03:08:45');
/*!40000 ALTER TABLE `par_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `par_items`
--

DROP TABLE IF EXISTS `par_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `par_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL,
  `item_no` int(11) NOT NULL DEFAULT 1,
  `asset_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `property_number` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_id` (`form_id`),
  KEY `idx_asset_id` (`asset_id`),
  CONSTRAINT `par_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `par_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `par_items`
--

LOCK TABLES `par_items` WRITE;
/*!40000 ALTER TABLE `par_items` DISABLE KEYS */;
INSERT INTO `par_items` VALUES (1,1,1,1,2.00,'units','Laptop AMD Ryzen','2026','2026-04-07',NULL,56000.00);
/*!40000 ALTER TABLE `par_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_policies`
--

DROP TABLE IF EXISTS `password_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_name` varchar(100) NOT NULL,
  `min_length` int(11) DEFAULT 8,
  `require_uppercase` tinyint(1) DEFAULT 1,
  `require_lowercase` tinyint(1) DEFAULT 1,
  `require_numbers` tinyint(1) DEFAULT 1,
  `require_special_chars` tinyint(1) DEFAULT 1,
  `max_age_days` int(11) DEFAULT 90,
  `prevent_reuse` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `policy_name` (`policy_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_policies`
--

LOCK TABLES `password_policies` WRITE;
/*!40000 ALTER TABLE `password_policies` DISABLE KEYS */;
INSERT INTO `password_policies` VALUES (1,'Default Policy',8,1,1,1,1,90,5,1,'2026-01-06 02:28:39','2026-01-06 02:28:39');
/*!40000 ALTER TABLE `password_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (15,'wjll2022-2920-98466@bicol-u.edu.ph','e0f3d647ee489b60aebc048e0ff23a379e4e12e2cfb23ceb3ca7d73049992a2b','2026-01-07 02:02:15','2026-01-07 00:02:15',0),(17,'waltonloneza@gmail.com','686d6ee5df06e2a900ce04636b45b154aeeffc324742703abb67faacfd4c463e','2026-01-07 02:04:59','2026-01-07 00:04:59',1),(18,'admin@pims.com','85d69f4007665a48f2fd67a8a18993614046ad707f9ce35f6d5cb91cfca75886','2026-04-21 03:49:18','2026-04-21 00:49:18',0);
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peripherals`
--

DROP TABLE IF EXISTS `peripherals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `peripherals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_item_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Peripheral name (e.g., Monitor, Keyboard, Mouse)',
  `model` varchar(255) DEFAULT NULL COMMENT 'Model number or designation',
  `serial_number` varchar(255) DEFAULT NULL COMMENT 'Unique serial number',
  `status` enum('serviceable','unserviceable','red_tagged','no_tag','disposed') NOT NULL DEFAULT 'serviceable' COMMENT 'Current status of the peripheral',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated the record',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_serial_number` (`serial_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_peripherals_created_by` (`created_by`),
  KEY `fk_peripherals_updated_by` (`updated_by`),
  KEY `idx_asset_item_id` (`asset_item_id`),
  CONSTRAINT `fk_peripherals_asset_item` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_peripherals_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_peripherals_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Peripheral devices attached to assets (monitors, keyboards, mice, etc.)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peripherals`
--

LOCK TABLES `peripherals` WRITE;
/*!40000 ALTER TABLE `peripherals` DISABLE KEYS */;
/*!40000 ALTER TABLE `peripherals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'users.create','Create new users','users','2026-01-04 01:09:27'),(2,'users.read','View users list','users','2026-01-04 01:09:27'),(3,'users.update','Edit user information','users','2026-01-04 01:09:27'),(4,'users.delete','Delete users','users','2026-01-04 01:09:27'),(5,'users.activate','Activate/deactivate users','users','2026-01-04 01:09:27'),(6,'inventory.create','Add new products','inventory','2026-01-04 01:09:27'),(7,'inventory.read','View products list','inventory','2026-01-04 01:09:27'),(8,'inventory.update','Edit product information','inventory','2026-01-04 01:09:27'),(9,'inventory.delete','Delete products','inventory','2026-01-04 01:09:27'),(10,'inventory.transaction.in','Add stock (IN transactions)','inventory','2026-01-04 01:09:27'),(11,'inventory.transaction.out','Remove stock (OUT transactions)','inventory','2026-01-04 01:09:27'),(12,'categories.create','Create new categories','categories','2026-01-04 01:09:27'),(13,'categories.read','View categories list','categories','2026-01-04 01:09:27'),(14,'categories.update','Edit category information','categories','2026-01-04 01:09:27'),(15,'categories.delete','Delete categories','categories','2026-01-04 01:09:27'),(16,'reports.view','View system reports','reports','2026-01-04 01:09:27'),(17,'reports.export','Export reports','reports','2026-01-04 01:09:27'),(18,'system.settings','Access system settings','system','2026-01-04 01:09:27'),(19,'system.logs','View system logs','system','2026-01-04 01:09:27'),(20,'system.backup','Create system backup','system','2026-01-04 01:09:27'),(21,'system.audit','Access security audit','system','2026-01-04 01:09:27');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `red_tags`
--

DROP TABLE IF EXISTS `red_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `red_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `control_no` varchar(50) NOT NULL,
  `red_tag_no` varchar(50) NOT NULL,
  `date_received` date NOT NULL,
  `tagged_by` varchar(100) NOT NULL,
  `item_location` varchar(255) NOT NULL,
  `item_description` text NOT NULL,
  `removal_reason` text NOT NULL,
  `action` varchar(50) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `asset_item_id` int(11) DEFAULT NULL,
  `peripheral_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `disposal_reason` text DEFAULT NULL,
  `disposal_date` date DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `component_type` enum('main_asset','monitor','ups','peripheral') DEFAULT 'main_asset',
  `component_description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `office_id` (`office_id`),
  KEY `asset_id` (`asset_item_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_peripheral_id` (`peripheral_id`),
  CONSTRAINT `fk_red_tags_asset_item_id` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `red_tags`
--

LOCK TABLES `red_tags` WRITE;
/*!40000 ALTER TABLE `red_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `red_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_audit_trail`
--

DROP TABLE IF EXISTS `report_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_audit_trail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(50) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `action` enum('generated','viewed','exported','printed','approved','modified','deleted') NOT NULL,
  `user_id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `file_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_user_action` (`user_id`,`action`),
  KEY `idx_office_date` (`office_id`,`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit trail for all report activities';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_audit_trail`
--

LOCK TABLES `report_audit_trail` WRITE;
/*!40000 ALTER TABLE `report_audit_trail` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_audit_trail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_generation_history`
--

DROP TABLE IF EXISTS `report_generation_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_generation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_id` varchar(50) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `generation_method` enum('manual','scheduled','api') NOT NULL,
  `office_id` int(11) NOT NULL,
  `generated_by` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `record_count` int(11) DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `generation_time` decimal(10,3) DEFAULT NULL,
  `status` enum('generating','completed','failed','cancelled') NOT NULL DEFAULT 'generating',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_office_date` (`office_id`,`created_at`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='History of all report generation attempts';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_generation_history`
--

LOCK TABLES `report_generation_history` WRITE;
/*!40000 ALTER TABLE `report_generation_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_generation_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_schedules`
--

DROP TABLE IF EXISTS `report_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_name` varchar(255) NOT NULL,
  `report_type` enum('inventory','asset','consumable','borrow_request','monthly','quarterly','annual') NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','annually') NOT NULL,
  `schedule_day` int(11) DEFAULT NULL,
  `schedule_time` time NOT NULL DEFAULT '08:00:00',
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipients`)),
  `office_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` datetime DEFAULT NULL,
  `next_run` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_office_active` (`office_id`,`is_active`),
  KEY `idx_next_run` (`next_run`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Scheduled report generation';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_schedules`
--

LOCK TABLES `report_schedules` WRITE;
/*!40000 ALTER TABLE `report_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_templates`
--

DROP TABLE IF EXISTS `report_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_templates` (
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
  KEY `idx_office_type` (`office_id`,`report_type`),
  KEY `idx_default_active` (`is_default`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Custom report templates for LGU compliance';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_templates`
--

LOCK TABLES `report_templates` WRITE;
/*!40000 ALTER TABLE `report_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ris_forms`
--

DROP TABLE IF EXISTS `ris_forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_forms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ris_no` varchar(50) DEFAULT NULL,
  `sai_no` varchar(50) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `division` varchar(100) NOT NULL,
  `office` varchar(100) NOT NULL,
  `responsibility_center` varchar(100) NOT NULL,
  `date` date DEFAULT NULL,
  `date_2` date DEFAULT NULL,
  `purpose` text NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `requested_by_position` varchar(100) NOT NULL,
  `requested_date` date DEFAULT NULL,
  `approved_by` varchar(100) NOT NULL,
  `approved_by_position` varchar(100) NOT NULL,
  `approved_date` date DEFAULT NULL,
  `issued_by` varchar(100) NOT NULL,
  `issued_by_position` varchar(100) NOT NULL,
  `issued_date` date DEFAULT NULL,
  `received_by` varchar(100) NOT NULL,
  `received_by_position` varchar(100) NOT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('draft','submitted','approved','issued','received','cancelled') DEFAULT 'draft',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_ris_no` (`ris_no`),
  KEY `idx_sai_no` (`sai_no`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_date` (`date`),
  CONSTRAINT `ris_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_forms`
--

LOCK TABLES `ris_forms` WRITE;
/*!40000 ALTER TABLE `ris_forms` DISABLE KEYS */;
INSERT INTO `ris_forms` VALUES (1,NULL,NULL,NULL,'','OSB','',NULL,NULL,'','LEO PETERSON','MAYOR',NULL,'ELTON ESCANO','MAYOR',NULL,'DANIEL ATLAS','CLERK',NULL,'BENJAMIN THOMPSON','CLERK',NULL,'draft',0.00,5,'2026-04-10 02:23:36','2026-04-10 02:23:36');
/*!40000 ALTER TABLE `ris_forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ris_items`
--

DROP TABLE IF EXISTS `ris_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ris_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ris_form_id` int(11) NOT NULL,
  `stock_no` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ris_form_id` (`ris_form_id`),
  KEY `idx_stock_no` (`stock_no`),
  CONSTRAINT `ris_items_ibfk_1` FOREIGN KEY (`ris_form_id`) REFERENCES `ris_forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ris_items`
--

LOCK TABLES `ris_items` WRITE;
/*!40000 ALTER TABLE `ris_items` DISABLE KEYS */;
INSERT INTO `ris_items` VALUES (1,1,1,'bottles','Air Freshner (Spray)',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(2,1,2,'pc','Albatross 50gms',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(3,1,3,'pc','Arch File A4 Blue',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(4,1,4,'pc','Arch File Long Blue',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(5,1,5,'box','Ballpen Black',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(6,1,6,'pcs.','Battery AA',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(7,1,7,'pcs.','Battery AAA',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(8,1,8,'box','Binder Clip 25 mm',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(9,1,9,'box','Binder Clip 41 mm',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(10,1,10,'ream','Bookpaper A4',40.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(11,1,11,'ream','Bookpaper Long',70.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(12,1,12,'set','Brother Ink BT5000 BMCY',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(13,1,13,'btls','Brother ink D60 Black',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(14,1,14,'pc','Brown Envelope Long',80.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(15,1,15,'pc','Brown Plastic Envelope Long',30.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(16,1,16,'pc','Bulb  Watts (LED) 12 watts',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(17,1,17,'btls','Canon Ink G1010 CYM',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(18,1,18,'btls','Canon ink G1010black',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(19,1,19,'pc','Clear book (long)',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(20,1,20,'pc','Computer Keyboard',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(21,1,21,'pc','Cork board 60cmx90cm',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(22,1,22,'pc','Data File Box Long',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(23,1,23,'liter','Dishwashing Liquid',12.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(24,1,24,'pc','Doormat Rubberized',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(25,1,25,'pc','Dust Pan',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(26,1,26,'pc','Extension wire (10m)',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(27,1,27,'pc','Extension wire (20m)',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(28,1,28,'box','Fastener long (plastic)',5.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(29,1,29,'box','Fastener plastic small',5.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(30,1,30,'sets','Floormop with spinner',3.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(31,1,31,'pcs.','Folder Long White',100.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(32,1,32,'pc','Frame 8x13',110.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(33,1,33,'roll','Gina Cloth',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(34,1,34,'btls','Glass Cleaner',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(35,1,35,'bottles','Glue 130g',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(36,1,36,'box','Highlighter (Yellow)',5.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(37,1,37,'pc','HP 336X High Yield',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(38,1,38,'btls','Insect Repellant (Spray) Big',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(39,1,39,'pc','Interfolded Tissue Paper',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(40,1,40,'pc','Long Expanded Folder (Blue)',60.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(41,1,41,'pc','Long Expanded Folder (Green)',200.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(42,1,42,'box','Mailing Envelope Long',3.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(43,1,43,'pc','Mouse Pad',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(44,1,44,'btls','Muriatic acid Liter',3.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(45,1,45,'box','paper clip 28mm',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(46,1,46,'box','Paper Clip 33mm',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(47,1,47,'box','Paper Clip 50mm',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(48,1,48,'pc.','Paper Puncher',1.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(49,1,49,'box','Pencil (Mongol 2) 12\'s',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(50,1,50,'pc','Pencil Sharpener (Heavy Duty)',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(51,1,51,'box','Permanent Marker (Pilot) Black',1.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(52,1,52,'pc','Plastic pail',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(53,1,53,'pcs.','Puncher (HD)',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(54,1,54,'bxs','Push Pin',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(55,1,55,'pcs.','Record Book (300lvs)',30.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(56,1,56,'pcs.','Record Book (500lvs)',35.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(57,1,57,'pc','Rubber band (small)',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(58,1,58,'pc','Scissors (Stainless)',12.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(59,1,59,'pc','Scotchbrite sponge',48.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(60,1,60,'box','Signpen 0.5 Black',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(61,1,61,'box','Signpen energel black (0.7)',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(62,1,62,'box','Signpen energel blue (0.7)',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(63,1,63,'kl.','Soap (Detergent)',5.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(64,1,64,'pc','Soft Broom',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(65,1,65,'pack','Special Paper Long Cream (180 gsm)',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(66,1,66,'pcks','Special Paper Long Cream (90gsm)',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(67,1,67,'pc','Spiral 1\"',15.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(68,1,68,'pc','Sponges wiper with long handle',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(69,1,69,'box','Staple wire #35',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(70,1,70,'unit','Steel Rack',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(71,1,71,'pcks','Sticker paper long matte',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(72,1,72,'roll','Tape (Packing Tape) 3\"',6.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(73,1,73,'roll','Tape (Scotch) 1\"',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(74,1,74,'roll','Tape (Scotch) 2\"',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(75,1,75,'pc','Tape dispenser',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(76,1,76,'roll','Tape -Double Side 2\"',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(77,1,77,'roll','Tape -Double Sided 1\"',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(78,1,78,'box','Thumbtacks',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(79,1,79,'roll','Tissue paper',100.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(80,1,80,'pc','Toilet bowl brush',8.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(81,1,81,'btls','Toilet bowl Cleaner(500ml)',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(82,1,82,'pack','Trash Bag (Black)  Large',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(83,1,83,'pack','Trash Bag (Black)  Medium',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(84,1,84,'pc','Trash Bin with Swing Lid Black',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(85,1,85,'pc','USB64 GB',20.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(86,1,86,'reams','Vellum Paper Long (Cream)',3.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(87,1,87,'pack','Vellum Paper Long (White)',4.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(88,1,88,'pc','White board 1/4',2.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(89,1,89,'pc','White Board Marker Black (Pilot)',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(90,1,90,'pad','Yellow paper',12.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36'),(91,1,91,'liter','Zonrox',10.00,0.00,0.00,'2026-04-10 02:23:36','2026-04-10 02:23:36');
/*!40000 ALTER TABLE `ris_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `ris_items_after_insert` AFTER INSERT ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `ris_items_after_update` AFTER UPDATE ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `ris_items_after_delete` AFTER DELETE ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = OLD.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.ris_form_id;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `ris_summary`
--

DROP TABLE IF EXISTS `ris_summary`;
/*!50001 DROP VIEW IF EXISTS `ris_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `ris_summary` AS SELECT
 1 AS `id`,
  1 AS `ris_no`,
  1 AS `sai_no`,
  1 AS `code`,
  1 AS `division`,
  1 AS `office`,
  1 AS `responsibility_center`,
  1 AS `date`,
  1 AS `purpose`,
  1 AS `status`,
  1 AS `total_amount`,
  1 AS `item_count`,
  1 AS `created_by`,
  1 AS `first_name`,
  1 AS `last_name`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,'system_admin',12,1,1,1,1,'2026-01-04 01:09:27'),(2,'system_admin',15,1,1,1,1,'2026-01-04 01:09:27'),(3,'system_admin',13,1,1,1,1,'2026-01-04 01:09:27'),(4,'system_admin',14,1,1,1,1,'2026-01-04 01:09:27'),(5,'system_admin',6,1,1,1,1,'2026-01-04 01:09:27'),(6,'system_admin',9,1,1,1,1,'2026-01-04 01:09:27'),(7,'system_admin',7,1,1,1,1,'2026-01-04 01:09:27'),(8,'system_admin',10,1,1,1,1,'2026-01-04 01:09:27'),(9,'system_admin',11,1,1,1,1,'2026-01-04 01:09:27'),(10,'system_admin',8,1,1,1,1,'2026-01-04 01:09:27'),(11,'system_admin',17,1,1,1,1,'2026-01-04 01:09:27'),(12,'system_admin',16,1,1,1,1,'2026-01-04 01:09:27'),(13,'system_admin',21,1,1,1,1,'2026-01-04 01:09:27'),(14,'system_admin',20,1,1,1,1,'2026-01-04 01:09:27'),(15,'system_admin',19,1,1,1,1,'2026-01-04 01:09:27'),(16,'system_admin',18,1,1,1,1,'2026-01-04 01:09:27'),(17,'system_admin',5,1,1,1,1,'2026-01-04 01:09:27'),(18,'system_admin',1,1,1,1,1,'2026-01-04 01:09:27'),(19,'system_admin',4,1,1,1,1,'2026-01-04 01:09:27'),(20,'system_admin',2,1,1,1,1,'2026-01-04 01:09:27'),(21,'system_admin',3,1,1,1,1,'2026-01-04 01:09:27'),(32,'admin',12,1,1,1,1,'2026-01-04 01:09:27'),(33,'admin',15,1,1,1,1,'2026-01-04 01:09:27'),(34,'admin',13,1,1,1,1,'2026-01-04 01:09:27'),(35,'admin',14,1,1,1,1,'2026-01-04 01:09:27'),(36,'admin',6,1,1,1,1,'2026-01-04 01:09:27'),(37,'admin',9,1,1,1,1,'2026-01-04 01:09:27'),(38,'admin',7,1,1,1,1,'2026-01-04 01:09:27'),(39,'admin',10,1,1,1,1,'2026-01-04 01:09:27'),(40,'admin',11,1,1,1,1,'2026-01-04 01:09:27'),(41,'admin',8,1,1,1,1,'2026-01-04 01:09:27'),(42,'admin',17,1,1,1,1,'2026-01-04 01:09:27'),(43,'admin',16,1,1,1,1,'2026-01-04 01:09:27'),(44,'admin',21,0,1,0,0,'2026-01-04 01:09:27'),(45,'admin',20,0,1,0,0,'2026-01-04 01:09:27'),(46,'admin',19,0,1,0,0,'2026-01-04 01:09:27'),(47,'admin',18,0,1,0,0,'2026-01-04 01:09:27'),(48,'admin',5,1,1,1,1,'2026-01-04 01:09:27'),(49,'admin',1,1,1,1,1,'2026-01-04 01:09:27'),(50,'admin',4,1,1,1,0,'2026-01-04 01:09:27'),(51,'admin',2,1,1,1,1,'2026-01-04 01:09:27'),(52,'admin',3,1,1,1,1,'2026-01-04 01:09:27'),(63,'office_admin',12,1,0,0,0,'2026-01-04 01:09:27'),(64,'office_admin',15,0,0,0,0,'2026-01-04 01:09:27'),(65,'office_admin',13,0,1,0,0,'2026-01-04 01:09:27'),(66,'office_admin',14,1,0,1,0,'2026-01-04 01:09:27'),(67,'office_admin',6,1,0,0,0,'2026-01-04 01:09:27'),(68,'office_admin',9,0,0,0,0,'2026-01-04 01:09:27'),(69,'office_admin',7,0,1,0,0,'2026-01-04 01:09:27'),(70,'office_admin',10,1,0,0,0,'2026-01-04 01:09:27'),(71,'office_admin',11,1,0,0,0,'2026-01-04 01:09:27'),(72,'office_admin',8,1,0,1,0,'2026-01-04 01:09:27'),(73,'office_admin',17,0,0,0,0,'2026-01-04 01:09:27'),(74,'office_admin',16,0,1,0,0,'2026-01-04 01:09:27'),(75,'office_admin',21,0,0,0,0,'2026-01-04 01:09:27'),(76,'office_admin',20,0,0,0,0,'2026-01-04 01:09:27'),(77,'office_admin',19,0,0,0,0,'2026-01-04 01:09:27'),(78,'office_admin',18,0,0,0,0,'2026-01-04 01:09:27'),(79,'office_admin',5,0,0,0,0,'2026-01-04 01:09:27'),(80,'office_admin',1,0,0,0,0,'2026-01-04 01:09:27'),(81,'office_admin',4,0,0,0,0,'2026-01-04 01:09:27'),(82,'office_admin',2,0,1,0,0,'2026-01-04 01:09:27'),(83,'office_admin',3,0,0,0,0,'2026-01-04 01:09:27'),(94,'user',12,0,0,0,0,'2026-01-04 01:09:27'),(95,'user',15,0,0,0,0,'2026-01-04 01:09:27'),(96,'user',13,0,1,0,0,'2026-01-04 01:09:27'),(97,'user',14,0,0,0,0,'2026-01-04 01:09:27'),(98,'user',6,0,0,0,0,'2026-01-04 01:09:27'),(99,'user',9,0,0,0,0,'2026-01-04 01:09:27'),(100,'user',7,0,1,0,0,'2026-01-04 01:09:27'),(101,'user',10,1,0,0,0,'2026-01-04 01:09:27'),(102,'user',11,1,0,0,0,'2026-01-04 01:09:27'),(103,'user',8,0,0,0,0,'2026-01-04 01:09:27'),(104,'user',17,0,0,0,0,'2026-01-04 01:09:27'),(105,'user',16,0,0,0,0,'2026-01-04 01:09:27'),(106,'user',21,0,0,0,0,'2026-01-04 01:09:27'),(107,'user',20,0,0,0,0,'2026-01-04 01:09:27'),(108,'user',19,0,0,0,0,'2026-01-04 01:09:27'),(109,'user',18,0,0,0,0,'2026-01-04 01:09:27'),(110,'user',5,0,0,0,0,'2026-01-04 01:09:27'),(111,'user',1,0,0,0,0,'2026-01-04 01:09:27'),(112,'user',4,0,0,0,0,'2026-01-04 01:09:27'),(113,'user',2,0,0,0,0,'2026-01-04 01:09:27'),(114,'user',3,0,0,0,0,'2026-01-04 01:09:27');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheduled_backups`
--

DROP TABLE IF EXISTS `scheduled_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scheduled_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `backup_type` enum('full','database','files') DEFAULT 'full',
  `schedule_type` enum('daily','weekly','monthly') DEFAULT 'daily',
  `schedule_day` int(11) DEFAULT NULL,
  `schedule_time` time DEFAULT '00:00:00',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `include_files` tinyint(1) NOT NULL DEFAULT 1,
  `include_database` tinyint(1) NOT NULL DEFAULT 1,
  `online_backup` tinyint(1) NOT NULL DEFAULT 0,
  `cloud_provider` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) GENERATED ALWAYS AS (`enabled`) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`),
  KEY `next_run` (`next_run`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheduled_backups`
--

LOCK TABLES `scheduled_backups` WRITE;
/*!40000 ALTER TABLE `scheduled_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheduled_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_alerts`
--

DROP TABLE IF EXISTS `security_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('critical','high','medium','low') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL,
  `status` enum('open','investigating','resolved','false_positive') DEFAULT 'open',
  `affected_user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_alert_affected_user` (`affected_user_id`),
  KEY `fk_alert_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_alert_affected_user` FOREIGN KEY (`affected_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_alert_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_alerts`
--

LOCK TABLES `security_alerts` WRITE;
/*!40000 ALTER TABLE `security_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_audit_logs`
--

DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `audit_id` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `score` int(11) NOT NULL,
  `issues_found` int(11) NOT NULL,
  `critical_issues` int(11) DEFAULT 0,
  `high_issues` int(11) DEFAULT 0,
  `medium_issues` int(11) DEFAULT 0,
  `low_issues` int(11) DEFAULT 0,
  `audit_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`audit_data`)),
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_id` (`audit_id`),
  KEY `idx_category` (`category`),
  KEY `idx_performed_by` (`performed_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_performed_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_audit_logs`
--

LOCK TABLES `security_audit_logs` WRITE;
/*!40000 ALTER TABLE `security_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_logs`
--

DROP TABLE IF EXISTS `security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `severity` (`severity`),
  KEY `timestamp` (`timestamp`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_logs`
--

LOCK TABLES `security_logs` WRITE;
/*!40000 ALTER TABLE `security_logs` DISABLE KEYS */;
INSERT INTO `security_logs` VALUES (1,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 05:32:28'),(2,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 07:10:21'),(3,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 08:16:28'),(4,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 11:08:29'),(5,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 12:19:11'),(6,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 14:07:19'),(7,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-06 15:13:56'),(8,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 01:16:25'),(9,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 03:33:30'),(10,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 04:47:29'),(11,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 08:21:21'),(12,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 10:35:44'),(13,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 11:36:36'),(14,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 13:26:10'),(15,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 15:22:08'),(16,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-07 23:47:41'),(17,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-09 10:51:35'),(18,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-09 11:52:39'),(19,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-09 12:53:02'),(20,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 06:24:33'),(21,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 08:22:04'),(22,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 09:22:42'),(23,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 10:24:56'),(24,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 11:37:27'),(25,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-11 12:42:45'),(26,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 13:22:08'),(27,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-12 15:21:21'),(28,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-14 13:49:28'),(29,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-16 13:57:57'),(30,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-17 06:08:04'),(31,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-17 07:24:31'),(32,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-19 03:29:57'),(33,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-19 04:57:00'),(34,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-20 02:47:01'),(35,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-20 07:15:52'),(36,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-21 13:37:21'),(37,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-21 14:38:20'),(38,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-22 12:20:05'),(39,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-22 13:23:34'),(40,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-23 04:28:42'),(41,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-23 09:26:49'),(42,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-23 11:42:43'),(43,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-25 12:59:52'),(44,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-26 12:56:44'),(45,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-27 02:14:51'),(46,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-27 03:28:35'),(47,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-27 04:50:21'),(48,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','2026-01-27 09:41:50'),(49,'session_timeout','Session timeout for user: Walton loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-27 12:34:56'),(50,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-28 12:03:38'),(51,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-28 13:17:37'),(52,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-29 13:28:55'),(53,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-01-30 03:46:10'),(54,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 07:41:24'),(55,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 09:17:15'),(56,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-01 10:17:42'),(57,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-05 00:10:39'),(58,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-05 01:11:16'),(59,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-05 03:23:55'),(60,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-05 04:30:48'),(61,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-05 13:28:48'),(62,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-07 03:09:04'),(63,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-07 04:36:52'),(64,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-07 05:42:13'),(65,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 03:39:50'),(66,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 06:09:57'),(67,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 07:11:36'),(68,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-11 08:13:13'),(69,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-12 01:49:51'),(70,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-12 03:10:22'),(71,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-12 06:03:40'),(72,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-13 04:40:48'),(73,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-13 15:59:54'),(74,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-13 17:05:32'),(75,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-14 04:29:00'),(76,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-14 13:15:56'),(77,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-14 15:18:45'),(78,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-15 01:46:27'),(79,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-15 02:48:07'),(80,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','2026-02-18 03:36:21'),(81,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-26 01:14:08'),(82,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 06:37:00'),(83,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 10:25:14'),(84,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 13:28:25'),(85,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-27 14:34:50'),(86,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-28 00:29:48'),(87,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-28 01:33:46'),(88,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-28 02:34:14'),(89,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-28 04:02:01'),(90,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-02-28 14:48:32'),(91,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-01 06:21:01'),(92,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-02 01:55:22'),(93,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-03 02:24:38'),(94,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-03 06:09:45'),(95,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-03 08:09:15'),(96,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-04 01:52:12'),(97,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320','2026-03-04 02:59:47'),(98,'session_timeout','Session timeout for user: joshua escano (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-04 05:24:04'),(99,'session_timeout','Session timeout for user: John Patrick Jazareno (lgupilar.supplyroom@gmail.com)','medium',16,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-04 06:45:36'),(100,'session_timeout','Session timeout for user: Joshua Escaño (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320','2026-03-05 01:28:29'),(101,'session_timeout','Session timeout for user: Joshua Escaño (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (X11; Linux aarch64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 CrKey/1.54.250320','2026-03-05 02:42:08'),(102,'session_timeout','Session timeout for user: Joshua Escaño (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-05 03:44:58'),(103,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-05 05:27:26'),(104,'session_timeout','Session timeout for user: Joshua Escaño (joshuamarifrancis@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-05 07:26:30'),(105,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 02:23:50'),(106,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',17,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 03:30:20'),(107,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 05:28:51'),(108,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 06:30:21'),(109,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:50:13'),(110,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:51:39'),(111,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:53:00'),(112,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:55:08'),(113,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:56:19'),(114,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 13:58:02'),(115,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-03-09 13:59:58'),(116,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-03-09 14:01:05'),(117,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:02:41'),(118,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:03:36'),(119,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:04:17'),(120,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:05:31'),(121,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:06:24'),(122,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-09 14:07:17'),(123,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-10 06:37:42'),(124,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0','2026-03-10 06:54:16'),(125,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-10 07:38:57'),(126,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:19:04'),(127,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:19:57'),(128,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:27:07'),(129,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:29:08'),(130,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 01:31:14'),(131,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 02:17:23'),(132,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 02:40:57'),(133,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 03:01:00'),(134,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 03:08:14'),(135,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 03:18:12'),(136,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 03:25:31'),(137,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 06:39:38'),(138,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 06:47:39'),(139,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 07:00:48'),(140,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 08:34:44'),(141,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 10:45:55'),(142,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 11:01:55'),(143,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 11:11:25'),(144,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 12:16:39'),(145,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 12:33:38'),(146,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 13:04:54'),(147,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 13:35:07'),(148,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 13:44:33'),(149,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 13:58:16'),(150,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:04:55'),(151,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-12 14:24:59'),(152,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 01:10:30'),(153,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 01:55:14'),(154,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 02:05:35'),(155,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 02:28:00'),(156,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 02:57:21'),(157,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 03:12:53'),(158,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 03:23:32'),(159,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 03:43:07'),(160,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 06:07:31'),(161,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 06:17:04'),(162,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 06:30:57'),(163,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 14:24:46'),(164,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-13 15:10:15'),(165,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 01:59:38'),(166,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 02:37:30'),(167,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 03:54:26'),(168,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 05:17:20'),(169,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 05:35:55'),(170,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 06:10:38'),(171,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 08:42:00'),(172,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 13:41:03'),(173,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 15:15:54'),(174,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-14 23:59:14'),(175,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 00:22:29'),(176,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 01:23:08'),(177,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 01:30:49'),(178,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 01:49:29'),(179,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 12:09:20'),(180,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 12:40:29'),(181,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 12:52:33'),(182,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:27:00'),(183,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:27:19'),(184,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:28:28'),(185,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:28:57'),(186,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:30:52'),(187,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-15 13:44:08'),(188,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 03:34:38'),(189,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 07:37:54'),(190,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-16 08:10:45'),(191,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 00:55:46'),(192,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 01:31:29'),(193,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 02:12:21'),(194,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 02:57:48'),(195,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 06:15:30'),(196,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 06:48:01'),(197,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 07:21:53'),(198,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 07:52:18'),(199,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-17 08:25:42'),(200,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-18 00:50:06'),(201,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-18 01:24:07'),(202,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-18 01:55:28'),(203,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-18 02:56:29'),(204,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-19 01:14:18'),(205,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-19 02:06:58'),(206,'session_timeout','Session timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36','2026-03-19 03:44:11'),(207,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-22 05:27:43'),(208,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-22 05:59:38'),(209,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-23 13:03:38'),(210,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 01:14:16'),(211,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 01:53:41'),(212,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 02:23:55'),(213,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 02:54:43'),(214,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 07:14:36'),(215,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-24 07:47:18'),(216,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 01:33:58'),(217,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 02:08:19'),(218,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 02:39:43'),(219,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 03:10:39'),(220,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 03:42:03'),(221,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 06:02:58'),(222,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 06:33:55'),(223,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 07:09:07'),(224,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 07:45:43'),(225,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 08:26:30'),(226,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 13:03:59'),(227,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-25 13:35:47'),(228,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 00:48:28'),(229,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 01:36:56'),(230,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 02:16:22'),(231,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 02:51:35'),(232,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 03:23:48'),(233,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 03:57:56'),(234,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 05:32:50'),(235,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 06:05:01'),(236,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 07:18:59'),(237,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 14:14:15'),(238,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 14:49:57'),(239,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-26 15:23:54'),(240,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 01:50:32'),(241,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 02:23:23'),(242,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 02:55:58'),(243,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 03:26:20'),(244,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 03:26:20'),(245,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 03:56:44'),(246,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 13:37:59'),(247,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-27 14:08:23'),(248,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 00:55:02'),(249,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 01:28:17'),(250,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 01:58:46'),(251,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-03-28 13:22:29'),(252,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 14:00:07'),(253,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-28 14:31:01'),(254,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 02:58:11'),(255,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 03:33:17'),(256,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 05:18:54'),(257,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 05:52:03'),(258,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 06:22:49'),(259,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 06:53:17'),(260,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 07:51:15'),(261,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-30 08:30:38'),(262,'session_timeout','Session timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-31 02:40:37'),(263,'session_timeout','Session idle timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-03-31 03:25:40'),(264,'session_timeout','Session idle timeout for user: Walton Loneza (waltonloneza@gmail.com)','medium',5,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-04-10 13:57:03'),(265,'session_timeout','Session idle timeout for user: System Administrator (admin@pims.com)','medium',1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:44');
/*!40000 ALTER TABLE `security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_metrics`
--

DROP TABLE IF EXISTS `security_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_date` date NOT NULL,
  `total_users` int(11) NOT NULL,
  `active_users` int(11) NOT NULL,
  `failed_logins` int(11) DEFAULT 0,
  `successful_logins` int(11) DEFAULT 0,
  `password_changes` int(11) DEFAULT 0,
  `security_alerts` int(11) DEFAULT 0,
  `audit_score` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`metric_date`),
  KEY `idx_metric_date` (`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_metrics`
--

LOCK TABLES `security_metrics` WRITE;
/*!40000 ALTER TABLE `security_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signatory_authorities`
--

DROP TABLE IF EXISTS `signatory_authorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signatory_authorities` (
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
  KEY `idx_office_type` (`office_id`,`signatory_type`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_effective_dates` (`effective_date`,`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Authorized signatories for LGU documents';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signatory_authorities`
--

LOCK TABLES `signatory_authorities` WRITE;
/*!40000 ALTER TABLE `signatory_authorities` DISABLE KEYS */;
/*!40000 ALTER TABLE `signatory_authorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `software`
--

DROP TABLE IF EXISTS `software`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `software` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'User ID who last updated the record',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_software_name` (`software_name`),
  KEY `idx_category` (`category`),
  KEY `idx_vendor` (`vendor`),
  KEY `idx_license_type` (`license_type`),
  KEY `idx_status` (`status`),
  KEY `idx_purchase_date` (`purchase_date`),
  KEY `idx_renewal_date` (`renewal_date`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Software licenses and installations management';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `software`
--

LOCK TABLES `software` WRITE;
/*!40000 ALTER TABLE `software` DISABLE KEYS */;
INSERT INTO `software` VALUES (1,'Microsoft Office 365','Office Suite','Productivity suite with Word, Excel, PowerPoint, Outlook','Microsoft Corporation','2023','Annual Subscription','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2024-01-15',3500.00,'2025-01-15',3500.00,'active','Administration Office','2024-01-20','Annual subscription for 10 users',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(2,'Windows 11 Pro','Operating System','Professional operating system for workstations','Microsoft Corporation','23H2','Perpetual','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2023-06-10',8500.00,NULL,0.00,'active','IT Department','2023-06-15','Volume license for 5 computers',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(3,'Kaspersky Endpoint Security','Antivirus','Business antivirus and endpoint protection','Kaspersky Lab','12.2','Annual Subscription','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2024-02-01',2800.00,'2025-02-01',2800.00,'active','IT Department','2024-02-05','Protects 15 endpoints',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(4,'Adobe Creative Cloud','Design Software','Creative suite for graphic design and video editing','Adobe Inc.','2024','Annual Subscription','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2023-12-01',4200.00,'2024-12-01',4200.00,'active','Marketing Office','2023-12-10','All apps plan for 2 users',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(5,'MySQL Community Server','Database','Open-source relational database management system','Oracle Corporation','8.0','Open Source',NULL,'2023-09-15',0.00,NULL,0.00,'active','IT Department','2023-09-20','Free community edition for web applications',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(6,'QuickBooks Desktop','Accounting','Accounting software for small business financial management','Intuit Inc.','2023','Perpetual','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2023-11-10',15000.00,NULL,0.00,'active','Accounting Office','2023-11-15','Single user license with payroll module',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(7,'Visual Studio Professional','Development Tools','Integrated development environment for software development','Microsoft Corporation','2022','Perpetual','XXXXX-XXXXX-XXXXX-XXXXX-XXXXX','2023-08-20',7500.00,NULL,0.00,'active','IT Department','2023-08-25','Professional edition for 3 developers',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(8,'Google Workspace','Office Suite','Cloud-based productivity and collaboration tools','Google LLC','2024','Monthly Subscription',NULL,'2024-01-01',1200.00,NULL,0.00,'active','All Departments','2024-01-05','Business Standard plan for 20 users',NULL,1,'2026-03-01 06:15:53',NULL,NULL),(9,'Adobe Acrobat','Acrobat','For PDF purposes','N/A','1.0','annual','2039-xxxx-xxxx-xxxx','2026-04-07',450.00,'2028-11-22',0.00,'active',NULL,NULL,NULL,NULL,5,'2026-04-07 01:18:40',NULL,NULL),(10,'Adobe Acrobat','Acrobat','For PDF purposes','N/A','1.0','annual','2039-xxxx-xxxx-xxxx','2026-04-07',450.00,NULL,0.00,'active','',NULL,'',NULL,5,'2026-04-07 02:04:06',NULL,NULL),(11,'Adobe Acrobat','Database','test','test','1','Open Source','2039-xxxx-xxxx-xxxx','2026-04-07',4500.00,'2030-10-24',6700.00,'active','BENJAMIN SANTOS','2026-04-07','N/A',NULL,5,'2026-04-07 02:05:17',NULL,NULL);
/*!40000 ALTER TABLE `software` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=2976 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (2736,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:02:34'),(2737,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:05:48'),(2738,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:05:53'),(2739,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:08:15'),(2740,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:11:07'),(2741,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:16:16'),(2742,1,'access','settings','User accessed system settings page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:17:00'),(2743,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:17:04'),(2744,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:23:57'),(2745,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:30:13'),(2746,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:33:06'),(2747,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:36:02'),(2748,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:38:23'),(2749,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:48:08'),(2750,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:50:21'),(2751,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:53:36'),(2752,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 02:56:18'),(2753,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:01:01'),(2754,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:07:36'),(2755,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:10:42'),(2756,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:11:22'),(2757,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:11:55'),(2758,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:13:32'),(2759,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:15:00'),(2760,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:17:58'),(2761,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:20:38'),(2762,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:20:44'),(2763,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:22:45'),(2764,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:23:15'),(2765,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:27:25'),(2766,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:27:31'),(2767,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:30:07'),(2768,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:30:54'),(2769,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:31:52'),(2770,1,'export','categories','System admin exported categories data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:31:55'),(2771,1,'export','categories','Categories exported successfully: 34 records','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:31:55'),(2772,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:42:53'),(2773,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:44:56'),(2774,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-18 03:46:56'),(2775,1,'login_success','authentication','User logged in: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:23:43'),(2776,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:23:43'),(2777,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:04'),(2778,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:17'),(2779,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:35'),(2780,1,'update','system_settings','Dark mode enabled','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:35'),(2781,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:44'),(2782,1,'update','system_settings','Dark mode disabled','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:44'),(2783,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:24:53'),(2784,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:29:22'),(2785,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:29:39'),(2786,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:29:40'),(2787,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:29:49'),(2788,5,'login_failed','authentication','Invalid password for user: Walton Loneza (waltonloneza@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:30:34'),(2789,5,'login_success','authentication','User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:30:49'),(2790,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:30:49'),(2791,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:30:57'),(2792,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:31:02'),(2793,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 01:31:18'),(2794,1,'logout','authentication','User logged out: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:31:28'),(2795,5,'login_success','authentication','User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:31:31'),(2796,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:31:31'),(2797,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 01:33:06'),(2798,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:33:27'),(2799,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:38:29'),(2800,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:38:31'),(2801,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:39:35'),(2802,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:39:36'),(2803,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:42:26'),(2804,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:44:50'),(2805,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:45:21'),(2806,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:48:08'),(2807,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:48:46'),(2808,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:51:22'),(2809,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:51:33'),(2810,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:55:42'),(2811,5,'logout','authentication','User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:55:56'),(2812,1,'login_success','authentication','User logged in: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:56:18'),(2813,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:56:18'),(2814,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:56:33'),(2815,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:56:50'),(2816,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 01:59:04'),(2817,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:02:07'),(2818,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:05:42'),(2819,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:05:47'),(2820,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:05:51'),(2821,5,'login_success','authentication','User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:07:53'),(2822,5,'access','admin_dashboard','Admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:07:54'),(2823,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:08:05'),(2824,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:09:40'),(2825,5,'Accessed Property Acknowledgment Receipt Form','forms','par_form.php','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:11:32'),(2826,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:11:55'),(2827,5,'Accessed Property Acknowledgment Receipt Form','forms','par_form.php','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:12:05'),(2828,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:12:22'),(2829,5,'Accessed Property Acknowledgment Receipt Form','forms','par_form.php','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:12:25'),(2830,5,'Accessed Inventory Custodian Slip Form','forms','ics_form.php','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:12:37'),(2831,5,'Accessed Requisition and Issue Slip Form','forms','ris_form.php','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 02:12:55'),(2832,1,'category_status_updated','asset_management','Updated status for category: AFFE (05-040) to active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:13:33'),(2833,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:15:46'),(2834,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:17:43'),(2835,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:17:51'),(2836,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:18:14'),(2837,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:18:23'),(2838,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:19:48'),(2839,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:23:34'),(2840,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:23:44'),(2841,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:28:06'),(2842,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:28:11'),(2843,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:28:44'),(2844,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','2026-04-20 02:28:49'),(2845,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:30:29'),(2846,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:30:42'),(2847,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:31:56'),(2848,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:32:04'),(2849,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:34:56'),(2850,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:35:03'),(2851,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:35:09'),(2852,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:35:55'),(2853,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:36:51'),(2854,1,'access','categories','System admin accessed categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:39:44'),(2855,1,'export','categories','System admin exported categories data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:40:23'),(2856,1,'export','categories','Categories exported successfully: 34 records','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:40:23'),(2857,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:40:50'),(2858,1,'access','sub_categories','System admin accessed sub categories page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:41:54'),(2859,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:42:31'),(2860,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:44:46'),(2861,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:46:27'),(2862,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:47:21'),(2863,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:48:39'),(2864,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:52:02'),(2865,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:53:00'),(2866,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:54:24'),(2867,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:54:33'),(2868,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:54:55'),(2869,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:57:10'),(2870,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:58:14'),(2871,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:58:21'),(2872,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:58:29'),(2873,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 02:59:37'),(2874,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:01:04'),(2875,1,'export','units','System admin exported units data','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:01:08'),(2876,1,'export','units','Units exported successfully: 33 records','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:01:08'),(2877,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:01:23'),(2878,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:02:43'),(2879,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:04:44'),(2880,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:05:20'),(2881,1,'office_status_updated','office_management','Updated office status: Lying in (025) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:05:20'),(2882,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 03:05:26'),(2883,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:05:41'),(2884,1,'office_status_updated','office_management','Updated office status: Lying in (025) to active','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:05:41'),(2885,5,'access','assets','Admin accessed assets page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0','2026-04-20 03:05:43'),(2886,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:06:58'),(2887,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:09:49'),(2888,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:11:24'),(2889,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:11:27'),(2890,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:13:33'),(2891,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:15:17'),(2892,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:17:27'),(2893,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:20:21'),(2894,1,'offices_import_attempt','office_management','Import attempt: 0 imported, 0 skipped, 24 errors','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:20:21'),(2895,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:21:00'),(2896,1,'offices_import_attempt','office_management','Import attempt: 0 imported, 0 skipped, 24 errors','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:21:00'),(2897,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2898,1,'office_imported','office_management','Imported office: Head Office (050)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2899,1,'office_imported','office_management','Imported office: North District (051)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2900,1,'office_imported','office_management','Imported office: South District (052)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2901,1,'office_imported','office_management','Imported office: East District (053)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2902,1,'office_imported','office_management','Imported office: West District (054)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2903,1,'office_imported','office_management','Imported office: Central District (055)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2904,1,'office_imported','office_management','Imported office: Finance Office (056)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2905,1,'office_imported','office_management','Imported office: HR Department (057)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2906,1,'office_imported','office_management','Imported office: IT Department (058)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2907,1,'office_imported','office_management','Imported office: Operations (059)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2908,1,'office_imported','office_management','Imported office: Maintenance (060)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2909,1,'office_imported','office_management','Imported office: Security (061)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2910,1,'office_imported','office_management','Imported office: Records Division (062)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2911,1,'office_imported','office_management','Imported office: Legal Department (063)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2912,1,'office_imported','office_management','Imported office: Procurement (064)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2913,1,'office_imported','office_management','Imported office: Audit Office (065)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2914,1,'office_imported','office_management','Imported office: Planning Division (066)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2915,1,'office_imported','office_management','Imported office: Public Relations (067)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2916,1,'office_imported','office_management','Imported office: Medical Clinic (068)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2917,1,'office_imported','office_management','Imported office: Training Center (069)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2918,1,'office_imported','office_management','Imported office: Warehouse (070)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2919,1,'office_imported','office_management','Imported office: Transportation (071)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2920,1,'office_imported','office_management','Imported office: Catering Services (072)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2921,1,'office_imported','office_management','Imported office: Facilities Management (073)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2922,1,'offices_import_attempt','office_management','Import attempt: 24 imported, 0 skipped, 0 errors','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:12'),(2923,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:41'),(2924,1,'office_status_updated','office_management','Updated office status: Planning Division (066) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:22:41'),(2925,1,'access','units','System Admin accessed units management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:24:54'),(2926,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:24:56'),(2927,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:25:02'),(2928,1,'office_status_updated','office_management','Updated office status: Audit Office (065) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:25:02'),(2929,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:25:07'),(2930,1,'office_status_updated','office_management','Updated office status: Catering Services (072) to inactive','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:25:07'),(2931,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:26:53'),(2932,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 03:26:58'),(2933,1,'login_success','authentication','User logged in: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:35:25'),(2934,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:35:26'),(2935,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:35:32'),(2936,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:36:13'),(2937,1,'access','offices','System admin accessed offices page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:38:50'),(2938,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:39:08'),(2939,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:40:31'),(2940,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:42:31'),(2941,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:42:55'),(2942,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 05:44:52'),(2943,1,'login_success','authentication','User logged in: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:21:52'),(2944,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:21:53'),(2945,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:22:00'),(2946,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:25:55'),(2947,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:26:04'),(2948,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:35:22'),(2949,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:37:30'),(2950,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:41:56'),(2951,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:42:36'),(2952,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:42:49'),(2953,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-20 06:43:39'),(2954,NULL,'login_failed','authentication','User not found for email: admin@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:39:08'),(2955,NULL,'login_failed','authentication','User not found for email: admin@gmail.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:39:36'),(2956,1,'session_timeout','authentication','Session idle timeout for user: System Administrator (admin@pims.com) after 64624 seconds of inactivity','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:43'),(2957,1,'login_success','authentication','User logged in: System Administrator (admin@pims.com) with role: system_admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:46'),(2958,1,'access','dashboard','System admin accessed dashboard','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:46'),(2959,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:50'),(2960,NULL,'login_failed','authentication','User not found for email: admin@pims.gov.ph','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:40:59'),(2961,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:41:05'),(2962,1,'backup_downloaded','backup_system','Downloaded backup: Daily Backup (files)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:41:19'),(2963,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:42:09'),(2964,1,'login_failed','authentication','Invalid password for user: System Administrator (admin@pims.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:42:58'),(2965,16,'login_failed','authentication','Invalid password for user: John Patrick Jazareno (lgupilar.supplyroom@gmail.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:43:19'),(2966,NULL,'login_failed','authentication','User not found for email: admin@admin.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:46:31'),(2967,NULL,'login_failed','authentication','User not found for email: admin@admin.com','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:47:01'),(2968,1,'login_failed','authentication','Invalid password for user: System Administrator (admin@pims.com)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 00:48:25'),(2969,1,'access','user_management','System admin accessed user management page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:06:09'),(2970,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:06:12'),(2971,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:06:22'),(2972,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:09:38'),(2973,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:31:19'),(2974,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:33:32'),(2975,1,'access','backup','System admin accessed backup page','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-04-21 01:37:13');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'system_name','PIMS','string','System display name','2026-01-06 03:08:21','2026-01-06 03:08:21'),(2,'system_email','waltielappy@gmail.com','string','System email address for notifications','2026-01-06 03:08:21','2026-01-06 03:08:29'),(3,'maintenance_mode','0','boolean','Enable/disable maintenance mode','2026-01-06 03:08:21','2026-01-06 03:08:21'),(4,'allow_registration','1','boolean','Allow new user registration','2026-01-06 03:08:21','2026-01-06 03:08:21'),(5,'session_timeout','60','integer','User session timeout in seconds','2026-01-06 03:08:21','2026-03-31 03:26:00'),(6,'max_login_attempts','5','integer','Maximum failed login attempts before lockout','2026-01-06 03:08:21','2026-01-06 03:08:21'),(7,'password_min_length','8','integer','Minimum password length','2026-01-06 03:08:21','2026-01-06 03:08:21'),(8,'backup_retention_days','30','integer','Number of days to keep backup files','2026-01-06 03:08:21','2026-01-06 03:08:21'),(9,'email_notifications','1','boolean','Enable email notifications','2026-01-06 03:08:21','2026-01-06 03:08:21'),(10,'debug_mode','0','boolean','Enable debug mode and error logging','2026-01-06 03:08:21','2026-01-06 03:08:21'),(11,'system_logo','img/system_logo.png','string',NULL,'2026-01-06 05:04:27','2026-01-06 05:05:32'),(12,'primary_color','#0d39e7','string',NULL,'2026-01-06 05:20:06','2026-01-06 05:21:18'),(13,'secondary_color','#5cc2f2','string',NULL,'2026-01-06 05:20:06','2026-01-06 05:20:35'),(14,'accent_color','#6b90ff','string',NULL,'2026-01-06 05:20:06','2026-01-06 05:21:36'),(16,'dark_mode','0','string',NULL,'2026-03-09 13:50:13','2026-04-20 01:24:44'),(17,'auto_save_interval','5','string',NULL,'2026-03-09 13:50:13','2026-03-31 03:26:00'),(18,'items_per_page','25','string',NULL,'2026-03-09 13:50:13','2026-03-31 03:26:00'),(19,'date_format','Y-m-d','string',NULL,'2026-03-09 13:50:13','2026-03-31 03:26:00'),(20,'time_format','24h','string',NULL,'2026-03-09 13:50:13','2026-03-31 03:26:00'),(27,'theme_preset','default','string',NULL,'2026-03-12 01:18:54','2026-03-31 03:26:00');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_formats`
--

DROP TABLE IF EXISTS `tag_formats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_type` varchar(50) NOT NULL,
  `format_components` text DEFAULT NULL,
  `auto_increment` tinyint(1) DEFAULT 0,
  `digits` int(2) DEFAULT 4,
  `separator` varchar(10) DEFAULT '-',
  `current_number` int(11) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_type` (`tag_type`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag_formats`
--

LOCK TABLES `tag_formats` WRITE;
/*!40000 ALTER TABLE `tag_formats` DISABLE KEYS */;
INSERT INTO `tag_formats` VALUES (1,'property_no','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"}]\"',1,2,'/',22,'inactive',1,1,'2026-01-08 04:12:14','2026-03-26 06:27:12'),(2,'ics_no','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"}]\"',1,2,'/',20,'inactive',1,1,'2026-01-09 09:25:27','2026-03-26 06:27:07'),(3,'itr_no','[{\"type\":\"text\",\"value\":\"ITR\"},{\"type\":\"digits\",\"digits\":4}]',1,4,'-',10,'active',1,1,'2026-01-09 09:30:48','2026-04-10 02:38:32'),(4,'par_no','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"}]\"',1,2,'/',26,'inactive',1,1,'2026-01-09 09:42:59','2026-03-26 06:27:24'),(5,'ris_no','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"digits\\\",\\\"separator\\\":\\\"-\\\",\\\"digits\\\":4}]\"',1,4,'/',9,'inactive',1,1,'2026-01-09 09:44:09','2026-03-26 06:27:45'),(6,'sai_no','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"digits\\\",\\\"separator\\\":\\\"-\\\",\\\"digits\\\":4}]\"',1,4,'/',30,'inactive',1,1,'2026-01-09 09:44:49','2026-03-26 06:27:52'),(7,'code','\"[{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"digits\\\",\\\"separator\\\":\\\"-\\\",\\\"digits\\\":4},{\\\"type\\\":\\\"month\\\",\\\"separator\\\":\\\"-\\\"}]\"',1,4,'/',30,'inactive',1,1,'2026-01-09 09:45:35','2026-03-26 06:28:09'),(8,'inventory_tag','\"[{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"form_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"text\\\",\\\"separator\\\":\\\"-\\\",\\\"value\\\":\\\"05\\\"},{\\\"type\\\":\\\"category_code\\\",\\\"separator\\\":\\\"-\\\"},{\\\"type\\\":\\\"sub_category_code\\\",\\\"separator\\\":\\\"-\\\"}]\"',1,2,'/',82,'active',NULL,5,'2026-01-23 02:53:48','2026-04-13 08:49:14'),(9,'red_tag_control','\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"RT\\\"},{\\\"type\\\":\\\"digits\\\",\\\"separator\\\":\\\"-\\\",\\\"digits\\\":4}]\"',1,4,'-',52,'active',1,1,'2026-02-01 08:06:12','2026-03-27 01:25:09'),(10,'red_tag_no','\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"PS-5S-000-000-0000\\\"}]\"',1,2,'/',38,'active',1,1,'2026-02-01 08:06:37','2026-04-05 00:27:02');
/*!40000 ALTER TABLE `tag_formats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `thresholds`
--

DROP TABLE IF EXISTS `thresholds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `thresholds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `threshold_type` varchar(50) NOT NULL,
  `threshold_value` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `thresholds`
--

LOCK TABLES `thresholds` WRITE;
/*!40000 ALTER TABLE `thresholds` DISABLE KEYS */;
INSERT INTO `thresholds` VALUES (1,'unit_cost_max',50000.00,'0','2026-01-09 13:16:48','2026-01-09 13:16:48');
/*!40000 ALTER TABLE `thresholds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `units` (
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
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Standardized units of measurement for assets and consumables';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (2,'pieces','pcs','count','Multiple individual items','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(4,'units','units','count','Multiple units of measurement','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(6,'sets','sets','count','Multiple complete sets','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(8,'pairs','pairs','count','Multiple pairs','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(10,'dozens','dozens','count','Multiple dozens','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(12,'boxes','boxes','count','Multiple boxes','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(14,'cartons','cartons','count','Multiple cartons','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(16,'packs','packs','count','Multiple packs','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(18,'packages','packages','count','Multiple packages','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(20,'bags','bags','count','Multiple bags','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(22,'containers','containers','count','Multiple containers','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(24,'bottles','bottles','count','Multiple bottles','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(26,'reams','reams','count','Multiple reams','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(28,'kilograms','kgs','weight','Multiple kilograms','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(30,'grams','gs','weight','Multiple grams','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(32,'tons','tons','weight','Multiple tons','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(34,'meters','ms','length','Multiple meters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(36,'centimeters','cms','length','Multiple centimeters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(38,'kilometers','kms','length','Multiple kilometers','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(40,'feet','fts','length','Multiple feet','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(42,'inches','ins','length','Multiple inches','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(44,'liters','liters','volume','Multiple liters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(46,'milliliters','mls','volume','Multiple milliliters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(48,'cubic_meters','m3s','volume','Multiple cubic meters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(50,'square_meters','m2s','area','Multiple square meters','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(52,'hectares','has','area','Multiple hectares','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(54,'hours','hrs','time','Multiple hours','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(56,'days','days','time','Multiple days','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(58,'months','mos','time','Multiple months','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(60,'years','yrs','time','Multiple years','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(62,'rolls','rolls','other','Multiple rolls','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(64,'sheets','sheets','other','Multiple sheets','active','2026-03-19 03:25:32','2026-03-19 03:25:32',NULL,NULL),(65,'lots','lot','area','','active','2026-04-09 11:59:38','2026-04-09 11:59:38',1,NULL);
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_password_history`
--

DROP TABLE IF EXISTS `user_password_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_password_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_password_history`
--

LOCK TABLES `user_password_history` WRITE;
/*!40000 ALTER TABLE `user_password_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_password_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `office` int(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('system_admin','admin','office_admin','user','fuel','main_user') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `password_changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `must_change_password` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_office` (`office`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`office`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'system_admin','admin@pims.com','','',NULL,'$2y$10$sTwhCxd.JawevaKAgnfMaO1p.PJ34C9ROfU4nbTkmuHHdDOzcq/nm','system_admin','System','Administrator',1,'2026-01-03 13:00:37','2026-01-04 13:21:03',NULL,0,0,'2026-01-06 02:21:26',0),(2,'wjll2022-2920-98466@bicol-u.edu.ph','wjll2022-2920-98466@bicol-u.edu.ph',NULL,NULL,5,'$2y$10$0mPC7iEVtjGUOVHLqGdmNe5whIhEuPVQfmdliPsnSdupq20au5cl2','admin','Walton','loneza',1,'2026-01-03 22:34:21','2026-02-25 01:39:23',NULL,0,0,'2026-01-06 02:21:26',0),(4,'notlawsfinds@gmail.com','notlawsfinds@gmail.com',NULL,NULL,2,'$2y$10$ekzQ67QhSp7H3QhmLyjbxeUwgXPw4d35vEm0mlbQX98WGDJvVRkry','office_admin','Joshua ','Escano',1,'2026-01-03 22:44:32','2026-02-25 01:39:20',NULL,0,0,'2026-01-06 02:21:26',0),(5,'waltonloneza@gmail.com','waltonloneza@gmail.com','','',3,'$2y$10$2P2Q00QrNIcMU/paGbgE8u5.mBKaZqgZIf.wolQlRiPvnXx4/aydi','admin','Walton','Loneza',1,'2026-01-03 22:49:38','2026-02-28 01:13:24',NULL,0,0,'2026-01-06 02:21:26',0),(11,'waltielappy@gmail.com','waltielappy@gmail.com',NULL,NULL,5,'$2y$10$hO1CH2GRcHTr81fLfLGokOk6kTlm9zja8X4ipgsq3Pb1ffMFS5bmu','user','Elton John','Moises',0,'2026-01-04 00:39:40','2026-02-25 01:39:12',NULL,0,0,'2026-01-06 02:21:26',0),(13,'ejbm2022-9110-55459@bicol-u.edu.ph','ejbm2022-9110-55459@bicol-u.edu.ph',NULL,NULL,1,'$2y$10$o54U6aFysIeH5wTqGKNiN.pYkUhYuvpyfdyNFerUZF/RSTbwg/RRa','user','Elton','Moises',1,'2026-02-10 13:03:28','2026-02-25 01:39:01',NULL,0,0,'2026-02-10 13:03:28',0),(16,'lgupilar.supplyroom@gmail.com','lgupilar.supplyroom@gmail.com',NULL,NULL,3,'$2y$10$w4FzikJXfEqNn5ulfCZkaejz9v8KEPz4NV7QYFa0F/g9JQDqoAMZa','admin','John Patrick','Jazareno',1,'2026-02-25 01:41:01','2026-02-25 01:41:01',NULL,0,0,'2026-02-25 01:41:01',0),(17,'joshuamarifrancis@gmail.com','joshuamarifrancis@gmail.com','','',5,'$2y$10$mLtzMicopmz6FtuqgBzXEulfmAZGt5eCjPiBs47ZWwORY2njUv0yK','office_admin','Joshua','Escaño',1,'2026-02-25 08:40:28','2026-03-04 08:00:07',NULL,0,0,'2026-02-25 08:40:28',0),(18,'OM@pims.com','OM@pims.com',NULL,NULL,4,'$2y$10$6kUby429S74/f.Kd.400iO5vhbNYm8Xjjya5n7hlShCbiSdusmDyq','office_admin','OM','admin',1,'2026-03-03 03:38:17','2026-03-03 03:38:17',NULL,0,0,'2026-03-03 03:38:17',0),(19,'AD@pims.com','AD@pims.com',NULL,NULL,5,'$2y$10$01nH1ThL4s.XHcVy.ixNNexdNc9MgRfnSuhQ5xuUFItgNXuWFs8e2','admin','admin','admin',1,'2026-03-03 06:11:33','2026-03-03 06:11:33',NULL,0,0,'2026-03-03 06:11:33',0),(20,'ks@gmail.com','ks@gmail.com',NULL,NULL,3,'$2y$10$4rZkPhSS11.Bh4Wi96T0YOLMCnnQCKajPiFooI24nb5GkRLiiW4Cm','fuel','Kenneth','Sy',1,'2026-03-11 14:01:24','2026-03-11 14:01:24',NULL,0,0,'2026-03-11 14:01:24',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `v_consumable_usage_trends`
--

DROP TABLE IF EXISTS `v_consumable_usage_trends`;
/*!50001 DROP VIEW IF EXISTS `v_consumable_usage_trends`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_consumable_usage_trends` AS SELECT
 1 AS `consumable_id`,
  1 AS `consumable_description`,
  1 AS `units`,
  1 AS `consumption_count`,
  1 AS `total_consumed`,
  1 AS `avg_consumption_per_transaction`,
  1 AS `lowest_stock_level`,
  1 AS `last_consumed_date` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_consumption_summary`
--

DROP TABLE IF EXISTS `v_consumption_summary`;
/*!50001 DROP VIEW IF EXISTS `v_consumption_summary`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_consumption_summary` AS SELECT
 1 AS `id`,
  1 AS `consumable_id`,
  1 AS `consumable_description`,
  1 AS `quantity_consumed`,
  1 AS `remaining_quantity`,
  1 AS `user_name`,
  1 AS `user_email`,
  1 AS `office_name`,
  1 AS `consumed_at`,
  1 AS `purpose`,
  1 AS `reference_number`,
  1 AS `consumable_units`,
  1 AS `consumable_unit_cost`,
  1 AS `total_cost` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_monthly_office_consumption`
--

DROP TABLE IF EXISTS `v_monthly_office_consumption`;
/*!50001 DROP VIEW IF EXISTS `v_monthly_office_consumption`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_monthly_office_consumption` AS SELECT
 1 AS `month_year`,
  1 AS `office_id`,
  1 AS `office_name`,
  1 AS `total_transactions`,
  1 AS `total_quantity_consumed`,
  1 AS `total_cost` */;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'pims'
--

--
-- Final view structure for view `asset_category_tables`
--

/*!50001 DROP VIEW IF EXISTS `asset_category_tables`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `asset_category_tables` AS select `ac`.`id` AS `category_id`,`ac`.`category_name` AS `category_name`,`ac`.`category_code` AS `category_code`,case `ac`.`category_code` when 'FF' then 'asset_furniture' when 'CE' then 'asset_computers' when 'VH' then 'asset_vehicles' when 'ME' then 'asset_machinery' when 'BI' then 'asset_buildings' when 'LD' then 'asset_land' when 'SW' then 'asset_software' when 'OE' then 'asset_office_equipment' else NULL end AS `specific_table_name` from `asset_categories` `ac` where `ac`.`status` = 'active' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `consumable_release_history_view`
--

/*!50001 DROP VIEW IF EXISTS `consumable_release_history_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `consumable_release_history_view` AS select `h`.`id` AS `id`,`h`.`consumable_id` AS `consumable_id`,`h`.`description` AS `description`,`h`.`quantity_released` AS `quantity_released`,`h`.`unit_cost` AS `unit_cost`,`h`.`total_value` AS `total_value`,`h`.`from_office_id` AS `from_office_id`,`fo`.`office_name` AS `from_office_name`,`h`.`to_office_id` AS `to_office_id`,`to_off`.`office_name` AS `to_office_name`,`h`.`released_by` AS `released_by`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,concat(`u`.`first_name`,' ',`u`.`last_name`) AS `released_by_name`,`h`.`release_date` AS `release_date`,`h`.`notes` AS `notes`,`h`.`created_at` AS `created_at` from (((`consumable_release_history` `h` left join `offices` `fo` on(`h`.`from_office_id` = `fo`.`id`)) left join `offices` `to_off` on(`h`.`to_office_id` = `to_off`.`id`)) left join `users` `u` on(`h`.`released_by` = `u`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `fund_allocation_summary`
--

/*!50001 DROP VIEW IF EXISTS `fund_allocation_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fund_allocation_summary` AS select `fa`.`id` AS `id`,`fa`.`fund_id` AS `fund_id`,`fa`.`office_id` AS `office_id`,`o`.`office_name` AS `office_name`,`f`.`fund_code` AS `fund_code`,`f`.`fund_name` AS `fund_name`,`f`.`fund_cluster` AS `fund_cluster`,`fa`.`allocated_amount` AS `allocated_amount`,`fa`.`utilized_amount` AS `utilized_amount`,`fa`.`remaining_balance` AS `remaining_balance`,`fa`.`allocation_date` AS `allocation_date`,`fa`.`status` AS `status`,round(`fa`.`utilized_amount` / `fa`.`allocated_amount` * 100,2) AS `utilization_percentage`,`fa`.`created_at` AS `created_at`,`fa`.`updated_at` AS `updated_at` from ((`fund_allocations` `fa` join `funds` `f` on(`fa`.`fund_id` = `f`.`id`)) join `offices` `o` on(`fa`.`office_id` = `o`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `fund_summary`
--

/*!50001 DROP VIEW IF EXISTS `fund_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fund_summary` AS select `f`.`id` AS `id`,`f`.`fund_code` AS `fund_code`,`f`.`fund_name` AS `fund_name`,`f`.`fund_cluster` AS `fund_cluster`,`f`.`description` AS `description`,`f`.`department` AS `department`,`f`.`budget_year` AS `budget_year`,`f`.`initial_amount` AS `initial_amount`,`f`.`current_balance` AS `current_balance`,`f`.`status` AS `status`,`f`.`start_date` AS `start_date`,`f`.`end_date` AS `end_date`,count(`ft`.`id`) AS `transaction_count`,coalesce(sum(case when `ft`.`transaction_type` = 'expenditure' then `ft`.`amount` else 0 end),0) AS `total_expenditures`,coalesce(sum(case when `ft`.`transaction_type` = 'allocation' then `ft`.`amount` else 0 end),0) AS `total_allocations`,`f`.`created_at` AS `created_at`,`f`.`updated_at` AS `updated_at` from (`funds` `f` left join `fund_transactions` `ft` on(`f`.`id` = `ft`.`fund_id`)) group by `f`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `itr_summary`
--

/*!50001 DROP VIEW IF EXISTS `itr_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `itr_summary` AS select `itr`.`id` AS `id`,`itr`.`entity_name` AS `entity_name`,`itr`.`fund_cluster` AS `fund_cluster`,`itr`.`itr_no` AS `itr_no`,`itr`.`from_office` AS `from_office`,`itr`.`to_office` AS `to_office`,`itr`.`transfer_date` AS `transfer_date`,`itr`.`transfer_type` AS `transfer_type`,`itr`.`end_user` AS `end_user`,`itr`.`purpose` AS `purpose`,`itr`.`status` AS `status`,`itr`.`total_amount` AS `total_amount`,count(`ii`.`id`) AS `item_count`,`itr`.`created_by` AS `created_by`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`itr`.`created_at` AS `created_at`,`itr`.`updated_at` AS `updated_at` from ((`itr_forms` `itr` left join `itr_items` `ii` on(`itr`.`id` = `ii`.`form_id`)) left join `users` `u` on(`itr`.`created_by` = `u`.`id`)) group by `itr`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `ris_summary`
--

/*!50001 DROP VIEW IF EXISTS `ris_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `ris_summary` AS select `rf`.`id` AS `id`,`rf`.`ris_no` AS `ris_no`,`rf`.`sai_no` AS `sai_no`,`rf`.`code` AS `code`,`rf`.`division` AS `division`,`rf`.`office` AS `office`,`rf`.`responsibility_center` AS `responsibility_center`,`rf`.`date` AS `date`,`rf`.`purpose` AS `purpose`,`rf`.`status` AS `status`,`rf`.`total_amount` AS `total_amount`,count(`ri`.`id`) AS `item_count`,`rf`.`created_by` AS `created_by`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`rf`.`created_at` AS `created_at`,`rf`.`updated_at` AS `updated_at` from ((`ris_forms` `rf` left join `ris_items` `ri` on(`rf`.`id` = `ri`.`ris_form_id`)) left join `users` `u` on(`rf`.`created_by` = `u`.`id`)) group by `rf`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_consumable_usage_trends`
--

/*!50001 DROP VIEW IF EXISTS `v_consumable_usage_trends`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_consumable_usage_trends` AS select `ch`.`consumable_id` AS `consumable_id`,`ch`.`consumable_description` AS `consumable_description`,`c`.`units` AS `units`,count(0) AS `consumption_count`,sum(`ch`.`quantity_consumed`) AS `total_consumed`,avg(`ch`.`quantity_consumed`) AS `avg_consumption_per_transaction`,min(`ch`.`remaining_quantity`) AS `lowest_stock_level`,max(`ch`.`consumed_at`) AS `last_consumed_date` from (`consume_history` `ch` left join `consumables` `c` on(`ch`.`consumable_id` = `c`.`id`)) group by `ch`.`consumable_id`,`ch`.`consumable_description`,`c`.`units` order by sum(`ch`.`quantity_consumed`) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_consumption_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_consumption_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_consumption_summary` AS select `ch`.`id` AS `id`,`ch`.`consumable_id` AS `consumable_id`,`ch`.`consumable_description` AS `consumable_description`,`ch`.`quantity_consumed` AS `quantity_consumed`,`ch`.`remaining_quantity` AS `remaining_quantity`,`ch`.`user_name` AS `user_name`,`ch`.`user_email` AS `user_email`,`ch`.`office_name` AS `office_name`,`ch`.`consumed_at` AS `consumed_at`,`ch`.`purpose` AS `purpose`,`ch`.`reference_number` AS `reference_number`,`c`.`units` AS `consumable_units`,`c`.`unit_cost` AS `consumable_unit_cost`,`ch`.`quantity_consumed` * `c`.`unit_cost` AS `total_cost` from (`consume_history` `ch` left join `consumables` `c` on(`ch`.`consumable_id` = `c`.`id`)) order by `ch`.`consumed_at` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_monthly_office_consumption`
--

/*!50001 DROP VIEW IF EXISTS `v_monthly_office_consumption`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_monthly_office_consumption` AS select date_format(`ch`.`consumed_at`,'%Y-%m') AS `month_year`,`ch`.`office_id` AS `office_id`,`ch`.`office_name` AS `office_name`,count(0) AS `total_transactions`,sum(`ch`.`quantity_consumed`) AS `total_quantity_consumed`,sum(`ch`.`quantity_consumed` * `c`.`unit_cost`) AS `total_cost` from (`consume_history` `ch` left join `consumables` `c` on(`ch`.`consumable_id` = `c`.`id`)) group by date_format(`ch`.`consumed_at`,'%Y-%m'),`ch`.`office_id`,`ch`.`office_name` order by date_format(`ch`.`consumed_at`,'%Y-%m') desc,`ch`.`office_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-21  9:37:15
