-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 07, 2026 at 12:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `databasemigas`
--

-- --------------------------------------------------------

--
-- Table structure for table `cashflows`
--

CREATE TABLE `cashflows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `production` double DEFAULT 0,
  `income` double NOT NULL DEFAULT 0,
  `opex` double NOT NULL DEFAULT 0,
  `taxable_income` double NOT NULL DEFAULT 0,
  `net_cashflow` double NOT NULL DEFAULT 0,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashflows`
--

INSERT INTO `cashflows` (`id`, `year`, `production`, `income`, `opex`, `taxable_income`, `net_cashflow`, `project_id`, `created_at`, `updated_at`) VALUES
(4, 1, 170, 5440, 180, 3960, 3676, 3, '2026-05-24 11:10:21', '2026-05-24 11:10:21'),
(5, 2, 201, 6432, 180, 4952, 4271.2, 3, '2026-05-24 11:10:55', '2026-05-24 11:10:55'),
(6, 3, 217, 6944, 180, 5464, 4578.4, 3, '2026-05-24 11:11:40', '2026-05-24 11:11:40'),
(7, 4, 198, 6336, 184, 4852, 4211.2, 3, '2026-05-24 11:12:22', '2026-05-24 11:12:22'),
(8, 5, 192, 6144, 189, 4655, 4093, 3, '2026-05-24 11:12:48', '2026-05-24 11:12:48'),
(9, 6, 185, 5920, 195, 4425, 3955, 3, '2026-05-24 11:13:10', '2026-05-24 11:13:10'),
(10, 7, 179, 5728, 200, 4228, 3836.8, 3, '2026-05-24 13:11:43', '2026-05-24 13:11:43'),
(11, 8, 174, 5568, 205, 4063, 3737.8, 3, '2026-05-24 11:13:42', '2026-05-24 11:13:42'),
(12, 9, 169, 5408, 210, 3898, 3638.8, 3, '2026-05-24 11:14:03', '2026-05-24 11:14:03'),
(13, 10, 165, 5280, 215, 3765, 3559, 3, '2026-05-28 08:29:52', '2026-05-28 08:29:52'),
(14, 1, 180000, 8100000, 4500000, 2100000, 2676000, 4, '2026-05-28 07:39:49', '2026-05-28 07:39:49'),
(15, 2, 135000, 5805000, 4200000, 105000, 1558800, 4, '2026-05-28 07:40:15', '2026-05-28 07:40:15'),
(16, 3, 101000, 4141000, 4000000, 0, 141000, 4, '2026-05-28 07:40:37', '2026-05-28 07:40:37'),
(17, 4, 76000, 3040000, 3800000, 0, -760000, 4, '2026-05-28 07:40:57', '2026-05-28 07:40:57'),
(18, 5, 57000, 2166000, 3600000, 0, -1434000, 4, '2026-05-28 07:41:35', '2026-05-28 07:41:35'),
(19, 2, 135000, 5805000, 4200000, 105000, 1558800, 4, '2026-05-28 07:43:36', '2026-05-28 07:43:36'),
(20, 6, 43000, 1548000, 3400000, 0, -1852000, 4, '2026-05-28 07:42:27', '2026-05-28 07:42:27'),
(21, 7, 32000, 1120000, 3200000, 0, -2080000, 4, '2026-05-28 07:44:42', '2026-05-28 07:44:42'),
(22, 8, 24000, 816000, 3000000, 0, -2184000, 4, '2026-05-28 07:45:02', '2026-05-28 07:45:02'),
(23, 9, 18000, 594000, 2900000, 0, -2306000, 4, '2026-05-28 07:45:21', '2026-05-28 07:45:21'),
(24, 10, 13000, 416000, 2800000, 0, -2384000, 4, '2026-05-28 07:45:43', '2026-05-28 07:45:43');

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `site_manager` varchar(255) NOT NULL,
  `invest_capital` int(11) NOT NULL,
  `invest_noncapital` int(11) NOT NULL,
  `tax` double NOT NULL,
  `investment_years` int(11) NOT NULL DEFAULT 7,
  `depreciation` int(11) NOT NULL,
  `depreciation_method` varchar(50) NOT NULL DEFAULT 'Straight Line',
  `decline_rate` double NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `name`, `site_manager`, `invest_capital`, `invest_noncapital`, `tax`, `investment_years`, `depreciation`, `depreciation_method`, `decline_rate`, `user_id`, `created_at`, `updated_at`) VALUES
(3, 'Blok Minyak \"UPN Veteran\"', 'anastasya', 13000, 8000, 40, 10, 0, 'Straight Line', 10, 1, '2026-05-24 11:21:30', '2026-05-24 11:21:30'),
(4, 'Blok Minyak Seturan', 'tasya', 15000000, 3000000, 44, 10, 0, 'Straight Line', 25, 1, '2026-05-28 07:39:05', '2026-05-28 07:39:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `updated_at`, `role`, `profile_photo`) VALUES
(1, 'tasya', 'anastasya@gmail.com', '$2y$10$Fee9mR0J2jk2Ld4WMycjyea2NBiUnnW6l5KQ2unWbTqBN/QRr.0Di', '2026-06-07 05:57:04', '2026-06-07 05:57:04', 'Senior Oil & Gas Analyst', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cashflows`
--
ALTER TABLE `cashflows`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `biaya` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pemilik` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cashflows`
--
ALTER TABLE `cashflows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cashflows`
--
ALTER TABLE `cashflows`
  ADD CONSTRAINT `biaya` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `pemilik` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
