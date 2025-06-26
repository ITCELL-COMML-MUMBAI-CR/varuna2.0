-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 26, 2025 at 09:28 AM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u473452443_comml_bb_cr`
--

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `licensee_id` int(11) DEFAULT NULL,
  `section_code` varchar(20) DEFAULT NULL,
  `station_code` varchar(20) NOT NULL,
  `contract_name` varchar(255) NOT NULL,
  `contract_type` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `stalls` int(11) DEFAULT NULL,
  `license_fee` decimal(10,2) NOT NULL,
  `period` varchar(255) NOT NULL,
  `status` enum('Under extension','Expired','Regular','Terminated') NOT NULL,
  `fssai_image` varchar(255) DEFAULT NULL,
  `fire_safety_image` varchar(255) DEFAULT NULL,
  `pest_control_image` varchar(255) DEFAULT NULL,
  `rail_neer_stock` int(11) DEFAULT NULL,
  `water_safety_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `netra_roles`
--

CREATE TABLE `netra_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `netra_users`
--

CREATE TABLE `netra_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `samarth_users`
--

CREATE TABLE `samarth_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('cci_admin','sci','viewer') NOT NULL,
  `section` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Section`
--

CREATE TABLE `Section` (
  `Section_Code` varchar(20) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `CCI` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sparsh_amenity_strategy`
--

CREATE TABLE `sparsh_amenity_strategy` (
  `strategy_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  `short_term_cats` text DEFAULT NULL,
  `medium_term_cats` text DEFAULT NULL,
  `long_term_cats` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sparsh_master_amenities`
--

CREATE TABLE `sparsh_master_amenities` (
  `amenity_id` int(11) NOT NULL,
  `amenity_name` varchar(255) NOT NULL,
  `guideline_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sparsh_station_status`
--

CREATE TABLE `sparsh_station_status` (
  `status_id` int(11) NOT NULL,
  `station_code` varchar(20) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  `current_status` enum('Available','Proposed','WIP','Not Avbl','NA') NOT NULL DEFAULT 'Not Avbl',
  `location_details` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `last_updated_by` varchar(100) DEFAULT NULL,
  `last_updated_on` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Station`
--

CREATE TABLE `Station` (
  `Code` varchar(20) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Category` varchar(50) DEFAULT NULL,
  `Section_Code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trains`
--

CREATE TABLE `trains` (
  `id` int(11) NOT NULL,
  `train_number` varchar(10) NOT NULL,
  `train_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_access_tokens`
--

CREATE TABLE `varuna_access_tokens` (
  `id` int(11) NOT NULL,
  `licensee_id` int(11) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_by_user_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_activity_log`
--

CREATE TABLE `varuna_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_authority_signatures`
--

CREATE TABLE `varuna_authority_signatures` (
  `user_id` int(11) NOT NULL,
  `signature_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_contract_types`
--

CREATE TABLE `varuna_contract_types` (
  `ContractType` varchar(255) NOT NULL,
  `TrainStation` text NOT NULL,
  `Section` text NOT NULL,
  `Police` text NOT NULL,
  `Medical` text NOT NULL,
  `TA` text NOT NULL,
  `PPO` text NOT NULL,
  `AadharCard` text DEFAULT NULL,
  `FSSAI` text NOT NULL,
  `FireSafety` text NOT NULL,
  `PestControl` text NOT NULL,
  `RailNeerAvailability` text NOT NULL,
  `WaterSafety` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_id_styles`
--

CREATE TABLE `varuna_id_styles` (
  `contract_type` varchar(255) NOT NULL,
  `bg_color` varchar(20) DEFAULT '#FFE4C4',
  `vendor_name_color` varchar(20) DEFAULT '#00BFFF',
  `station_train_color` varchar(20) DEFAULT '#f52c2c',
  `nav_logo_bg_color` varchar(20) NOT NULL DEFAULT '#4682B4',
  `nav_logo_font_color` varchar(20) NOT NULL DEFAULT '#FFFFFF',
  `licensee_name_color` varchar(20) NOT NULL DEFAULT '#CF5C36',
  `instructions_color` varchar(20) NOT NULL DEFAULT '#CF5C36',
  `default_font_color` varchar(20) NOT NULL DEFAULT '#000000',
  `border_color` varchar(20) NOT NULL DEFAULT '#00BFFF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_licensee`
--

CREATE TABLE `varuna_licensee` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_public_tokens`
--

CREATE TABLE `varuna_public_tokens` (
  `id` int(11) NOT NULL,
  `licensee_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_remarks`
--

CREATE TABLE `varuna_remarks` (
  `id` int(11) NOT NULL,
  `staff_id` varchar(10) NOT NULL,
  `remark_by_user_id` int(11) NOT NULL,
  `remark` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_staff`
--

CREATE TABLE `varuna_staff` (
  `id` varchar(10) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `adhar_card_number` varchar(12) DEFAULT NULL,
  `adhar_card_image` varchar(255) DEFAULT NULL COMMENT 'File path for the Aadhar card image',
  `police_image` varchar(255) DEFAULT NULL,
  `police_issue_date` date DEFAULT NULL,
  `police_expiry_date` date DEFAULT NULL,
  `medical_image` varchar(255) DEFAULT NULL,
  `medical_issue_date` date DEFAULT NULL,
  `medical_expiry_date` date DEFAULT NULL,
  `ta_image` varchar(255) DEFAULT NULL,
  `ta_expiry_date` date DEFAULT NULL,
  `ppo_image` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) NOT NULL,
  `signature_image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','terminated') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_staff_designation`
--

CREATE TABLE `varuna_staff_designation` (
  `id` int(11) NOT NULL,
  `designation_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `varuna_users`
--

CREATE TABLE `varuna_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL COMMENT 'e.g., ADMIN, SCI, VIEWER',
  `section` varchar(100) DEFAULT NULL,
  `department_section` varchar(100) DEFAULT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'e.g., active, inactive',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `licensee_id_foreign_idx` (`licensee_id`),
  ADD KEY `fk_contract_type` (`contract_type`);

--
-- Indexes for table `netra_roles`
--
ALTER TABLE `netra_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `netra_users`
--
ALTER TABLE `netra_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `samarth_users`
--
ALTER TABLE `samarth_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `Section`
--
ALTER TABLE `Section`
  ADD PRIMARY KEY (`Section_Code`);

--
-- Indexes for table `sparsh_amenity_strategy`
--
ALTER TABLE `sparsh_amenity_strategy`
  ADD PRIMARY KEY (`strategy_id`),
  ADD UNIQUE KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `sparsh_master_amenities`
--
ALTER TABLE `sparsh_master_amenities`
  ADD PRIMARY KEY (`amenity_id`),
  ADD UNIQUE KEY `amenity_name` (`amenity_name`);

--
-- Indexes for table `sparsh_station_status`
--
ALTER TABLE `sparsh_station_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `station_amenity_unique` (`station_code`,`amenity_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Indexes for table `Station`
--
ALTER TABLE `Station`
  ADD PRIMARY KEY (`Code`),
  ADD KEY `Station_ibfk_1` (`Section_Code`);

--
-- Indexes for table `trains`
--
ALTER TABLE `trains`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `train_number` (`train_number`);

--
-- Indexes for table `varuna_access_tokens`
--
ALTER TABLE `varuna_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `licensee_id` (`licensee_id`);

--
-- Indexes for table `varuna_activity_log`
--
ALTER TABLE `varuna_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `varuna_authority_signatures`
--
ALTER TABLE `varuna_authority_signatures`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `varuna_contract_types`
--
ALTER TABLE `varuna_contract_types`
  ADD PRIMARY KEY (`ContractType`);

--
-- Indexes for table `varuna_id_styles`
--
ALTER TABLE `varuna_id_styles`
  ADD PRIMARY KEY (`contract_type`);

--
-- Indexes for table `varuna_licensee`
--
ALTER TABLE `varuna_licensee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `varuna_public_tokens`
--
ALTER TABLE `varuna_public_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `licensee_id` (`licensee_id`);

--
-- Indexes for table `varuna_remarks`
--
ALTER TABLE `varuna_remarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `varuna_staff`
--
ALTER TABLE `varuna_staff`
  ADD PRIMARY KEY (`id`),
  ADD KEY `varuna_staff_ibfk_1` (`contract_id`);

--
-- Indexes for table `varuna_staff_designation`
--
ALTER TABLE `varuna_staff_designation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `designation_name` (`designation_name`);

--
-- Indexes for table `varuna_users`
--
ALTER TABLE `varuna_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `netra_roles`
--
ALTER TABLE `netra_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `netra_users`
--
ALTER TABLE `netra_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `samarth_users`
--
ALTER TABLE `samarth_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sparsh_amenity_strategy`
--
ALTER TABLE `sparsh_amenity_strategy`
  MODIFY `strategy_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sparsh_master_amenities`
--
ALTER TABLE `sparsh_master_amenities`
  MODIFY `amenity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sparsh_station_status`
--
ALTER TABLE `sparsh_station_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trains`
--
ALTER TABLE `trains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_access_tokens`
--
ALTER TABLE `varuna_access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_activity_log`
--
ALTER TABLE `varuna_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_licensee`
--
ALTER TABLE `varuna_licensee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_public_tokens`
--
ALTER TABLE `varuna_public_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_remarks`
--
ALTER TABLE `varuna_remarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_staff_designation`
--
ALTER TABLE `varuna_staff_designation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `varuna_users`
--
ALTER TABLE `varuna_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `fk_contract_type` FOREIGN KEY (`contract_type`) REFERENCES `varuna_contract_types` (`ContractType`) ON UPDATE CASCADE;

--
-- Constraints for table `netra_users`
--
ALTER TABLE `netra_users`
  ADD CONSTRAINT `netra_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `netra_roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sparsh_amenity_strategy`
--
ALTER TABLE `sparsh_amenity_strategy`
  ADD CONSTRAINT `fk_strategy_amenity_id` FOREIGN KEY (`amenity_id`) REFERENCES `sparsh_master_amenities` (`amenity_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sparsh_station_status`
--
ALTER TABLE `sparsh_station_status`
  ADD CONSTRAINT `fk_amenity_id` FOREIGN KEY (`amenity_id`) REFERENCES `sparsh_master_amenities` (`amenity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_station_code` FOREIGN KEY (`station_code`) REFERENCES `Station` (`Code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Station`
--
ALTER TABLE `Station`
  ADD CONSTRAINT `Station_ibfk_1` FOREIGN KEY (`Section_Code`) REFERENCES `Section` (`Section_Code`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `varuna_access_tokens`
--
ALTER TABLE `varuna_access_tokens`
  ADD CONSTRAINT `varuna_access_tokens_ibfk_1` FOREIGN KEY (`licensee_id`) REFERENCES `varuna_licensee` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `varuna_staff`
--
ALTER TABLE `varuna_staff`
  ADD CONSTRAINT `varuna_staff_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
