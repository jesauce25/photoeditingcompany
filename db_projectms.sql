-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 18, 2025 at 06:12 PM
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
(11, 12, 'art', '$2y$10$ra3.faagk/2YIKnwhTPDQu3EkCaD7zj0pGpfnBbrYcp', 'Graphic Artist', 'Active', '2025-04-07 03:45:26'),
(12, 13, 'test', '$2y$10$CufgDPYCheRFNEbHnHcUhupldf5HgQKp4pkKK1rETFn', 'Graphic Artist', 'Active', '2025-04-14 01:30:26'),
(13, 14, 'paulo', '$2y$10$hELFcRk/wCEDgieu3UYy/OWPXNZbQF99NMKctW7Icl9', 'Graphic Artist', 'Active', '2025-04-15 07:23:00');

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

--
-- Dumping data for table `tbl_companies`
--

INSERT INTO `tbl_companies` (`company_id`, `company_name`, `address`, `country`, `email`, `person_in_charge`, `logo_path`, `date_signed_up`, `date_created`, `date_updated`) VALUES
(15, '123', '123', 'Canada', '123@gmail.com', '123', 'uploads/company_logos/company_1744070705_67f468313723f.png', '2025-04-02', '2025-04-08 00:04:04', '2025-04-08 00:05:05'),
(17, 'Abaquita', '123sad', 'Australia', 'paulolatayada21@gmail.com', '123', 'uploads/company_logos/company_1744701726_67fe091e41d7d.png', '2025-04-02', '2025-04-15 07:22:06', '2025-04-15 07:22:25'),
(18, 'EMALL', '123', 'Philippines', 'dioscoraabaquita8@gmail.com', '213', 'uploads/company_logos/company_1744720523_67fe528b49e21.png', '2025-04-08', '2025-04-15 12:35:23', NULL);

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
  `status_project` enum('pending','in_progress','review','completed','delayed') NOT NULL DEFAULT 'pending',
  `total_images` int(11) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_projects`
--

INSERT INTO `tbl_projects` (`project_id`, `project_title`, `company_id`, `description`, `date_arrived`, `deadline`, `priority`, `status_project`, `total_images`, `created_by`, `date_created`, `date_updated`) VALUES
(38, '2', 15, '', '2025-04-11', '2025-04-23', 'medium', 'pending', 16, 3, '2025-04-09 01:34:18', NULL),
(42, '111111', 16, '11', '2025-04-04', '2025-04-19', 'medium', 'pending', 11, 3, '2025-04-11 01:07:57', NULL),
(43, '123', 15, '123', '2025-03-31', '2025-04-18', 'high', 'pending', 0, 3, '2025-04-14 00:35:44', '2025-04-18 15:59:55'),
(45, '123', 15, '', '2025-04-13', '2025-05-01', 'high', 'pending', 22, 3, '2025-04-14 01:31:56', '2025-04-18 16:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_assignments`
--

CREATE TABLE `tbl_project_assignments` (
  `assignment_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_task` varchar(50) NOT NULL,
  `assigned_images` int(11) NOT NULL DEFAULT 0,
  `status_assignee` varchar(50) NOT NULL,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_images`
--

CREATE TABLE `tbl_project_images` (
  `image_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_image` enum('available','assigned','in_progress','completed') NOT NULL DEFAULT 'available',
  `assignment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_images`
--

INSERT INTO `tbl_project_images` (`image_id`, `project_id`, `image_path`, `file_type`, `file_size`, `upload_date`, `status_image`, `assignment_id`) VALUES
(98, 38, 'download (2) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(99, 38, 'download (3) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(100, 38, 'download (4) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(101, 38, 'download (5) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(102, 38, 'download (6) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(103, 38, 'download (7) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(104, 38, 'download (8) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(105, 38, 'download (9) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(106, 38, 'download (10) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(107, 38, 'download (11) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(108, 38, 'download (12) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(109, 38, 'download (13) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(110, 38, 'download (14) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(111, 38, 'download (15) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(112, 38, 'download (16) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-09 01:34:18', 'available', NULL),
(200, 42, 'serene_lakeside_scene - Copy (2).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(201, 42, 'serene_lakeside_scene - Copy (3).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(202, 42, 'serene_lakeside_scene - Copy (4).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(203, 42, 'serene_lakeside_scene - Copy (5).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(204, 42, 'serene_lakeside_scene - Copy (6).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(205, 42, 'serene_lakeside_scene - Copy (7).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(206, 42, 'serene_lakeside_scene - Copy (8).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(207, 42, 'serene_lakeside_scene - Copy (9).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(208, 42, 'serene_lakeside_scene - Copy (10).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(209, 42, 'serene_lakeside_scene - Copy (11).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(210, 42, 'serene_lakeside_scene - Copy (12).tiff', 'image/tiff', 5029554, '2025-04-11 01:07:57', 'available', NULL),
(216, 43, 'serene_lakeside_scene - Copy (2).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(217, 43, 'serene_lakeside_scene - Copy (3).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(218, 43, 'serene_lakeside_scene - Copy (4).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(219, 43, 'serene_lakeside_scene - Copy (5).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(220, 43, 'serene_lakeside_scene - Copy (6).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(221, 43, 'serene_lakeside_scene - Copy (7).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(222, 43, 'serene_lakeside_scene - Copy (8).tiff', 'image/tiff', 5029554, '2025-04-14 00:35:44', 'available', NULL),
(224, 43, 'serene_lakeside_scene - Copy (4).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(225, 43, 'serene_lakeside_scene - Copy (5).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(226, 43, 'serene_lakeside_scene - Copy (6).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(227, 43, 'serene_lakeside_scene - Copy (7).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(228, 43, 'serene_lakeside_scene - Copy (8).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(229, 43, 'serene_lakeside_scene - Copy (9).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(230, 43, 'serene_lakeside_scene - Copy (10).tiff', 'image/tiff', 5029554, '2025-04-14 01:05:01', 'available', NULL),
(279, 45, 'serene_lakeside_scene - Copy (23).tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'assigned', NULL),
(280, 45, 'serene_lakeside_scene - Copy (24).tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'assigned', NULL),
(281, 45, 'serene_lakeside_scene - Copy (25).tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'available', NULL),
(282, 45, 'serene_lakeside_scene - Copy (26).tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'available', NULL),
(283, 45, 'serene_lakeside_scene - Copy.tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'available', NULL),
(284, 45, 'serene_lakeside_scene.tiff', 'image/tiff', 5029554, '2025-04-14 01:31:56', 'available', NULL),
(285, 45, '$_57.jpeg', 'image/jpeg', 218849, '2025-04-14 01:31:56', 'available', NULL),
(286, 45, '484631804_1163365521952056_2467671013877831750_n.jpg', 'image/jpeg', 130609, '2025-04-14 01:31:56', 'available', NULL),
(287, 45, 'download (1) - Copy.jpeg', 'image/jpeg', 7324, '2025-04-14 01:31:56', 'available', NULL),
(288, 45, 'download (1) - Copy.png', 'image/png', 3598, '2025-04-14 01:31:56', 'available', NULL),
(289, 45, 'download (1).jpeg', 'image/jpeg', 7324, '2025-04-14 01:31:56', 'available', NULL),
(290, 45, 'download (1).png', 'image/png', 3598, '2025-04-14 01:31:56', 'available', NULL),
(291, 45, '1744978305_6802418125925_baby.png', 'image/png', 123053, '2025-04-18 12:11:45', 'available', NULL),
(292, 45, '1744978305_68024181260c0_graduate.png', 'image/png', 49928, '2025-04-18 12:11:45', 'available', NULL),
(293, 45, '1744978305_680241812643d_lord.png', 'image/png', 581007, '2025-04-18 12:11:45', 'available', NULL),
(294, 45, '1744979596_6802468c2d8d2_baby.png', 'image/png', 123053, '2025-04-18 12:33:16', 'available', NULL),
(295, 45, '1744979596_6802468c2e06c_graduate.png', 'image/png', 49928, '2025-04-18 12:33:16', 'assigned', NULL),
(296, 45, '1744979596_6802468c2e696_lord.png', 'image/png', 581007, '2025-04-18 12:33:16', 'assigned', NULL),
(297, 45, '1744979596_6802468c2ea8e_Screenshot 2025-03-31 222958.png', 'image/png', 130520, '2025-04-18 12:33:16', 'assigned', NULL),
(298, 45, '1744979601_68024691e2dde_baby.png', 'image/png', 123053, '2025-04-18 12:33:21', 'assigned', NULL),
(300, 45, '1744992560_6802793027d26_baby.png', 'image/png', 123053, '2025-04-18 16:09:20', 'available', NULL),
(301, 45, '1744992612_68027964682a9_baby.png', 'image/png', 123053, '2025-04-18 16:10:12', 'available', NULL);

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
(12, 'art', 'a', 'artst', '2025-03-31 00:00:00', '13', '123', 'art@gmail.com', 'profile_67f34a56179a6.png', '2025-04-07 12:14:34', '2025-04-07 03:45:26', NULL, NULL),
(13, 'test', 't', 'test', '2025-04-25 00:00:00', '123', '123', '123@gmail.com', 'profile_67fc65329b98c.jpeg', '0000-00-00 00:00:00', '2025-04-14 01:30:26', NULL, NULL),
(14, 'paulo4', '123', 'abaquita', '2025-04-08 00:00:00', '123', '123', 'dioscoraabaquita8@gmail.com', 'profile_67fe095412c2d.png', '0000-00-00 00:00:00', '2025-04-15 07:23:00', NULL, NULL);

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
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_assignment_id` (`assignment_id`);

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
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=302;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
