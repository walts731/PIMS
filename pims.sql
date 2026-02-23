-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 18, 2026 at 04:48 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pims`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_categories_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_categories_id`, `description`, `unit`, `quantity`, `unit_cost`, `office_id`, `created_at`, `updated_at`) VALUES
(23, NULL, 'Laptop AMD Ryzen', 'Pieces', 2, 22500.00, 4, '2026-01-23 08:25:02', '2026-01-23 08:25:02'),
(24, 2, 'Computer desktop i7', 'Units', 1, 40000.00, 1, '2026-01-23 08:40:32', '2026-01-23 12:07:38'),
(25, NULL, 'Mouse', 'Pieces', 1, 340.00, 2, '2026-01-23 08:43:34', '2026-01-23 08:43:34'),
(26, NULL, 'Hilux Van (MR-2025-00033)', 'Units', 1, 45000.00, 4, '2026-01-23 08:46:41', '2026-01-23 08:46:41'),
(27, NULL, 'Office Table – Wooden', 'Units', 1, 50000.00, 3, '2026-01-23 08:50:11', '2026-01-23 08:50:11'),
(28, NULL, 'Printer Epson', 'Units', 1, 45000.00, 2, '2026-01-23 08:53:00', '2026-01-23 08:53:00'),
(32, NULL, 'Power Drill (corded) ', 'Units', 1, 56000.00, 5, '2026-01-23 08:56:55', '2026-01-23 08:56:55'),
(33, NULL, 'SDD', 'Units', 1, 52000.00, 1, '2026-01-23 09:35:20', '2026-01-23 09:35:20'),
(34, 2, 'Laptop: Lenovo IdeaPad Slim 3 (i5, 16GB RAM)', 'Pieces', 2, 38500.00, 1, '2026-01-25 12:00:36', '2026-01-25 12:14:43'),
(35, 2, 'Apple MacBook Pro 14-inch (Space Grey)', 'Pieces', 1, 110990.00, 1, '2026-01-25 12:28:27', '2026-01-25 12:29:51'),
(36, 2, 'ASUS Vivobook 16 (M1605YA-MB155WS)', 'Units', 1, 33995.00, 4, '2026-01-25 12:44:27', '2026-01-25 12:46:38'),
(37, 2, 'Dell PowerEdge T160 Tower Server', 'Units', 1, 97000.00, 4, '2026-01-25 12:51:40', '2026-01-25 12:55:19'),
(38, 2, 'iPad Air 11-inch (M2 Chip)', 'Units', 1, 42990.00, 4, '2026-01-25 13:01:20', '2026-01-25 13:02:27'),
(39, 2, 'Dell OptiPlex SFF (Plus 7010 Series)', 'Pieces', 1, 41400.00, 4, '2026-01-25 13:05:36', '2026-01-25 13:09:10'),
(40, 2, 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', 'Units', 1, 89999.00, 4, '2026-01-25 13:11:47', '2026-01-25 13:12:40'),
(42, NULL, 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', 'Pieces', 1, 89999.00, 1, '2026-01-27 01:53:39', '2026-01-27 01:53:39'),
(43, 2, 'Apple MacBook Air 13.6\" (2022/2025 Refreshed Pricing)', 'Units', 2, 48500.00, 1, '2026-01-27 09:45:50', '2026-01-27 09:58:23'),
(44, 2, 'ASUS A3402 All-in-One Desktop', 'Units', 3, 42995.00, 4, '2026-01-27 10:06:00', '2026-01-28 13:20:19'),
(45, 2, 'HP Laptop 15-fd1168TU', 'Units', 2, 49990.00, 4, '2026-01-27 10:09:51', '2026-01-27 10:30:20'),
(46, 2, 'Laptop AMD Ryzen', 'Sets', 5, 13140.00, 4, '2026-01-28 13:18:38', '2026-02-04 23:48:34');

-- --------------------------------------------------------

--
-- Table structure for table `asset_buildings`
--

CREATE TABLE `asset_buildings` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `useful_life_years` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `category_name`, `category_code`, `description`, `depreciation_rate`, `useful_life_years`, `status`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Furniture & Fixtures', 'FF', 'Office furniture, chairs, desks, cabinets, and other fixtures', 10.00, 7, 'active', '2026-01-06 06:08:57', '2026-01-07 11:40:36', 1, 1),
(2, 'Computer Equipment', 'ITS', 'Desktop computers, laptops, servers, and related peripherals', 33.33, 3, 'active', '2026-01-06 06:08:57', '2026-01-06 07:02:38', 1, 1),
(3, 'Vehicles', 'VH', 'Company vehicles, cars, trucks, and transportation equipment', 20.00, 5, 'active', '2026-01-06 06:08:57', '2026-01-06 06:08:57', 1, NULL),
(4, 'Machinery & Equipment', 'ME', 'Industrial machinery, production equipment, and tools', 15.00, 10, 'active', '2026-01-06 06:08:57', '2026-01-06 06:08:57', 1, NULL),
(6, 'Land', 'LD', 'Land and land improvements (non-depreciable)', 0.00, 0, 'active', '2026-01-06 06:08:57', '2026-01-06 07:00:27', 1, 1),
(7, 'Software', 'SW', 'Licensed software, applications, and digital assets', 33.33, 3, 'active', '2026-01-06 06:08:57', '2026-01-06 06:08:57', 1, NULL),
(8, 'Office Equipment', 'OE', 'Printers, scanners, phones, and general office equipment', 20.00, 5, 'active', '2026-01-06 06:08:57', '2026-01-06 06:08:57', 1, NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `asset_category_tables`
-- (See below for the actual view)
--
CREATE TABLE `asset_category_tables` (
`category_id` int(11)
,`category_name` varchar(100)
,`category_code` varchar(10)
,`specific_table_name` varchar(22)
);

-- --------------------------------------------------------

--
-- Table structure for table `asset_computers`
--

CREATE TABLE `asset_computers` (
  `id` int(11) NOT NULL,
  `asset_item_id` int(11) NOT NULL,
  `processor` varchar(100) DEFAULT NULL,
  `ram_capacity` varchar(20) DEFAULT NULL,
  `storage_type` enum('hdd','ssd','hybrid') DEFAULT 'hdd',
  `storage_capacity` varchar(20) DEFAULT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_computers`
--

INSERT INTO `asset_computers` (`id`, `asset_item_id`, `processor`, `ram_capacity`, `storage_type`, `storage_capacity`, `graphics_card`, `operating_system`, `mac_address`, `ip_address`, `serial_number`, `warranty_provider`, `warranty_expiry`, `purchase_date`, `last_service_date`, `condition_status`, `assigned_to`, `department`, `notes`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(2, 57, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB Unified Memory', 'hdd', '512GB SSD', NULL, 'macOS Sequoia', NULL, NULL, '145234345', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 12:29:51', '2026-01-25 12:29:51', 5, NULL),
(3, 58, 'AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)', '8GB DDR4 (Upgradable', 'hdd', '512GB M.2 NVMe™ PCIe', NULL, 'Windows® 11 Home', NULL, NULL, '3474665876', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 12:46:38', '2026-01-25 12:46:38', 5, NULL),
(4, 59, 'Intel® Xeon® E-2434 (4C/8T, 3.4GHz)', '16GB DDR5 ECC UDIMM', 'hdd', '2TB 7.2K RPM SATA 6G', NULL, 'Windows Server 2022 (Standard Edition)', NULL, NULL, '2376688978', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 12:55:19', '2026-01-25 12:55:19', 5, NULL),
(5, 60, 'Apple M2 Chip (8-core CPU / 9-core GPU)', '8GB Unified Memory', 'hdd', '128GB SSD', NULL, 'iPadOS 19', NULL, NULL, '3454345423', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 13:02:27', '2026-01-25 13:02:27', 5, NULL),
(6, 61, 'Intel® Core™ i3-13100 (Up to 4.5GHz)', '8GB DDR5 4800MHz', 'hdd', '256GB M.2 PCIe NVMe ', NULL, 'Windows® 11 Pro', NULL, NULL, '3678786564643', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 13:09:10', '2026-01-25 13:09:10', 5, NULL),
(7, 62, 'Intel® Core™ i9-14900HX (24 Cores, up to 5.8GHz)', '16GB DDR5 (Upgradabl', 'hdd', '1TB NVMe Gen 4 SSD', NULL, 'Windows® 11 Home', NULL, NULL, '456645342255', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-25 13:12:40', '2026-01-25 13:12:40', 5, NULL),
(8, 64, 'Apple M2 Chip (8-core CPU / 8-core GPU)', '8GB Unified Memory', 'hdd', '256GB SSD', NULL, 'macOS Sequoia', NULL, NULL, '367878656464334', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-27 09:46:42', '2026-01-27 09:46:42', 5, NULL),
(9, 65, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB LPDDR5-4800MHz ', 'hdd', '128GB SSD', NULL, 'macOS Sequoia', NULL, NULL, '367878656464321', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-27 09:58:23', '2026-01-27 09:58:23', 5, NULL),
(10, 69, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB LPDDR5-4800MHz ', 'hdd', '128GB SSD', NULL, 'macOS Sequoia', NULL, NULL, '3678786564643111', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-27 10:11:00', '2026-01-27 10:11:00', 5, NULL),
(11, 70, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB LPDDR5-4800MHz ', 'hdd', '2TB 7.2K RPM SATA 6G', NULL, 'Windows® 11 Home Single Language', NULL, NULL, '456733245hg45', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-27 10:30:20', '2026-01-27 10:30:20', 5, NULL),
(12, 67, '', '', 'hdd', '', NULL, '', NULL, NULL, '', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-28 13:09:26', '2026-01-28 13:09:26', 1, NULL),
(15, 71, 'Intel® Xeon® E-2434 (4C/8T, 3.4GHz)', '16GB Unified Memory', 'hdd', '512GB M.2 NVMe™ PCIe', NULL, 'Windows® 11 Home', NULL, NULL, '456733245hg45', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-28 13:19:40', '2026-01-28 13:19:40', 5, NULL),
(16, 68, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB LPDDR5-4800MHz ', 'hdd', '2TB 7.2K RPM SATA 6G', NULL, 'macOS Sequoia', NULL, NULL, '456645342255', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-28 13:20:19', '2026-01-28 13:20:19', 5, NULL),
(17, 72, 'AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)', '8GB DDR4 (Upgradable', 'hdd', '2TB 7.2K RPM SATA 6G', NULL, 'macOS Sequoia', NULL, NULL, '3474665876', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-28 13:23:15', '2026-01-28 13:23:15', 5, NULL),
(18, 73, 'Apple M3 Chip (8-core CPU, 10-core GPU)', '16GB LPDDR5-4800MHz ', 'hdd', '2TB 7.2K RPM SATA 6G', NULL, 'macOS Sequoia', NULL, NULL, '456645342255', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-01-29 13:26:25', '2026-01-29 13:26:25', 5, NULL),
(19, 74, 'AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz)', '16GB LPDDR5-4800MHz ', 'hdd', '512GB SSD M.2 2242 P', NULL, 'Windows® 11 Home Single Language', NULL, NULL, '3678786564643676', NULL, NULL, NULL, NULL, 'good', NULL, NULL, NULL, '2026-02-04 23:48:34', '2026-02-04 23:48:34', 5, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `asset_furniture`
--

CREATE TABLE `asset_furniture` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_items`
--

CREATE TABLE `asset_items` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `end_user` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `ics_id` int(11) DEFAULT NULL,
  `par_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `property_number` varchar(100) DEFAULT NULL,
  `property_no` varchar(100) DEFAULT NULL,
  `inventory_tag` varchar(100) DEFAULT NULL,
  `date_counted` date DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','disposed','unserviceable','no_tag','serviceable','pending_tag','red_tagged') NOT NULL DEFAULT 'no_tag',
  `value` decimal(10,2) DEFAULT 0.00,
  `acquisition_date` date DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_items`
--

INSERT INTO `asset_items` (`id`, `asset_id`, `employee_id`, `end_user`, `category_id`, `ics_id`, `par_id`, `description`, `unit`, `property_number`, `property_no`, `inventory_tag`, `date_counted`, `image`, `qr_code`, `status`, `value`, `acquisition_date`, `office_id`, `created_at`, `last_updated`) VALUES
(45, 23, NULL, NULL, NULL, NULL, 6, 'Laptop AMD Ryzen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'no_tag', 22500.00, '0000-00-00', 4, '2026-01-23 08:25:02', '2026-01-23 08:25:02'),
(46, 23, NULL, NULL, NULL, NULL, 6, 'Laptop AMD Ryzen', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'no_tag', 22500.00, '0000-00-00', 4, '2026-01-23 08:25:02', '2026-01-23 08:25:02'),
(47, 24, 3, NULL, 2, NULL, 8, 'Computer desktop i7', NULL, 'PILAR-ITS-2026-002', 'PN-2019-05-02-0001-0134', 'INV-2026-00009', '2026-01-23', '', NULL, 'red_tagged', 40000.00, '0000-00-00', 1, '2026-01-23 08:40:32', '2026-02-01 09:23:51'),
(48, 25, 1, NULL, NULL, NULL, 9, 'Mouse', NULL, 'PILAR-ITS-2026-003', 'PN-2019-05-02-0001-0156', 'INV-2026-00007', '2026-01-23', '', NULL, 'serviceable', 340.00, '0000-00-00', 2, '2026-01-23 08:43:34', '2026-01-23 11:50:06'),
(49, 26, NULL, NULL, NULL, NULL, 10, 'Hilux Van (MR-2025-00033)', NULL, 'PILAR-ITS-2026-002', NULL, NULL, NULL, NULL, NULL, 'no_tag', 45000.00, '0000-00-00', 4, '2026-01-23 08:46:41', '2026-01-23 08:46:41'),
(50, 27, NULL, NULL, NULL, NULL, 11, 'Office Table – Wooden', NULL, 'PILAR-ITS-2026-0025', NULL, NULL, NULL, NULL, NULL, 'no_tag', 50000.00, '0000-00-00', 3, '2026-01-23 08:50:11', '2026-01-23 08:50:11'),
(51, 28, 2, NULL, 2, NULL, 12, 'Printer Epson', NULL, 'PILAR-ITS-2026-00253', 'PN-2019-05-02-0001-01', 'INV-2026-00008', '2026-01-23', '', NULL, 'serviceable', 45000.00, '0000-00-00', 2, '2026-01-23 08:53:00', '2026-01-23 12:04:06'),
(53, 32, 3, NULL, NULL, NULL, 16, 'Power Drill (corded) ', NULL, 'PILAR-ITS-2026-0033', NULL, NULL, NULL, NULL, NULL, 'pending_tag', 56000.00, '2026-01-23', 5, '2026-01-23 08:56:55', '2026-01-23 10:04:53'),
(54, 33, NULL, NULL, NULL, NULL, 17, 'SDD', NULL, NULL, 'PAR-0002', NULL, NULL, NULL, NULL, 'pending_tag', 52000.00, '2026-01-23', 1, '2026-01-23 09:35:20', '2026-01-23 09:51:28'),
(55, 34, 3, NULL, 2, 10, NULL, 'Laptop: Lenovo IdeaPad Slim 3 (i5, 16GB RAM)', NULL, NULL, 'PN-2019-05-02-0001-0123', 'INV-2026-00010', '2026-01-25', '', NULL, 'serviceable', 38500.00, '2026-01-25', 1, '2026-01-25 12:00:36', '2026-01-25 12:02:30'),
(56, 34, 3, NULL, 2, 10, NULL, 'Laptop: Lenovo IdeaPad Slim 3 (i5, 16GB RAM)', NULL, NULL, 'ITM-55-13453', 'INV-2026-00011', '2026-01-25', 'asset_56_1769343283.jpeg', NULL, 'serviceable', 38500.00, '2026-01-25', 1, '2026-01-25 12:00:36', '2026-01-25 12:14:43'),
(57, 35, 3, NULL, 2, NULL, 18, 'Apple MacBook Pro 14-inch (Space Grey)', NULL, NULL, 'PAR-0003', 'INV-2026-00012', '2026-01-25', '', NULL, 'serviceable', 110990.00, '2026-01-25', 1, '2026-01-25 12:28:27', '2026-01-25 12:29:51'),
(58, 36, 2, NULL, 2, 11, NULL, 'ASUS Vivobook 16 (M1605YA-MB155WS)', NULL, NULL, 'MR-2025-0002023', 'INV-2026-00013', '2026-01-25', 'asset_58_1769345198.jpg', NULL, 'serviceable', 33995.00, '2026-01-25', 4, '2026-01-25 12:44:27', '2026-01-25 12:46:38'),
(59, 37, 3, NULL, 2, NULL, 19, 'Dell PowerEdge T160 Tower Server', NULL, NULL, 'PAR-0004', 'INV-2026-00014', '2026-01-25', '', NULL, 'serviceable', 97000.00, '2026-01-25', 4, '2026-01-25 12:51:40', '2026-01-25 12:55:19'),
(60, 38, 1, NULL, 2, 12, NULL, 'iPad Air 11-inch (M2 Chip)', NULL, NULL, 'ITM-55-13453', 'INV-2026-00015', '2026-01-25', '', 'qr_asset_60_1769346147.png', 'serviceable', 42990.00, '2026-01-25', 4, '2026-01-25 13:01:20', '2026-01-25 13:02:27'),
(61, 39, 1, NULL, 2, 13, NULL, 'Dell OptiPlex SFF (Plus 7010 Series)', NULL, NULL, 'PAR-0006', 'INV-2026-00016', '2026-01-25', '', 'qr_asset_61_1769346550.png', 'serviceable', 41400.00, '2026-01-25', 4, '2026-01-25 13:05:36', '2026-01-25 13:09:10'),
(62, 40, 1, 'Angela Rizal', 2, NULL, 20, 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', NULL, NULL, 'PAR-0007', 'INV-2026-00017', '2026-01-25', '', 'qr_asset_62_1769346760.png', 'serviceable', 89999.00, '2026-01-25', 4, '2026-01-25 13:11:47', '2026-01-27 12:00:37'),
(63, 42, NULL, NULL, NULL, NULL, 22, 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', 'Pieces', NULL, 'PAR-0008', NULL, NULL, NULL, NULL, 'no_tag', 89999.00, '2026-01-27', 1, '2026-01-27 01:53:39', '2026-01-27 01:53:39'),
(64, 43, 6, NULL, 2, 14, NULL, 'Apple MacBook Air 13.6\" (2022/2025 Refreshed Pricing)', '0', NULL, 'PAR-0010', 'INV-2026-00018', '2026-01-27', '', 'qr_asset_64_1769507202.png', 'serviceable', 48500.00, '2026-01-27', 1, '2026-01-27 09:45:50', '2026-01-27 09:46:42'),
(65, 43, 1, '0', 2, 14, NULL, 'Apple MacBook Air 13.6\" (2022/2025 Refreshed Pricing)', '0', NULL, 'PAR-0019', 'INV-2026-00019', '2026-01-27', 'asset_65_1769507903.jpg', 'qr_asset_65_1769507903.png', 'serviceable', 48500.00, '2026-01-27', 1, '2026-01-27 09:45:50', '2026-01-27 09:58:23'),
(66, 44, 1, 'Roberto Cruz', 2, 15, NULL, 'ASUS A3402 All-in-One Desktop', '0', NULL, 'PAR-0053', 'INV-2026-00022', '2026-01-28', NULL, NULL, 'serviceable', 42995.00, '2026-01-27', 4, '2026-01-27 10:06:00', '2026-01-28 13:01:53'),
(67, 44, 1, 'Test User for Debug', 2, 15, NULL, 'ASUS A3402 All-in-One Desktop', '0', NULL, 'PAR-TEST-001', 'INV-2026-TEST-001', '2026-01-28', '', 'qr_asset_67_1769605766.png', 'serviceable', 42995.00, '2026-01-27', 4, '2026-01-27 10:06:00', '2026-01-28 13:09:26'),
(68, 44, 1, 'Angela Rizal', 2, 15, NULL, 'ASUS A3402 All-in-One Desktop', '0', NULL, 'PAR-0106', 'INV-2026-00023', '2026-01-28', '', 'qr_asset_68_1769606419.png', 'serviceable', 42995.00, '2026-01-27', 4, '2026-01-27 10:06:00', '2026-01-28 13:20:19'),
(69, 45, 6, '0', 2, 16, NULL, 'HP Laptop 15-fd1168TU', 'Units', NULL, 'PAR-0021', 'INV-2026-00020', '2026-01-27', '', 'qr_asset_69_1769508660.png', 'serviceable', 49990.00, '2026-01-27', 4, '2026-01-27 10:09:51', '2026-01-27 10:11:00'),
(70, 45, 6, 'Jake Paul', 2, 16, NULL, 'HP Laptop 15-fd1168TU', 'Units', NULL, 'PAR-0038', 'INV-2026-00021', '2026-01-27', '', 'qr_asset_70_1769509820.png', 'serviceable', 49990.00, '2026-01-27', 4, '2026-01-27 10:09:51', '2026-01-27 11:00:27'),
(71, 46, 1, 'Jake Paul', 2, NULL, 23, 'Laptop AMD Ryzen', 'Sets', NULL, 'PAR-0105', 'INV-2026-00022', '2026-01-28', '', 'qr_asset_71_1769606380.png', 'serviceable', 13140.00, '2026-01-28', 4, '2026-01-28 13:18:38', '2026-01-28 13:19:40'),
(72, 46, 1, 'Roberto Cruz', 2, NULL, 23, 'Laptop AMD Ryzen', 'Sets', NULL, 'PAR-0107', 'INV-2026-00024', '2026-01-28', '', 'qr_asset_72_1769606595.png', 'serviceable', 13140.00, '2026-01-28', 4, '2026-01-28 13:18:38', '2026-01-28 13:23:15'),
(73, 46, 1, 'Angela Rizal', 2, NULL, 23, 'Laptop AMD Ryzen', 'Sets', NULL, 'PAR-0108', 'INV-2026-00025', '2026-01-29', '', 'qr_asset_73_1769693185.png', 'red_tagged', 13140.00, '2026-01-28', 4, '2026-01-28 13:18:38', '2026-02-01 10:14:04'),
(74, 46, 6, 'John Legend', 2, NULL, 23, 'Laptop AMD Ryzen', 'Sets', NULL, 'PAR-0110', 'INV-2026-00026', '2026-02-05', 'asset_74_1770248912.jpg', 'qr_asset_74_1770248914.png', 'red_tagged', 13140.00, '2026-01-28', 4, '2026-01-28 13:18:38', '2026-02-05 00:03:51'),
(75, 46, NULL, NULL, NULL, NULL, 23, 'Laptop AMD Ryzen', 'Sets', NULL, NULL, NULL, NULL, NULL, NULL, 'no_tag', 13140.00, '2026-01-28', 4, '2026-01-28 13:18:38', '2026-01-28 13:18:38');

--
-- Triggers `asset_items`
--
DELIMITER $$
CREATE TRIGGER `tr_asset_assign_employee` AFTER INSERT ON `asset_items` FOR EACH ROW BEGIN
            IF NEW.employee_id IS NOT NULL THEN
                UPDATE employees 
                SET clearance_status = 'uncleared' 
                WHERE id = NEW.employee_id;
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_asset_update_employee` AFTER UPDATE ON `asset_items` FOR EACH ROW BEGIN
            IF NEW.employee_id IS NOT NULL AND (OLD.employee_id IS NULL OR OLD.employee_id != NEW.employee_id) THEN
                UPDATE employees 
                SET clearance_status = 'uncleared' 
                WHERE id = NEW.employee_id;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `asset_item_history`
--

CREATE TABLE `asset_item_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_item_history`
--

INSERT INTO `asset_item_history` (`id`, `item_id`, `action`, `details`, `old_value`, `new_value`, `created_by`, `created_at`) VALUES
(1, 48, 'Tag Created', 'Created tag for item ID 48: Property No: PN-2019-05-02-0001-0156, Inventory Tag: INV-2026-00007, Date Counted: 2026-01-23, Category: FF - Furniture & Fixtures, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: John Legend (No image)', NULL, NULL, 5, '2026-01-23 11:50:06'),
(2, 51, 'Tag Created', 'Created tag for item ID 51: Property No: PN-2019-05-02-0001-01, Inventory Tag: INV-2026-00008, Date Counted: 2026-01-23, Category: ITS - Computer Equipment, Person Accountable: EMP0002 (Santos, Maria), End User: Jack Robertson (No image)', NULL, NULL, 5, '2026-01-23 12:04:06'),
(3, 47, 'Tag Created', 'Created tag for item ID 47: Property No: PN-2019-05-02-0001-0134, Inventory Tag: INV-2026-00009, Date Counted: 2026-01-23, Category: ITS - Computer Equipment, Person Accountable: EMP0003 (Reyes, Jose), End User: Jake Paul (No image)', NULL, NULL, 5, '2026-01-23 12:07:38'),
(4, 55, 'Tag Created', 'Created tag for item ID 55: Property No: PN-2019-05-02-0001-0123, Inventory Tag: INV-2026-00010, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0003 (Reyes, Jose), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-25 12:02:30'),
(5, 56, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Intel® Core™ i5-13420H (Up to 4.6GHz, 8 Cores), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 512GB SSD M.2 2242 PCIe® 4.0x4 NVMe®, OS: Windows® 11 Home Single Language, Serial: 456733245hg4523', NULL, NULL, 5, '2026-01-25 12:14:43'),
(6, 56, 'Tag Created', 'Created tag for item ID 56: Property No: ITM-55-13453, Inventory Tag: INV-2026-00011, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0003 (Reyes, Jose), End User: Roberto Cruz (Image: asset_56_1769343283.jpeg)', NULL, NULL, 5, '2026-01-25 12:14:43'),
(7, 57, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB Unified Memory, Storage: 512GB SSD, OS: macOS Sequoia, Serial: 145234345', NULL, NULL, 5, '2026-01-25 12:29:51'),
(8, 57, 'Tag Created', 'Created tag for item ID 57: Property No: PAR-0003, Inventory Tag: INV-2026-00012, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0003 (Reyes, Jose), End User: John Legend (No image)', NULL, NULL, 5, '2026-01-25 12:29:51'),
(9, 58, 'ICS Created', 'Created via ICS form ICS-01-0014 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱33,995.00', NULL, NULL, 5, '2026-01-25 12:44:27'),
(10, 58, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: 8GB DDR4 (Upgradable via 1x SO-DIMM slot), Storage: 512GB M.2 NVMe™ PCIe® 3.0 SSD, OS: Windows® 11 Home, Serial: 3474665876', NULL, NULL, 5, '2026-01-25 12:46:38'),
(11, 58, 'Tag Created', 'Created tag for item ID 58: Property No: MR-2025-0002023, Inventory Tag: INV-2026-00013, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0002 (Santos, Maria), End User: Angela Rizal (Image: asset_58_1769345198.jpg)', NULL, NULL, 5, '2026-01-25 12:46:38'),
(12, 59, 'PAR Created', 'Created via PAR form PAR-0021-2026 - Entity: LGU PILAR, Quantity: 1, Unit: Units, Amount: ₱97,000.00', NULL, NULL, 5, '2026-01-25 12:51:40'),
(13, 59, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Intel® Xeon® E-2434 (4C/8T, 3.4GHz), RAM: 16GB DDR5 ECC UDIMM, Storage: 2TB 7.2K RPM SATA 6Gbps (Enterprise Drive), OS: Windows Server 2022 (Standard Edition), Serial: 2376688978', NULL, NULL, 5, '2026-01-25 12:55:19'),
(14, 59, 'Tag Created', 'Created tag for item ID 59: Property No: PAR-0004, Inventory Tag: INV-2026-00014, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0003 (Reyes, Jose), End User: Angela Rizal (No image)', NULL, NULL, 5, '2026-01-25 12:55:19'),
(15, 60, 'ICS Created', 'Created via ICS form ICS-01-0015 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱42,990.00', NULL, NULL, 5, '2026-01-25 13:01:20'),
(16, 60, 'QR Code Generated', 'QR code generated for asset item: qr_asset_60_1769346147.png', NULL, NULL, 5, '2026-01-25 13:02:27'),
(17, 60, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M2 Chip (8-core CPU / 9-core GPU), RAM: 8GB Unified Memory, Storage: 128GB SSD, OS: iPadOS 19, Serial: 3454345423', NULL, NULL, 5, '2026-01-25 13:02:27'),
(18, 60, 'Tag Created', 'Created tag for item ID 60: Property No: ITM-55-13453, Inventory Tag: INV-2026-00015, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-25 13:02:27'),
(19, 61, 'ICS Created', 'Created via ICS form ICS-01-0016 - Entity: East District, Item No: 1, Quantity: 1, Unit: Pieces, Unit Cost: ₱41,400.00', NULL, NULL, 5, '2026-01-25 13:05:36'),
(20, 61, 'QR Code Generated', 'QR code generated for asset item: qr_asset_61_1769346550.png', NULL, NULL, 5, '2026-01-25 13:09:10'),
(21, 61, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Intel® Core™ i3-13100 (Up to 4.5GHz), RAM: 8GB DDR5 4800MHz, Storage: 256GB M.2 PCIe NVMe SSD, OS: Windows® 11 Pro, Serial: 3678786564643', NULL, NULL, 5, '2026-01-25 13:09:10'),
(22, 61, 'Tag Created', 'Created tag for item ID 61: Property No: PAR-0006, Inventory Tag: INV-2026-00016, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-25 13:09:10'),
(23, 62, 'PAR Created', 'Created via PAR form PAR-0022-2026 - Entity: LGU PILAR, Quantity: 1, Unit: Units, Amount: ₱89,999.00', NULL, NULL, 5, '2026-01-25 13:11:47'),
(24, 62, 'QR Code Generated', 'QR code generated for asset item: qr_asset_62_1769346760.png', NULL, NULL, 5, '2026-01-25 13:12:40'),
(25, 62, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Intel® Core™ i9-14900HX (24 Cores, up to 5.8GHz), RAM: 16GB DDR5 (Upgradable to 32GB), Storage: 1TB NVMe Gen 4 SSD, OS: Windows® 11 Home, Serial: 456645342255', NULL, NULL, 5, '2026-01-25 13:12:40'),
(26, 62, 'Tag Created', 'Created tag for item ID 62: Property No: PAR-0007, Inventory Tag: INV-2026-00017, Date Counted: 2026-01-25, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Angela Rizal (No image)', NULL, NULL, 5, '2026-01-25 13:12:40'),
(27, 63, 'PAR Created', 'Created via PAR form PAR-0024-2026 - Entity: LGU PILAR, Quantity: 1, Unit: Pieces, Amount: ₱89,999.00', NULL, NULL, 5, '2026-01-27 01:53:39'),
(28, 64, 'ICS Created', 'Created via ICS form ICS-01-0017 - Entity: Head Office, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱48,500.00', NULL, NULL, 5, '2026-01-27 09:45:50'),
(29, 65, 'ICS Created', 'Created via ICS form ICS-01-0017 - Entity: Head Office, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱48,500.00', NULL, NULL, 5, '2026-01-27 09:45:50'),
(30, 64, 'QR Code Generated', 'QR code generated for asset item: qr_asset_64_1769507202.png', NULL, NULL, 5, '2026-01-27 09:46:42'),
(31, 64, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M2 Chip (8-core CPU / 8-core GPU), RAM: 8GB Unified Memory, Storage: 256GB SSD, OS: macOS Sequoia, Serial: 367878656464334', NULL, NULL, 5, '2026-01-27 09:46:42'),
(32, 64, 'Tag Created', 'Created tag for item ID 64: Property No: PAR-0010, Inventory Tag: INV-2026-00018, Date Counted: 2026-01-27, Category: ITS - Computer Equipment, Person Accountable: EMP20260001 (loneza, Walton), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-27 09:46:42'),
(33, 65, 'QR Code Generated', 'QR code generated for asset item: qr_asset_65_1769507903.png', NULL, NULL, 5, '2026-01-27 09:58:23'),
(34, 65, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 128GB SSD, OS: macOS Sequoia, Serial: 367878656464321', NULL, NULL, 5, '2026-01-27 09:58:23'),
(35, 65, 'Tag Created', 'Created tag for item ID 65: Property No: PAR-0019, Inventory Tag: INV-2026-00019, Date Counted: 2026-01-27, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Roberto Cruz (Image: asset_65_1769507903.jpg)', NULL, NULL, 5, '2026-01-27 09:58:23'),
(36, 66, 'ICS Created', 'Created via ICS form ICS-01-0018 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱42,995.00', NULL, NULL, 5, '2026-01-27 10:06:00'),
(37, 67, 'ICS Created', 'Created via ICS form ICS-01-0018 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱42,995.00', NULL, NULL, 5, '2026-01-27 10:06:00'),
(38, 68, 'ICS Created', 'Created via ICS form ICS-01-0018 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱42,995.00', NULL, NULL, 5, '2026-01-27 10:06:00'),
(39, 69, 'ICS Created', 'Created via ICS form ICS-01-0019 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱49,990.00', NULL, NULL, 5, '2026-01-27 10:09:51'),
(40, 70, 'ICS Created', 'Created via ICS form ICS-01-0019 - Entity: East District, Item No: 1, Quantity: 1, Unit: Units, Unit Cost: ₱49,990.00', NULL, NULL, 5, '2026-01-27 10:09:51'),
(41, 69, 'QR Code Generated', 'QR code generated for asset item: qr_asset_69_1769508660.png', NULL, NULL, 5, '2026-01-27 10:11:00'),
(42, 69, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 128GB SSD, OS: macOS Sequoia, Serial: 3678786564643111', NULL, NULL, 5, '2026-01-27 10:11:00'),
(43, 69, 'Tag Created', 'Created tag for item ID 69: Property No: PAR-0021, Inventory Tag: INV-2026-00020, Date Counted: 2026-01-27, Category: ITS - Computer Equipment, Person Accountable: EMP20260001 (loneza, Walton), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-27 10:11:00'),
(44, 70, 'QR Code Generated', 'QR code generated for asset item: qr_asset_70_1769509820.png', NULL, NULL, 5, '2026-01-27 10:30:20'),
(45, 70, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 2TB 7.2K RPM SATA 6Gbps (Enterprise Drive), OS: Windows® 11 Home Single Language, Serial: 456733245hg45', NULL, NULL, 5, '2026-01-27 10:30:20'),
(46, 70, 'Tag Created', 'Created tag for item ID 70: Property No: PAR-0038, Inventory Tag: INV-2026-00021, Date Counted: 2026-01-27, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-27 10:30:20'),
(47, 62, 'ITR Transfer', 'Transferred via ITR form ITR-2026-00015 - From: Walton loneza, To: Juan Dela Cruz, Transfer Type: Reassignment, End User: Angela Rizal, Purpose: reassignment', 'Employee ID: 6 (Walton loneza)', 'Employee ID: 1 (Juan Dela Cruz)', 5, '2026-01-27 12:00:37'),
(56, 67, 'QR Code Generated', 'QR code generated for asset item: qr_asset_67_1769605766.png', NULL, NULL, 1, '2026-01-28 13:09:26'),
(57, 67, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Not specified, RAM: Not specified, Storage: Not specified, OS: Not specified, Serial: Not specified', NULL, NULL, 1, '2026-01-28 13:09:26'),
(58, 67, 'Tag Created', 'Created tag for item ID 67: Property No: PAR-TEST-001, Inventory Tag: INV-2026-TEST-001, Date Counted: 2026-01-28, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Test User for Debug (No image)', NULL, NULL, 1, '2026-01-28 13:09:26'),
(62, 71, 'PAR Created', 'Created via PAR form PAR-0025-2026 - Entity: INVENTORY, Quantity: 5, Unit: Sets, Amount: ₱65,700.00', NULL, NULL, 5, '2026-01-28 13:18:38'),
(63, 72, 'PAR Created', 'Created via PAR form PAR-0025-2026 - Entity: INVENTORY, Quantity: 5, Unit: Sets, Amount: ₱65,700.00', NULL, NULL, 5, '2026-01-28 13:18:38'),
(64, 73, 'PAR Created', 'Created via PAR form PAR-0025-2026 - Entity: INVENTORY, Quantity: 5, Unit: Sets, Amount: ₱65,700.00', NULL, NULL, 5, '2026-01-28 13:18:38'),
(65, 74, 'PAR Created', 'Created via PAR form PAR-0025-2026 - Entity: INVENTORY, Quantity: 5, Unit: Sets, Amount: ₱65,700.00', NULL, NULL, 5, '2026-01-28 13:18:38'),
(66, 75, 'PAR Created', 'Created via PAR form PAR-0025-2026 - Entity: INVENTORY, Quantity: 5, Unit: Sets, Amount: ₱65,700.00', NULL, NULL, 5, '2026-01-28 13:18:38'),
(67, 71, 'QR Code Generated', 'QR code generated for asset item: qr_asset_71_1769606380.png', NULL, NULL, 5, '2026-01-28 13:19:40'),
(68, 71, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Intel® Xeon® E-2434 (4C/8T, 3.4GHz), RAM: 16GB Unified Memory, Storage: 512GB M.2 NVMe™ PCIe® 3.0 SSD, OS: Windows® 11 Home, Serial: 456733245hg45', NULL, NULL, 5, '2026-01-28 13:19:40'),
(69, 71, 'Tag Created', 'Created tag for item ID 71: Property No: PAR-0105, Inventory Tag: INV-2026-00022, Date Counted: 2026-01-28, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Jake Paul (No image)', NULL, NULL, 5, '2026-01-28 13:19:40'),
(70, 68, 'QR Code Generated', 'QR code generated for asset item: qr_asset_68_1769606419.png', NULL, NULL, 5, '2026-01-28 13:20:19'),
(71, 68, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 2TB 7.2K RPM SATA 6Gbps (Enterprise Drive), OS: macOS Sequoia, Serial: 456645342255', NULL, NULL, 5, '2026-01-28 13:20:19'),
(72, 68, 'Tag Created', 'Created tag for item ID 68: Property No: PAR-0106, Inventory Tag: INV-2026-00023, Date Counted: 2026-01-28, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Angela Rizal (No image)', NULL, NULL, 5, '2026-01-28 13:20:19'),
(73, 72, 'QR Code Generated', 'QR code generated for asset item: qr_asset_72_1769606595.png', NULL, NULL, 5, '2026-01-28 13:23:15'),
(74, 72, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: 8GB DDR4 (Upgradable via 1x SO-DIMM slot), Storage: 2TB 7.2K RPM SATA 6Gbps (Enterprise Drive), OS: macOS Sequoia, Serial: 3474665876', NULL, NULL, 5, '2026-01-28 13:23:15'),
(75, 72, 'Tag Created', 'Created tag for item ID 72: Property No: PAR-0107, Inventory Tag: INV-2026-00024, Date Counted: 2026-01-28, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Roberto Cruz (No image)', NULL, NULL, 5, '2026-01-28 13:23:15'),
(76, 73, 'QR Code Generated', 'QR code generated for asset item: qr_asset_73_1769693185.png', NULL, NULL, 5, '2026-01-29 13:26:25'),
(77, 73, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: Apple M3 Chip (8-core CPU, 10-core GPU), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 2TB 7.2K RPM SATA 6Gbps (Enterprise Drive), OS: macOS Sequoia, Serial: 456645342255', NULL, NULL, 5, '2026-01-29 13:26:25'),
(78, 73, 'Tag Created', 'Created tag for item ID 73: Property No: PAR-0108, Inventory Tag: INV-2026-00025, Date Counted: 2026-01-29, Category: ITS - Computer Equipment, Person Accountable: EMP0001 (Dela Cruz, Juan), End User: Angela Rizal (No image)', NULL, NULL, 5, '2026-01-29 13:26:25'),
(79, 74, 'QR Code Generated', 'QR code generated for asset item: qr_asset_74_1770248914.png', NULL, NULL, 5, '2026-02-04 23:48:34'),
(80, 74, 'Computer Specs Updated', 'Computer Equipment specs saved - Processor: AMD Ryzen™ 5 7530U (6-core/12-thread, up to 4.5GHz), RAM: 16GB LPDDR5-4800MHz (Soldered), Storage: 512GB SSD M.2 2242 PCIe® 4.0x4 NVMe®, OS: Windows® 11 Home Single Language, Serial: 3678786564643676', NULL, NULL, 5, '2026-02-04 23:48:34'),
(81, 74, 'Tag Created', 'Created tag for item ID 74: Property No: PAR-0110, Inventory Tag: INV-2026-00026, Date Counted: 2026-02-05, Category: ITS - Computer Equipment, Person Accountable: EMP20260001 (loneza, Walton), End User: John Legend (Image: asset_74_1770248912.jpg)', NULL, NULL, 5, '2026-02-04 23:48:34'),
(82, 74, 'status_change', 'Status changed via IIRUP Form: IIRUP-2026-4701', 'serviceable', 'unserviceable', 5, '2026-02-04 23:55:27'),
(83, 74, 'status_change', 'Status changed via Red Tag: PS-5S-02-F-20-20', 'unserviceable', 'red_tagged', 5, '2026-02-05 00:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `asset_land`
--

CREATE TABLE `asset_land` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_machinery`
--

CREATE TABLE `asset_machinery` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_office_equipment`
--

CREATE TABLE `asset_office_equipment` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_software`
--

CREATE TABLE `asset_software` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_vehicles`
--

CREATE TABLE `asset_vehicles` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backups`
--

CREATE TABLE `backups` (
  `id` int(11) NOT NULL,
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
  `cloud_backup_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backups`
--

INSERT INTO `backups` (`id`, `name`, `type`, `include_files`, `include_database`, `file_path`, `created_by`, `created_at`, `online_backup`, `cloud_provider`, `cloud_backup_url`, `cloud_backup_status`, `cloud_backup_error`, `cloud_backup_at`) VALUES
(8, 'Daily Backup', 'full', 1, 1, '../backups/Daily Backup_2026-01-05_13-41-33', 1, '2026-01-05 12:41:33', 0, NULL, NULL, NULL, NULL, NULL),
(9, 'Daily Backup', 'full', 1, 1, '../backups/Daily Backup_2026-01-05_14-05-54', 1, '2026-01-05 13:05:54', 0, NULL, NULL, NULL, NULL, NULL),
(10, 'online Backup', 'full', 1, 1, '../backups/online Backup_2026-01-05_14-29-41', 1, '2026-01-05 13:29:41', 1, '0', 'https://drive.google.com/file/d/1qrf12E9fs98ak_we_UXsTnEyGt4z5pcR/view', 'completed', NULL, '2026-01-05 14:13:59'),
(11, 'Daily Backup', 'full', 1, 1, '../backups/Daily Backup_2026-01-05_14-46-20', 1, '2026-01-05 13:46:21', 0, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `backup_execution_logs`
--

CREATE TABLE `backup_execution_logs` (
  `id` int(11) NOT NULL,
  `scheduled_backup_id` int(11) NOT NULL,
  `execution_status` enum('running','completed','failed') NOT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `backup_id` int(11) DEFAULT NULL COMMENT 'ID of the created backup if successful',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `consumables`
--

CREATE TABLE `consumables` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 10,
  `office_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consumables`
--

INSERT INTO `consumables` (`id`, `description`, `quantity`, `unit_cost`, `reorder_level`, `office_id`, `created_at`, `updated_at`) VALUES
(2, 'UTP Connectors (RJ45 Cat6, 100pcs)', 2, 850.00, 1, 4, '2026-01-11 10:53:51', '2026-01-11 12:58:41'),
(3, 'UTP Cable (Cat6, 305m/Box)', 1, 6500.00, 0, 1, '2026-01-11 10:57:20', '2026-01-11 12:58:16');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_no` varchar(20) DEFAULT NULL,
  `firstname` varchar(50) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `office_id` int(11) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_status` enum('permanent','contractual','job_order','resigned','retired') DEFAULT 'permanent',
  `clearance_status` enum('cleared','uncleared') DEFAULT 'uncleared',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_no`, `firstname`, `lastname`, `email`, `phone`, `profile_photo`, `office_id`, `position`, `employment_status`, `clearance_status`, `created_at`, `updated_at`) VALUES
(1, 'EMP0001', 'Juan', 'Dela Cruz', 'juan.cruz@lgu.gov', '9123456789', NULL, 1, 'Office Clerk', 'permanent', 'uncleared', '2026-01-21 14:27:49', '2026-01-22 12:08:11'),
(2, 'EMP0002', 'Maria', 'Santos', 'maria.santos@lgu.gov', '09234567890', NULL, 2, 'Accountant', 'contractual', 'uncleared', '2026-01-21 14:27:49', '2026-01-23 12:04:06'),
(3, 'EMP0003', 'Jose', 'Reyes', 'jose.reyes@lgu.gov', '09345678901', NULL, 3, 'IT Specialist', 'job_order', 'uncleared', '2026-01-21 14:27:49', '2026-01-21 14:37:48'),
(6, 'EMP20260001', 'Walton', 'loneza', 'wjll2022-2920-98466@bicol-u.edu.ph', '9107171456', 'uploads/employees/employee_6_1769087366.png', 4, '', 'permanent', 'uncleared', '2026-01-22 13:09:26', '2026-01-27 09:42:34');

-- --------------------------------------------------------

--
-- Table structure for table `failed_login_attempts`
--

CREATE TABLE `failed_login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_blocked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `form_code` varchar(50) NOT NULL,
  `form_title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `header_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `form_code`, `form_title`, `description`, `header_image`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'PAR', 'Property Acknowledgement Receipt', 'Form for acknowledging receipt of government property', '1767700581_PAR.png', 'active', 1, NULL, '2026-01-06 10:17:58', '2026-01-06 11:56:21'),
(2, 'ICS', 'Inventory Custodian Slip', 'Form for transferring accountability of property', '1767703470_Screenshot 2026-01-06 194414.png', 'active', 1, 1, '2026-01-06 10:17:58', '2026-01-06 12:44:30'),
(3, 'RIS', 'Requisition and Issue Slip', 'Form for requesting and issuing supplies', '1767705532_RIS HEADER.png', 'active', 1, 1, '2026-01-06 10:17:58', '2026-01-06 13:18:52'),
(6, 'ITR', 'Inventory Transfer Receipt', 'For transferring assets on person accountable.', '1767706199_ITR HEADER.png', 'active', 1, 1, '2026-01-06 10:23:41', '2026-01-06 13:29:59'),
(7, 'IIRUP', 'Inventory and Inspection Report of Unserviceable Property', 'for dropping unserviceable items from the inventory records and determines how they will be disposed', '1767706581_IIRUP HEADER.png', 'active', 1, 1, '2026-01-06 10:50:01', '2026-01-06 13:36:21');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_in`
--

CREATE TABLE `fuel_in` (
  `id` int(11) NOT NULL,
  `date_time` datetime NOT NULL DEFAULT current_timestamp(),
  `fuel_type` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `delivery_receipt` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `received_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_in`
--

INSERT INTO `fuel_in` (`id`, `date_time`, `fuel_type`, `quantity`, `unit_price`, `total_cost`, `storage_location`, `delivery_receipt`, `supplier_name`, `received_by`, `remarks`, `created_by`, `created_at`) VALUES
(2, '2026-02-07 13:24:10', 2, 50.00, 50.00, 2500.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:24:10'),
(3, '2026-02-07 13:24:54', 1, 50.00, 50.00, 2500.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:24:54'),
(4, '2026-02-07 13:35:15', 1, 28.00, 50.00, 1400.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:35:15'),
(5, '2026-02-07 13:37:40', 1, 28.00, 50.00, 1400.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:37:40'),
(6, '2026-02-07 13:40:59', 1, 28.00, 50.00, 1400.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:40:59'),
(7, '2026-02-07 13:42:13', 1, 28.00, 50.00, 1400.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 05:42:13'),
(8, '2026-02-07 20:56:53', 3, 40.00, 45.00, 1800.00, 'Storage Room', '09890967675', 'Elton John Moises', '5', '', 5, '2026-02-07 12:56:53');

-- --------------------------------------------------------

--
-- Table structure for table `fuel_inventory`
--

CREATE TABLE `fuel_inventory` (
  `id` int(11) NOT NULL,
  `tank_number` varchar(50) NOT NULL,
  `fuel_type` enum('diesel','gasoline','premium') NOT NULL,
  `capacity` decimal(10,2) NOT NULL COMMENT 'Tank capacity in liters',
  `current_level` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Current fuel level in liters',
  `location` varchar(100) DEFAULT NULL COMMENT 'Physical location of tank',
  `status` enum('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated the tank'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_inventory`
--

INSERT INTO `fuel_inventory` (`id`, `tank_number`, `fuel_type`, `capacity`, `current_level`, `location`, `status`, `last_updated`, `created_at`, `updated_by`) VALUES
(1, 'TANK-D001', 'diesel', 5000.00, 3400.00, 'Main Station - Diesel Tank 1', 'active', '2026-02-07 05:57:58', '2026-02-07 03:30:04', 1),
(2, 'TANK-D002', 'diesel', 3000.00, 2100.00, 'Main Station - Diesel Tank 2', 'active', '2026-02-07 03:30:04', '2026-02-07 03:30:04', 1),
(3, 'TANK-G001', 'gasoline', 2000.00, 1500.00, 'Main Station - Gasoline Tank 1', 'active', '2026-02-07 03:30:04', '2026-02-07 03:30:04', 1),
(4, 'TANK-G002', 'gasoline', 1500.00, 800.00, 'Main Station - Gasoline Tank 2', 'active', '2026-02-07 03:30:04', '2026-02-07 03:30:04', 1),
(5, 'TANK-P001', 'premium', 1000.00, 790.00, 'Main Station - Premium Tank', 'active', '2026-02-07 12:56:53', '2026-02-07 03:30:04', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fuel_out`
--

CREATE TABLE `fuel_out` (
  `id` int(11) NOT NULL,
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
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_stock`
--

CREATE TABLE `fuel_stock` (
  `fuel_type_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_stock`
--

INSERT INTO `fuel_stock` (`fuel_type_id`, `quantity`, `updated_at`, `created_by`) VALUES
(1, 1550.00, '2026-02-07 05:57:58', 1),
(2, 1929.00, '2026-02-07 02:41:49', 1),
(3, 1965.00, '2026-02-07 12:56:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fuel_transactions`
--

CREATE TABLE `fuel_transactions` (
  `id` int(11) NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL,
  `fuel_type` enum('diesel','gasoline','premium') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp(),
  `source` varchar(100) DEFAULT NULL COMMENT 'For IN transactions: Delivery, Transfer, Refill, etc.',
  `supplier` varchar(100) DEFAULT NULL COMMENT 'For IN transactions: Supplier name',
  `employee_id` int(11) DEFAULT NULL COMMENT 'For OUT transactions: Employee who received fuel',
  `recipient_name` varchar(100) DEFAULT NULL COMMENT 'For OUT transactions: If no employee selected',
  `purpose` varchar(200) DEFAULT NULL COMMENT 'For OUT transactions: Purpose of fuel usage',
  `vehicle_equipment` varchar(100) DEFAULT NULL COMMENT 'For OUT transactions: Vehicle or equipment ID',
  `odometer_reading` int(11) DEFAULT NULL COMMENT 'For OUT transactions: Current odometer reading',
  `odometer_unit` varchar(10) DEFAULT 'km' COMMENT 'For OUT transactions: km or miles',
  `tank_number` varchar(50) DEFAULT NULL COMMENT 'Tank involved in transaction',
  `user_id` int(11) NOT NULL COMMENT 'User who recorded the transaction',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_transactions`
--

INSERT INTO `fuel_transactions` (`id`, `transaction_type`, `fuel_type`, `quantity`, `transaction_date`, `source`, `supplier`, `employee_id`, `recipient_name`, `purpose`, `vehicle_equipment`, `odometer_reading`, `odometer_unit`, `tank_number`, `user_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'IN', '', 2000.00, '2026-02-07 10:32:16', 'Delivery', 'Petron Corporation', NULL, NULL, NULL, NULL, NULL, 'km', 'TANK-D001', 1, 'Regular diesel delivery', '2026-02-07 02:32:16', '2026-02-07 02:32:16'),
(2, 'IN', '', 1500.00, '2026-02-07 10:32:16', 'Delivery', 'Shell Philippines', NULL, NULL, NULL, NULL, NULL, 'km', 'TANK-G001', 1, 'Premium gasoline delivery', '2026-02-07 02:32:16', '2026-02-07 02:32:16'),
(3, 'OUT', '', 50.00, '2026-02-07 10:32:16', NULL, NULL, 1, NULL, 'Field Operations', 'VEH-001', NULL, 'km', 'TANK-D001', 1, 'Vehicle refueling for field work', '2026-02-07 02:32:16', '2026-02-07 02:32:16'),
(4, 'OUT', '', 30.00, '2026-02-07 10:32:16', NULL, NULL, 2, NULL, 'Office Travel', 'VEH-002', NULL, 'km', 'TANK-G001', 1, 'Office vehicle refueling', '2026-02-07 02:32:16', '2026-02-07 02:32:16'),
(5, 'OUT', 'diesel', 50.00, '2026-02-07 13:44:01', NULL, NULL, NULL, 'J.LUMIBAO', 'MOTORPOOL', 'BACKHOE', NULL, 'km', 'TANK-D001', 5, '', '2026-02-07 05:44:01', '2026-02-07 05:44:01'),
(6, 'OUT', 'diesel', 50.00, '2026-02-07 13:57:58', NULL, NULL, NULL, 'J.LUMIBAO', 'MOTORPOOL', 'BACKHOE', NULL, 'km', 'TANK-D001', 5, '', '2026-02-07 05:57:58', '2026-02-07 05:57:58'),
(7, 'IN', 'premium', 40.00, '2026-02-07 20:56:53', 'Delivery', 'Elton John Moises', NULL, NULL, NULL, NULL, NULL, 'km', 'TANK-P001', 5, '', '2026-02-07 12:56:53', '2026-02-07 12:56:53');

--
-- Triggers `fuel_transactions`
--
DELIMITER $$
CREATE TRIGGER `tr_fuel_in_inventory_update` AFTER INSERT ON `fuel_transactions` FOR EACH ROW BEGIN
        IF NEW.transaction_type = 'IN' AND NEW.tank_number IS NOT NULL THEN
            UPDATE fuel_inventory 
            SET current_level = current_level + NEW.quantity,
                last_updated = NOW()
            WHERE tank_number = NEW.tank_number;
        END IF;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_fuel_out_inventory_update` AFTER INSERT ON `fuel_transactions` FOR EACH ROW BEGIN
        IF NEW.transaction_type = 'OUT' AND NEW.tank_number IS NOT NULL THEN
            UPDATE fuel_inventory 
            SET current_level = current_level - NEW.quantity,
                last_updated = NOW()
            WHERE tank_number = NEW.tank_number AND current_level >= NEW.quantity;
        END IF;
    END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_fuel_transaction_delete_reverse` AFTER DELETE ON `fuel_transactions` FOR EACH ROW BEGIN
        IF OLD.tank_number IS NOT NULL THEN
            IF OLD.transaction_type = 'IN' THEN
                -- Reverse the fuel IN (subtract from inventory)
                UPDATE fuel_inventory 
                SET current_level = current_level - OLD.quantity,
                    last_updated = NOW()
                WHERE tank_number = OLD.tank_number;
            ELSEIF OLD.transaction_type = 'OUT' THEN
                -- Reverse the fuel OUT (add back to inventory)
                UPDATE fuel_inventory 
                SET current_level = current_level + OLD.quantity,
                    last_updated = NOW()
                WHERE tank_number = OLD.tank_number;
            END IF;
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_types`
--

CREATE TABLE `fuel_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fuel_types`
--

INSERT INTO `fuel_types` (`id`, `name`, `is_active`, `created_at`) VALUES
(1, 'Diesel', 1, '2026-02-07 02:41:49'),
(2, 'Gasoline', 1, '2026-02-07 02:41:49'),
(3, 'Premium', 1, '2026-02-07 02:41:49');

-- --------------------------------------------------------

--
-- Table structure for table `ics_form`
--

CREATE TABLE `ics_form` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ics_forms`
--

CREATE TABLE `ics_forms` (
  `id` int(11) NOT NULL,
  `entity_name` varchar(255) NOT NULL,
  `fund_cluster` varchar(100) NOT NULL,
  `ics_no` varchar(50) NOT NULL,
  `received_from` varchar(255) NOT NULL,
  `received_from_position` varchar(255) NOT NULL,
  `received_from_date` date NOT NULL,
  `received_by` varchar(255) NOT NULL,
  `received_by_position` varchar(255) NOT NULL,
  `received_by_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ics_forms`
--

INSERT INTO `ics_forms` (`id`, `entity_name`, `fund_cluster`, `ics_no`, `received_from`, `received_from_position`, `received_from_date`, `received_by`, `received_by_position`, `received_by_date`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(10, 'Head Office', 'COMPUTERIZATION', 'ICS-01-0013', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-25 12:00:36', '2026-01-25 12:00:36'),
(11, 'East District', 'COMPUTERIZATION', 'ICS-01-0014', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-25 12:44:27', '2026-01-25 12:44:27'),
(12, 'East District', 'COMPUTERIZATION', 'ICS-01-0015', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-25 13:01:20', '2026-01-25 13:01:20'),
(13, 'East District', 'COMPUTERIZATION', 'ICS-01-0016', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-25 13:05:36', '2026-01-25 13:05:36'),
(14, 'Head Office', 'COMPUTERIZATION', 'ICS-01-0017', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-27 09:45:50', '2026-01-27 09:45:50'),
(15, 'East District', 'COMPUTERIZATION', 'ICS-01-0018', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-27 10:06:00', '2026-01-27 10:06:00'),
(16, 'East District', 'COMPUTERIZATION', 'ICS-01-0019', 'James Gosling', 'OFFICER', '0000-00-00', 'Leo Peterson', 'PROPERTY CUSTODIAN', '0000-00-00', 5, 5, '2026-01-27 10:09:51', '2026-01-27 10:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `ics_items`
--

CREATE TABLE `ics_items` (
  `item_id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ics_items`
--

INSERT INTO `ics_items` (`item_id`, `form_id`, `ics_id`, `asset_id`, `ics_no`, `quantity`, `unit`, `unit_cost`, `total_cost`, `description`, `useful_life`, `item_no`, `estimated_useful_life`, `created_at`) VALUES
(10, 10, 0, NULL, NULL, 2.00, '0', 38500.00, 77000.00, 'Laptop: Lenovo IdeaPad Slim 3 (i5, 16GB RAM)', '3', '1', NULL, '2026-01-25 12:00:36'),
(11, 11, 0, NULL, NULL, 1.00, '0', 33995.00, 33995.00, 'ASUS Vivobook 16 (M1605YA-MB155WS)', '3', '1', NULL, '2026-01-25 12:44:27'),
(12, 12, 0, NULL, NULL, 1.00, '0', 42990.00, 42990.00, 'iPad Air 11-inch (M2 Chip)', '3', '1', NULL, '2026-01-25 13:01:20'),
(13, 13, 0, NULL, NULL, 1.00, '0', 41400.00, 41400.00, 'Dell OptiPlex SFF (Plus 7010 Series)', '3', '1', NULL, '2026-01-25 13:05:36'),
(14, 14, 0, NULL, NULL, 2.00, '0', 48500.00, 97000.00, 'Apple MacBook Air 13.6\" (2022/2025 Refreshed Pricing)', '3', '1', NULL, '2026-01-27 09:45:50'),
(15, 15, 0, NULL, NULL, 3.00, '0', 42995.00, 128985.00, 'ASUS A3402 All-in-One Desktop', '4', '1', NULL, '2026-01-27 10:06:00'),
(16, 16, 0, NULL, NULL, 2.00, '0', 49990.00, 99980.00, 'HP Laptop 15-fd1168TU', '4', '1', NULL, '2026-01-27 10:09:51');

-- --------------------------------------------------------

--
-- Table structure for table `iirup_forms`
--

CREATE TABLE `iirup_forms` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iirup_forms`
--

INSERT INTO `iirup_forms` (`id`, `form_number`, `as_of_year`, `accountable_officer`, `designation`, `department_office`, `accountable_officer_name`, `accountable_officer_designation`, `authorized_official_name`, `authorized_official_designation`, `inspection_officer_name`, `witness_name`, `status`, `total_items`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'IIRUP-2026-7654', 2024, 'Direct Test Officer', 'Direct Test Designation', 'Head Office', 'Direct Test Name', 'Direct Test Designation', 'Direct Authorized', 'Direct Auth Designation', 'Direct Inspector', 'Direct Witness', 'draft', 1, 1, 1, '2026-01-17 06:07:44', '2026-01-17 06:07:44'),
(3, 'IIRUP-2026-9509', 2024, 'Debug Test Officer', 'Debug Test Designation', 'Head Office', 'Debug Test Name', 'Debug Test Designation', 'Debug Authorized', 'Debug Auth Designation', 'Debug Inspector', 'Debug Witness', 'draft', 1, 1, 1, '2026-01-17 06:09:27', '2026-01-17 06:09:27'),
(6, 'IIRUP-2026-7852', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-01-17 06:22:00', '2026-01-17 06:22:00'),
(7, 'IIRUP-2026-1778', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-01-17 06:27:46', '2026-01-17 06:27:46'),
(8, 'IIRUP-2026-9694', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-01-18 05:35:32', '2026-01-18 05:35:32'),
(13, 'IIRUP-2026-3353', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-01-18 14:07:33', '2026-01-18 14:07:33'),
(14, 'IIRUP-2026-9361', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-01-30 12:13:15', '2026-01-30 12:13:15'),
(15, 'IIRUP-2026-8615', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 2, 5, 5, '2026-02-01 06:45:21', '2026-02-01 06:45:21'),
(17, 'IIRUP-2026-4701', 2026, 'WALTON LONEZA', 'SUPPLY OFFICE', 'East District', 'test', 'test', 'test', 'test', 'test', 'test', 'draft', 1, 5, 5, '2026-02-04 23:55:27', '2026-02-04 23:55:27');

-- --------------------------------------------------------

--
-- Table structure for table `iirup_items`
--

CREATE TABLE `iirup_items` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iirup_items`
--

INSERT INTO `iirup_items` (`id`, `form_id`, `date_acquired`, `particulars`, `property_no`, `quantity`, `unit_cost`, `total_cost`, `accumulated_depreciation`, `impairment_losses`, `carrying_amount`, `inventory_remarks`, `disposal_sale`, `disposal_transfer`, `disposal_destruction`, `disposal_others`, `disposal_total`, `appraised_value`, `total`, `or_no`, `amount`, `dept_office`, `control_no`, `date_received`, `item_order`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-01-01', 'Direct Test Item', 'PROP-001', 1.00, 100.00, 100.00, 0.00, 0.00, 100.00, 'Direct test remarks', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'Head Office', 'CTRL-001', '2024-01-01', 1, '2026-01-17 06:07:44', '2026-01-17 06:07:44'),
(5, 6, '2026-01-07', 'Book Shelf (Steel, Open Type, 5-Layers)', '', 1.00, 6500.00, 6500.00, 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'Head Office', '', NULL, 1, '2026-01-17 06:22:00', '2026-01-17 06:22:00'),
(6, 7, '2026-01-07', 'Executive L-Desk (160cm, Wood Finish)', '', 1.00, 4500.00, 4500.00, 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'North District', '', NULL, 1, '2026-01-17 06:27:46', '2026-01-17 06:27:46'),
(7, 8, '2026-01-11', 'Laptop for Field Ops (Ruggedized, i5)', 'prop-0034', 1.00, 27500.00, 27500.00, 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'North District', '', NULL, 1, '2026-01-18 05:35:32', '2026-01-18 05:35:32'),
(12, 13, NULL, 'Laptop for Field Ops (Ruggedized, i5)', 'prop-0034', 1.00, 27500.00, 27500.00, 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'North District', '', NULL, 1, '2026-01-18 14:07:33', '2026-01-18 14:07:33'),
(13, 15, '2026-01-28', 'Laptop AMD Ryzen', 'PAR-0108', 1.00, 13140.00, 13140.00, 0.00, 0.00, 0.00, 'unserviceable', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'East District', '', NULL, 1, '2026-02-01 06:45:21', '2026-02-01 06:45:21'),
(14, 15, NULL, 'Computer desktop i7', 'PN-2019-05-02-0001-0134', 1.00, 40000.00, 40000.00, 0.00, 0.00, 0.00, 'unserviceable', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'Head Office', '', NULL, 2, '2026-02-01 06:45:21', '2026-02-01 06:45:21'),
(16, 17, '2026-01-28', 'Laptop AMD Ryzen', 'PAR-0110', 1.00, 13140.00, 13140.00, 0.00, 0.00, 0.00, 'unserviceable', 0.00, 0.00, 0.00, '', 0.00, 0.00, 0.00, '', 0.00, 'East District', '', NULL, 1, '2026-02-04 23:55:27', '2026-02-04 23:55:27');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_tags`
--

CREATE TABLE `inventory_tags` (
  `id` int(11) NOT NULL,
  `asset_item_id` int(11) NOT NULL,
  `tag_number` varchar(100) NOT NULL,
  `property_number` varchar(100) NOT NULL,
  `item_description` text NOT NULL,
  `category_id` int(11) NOT NULL,
  `person_accountable` int(11) NOT NULL,
  `end_user` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `condition` enum('Excellent','Good','Fair','Poor','Damaged') NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_tags`
--

INSERT INTO `inventory_tags` (`id`, `asset_item_id`, `tag_number`, `property_number`, `item_description`, `category_id`, `person_accountable`, `end_user`, `location`, `condition`, `created_by`, `created_at`, `updated_by`, `updated_at`) VALUES
(2, 54, 'INV-2026-00002', 'PAR-0002', 'SDD', 2, 1, 'John Legend', 'Main Office', 'Good', 5, '2026-01-23 09:36:28', NULL, '2026-01-23 09:36:28'),
(3, 54, 'INV-2026-00003', 'PAR-0002', 'SDD', 2, 1, 'John Legend', 'Main Office', 'Good', 5, '2026-01-23 09:45:57', NULL, '2026-01-23 09:45:57'),
(4, 54, 'INV-2026-00004', 'PAR-0002', 'SDD', 1, 1, 'Angela Rizal', 'Main Office', 'Good', 5, '2026-01-23 09:47:20', NULL, '2026-01-23 09:47:20'),
(5, 54, 'INV-2026-00005', 'PAR-0002', 'SDD', 2, 1, 'Angela Rizal', 'Main Office', 'Good', 5, '2026-01-23 09:51:28', NULL, '2026-01-23 09:51:28'),
(6, 53, 'INV-2026-00006', 'PAR-000245', 'Power Drill (corded) ', 1, 3, 'Jake Paul', 'Main Office', 'Good', 5, '2026-01-23 10:04:53', NULL, '2026-01-23 10:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_tag_attachments`
--

CREATE TABLE `inventory_tag_attachments` (
  `id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `asset_item_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_tag_attachments`
--

INSERT INTO `inventory_tag_attachments` (`id`, `tag_id`, `asset_item_id`, `filename`, `original_name`, `file_type`, `file_size`, `file_path`, `created_by`, `created_at`) VALUES
(1, 1, 43, 'asset_43_1769142262.jpeg', 'images (11).jpeg', 'image/jpeg', 18026, 'uploads/inventory_tags/asset_43_1769142262.jpeg', 17, '2026-01-23 04:24:22'),
(2, 2, 54, 'asset_54_1769160988.jpeg', 'images (8).jpeg', 'image/jpeg', 17146, 'uploads/inventory_tags/asset_54_1769160988.jpeg', 5, '2026-01-23 09:36:28'),
(3, 3, 54, 'asset_54_1769161557.jpeg', 'images (8).jpeg', 'image/jpeg', 17146, 'uploads/inventory_tags/asset_54_1769161557.jpeg', 5, '2026-01-23 09:45:57'),
(4, 4, 54, 'asset_54_1769161640.jpeg', 'images (8).jpeg', 'image/jpeg', 17146, 'uploads/inventory_tags/asset_54_1769161640.jpeg', 5, '2026-01-23 09:47:20'),
(5, 5, 54, 'asset_54_1769161888.jpeg', 'images (8).jpeg', 'image/jpeg', 17146, 'uploads/inventory_tags/asset_54_1769161888.jpeg', 5, '2026-01-23 09:51:28');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_tag_history`
--

CREATE TABLE `inventory_tag_history` (
  `id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `asset_item_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Submitted, Approved, Rejected, Processed, Updated',
  `details` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_tag_history`
--

INSERT INTO `inventory_tag_history` (`id`, `tag_id`, `asset_item_id`, `action`, `details`, `created_by`, `created_at`) VALUES
(1, 1, 43, 'Submitted', 'Tag submission created for item ID 43: Tag Number: INV-2026-00001, Property Number: prop-0034, Category: ITS (Image: asset_43_1769142262.jpeg)', 17, '2026-01-23 04:24:22'),
(2, 2, 54, 'Submitted', 'Tag submission created for item ID 54: Tag Number: INV-2026-00002, Property Number: PAR-0002, Category: ITS (Image: asset_54_1769160988.jpeg)', 5, '2026-01-23 09:36:28'),
(3, 3, 54, 'Submitted', 'Tag submission created for item ID 54: Tag Number: INV-2026-00003, Property Number: PAR-0002, Category: ITS (Image: asset_54_1769161557.jpeg)', 5, '2026-01-23 09:45:57'),
(4, 4, 54, 'Submitted', 'Tag submission created for item ID 54: Tag Number: INV-2026-00004, Property Number: PAR-0002, Category: FF (Image: asset_54_1769161640.jpeg)', 5, '2026-01-23 09:47:20'),
(5, 5, 54, 'Submitted', 'Tag submission created for item ID 54: Tag Number: INV-2026-00005, Property Number: PAR-0002, Category: ITS (Image: asset_54_1769161888.jpeg)', 5, '2026-01-23 09:51:28'),
(6, 6, 53, 'Submitted', 'Tag submission created for item ID 53: Tag Number: INV-2026-00006, Property Number: PAR-000245, Category: FF', 5, '2026-01-23 10:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `itr_forms`
--

CREATE TABLE `itr_forms` (
  `id` int(11) NOT NULL,
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
  `requested_date` date NOT NULL,
  `approved_by` varchar(100) NOT NULL,
  `approved_by_position` varchar(100) NOT NULL,
  `approved_date` date NOT NULL,
  `released_by` varchar(100) NOT NULL,
  `released_by_position` varchar(100) NOT NULL,
  `released_date` date NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `received_by_position` varchar(100) NOT NULL,
  `received_date` date NOT NULL,
  `status` enum('draft','submitted','approved','released','received','cancelled') DEFAULT 'draft',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itr_forms`
--

INSERT INTO `itr_forms` (`id`, `entity_name`, `fund_cluster`, `itr_no`, `from_office`, `to_office`, `transfer_date`, `transfer_type`, `transfer_type_others`, `end_user`, `purpose`, `requested_by`, `requested_by_position`, `requested_date`, `approved_by`, `approved_by_position`, `approved_date`, `released_by`, `released_by_position`, `released_date`, `received_by`, `received_by_position`, `received_date`, `status`, `total_amount`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00004', '1', '6', '2026-01-27', 'Reassignment', '', 'Roberto Cruz', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 89999.00, 5, 5, '2026-01-27 08:55:08', '2026-01-27 08:55:08'),
(2, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00005', '1', '6', '2026-01-27', 'Reassignment', '', 'Roberto Cruz', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 89999.00, 5, 5, '2026-01-27 09:01:55', '2026-01-27 09:01:55'),
(3, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00006', '1', '6', '2026-01-27', 'Reassignment', '', 'Roberto Cruz', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:04:56', '2026-01-27 09:04:56'),
(4, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00007', '1', '6', '2026-01-27', 'Reassignment', '', 'Angela Rizal', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:29:37', '2026-01-27 09:29:37'),
(5, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00008', '1', '6', '2026-01-27', 'Reassignment', '', 'Jack Robertson', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:30:21', '2026-01-27 09:30:21'),
(6, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00009', '1', '6', '2026-01-27', 'Reassignment', '', 'John Kenneth Litana', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:33:09', '2026-01-27 09:33:09'),
(7, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00010', '1', '6', '2026-01-27', 'Reassignment', '', 'John Legend', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:36:08', '2026-01-27 09:36:08'),
(8, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00011', '1', '6', '2026-01-27', 'Reassignment', '', 'Jack Robertson', 'Reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 09:42:34', '2026-01-27 09:42:34'),
(9, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00012', '1', '6', '2026-01-27', 'Reassignment', '', 'Jake Paul', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 11:00:27', '2026-01-27 11:00:27'),
(12, 'LGU PILAR', 'COMPUTERIZATION', 'ITR-2026-00015', '6', '1', '2026-01-27', 'Reassignment', '', 'Angela Rizal', 'reassignment', '', '', '0000-00-00', 'Leroy Brown', 'Mayor', '0000-00-00', 'Daniel Atlas', 'Officer', '0000-00-00', 'Henley Reeves', 'Property Custodian', '0000-00-00', 'draft', 0.00, 5, 5, '2026-01-27 12:00:37', '2026-01-27 12:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `itr_items`
--

CREATE TABLE `itr_items` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itr_items`
--

INSERT INTO `itr_items` (`id`, `form_id`, `item_no`, `date_acquired`, `ics_par_no`, `description`, `quantity`, `unit`, `unit_price`, `total_amount`, `condition_of_inventory`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-25', 'PAR-0007', '62', 1.00, 'pcs', 89999.00, 89999.00, 'serviceable', NULL, '2026-01-27 08:55:08', '2026-01-27 08:55:08'),
(2, 2, 1, '2026-01-25', 'PAR-0007', '62', 1.00, 'pcs', 89999.00, 89999.00, 'serviceable', NULL, '2026-01-27 09:01:55', '2026-01-27 09:01:55');

--
-- Triggers `itr_items`
--
DELIMITER $$
CREATE TRIGGER `itr_items_after_delete` AFTER DELETE ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = OLD.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.form_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `itr_items_after_insert` AFTER INSERT ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = NEW.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.form_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `itr_items_after_update` AFTER UPDATE ON `itr_items` FOR EACH ROW BEGIN
    UPDATE itr_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM itr_items 
        WHERE form_id = NEW.form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.form_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `itr_summary`
-- (See below for the actual view)
--
CREATE TABLE `itr_summary` (
`id` int(11)
,`entity_name` varchar(255)
,`fund_cluster` varchar(100)
,`itr_no` varchar(50)
,`from_office` varchar(255)
,`to_office` varchar(255)
,`transfer_date` date
,`transfer_type` enum('Donation','Reassignment','Relocate','Others')
,`end_user` varchar(100)
,`purpose` text
,`status` enum('draft','submitted','approved','released','received','cancelled')
,`total_amount` decimal(12,2)
,`item_count` bigint(21)
,`created_by` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `failure_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error','system') DEFAULT 'info',
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_id`, `related_type`, `is_read`, `created_at`, `read_at`) VALUES
(2, 1, 'New Asset Added', 'A new asset has been added to the inventory.', 'info', 1, 'asset', 0, '2026-02-05 12:58:08', NULL),
(3, 1, 'System Update', 'The system has been updated with new features.', 'system', NULL, NULL, 0, '2026-02-05 12:58:08', NULL),
(4, 1, 'Low Stock Alert', 'Some consumables are running low on stock.', 'warning', NULL, 'consumable', 1, '2026-02-05 12:58:08', '2026-02-11 03:30:05'),
(5, 1, 'Maintenance Reminder', 'Scheduled maintenance is due for some assets.', 'warning', NULL, 'asset', 0, '2026-02-05 12:58:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `system_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `asset_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `employee_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `user_id`, `email_notifications`, `system_notifications`, `asset_notifications`, `employee_notifications`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, '2026-02-05 12:58:08', '2026-02-05 12:58:08'),
(2, 2, 1, 1, 1, 1, '2026-02-05 12:58:08', '2026-02-05 12:58:08'),
(3, 5, 1, 1, 1, 1, '2026-02-05 12:58:08', '2026-02-05 12:58:08');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
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
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `office_name`, `office_code`, `address`, `state`, `postal_code`, `country`, `phone`, `email`, `capacity`, `status`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 'Head Office', 'HO', '123 Municipal Hall, Makati Avenue', 'Metro Manila', '1200', 'Philippines', '+63-2-8888-0001', 'headoffice@pims.com', 100, 'active', '2026-01-06 07:30:05', '2026-01-06 07:30:05', 1, NULL),
(2, 'North District', 'ND', '456 North Avenue, Quezon City', 'Metro Manila', '1100', 'Philippines', '+63-2-8888-0002', 'north@pims.com', 50, 'active', '2026-01-06 07:30:05', '2026-01-06 07:30:05', 1, NULL),
(3, 'South District', 'SD', '789 South Expressway, Alabang', 'Metro Manila', '1770', 'Philippines', '+63-2-8888-0003', 'south@pims.com', 40, 'active', '2026-01-06 07:30:05', '2026-01-06 07:30:05', 1, NULL),
(4, 'East District', 'EDS', '321 East Road, Pasig City', 'Metro Manila', '1600', 'Philippines', '+63-2-8888-0004', 'east@pims.com', 35, 'active', '2026-01-06 07:30:05', '2026-01-06 07:51:02', 1, 1),
(5, 'West District', 'WD', '654 West Boulevard, Manila', 'Metro Manila', '1000', 'Philippines', '+63-2-8888-0005', 'west@pims.com', 45, 'active', '2026-01-06 07:30:05', '2026-01-06 07:30:05', 1, NULL),
(6, 'Test Office', 'TO', '123 Test Address', 'Test State', '1234', 'Philippines', '123-456-7890', 'test@example.com', 25, 'active', '2026-01-06 07:33:52', '2026-01-06 07:33:52', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `online_backup_configs`
--

CREATE TABLE `online_backup_configs` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `online_backup_configs`
--

INSERT INTO `online_backup_configs` (`id`, `provider`, `config_name`, `api_key`, `api_secret`, `access_token`, `refresh_token`, `bucket_name`, `folder_path`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'google_drive', 'Google Drive Backup', '822354506716-8on8lknqkudd71ae2v935aq7hkvib4kq.apps.googleusercontent.com', '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', 'ya29.a0Aa7pCA_l51pZ0iktGhYIs71GN9o2QiGcsand7k9CxDQQpyBQtRjgc7NMToZUlZnMFC9nSmeFQrc7zGywdTGWMPBDy9iBOEpf_QFf3eJOUcJZNUfePIGPhO7sZb3DotyyEx2airAPPBUTE-RATZ8DGzG2PkyO3RKPCyk9iPKl588ACXk9gqQvLeS7vheLMBYlHRxe5V4aCgYKAe4SARESFQHGX2MimGU0dLbDFtN0I3aLKjKrYw0206', '1//0evabxpw2eEOKCgYIARAAGA4SNwF-L9IrMe3TQwU8ekRvEgkTEhkFCqiF1gTHfJAjgWmoOswjzBmrsnJj2HPUfXOctbCgDhxvpc8', '', 'backup', 1, 1, '2026-01-04 13:52:31', '2026-01-05 13:59:23'),
(2, 'dropbox', 'Dropbox Backup', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-01-04 13:52:31', '2026-01-04 13:52:31'),
(3, 'onedrive', 'OneDrive Backup', NULL, NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-01-04 13:52:31', '2026-01-04 13:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `par_form`
--

CREATE TABLE `par_form` (
  `id` int(11) NOT NULL,
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
  `date_received_right` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `par_forms`
--

CREATE TABLE `par_forms` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `par_forms`
--

INSERT INTO `par_forms` (`id`, `entity_name`, `fund_cluster`, `par_no`, `office_location`, `received_by_name`, `received_by_position`, `received_by_date`, `issued_by_name`, `issued_by_position`, `issued_by_date`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(6, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0008-2026', 'East District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:25:02', '2026-01-23 08:25:02'),
(8, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0010-2026', 'Head Office', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:40:32', '2026-01-23 08:40:32'),
(9, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0011-2026', 'North District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:43:34', '2026-01-23 08:43:34'),
(10, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0012-2026', 'East District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:46:41', '2026-01-23 08:46:41'),
(11, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0013-2026', 'South District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:50:11', '2026-01-23 08:50:11'),
(12, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0014-2026', 'North District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:53:00', '2026-01-23 08:53:00'),
(16, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0018-2026', 'West District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 08:56:54', '2026-01-23 08:56:54'),
(17, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0019-2026', 'Head Office', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-23 09:35:20', '2026-01-23 09:35:20'),
(18, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0020-2026', 'Head Office', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-25 12:28:27', '2026-01-25 12:28:27'),
(19, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0021-2026', 'East District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-25 12:51:40', '2026-01-25 12:51:40'),
(20, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0022-2026', 'East District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-25 13:11:47', '2026-01-25 13:11:47'),
(22, 'LGU PILAR', 'COMPUTERIZATION', 'PAR-0024-2026', 'Head Office', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-27 01:53:39', '2026-01-27 01:53:39'),
(23, 'INVENTORY', 'COMPUTERIZATION', 'PAR-0025-2026', 'East District', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'LEO PETERSON', 'CLERK', '0000-00-00', NULL, 5, 5, '2026-01-28 13:18:38', '2026-01-28 13:18:38');

-- --------------------------------------------------------

--
-- Table structure for table `par_items`
--

CREATE TABLE `par_items` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `item_no` int(11) NOT NULL DEFAULT 1,
  `asset_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `property_number` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `par_items`
--

INSERT INTO `par_items` (`id`, `form_id`, `item_no`, `asset_id`, `quantity`, `unit`, `description`, `property_number`, `date_acquired`, `unit_price`, `amount`) VALUES
(5, 6, 1, NULL, 2.00, 'Pieces', 'Laptop AMD Ryzen', '0', '2026-01-23', NULL, 45000.00),
(6, 8, 1, NULL, 1.00, 'Units', 'Computer desktop i7', '0', '2026-01-23', NULL, 40000.00),
(7, 9, 1, 25, 1.00, 'Pieces', 'Mouse', '0', '2026-01-23', NULL, 340.00),
(8, 10, 1, 26, 1.00, 'Units', 'Hilux Van (MR-2025-00033)', '0', '2026-01-23', NULL, 45000.00),
(9, 11, 1, 27, 1.00, 'Units', 'Office Table – Wooden', '0', '2026-01-23', NULL, 50000.00),
(10, 12, 1, 28, 1.00, 'Units', 'Printer Epson', '0', '2026-01-23', NULL, 45000.00),
(14, 16, 1, 32, 1.00, 'Units', 'Power Drill (corded) ', '0', '2026-01-23', NULL, 56000.00),
(15, 17, 1, 33, 1.00, 'Units', 'SDD', '0', '2026-01-23', NULL, 52000.00),
(16, 18, 1, 35, 1.00, 'Pieces', 'Apple MacBook Pro 14-inch (Space Grey)', '0', '2026-01-25', NULL, 110990.00),
(17, 19, 1, 37, 1.00, 'Units', 'Dell PowerEdge T160 Tower Server', '0', '2026-01-25', NULL, 97000.00),
(18, 20, 1, 40, 1.00, 'Units', 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', '0', '2026-01-25', NULL, 89999.00),
(20, 22, 1, 42, 1.00, 'Pieces', 'Acer Predator Helios Neo 16 (PHN16-72-92K1)', '0', '2026-01-27', NULL, 89999.00),
(21, 23, 1, 46, 5.00, 'Sets', 'Laptop AMD Ryzen', '0', '2026-01-28', NULL, 65700.00);

-- --------------------------------------------------------

--
-- Table structure for table `password_policies`
--

CREATE TABLE `password_policies` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_policies`
--

INSERT INTO `password_policies` (`id`, `policy_name`, `min_length`, `require_uppercase`, `require_lowercase`, `require_numbers`, `require_special_chars`, `max_age_days`, `prevent_reuse`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Policy', 8, 1, 1, 1, 1, 90, 5, 1, '2026-01-06 02:28:39', '2026-01-06 02:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`, `is_used`) VALUES
(15, 'wjll2022-2920-98466@bicol-u.edu.ph', 'e0f3d647ee489b60aebc048e0ff23a379e4e12e2cfb23ceb3ca7d73049992a2b', '2026-01-07 02:02:15', '2026-01-07 00:02:15', 0),
(17, 'waltonloneza@gmail.com', '686d6ee5df06e2a900ce04636b45b154aeeffc324742703abb67faacfd4c463e', '2026-01-07 02:04:59', '2026-01-07 00:04:59', 1);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`, `module`, `created_at`) VALUES
(1, 'users.create', 'Create new users', 'users', '2026-01-04 01:09:27'),
(2, 'users.read', 'View users list', 'users', '2026-01-04 01:09:27'),
(3, 'users.update', 'Edit user information', 'users', '2026-01-04 01:09:27'),
(4, 'users.delete', 'Delete users', 'users', '2026-01-04 01:09:27'),
(5, 'users.activate', 'Activate/deactivate users', 'users', '2026-01-04 01:09:27'),
(6, 'inventory.create', 'Add new products', 'inventory', '2026-01-04 01:09:27'),
(7, 'inventory.read', 'View products list', 'inventory', '2026-01-04 01:09:27'),
(8, 'inventory.update', 'Edit product information', 'inventory', '2026-01-04 01:09:27'),
(9, 'inventory.delete', 'Delete products', 'inventory', '2026-01-04 01:09:27'),
(10, 'inventory.transaction.in', 'Add stock (IN transactions)', 'inventory', '2026-01-04 01:09:27'),
(11, 'inventory.transaction.out', 'Remove stock (OUT transactions)', 'inventory', '2026-01-04 01:09:27'),
(12, 'categories.create', 'Create new categories', 'categories', '2026-01-04 01:09:27'),
(13, 'categories.read', 'View categories list', 'categories', '2026-01-04 01:09:27'),
(14, 'categories.update', 'Edit category information', 'categories', '2026-01-04 01:09:27'),
(15, 'categories.delete', 'Delete categories', 'categories', '2026-01-04 01:09:27'),
(16, 'reports.view', 'View system reports', 'reports', '2026-01-04 01:09:27'),
(17, 'reports.export', 'Export reports', 'reports', '2026-01-04 01:09:27'),
(18, 'system.settings', 'Access system settings', 'system', '2026-01-04 01:09:27'),
(19, 'system.logs', 'View system logs', 'system', '2026-01-04 01:09:27'),
(20, 'system.backup', 'Create system backup', 'system', '2026-01-04 01:09:27'),
(21, 'system.audit', 'Access security audit', 'system', '2026-01-04 01:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `red_tags`
--

CREATE TABLE `red_tags` (
  `id` int(11) NOT NULL,
  `control_no` varchar(50) NOT NULL,
  `red_tag_no` varchar(50) NOT NULL,
  `date_received` date NOT NULL,
  `tagged_by` varchar(100) NOT NULL,
  `item_location` varchar(255) NOT NULL,
  `item_description` text NOT NULL,
  `removal_reason` text NOT NULL,
  `action` varchar(50) NOT NULL,
  `office_id` int(11) DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `red_tags`
--

INSERT INTO `red_tags` (`id`, `control_no`, `red_tag_no`, `date_received`, `tagged_by`, `item_location`, `item_description`, `removal_reason`, `action`, `office_id`, `asset_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PS-5S-02-F-13-13', 'CTRL-0017', '2026-02-01', 'walton loneza', 'Head Office', 'Computer desktop i7', 'broken', 'repair', 1, 47, 5, '2026-02-01 09:23:51', '2026-02-01 09:23:51'),
(2, 'PS-5S-02-F-17-17', 'CTRL-0023', '2026-02-01', 'walton loneza', 'East District', 'Laptop AMD Ryzen', 'broken', 'repair', 4, 73, 5, '2026-02-01 10:14:04', '2026-02-01 10:14:04'),
(6, 'PS-5S-02-F-20-20', 'CTRL-0031', '2026-02-05', 'walton loneza', 'East District', 'Laptop AMD Ryzen', 'broken', 'repair', 4, 74, 5, '2026-02-05 00:03:51', '2026-02-05 00:03:51');

-- --------------------------------------------------------

--
-- Table structure for table `ris_forms`
--

CREATE TABLE `ris_forms` (
  `id` int(11) NOT NULL,
  `ris_no` varchar(50) NOT NULL,
  `sai_no` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `division` varchar(100) NOT NULL,
  `office` varchar(100) NOT NULL,
  `responsibility_center` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `date_2` date NOT NULL,
  `purpose` text NOT NULL,
  `requested_by` varchar(100) NOT NULL,
  `requested_by_position` varchar(100) NOT NULL,
  `requested_date` date NOT NULL,
  `approved_by` varchar(100) NOT NULL,
  `approved_by_position` varchar(100) NOT NULL,
  `approved_date` date NOT NULL,
  `issued_by` varchar(100) NOT NULL,
  `issued_by_position` varchar(100) NOT NULL,
  `issued_date` date NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `received_by_position` varchar(100) NOT NULL,
  `received_date` date NOT NULL,
  `status` enum('draft','submitted','approved','issued','received','cancelled') DEFAULT 'draft',
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ris_forms`
--

INSERT INTO `ris_forms` (`id`, `ris_no`, `sai_no`, `code`, `division`, `office`, `responsibility_center`, `date`, `date_2`, `purpose`, `requested_by`, `requested_by_position`, `requested_date`, `approved_by`, `approved_by_position`, `approved_date`, `issued_by`, `issued_by_position`, `issued_date`, `received_by`, `received_by_position`, `received_date`, `status`, `total_amount`, `created_by`, `created_at`, `updated_at`) VALUES
(2, '15-RIS-01-015', '01-2026-SAI-15', '0015', 'Finance Division', 'South District', 'Budget and Accounting Section', '2026-01-11', '2026-01-11', 'for printing', 'LEO PETERSON', 'MAYOR', '0000-00-00', 'CAROLYN SY-REYES', 'MAYOR', '0000-00-00', 'DANIEL ATLAS', 'CLERK', '0000-00-00', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'draft', 2950.00, 5, '2026-01-11 10:37:43', '2026-01-11 10:37:43'),
(3, '17-RIS-01-017', '01-2026-SAI-17', '0017', 'Finance Division', 'East District', 'Budget and Accounting Section', '2026-01-11', '2026-01-11', 'for ict', 'LEO PETERSON', 'MAYOR', '0000-00-00', 'CAROLYN SY-REYES', 'MAYOR', '0000-00-00', 'DANIEL ATLAS', 'CLERK', '0000-00-00', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'draft', 1700.00, 5, '2026-01-11 10:53:51', '2026-01-11 10:53:51'),
(4, '18-RIS-01-018', '01-2026-SAI-18', '0018', 'Finance Division', 'Head Office', 'Budget and Accounting Section', '2026-01-11', '2026-01-11', 'for ict', 'LEO PETERSON', 'MAYOR', '0000-00-00', 'CAROLYN SY-REYES', 'MAYOR', '0000-00-00', 'DANIEL ATLAS', 'CLERK', '0000-00-00', 'BENJAMIN THOMPSON', 'PROPERTY CUSTODIAN', '0000-00-00', 'draft', 6500.00, 5, '2026-01-11 10:57:20', '2026-01-11 10:57:20');

-- --------------------------------------------------------

--
-- Table structure for table `ris_items`
--

CREATE TABLE `ris_items` (
  `id` int(11) NOT NULL,
  `ris_form_id` int(11) NOT NULL,
  `stock_no` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ris_items`
--

INSERT INTO `ris_items` (`id`, `ris_form_id`, `stock_no`, `unit`, `description`, `quantity`, `price`, `total_amount`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 'pcs', '0', 10.00, 295.00, 2950.00, '2026-01-11 10:37:43', '2026-01-11 10:37:43'),
(3, 3, 1, 'pcs', '0', 2.00, 850.00, 1700.00, '2026-01-11 10:53:51', '2026-01-11 10:53:51'),
(4, 4, 1, 'pcs', 'UTP Cable (Cat6, 305m/Box)', 1.00, 6500.00, 6500.00, '2026-01-11 10:57:20', '2026-01-11 10:57:20');

--
-- Triggers `ris_items`
--
DELIMITER $$
CREATE TRIGGER `ris_items_after_delete` AFTER DELETE ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = OLD.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = OLD.ris_form_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `ris_items_after_insert` AFTER INSERT ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `ris_items_after_update` AFTER UPDATE ON `ris_items` FOR EACH ROW BEGIN
    UPDATE ris_forms 
    SET total_amount = (
        SELECT COALESCE(SUM(total_amount), 0) 
        FROM ris_items 
        WHERE ris_form_id = NEW.ris_form_id
    ),
    updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.ris_form_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `ris_summary`
-- (See below for the actual view)
--
CREATE TABLE `ris_summary` (
`id` int(11)
,`ris_no` varchar(50)
,`sai_no` varchar(50)
,`code` varchar(50)
,`division` varchar(100)
,`office` varchar(100)
,`responsibility_center` varchar(100)
,`date` date
,`purpose` text
,`status` enum('draft','submitted','approved','issued','received','cancelled')
,`total_amount` decimal(12,2)
,`item_count` bigint(21)
,`created_by` int(11)
,`first_name` varchar(50)
,`last_name` varchar(50)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role` enum('system_admin','admin','office_admin','user') NOT NULL,
  `permission_id` int(11) NOT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_read` tinyint(1) DEFAULT 1,
  `can_update` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role`, `permission_id`, `can_create`, `can_read`, `can_update`, `can_delete`, `created_at`) VALUES
(1, 'system_admin', 12, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(2, 'system_admin', 15, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(3, 'system_admin', 13, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(4, 'system_admin', 14, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(5, 'system_admin', 6, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(6, 'system_admin', 9, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(7, 'system_admin', 7, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(8, 'system_admin', 10, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(9, 'system_admin', 11, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(10, 'system_admin', 8, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(11, 'system_admin', 17, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(12, 'system_admin', 16, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(13, 'system_admin', 21, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(14, 'system_admin', 20, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(15, 'system_admin', 19, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(16, 'system_admin', 18, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(17, 'system_admin', 5, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(18, 'system_admin', 1, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(19, 'system_admin', 4, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(20, 'system_admin', 2, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(21, 'system_admin', 3, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(32, 'admin', 12, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(33, 'admin', 15, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(34, 'admin', 13, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(35, 'admin', 14, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(36, 'admin', 6, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(37, 'admin', 9, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(38, 'admin', 7, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(39, 'admin', 10, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(40, 'admin', 11, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(41, 'admin', 8, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(42, 'admin', 17, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(43, 'admin', 16, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(44, 'admin', 21, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(45, 'admin', 20, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(46, 'admin', 19, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(47, 'admin', 18, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(48, 'admin', 5, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(49, 'admin', 1, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(50, 'admin', 4, 1, 1, 1, 0, '2026-01-04 01:09:27'),
(51, 'admin', 2, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(52, 'admin', 3, 1, 1, 1, 1, '2026-01-04 01:09:27'),
(63, 'office_admin', 12, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(64, 'office_admin', 15, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(65, 'office_admin', 13, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(66, 'office_admin', 14, 1, 0, 1, 0, '2026-01-04 01:09:27'),
(67, 'office_admin', 6, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(68, 'office_admin', 9, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(69, 'office_admin', 7, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(70, 'office_admin', 10, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(71, 'office_admin', 11, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(72, 'office_admin', 8, 1, 0, 1, 0, '2026-01-04 01:09:27'),
(73, 'office_admin', 17, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(74, 'office_admin', 16, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(75, 'office_admin', 21, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(76, 'office_admin', 20, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(77, 'office_admin', 19, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(78, 'office_admin', 18, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(79, 'office_admin', 5, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(80, 'office_admin', 1, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(81, 'office_admin', 4, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(82, 'office_admin', 2, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(83, 'office_admin', 3, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(94, 'user', 12, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(95, 'user', 15, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(96, 'user', 13, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(97, 'user', 14, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(98, 'user', 6, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(99, 'user', 9, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(100, 'user', 7, 0, 1, 0, 0, '2026-01-04 01:09:27'),
(101, 'user', 10, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(102, 'user', 11, 1, 0, 0, 0, '2026-01-04 01:09:27'),
(103, 'user', 8, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(104, 'user', 17, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(105, 'user', 16, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(106, 'user', 21, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(107, 'user', 20, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(108, 'user', 19, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(109, 'user', 18, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(110, 'user', 5, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(111, 'user', 1, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(112, 'user', 4, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(113, 'user', 2, 0, 0, 0, 0, '2026-01-04 01:09:27'),
(114, 'user', 3, 0, 0, 0, 0, '2026-01-04 01:09:27');

-- --------------------------------------------------------

--
-- Table structure for table `scheduled_backups`
--

CREATE TABLE `scheduled_backups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `backup_type` enum('full','database','files') DEFAULT 'full',
  `schedule_type` enum('daily','weekly','monthly') DEFAULT 'daily',
  `schedule_day` int(11) DEFAULT NULL,
  `schedule_time` time DEFAULT '00:00:00',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_alerts`
--

CREATE TABLE `security_alerts` (
  `id` int(11) NOT NULL,
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
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_audit_logs`
--

CREATE TABLE `security_audit_logs` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_logs`
--

CREATE TABLE `security_logs` (
  `id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'low',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `security_logs`
--

INSERT INTO `security_logs` (`id`, `event_type`, `description`, `severity`, `user_id`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 05:32:28'),
(2, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 07:10:21'),
(3, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 08:16:28'),
(4, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 11:08:29'),
(5, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 12:19:11'),
(6, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 14:07:19'),
(7, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-06 15:13:56'),
(8, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 01:16:25'),
(9, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 03:33:30'),
(10, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 04:47:29'),
(11, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 08:21:21'),
(12, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 10:35:44'),
(13, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 11:36:36'),
(14, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 13:26:10'),
(15, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 15:22:08'),
(16, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 23:47:41'),
(17, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:51:35'),
(18, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:39'),
(19, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:53:02'),
(20, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:24:33'),
(21, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:22:04'),
(22, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:22:42'),
(23, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:24:56'),
(24, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:37:27'),
(25, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:42:45'),
(26, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:08'),
(27, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:21:21'),
(28, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:49:28'),
(29, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 13:57:57'),
(30, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:04'),
(31, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:24:31'),
(32, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:29:57'),
(33, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 04:57:00'),
(34, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:01'),
(35, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:15:52'),
(36, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:21'),
(37, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:38:20'),
(38, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:20:05'),
(39, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:23:34'),
(40, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 04:28:42'),
(41, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:26:49'),
(42, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 11:42:43'),
(43, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:59:52'),
(44, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:56:44'),
(45, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:14:51'),
(46, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:28:35'),
(47, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:50:21'),
(48, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:41:50'),
(49, 'session_timeout', 'Session timeout for user: Walton loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:34:56'),
(50, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:03:38'),
(51, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:37'),
(52, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:28:55'),
(53, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:46:10'),
(54, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:41:24'),
(55, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:15'),
(56, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:42'),
(57, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:10:39'),
(58, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:16'),
(59, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:23:55'),
(60, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:30:48'),
(61, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:28:48'),
(62, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:09:04'),
(63, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:36:52'),
(64, 'session_timeout', 'Session timeout for user: Walton Loneza (waltonloneza@gmail.com)', 'medium', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:13'),
(65, 'session_timeout', 'Session timeout for user: Joshua Esc (joshuamarifrancis@gmail.com)', 'medium', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:07:38'),
(66, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 06:42:27'),
(67, 'session_timeout', 'Session timeout for user: System Administrator (admin@pims.com)', 'medium', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 07:55:49'),
(68, 'session_timeout', 'Session timeout for user: Joshua Esc (joshuamarifrancis@gmail.com)', 'medium', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:31:11'),
(69, 'session_timeout', 'Session timeout for user: Joshua Esc (joshuamarifrancis@gmail.com)', 'medium', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:00:29');

-- --------------------------------------------------------

--
-- Table structure for table `security_metrics`
--

CREATE TABLE `security_metrics` (
  `id` int(11) NOT NULL,
  `metric_date` date NOT NULL,
  `total_users` int(11) NOT NULL,
  `active_users` int(11) NOT NULL,
  `failed_logins` int(11) DEFAULT 0,
  `successful_logins` int(11) DEFAULT 0,
  `password_changes` int(11) DEFAULT 0,
  `security_alerts` int(11) DEFAULT 0,
  `audit_score` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(904, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:32:24'),
(905, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:32:26'),
(906, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:33:03'),
(907, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:33:20'),
(908, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:33:20'),
(909, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:34:04'),
(910, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:34:28'),
(911, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:42:49'),
(912, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:43:51'),
(913, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:45:34'),
(914, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 00:54:06'),
(915, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 01:11:00'),
(916, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 01:11:00'),
(917, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 01:20:02'),
(918, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 01:20:02'),
(919, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 01:28:44'),
(920, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:05:01'),
(921, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:05:01'),
(922, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:05:07'),
(923, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:09:56'),
(924, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:14:54'),
(925, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:17:58'),
(926, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:18:09'),
(927, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:22:12'),
(928, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:22:35'),
(929, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:27:28'),
(930, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:27:31'),
(931, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:32:44'),
(932, 5, 'logout', 'authentication', 'User logged out: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:37:29'),
(933, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:37:40'),
(934, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:37:40'),
(935, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:45:18'),
(936, 1, 'Reset tag counter', 'tags', 'Tag type: itr_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:46:32'),
(937, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:46:32'),
(938, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:46:33'),
(939, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:46:42'),
(940, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:46:43'),
(941, 1, 'Reset tag counter', 'tags', 'Tag type: sai_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:47:01'),
(942, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:47:01'),
(943, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:47:01'),
(944, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:48:00'),
(945, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 03:59:17'),
(946, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:01:27'),
(947, 1, 'Reset tag counter', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:03:07'),
(948, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:03:07'),
(949, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:03:07'),
(950, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:03:29'),
(951, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:04:18'),
(952, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:06:36'),
(953, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:08:13'),
(954, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:12:14'),
(955, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:12:14'),
(956, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:14:01'),
(957, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:14:01'),
(958, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:17:45'),
(959, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:17:45'),
(960, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:18:19'),
(961, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:18:19'),
(962, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:19:08'),
(963, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:19:08'),
(964, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:22:28'),
(965, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:22:28'),
(966, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:23:03'),
(967, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:23:03'),
(968, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:24:46'),
(969, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:24:46'),
(970, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:26:19'),
(971, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:26:19'),
(972, 1, 'Updated tag format', 'tags', 'Tag type: property_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:26:34'),
(973, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 04:26:34'),
(974, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:07:35'),
(975, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:07:35'),
(976, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:07:45'),
(977, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:08:08'),
(978, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:08:13'),
(979, 5, 'logout', 'authentication', 'User logged out: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:08:30'),
(980, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:08:55'),
(981, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:08:56'),
(982, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:09:32'),
(983, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:23:51'),
(984, 1, 'Updated tag format', 'tags', 'Tag type: ics_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:25:27'),
(985, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:25:27'),
(986, 1, 'Updated tag format', 'tags', 'Tag type: itr_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:30:48'),
(987, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:30:48'),
(988, 1, 'Updated tag format', 'tags', 'Tag type: par_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:42:59'),
(989, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:42:59'),
(990, 1, 'Updated tag format', 'tags', 'Tag type: ris_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:44:09'),
(991, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:44:09'),
(992, 1, 'Updated tag format', 'tags', 'Tag type: sai_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:44:49'),
(993, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:44:49'),
(994, 1, 'Updated tag format', 'tags', 'Tag type: code', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:45:35'),
(995, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:45:35'),
(996, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:47:34'),
(997, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:47:37'),
(998, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:48:21'),
(999, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:48:21'),
(1000, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:49:43'),
(1001, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:49:47'),
(1002, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:56:11'),
(1003, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 09:59:14'),
(1004, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:00:18'),
(1005, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:00:20'),
(1006, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:00:23'),
(1007, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:00:31'),
(1008, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:05:24'),
(1009, 17, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:09:50'),
(1010, 17, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:10:20'),
(1011, 17, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:10:29'),
(1012, 17, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:10:49'),
(1013, 17, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:12:37'),
(1014, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:13:07'),
(1015, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:13:24'),
(1016, 17, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:13:37'),
(1017, 17, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:14:09'),
(1018, 17, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:46:57'),
(1019, 17, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:47:02'),
(1020, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3794 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:51:35'),
(1021, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:51:50'),
(1022, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:51:51'),
(1023, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:52:01'),
(1024, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:53:40'),
(1025, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:55:15'),
(1026, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:57:03'),
(1027, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 10:59:41'),
(1028, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:04:37'),
(1029, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:09:00'),
(1030, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:09:04'),
(1031, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:17:41'),
(1032, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:22:49'),
(1033, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:31:01'),
(1034, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:34:09'),
(1035, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:34:47'),
(1036, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:36:11'),
(1037, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:38:05'),
(1038, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:43:44'),
(1039, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:44:23'),
(1040, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:44:56'),
(1041, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0007, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:44:56'),
(1042, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:44:57'),
(1043, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:42'),
(1044, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:43'),
(1045, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:43'),
(1046, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:47'),
(1047, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:47'),
(1048, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:47'),
(1049, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:48'),
(1050, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:48'),
(1051, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:48'),
(1052, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:48'),
(1053, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:49:56'),
(1054, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:50:09'),
(1055, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:50:17'),
(1056, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:50:25'),
(1057, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:50:31'),
(1058, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:51:34'),
(1059, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:51:37'),
(1060, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3649 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:39'),
(1061, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:50'),
(1062, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:50'),
(1063, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:54'),
(1064, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:52:55'),
(1065, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 4, ICS No: ICS-01-0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:53:06'),
(1066, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:55:37'),
(1067, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:55:40'),
(1068, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:55:41'),
(1069, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:55:42'),
(1070, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:57:00'),
(1071, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:57:01'),
(1072, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:57:01'),
(1073, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:57:11'),
(1074, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:57:44'),
(1075, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 11:59:39'),
(1076, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:00:40'),
(1077, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:01:18'),
(1078, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:01:23'),
(1079, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:02:39'),
(1080, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0008, Entity: Head Office', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:02:39'),
(1081, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:02:40'),
(1082, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:02:43'),
(1083, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:02:49'),
(1084, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:04:49'),
(1085, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:06:37'),
(1086, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:07:14'),
(1087, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:09:16'),
(1088, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:11:02'),
(1089, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:12:03'),
(1090, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:12:39'),
(1091, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:18:16'),
(1092, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:21:25'),
(1093, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:22:39'),
(1094, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:24:27'),
(1095, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:24:34'),
(1096, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:24:37'),
(1097, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:26:38'),
(1098, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 5, ICS No: ICS-01-0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:32:22'),
(1099, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:32:30'),
(1100, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:40:14'),
(1101, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:40:30'),
(1102, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:43:33'),
(1103, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0009, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:43:33'),
(1104, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:43:33'),
(1105, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:43:35'),
(1106, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:44:26'),
(1107, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:44:47'),
(1108, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:44:52'),
(1109, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:48:48'),
(1110, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:49:27'),
(1111, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0010, Entity: North District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:49:27'),
(1112, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:49:27'),
(1113, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:49:33'),
(1114, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:49:36'),
(1115, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:51:04'),
(1116, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3612 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:53:02'),
(1117, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:53:14'),
(1118, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:53:14'),
(1119, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:53:20'),
(1120, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:56:59'),
(1121, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 12:57:18'),
(1122, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:05:36'),
(1123, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:07:23'),
(1124, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:08:08'),
(1125, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:08:18'),
(1126, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:08:46'),
(1127, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:08:52'),
(1128, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:08:59'),
(1129, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:09:02'),
(1130, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:09:04');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1131, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:11:31'),
(1132, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:19:06'),
(1133, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:21:39'),
(1134, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:23:44'),
(1135, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-09 13:25:19'),
(1136, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:45:00'),
(1137, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:45:01'),
(1138, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:45:05'),
(1139, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:45:36'),
(1140, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:45:44'),
(1141, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:46:19'),
(1142, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:46:26'),
(1143, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:50:20'),
(1144, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:50:25'),
(1145, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:54:02'),
(1146, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:54:03'),
(1147, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:55:45'),
(1148, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:55:46'),
(1149, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:56:54'),
(1150, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:58:08'),
(1151, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:59:15'),
(1152, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 09:59:50'),
(1153, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:01:08'),
(1154, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:01:31'),
(1155, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:02:59'),
(1156, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:03:27'),
(1157, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:04:32'),
(1158, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:06:08'),
(1159, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:08:04'),
(1160, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:09:34'),
(1161, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:10:10'),
(1162, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:10:39'),
(1163, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:11:22'),
(1164, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:12:02'),
(1165, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:18:32'),
(1166, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:23:18'),
(1167, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:24:40'),
(1168, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:25:05'),
(1169, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 10:29:35'),
(1170, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:41:59'),
(1171, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:41:59'),
(1172, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:42:03'),
(1173, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:42:21'),
(1174, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:42:23'),
(1175, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-10 12:55:55'),
(1176, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:19:47'),
(1177, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:19:47'),
(1178, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:19:50'),
(1179, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:19:54'),
(1180, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:20:10'),
(1181, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:24:05'),
(1182, 5, 'Printed ICS Form', 'forms', 'ICS ID: 7, ICS No: ICS-01-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:24:08'),
(1183, 5, 'Printed ICS Form', 'forms', 'ICS ID: 6, ICS No: ICS-01-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:28:07'),
(1184, 5, 'Printed ICS Form', 'forms', 'ICS ID: 6, ICS No: ICS-01-0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:32:17'),
(1185, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:35:02'),
(1186, 5, 'Exported ICS Data', 'forms', 'Format: excel, Date Range:  to ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:35:08'),
(1187, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:38:08'),
(1188, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:46:56'),
(1189, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:54:42'),
(1190, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:56:07'),
(1191, 5, 'Failed to create ICS form', 'forms', 'Error: Column count doesn\'t match value count at row 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:56:07'),
(1192, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:56:07'),
(1193, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 05:58:52'),
(1194, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:00:42'),
(1195, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0012, Entity: Head Office', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:00:42'),
(1196, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:00:43'),
(1197, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:01:32'),
(1198, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:03:03'),
(1199, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:05:53'),
(1200, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:07:23'),
(1201, 17, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:09:42'),
(1202, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3886 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 06:24:33'),
(1203, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:12:08'),
(1204, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:12:08'),
(1205, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:12:12'),
(1206, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:15:28'),
(1207, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:17:16'),
(1208, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:19:05'),
(1209, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:20:22'),
(1210, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:20:31'),
(1211, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:20:36'),
(1212, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:22:32'),
(1213, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:23:09'),
(1214, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:24:29'),
(1215, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:24:33'),
(1216, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:24:37'),
(1217, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:28:35'),
(1218, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:28:36'),
(1219, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:28:37'),
(1220, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:28:51'),
(1221, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:32:55'),
(1222, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:33:23'),
(1223, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0003-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:35:41'),
(1224, 5, 'Failed to create PAR form', 'forms', 'Error: Cannot add or update a child row: a foreign key constraint fails (`pims`.`par_items`, CONSTRAINT `par_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `par_form` (`id`) ON DELETE CASCADE)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:35:41'),
(1225, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:35:41'),
(1226, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:47:55'),
(1227, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0004-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:49:08'),
(1228, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0004-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:49:08'),
(1229, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:49:08'),
(1230, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:54:33'),
(1231, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0005-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:55:34'),
(1232, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:56:03'),
(1233, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0006-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:56:03'),
(1234, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 07:56:03'),
(1235, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4196 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:22:04'),
(1236, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:22:17'),
(1237, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:22:17'),
(1238, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:22:20'),
(1239, 5, 'Accessed PAR Entries', 'forms', 'par_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:40:15'),
(1240, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:41:55'),
(1241, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:44:28'),
(1242, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:44:29'),
(1243, 5, 'Viewed ICS Form', 'forms', 'ICS ID: 9, ICS No: ICS-01-0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:44:30'),
(1244, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:46:10'),
(1245, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:46:13'),
(1246, 5, 'Accessed PAR Entries', 'forms', 'par_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:46:14'),
(1247, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:46:19'),
(1248, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:50:43'),
(1249, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:51:44'),
(1250, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:52:36'),
(1251, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:54:01'),
(1252, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:55:45'),
(1253, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:55:51'),
(1254, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 08:59:09'),
(1255, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:20'),
(1256, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:26'),
(1257, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:36'),
(1258, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:37'),
(1259, 5, 'Printed ICS Form', 'forms', 'ICS ID: 9, ICS No: ICS-01-0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:38'),
(1260, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:47'),
(1261, 5, 'Accessed PAR Entries', 'forms', 'par_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:49'),
(1262, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:02:50'),
(1263, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:03:55'),
(1264, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:05:15'),
(1265, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:06:37'),
(1266, 5, 'Printed PAR Form', 'forms', 'PAR ID: 4, PAR No: PAR-0006-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:08:11'),
(1267, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 2, PAR No: PAR-0004-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:08:30'),
(1268, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:09:54'),
(1269, 5, 'Accessed PAR Entries', 'forms', 'par_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:10:03'),
(1270, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:10:09'),
(1271, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:14:22'),
(1272, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:19:31'),
(1273, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:20:55'),
(1274, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:21:30'),
(1275, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3625 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:22:42'),
(1276, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:22:54'),
(1277, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:22:54'),
(1278, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:22:58'),
(1279, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:27:48'),
(1280, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:40:38'),
(1281, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:54:00'),
(1282, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 09:56:29'),
(1283, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:01:13'),
(1284, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 02-RIS-01-002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:12'),
(1285, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-02', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:12'),
(1286, 5, 'Code counter incremented', 'forms', 'Generated Code: 0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:12'),
(1287, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 03-RIS-01-003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:47'),
(1288, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-03', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:47'),
(1289, 5, 'Code counter incremented', 'forms', 'Generated Code: 0003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:02:47'),
(1290, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 04-RIS-01-004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:04:26'),
(1291, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:04:26'),
(1292, 5, 'Code counter incremented', 'forms', 'Generated Code: 0004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:04:26'),
(1293, 5, 'Failed to create RIS form', 'forms', 'Error: Column \'requested_date\' cannot be null', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:04:26'),
(1294, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:04:26'),
(1295, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 05-RIS-01-005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:07'),
(1296, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:07'),
(1297, 5, 'Code counter incremented', 'forms', 'Generated Code: 0005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:07'),
(1298, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 06-RIS-01-006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:48'),
(1299, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:48'),
(1300, 5, 'Code counter incremented', 'forms', 'Generated Code: 0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:19:48'),
(1301, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3722 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:24:56'),
(1302, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:04'),
(1303, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:04'),
(1304, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:09'),
(1305, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 07-RIS-01-007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:53'),
(1306, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-07', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:53'),
(1307, 5, 'Code counter incremented', 'forms', 'Generated Code: 0007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:25:53'),
(1308, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 08-RIS-01-008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:26:49'),
(1309, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:26:49'),
(1310, 5, 'Code counter incremented', 'forms', 'Generated Code: 0008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:26:49'),
(1311, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 09-RIS-01-009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:14'),
(1312, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-09', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:14'),
(1313, 5, 'Code counter incremented', 'forms', 'Generated Code: 0009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:14'),
(1314, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 10-RIS-01-010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:42'),
(1315, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:42'),
(1316, 5, 'Code counter incremented', 'forms', 'Generated Code: 0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:28:42'),
(1317, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 11-RIS-01-011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:29:15'),
(1318, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:29:15'),
(1319, 5, 'Code counter incremented', 'forms', 'Generated Code: 0011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:29:15'),
(1320, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 12-RIS-01-012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:30:23'),
(1321, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:30:23'),
(1322, 5, 'Code counter incremented', 'forms', 'Generated Code: 0012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:30:23'),
(1323, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 13-RIS-01-013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:31:21'),
(1324, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:31:21'),
(1325, 5, 'Code counter incremented', 'forms', 'Generated Code: 0013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:31:21'),
(1326, 5, 'Failed to create RIS form', 'forms', 'Error: Column \'requested_date\' cannot be null', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:31:21'),
(1327, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:31:21'),
(1328, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:35:55'),
(1329, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 14-RIS-01-014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:36:48'),
(1330, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:36:48'),
(1331, 5, 'Code counter incremented', 'forms', 'Generated Code: 0014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:36:48'),
(1332, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 15-RIS-01-015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:37:43'),
(1333, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:37:43'),
(1334, 5, 'Code counter incremented', 'forms', 'Generated Code: 0015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:37:43'),
(1335, 5, 'Created RIS form', 'forms', 'RIS No: 15-RIS-01-015, Division: Finance Division, Office: South District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:37:43'),
(1336, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:37:43'),
(1337, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:41:18'),
(1338, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 16-RIS-01-016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:42:17'),
(1339, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:42:17'),
(1340, 5, 'Code counter incremented', 'forms', 'Generated Code: 0016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:42:17'),
(1341, 5, 'Failed to create RIS form', 'forms', 'Error: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near \'\\\'\\\'\'\', \'CAROLYN SY-REYES\', \'MAYOR\', \'\'\\\'\\\'\'\', \'LEO PETERSON\', \'CLERK\', \'\'\\\'\\...\' at line 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:42:17'),
(1342, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:42:17'),
(1343, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:44:57'),
(1344, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:45:00'),
(1345, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:51:44'),
(1346, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:08'),
(1347, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 17-RIS-01-017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:51'),
(1348, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:51'),
(1349, 5, 'Code counter incremented', 'forms', 'Generated Code: 0017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:51'),
(1350, 5, 'Created RIS form', 'forms', 'RIS No: 17-RIS-01-017, Division: Finance Division, Office: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:51');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1351, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:53:51'),
(1352, 5, 'RIS counter incremented', 'forms', 'Generated RIS number: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:57:20'),
(1353, 5, 'SAI counter incremented', 'forms', 'Generated SAI number: 01-2026-SAI-18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:57:20'),
(1354, 5, 'Code counter incremented', 'forms', 'Generated Code: 0018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:57:20'),
(1355, 5, 'Created RIS form', 'forms', 'RIS No: 18-RIS-01-018, Division: Finance Division, Office: Head Office', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:57:20'),
(1356, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 10:57:20'),
(1357, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4343 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:37:27'),
(1358, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:37:35'),
(1359, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:37:35'),
(1360, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:40:02'),
(1361, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:40:03'),
(1362, 5, 'Viewed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:40:17'),
(1363, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:40:24'),
(1364, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:43:30'),
(1365, 5, 'Viewed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:45:21'),
(1366, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:45:30'),
(1367, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:47:09'),
(1368, 5, 'Viewed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:47:20'),
(1369, 5, 'Viewed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:51:47'),
(1370, 5, 'Viewed RIS Form', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:54:48'),
(1371, 5, 'Viewed RIS Form', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:57:39'),
(1372, 5, 'Viewed RIS Form', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:58:30'),
(1373, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 11:59:00'),
(1374, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:04:16'),
(1375, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:07:36'),
(1376, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:09:08'),
(1377, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:14:36'),
(1378, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:15:51'),
(1379, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:16:31'),
(1380, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:17:34'),
(1381, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:17:36'),
(1382, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:17:41'),
(1383, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:21:03'),
(1384, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:21:05'),
(1385, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:21:07'),
(1386, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:21:08'),
(1387, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:22:02'),
(1388, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:22:07'),
(1389, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:22:44'),
(1390, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:22:52'),
(1391, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:23:51'),
(1392, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:23:57'),
(1393, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:23:58'),
(1394, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:27:40'),
(1395, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:27:44'),
(1396, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:30:05'),
(1397, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3910 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:42:45'),
(1398, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:42:52'),
(1399, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:42:53'),
(1400, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:43:38'),
(1401, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:44:50'),
(1402, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:44:51'),
(1403, 5, 'access', 'consumables', 'Admin accessed consumables management', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:47:23'),
(1404, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:48:15'),
(1405, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:48:19'),
(1406, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:51:20'),
(1407, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:51:22'),
(1408, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:51:52'),
(1409, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:52:08'),
(1410, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:52:57'),
(1411, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:53:10'),
(1412, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:53:45'),
(1413, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:53:52'),
(1414, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:55:42'),
(1415, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:56:51'),
(1416, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:57:45'),
(1417, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:57:48'),
(1418, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:01'),
(1419, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:05'),
(1420, 5, 'consumable_reorder_updated', 'consumable_management', 'Updated reorder level for consumable: UTP Cable (Cat6, 305m/Box) to 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:05'),
(1421, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:13'),
(1422, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:16'),
(1423, 5, 'consumable_reorder_updated', 'consumable_management', 'Updated reorder level for consumable: UTP Cable (Cat6, 305m/Box) to 0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:16'),
(1424, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:35'),
(1425, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:41'),
(1426, 5, 'consumable_reorder_updated', 'consumable_management', 'Updated reorder level for consumable: UTP Connectors (RJ45 Cat6, 100pcs) to 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:58:41'),
(1427, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:59:07'),
(1428, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 12:59:50'),
(1429, 5, 'logout', 'authentication', 'User logged out: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 13:01:08'),
(1430, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 11:57:59'),
(1431, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 11:57:59'),
(1432, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 11:58:06'),
(1433, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 11:58:10'),
(1434, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:00:17'),
(1435, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:04:38'),
(1436, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:04:42'),
(1437, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:04:44'),
(1438, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:04:47'),
(1439, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:04:48'),
(1440, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:08:00'),
(1441, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:08:11'),
(1442, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:08:19'),
(1443, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:08:22'),
(1444, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:08:29'),
(1445, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:09:48'),
(1446, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:10:05'),
(1447, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:03'),
(1448, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:05'),
(1449, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:11'),
(1450, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:14'),
(1451, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:18'),
(1452, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:21'),
(1453, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:27'),
(1454, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:30'),
(1455, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:33'),
(1456, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:36'),
(1457, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:38'),
(1458, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:11:39'),
(1459, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:12:45'),
(1460, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:12:46'),
(1461, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:12:49'),
(1462, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:12:52'),
(1463, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:12:56'),
(1464, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:13:01'),
(1465, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:13:07'),
(1466, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:13:12'),
(1467, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:13:16'),
(1468, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:23:32'),
(1469, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:29:24'),
(1470, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 12:37:44'),
(1471, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 5049 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:08'),
(1472, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:20'),
(1473, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:20'),
(1474, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:24'),
(1475, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:26:45'),
(1476, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:27:28'),
(1477, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:28:00'),
(1478, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:28:27'),
(1479, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:29:11'),
(1480, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:30:13'),
(1481, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:34:55'),
(1482, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 7141 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:21:21'),
(1483, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:21:39'),
(1484, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:21:39'),
(1485, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:22:02'),
(1486, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:24:02'),
(1487, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:26:07'),
(1488, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:28:08'),
(1489, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:29:53'),
(1490, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:13'),
(1491, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:21'),
(1492, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:21'),
(1493, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:22'),
(1494, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:22'),
(1495, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:33:22'),
(1496, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:38:30'),
(1497, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:38:30'),
(1498, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:38:30'),
(1499, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:39:59'),
(1500, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:43:59'),
(1501, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:45:01'),
(1502, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:45:34'),
(1503, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:48:22'),
(1504, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:49:30'),
(1505, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:51:30'),
(1506, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:53:06'),
(1507, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:54:06'),
(1508, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:54:09'),
(1509, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:54:09'),
(1510, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 15:55:34'),
(1511, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 14:45:15'),
(1512, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 14:45:16'),
(1513, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 14:45:20'),
(1514, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 14:53:39'),
(1515, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 22:40:34'),
(1516, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 22:40:34'),
(1517, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 22:40:41'),
(1518, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 22:41:09'),
(1519, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 11:58:54'),
(1520, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 11:58:54'),
(1521, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 11:59:01'),
(1522, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:05:10'),
(1523, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:06:10'),
(1524, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:13:08'),
(1525, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:13:35'),
(1526, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:14:58'),
(1527, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:16:27'),
(1528, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:17:10'),
(1529, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:18:13'),
(1530, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:25:52'),
(1531, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:29:09'),
(1532, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:32:56'),
(1533, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 12:45:44'),
(1534, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 6633 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:49:27'),
(1535, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:49:44'),
(1536, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:49:44'),
(1537, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:50:16'),
(1538, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:53:23'),
(1539, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 13:56:00'),
(1540, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:00:17'),
(1541, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:00:18'),
(1542, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:06:32'),
(1543, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:07:32'),
(1544, 5, 'Error creating IIRUP Form: Column count doesn\'t match value count at row 1', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:07:55'),
(1545, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 14:07:55'),
(1546, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 12:10:04'),
(1547, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 12:10:05'),
(1548, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 12:11:28'),
(1549, 5, 'Error creating IIRUP Form: Column count doesn\'t match value count at row 1', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 12:11:53'),
(1550, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 12:11:53'),
(1551, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 6473 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 13:57:57'),
(1552, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 13:58:11'),
(1553, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 13:58:12'),
(1554, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 13:58:17'),
(1555, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 14:15:28'),
(1556, 5, 'Error creating IIRUP Form: Column count doesn\'t match value count at row 1', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 14:16:06'),
(1557, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-16 14:16:06'),
(1558, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:49:39'),
(1559, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:49:39'),
(1560, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:49:44'),
(1561, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:53:28'),
(1562, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:53:53'),
(1563, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:53:53'),
(1564, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:58:02'),
(1565, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:58:24'),
(1566, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 04:58:24'),
(1567, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:01:29');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1568, 5, 'Created IIRUP Form', 'forms', 'form_id: 11, form_number: IIRUP-2026-6368', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:01:51'),
(1569, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:01:51'),
(1570, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:02:15'),
(1571, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:09:30'),
(1572, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:26:41'),
(1573, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:28:10'),
(1574, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:28:10'),
(1575, 1, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', 'Unknown', 'Unknown', '2026-01-17 05:30:29'),
(1576, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:32:12'),
(1577, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:32:24'),
(1578, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:33:28'),
(1579, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 05:33:28'),
(1580, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4705 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:04'),
(1581, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:11'),
(1582, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:12'),
(1583, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:15'),
(1584, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:39'),
(1585, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:08:39'),
(1586, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:11:06'),
(1587, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:11:09'),
(1588, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:14:46'),
(1589, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:14:46'),
(1590, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:17:36'),
(1591, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:17:37'),
(1592, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:17:38'),
(1593, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:19:44'),
(1594, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:19:44'),
(1595, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:20:06'),
(1596, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:20:07'),
(1597, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:20:08'),
(1598, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:20:08'),
(1599, 5, 'Created IIRUP Form', 'forms', 'form_id: 6, form_number: IIRUP-2026-7852', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:22:00'),
(1600, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:22:00'),
(1601, 5, 'Created IIRUP Form', 'forms', 'form_id: 7, form_number: IIRUP-2026-1778', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:27:46'),
(1602, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:27:46'),
(1603, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:34:22'),
(1604, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:36:31'),
(1605, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:37:28'),
(1606, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:50:20'),
(1607, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:50:21'),
(1608, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 06:55:18'),
(1609, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4580 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:24:31'),
(1610, 5, 'login_failed', 'authentication', 'Invalid password for user: Walton loneza (waltonloneza@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:24:45'),
(1611, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:24:56'),
(1612, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:24:56'),
(1613, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:25:05'),
(1614, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 07:25:08'),
(1615, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 08:36:30'),
(1616, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-17 08:36:30'),
(1617, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:33:49'),
(1618, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:33:49'),
(1619, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:33:54'),
(1620, 5, 'Created IIRUP Form', 'forms', 'form_id: 8, form_number: IIRUP-2026-9694', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:35:32'),
(1621, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:35:32'),
(1622, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:40:29'),
(1623, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:43:00'),
(1624, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:45:22'),
(1625, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:54:41'),
(1626, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:54:42'),
(1627, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:59:14'),
(1628, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 05:59:14'),
(1629, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:01:29'),
(1630, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:01:30'),
(1631, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:05:10'),
(1632, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:05:11'),
(1633, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:06:51'),
(1634, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:06:54'),
(1635, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:06:55'),
(1636, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:08:41'),
(1637, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 06:08:41'),
(1638, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 13:57:30'),
(1639, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 13:57:31'),
(1640, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 13:57:35'),
(1641, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 13:58:46'),
(1642, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 13:58:46'),
(1643, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:02:34'),
(1644, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:02:43'),
(1645, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:02:44'),
(1646, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:04:09'),
(1647, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:04:12'),
(1648, 5, 'Error creating IIRUP Form: Unknown column \'updated_at\' in \'field list\'', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:04:21'),
(1649, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:04:21'),
(1650, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:07:23'),
(1651, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:07:26'),
(1652, 5, 'Updated asset status to unserviceable', 'assets', 'asset_ids: 43, form_id: 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:07:33'),
(1653, 5, 'Created IIRUP Form', 'forms', 'form_id: 13, form_number: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:07:33'),
(1654, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:07:33'),
(1655, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:08:41'),
(1656, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:08:55'),
(1657, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:12:12'),
(1658, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:12:27'),
(1659, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:12:29'),
(1660, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-18 14:14:31'),
(1661, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:29:41'),
(1662, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:29:42'),
(1663, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:32:33'),
(1664, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:32:37'),
(1665, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:32:41'),
(1666, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:38:16'),
(1667, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:40:08'),
(1668, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:40:10'),
(1669, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:40:11'),
(1670, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:41:48'),
(1671, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:42:59'),
(1672, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:12'),
(1673, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:26'),
(1674, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:39'),
(1675, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:58'),
(1676, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:59'),
(1677, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:43:59'),
(1678, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:44:49'),
(1679, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:45:45'),
(1680, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:46:03'),
(1681, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:46:39'),
(1682, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:47:05'),
(1683, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:48:22'),
(1684, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:50:19'),
(1685, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:51:06'),
(1686, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:52:14'),
(1687, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:52:56'),
(1688, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:53:26'),
(1689, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:54:23'),
(1690, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:54:53'),
(1691, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 02:55:19'),
(1692, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:01:42'),
(1693, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:02:02'),
(1694, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:04:02'),
(1695, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:05:42'),
(1696, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:19:36'),
(1697, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:20:03'),
(1698, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:21:22'),
(1699, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:24:30'),
(1700, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:25:30'),
(1701, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:26:21'),
(1702, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:27:10'),
(1703, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:27:11'),
(1704, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:29:01'),
(1705, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3616 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:29:57'),
(1706, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:05'),
(1707, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:06'),
(1708, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:10'),
(1709, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:13'),
(1710, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:15'),
(1711, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:30:19'),
(1712, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:32:25'),
(1713, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:33:30'),
(1714, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:33:34'),
(1715, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:33:35'),
(1716, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:33:35'),
(1717, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:33:51'),
(1718, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:35:35'),
(1719, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:37:04'),
(1720, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:37:54'),
(1721, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:38:41'),
(1722, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:39:23'),
(1723, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:40:06'),
(1724, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:41:07'),
(1725, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:41:39'),
(1726, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:41:54'),
(1727, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:42:57'),
(1728, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:44:11'),
(1729, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:44:52'),
(1730, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:45:05'),
(1731, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:46:51'),
(1732, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:47:10'),
(1733, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:47:26'),
(1734, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:49:35'),
(1735, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:49:37'),
(1736, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:51:02'),
(1737, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:51:50'),
(1738, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:52:43'),
(1739, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:54:34'),
(1740, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:55:31'),
(1741, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:56:53'),
(1742, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 03:58:25'),
(1743, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 5215 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 04:57:00'),
(1744, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:40:44'),
(1745, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:40:44'),
(1746, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:40:47'),
(1747, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:41:17'),
(1748, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:41:18'),
(1749, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:43:19'),
(1750, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:43:21'),
(1751, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:47:49'),
(1752, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 05:48:26'),
(1753, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 12:20:32'),
(1754, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 12:20:32'),
(1755, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 12:20:42'),
(1756, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 12:20:44'),
(1757, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 12:20:45'),
(1758, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:48:57'),
(1759, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:48:58'),
(1760, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:49:03'),
(1761, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:49:22'),
(1762, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:49:24'),
(1763, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:51:58'),
(1764, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:54:55'),
(1765, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 13:59:34'),
(1766, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:00:57'),
(1767, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:01:47'),
(1768, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:02:12'),
(1769, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:05:11'),
(1770, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:09:04'),
(1771, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:11:19'),
(1772, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:12:05'),
(1773, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:12:23'),
(1774, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:14:15'),
(1775, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:17:29'),
(1776, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:18:04'),
(1777, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:18:20'),
(1778, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:18:34'),
(1779, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:20:21'),
(1780, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:20:58'),
(1781, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:21:37');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(1782, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:21:44'),
(1783, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:22:01'),
(1784, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:22:27'),
(1785, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:22:34'),
(1786, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:23:03'),
(1787, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:46:22'),
(1788, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:46:23'),
(1789, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:46:27'),
(1790, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:46:39'),
(1791, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:46:41'),
(1792, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:48:10'),
(1793, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:50:00'),
(1794, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:50:37'),
(1795, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:50:45'),
(1796, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:51:51'),
(1797, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:51:59'),
(1798, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:52:22'),
(1799, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:53:57'),
(1800, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:55:16'),
(1801, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:57:21'),
(1802, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:00:05'),
(1803, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:00:25'),
(1804, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:02:15'),
(1805, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:02:46'),
(1806, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:05:32'),
(1807, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:06:51'),
(1808, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:07:09'),
(1809, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:07:22'),
(1810, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:07:39'),
(1811, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:08:31'),
(1812, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:08:58'),
(1813, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:09:24'),
(1814, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:09:46'),
(1815, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:10:04'),
(1816, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:10:55'),
(1817, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:11:59'),
(1818, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:15:11'),
(1819, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:15:48'),
(1820, 5, 'Printed IIRUP Form', 'forms', 'IIRUP ID: 13, Form No: IIRUP-2026-3353', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:17:56'),
(1821, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:18:41'),
(1822, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:26:59'),
(1823, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:27:42'),
(1824, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:39:49'),
(1825, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:40:15'),
(1826, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:42:07'),
(1827, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:46:20'),
(1828, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3639 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:01'),
(1829, 5, 'login_failed', 'authentication', 'Invalid password for user: Walton loneza (waltonloneza@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:09'),
(1830, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:16'),
(1831, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:17'),
(1832, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:20'),
(1833, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:48:13'),
(1834, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:48:56'),
(1835, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:49:16'),
(1836, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:49:52'),
(1837, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:50:10'),
(1838, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:56:47'),
(1839, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:59:26'),
(1840, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:03:58'),
(1841, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:05:08'),
(1842, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:06:14'),
(1843, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:07:21'),
(1844, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:11:13'),
(1845, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:14:14'),
(1846, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:17:23'),
(1847, 17, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:20:14'),
(1848, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:21:00'),
(1849, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:21:29'),
(1850, 17, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:21:36'),
(1851, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:25:37'),
(1852, NULL, 'login_failed', 'authentication', 'Invalid email format: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:02:59'),
(1853, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:03:09'),
(1854, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:03:10'),
(1855, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:03:14'),
(1856, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:03:23'),
(1857, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:26:25'),
(1858, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:26:26'),
(1859, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:33:07'),
(1860, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4363 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:15:52'),
(1861, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:16:02'),
(1862, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:16:03'),
(1863, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:16:24'),
(1864, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 07:54:18'),
(1865, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:16:24'),
(1866, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:16:24'),
(1867, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:16:48'),
(1868, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:16:51'),
(1869, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:28:37'),
(1870, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:33:35'),
(1871, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:37:31'),
(1872, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:38:48'),
(1873, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:39:13'),
(1874, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:40:15'),
(1875, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:40:36'),
(1876, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:41:00'),
(1877, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:41:57'),
(1878, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:42:31'),
(1879, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:43:11'),
(1880, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:43:25'),
(1881, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:43:52'),
(1882, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:44:45'),
(1883, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:45:16'),
(1884, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:46:17'),
(1885, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:47:01'),
(1886, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:47:17'),
(1887, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:52:06'),
(1888, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:53:29'),
(1889, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:55:33'),
(1890, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:56:20'),
(1891, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:56:37'),
(1892, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:56:59'),
(1893, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:57:26'),
(1894, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:57:38'),
(1895, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:57:58'),
(1896, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:58:30'),
(1897, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:58:58'),
(1898, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:59:36'),
(1899, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 13:59:57'),
(1900, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:00:18'),
(1901, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:00:27'),
(1902, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:00:35'),
(1903, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:00:47'),
(1904, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:00'),
(1905, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:07'),
(1906, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:16'),
(1907, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:32'),
(1908, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:42'),
(1909, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:01:52'),
(1910, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:03:22'),
(1911, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 14:03:37'),
(1912, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:23:09'),
(1913, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:23:10'),
(1914, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:23:15'),
(1915, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:31:40'),
(1916, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:32:33'),
(1917, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:44:45'),
(1918, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:38'),
(1919, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:39'),
(1920, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:39'),
(1921, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:39'),
(1922, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:39'),
(1923, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:45:40'),
(1924, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:52:09'),
(1925, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:52:14'),
(1926, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:52:16'),
(1927, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:53:25'),
(1928, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:53:26'),
(1929, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:53:29'),
(1930, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:54:42'),
(1931, 17, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:55:06'),
(1932, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:55:52'),
(1933, 17, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:56:02'),
(1934, 17, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 12:56:10'),
(1935, 17, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:03:53'),
(1936, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4452 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:21'),
(1937, 2, 'login_failed', 'authentication', 'Invalid password for user: Walton loneza (wjll2022-2920-98466@bicol-u.edu.ph)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:32'),
(1938, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:42'),
(1939, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:43'),
(1940, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:37:50'),
(1941, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:39:36'),
(1942, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 13:39:52'),
(1943, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:01:50'),
(1944, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:01:52'),
(1945, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:30:34'),
(1946, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:30:34'),
(1947, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3638 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:38:20'),
(1948, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:38:32'),
(1949, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:38:32'),
(1950, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:38:35'),
(1951, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:39:29'),
(1952, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:40:29'),
(1953, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:41:54'),
(1954, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:43:20'),
(1955, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:46:37'),
(1956, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:48:01'),
(1957, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-21 14:56:07'),
(1958, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:19:05'),
(1959, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:19:05'),
(1960, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:20:43'),
(1961, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:29:19'),
(1962, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:29:22'),
(1963, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:33:08'),
(1964, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:33:14'),
(1965, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:34:53'),
(1966, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:33'),
(1967, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:36'),
(1968, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:37'),
(1969, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:39'),
(1970, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:40'),
(1971, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:42'),
(1972, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:45'),
(1973, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:36:48'),
(1974, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:39:42'),
(1975, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:39:44'),
(1976, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:40:56'),
(1977, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:46:02'),
(1978, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:46:08'),
(1979, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:46:12'),
(1980, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:46:20'),
(1981, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:47:37'),
(1982, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:47:49'),
(1983, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:47:52'),
(1984, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:48:02'),
(1985, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:48:04'),
(1986, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:52:39'),
(1987, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:59:06'),
(1988, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:59:12'),
(1989, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:59:25'),
(1990, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:59:40'),
(1991, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 11:59:44'),
(1992, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:03:20'),
(1993, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:06:09'),
(1994, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:07:57'),
(1995, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:01'),
(1996, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:04'),
(1997, 5, 'update', 'employees', 'Updated employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:04'),
(1998, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:04'),
(1999, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:08'),
(2000, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:11'),
(2001, 5, 'update', 'employees', 'Updated employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:11'),
(2002, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:08:11'),
(2003, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:12:53'),
(2004, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:16:58'),
(2005, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:01');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(2006, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:13'),
(2007, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:16'),
(2008, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:34'),
(2009, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:39'),
(2010, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:42'),
(2011, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:17:57'),
(2012, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3660 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:20:05'),
(2013, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:20:13'),
(2014, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:20:13'),
(2015, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:20:16'),
(2016, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:21:54'),
(2017, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:28:46'),
(2018, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:28:52'),
(2019, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 12:29:25'),
(2020, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:05:01'),
(2021, 5, 'create', 'employees', 'Added new employee: Walton loneza (EMP20260001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:05:01'),
(2022, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:05:01'),
(2023, 5, 'view', 'employees', 'Viewed employee: Walton loneza', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:05:16'),
(2024, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:05:27'),
(2025, 5, 'view', 'employees', 'Viewed employee: Walton loneza', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:06:06'),
(2026, 5, 'view', 'employees', 'Viewed employee: Walton loneza', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:06'),
(2027, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:13'),
(2028, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:26'),
(2029, 5, 'create', 'employees', 'Added new employee: Walton loneza (EMP20260001)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:26'),
(2030, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:26'),
(2031, 5, 'view', 'employees', 'Viewed employee: Walton loneza', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:30'),
(2032, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:09:50'),
(2033, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3801 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:23:34'),
(2034, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:23:52'),
(2035, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:23:52'),
(2036, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:25:15'),
(2037, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:25:22'),
(2038, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:25:32'),
(2039, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:03'),
(2040, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:06'),
(2041, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:07'),
(2042, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:07'),
(2043, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:08'),
(2044, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:31:44'),
(2045, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:40:54'),
(2046, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:43:49'),
(2047, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:44:19'),
(2048, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:44:25'),
(2049, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 13:44:58'),
(2050, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 14:01:39'),
(2051, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 14:01:39'),
(2052, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-22 14:01:48'),
(2053, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 01:30:15'),
(2054, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 01:30:15'),
(2055, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 01:30:26'),
(2056, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 10707 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 04:28:42'),
(2057, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 07:59:20'),
(2058, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 07:59:21'),
(2059, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 07:59:30'),
(2060, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:04:33'),
(2061, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:04:36'),
(2062, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:04:59'),
(2063, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0007-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:06:21'),
(2064, 5, 'Failed to create PAR form', 'forms', 'Error: Unknown column \'property_number\' in \'where clause\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:06:21'),
(2065, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:06:21'),
(2066, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:24:16'),
(2067, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:24:16'),
(2068, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:24:17'),
(2069, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0008-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:25:02'),
(2070, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0008-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:25:02'),
(2071, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:25:02'),
(2072, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:38:12'),
(2073, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0009-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:38:56'),
(2074, 5, 'Failed to create PAR form', 'forms', 'Error: Unknown column \'property_no\' in \'field list\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:38:56'),
(2075, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:38:56'),
(2076, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:39:54'),
(2077, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:39:54'),
(2078, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:39:55'),
(2079, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0010-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:40:32'),
(2080, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0010-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:40:32'),
(2081, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:40:32'),
(2082, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:42:50'),
(2083, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0011-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:43:34'),
(2084, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0011-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:43:34'),
(2085, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:43:34'),
(2086, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:46:09'),
(2087, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0012-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:46:41'),
(2088, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0012-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:46:41'),
(2089, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:46:41'),
(2090, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:49:37'),
(2091, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:49:37'),
(2092, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:49:37'),
(2093, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0013-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:50:11'),
(2094, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0013-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:50:11'),
(2095, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:50:11'),
(2096, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:52:39'),
(2097, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:52:39'),
(2098, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:52:40'),
(2099, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:52:40'),
(2100, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0014-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:53:00'),
(2101, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0014-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:53:00'),
(2102, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:53:00'),
(2103, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:54:12'),
(2104, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0015-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:54:36'),
(2105, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0016-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:55:29'),
(2106, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0017-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:56:21'),
(2107, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0018-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:56:54'),
(2108, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0018-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:56:55'),
(2109, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 08:56:55'),
(2110, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 5249 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:26:49'),
(2111, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:27:17'),
(2112, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:27:17'),
(2113, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:27:33'),
(2114, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:28:51'),
(2115, 5, 'Accessed PAR Entries', 'forms', 'par_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:30:47'),
(2116, 5, 'Viewed PAR Form', 'forms', 'PAR ID: 16, PAR No: PAR-0018-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:30:49'),
(2117, 5, 'Printed PAR Form', 'forms', 'PAR ID: 16, PAR No: PAR-0018-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:30:54'),
(2118, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:31:15'),
(2119, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:31:23'),
(2120, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:31:43'),
(2121, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:34:12'),
(2122, 5, 'Failed to create PAR form', 'forms', 'Error: Item 1: Amount must be above ₱50,000. Current amount: ₱34,000.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:34:52'),
(2123, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:34:52'),
(2124, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0019-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:35:20'),
(2125, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0019-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:35:20'),
(2126, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:35:20'),
(2127, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 09:35:44'),
(2128, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 10:04:24'),
(2129, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 10:23:56'),
(2130, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 8126 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 11:42:43'),
(2131, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 11:42:55'),
(2132, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 11:42:55'),
(2133, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 11:43:06'),
(2134, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 12:03:35'),
(2135, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 12:04:39'),
(2136, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 12:07:11'),
(2137, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-23 12:07:56'),
(2138, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:52:52'),
(2139, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:52:53'),
(2140, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:53:19'),
(2141, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:55:01'),
(2142, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:55:10'),
(2143, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:55:15'),
(2144, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 11:56:41'),
(2145, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:36'),
(2146, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0013, Entity: Head Office', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:36'),
(2147, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:36'),
(2148, 5, 'Accessed ICS Entries', 'forms', 'ics_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:40'),
(2149, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:45'),
(2150, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:00:50'),
(2151, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:25:35'),
(2152, 5, 'Failed to create PAR form', 'forms', 'Error: Item 1: Amount must be above ₱50,000. Current amount: ₱33,995.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:26:42'),
(2153, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:26:42'),
(2154, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0020-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:28:27'),
(2155, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0020-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:28:27'),
(2156, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:28:27'),
(2157, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:28:31'),
(2158, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:28:45'),
(2159, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:42:58'),
(2160, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:44:27'),
(2161, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0014, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:44:27'),
(2162, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:44:27'),
(2163, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:44:32'),
(2164, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:50:58'),
(2165, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0021-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:51:40'),
(2166, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0021-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:51:40'),
(2167, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:51:40'),
(2168, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:51:54'),
(2169, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4020 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 12:59:52'),
(2170, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:00:02'),
(2171, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:00:02'),
(2172, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:00:05'),
(2173, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:01:20'),
(2174, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0015, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:01:20'),
(2175, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:01:20'),
(2176, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:01:24'),
(2177, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:05:06'),
(2178, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:05:36'),
(2179, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0016, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:05:36'),
(2180, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:05:36'),
(2181, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:05:42'),
(2182, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:09:10'),
(2183, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:10:47'),
(2184, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0022-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:11:47'),
(2185, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0022-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:11:47'),
(2186, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:11:47'),
(2187, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-25 13:11:51'),
(2188, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:28:46'),
(2189, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:28:46'),
(2190, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:28:52'),
(2191, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:40:24'),
(2192, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:41:16'),
(2193, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:43:12'),
(2194, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:54:41'),
(2195, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:56:03'),
(2196, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 11:59:03'),
(2197, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:01:51'),
(2198, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:04:16'),
(2199, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:05:26'),
(2200, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:05:30'),
(2201, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:05:30'),
(2202, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:06:31'),
(2203, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:08:09'),
(2204, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:10:59'),
(2205, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:12:52'),
(2206, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:14:25'),
(2207, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:15:25'),
(2208, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:16:58'),
(2209, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:21:40'),
(2210, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:21:44'),
(2211, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:21:44'),
(2212, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 5278 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:56:44'),
(2213, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:56:51'),
(2214, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:56:51'),
(2215, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:56:56'),
(2216, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 12:58:16'),
(2217, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 13:00:56'),
(2218, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 13:02:40'),
(2219, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 13:04:13'),
(2220, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-26 13:10:34'),
(2221, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:14:10'),
(2222, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:14:11'),
(2223, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:14:14'),
(2224, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:34:30'),
(2225, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:36:05'),
(2226, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:36:51'),
(2227, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:38:26');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(2228, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:43:59'),
(2229, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:44:00'),
(2230, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:44:00'),
(2231, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:44:03'),
(2232, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:45:35'),
(2233, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:46:36'),
(2234, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:46:37'),
(2235, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:48:31'),
(2236, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:48:58'),
(2237, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: , , , , , , INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:49:06'),
(2238, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:51:28'),
(2239, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:51:31'),
(2240, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:51:37'),
(2241, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0023-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:52:23'),
(2242, 5, 'Failed to create PAR form', 'forms', 'Error: Unknown column \'unit\' in \'field list\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:52:23'),
(2243, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:52:23'),
(2244, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:53:07'),
(2245, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0024-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:53:39'),
(2246, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0024-2026, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:53:39'),
(2247, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:53:39'),
(2248, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:02'),
(2249, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:05'),
(2250, 5, 'print', 'inventory_tag', 'Printed inventory tag: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:19'),
(2251, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:36'),
(2252, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:54'),
(2253, 5, 'print', 'inventory_tag', 'Printed inventory tag: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:54:57'),
(2254, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:55:17'),
(2255, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:55:19'),
(2256, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:55:47'),
(2257, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:56:36'),
(2258, 5, 'print', 'inventory_tag', 'Printed inventory tag: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:56:39'),
(2259, 5, 'print', 'inventory_tag', 'Printed inventory tag: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:57:24'),
(2260, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:59:26'),
(2261, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 01:59:34'),
(2262, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:00:25'),
(2263, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:03:22'),
(2264, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:03:28'),
(2265, 5, 'print', 'inventory_tag', 'Printed inventory tag: ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:03:30'),
(2266, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:03:30'),
(2267, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:04:03'),
(2268, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:05:24'),
(2269, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:07:05'),
(2270, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:07:55'),
(2271, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:08:09'),
(2272, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:08:23'),
(2273, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:11:05'),
(2274, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:13:00'),
(2275, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3641 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:14:51'),
(2276, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:15:04'),
(2277, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:15:04'),
(2278, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:15:15'),
(2279, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:17:09'),
(2280, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:17:33'),
(2281, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:19:07'),
(2282, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:21:32'),
(2283, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:23:30'),
(2284, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:31:04'),
(2285, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:31:24'),
(2286, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:34:53'),
(2287, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:39:18'),
(2288, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:39:35'),
(2289, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:39:43'),
(2290, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:40:26'),
(2291, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:41:54'),
(2292, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:42:41'),
(2293, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:43:44'),
(2294, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:44:51'),
(2295, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:50:01'),
(2296, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:51:01'),
(2297, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:51:59'),
(2298, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:52:19'),
(2299, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 02:54:32'),
(2300, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:21:26'),
(2301, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:21:40'),
(2302, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:26:33'),
(2303, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4411 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:28:35'),
(2304, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:29:04'),
(2305, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:29:04'),
(2306, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:29:09'),
(2307, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:29:16'),
(2308, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:30:30'),
(2309, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:30:32'),
(2310, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:34:00'),
(2311, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:36:23'),
(2312, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:36:27'),
(2313, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:39:59'),
(2314, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:40:13'),
(2315, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:40:23'),
(2316, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:41:56'),
(2317, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:05'),
(2318, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:08'),
(2319, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:11'),
(2320, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:12'),
(2321, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:12'),
(2322, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:12'),
(2323, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:20'),
(2324, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:23'),
(2325, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:24'),
(2326, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:37'),
(2327, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:44'),
(2328, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:47'),
(2329, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:48'),
(2330, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 03:43:49'),
(2331, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4877 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:50:21'),
(2332, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:50:27'),
(2333, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:50:28'),
(2334, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:50:31'),
(2335, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 04:54:54'),
(2336, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:08:25'),
(2337, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:09:28'),
(2338, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:11:48'),
(2339, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:16:12'),
(2340, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:16:23'),
(2341, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:26:05'),
(2342, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:38:59'),
(2343, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:44:11'),
(2344, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:44:59'),
(2345, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:45:01'),
(2346, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:45:09'),
(2347, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 05:47:45'),
(2348, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:40:18'),
(2349, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:40:19'),
(2350, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:40:25'),
(2351, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:42:50'),
(2352, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:47:50'),
(2353, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:50:34'),
(2354, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:51:32'),
(2355, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:54:09'),
(2356, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:08'),
(2357, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00004, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:08'),
(2358, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:08'),
(2359, 5, 'Accessed ITR Entries', 'forms', 'itr_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:12'),
(2360, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:32'),
(2361, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:55:56'),
(2362, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:56:00'),
(2363, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:56:38'),
(2364, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:57:08'),
(2365, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:59:06'),
(2366, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:59:09'),
(2367, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:59:34'),
(2368, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 08:59:37'),
(2369, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:16'),
(2370, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:21'),
(2371, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:55'),
(2372, 5, 'Asset item transfer - no items found', 'assets', 'Item: 62, From Employee ID: 1, To Employee ID: 6, ITR: ITR-2026-00005', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:55'),
(2373, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00005, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:55'),
(2374, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:01:55'),
(2375, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:04:31'),
(2376, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:04:56'),
(2377, 5, 'Asset item transfer - no items found', 'assets', 'Item: 62, From Employee ID: 1, To Employee ID: 6, ITR: ITR-2026-00006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:04:56'),
(2378, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00006, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:04:56'),
(2379, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:04:56'),
(2380, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:05:22'),
(2381, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:06:25'),
(2382, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:27:42'),
(2383, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:28:59'),
(2384, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:29:37'),
(2385, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Description=\'62\', From Employee ID=1, To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:29:37'),
(2386, 5, 'ITR Asset Transfer - No matching items found', 'assets', 'No asset items found with Description=\'62\' and Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:29:37'),
(2387, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00007, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:29:37'),
(2388, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:29:37'),
(2389, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:30:21'),
(2390, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Description=\'62\', From Employee ID=1, To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:30:21'),
(2391, 5, 'ITR Asset Transfer - No matching items found', 'assets', 'No asset items found with Description=\'62\' and Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:30:21'),
(2392, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00008, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:30:21'),
(2393, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:30:21'),
(2394, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:32:46'),
(2395, 5, 'ITR Form Data Received', 'forms', 'From Office: 1, To Office: 6, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2396, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2397, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Description=\'62\', From Employee ID=1, To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2398, 5, 'ITR Asset Check Results', 'assets', 'Found 0 matching items for Description=\'62\' and Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2399, 5, 'ITR Asset Transfer - No matching items found', 'assets', 'No asset items found with Description=\'62\' and Employee ID=1. Found 0 items with this description total: []', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2400, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00009, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2401, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:33:09'),
(2402, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:35:45'),
(2403, 5, 'ITR Form Data Received', 'forms', 'From Office: 1, To Office: 6, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2404, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2405, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Description=\'62\', To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2406, 5, 'ITR Asset Check SQL', 'assets', 'Check SQL: SELECT id, description, employee_id FROM asset_items WHERE description = \'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2407, 5, 'ITR Asset Check Results', 'assets', 'Found 0 items with Description=\'62\': []', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2408, 5, 'ITR Asset Transfer - No items found', 'assets', 'No asset items found with Description=\'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2409, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00010, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2410, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:36:08'),
(2411, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:37:14'),
(2412, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 3693 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:41:50'),
(2413, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:41:58'),
(2414, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:41:59'),
(2415, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:07'),
(2416, 5, 'ITR Form Data Received', 'forms', 'From Office: 1, To Office: 6, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2417, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2418, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Asset ID=62, To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2419, 5, 'ITR Asset Check SQL', 'assets', 'Check SQL: SELECT id, description, employee_id FROM asset_items WHERE id = \'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2420, 5, 'ITR Asset Check Results', 'assets', 'Found asset: ID=62, Description=\'Acer Predator Helios Neo 16 (PHN16-72-92K1)\', Current Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2421, 5, 'ITR Asset Update SQL', 'assets', 'Update SQL: UPDATE asset_items SET employee_id = \'6\' WHERE id = \'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2422, 5, 'Asset item transferred', 'assets', 'Asset ID: 62, Description: Acer Predator Helios Neo 16 (PHN16-72-92K1), From Employee ID: 1, To Employee ID: 6, ITR: ITR-2026-00011, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2423, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00011, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2424, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:42:34'),
(2425, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:00'),
(2426, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:03'),
(2427, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:17'),
(2428, 5, 'view', 'employees', 'Viewed employee: Walton loneza', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:19'),
(2429, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:32'),
(2430, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:43:55'),
(2431, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:44:37'),
(2432, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:45:50'),
(2433, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0017, Entity: Head Office', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:45:50'),
(2434, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:45:50'),
(2435, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:45:53'),
(2436, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0010', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:46:42'),
(2437, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:53:20'),
(2438, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:57:03'),
(2439, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0019', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:58:23'),
(2440, 5, 'Asset item updated successfully', 'assets', 'Item ID: 65, End User: Roberto Cruz, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 09:58:23');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(2441, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:02:23'),
(2442, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:06:00'),
(2443, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0018, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:06:00'),
(2444, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:06:00'),
(2445, 5, 'ICS counter incremented', 'forms', 'Generated ICS number: ICS-01-0019', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:09:51'),
(2446, 5, 'ICS Asset Item Insert Debug', 'assets', 'Item: HP Laptop 15-fd1168TU, Unit: \'Units\', Unit Cost: 49990, Office ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:09:51'),
(2447, 5, 'ICS Asset Item Insert Debug', 'assets', 'Item: HP Laptop 15-fd1168TU, Unit: \'Units\', Unit Cost: 49990, Office ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:09:51'),
(2448, 5, 'Created ICS form', 'forms', 'ICS No: ICS-01-0019, Entity: East District', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:09:51'),
(2449, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:09:51'),
(2450, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:10:20'),
(2451, 5, 'Tag Form Data Received', 'forms', 'Item ID: 69, End User: \'Roberto Cruz\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:11:00'),
(2452, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0021', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:11:00'),
(2453, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = ?, \r\n                   inventory_tag = ?, \r\n                   date_counted = ?,\r\n                   image = ?,\r\n                   employee_id = ?, \r\n                   category_id = ?,\r\n                   end_user = ?,\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = ?', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:11:00'),
(2454, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0021\', inventory_tag=\'INV-2026-00020\', date_counted=\'2026-01-27\', image=\'\', employee_id=6, category_id=2, end_user=\'Roberto Cruz\', item_id=69', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:11:00'),
(2455, 5, 'Asset item updated successfully', 'assets', 'Item ID: 69, End User: Roberto Cruz, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:11:00'),
(2456, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'John Legend\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:19:18'),
(2457, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0023', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:19:18'),
(2460, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'Jack Robertson\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:20:43'),
(2461, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:20:43'),
(2464, 5, 'logout', 'authentication', 'User logged out: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:21:26'),
(2465, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:21:39'),
(2466, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:21:39'),
(2467, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:23:25'),
(2468, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:23:32'),
(2469, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:23:33'),
(2470, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:23:39'),
(2471, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:23:42'),
(2472, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'Angela Rizal\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:24:13'),
(2473, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0029', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:24:13'),
(2476, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'John Legend\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:27:17'),
(2477, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0032', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:27:17'),
(2480, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'John Kenneth Litana\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:28:38'),
(2481, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0035', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:28:38'),
(2484, 5, 'Tag Form Data Received', 'forms', 'Item ID: 70, End User: \'Roberto Cruz\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:30:20'),
(2485, 5, 'Property number counter incremented', 'forms', 'Generated property number: PAR-0038', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:30:20'),
(2486, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0038\', \r\n                   inventory_tag = \'INV-2026-00021\', \r\n                   date_counted = \'2026-01-27\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Roberto Cruz\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 70', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:30:20'),
(2487, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0038\', inventory_tag=\'INV-2026-00021\', date_counted=\'2026-01-27\', image=\'\', employee_id=1, category_id=2, end_user=\'Roberto Cruz\' (length: 12), item_id=70', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:30:20'),
(2488, 5, 'Asset item updated successfully', 'assets', 'Item ID: 70, End User: Roberto Cruz, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:30:20'),
(2489, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:31:46'),
(2490, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 10:49:04'),
(2491, 5, 'ITR Form Data Received', 'forms', 'From Office: 1, To Office: 6, Items: [\"70\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2492, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2493, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Asset ID=70, To Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2494, 5, 'ITR Asset Check SQL', 'assets', 'Check SQL: SELECT id, description, employee_id FROM asset_items WHERE id = \'70\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2495, 5, 'ITR Asset Check Results', 'assets', 'Found asset: ID=70, Description=\'HP Laptop 15-fd1168TU\', Current Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2496, 5, 'ITR Asset Update SQL', 'assets', 'Update SQL: UPDATE asset_items SET employee_id = \'6\', end_user = \'Jake Paul\' WHERE id = \'70\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2497, 5, 'Asset item transferred', 'assets', 'Asset ID: 70, Description: HP Laptop 15-fd1168TU, From Employee ID: 1, To Employee ID: 6, End User: Jake Paul, ITR: ITR-2026-00012, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2498, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00012, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2499, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-27 11:00:27'),
(2500, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:23:10'),
(2501, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:23:11'),
(2502, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:23:41'),
(2503, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:56:18'),
(2504, 5, 'ITR Form Data Received', 'forms', 'From Office: 6, To Office: 1, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:56:45'),
(2505, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:56:45'),
(2511, 5, 'ITR Form Data Received', 'forms', 'From Office: 6, To Office: 1, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:57:37'),
(2512, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 11:57:37'),
(2518, 5, 'ITR Form Data Received', 'forms', 'From Office: 6, To Office: 1, Items: [\"62\"]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2519, 5, 'ITR counter incremented', 'forms', 'Generated ITR number: ITR-2026-00015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2520, 5, 'ITR Asset Transfer Debug', 'assets', 'Attempting to transfer: Asset ID=62, To Employee ID=1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2521, 5, 'ITR Asset Check SQL', 'assets', 'Check SQL: SELECT id, description, employee_id FROM asset_items WHERE id = \'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2522, 5, 'ITR Asset Check Results', 'assets', 'Found asset: ID=62, Description=\'Acer Predator Helios Neo 16 (PHN16-72-92K1)\', Current Employee ID=6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2523, 5, 'ITR Asset Update SQL', 'assets', 'Update SQL: UPDATE asset_items SET employee_id = \'1\', end_user = \'Angela Rizal\' WHERE id = \'62\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2524, 5, 'Asset item transferred', 'assets', 'Asset ID: 62, Description: Acer Predator Helios Neo 16 (PHN16-72-92K1), From Employee ID: 6, To Employee ID: 1, End User: Angela Rizal, ITR: ITR-2026-00015, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2525, 5, 'Created ITR form', 'forms', 'ITR No: ITR-2026-00015, Entity: LGU PILAR', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2526, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:37'),
(2527, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:50'),
(2528, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:00:55'),
(2529, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton loneza (waltonloneza@gmail.com) after 4306 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:34:56'),
(2530, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:35:04'),
(2531, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:35:04'),
(2532, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:35:08'),
(2533, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:35:17'),
(2534, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-27 12:36:48'),
(2535, 5, 'login_success', 'authentication', 'User logged in: Walton loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:21:37'),
(2536, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:21:38'),
(2537, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:21:50'),
(2538, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:22:00'),
(2539, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00009, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017, INV-2026-00018, INV-2026-00019, INV-2026-00020, INV-2026-00021', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:22:10'),
(2540, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:25:54'),
(2541, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:26:01'),
(2542, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:28:01'),
(2543, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:28:03'),
(2544, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:28:08'),
(2545, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:28:10'),
(2546, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:34'),
(2547, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:37'),
(2548, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:39'),
(2549, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:41'),
(2550, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:43'),
(2551, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:45'),
(2552, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:49'),
(2553, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:51'),
(2554, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:54'),
(2555, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:29:59'),
(2556, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:30:01'),
(2557, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:35:03'),
(2558, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:37:17'),
(2559, 5, 'update', 'profile', 'Admin updated profile information', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:37:17'),
(2560, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 06:37:32'),
(2561, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 10:40:27'),
(2562, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 10:40:28'),
(2563, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 10:47:10'),
(2564, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 10:56:00'),
(2565, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:07:43'),
(2566, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:08:42'),
(2567, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:12:28'),
(2568, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:12:55'),
(2569, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:14:17'),
(2570, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:16:28'),
(2571, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:17:11'),
(2572, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:17:20'),
(2573, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:22:41'),
(2574, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:22:44'),
(2575, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 11:22:57'),
(2576, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 4991 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:03:38'),
(2577, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:03:46'),
(2578, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:03:46'),
(2579, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:05:51'),
(2580, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:08:42'),
(2581, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:14:13'),
(2582, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:24:07'),
(2583, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:24:12'),
(2584, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:29:10'),
(2585, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:41:07'),
(2586, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:41:10'),
(2587, 17, 'Tag Form Data Received', 'forms', 'Item ID: 66, End User: \'John Kenneth Litana\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:45:50'),
(2591, 17, 'Tag Form Data Received', 'forms', 'Item ID: 66, End User: \'John Kenneth Litana\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:46:43'),
(2595, 17, 'Tag Form Data Received', 'forms', 'Item ID: 67, End User: \'Angela Rizal\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:47:12'),
(2599, 17, 'Tag Form Data Received', 'forms', 'Item ID: 67, End User: \'Angela Rizal\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:49:32'),
(2603, 17, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:59:20'),
(2604, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 12:59:40'),
(2605, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:01:53'),
(2606, 17, 'Tag Form Data Received', 'forms', 'Item ID: 67, End User: \'Angela Rizal\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:03:10'),
(2610, 1, 'Tag Form Data Received', 'forms', 'Item ID: 67, Category ID: 2, Property No: \'PAR-TEST-001\', Inventory Tag: \'INV-2026-TEST-001\', End User: \'Test User for Debug\', Person Accountable: 1, Date Counted: \'2026-01-28\'', 'Unknown', 'Unknown', '2026-01-28 13:09:26'),
(2611, 1, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-TEST-001\', \r\n                   inventory_tag = \'INV-2026-TEST-001\', \r\n                   date_counted = \'2026-01-28\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Test User for Debug\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 67', 'Unknown', 'Unknown', '2026-01-28 13:09:26'),
(2612, 1, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-TEST-001\', inventory_tag=\'INV-2026-TEST-001\', date_counted=\'2026-01-28\', image=\'\', employee_id=1, category_id=2, end_user=\'Test User for Debug\' (length: 19), item_id=67', 'Unknown', 'Unknown', '2026-01-28 13:09:26'),
(2613, 1, 'Asset item updated successfully', 'assets', 'Item ID: 67, End User: Test User for Debug, Rows affected: 1', 'Unknown', 'Unknown', '2026-01-28 13:09:26'),
(2614, 1, 'QR Code Generated Successfully', 'assets', 'Item ID: 67, QR File: qr_asset_67_1769605766.png', 'Unknown', 'Unknown', '2026-01-28 13:09:26'),
(2615, 17, 'Tag Form Data Received', 'forms', 'Item ID: 67, Category ID: 2, Property No: \'PAR-TEST-001\', Inventory Tag: \'INV-2026-00022\', End User: \'John Kenneth Litana\', Person Accountable: 1, Date Counted: \'2026-01-28\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:10:23'),
(2620, 17, 'Tag Form Data Received', 'forms', 'Item ID: 67, Category ID: 2, Property No: \'PAR-TEST-001\', Inventory Tag: \'INV-2026-00022\', End User: \'Angela Rizal\', Person Accountable: 1, Date Counted: \'2026-01-28\'', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:15:31'),
(2625, 17, 'Tag Form Data Received', 'forms', 'Item ID: 68, End User: \'Angela Rizal\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:16'),
(2629, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 4431 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:37'),
(2630, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:44'),
(2631, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:44'),
(2632, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:17:47'),
(2633, 5, 'Failed to create PAR form', 'forms', 'Error: Item 1: Amount must be above ₱50,000. Current amount: ₱4,590.00', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:15'),
(2634, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:15'),
(2635, 5, 'PAR counter incremented', 'forms', 'Generated PAR number: PAR-0025-2026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:38'),
(2636, 5, 'Created PAR form', 'forms', 'PAR No: PAR-0025-2026, Entity: INVENTORY', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:38'),
(2637, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:38'),
(2638, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:18:42'),
(2639, 5, 'Tag Form Data Received', 'forms', 'Item ID: 71, End User: \'Jake Paul\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:19:40'),
(2640, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0105\', \r\n                   inventory_tag = \'INV-2026-00022\', \r\n                   date_counted = \'2026-01-28\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Jake Paul\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 71', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:19:40'),
(2641, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0105\', inventory_tag=\'INV-2026-00022\', date_counted=\'2026-01-28\', image=\'\', employee_id=1, category_id=2, end_user=\'Jake Paul\' (length: 9), item_id=71', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:19:40'),
(2642, 5, 'Asset item updated successfully', 'assets', 'Item ID: 71, End User: Jake Paul, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:19:40'),
(2643, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:19:58'),
(2644, 5, 'Tag Form Data Received', 'forms', 'Item ID: 68, End User: \'Angela Rizal\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:20:19'),
(2645, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0106\', \r\n                   inventory_tag = \'INV-2026-00023\', \r\n                   date_counted = \'2026-01-28\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Angela Rizal\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 68', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:20:19'),
(2646, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0106\', inventory_tag=\'INV-2026-00023\', date_counted=\'2026-01-28\', image=\'\', employee_id=1, category_id=2, end_user=\'Angela Rizal\' (length: 12), item_id=68', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:20:19'),
(2647, 5, 'Asset item updated successfully', 'assets', 'Item ID: 68, End User: Angela Rizal, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:20:19'),
(2648, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:21:03'),
(2649, 5, 'Tag Form Data Received', 'forms', 'Item ID: 72, End User: \'Roberto Cruz\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:15'),
(2650, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0107\', \r\n                   inventory_tag = \'INV-2026-00024\', \r\n                   date_counted = \'2026-01-28\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Roberto Cruz\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 72', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:15'),
(2651, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0107\', inventory_tag=\'INV-2026-00024\', date_counted=\'2026-01-28\', image=\'\', employee_id=1, category_id=2, end_user=\'Roberto Cruz\' (length: 12), item_id=72', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:15'),
(2652, 5, 'Asset item updated successfully', 'assets', 'Item ID: 72, End User: Roberto Cruz, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:15'),
(2653, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:22'),
(2654, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-28 13:23:27'),
(2655, 5, 'login_failed', 'authentication', 'Invalid password for user: Walton Loneza (waltonloneza@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:28:24'),
(2656, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:28:31'),
(2657, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:28:32'),
(2658, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:28:42'),
(2659, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:28:46'),
(2660, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:29:01'),
(2661, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:34:50'),
(2662, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:36:09'),
(2663, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:41:51'),
(2664, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:42:10'),
(2665, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:42:12'),
(2666, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:43:22'),
(2667, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:46:08'),
(2668, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:51:26'),
(2669, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:53:22'),
(2670, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:57:05'),
(2671, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 12:57:58'),
(2672, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:11:27'),
(2673, 5, 'access', 'qr_scanner', 'User accessed QR scanner', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:11:28'),
(2674, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:17:29'),
(2675, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:18:59'),
(2676, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:26:03'),
(2677, 5, 'Tag Form Data Received', 'forms', 'Item ID: 73, End User: \'Angela Rizal\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:26:24'),
(2678, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0108\', \r\n                   inventory_tag = \'INV-2026-00025\', \r\n                   date_counted = \'2026-01-29\',\r\n                   image = \'\',\r\n                   employee_id = 1, \r\n                   category_id = 2,\r\n                   end_user = \'Angela Rizal\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 73', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:26:24'),
(2679, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0108\', inventory_tag=\'INV-2026-00025\', date_counted=\'2026-01-29\', image=\'\', employee_id=1, category_id=2, end_user=\'Angela Rizal\' (length: 12), item_id=73', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:26:24'),
(2680, 5, 'Asset item updated successfully', 'assets', 'Item ID: 73, End User: Angela Rizal, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:26:24'),
(2681, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3624 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:28:55');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(2682, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:29:09'),
(2683, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-29 13:29:09'),
(2684, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:40:21'),
(2685, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:40:22'),
(2686, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:40:35'),
(2687, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:44:47'),
(2688, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:44:51'),
(2689, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:47:05'),
(2690, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:47:11'),
(2691, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:47:29'),
(2692, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:47:37'),
(2693, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:51:16'),
(2694, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:53:21'),
(2695, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:53:56'),
(2696, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:55:15'),
(2697, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:55:29'),
(2698, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:56:18'),
(2699, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:56:35'),
(2700, 5, 'Viewed RIS Form', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:56:39'),
(2701, 5, 'Printed RIS entry', 'forms', 'RIS ID: 4, RIS No: 18-RIS-01-018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:56:54'),
(2702, 5, 'Accessed RIS Entries', 'forms', 'ris_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:57:02'),
(2703, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:57:45'),
(2704, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 02:59:07'),
(2705, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:05:49'),
(2706, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:06:03'),
(2707, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:06:59'),
(2708, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:16:27'),
(2709, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:16:37'),
(2710, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:16:55'),
(2711, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:17:04'),
(2712, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:31:38'),
(2713, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:36:02'),
(2714, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:36:38'),
(2715, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:36:43'),
(2716, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:37:12'),
(2717, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:37:27'),
(2718, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3949 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:46:10'),
(2719, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:46:22'),
(2720, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:46:22'),
(2721, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 03:46:26'),
(2722, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 04:20:26'),
(2723, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:12:57'),
(2724, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:12:57'),
(2725, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:13:02'),
(2726, 5, 'Created IIRUP Form', 'forms', 'form_id: 14, form_number: IIRUP-2026-9361', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:13:15'),
(2727, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:13:15'),
(2728, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:13:20'),
(2729, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:13:24'),
(2730, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:16:58'),
(2731, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:19:29'),
(2732, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:20:56'),
(2733, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:21:02'),
(2734, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:21:06'),
(2735, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:22:29'),
(2736, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:26:28'),
(2737, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:28:00'),
(2738, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:28:11'),
(2739, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:28:12'),
(2740, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:30:58'),
(2741, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:31:03'),
(2742, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:31:05'),
(2743, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:36:57'),
(2744, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-30 12:38:27'),
(2745, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-30 12:39:29'),
(2746, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-30 12:42:12'),
(2747, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:42:45'),
(2748, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:42:45'),
(2749, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:44:25'),
(2750, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:45:35'),
(2751, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-30 12:47:30'),
(2752, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:47:43'),
(2753, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:50:36'),
(2754, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:51:01'),
(2755, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:54:18'),
(2756, 5, 'Accessed Requisition and Issue Slip Form', 'forms', 'ris_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:54:57'),
(2757, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:00'),
(2758, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:02'),
(2759, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:04'),
(2760, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:28'),
(2761, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:43'),
(2762, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:46'),
(2763, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:55:53'),
(2764, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:56:21'),
(2765, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 12:56:23'),
(2766, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 13:03:49'),
(2767, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 13:04:05'),
(2768, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 13:04:17'),
(2769, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-30 13:04:31'),
(2770, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:10:59'),
(2771, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:10:59'),
(2772, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:11:03'),
(2773, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:14:58'),
(2774, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:31:35'),
(2775, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:32:59'),
(2776, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:47:32'),
(2777, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:47:48'),
(2778, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 05:48:08'),
(2779, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:34:59'),
(2780, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:34:59'),
(2781, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:44:33'),
(2782, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:44:35'),
(2783, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:44:50'),
(2784, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:45:01'),
(2785, 5, 'Updated asset status to unserviceable', 'assets', 'asset_ids: 47,73, form_id: 15', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:45:21'),
(2786, 5, 'Created IIRUP Form', 'forms', 'form_id: 15, form_number: IIRUP-2026-8615', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:45:21'),
(2787, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:45:22'),
(2788, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:47:05'),
(2789, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:47:15'),
(2790, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:47:50'),
(2791, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:48:11'),
(2792, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:55:27'),
(2793, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:55:35'),
(2794, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:55:41'),
(2795, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 06:56:36'),
(2796, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:03:16'),
(2797, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:03:31'),
(2798, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:07:19'),
(2799, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:08:31'),
(2800, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:08:39'),
(2801, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:10:02'),
(2802, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:10:27'),
(2803, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:12:50'),
(2804, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:13:00'),
(2805, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:14:23'),
(2806, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:14:34'),
(2807, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:14:38'),
(2808, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:14:43'),
(2809, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:16:51'),
(2810, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:16:59'),
(2811, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:17:02'),
(2812, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:18:52'),
(2813, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:27:23'),
(2814, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:27:25'),
(2815, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:27:27'),
(2816, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:27:29'),
(2817, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:28:10'),
(2818, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:28:11'),
(2819, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:28:14'),
(2820, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3984 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:41:23'),
(2821, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:41:31'),
(2822, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:41:31'),
(2823, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:41:37'),
(2824, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:46:26'),
(2825, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:50:12'),
(2826, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:52:30'),
(2827, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:55:46'),
(2828, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:57:12'),
(2829, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:58:00'),
(2830, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 07:59:13'),
(2831, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:00:17'),
(2832, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:02:25'),
(2833, 5, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:02:51'),
(2834, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:03:04'),
(2835, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:03:04'),
(2836, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:03:14'),
(2837, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:04:52'),
(2838, 1, 'Updated tag format', 'tags', 'Tag type: red_tag_control', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:12'),
(2839, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:12'),
(2840, 1, 'Updated tag format', 'tags', 'Tag type: red_tag_no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:37'),
(2841, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:37'),
(2842, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:54'),
(2843, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:06:58'),
(2844, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:07:03'),
(2845, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:07:23'),
(2846, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:07:23'),
(2847, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:07:30'),
(2848, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:07:32'),
(2849, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:09:06'),
(2850, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:09:08'),
(2851, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:10:02'),
(2852, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:11:41'),
(2853, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:13:17'),
(2854, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:13:19'),
(2855, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:13:31'),
(2856, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:24:41'),
(2857, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:24:51'),
(2858, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:24:53'),
(2859, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:26:27'),
(2860, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:26:28'),
(2861, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:38:56'),
(2862, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:39:29'),
(2863, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:43:28'),
(2864, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:43:33'),
(2865, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:43:43'),
(2866, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:43:45'),
(2867, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:11'),
(2868, 5, 'redtag_created', 'inventory', 'Created red tag for: Computer desktop i7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:11'),
(2869, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:16'),
(2870, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:32'),
(2871, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:34'),
(2872, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:44'),
(2873, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:44:53'),
(2874, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:47:16'),
(2875, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:47:20'),
(2876, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:47:22'),
(2877, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 08:47:32'),
(2878, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 4192 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:15'),
(2879, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:23'),
(2880, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:23'),
(2881, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:27'),
(2882, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:29'),
(2883, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:34'),
(2884, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:38'),
(2885, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:17:54'),
(2886, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:23:51'),
(2887, 5, 'asset_status_updated', 'inventory', 'Asset ID 47 status changed to red_tagged', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:23:51'),
(2888, 5, 'redtag_created', 'inventory', 'Created red tag PS-5S-02-F-13-13 for: Computer desktop i7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:23:51'),
(2889, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:24:08'),
(2890, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:24:10'),
(2891, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:28:42'),
(2892, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:36:36'),
(2893, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:39:06'),
(2894, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:43:42'),
(2895, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:47:48'),
(2896, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:51:33'),
(2897, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:54:01'),
(2898, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:55:26'),
(2899, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:56:16');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(2900, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:56:50'),
(2901, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:56:52'),
(2902, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:56:54'),
(2903, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:57:19'),
(2904, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:57:21'),
(2905, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:57:21'),
(2906, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:57:21'),
(2907, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:57:21'),
(2908, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:58:07'),
(2909, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:58:40'),
(2910, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 09:59:33'),
(2911, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:00:10'),
(2912, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:00:16'),
(2913, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:00:19'),
(2914, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:00:22'),
(2915, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:00:59'),
(2916, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:01:02'),
(2917, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:02:38'),
(2918, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:02:57'),
(2919, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:03:38'),
(2920, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:03:41'),
(2921, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:03:42'),
(2922, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:03:43'),
(2923, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:04:27'),
(2924, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:04:28'),
(2925, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:04:31'),
(2926, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:04:32'),
(2927, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:04:34'),
(2928, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:05:04'),
(2929, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:07:22'),
(2930, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:07:24'),
(2931, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:08:41'),
(2932, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:10:33'),
(2933, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:11:17'),
(2934, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:12:22'),
(2935, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:12:40'),
(2936, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:12:45'),
(2937, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:13:14'),
(2938, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:13:15'),
(2939, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:13:56'),
(2940, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:04'),
(2941, 5, 'asset_status_updated', 'inventory', 'Asset ID 73 status changed to red_tagged', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:04'),
(2942, 5, 'redtag_created', 'inventory', 'Created red tag PS-5S-02-F-17-17 for: Laptop AMD Ryzen', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:04'),
(2943, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:47'),
(2944, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:49'),
(2945, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-17-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:14:51'),
(2946, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:15:22'),
(2947, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:15:34'),
(2948, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:15:45'),
(2949, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:15:51'),
(2950, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-13-13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:15:58'),
(2951, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-17-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:16:14'),
(2952, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-17-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:16:51'),
(2953, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:21'),
(2954, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3619 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:42'),
(2955, 5, 'login_failed', 'authentication', 'Invalid password for user: Walton Loneza (waltonloneza@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:49'),
(2956, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:56'),
(2957, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:17:56'),
(2958, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:18:00'),
(2959, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:18:29'),
(2960, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:19:51'),
(2961, 5, 'print', 'red_tags', 'Printed multiple red tags: 2 tags', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:20:25'),
(2962, 5, 'print', 'red_tags', 'Printed multiple red tags: 2 tags', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:21:31'),
(2963, 5, 'print', 'red_tags', 'Printed multiple red tags: 2 tags', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:22:31'),
(2964, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:23:14'),
(2965, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:23:16'),
(2966, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017, INV-2026-00018, INV-2026-00019, INV-2026-00020, INV-2026-00021, INV-2026-00022, INV-2026-00022, INV-2026-00023, INV-2026-00024, INV-2026-TEST-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:23:23'),
(2967, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:24:16'),
(2968, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-17-17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:24:17'),
(2969, 5, 'print', 'red_tags', 'Printed multiple red tags: 2 tags', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:24:24'),
(2970, 5, 'print', 'red_tags', 'Printed multiple red tags: 2 tags', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:29:50'),
(2971, 5, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:30:19'),
(2972, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:30:31'),
(2973, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:30:31'),
(2974, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:30:43'),
(2975, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:33:50'),
(2976, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:33:53'),
(2977, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:33:54'),
(2978, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:33:54'),
(2979, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:33:58'),
(2980, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:34:18'),
(2981, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:34:23'),
(2982, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:37:36'),
(2983, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:38:24'),
(2984, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:38:29'),
(2985, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:41:03'),
(2986, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:41:19'),
(2987, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:41:25'),
(2988, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:41:28'),
(2989, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:43:35'),
(2990, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-01 10:43:40'),
(2991, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:07:24'),
(2992, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:07:24'),
(2993, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:19:16'),
(2994, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:30:41'),
(2995, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:35:09'),
(2996, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:38:53'),
(2997, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:41:28'),
(2998, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:42:09'),
(2999, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:45:57'),
(3000, 5, 'Tag Form Data Received', 'forms', 'Item ID: 74, End User: \'John Legend\', Person Accountable: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:48:32'),
(3001, 5, 'Tag Update SQL Debug', 'forms', 'SQL: UPDATE asset_items SET \r\n                   property_no = \'PAR-0110\', \r\n                   inventory_tag = \'INV-2026-00026\', \r\n                   date_counted = \'2026-02-05\',\r\n                   image = \'asset_74_1770248912.jpg\',\r\n                   employee_id = 6, \r\n                   category_id = 2,\r\n                   end_user = \'John Legend\',\r\n                   status = \'serviceable\',\r\n                   last_updated = CURRENT_TIMESTAMP\r\n                   WHERE id = 74', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:48:32'),
(3002, 5, 'Tag Update Values Debug', 'forms', 'Values: property_no=\'PAR-0110\', inventory_tag=\'INV-2026-00026\', date_counted=\'2026-02-05\', image=\'asset_74_1770248912.jpg\', employee_id=6, category_id=2, end_user=\'John Legend\' (length: 11), item_id=74', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:48:32'),
(3003, 5, 'Asset item updated successfully', 'assets', 'Item ID: 74, End User: John Legend, Rows affected: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:48:32'),
(3004, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:52:07'),
(3005, 5, 'Error creating IIRUP Form: Table \'pims.asset_history\' doesn\'t exist', 'forms', 'error', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:52:41'),
(3006, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:52:41'),
(3007, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:54:52'),
(3008, 5, 'Accessed IIRUP Entries', 'forms', 'iirup_entries.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:54:56'),
(3009, 5, 'Viewed IIRUP Form', 'forms', 'IIRUP ID: 15, Form No: IIRUP-2026-8615', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:07'),
(3010, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:12'),
(3011, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:19'),
(3012, 5, 'Updated asset status to unserviceable', 'assets', 'asset_ids: 74, form_id: 17', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:27'),
(3013, 5, 'Created IIRUP Form', 'forms', 'form_id: 17, form_number: IIRUP-2026-4701', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:27'),
(3014, 5, 'Accessed Individual Item Request for User Property Form', 'forms', 'iirup_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:55:27'),
(3015, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:57:46'),
(3016, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:58:27'),
(3017, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 23:58:36'),
(3018, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:09'),
(3019, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:13'),
(3020, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:16'),
(3021, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:21'),
(3022, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:23'),
(3023, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:26'),
(3024, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:28'),
(3025, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:29'),
(3026, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:30'),
(3027, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:32'),
(3028, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:35'),
(3029, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:38'),
(3030, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:41'),
(3031, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:43'),
(3032, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:43'),
(3033, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:46'),
(3034, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:00:50'),
(3035, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:01:01'),
(3036, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:01:18'),
(3037, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:02:07'),
(3038, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:02:28'),
(3039, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:03:51'),
(3040, 5, 'asset_status_updated', 'inventory', 'Asset ID 74 status changed from unserviceable to red_tagged', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:03:51'),
(3041, 5, 'redtag_created', 'inventory', 'Created red tag PS-5S-02-F-20-20 for: Laptop AMD Ryzen', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:03:51'),
(3042, 5, 'Accessed Create Red Tag page', 'inventory', 'create_redtag.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:03:58'),
(3043, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3795 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:10:39'),
(3044, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:11:06'),
(3045, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:11:06'),
(3046, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:11:12'),
(3047, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:11:42'),
(3048, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:21:57'),
(3049, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:26:48'),
(3050, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:26:52'),
(3051, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:26:54'),
(3052, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:27:40'),
(3053, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:27:43'),
(3054, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:27:45'),
(3055, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:33:17'),
(3056, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:33:32'),
(3057, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:33:42'),
(3058, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:36:19'),
(3059, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:36:21'),
(3060, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:36:44'),
(3061, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:36:45'),
(3062, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:36:45'),
(3063, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:37:28'),
(3064, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:37:29'),
(3065, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:37:31'),
(3066, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:37:32'),
(3067, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:37:33'),
(3068, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:38:32'),
(3069, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:38:33'),
(3070, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:38:34'),
(3071, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:38:35'),
(3072, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:39:37'),
(3073, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:40:57'),
(3074, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:42:23'),
(3075, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:47:07'),
(3076, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:48:53'),
(3077, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:51:01'),
(3078, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:51:05'),
(3079, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:51:08'),
(3080, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:51:22'),
(3081, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:52:45'),
(3082, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:52:50'),
(3083, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:52:50'),
(3084, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:52:51'),
(3085, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:34'),
(3086, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:36'),
(3087, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:36'),
(3088, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:43'),
(3089, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:45'),
(3090, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:45'),
(3091, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:55'),
(3092, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 00:53:56'),
(3093, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3610 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:16'),
(3094, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:29'),
(3095, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:29'),
(3096, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:36'),
(3097, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 01:11:39'),
(3098, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 7946 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:23:55'),
(3099, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:09'),
(3100, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:09'),
(3101, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:18'),
(3102, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:20'),
(3103, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:50'),
(3104, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:51'),
(3105, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:52'),
(3106, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:53'),
(3107, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:24:55'),
(3108, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:31:18'),
(3109, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:31:23'),
(3110, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:31:25'),
(3111, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:31:31'),
(3112, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:31:32'),
(3113, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:32:52'),
(3114, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:32:55'),
(3115, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:32:57'),
(3116, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:32:59'),
(3117, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:34:32'),
(3118, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:34:33'),
(3119, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:34:35'),
(3120, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:34:37'),
(3121, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:39:26'),
(3122, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:39:56'),
(3123, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:39:58');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(3124, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:39:59'),
(3125, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:40:00'),
(3126, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:43:47'),
(3127, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:43:49'),
(3128, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:43:53'),
(3129, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:43:55'),
(3130, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:43:57'),
(3131, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:47:19'),
(3132, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:47:21'),
(3133, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:47:22'),
(3134, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:47:22'),
(3135, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:48:50'),
(3136, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:48:53'),
(3137, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:48:55'),
(3138, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:49:01'),
(3139, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 03:49:03'),
(3140, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:12:13'),
(3141, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:12:19'),
(3142, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:12:21'),
(3143, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:17:17'),
(3144, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 04:19:09'),
(3145, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 04:24:54'),
(3146, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 04:28:06'),
(3147, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:28:14'),
(3148, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:28:16'),
(3149, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:30:40'),
(3150, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:30:44'),
(3151, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:30:47'),
(3152, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3999 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:30:48'),
(3153, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:06'),
(3154, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:06'),
(3155, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:09'),
(3156, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:12'),
(3157, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:14'),
(3158, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:23'),
(3159, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:31:24'),
(3160, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:35:34'),
(3161, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:35:38'),
(3162, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:35:40'),
(3163, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:36:11'),
(3164, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:39:45'),
(3165, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:39:55'),
(3166, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:39:57'),
(3167, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:41:42'),
(3168, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:41:46'),
(3169, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:16'),
(3170, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:18'),
(3171, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:19'),
(3172, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:20'),
(3173, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:38'),
(3174, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:43:47'),
(3175, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:44:58'),
(3176, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:46:52'),
(3177, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:47:01'),
(3178, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:47:04'),
(3179, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:47:24'),
(3180, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:47:25'),
(3181, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 04:47:26'),
(3182, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 04:52:53'),
(3183, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 04:56:00'),
(3184, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:17:41'),
(3185, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:17:42'),
(3186, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:21:01'),
(3187, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-05 12:27:42'),
(3188, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:28:43'),
(3189, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:28:58'),
(3190, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:36:01'),
(3191, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:36:03'),
(3192, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:37:26'),
(3193, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:37:29'),
(3194, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:38:00'),
(3195, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:55:21'),
(3196, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:55:22'),
(3197, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 12:55:31'),
(3198, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:01:21'),
(3199, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:01:37'),
(3200, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:20:14'),
(3201, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:20:20'),
(3202, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:20:21'),
(3203, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:20:24'),
(3204, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:20:27'),
(3205, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:21:34'),
(3206, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:27:03'),
(3207, 5, 'notifications_accessed', 'notifications', 'Accessed notifications page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:28:36'),
(3208, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 4266 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:28:47'),
(3209, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:30:25'),
(3210, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:30:25'),
(3211, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:32:01'),
(3212, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 13:33:03'),
(3213, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:56:46'),
(3214, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:56:47'),
(3215, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:56:54'),
(3216, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:57:06'),
(3217, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:57:07'),
(3218, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:57:15'),
(3219, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:57:17'),
(3220, 17, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:59:19'),
(3221, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 01:59:23'),
(3222, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:13:21'),
(3223, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:13:30'),
(3224, 17, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:29:58'),
(3225, 17, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:33:03'),
(3226, 17, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:34:28'),
(3227, 17, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 02:41:05'),
(3228, 17, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 4338 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:09:04'),
(3229, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:09:15'),
(3230, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:09:15'),
(3231, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:09:19'),
(3232, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:15:24'),
(3233, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:29:06'),
(3234, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:30:53'),
(3235, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:31:14'),
(3236, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:32:50'),
(3237, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:34:44'),
(3238, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:43:11'),
(3239, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:43:12'),
(3240, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:43:15'),
(3241, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:43:15'),
(3242, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:43:16'),
(3243, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:51:41'),
(3244, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:05'),
(3245, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:09'),
(3246, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:15'),
(3247, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:18'),
(3248, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:26'),
(3249, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:26'),
(3250, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:52:55'),
(3251, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:53:09'),
(3252, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:53:15'),
(3253, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 03:53:22'),
(3254, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:05'),
(3255, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:13'),
(3256, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:14'),
(3257, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:14'),
(3258, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:15'),
(3259, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:16'),
(3260, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:16'),
(3261, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:04:17'),
(3262, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 5257 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:36:52'),
(3263, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:37:14'),
(3264, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:37:14'),
(3265, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:37:48'),
(3266, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:37:52'),
(3267, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:40:10'),
(3268, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:42:19'),
(3269, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:43:59'),
(3270, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-07 04:46:33'),
(3271, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:48:26'),
(3272, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:48:59'),
(3273, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:49:19'),
(3274, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:51:26'),
(3275, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:53:04'),
(3276, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:53:13'),
(3277, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:53:14'),
(3278, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:53:15'),
(3279, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:53:15'),
(3280, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:54:29'),
(3281, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:55:46'),
(3282, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:55:53'),
(3283, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:55:57'),
(3284, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:59:42'),
(3285, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:59:43'),
(3286, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:59:44'),
(3287, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:59:45'),
(3288, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 04:59:45'),
(3289, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-07 05:02:37'),
(3290, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-02-07 05:05:28'),
(3291, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:05:35'),
(3292, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:08:23'),
(3293, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:13:00'),
(3294, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:13:01'),
(3295, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:16:58'),
(3296, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:19:10'),
(3297, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:19:12'),
(3298, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:19:33'),
(3299, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:24:17'),
(3300, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:24:35'),
(3301, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:24:42'),
(3302, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:25:01'),
(3303, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:25:48'),
(3304, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:27:32'),
(3305, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:27:44'),
(3306, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:34:43'),
(3307, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:34:51'),
(3308, 5, 'session_timeout', 'authentication', 'Session expired for user: Walton Loneza (waltonloneza@gmail.com) after 3899 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:13'),
(3309, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:24'),
(3310, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:24'),
(3311, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:28'),
(3312, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:29'),
(3313, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:37'),
(3314, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:42:42'),
(3315, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:43:34'),
(3316, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:44:01'),
(3317, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:44:09'),
(3318, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:44:12'),
(3319, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:57:38'),
(3320, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:57:58'),
(3321, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:58:00'),
(3322, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:58:04'),
(3323, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 05:58:21'),
(3324, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 06:05:56'),
(3325, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 06:06:04'),
(3326, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 06:06:06'),
(3327, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 06:06:12'),
(3328, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:02'),
(3329, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:02'),
(3330, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:23'),
(3331, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:27'),
(3332, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:30'),
(3333, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:55:41'),
(3334, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:56:15'),
(3335, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:56:53'),
(3336, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:56:57'),
(3337, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:59:15'),
(3338, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:59:18'),
(3339, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:59:20'),
(3340, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 12:59:23'),
(3341, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:10:38'),
(3342, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:12:35'),
(3343, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:12:42'),
(3344, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:12:53'),
(3345, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:13:05'),
(3346, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:13:21'),
(3347, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:13:27'),
(3348, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:13:28'),
(3349, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-07 13:13:29');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(3350, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 22:56:26'),
(3351, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 22:56:26'),
(3352, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 22:59:14'),
(3353, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:00:34'),
(3354, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:07:36'),
(3355, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:08:05'),
(3356, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:17:19'),
(3357, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:18:36'),
(3358, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:19:21'),
(3359, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:20:08'),
(3360, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:20:09'),
(3361, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:21:05'),
(3362, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:23:52'),
(3363, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:26:44'),
(3364, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:27:10'),
(3365, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:28:23'),
(3366, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:28:25'),
(3367, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:28:42'),
(3368, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:29:54'),
(3369, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:31:02'),
(3370, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:31:04'),
(3371, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:31:04'),
(3372, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:31:04'),
(3373, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:31:55'),
(3374, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:32:20'),
(3375, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:09'),
(3376, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:10'),
(3377, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:11'),
(3378, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:11'),
(3379, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:11'),
(3380, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:11'),
(3381, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:53'),
(3382, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:54'),
(3383, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:54'),
(3384, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:54'),
(3385, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:55'),
(3386, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:33:55'),
(3387, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:34:39'),
(3388, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:34:39'),
(3389, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:34:40'),
(3390, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:36:13'),
(3391, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:42:44'),
(3392, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:42:45'),
(3393, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:42:46'),
(3394, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:44:14'),
(3395, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:10'),
(3396, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:12'),
(3397, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:12'),
(3398, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:12'),
(3399, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:13'),
(3400, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:13'),
(3401, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:55'),
(3402, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:56'),
(3403, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:56'),
(3404, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 23:46:56'),
(3405, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:21:53'),
(3406, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:21:53'),
(3407, 5, 'Accessed Inventory Custodian Slip Form', 'forms', 'ics_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:22:14'),
(3408, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:23:43'),
(3409, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:23:49'),
(3410, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:24:01'),
(3411, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017, INV-2026-00018, INV-2026-00019, INV-2026-00020, INV-2026-00021, INV-2026-00022, INV-2026-00022, INV-2026-00023, INV-2026-00024, INV-2026-TEST-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:24:29'),
(3412, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:02'),
(3413, 5, 'access', 'no_inventory_tag', 'Admin accessed no inventory tag page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:26'),
(3414, 5, 'Accessed Unserviceable Assets page', 'inventory', 'unserviceable_assets.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:32'),
(3415, 5, 'access', 'red_tags', 'Admin accessed red tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:38'),
(3416, 5, 'print', 'red_tag', 'Printed red tag: PS-5S-02-F-20-20', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:43'),
(3417, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:25:55'),
(3418, 5, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:28:58'),
(3419, 5, 'print', 'inventory_tags', 'Printed multiple inventory tags: INV-2026-00007, INV-2026-00008, INV-2026-00010, INV-2026-00011, INV-2026-00012, INV-2026-00013, INV-2026-00014, INV-2026-00015, INV-2026-00016, INV-2026-00017, INV-2026-00018, INV-2026-00019, INV-2026-00020, INV-2026-00021, INV-2026-00022, INV-2026-00022, INV-2026-00023, INV-2026-00024, INV-2026-TEST-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:29:04'),
(3420, 5, 'access', 'consumables', 'Admin accessed consumables page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:33:47'),
(3421, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:40:10'),
(3422, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:40:33'),
(3423, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:42:52'),
(3424, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:42:55'),
(3425, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:42:57'),
(3426, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:42:58'),
(3427, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:43:06'),
(3428, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:44:15'),
(3429, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:44:20'),
(3430, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:44:26'),
(3431, 5, 'access', 'fuel_inventory', 'User accessed fuel inventory page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:44:29'),
(3432, 5, 'access', 'profile', 'Admin accessed profile page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:45:55'),
(3433, 5, 'access', 'system_settings', 'Accessed system settings page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:46:02'),
(3434, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:46:06'),
(3435, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:46:11'),
(3436, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15', '2026-02-10 06:47:01'),
(3437, 17, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:58:23'),
(3438, 17, 'Tag Form Data Received', 'forms', 'Item ID: 75, End User: \'Roberto Cruz\', Person Accountable: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:59:36'),
(3442, 17, 'access', 'inventory_tags', 'Admin accessed inventory tags page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:59:48'),
(3443, 17, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 06:59:52'),
(3444, 17, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:00:21'),
(3445, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:00:35'),
(3446, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:00:35'),
(3447, 5, 'access', 'employees', 'Admin accessed employees page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:00:38'),
(3448, 5, 'view', 'employees', 'Viewed employee: Juan Dela Cruz', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:00:44'),
(3449, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:01:07'),
(3450, 5, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 07:06:25'),
(3451, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 08:10:02'),
(3452, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 08:10:03'),
(3453, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 08:10:16'),
(3454, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:40:56'),
(3455, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:40:56'),
(3456, 5, 'reports_accessed', 'reports', 'Accessed reports page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:41:11'),
(3457, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:41:15'),
(3458, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:45:15'),
(3459, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:48:54'),
(3460, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:48:55'),
(3461, 5, 'Accessed Inventory Transfer Request Form', 'forms', 'itr_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:52:50'),
(3462, 5, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:56:01'),
(3463, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:56:17'),
(3464, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:56:27'),
(3465, 1, 'access', 'categories', 'System admin accessed categories page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:58:30'),
(3466, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 12:58:39'),
(3467, 1, 'access', 'settings', 'User accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:00:24'),
(3468, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:01:49'),
(3469, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:02:45'),
(3470, 1, 'create_user', 'users', 'Created user: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:02:45'),
(3471, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:03:27'),
(3472, 1, 'create_user', 'users', 'Created user: Elton Moises (ejbm2022-9110-55459@bicol-u.edu.ph) with role: user', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:03:28'),
(3473, 1, 'logout', 'authentication', 'User logged out: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:05:01'),
(3474, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:05:15'),
(3475, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:05:15'),
(3476, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:06:39'),
(3477, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:14:00'),
(3478, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:15:42'),
(3479, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:15:44'),
(3480, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:15:44'),
(3481, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:15:45'),
(3482, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:16:16'),
(3483, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:16:17'),
(3484, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:16:17'),
(3485, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:16:17'),
(3486, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:16:18'),
(3487, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:18:33'),
(3488, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:20:29'),
(3489, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:20:30'),
(3490, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:20:30'),
(3491, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:20:30'),
(3492, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:20:30'),
(3493, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-10 13:22:18'),
(3494, 5, 'login_success', 'authentication', 'User logged in: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:21:49'),
(3495, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:21:50'),
(3496, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:22:00'),
(3497, 5, 'access', 'admin_dashboard', 'Admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:23:03'),
(3498, 5, 'Accessed Property Acknowledgment Receipt Form', 'forms', 'par_form.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:23:07'),
(3499, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:23:19'),
(3500, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:24:32'),
(3501, 5, 'access', 'assets', 'Admin accessed assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:25:34'),
(3502, 5, 'access', 'property_card', 'User accessed Property Card page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:25:55'),
(3503, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:28:56'),
(3504, 5, 'print', 'inventory_tag', 'Printed inventory tag: INV-2026-00026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:29:14'),
(3505, 5, 'logout', 'authentication', 'User logged out: Walton Loneza (waltonloneza@gmail.com) with role: admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 00:30:09'),
(3506, 12, 'login_failed', 'authentication', 'Invalid password for user: Joshua Esc (joshuamarifrancis@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:03:47'),
(3507, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:04:08'),
(3508, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:27:06'),
(3509, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:27:06'),
(3510, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:27:25'),
(3511, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:27:43'),
(3512, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:31:08'),
(3513, 12, 'session_timeout', 'authentication', 'Session expired for user: Joshua Esc (joshuamarifrancis@gmail.com) after 5790 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:07:38'),
(3514, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:07:50'),
(3515, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:07:50'),
(3516, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:07:53'),
(3517, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:09:04'),
(3518, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:09:51'),
(3519, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:10:16'),
(3520, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:18:02'),
(3521, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:20:24'),
(3522, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:20:27'),
(3523, 12, 'access', 'dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:22:36'),
(3524, 1, 'login_failed', 'authentication', 'Invalid password for user: System Administrator (admin@pims.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:38:59'),
(3525, 1, 'login_failed', 'authentication', 'Invalid password for user: System Administrator (admin@pims.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:39:36'),
(3526, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:39:54'),
(3527, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:39:54'),
(3528, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 05:40:01'),
(3529, 1, 'session_timeout', 'authentication', 'Session expired for user: System Administrator (admin@pims.com) after 3753 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 06:42:27'),
(3530, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 06:42:39'),
(3531, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 06:42:39'),
(3532, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 06:42:44'),
(3533, 1, 'session_timeout', 'authentication', 'Session expired for user: System Administrator (admin@pims.com) after 4390 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 07:55:49'),
(3534, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 07:56:02'),
(3535, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 07:56:02'),
(3536, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 07:56:06'),
(3537, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:44:56'),
(3538, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:44:56'),
(3539, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:44:59'),
(3540, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:16:31'),
(3541, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:16:32'),
(3542, NULL, 'login_failed', 'authentication', 'Password too short for email: joshuamarifrancis@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:25:36'),
(3543, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:26:00'),
(3544, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:26:00'),
(3545, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:26:04'),
(3546, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:33:08'),
(3547, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:38:59'),
(3548, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 01:52:36'),
(3549, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:00:19'),
(3550, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:01:39'),
(3551, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:09:06'),
(3552, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:15:48'),
(3553, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:22:39'),
(3554, 12, 'session_timeout', 'authentication', 'Session expired for user: Joshua Esc (joshuamarifrancis@gmail.com) after 3911 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:31:11'),
(3555, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:31:30'),
(3556, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:31:30'),
(3557, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:34:25'),
(3558, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:39:04'),
(3559, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 02:41:05'),
(3560, 12, 'session_timeout', 'authentication', 'Session expired for user: Joshua Esc (joshuamarifrancis@gmail.com) after 8939 seconds', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:00:29'),
(3561, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:00:44'),
(3562, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:00:44'),
(3563, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:34:48'),
(3564, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:41:58'),
(3565, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:42:29'),
(3566, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:46:47'),
(3567, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:49:07'),
(3568, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:49:07'),
(3569, 1, 'access', 'settings', 'User accessed system settings page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:50:35'),
(3570, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:52:37'),
(3571, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:52:37');
INSERT INTO `system_logs` (`id`, `user_id`, `action`, `module`, `description`, `ip_address`, `user_agent`, `timestamp`) VALUES
(3572, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:53:23'),
(3573, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:53:23'),
(3574, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 05:57:33'),
(3575, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:07:39'),
(3576, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:07:39'),
(3577, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:16:23'),
(3578, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:16:26'),
(3579, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:16:31'),
(3580, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:18:08'),
(3581, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:20:38'),
(3582, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:22:14'),
(3583, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:22:14'),
(3584, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:57:25'),
(3585, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:57:25'),
(3586, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 06:57:28'),
(3587, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 07:01:01'),
(3588, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 07:07:46'),
(3589, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:37:09'),
(3590, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:37:10'),
(3591, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:37:44'),
(3592, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:38:48'),
(3593, 1, 'login_success', 'authentication', 'User logged in: System Administrator (admin@pims.com) with role: system_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:06'),
(3594, 1, 'access', 'dashboard', 'System admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:07'),
(3595, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:18'),
(3596, 1, 'access', 'categories', 'System admin accessed categories page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:27'),
(3597, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:36'),
(3598, 1, 'access', 'sub_categories', 'System admin accessed sub categories page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:43'),
(3599, 1, 'access', 'offices', 'System admin accessed offices page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:39:53'),
(3600, 1, 'Accessed Tags Management', 'tags', 'tags.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:40:07'),
(3601, 1, 'access', 'user_management', 'System admin accessed user management page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:40:25'),
(3602, 12, 'login_failed', 'authentication', 'Invalid password for user: Joshua Esc (joshuamarifrancis@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:48:20'),
(3603, 12, 'login_failed', 'authentication', 'Invalid password for user: Joshua Esc (joshuamarifrancis@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:48:38'),
(3604, 12, 'login_failed', 'authentication', 'Invalid password for user: Joshua Esc (joshuamarifrancis@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:49:02'),
(3605, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:49:21'),
(3606, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:49:21'),
(3607, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 02:53:51'),
(3608, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:00:51'),
(3609, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:00:53'),
(3610, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:02:44'),
(3611, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:07:12'),
(3612, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:09:18'),
(3613, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:11:20'),
(3614, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:11:25'),
(3615, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:11:31'),
(3616, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:14:27'),
(3617, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:17:34'),
(3618, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:17:36'),
(3619, 12, 'login_success', 'authentication', 'User logged in: Joshua Esc (joshuamarifrancis@gmail.com) with role: office_admin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:18:02'),
(3620, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:18:02'),
(3621, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:18:09'),
(3622, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:22:53'),
(3623, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:31'),
(3624, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:32'),
(3625, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:33'),
(3626, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:33'),
(3627, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:34'),
(3628, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:34'),
(3629, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:34'),
(3630, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:27:37'),
(3631, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:36:21'),
(3632, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:36:22'),
(3633, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:36:23'),
(3634, 12, 'access', 'office_dashboard', 'Office admin accessed dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:36:25'),
(3635, 12, 'access', 'office_assets', 'Office admin accessed office assets page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-18 03:36:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_name`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'system_name', 'PIMS', 'string', 'System display name', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(2, 'system_email', 'waltielappy@gmail.com', 'string', 'System email address for notifications', '2026-01-06 03:08:21', '2026-01-06 03:08:29'),
(3, 'maintenance_mode', '0', 'boolean', 'Enable/disable maintenance mode', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(4, 'allow_registration', '1', 'boolean', 'Allow new user registration', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(5, 'session_timeout', '3600', 'integer', 'User session timeout in seconds', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(6, 'max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(7, 'password_min_length', '8', 'integer', 'Minimum password length', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(8, 'backup_retention_days', '30', 'integer', 'Number of days to keep backup files', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(9, 'email_notifications', '1', 'boolean', 'Enable email notifications', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(10, 'debug_mode', '0', 'boolean', 'Enable debug mode and error logging', '2026-01-06 03:08:21', '2026-01-06 03:08:21'),
(11, 'system_logo', 'img/system_logo.png', 'string', NULL, '2026-01-06 05:04:27', '2026-01-06 05:05:32'),
(12, 'primary_color', '#0d39e7', 'string', NULL, '2026-01-06 05:20:06', '2026-01-06 05:21:18'),
(13, 'secondary_color', '#5cc2f2', 'string', NULL, '2026-01-06 05:20:06', '2026-01-06 05:20:35'),
(14, 'accent_color', '#6b90ff', 'string', NULL, '2026-01-06 05:20:06', '2026-01-06 05:21:36');

-- --------------------------------------------------------

--
-- Table structure for table `tag_formats`
--

CREATE TABLE `tag_formats` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tag_formats`
--

INSERT INTO `tag_formats` (`id`, `tag_type`, `format_components`, `auto_increment`, `digits`, `separator`, `current_number`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'property_no', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"PAR\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":4}]\"', 1, 4, '-', 112, 'active', 1, 1, '2026-01-08 04:12:14', '2026-02-10 06:59:39'),
(2, 'ics_no', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"ICS\\\"},{\\\"type\\\":\\\"month\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":4}]\"', 1, 4, '-', 19, 'active', 1, NULL, '2026-01-09 09:25:27', '2026-01-27 10:09:51'),
(3, 'itr_no', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"ITR\\\"},{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":5}]\"', 1, 5, '-', 15, 'active', 1, NULL, '2026-01-09 09:30:48', '2026-01-27 12:00:37'),
(4, 'par_no', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"PAR\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":4},{\\\"type\\\":\\\"year\\\"}]\"', 1, 4, '-', 25, 'active', 1, NULL, '2026-01-09 09:42:59', '2026-01-28 13:18:38'),
(5, 'ris_no', '\"[{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":2},{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"RIS\\\"},{\\\"type\\\":\\\"month\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":3}]\"', 1, 3, '-', 18, 'active', 1, NULL, '2026-01-09 09:44:09', '2026-01-11 10:57:20'),
(6, 'sai_no', '\"[{\\\"type\\\":\\\"month\\\"},{\\\"type\\\":\\\"year\\\"},{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"SAI\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":2}]\"', 1, 2, '-', 18, 'active', 1, NULL, '2026-01-09 09:44:49', '2026-01-11 10:57:20'),
(7, 'code', '\"[{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":4}]\"', 1, 4, '-', 18, 'active', 1, NULL, '2026-01-09 09:45:35', '2026-01-11 10:57:20'),
(8, 'inventory_tag', '[{\"type\":\"text\",\"value\":\"INV\"},{\"type\":\"year\"},{\"type\":\"digits\",\"digits\":5}]', 0, 4, '-', 26, 'active', NULL, 5, '2026-01-23 02:53:48', '2026-02-04 23:48:34'),
(9, 'red_tag_control', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"PS\\\"},{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"5S\\\"},{\\\"type\\\":\\\"month\\\"},{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"F\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":2},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":2}]\"', 1, 2, '-', 21, 'active', 1, NULL, '2026-02-01 08:06:12', '2026-02-05 00:03:58'),
(10, 'red_tag_no', '\"[{\\\"type\\\":\\\"text\\\",\\\"value\\\":\\\"CTRL\\\"},{\\\"type\\\":\\\"digits\\\",\\\"digits\\\":4}]\"', 1, 4, '-', 33, 'active', 1, NULL, '2026-02-01 08:06:37', '2026-02-05 00:03:58');

-- --------------------------------------------------------

--
-- Table structure for table `thresholds`
--

CREATE TABLE `thresholds` (
  `id` int(11) NOT NULL,
  `threshold_type` varchar(50) NOT NULL,
  `threshold_value` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thresholds`
--

INSERT INTO `thresholds` (`id`, `threshold_type`, `threshold_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'unit_cost_max', 50000.00, '0', '2026-01-09 13:16:48', '2026-01-09 13:16:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `office` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('system_admin','admin','office_admin','user') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `password_changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `must_change_password` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `phone`, `address`, `office`, `password_hash`, `role`, `first_name`, `last_name`, `is_active`, `created_at`, `updated_at`, `last_login`, `failed_login_attempts`, `is_locked`, `password_changed_at`, `must_change_password`) VALUES
(1, 'system_admin', 'admin@pims.com', '', '', NULL, '$2y$10$sTwhCxd.JawevaKAgnfMaO1p.PJ34C9ROfU4nbTkmuHHdDOzcq/nm', 'system_admin', 'System', 'Administrator', 1, '2026-01-03 13:00:37', '2026-01-04 13:21:03', NULL, 0, 0, '2026-01-06 02:21:26', 0),
(2, 'wjll2022-2920-98466@bicol-u.edu.ph', 'wjll2022-2920-98466@bicol-u.edu.ph', NULL, NULL, NULL, '$2y$10$0mPC7iEVtjGUOVHLqGdmNe5whIhEuPVQfmdliPsnSdupq20au5cl2', 'admin', 'Walton', 'loneza', 1, '2026-01-03 22:34:21', '2026-01-03 22:34:21', NULL, 0, 0, '2026-01-06 02:21:26', 0),
(4, 'notlawsfinds@gmail.com', 'notlawsfinds@gmail.com', NULL, NULL, NULL, '$2y$10$ekzQ67QhSp7H3QhmLyjbxeUwgXPw4d35vEm0mlbQX98WGDJvVRkry', 'office_admin', 'Joshua ', 'Escano', 1, '2026-01-03 22:44:32', '2026-01-03 22:44:32', NULL, 0, 0, '2026-01-06 02:21:26', 0),
(5, 'waltonloneza@gmail.com', 'waltonloneza@gmail.com', '', '', NULL, '$2y$10$2P2Q00QrNIcMU/paGbgE8u5.mBKaZqgZIf.wolQlRiPvnXx4/aydi', 'admin', 'Walton', 'Loneza', 1, '2026-01-03 22:49:38', '2026-01-28 06:37:17', NULL, 0, 0, '2026-01-06 02:21:26', 0),
(11, 'waltielappy@gmail.com', 'waltielappy@gmail.com', NULL, NULL, NULL, '$2y$10$hO1CH2GRcHTr81fLfLGokOk6kTlm9zja8X4ipgsq3Pb1ffMFS5bmu', 'user', 'Elton John', 'Moises', 0, '2026-01-04 00:39:40', '2026-01-04 00:45:06', NULL, 0, 0, '2026-01-06 02:21:26', 0),
(12, 'joshuamarifrancis@gmail.com', 'joshuamarifrancis@gmail.com', NULL, NULL, NULL, '$2y$10$CowQbiXUmW3JQNKT6OWyhOJJHq9ti3JWeuEY99k4mtqywO2PnWrA.', 'office_admin', 'Joshua', 'Esc', 1, '2026-02-10 13:02:45', '2026-02-10 13:02:45', NULL, 0, 0, '2026-02-10 13:02:45', 0),
(13, 'ejbm2022-9110-55459@bicol-u.edu.ph', 'ejbm2022-9110-55459@bicol-u.edu.ph', NULL, NULL, NULL, '$2y$10$o54U6aFysIeH5wTqGKNiN.pYkUhYuvpyfdyNFerUZF/RSTbwg/RRa', 'user', 'Elton', 'Moises', 1, '2026-02-10 13:03:28', '2026-02-10 13:03:28', NULL, 0, 0, '2026-02-10 13:03:28', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_password_history`
--

CREATE TABLE `user_password_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `asset_category_tables`
--
DROP TABLE IF EXISTS `asset_category_tables`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `asset_category_tables`  AS SELECT `ac`.`id` AS `category_id`, `ac`.`category_name` AS `category_name`, `ac`.`category_code` AS `category_code`, CASE `ac`.`category_code` WHEN 'FF' THEN 'asset_furniture' WHEN 'CE' THEN 'asset_computers' WHEN 'VH' THEN 'asset_vehicles' WHEN 'ME' THEN 'asset_machinery' WHEN 'BI' THEN 'asset_buildings' WHEN 'LD' THEN 'asset_land' WHEN 'SW' THEN 'asset_software' WHEN 'OE' THEN 'asset_office_equipment' ELSE NULL END AS `specific_table_name` FROM `asset_categories` AS `ac` WHERE `ac`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `itr_summary`
--
DROP TABLE IF EXISTS `itr_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `itr_summary`  AS SELECT `itr`.`id` AS `id`, `itr`.`entity_name` AS `entity_name`, `itr`.`fund_cluster` AS `fund_cluster`, `itr`.`itr_no` AS `itr_no`, `itr`.`from_office` AS `from_office`, `itr`.`to_office` AS `to_office`, `itr`.`transfer_date` AS `transfer_date`, `itr`.`transfer_type` AS `transfer_type`, `itr`.`end_user` AS `end_user`, `itr`.`purpose` AS `purpose`, `itr`.`status` AS `status`, `itr`.`total_amount` AS `total_amount`, count(`ii`.`id`) AS `item_count`, `itr`.`created_by` AS `created_by`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `itr`.`created_at` AS `created_at`, `itr`.`updated_at` AS `updated_at` FROM ((`itr_forms` `itr` left join `itr_items` `ii` on(`itr`.`id` = `ii`.`form_id`)) left join `users` `u` on(`itr`.`created_by` = `u`.`id`)) GROUP BY `itr`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `ris_summary`
--
DROP TABLE IF EXISTS `ris_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ris_summary`  AS SELECT `rf`.`id` AS `id`, `rf`.`ris_no` AS `ris_no`, `rf`.`sai_no` AS `sai_no`, `rf`.`code` AS `code`, `rf`.`division` AS `division`, `rf`.`office` AS `office`, `rf`.`responsibility_center` AS `responsibility_center`, `rf`.`date` AS `date`, `rf`.`purpose` AS `purpose`, `rf`.`status` AS `status`, `rf`.`total_amount` AS `total_amount`, count(`ri`.`id`) AS `item_count`, `rf`.`created_by` AS `created_by`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `rf`.`created_at` AS `created_at`, `rf`.`updated_at` AS `updated_at` FROM ((`ris_forms` `rf` left join `ris_items` `ri` on(`rf`.`id` = `ri`.`ris_form_id`)) left join `users` `u` on(`rf`.`created_by` = `u`.`id`)) GROUP BY `rf`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_category` (`asset_categories_id`),
  ADD KEY `idx_asset_office` (`office_id`),
  ADD KEY `idx_asset_description` (`description`);

--
-- Indexes for table `asset_buildings`
--
ALTER TABLE `asset_buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_building_type` (`building_type`),
  ADD KEY `idx_address` (`city`,`state`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD UNIQUE KEY `category_code` (`category_code`),
  ADD KEY `idx_category_code` (`category_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `asset_computers`
--
ALTER TABLE `asset_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_asset_id` (`asset_item_id`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_mac_address` (`mac_address`),
  ADD KEY `idx_warranty_expiry` (`warranty_expiry`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `asset_furniture`
--
ALTER TABLE `asset_furniture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_furniture_type` (`furniture_type`),
  ADD KEY `idx_location` (`location_building`,`location_floor`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_items`
--
ALTER TABLE `asset_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asset_item_asset` (`asset_id`),
  ADD KEY `idx_asset_item_status` (`status`),
  ADD KEY `idx_asset_item_office` (`office_id`),
  ADD KEY `fk_asset_items_ics` (`ics_id`),
  ADD KEY `idx_par_id` (`par_id`),
  ADD KEY `idx_asset_items_property_no` (`property_no`),
  ADD KEY `idx_asset_items_inventory_tag` (`inventory_tag`),
  ADD KEY `idx_asset_items_employee_id` (`employee_id`),
  ADD KEY `idx_asset_items_property_number` (`property_number`),
  ADD KEY `idx_asset_items_category_id` (`category_id`);

--
-- Indexes for table `asset_item_history`
--
ALTER TABLE `asset_item_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_asset_item_history_item_id` (`item_id`),
  ADD KEY `idx_asset_item_history_created_at` (`created_at`);

--
-- Indexes for table `asset_land`
--
ALTER TABLE `asset_land`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_land_type` (`land_type`),
  ADD KEY `idx_address` (`city`,`state`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_machinery`
--
ALTER TABLE `asset_machinery`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_next_maintenance` (`next_maintenance_date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_office_equipment`
--
ALTER TABLE `asset_office_equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_equipment_type` (`equipment_type`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_warranty_expiry` (`warranty_expiry`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_software`
--
ALTER TABLE `asset_software`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_software_name` (`software_name`),
  ADD KEY `idx_license_expiry` (`license_expiry`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `asset_vehicles`
--
ALTER TABLE `asset_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_id` (`asset_item_id`),
  ADD KEY `idx_plate_number` (`plate_number`),
  ADD KEY `idx_registration_expiry` (`registration_expiry`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_asset_item_id` (`asset_item_id`);

--
-- Indexes for table `backups`
--
ALTER TABLE `backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `backup_execution_logs`
--
ALTER TABLE `backup_execution_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consumables`
--
ALTER TABLE `consumables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `office_id` (`office_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_no` (`employee_no`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `idx_employees_employment_status` (`employment_status`),
  ADD KEY `idx_employees_clearance_status` (`clearance_status`),
  ADD KEY `idx_employees_employee_no` (`employee_no`);

--
-- Indexes for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempt_time` (`attempt_time`),
  ADD KEY `idx_is_blocked` (`is_blocked`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `form_code` (`form_code`),
  ADD KEY `idx_form_code` (`form_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `fuel_in`
--
ALTER TABLE `fuel_in`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fuel_in_date` (`date_time`),
  ADD KEY `idx_fuel_in_type` (`fuel_type`);

--
-- Indexes for table `fuel_inventory`
--
ALTER TABLE `fuel_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tank_number` (`tank_number`),
  ADD KEY `idx_fuel_type` (`fuel_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_fuel_inventory_updated_by` (`updated_by`);

--
-- Indexes for table `fuel_out`
--
ALTER TABLE `fuel_out`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fuel_out_date` (`fo_date`),
  ADD KEY `idx_fuel_out_type` (`fo_fuel_type`);

--
-- Indexes for table `fuel_stock`
--
ALTER TABLE `fuel_stock`
  ADD PRIMARY KEY (`fuel_type_id`);

--
-- Indexes for table `fuel_transactions`
--
ALTER TABLE `fuel_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_fuel_type` (`fuel_type`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_tank_number` (`tank_number`);

--
-- Indexes for table `fuel_types`
--
ALTER TABLE `fuel_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_fuel_type_name` (`name`);

--
-- Indexes for table `ics_form`
--
ALTER TABLE `ics_form`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_ics_no` (`ics_no`);

--
-- Indexes for table `ics_forms`
--
ALTER TABLE `ics_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ics_no` (`ics_no`);

--
-- Indexes for table `ics_items`
--
ALTER TABLE `ics_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_ics_id` (`ics_id`),
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `iirup_forms`
--
ALTER TABLE `iirup_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `form_number` (`form_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_as_of_year` (`as_of_year`);

--
-- Indexes for table `iirup_items`
--
ALTER TABLE `iirup_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`),
  ADD KEY `idx_property_no` (`property_no`),
  ADD KEY `idx_dept_office` (`dept_office`),
  ADD KEY `idx_item_order` (`item_order`);

--
-- Indexes for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_number` (`tag_number`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `person_accountable` (`person_accountable`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `fk_inventory_tags_asset_item` (`asset_item_id`);

--
-- Indexes for table `inventory_tag_attachments`
--
ALTER TABLE `inventory_tag_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tag_id` (`tag_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_tag_history`
--
ALTER TABLE `inventory_tag_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tag_id` (`tag_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `itr_forms`
--
ALTER TABLE `itr_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `itr_no` (`itr_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_itr_no` (`itr_no`),
  ADD KEY `idx_entity_name` (`entity_name`),
  ADD KEY `idx_from_office` (`from_office`),
  ADD KEY `idx_to_office` (`to_office`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_transfer_date` (`transfer_date`);

--
-- Indexes for table `itr_items`
--
ALTER TABLE `itr_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_item_no` (`item_no`),
  ADD KEY `idx_description` (`description`(255));

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `success` (`success`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `office_name` (`office_name`),
  ADD UNIQUE KEY `office_code` (`office_code`),
  ADD KEY `idx_office_code` (`office_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `online_backup_configs`
--
ALTER TABLE `online_backup_configs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `par_form`
--
ALTER TABLE `par_form`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_office_id` (`office_id`),
  ADD KEY `idx_par_no` (`par_no`);

--
-- Indexes for table `par_forms`
--
ALTER TABLE `par_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `par_no` (`par_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `par_items`
--
ALTER TABLE `par_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_form_id` (`form_id`),
  ADD KEY `idx_asset_id` (`asset_id`);

--
-- Indexes for table `password_policies`
--
ALTER TABLE `password_policies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `policy_name` (`policy_name`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `red_tags`
--
ALTER TABLE `red_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `control_no` (`control_no`),
  ADD UNIQUE KEY `red_tag_no` (`red_tag_no`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `ris_forms`
--
ALTER TABLE `ris_forms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ris_no` (`ris_no`),
  ADD UNIQUE KEY `sai_no` (`sai_no`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_ris_no` (`ris_no`),
  ADD KEY `idx_sai_no` (`sai_no`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `ris_items`
--
ALTER TABLE `ris_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ris_form_id` (`ris_form_id`),
  ADD KEY `idx_stock_no` (`stock_no`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `scheduled_backups`
--
ALTER TABLE `scheduled_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enabled` (`enabled`),
  ADD KEY `next_run` (`next_run`);

--
-- Indexes for table `security_alerts`
--
ALTER TABLE `security_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_alert_type` (`alert_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_alert_affected_user` (`affected_user_id`),
  ADD KEY `fk_alert_resolved_by` (`resolved_by`);

--
-- Indexes for table `security_audit_logs`
--
ALTER TABLE `security_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_id` (`audit_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_performed_by` (`performed_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `security_logs`
--
ALTER TABLE `security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `severity` (`severity`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `security_metrics`
--
ALTER TABLE `security_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`metric_date`),
  ADD KEY `idx_metric_date` (`metric_date`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `module` (`module`),
  ADD KEY `timestamp` (`timestamp`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `tag_formats`
--
ALTER TABLE `tag_formats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag_type` (`tag_type`);

--
-- Indexes for table `thresholds`
--
ALTER TABLE `thresholds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_office` (`office`);

--
-- Indexes for table `user_password_history`
--
ALTER TABLE `user_password_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `asset_buildings`
--
ALTER TABLE `asset_buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `asset_computers`
--
ALTER TABLE `asset_computers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `asset_furniture`
--
ALTER TABLE `asset_furniture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `asset_items`
--
ALTER TABLE `asset_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `asset_item_history`
--
ALTER TABLE `asset_item_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `asset_land`
--
ALTER TABLE `asset_land`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `asset_machinery`
--
ALTER TABLE `asset_machinery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_office_equipment`
--
ALTER TABLE `asset_office_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_software`
--
ALTER TABLE `asset_software`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_vehicles`
--
ALTER TABLE `asset_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backups`
--
ALTER TABLE `backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `backup_execution_logs`
--
ALTER TABLE `backup_execution_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `consumables`
--
ALTER TABLE `consumables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_login_attempts`
--
ALTER TABLE `failed_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fuel_in`
--
ALTER TABLE `fuel_in`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fuel_inventory`
--
ALTER TABLE `fuel_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fuel_out`
--
ALTER TABLE `fuel_out`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_transactions`
--
ALTER TABLE `fuel_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fuel_types`
--
ALTER TABLE `fuel_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ics_form`
--
ALTER TABLE `ics_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ics_forms`
--
ALTER TABLE `ics_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `ics_items`
--
ALTER TABLE `ics_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `iirup_forms`
--
ALTER TABLE `iirup_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `iirup_items`
--
ALTER TABLE `iirup_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_tag_attachments`
--
ALTER TABLE `inventory_tag_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_tag_history`
--
ALTER TABLE `inventory_tag_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `itr_forms`
--
ALTER TABLE `itr_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `itr_items`
--
ALTER TABLE `itr_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `online_backup_configs`
--
ALTER TABLE `online_backup_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `par_form`
--
ALTER TABLE `par_form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `par_forms`
--
ALTER TABLE `par_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `par_items`
--
ALTER TABLE `par_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `password_policies`
--
ALTER TABLE `password_policies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `red_tags`
--
ALTER TABLE `red_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ris_forms`
--
ALTER TABLE `ris_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ris_items`
--
ALTER TABLE `ris_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `scheduled_backups`
--
ALTER TABLE `scheduled_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_alerts`
--
ALTER TABLE `security_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_audit_logs`
--
ALTER TABLE `security_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_logs`
--
ALTER TABLE `security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `security_metrics`
--
ALTER TABLE `security_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3636;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tag_formats`
--
ALTER TABLE `tag_formats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `thresholds`
--
ALTER TABLE `thresholds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_password_history`
--
ALTER TABLE `user_password_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`asset_categories_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_buildings`
--
ALTER TABLE `asset_buildings`
  ADD CONSTRAINT `asset_buildings_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_buildings_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_buildings_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD CONSTRAINT `asset_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_categories_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_computers`
--
ALTER TABLE `asset_computers`
  ADD CONSTRAINT `asset_computers_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_computers_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_furniture`
--
ALTER TABLE `asset_furniture`
  ADD CONSTRAINT `asset_furniture_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_furniture_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_furniture_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_items`
--
ALTER TABLE `asset_items`
  ADD CONSTRAINT `asset_items_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_items_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asset_items_category` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asset_items_ics` FOREIGN KEY (`ics_id`) REFERENCES `ics_forms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asset_items_par_id` FOREIGN KEY (`par_id`) REFERENCES `par_forms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_item_history`
--
ALTER TABLE `asset_item_history`
  ADD CONSTRAINT `asset_item_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_item_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `asset_land`
--
ALTER TABLE `asset_land`
  ADD CONSTRAINT `asset_land_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_land_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_land_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_machinery`
--
ALTER TABLE `asset_machinery`
  ADD CONSTRAINT `asset_machinery_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_machinery_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_machinery_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_office_equipment`
--
ALTER TABLE `asset_office_equipment`
  ADD CONSTRAINT `asset_office_equipment_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_office_equipment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_office_equipment_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_software`
--
ALTER TABLE `asset_software`
  ADD CONSTRAINT `asset_software_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_software_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_software_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `asset_vehicles`
--
ALTER TABLE `asset_vehicles`
  ADD CONSTRAINT `asset_vehicles_ibfk_1` FOREIGN KEY (`asset_item_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asset_vehicles_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `asset_vehicles_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `backups`
--
ALTER TABLE `backups`
  ADD CONSTRAINT `backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `consumables`
--
ALTER TABLE `consumables`
  ADD CONSTRAINT `consumables_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `forms`
--
ALTER TABLE `forms`
  ADD CONSTRAINT `forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `forms_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fuel_in`
--
ALTER TABLE `fuel_in`
  ADD CONSTRAINT `fuel_in_ibfk_1` FOREIGN KEY (`fuel_type`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fuel_inventory`
--
ALTER TABLE `fuel_inventory`
  ADD CONSTRAINT `fk_fuel_inventory_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fuel_out`
--
ALTER TABLE `fuel_out`
  ADD CONSTRAINT `fuel_out_ibfk_1` FOREIGN KEY (`fo_fuel_type`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fuel_stock`
--
ALTER TABLE `fuel_stock`
  ADD CONSTRAINT `fuel_stock_ibfk_1` FOREIGN KEY (`fuel_type_id`) REFERENCES `fuel_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ics_form`
--
ALTER TABLE `ics_form`
  ADD CONSTRAINT `ics_form_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ics_form_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ics_items`
--
ALTER TABLE `ics_items`
  ADD CONSTRAINT `ics_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `ics_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `iirup_items`
--
ALTER TABLE `iirup_items`
  ADD CONSTRAINT `iirup_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `iirup_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_tags`
--
ALTER TABLE `inventory_tags`
  ADD CONSTRAINT `fk_inventory_tags_asset_item` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itr_forms`
--
ALTER TABLE `itr_forms`
  ADD CONSTRAINT `itr_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `itr_forms_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `itr_items`
--
ALTER TABLE `itr_items`
  ADD CONSTRAINT `itr_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `itr_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `offices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `offices_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `offices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `online_backup_configs`
--
ALTER TABLE `online_backup_configs`
  ADD CONSTRAINT `online_backup_configs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `par_form`
--
ALTER TABLE `par_form`
  ADD CONSTRAINT `par_form_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `par_form_ibfk_2` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `par_items`
--
ALTER TABLE `par_items`
  ADD CONSTRAINT `par_items_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `par_forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ris_forms`
--
ALTER TABLE `ris_forms`
  ADD CONSTRAINT `ris_forms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
