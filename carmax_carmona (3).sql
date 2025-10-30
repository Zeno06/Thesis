-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2025 at 02:49 AM
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
  `issue_photo` varchar(255) DEFAULT NULL,
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
  `is_ordered` tinyint(1) DEFAULT 0,
  `ordered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(10, 'sa.admin@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'superadmin', 'Admin', 'Super', 'active', '2025-10-15 08:54:32', '2025-10-15 09:08:35'),
(11, 'aq.cruz@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'acquisition', 'Cruz', 'Juan', 'active', '2025-10-15 08:54:32', '2025-10-29 16:33:52'),
(12, 'op.reyes@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'operation', 'Reyes', 'Maria', 'active', '2025-10-15 08:54:32', '2025-10-29 16:24:21');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_acquisition`
--

CREATE TABLE `vehicle_acquisition` (
  `acquisition_id` int(11) NOT NULL,
  `vehicle_model` varchar(100) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `year_model` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `wholecar_photo` varchar(255) DEFAULT NULL,
  `dashboard_photo` varchar(255) DEFAULT NULL,
  `hood_photo` varchar(255) DEFAULT NULL,
  `interior_photo` varchar(255) DEFAULT NULL,
  `exterior_photo` varchar(255) DEFAULT NULL,
  `trunk_photo` varchar(255) DEFAULT NULL,
  `spare_tires` enum('Yes','No') NOT NULL,
  `complete_tools` enum('Yes','No') NOT NULL,
  `original_plate` enum('Yes','No') NOT NULL,
  `complete_documents` enum('Yes','No') NOT NULL,
  `document_photos` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `projected_recon_price` decimal(10,2) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Quality Check','Approved','Sent to Operations') DEFAULT 'Draft',
  `quality_checked_by` varchar(100) DEFAULT NULL,
  `quality_checked_at` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `sent_to_operations_by` varchar(100) DEFAULT NULL,
  `sent_to_operations_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_inventory`
--

CREATE TABLE `vehicle_inventory` (
  `inventory_id` int(11) NOT NULL,
  `supplier` varchar(100) NOT NULL,
  `date_acquired` date NOT NULL,
  `year_model` int(11) NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `variant` varchar(50) DEFAULT NULL,
  `color` varchar(50) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `fuel_type` enum('Gasoline','Diesel','Hybrid','Electric') NOT NULL,
  `odometer` int(11) NOT NULL,
  `body_type` varchar(50) NOT NULL,
  `spare_key` enum('Yes','No') NOT NULL,
  `transmission` enum('Manual','Automatic') NOT NULL,
  `projected_repair_cost` decimal(10,2) NOT NULL,
  `actual_spend` decimal(10,2) DEFAULT NULL,
  `cost_breakdown` decimal(10,2) DEFAULT NULL,
  `receipt_photos` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `repairs_list` text DEFAULT NULL,
  `reconditions_list` text DEFAULT NULL,
  `costbreakdown_list` text DEFAULT NULL,
  `approved_checked_by` varchar(300) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
-- Indexes for table `vehicle_inventory`
--
ALTER TABLE `vehicle_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `acquisition_issues`
--
ALTER TABLE `acquisition_issues`
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `acquisition_parts`
--
ALTER TABLE `acquisition_parts`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  MODIFY `acquisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vehicle_inventory`
--
ALTER TABLE `vehicle_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- Constraints for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  ADD CONSTRAINT `vehicle_acquisition_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vehicle_inventory`
--
ALTER TABLE `vehicle_inventory`
  ADD CONSTRAINT `vehicle_inventory_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
