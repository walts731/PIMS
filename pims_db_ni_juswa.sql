-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 02:44 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `office` int(100) DEFAULT NULL,
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
(12, 'joshuamarifrancis@gmail.com', 'joshuamarifrancis@gmail.com', NULL, NULL, 3, '$2y$10$CowQbiXUmW3JQNKT6OWyhOJJHq9ti3JWeuEY99k4mtqywO2PnWrA.', 'office_admin', 'Joshua', 'Esc', 1, '2026-02-10 13:02:45', '2026-02-25 01:30:58', NULL, 0, 0, '2026-02-10 13:02:45', 0),
(13, 'ejbm2022-9110-55459@bicol-u.edu.ph', 'ejbm2022-9110-55459@bicol-u.edu.ph', NULL, NULL, NULL, '$2y$10$o54U6aFysIeH5wTqGKNiN.pYkUhYuvpyfdyNFerUZF/RSTbwg/RRa', 'user', 'Elton', 'Moises', 1, '2026-02-10 13:03:28', '2026-02-10 13:03:28', NULL, 0, 0, '2026-02-10 13:03:28', 0),
(14, 'lgupilar.supplyroom@gmail.com', 'lgupilar.supplyroom@gmail.com', NULL, NULL, 3, '$2y$10$U/SF49GDnjdK3nhAHWcN3uxDdv05paw5qfR3uAKcMjMcuJMg8pzc6', 'admin', 'John Patrick', 'Jazareno', 1, '2026-02-24 05:27:57', '2026-02-24 05:27:57', NULL, 0, 0, '2026-02-24 05:27:57', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_office` (`office`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`office`) REFERENCES `offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
