-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2025 at 08:47 AM
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
  `password` varchar(255) NOT NULL,
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
(13, 14, 'bairon', '$2y$10$KGFpydD6nIDmzwXzecgEGemRAPp/fm8/KEUGaGUOuIS', 'Graphic Artist', 'Active', '2025-04-14 05:33:05'),
(16, 17, 'paulo', '$2y$10$zjBM8gQYpfMm6gSfH0GLWO2Cw.z.MM/vFZ/c3FknMrD', 'Graphic Artist', 'Active', '2025-04-21 00:59:25'),
(17, 18, 'test', '$2y$10$I4okCgbWa7sW9ILM.dlH8OBeBKzzO8lEHhV/7UNpYEsmmkEMB5ppy', 'Graphic Artist', 'Active', '2025-04-21 01:03:46'),
(18, 19, 'admin1', '$2y$10$FsXHYlI4fPENMuU2Pqs/qeG1Xbygx2xAnxzYcn7KwdakeBSc/dbQa', 'Admin', 'Active', '2025-04-21 04:20:59');

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
(15, '123', '1231', 'Canada', '123@gmail.com', '123', 'uploads/company_logos/company_1744070705_67f468313723f.png', '2025-04-02', '2025-04-08 00:04:04', '2025-04-15 02:35:58'),
(16, 'test', 'test1', 'USA', 'test@gmail.com', 'test', '', '2025-03-31', '2025-04-08 01:39:50', '2025-04-15 02:36:09'),
(17, 'Emall', 'test', 'Philippines', 'emall@gmail.com', 'Paulo', 'uploads/company_logos/company_1744596907_67fc6fab1e9ba.jpg', '2025-04-14', '2025-04-14 02:15:07', NULL),
(18, 'DOTA1', '1231', 'USA', 'dota@gmail.com', 'gabin', 'uploads/company_logos/company_1744683091_67fdc053229a8.png', '2025-04-16', '2025-04-15 02:11:31', '2025-04-15 02:35:39'),
(19, '1231', '123112s', 'Australia', '123@gmail.com', '123', 'uploads/company_logos/company_1744684473_67fdc5b91d9e9.jpg', '2025-04-02', '2025-04-15 02:34:33', '2025-04-15 02:36:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notifications`
--

CREATE TABLE `tbl_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `entity_id` int(11) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
(56, 'HASGDJASHDKGASJDHAS', 17, '', '2025-04-08', '2025-04-24', 'medium', 'in_progress', 4, 1, '2025-04-24 00:12:54', '2025-04-26 05:12:57'),
(57, 'completedtest', 16, '', '2025-04-07', '2025-04-27', 'medium', 'in_progress', 3, 1, '2025-04-24 00:25:39', '2025-04-26 03:06:25'),
(58, '123', 15, '', '2025-04-17', '2025-04-25', 'medium', 'in_progress', 2, 1, '2025-04-26 02:05:00', '2025-04-26 03:02:55'),
(59, 'res', 17, '', '2025-04-09', '2025-04-30', 'medium', 'pending', 3, 1, '2025-04-26 02:38:33', '2025-04-26 05:29:49');

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
  `deadline` date DEFAULT NULL,
  `delay_acceptable` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_assignments`
--

INSERT INTO `tbl_project_assignments` (`assignment_id`, `project_id`, `user_id`, `role_task`, `assigned_images`, `status_assignee`, `assigned_date`, `last_updated`, `updated_by`, `deadline`, `delay_acceptable`) VALUES
(5, 56, 18, 'Final', 2, 'completed', '2025-04-26 00:19:54', '2025-04-26 02:01:44', NULL, '2025-05-03', ''),
(13, 56, 12, 'Retouch', 1, 'pending', '2025-04-26 01:27:55', '2025-04-26 04:17:37', NULL, '2025-04-22', '1'),
(14, 58, 12, 'Color Correction', 1, 'pending', '2025-04-26 03:02:55', '2025-04-26 03:02:55', NULL, '2025-05-03', ''),
(15, 57, 18, 'Color Correction', 1, 'pending', '2025-04-26 03:06:25', '2025-04-26 03:06:25', NULL, '2025-05-03', ''),
(16, 56, 18, 'Clipping Path', 1, 'pending', '2025-04-26 04:19:27', '2025-04-26 04:54:54', NULL, '2025-03-02', '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_images`
--

CREATE TABLE `tbl_project_images` (
  `image_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `image_role` varchar(50) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_image` enum('available','assigned','in_progress','completed') NOT NULL DEFAULT 'available',
  `assignment_id` int(11) DEFAULT NULL,
  `estimated_time` int(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_images`
--

INSERT INTO `tbl_project_images` (`image_id`, `project_id`, `image_path`, `image_role`, `file_type`, `file_size`, `upload_date`, `status_image`, `assignment_id`, `estimated_time`) VALUES
(6, 56, 'betterpicheadshot-1_1gqyq53.png', 'Final', 'image/png', 704801, '2025-04-26 00:19:09', '', 5, 5),
(7, 56, 'necktie.jpg', 'Final', 'image/jpeg', 267900, '2025-04-26 00:19:09', '', 5, 12),
(8, 56, 'ref.png', 'Clipping Path', 'image/png', 1112476, '2025-04-26 00:19:09', '', 16, 1),
(9, 57, 'betterpicheadshot-1_1gqyq53.png', 'Color Correction', 'image/png', 704801, '2025-04-26 00:50:08', '', 15, NULL),
(10, 57, 'necktie.jpg', '', 'image/jpeg', 267900, '2025-04-26 00:50:08', 'available', NULL, NULL),
(11, 57, 'ref.png', '', 'image/png', 1112476, '2025-04-26 00:50:08', 'available', NULL, NULL),
(12, 58, 'betterpic-export-19ac3af0-bf21_1wtngxi.jpg', 'Color Correction', 'image/jpeg', 135330, '2025-04-26 02:05:00', '', 14, NULL),
(13, 58, 'betterpic-export-19ac3af0-bf21_soerem.jpg', '', 'image/jpeg', 139882, '2025-04-26 02:05:00', 'available', NULL, NULL),
(14, 56, 'Capture001.png', '', 'image/png', 345427, '2025-04-26 02:49:26', 'available', NULL, NULL),
(15, 59, 'RobloxScreenShot20230810_181245628.png', '', 'image/png', 1006669, '2025-04-26 05:29:49', 'available', NULL, NULL),
(16, 59, 'RobloxScreenShot20230812_162658984.png', '', 'image/png', 1530246, '2025-04-26 05:29:49', 'available', NULL, NULL),
(17, 59, 'RobloxScreenShot20230815_131356993.png', '', 'image/png', 1192847, '2025-04-26 05:29:49', 'available', NULL, NULL);

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
(1, 'Admin', 'Admin', 'Admin', '0000-00-00 00:00:00', 'Minglanilla, Cebu', '0987456325', 'tes1@gmail.com', 'profile_6805d69e1d2a4.png', '2025-04-07 10:22:27', '2025-03-15 02:13:37', NULL, NULL),
(2, 'Artist', 'User', 'Account', '1990-01-01 00:00:00', '123 Artist Street, Graphic City', '09123456789', 'artist@example.com', 'default.jpg', '2025-04-07 10:16:05', '2025-04-01 01:16:55', NULL, NULL),
(3, 'Super', 'User', 'Admin', '1980-10-20 00:00:00', '789 Super Street, Admin City', '09345678901', 'superadmin@example.com', 'default.jpg', '2025-04-01 09:16:55', '2025-04-01 01:16:55', NULL, NULL),
(11, 'manager', 'm', 'manageer', '2025-04-02 00:00:00', '123', '12', 'manager@gmail.com', 'profile_67f349b526afd.png', '0000-00-00 00:00:00', '2025-04-07 03:42:45', NULL, NULL),
(12, 'art', 'a', 'artst', '2025-03-31 00:00:00', '13', '123', 'art@gmail.com', 'profile_67f34a56179a6.png', '2025-04-07 12:14:34', '2025-04-07 03:45:26', NULL, NULL),
(14, 'bairon', 'b', 'cobacha', '2025-02-11 00:00:00', '123', '123', 'bairon@gmail.com', 'profile_67fc9e11490c5.jpg', '0000-00-00 00:00:00', '2025-04-14 05:33:05', NULL, NULL),
(17, 'Paulo', 'L', 'Abaquita', '2025-04-14 00:00:00', '132', '123', 'paulolatayada21@gmail.com', 'profile_6805986d2bcda.jpg', '0000-00-00 00:00:00', '2025-04-21 00:59:25', NULL, NULL),
(18, 'test', 't', 'test', '2025-04-08 00:00:00', '123', '123', '123@gmail.com', 'profile_68059971e74f4.png', '0000-00-00 00:00:00', '2025-04-21 01:03:45', NULL, NULL),
(19, 'admin1', '1', 'admin1', '2025-04-08 00:00:00', 'admin1', '1', 'admin1@gmail.com', 'profile_6805c7ab15b94.png', '0000-00-00 00:00:00', '2025-04-21 04:20:59', NULL, NULL);

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
-- Indexes for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
