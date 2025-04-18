-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2025 at 07:19 AM
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
-- Database: `db_projectms`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_accounts`
--

CREATE TABLE `tbl_accounts` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_accounts`
--

INSERT INTO `tbl_accounts` (`account_id`, `user_id`, `username`, `password`, `role`, `status`, `date_added`) VALUES
(1, 1, 'admin', 'admin', 'Admin', 'Active', '2025-03-15 02:16:10'),
(3, 3, 'superadmin', 'superadmin123', 'Admin', 'Active', '2025-04-01 01:16:55'),
(10, 11, 'manager', '$2y$10$K0wyTVi15t46N3Ke5lptUuzmQhDTeaXEPxOIFN1zqzB', 'Project Manager', 'Active', '2025-04-07 03:42:45'),
(11, 12, 'art', '$2y$10$ra3.faagk/2YIKnwhTPDQu3EkCaD7zj0pGpfnBbrYcp', 'Graphic Artist', 'Active', '2025-04-07 03:45:26');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_companies`
--

CREATE TABLE `tbl_companies` (
  `company_id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `country` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `person_in_charge` varchar(100) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `date_signed_up` date NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_projects`
--

CREATE TABLE `tbl_projects` (
  `project_id` int(11) NOT NULL,
  `project_title` varchar(100) NOT NULL,
  `company_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `date_arrived` date NOT NULL,
  `deadline` date NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','review','completed','delayed') NOT NULL DEFAULT 'pending',
  `total_images` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_assignments`
--

CREATE TABLE `tbl_project_assignments` (
  `assignment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL,
  `task_type` varchar(50) DEFAULT NULL,
  `assigned_images` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','qa','completed','delayed') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_images`
--

CREATE TABLE `tbl_project_images` (
  `image_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `mid_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `birth_date` datetime NOT NULL,
  `address` varchar(50) NOT NULL,
  `contact_num` varchar(20) NOT NULL,
  `email_address` varchar(50) NOT NULL,
  `profile_img` varchar(50) NOT NULL,
  `date_updated` datetime NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`user_id`, `first_name`, `mid_name`, `last_name`, `birth_date`, `address`, `contact_num`, `email_address`, `profile_img`, `date_updated`, `date_added`, `reset_token`, `token_expires`) VALUES
(1, 'Admin', 'Admin', 'Admin', '1995-05-17 00:00:00', 'Minglanilla, Cebu', '0987456325', 'tes1@gmail.com', 'profile.jpg', '2025-04-07 10:22:27', '2025-03-15 02:13:37', NULL, NULL),
(2, 'Artist', 'User', 'Account', '1990-01-01 00:00:00', '123 Artist Street, Graphic City', '09123456789', 'artist@example.com', 'default.jpg', '2025-04-07 10:16:05', '2025-04-01 01:16:55', NULL, NULL),
(3, 'Super', 'User', 'Admin', '1980-10-20 00:00:00', '789 Super Street, Admin City', '09345678901', 'superadmin@example.com', 'default.jpg', '2025-04-01 09:16:55', '2025-04-01 01:16:55', NULL, NULL),
(11, 'manager', 'm', 'manageer', '2025-04-02 00:00:00', '123', '12', 'manager@gmail.com', 'profile_67f349b526afd.png', '0000-00-00 00:00:00', '2025-04-07 03:42:45', NULL, NULL),
(12, 'art', 'a', 'artst', '2025-03-31 00:00:00', '13', '123', 'art@gmail.com', 'profile_67f34a56179a6.png', '2025-04-07 12:14:34', '2025-04-07 03:45:26', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  ADD PRIMARY KEY (`company_id`);

--
-- Indexes for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  ADD PRIMARY KEY (`project_id`);

--
-- Indexes for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  ADD PRIMARY KEY (`assignment_id`);

--
-- Indexes for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
