-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 09:15 AM
-- Server version: 10.6.15-MariaDB
-- PHP Version: 8.2.12

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
-- Table structure for table `peripherals`
--

CREATE TABLE `peripherals` (
  `id` int(11) NOT NULL,
  `asset_item_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Peripheral name (e.g., Monitor, Keyboard, Mouse)',
  `model` varchar(255) DEFAULT NULL COMMENT 'Model number or designation',
  `serial_number` varchar(255) DEFAULT NULL COMMENT 'Unique serial number',
  `status` enum('serviceable','unserviceable','red_tagged','no_tag','disposed') NOT NULL DEFAULT 'serviceable' COMMENT 'Current status of the peripheral',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created the record',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated the record'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Peripheral devices attached to assets (monitors, keyboards, mice, etc.)';

--
-- Dumping data for table `peripherals`
--

INSERT INTO `peripherals` (`id`, `asset_item_id`, `name`, `model`, `serial_number`, `status`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES
(1, 1, 'Dell UltraSharp Monitor', 'U2419H', 'DLU2419H001234', 'serviceable', '2026-03-25 08:12:39', '2026-03-25 08:12:39', NULL, NULL),
(2, 2, 'Logitech Keyboard', 'K840', 'LGK840001234', 'serviceable', '2026-03-25 08:12:39', '2026-03-25 08:12:39', NULL, NULL),
(3, 3, 'HP Mouse', 'X1000', 'HPX1000001234', 'serviceable', '2026-03-25 08:12:39', '2026-03-25 08:12:39', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `peripherals`
--
ALTER TABLE `peripherals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_asset_item_id` (`asset_item_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_serial_number` (`serial_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_peripherals_created_by` (`created_by`),
  ADD KEY `fk_peripherals_updated_by` (`updated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `peripherals`
--
ALTER TABLE `peripherals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `peripherals`
--
ALTER TABLE `peripherals`
  ADD CONSTRAINT `fk_peripherals_asset_item` FOREIGN KEY (`asset_item_id`) REFERENCES `asset_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peripherals_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peripherals_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
