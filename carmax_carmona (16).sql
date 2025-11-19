-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 04:19 AM
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
  `issue_remarks` text DEFAULT NULL,
  `is_repaired` tinyint(1) DEFAULT 0,
  `repaired_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `acquisition_issues`
--

INSERT INTO `acquisition_issues` (`issue_id`, `acquisition_id`, `issue_name`, `issue_price`, `issue_photo`, `issue_remarks`, `is_repaired`, `repaired_by`, `created_at`) VALUES
(1, 1, 'Suspension', 25000.00, 'NEM123_HONDA_BR-V_2020/issues/1762835691_issue_0.jpg', 'Issue in Suspension good', 1, 'Janzhed', '2025-11-11 04:34:51'),
(2, 2, 'Front Hood', 1000.00, 'ABC123_Toyota_Vios_2015/issues/1762836191_issue_0.jpg', 'Fixed Hood', 1, 'Karl', '2025-11-11 04:43:11'),
(3, 2, 'Suspension', 2000.00, 'ABC123_Toyota_Vios_2015/issues/1762836392_new_issue_0.jpg', 'Issue in Suspension', 1, 'Karl', '2025-11-11 04:46:32'),
(4, 3, 'Hood', 10000.00, 'BMW123_BMW_2000/issues/1763516309_issue_0.jpg', 'Fixed', 1, 'Anthony', '2025-11-19 01:38:29');

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
  `is_ordered` tinyint(1) DEFAULT 0,
  `ordered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `acquisition_parts`
--

INSERT INTO `acquisition_parts` (`part_id`, `acquisition_id`, `part_name`, `part_price`, `part_remarks`, `is_ordered`, `ordered_by`, `created_at`) VALUES
(1, 1, 'Suspension', 10000.00, 'Ordered', 1, 'Janzhed', '2025-11-11 04:34:51'),
(2, 2, 'Hood', 1000.00, 'Ordered', 1, 'Karl', '2025-11-11 04:43:11'),
(3, 2, 'Suspension', 5000.00, 'Ordered', 1, 'Karl', '2025-11-11 04:46:32'),
(4, 3, 'Hood', 3000.00, 'Ordered', 1, 'Anthony', '2025-11-19 01:38:29');

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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `page`, `timestamp`) VALUES
(1, 11, 'Created new vehicle acquisition: BMW123 - BMW 2000 (Status: Quality Check)', 'Vehicle Acquisition', '2025-11-19 01:38:29'),
(2, 12, 'Updated pricing for vehicle: BMW123 - BMW 2000 (Selling Price: ₱564,300.00, Markup: 10%)', 'Operations', '2025-11-19 01:41:37'),
(3, 10, 'Created new user account: john crew (aq.crew@carmax.com) with role: acquisition', 'Manage Users', '2025-11-19 01:44:06'),
(4, 10, 'Updated user account: john crew (Role: acquisition, Status: inactive)', 'Manage Users', '2025-11-19 01:44:59'),
(5, 12, 'Archived vehicle (sold/removed from public): BMW123 - BMW 2000', 'Operations', '2025-11-19 01:51:25');

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
(10, 'sa.admin@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'superadmin', 'Admin', 'Super', 'active', '2025-10-15 08:54:32', '2025-11-19 02:11:55'),
(11, 'aq.cruz@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'acquisition', 'Cruz', 'Juan', 'active', '2025-10-15 08:54:32', '2025-11-19 02:10:03'),
(12, 'op.reyes@carmax.com', '$2y$10$lmcVdwlCSRFOa.OKCRtSp./Vvf1j9Nyo3O832/g6z87SalnaOpH32', 'operation', 'Reyes', 'Maria', 'active', '2025-10-15 08:54:32', '2025-11-19 01:52:02'),
(14, 'aq.crew@carmax.com', '$2y$12$ouy14WLseRzMdRs8smeR.uMJjkw0wxTU9o.33tb4rAAl69hBS9oj.', 'acquisition', 'crew', 'john', 'inactive', '2025-11-19 01:44:06', NULL);

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
  `orcr_photo` varchar(255) DEFAULT NULL,
  `deed_of_sale_photo` varchar(255) DEFAULT NULL,
  `insurance_photo` varchar(255) DEFAULT NULL,
  `spare_tires` enum('Yes','No') NOT NULL,
  `complete_tools` enum('Yes','No') NOT NULL,
  `original_plate` enum('Yes','No') NOT NULL,
  `complete_documents` enum('Yes','No') NOT NULL,
  `remarks` text DEFAULT NULL,
  `acquired_price` decimal(10,2) NOT NULL,
  `repair_cost` decimal(10,2) DEFAULT 0.00,
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
-- Dumping data for table `vehicle_acquisition`
--

INSERT INTO `vehicle_acquisition` (`acquisition_id`, `vehicle_model`, `plate_number`, `year_model`, `color`, `wholecar_photo`, `dashboard_photo`, `hood_photo`, `interior_photo`, `exterior_photo`, `trunk_photo`, `orcr_photo`, `deed_of_sale_photo`, `insurance_photo`, `spare_tires`, `complete_tools`, `original_plate`, `complete_documents`, `remarks`, `acquired_price`, `repair_cost`, `issues_cost`, `parts_cost`, `total_recon_cost`, `markup_percentage`, `markup_value`, `selling_price`, `operations_updated_by`, `operations_updated_at`, `created_by`, `created_at`, `status`, `quality_checked_by`, `quality_checked_at`, `approved_by`, `approved_at`, `sent_to_operations_by`, `sent_to_operations_at`, `is_released`, `released_by`, `released_at`, `archived_by`, `archived_at`) VALUES
(1, 'HONDA BR-V', 'NEM123', 2020, 'Red', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_BRV.jpg', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_dashboard.jpg', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_hood.jpg', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_interior.jpg', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_exterior.jpg', 'NEM123_HONDA_BR-V_2020/vehicle_photos/1762835691_trunk.jpg', 'NEM123_HONDA_BR-V_2020/documents/1762835691_document.jpg', 'NEM123_HONDA_BR-V_2020/documents/1762835691_document.jpg', 'NEM123_HONDA_BR-V_2020/documents/1762835691_document.jpg', 'Yes', 'Yes', 'Yes', 'Yes', 'Okay', 250000.00, 0.00, 25000.00, 10000.00, 285000.00, 10.00, 28500.00, 313500.00, 'Maria Reyes', '2025-11-11 12:47:29', 11, '2025-11-11 04:34:51', 'Sent to Operations', 'Juan Cruz', '2025-11-11 12:44:42', 'Juan Cruz', '2025-11-11 12:44:42', 'Juan Cruz', '2025-11-11 12:46:46', 1, 'Maria Reyes', '2025-11-11 12:47:36', NULL, NULL),
(2, 'Toyota Vios', 'ABC123', 2015, 'Red', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_TOYOTA-Vios.jpg', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_dashboard2.jpg', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_hood2.jpg', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_interior2.jpg', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_exterior2.jpg', 'ABC123_Toyota_Vios_2015/vehicle_photos/1762836191_trunk2.jpg', 'ABC123_Toyota_Vios_2015/documents/1762836191_document.jpg', 'ABC123_Toyota_Vios_2015/documents/1762836191_document2.png', 'ABC123_Toyota_Vios_2015/documents/1762836191_document3.jpg', 'Yes', 'Yes', 'Yes', 'Yes', 'Good', 300000.00, 0.00, 3000.00, 6000.00, 309000.00, 20.00, 61800.00, 370800.00, 'Maria Reyes', '2025-11-11 12:49:02', 11, '2025-11-11 04:43:11', 'Sent to Operations', 'Juan Cruz', '2025-11-11 12:46:32', 'Juan Cruz', '2025-11-11 12:46:32', 'Juan Cruz', '2025-11-11 12:48:44', 1, 'Maria Reyes', '2025-11-11 12:49:13', NULL, NULL),
(3, 'BMW', 'BMW123', 2000, 'Red', 'BMW123_BMW_2000/vehicle_photos/1763516309_TOYOTA-Vios.jpg', 'BMW123_BMW_2000/vehicle_photos/1763516309_dashboard.jpg', 'BMW123_BMW_2000/vehicle_photos/1763516309_hood.jpg', 'BMW123_BMW_2000/vehicle_photos/1763516309_interior2.jpg', 'BMW123_BMW_2000/vehicle_photos/1763516309_exterior.jpg', 'BMW123_BMW_2000/vehicle_photos/1763516309_trunk2.jpg', 'BMW123_BMW_2000/documents/1763516309_document.jpg', 'BMW123_BMW_2000/documents/1763516309_document3.jpg', 'BMW123_BMW_2000/documents/1763516309_document2.png', 'Yes', 'Yes', 'Yes', 'Yes', 'Bad', 500000.00, 0.00, 10000.00, 3000.00, 513000.00, 10.00, 51300.00, 564300.00, 'Maria Reyes', '2025-11-19 09:41:37', 11, '2025-11-19 01:38:29', 'Sent to Operations', 'Juan Cruz', '2025-11-19 09:39:18', 'Juan Cruz', '2025-11-19 09:39:18', 'Juan Cruz', '2025-11-19 09:39:29', 2, 'Maria Reyes', '2025-11-19 09:41:46', 'Maria Reyes', '2025-11-19 09:51:25');

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
-- Dumping data for table `vehicle_inventory`
--

INSERT INTO `vehicle_inventory` (`inventory_id`, `supplier`, `date_acquired`, `year_model`, `make`, `model`, `variant`, `color`, `plate_number`, `fuel_type`, `odometer`, `body_type`, `spare_key`, `transmission`, `projected_repair_cost`, `actual_spend`, `cost_breakdown`, `receipt_photos`, `remarks`, `repairs_list`, `reconditions_list`, `costbreakdown_list`, `approved_checked_by`, `created_by`, `created_at`) VALUES
(1, 'CARMONA', '2025-11-10', 2020, 'Toyota', 'Vios', '1.5G', 'green', 'NEM104', 'Gasoline', 1000, 'Sedan', 'Yes', 'Manual', 6000.00, NULL, 6000.00, '[\"1762873605_0_reciept.jpg\"]', 'Fixed', '[{\"name\":\"Hood\",\"price\":\"1000\"}]', '[{\"name\":\"Fixing of Hood\",\"price\":\"5000\"}]', '[{\"item\":\"Hood\",\"category\":\"Repair\",\"price\":1000},{\"item\":\"Fixing of Hood\",\"category\":\"Recondition\",\"price\":5000}]', 'Juan Cruz', 11, '2025-11-11 15:06:45');

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
  MODIFY `issue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `acquisition_parts`
--
ALTER TABLE `acquisition_parts`
  MODIFY `part_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `vehicle_acquisition`
--
ALTER TABLE `vehicle_acquisition`
  MODIFY `acquisition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vehicle_inventory`
--
ALTER TABLE `vehicle_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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

--
-- Constraints for table `vehicle_inventory`
--
ALTER TABLE `vehicle_inventory`
  ADD CONSTRAINT `vehicle_inventory_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
