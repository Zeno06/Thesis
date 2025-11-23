-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 23, 2025 at 06:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carmax_carmona`
--

-- --------------------------------------------------------

--
-- Table structure for table `acquisition_issues`
--

CREATE TABLE `acquisition_issues` (
  `issue_id` int(11) NOT NULL,
  `acquisition_id` int(11) NOT NULL,
  `issue_name` varchar(255) NOT NULL,
  `issue_price` decimal(10,2) DEFAULT NULL,
  `issue_photo` varchar(255) DEFAULT NULL,
  `receipt_photos` text DEFAULT NULL,
  `issue_remarks` text DEFAULT NULL,
  `is_repaired` tinyint(1) DEFAULT 0,
  `repaired_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acquisition_parts`
--

CREATE TABLE `acquisition_parts` (
  `part_id` int(11) NOT NULL,
  `acquisition_id` int(11) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_price` decimal(10,2) DEFAULT NULL,
  `part_remarks` text DEFAULT NULL,
  `receipt_photos` text DEFAULT NULL,
  `is_ordered` tinyint(1) DEFAULT 0,
  `ordered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `page` varchar(100) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('acquisition','operation','superadmin') NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `lastname`, `firstname`, `status`, `created_at`, `last_login`) VALUES
(1, 'sa.admin@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'superadmin', 'Admin', 'Super', 'active', '2025-10-15 00:54:32', '2025-11-23 05:17:57'),
(2, 'aq.cruz@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'acquisition', 'Cruz', 'Juan', 'active', '2025-10-15 00:54:32', '2025-11-23 17:41:01'),
(3, 'op.reyes@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'operation', 'Reyes', 'Maria', 'active', '2025-10-15 00:54:32', '2025-11-23 08:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_acquisition`
--

CREATE TABLE `vehicle_acquisition` (
  `acquisition_id` int(11) NOT NULL,
  `vehicle_model` varchar(100) NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `make` varchar(50) DEFAULT NULL,
  `plate_number` varchar(20) NOT NULL,
  `year_model` int(11) NOT NULL,
  `variant` varchar(50) DEFAULT NULL,
  `color` varchar(50) NOT NULL,
  `fuel_type` enum('Gasoline','Diesel','Hybrid','Electric') DEFAULT NULL,
  `odometer` int(11) DEFAULT NULL,
  `body_type` varchar(50) DEFAULT NULL,
  `transmission` enum('Manual','Automatic') DEFAULT NULL,
  `spare_key` enum('Yes','No') DEFAULT NULL,
  `dashboard_photo` varchar(255) DEFAULT NULL,
  `hood_photo` varchar(255) DEFAULT NULL,
  `interior_photo` varchar(255) DEFAULT NULL,
  `exterior_photo` varchar(255) DEFAULT NULL,
  `trunk_photo` varchar(255) DEFAULT NULL,
  `orcr_photo` varchar(255) DEFAULT NULL,
  `deed_of_sale_photo` varchar(255) DEFAULT NULL,
  `insurance_photo` varchar(255) DEFAULT NULL,
  `spare_tires` enum('Yes','No') NOT NULL,
  `complete_tools` enum('Yes','No') NOT NULL,
  `original_plate` enum('Yes','No') NOT NULL,
  `complete_documents` enum('Yes','No') NOT NULL,
  `remarks` text DEFAULT NULL,
  `acquired_price` decimal(10,2) NOT NULL,
  `issues_cost` decimal(10,2) DEFAULT 0.00,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `total_recon_cost` decimal(10,2) DEFAULT 0.00,
  `markup_percentage` decimal(5,2) DEFAULT 0.00,
  `markup_value` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `operations_updated_by` varchar(100) DEFAULT NULL,
  `operations_updated_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Quality Check','Approved','Sent to Operations') DEFAULT 'Draft',
  `quality_checked_by` varchar(100) DEFAULT NULL,
  `quality_checked_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `sent_to_operations_by` varchar(100) DEFAULT NULL,
  `sent_to_operations_at` datetime DEFAULT NULL,
  `is_released` tinyint(1) DEFAULT 0 COMMENT '0=Not Released, 1=Released, 2=Archived',
  `released_by` varchar(100) DEFAULT NULL,
  `released_at` datetime DEFAULT NULL,
  `archived_by` varchar(100) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `acquisition_issues`
--
ALTER TABLE `acquisition_issues`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `acquisition_id` (`acquisition_id`);

--
-- Indexes for table `acquisition_parts`
--
ALTER TABLE `acquisition_parts`
  ADD PRIMARY KEY (`part_id`),
  ADD KEY `acquisition_id` (`acquisition_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  ADD PRIMARY KEY (`acquisition_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acquisition_issues`
--
ALTER TABLE `acquisition_issues`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acquisition_parts`
--
ALTER TABLE `acquisition_parts`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  MODIFY `acquisition_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acquisition_issues`
--
ALTER TABLE `acquisition_issues`
  ADD CONSTRAINT `acquisition_issues_ibfk_1` FOREIGN KEY (`acquisition_id`) REFERENCES `vehicle_acquisition` (`acquisition_id`) ON DELETE CASCADE;

--
-- Constraints for table `acquisition_parts`
--
ALTER TABLE `acquisition_parts`
  ADD CONSTRAINT `acquisition_parts_ibfk_1` FOREIGN KEY (`acquisition_id`) REFERENCES `vehicle_acquisition` (`acquisition_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  ADD CONSTRAINT `vehicle_acquisition_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
