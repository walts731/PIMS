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
