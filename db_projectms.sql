-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2025 at 03:11 AM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `activity_type`, `entity_id`, `entity_type`, `details`, `created_at`) VALUES
(1, 20, 'task_hidden', 74, 'assignment', 'Assignment ID: 74 hidden', '2025-04-30 05:19:56'),
(2, 12, 'task_hidden', 80, 'assignment', 'Assignment ID: 80 hidden', '2025-05-01 01:06:26'),
(3, 12, 'task_hidden', 79, 'assignment', 'Assignment ID: 79 hidden', '2025-05-01 01:06:28'),
(4, 25, 'task_hidden', 203, 'assignment', 'Assignment ID: 203 hidden', '2025-05-05 00:23:31'),
(5, 25, 'task_hidden', 204, 'assignment', 'Assignment ID: 204 hidden', '2025-05-05 00:23:34');

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
  `has_overdue_tasks` tinyint(1) DEFAULT 0,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_unblocked_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_accounts`
--

INSERT INTO `tbl_accounts` (`account_id`, `user_id`, `username`, `password`, `role`, `status`, `has_overdue_tasks`, `is_protected`, `date_added`, `last_unblocked_at`) VALUES
(1, 1, 'admin', 'admin', 'Admin', 'Active', 0, 0, '2025-03-15 02:16:10', NULL),
(3, 3, 'superadmin', 'superadmin123', 'Admin', 'Active', 0, 0, '2025-04-01 01:16:55', NULL),
(10, 11, 'manager', '$2y$10$K0wyTVi15t46N3Ke5lptUuzmQhDTeaXEPxOIFN1zqzB', 'Project Manager', 'Active', 0, 0, '2025-04-07 03:42:45', NULL),
(18, 19, 'admin1', '$2y$10$FsXHYlI4fPENMuU2Pqs/qeG1Xbygx2xAnxzYcn7KwdakeBSc/dbQa', 'Admin', 'Active', 0, 0, '2025-04-21 04:20:59', NULL),
(21, 22, 'a', '$2y$10$bI00Hg2dh3DKnJHiJRwyruWtaXpANy9Wque/Bw3JajT4WjREKC7OW', 'Project Manager', 'Active', 0, 0, '2025-04-26 07:47:12', NULL),
(22, 23, 's', '$2y$10$G0ROnH30b9cSW8QOTh.vAOvVUc5SpCtNXIecZ9/q6q3llIbcjzM6e', 'Project Manager', 'Active', 0, 0, '2025-04-26 08:06:45', NULL),
(23, 24, 'is', '$2y$10$3oI66G8/CjcBlssKETT4fuEgQpDVCyuSbwU/zS19uVWOx91n6eo22', 'Graphic Artist', 'Active', 0, 0, '2025-05-03 06:32:36', '2025-05-03 01:14:57'),
(24, 25, 'art', '$2y$10$XvQwPSi2APVKD6K/ckO.M.CHtyHxG4J./fK6f5dhHz/Whwox2..q2', 'Graphic Artist', 'Active', 0, 0, '2025-05-05 00:12:50', '2025-05-04 18:25:15'),
(25, 26, 'test', '$2y$10$s6Ym1SwPjlJY36n6Ldh6KeL83T.w6ot.s9b.PycV4xWxrjOm22I6e', 'Graphic Artist', 'Active', 0, 0, '2025-05-05 00:13:36', NULL);

--
-- Triggers `tbl_accounts`
--
DELIMITER $$
CREATE TRIGGER `enforce_blocked_status` BEFORE UPDATE ON `tbl_accounts` FOR EACH ROW BEGIN
    -- If has_overdue_tasks=1 and user doesn't have protection, force Blocked
    IF NEW.has_overdue_tasks = 1 AND NEW.is_protected = 0 THEN
        SET NEW.status = 'Blocked';
    END IF;
    
    -- If protected or has_overdue_tasks=0, ensure status is Active
    IF NEW.is_protected = 1 OR NEW.has_overdue_tasks = 0 THEN
        SET NEW.status = 'Active';
    END IF;
END
$$
DELIMITER ;

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
(17, 'Emall', 'test', 'Philippines', 'emall@gmail.com', 'Paulo', 'uploads/company_logos/company_1744596907_67fc6fab1e9ba.jpg', '2025-04-14', '2025-04-14 02:15:07', NULL),
(18, 'DOTA1', '1231', 'USA', 'dota@gmail.com', 'gabin', 'uploads/company_logos/company_1744683091_67fdc053229a8.png', '2025-04-16', '2025-04-15 02:11:31', '2025-04-15 02:35:39'),
(19, '1231', '123112s', 'Australia', '123@gmail.com', '123', 'uploads/company_logos/company_1744684473_67fdc5b91d9e9.jpg', '2025-04-02', '2025-04-15 02:34:33', '2025-04-15 02:36:15'),
(27, 'Profile image', 'tes', 'Philippines', 'Profile@gmail.com', 'profile', 'uploads/company_logos/company_1745651781_680c884556b34.png', '2025-04-25', '2025-04-26 07:16:21', NULL);

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
(101, '123', 27, '', '2025-04-27', '2025-05-05', 'medium', 'in_progress', 9, 1, '2025-05-05 00:14:32', '2025-05-05 00:59:19'),
(102, 'test for tommorow to see the overdue deadline', 19, '', '2025-04-27', '2025-05-05', 'medium', 'in_progress', 11, 1, '2025-05-05 00:34:41', '2025-05-05 00:54:14');

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
  `delay_acceptable` varchar(50) NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_hidden` tinyint(1) NOT NULL,
  `forgiven_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_assignments`
--

INSERT INTO `tbl_project_assignments` (`assignment_id`, `project_id`, `user_id`, `role_task`, `assigned_images`, `status_assignee`, `assigned_date`, `last_updated`, `updated_by`, `deadline`, `delay_acceptable`, `is_locked`, `is_hidden`, `forgiven_at`) VALUES
(203, 101, 25, 'Retouch', 1, 'completed', '2025-05-05 00:16:29', '2025-05-05 00:23:31', NULL, '2025-05-12', '', 0, 1, NULL),
(204, 101, 25, 'Retouch', 1, 'completed', '2025-05-05 00:16:32', '2025-05-05 00:53:07', NULL, '2025-05-01', '1', 0, 1, '2025-05-05 00:20:51'),
(205, 101, 25, 'Clipping Path', 1, 'pending', '2025-05-05 00:16:37', '2025-05-05 00:24:15', NULL, '2025-04-29', '', 0, 0, '2025-05-05 00:24:15'),
(206, 101, 25, 'Retouch', 1, 'pending', '2025-05-05 00:16:40', '2025-05-05 00:24:15', NULL, '2025-05-12', '', 0, 0, NULL),
(207, 101, 25, 'Final', 1, 'pending', '2025-05-05 00:16:47', '2025-05-05 00:24:15', NULL, '2025-05-12', '', 0, 0, NULL),
(208, 102, 26, 'Retouch', 1, 'in_progress', '2025-05-05 00:34:58', '2025-05-05 00:36:32', NULL, '2025-05-12', '', 0, 0, NULL),
(209, 102, 26, 'Retouch', 1, 'pending', '2025-05-05 00:35:03', '2025-05-05 00:36:52', NULL, '2025-05-05', '', 0, 0, NULL),
(210, 102, 26, 'Retouch', 8, 'pending', '2025-05-05 00:35:09', '2025-05-05 00:38:59', NULL, '2025-05-06', '', 0, 0, NULL),
(211, 102, 26, 'Color Correction', 1, 'pending', '2025-05-05 00:35:15', '2025-05-05 00:35:15', NULL, '2025-05-12', '', 0, 0, NULL);

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
  `estimated_time` int(255) DEFAULT NULL,
  `redo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_images`
--

INSERT INTO `tbl_project_images` (`image_id`, `project_id`, `image_path`, `image_role`, `file_type`, `file_size`, `upload_date`, `status_image`, `assignment_id`, `estimated_time`, `redo`) VALUES
(329, 101, 'Screenshot (94) - Copy.png', 'Retouch', 'image/png', 924401, '2025-05-05 00:14:32', '', 203, NULL, ''),
(330, 101, 'Screenshot (94).png', 'Retouch', 'image/png', 924401, '2025-05-05 00:14:32', '', 206, NULL, ''),
(331, 101, 'Screenshot (95).png', '', 'image/png', 336943, '2025-05-05 00:14:32', 'available', NULL, NULL, ''),
(332, 101, 'Screenshot (96).png', '', 'image/png', 234933, '2025-05-05 00:14:32', 'available', NULL, NULL, ''),
(333, 101, 'Screenshot (97).png', 'Retouch', 'image/png', 1109405, '2025-05-05 00:14:32', '', 204, NULL, ''),
(334, 101, 'Screenshot (98).png', 'Final', 'image/png', 1675078, '2025-05-05 00:14:32', '', 207, NULL, ''),
(335, 101, 'Screenshot (99).png', '', 'image/png', 1023400, '2025-05-05 00:14:32', 'available', NULL, NULL, ''),
(336, 101, 'Screenshot (100) - Copy.png', '', 'image/png', 3098084, '2025-05-05 00:14:32', 'available', NULL, NULL, ''),
(337, 101, 'Screenshot (100).png', 'Clipping Path', 'image/png', 3098084, '2025-05-05 00:14:32', '', 205, NULL, ''),
(338, 102, 'Screenshot 2025-02-10 214129.png', 'Retouch', 'image/png', 72637, '2025-05-05 00:34:41', '', 208, NULL, ''),
(339, 102, 'Screenshot 2025-02-10 214400.png', 'Retouch', 'image/png', 121817, '2025-05-05 00:34:41', '', 210, NULL, ''),
(340, 102, 'Screenshot 2025-02-10 214557.png', 'Retouch', 'image/png', 327780, '2025-05-05 00:34:41', '', 210, NULL, ''),
(341, 102, 'Screenshot 2025-02-10 215410.png', 'Retouch', 'image/png', 72184, '2025-05-05 00:34:41', '', 210, NULL, ''),
(342, 102, 'Screenshot 2025-02-11 145825.png', 'Retouch', 'image/png', 1528331, '2025-05-05 00:34:41', '', 209, NULL, ''),
(343, 102, 'Screenshot 2025-02-11 145839.png', 'Retouch', 'image/png', 1503442, '2025-05-05 00:34:41', '', 210, NULL, ''),
(344, 102, 'Screenshot 2025-02-11 163358.png', 'Retouch', 'image/png', 112733, '2025-05-05 00:34:41', '', 210, NULL, ''),
(345, 102, 'Screenshot 2025-02-13 090507.png', 'Color Correction', 'image/png', 153997, '2025-05-05 00:34:41', '', 211, NULL, ''),
(346, 102, 'Screenshot 2025-02-13 090637.png', 'Retouch', 'image/png', 658610, '2025-05-05 00:34:41', '', 210, NULL, ''),
(347, 102, 'Screenshot 2025-02-13 111005.png', 'Retouch', 'image/png', 174536, '2025-05-05 00:34:41', '', 210, NULL, ''),
(348, 102, 'Screenshot 2025-02-13 111039.png', 'Retouch', 'image/png', 408912, '2025-05-05 00:34:41', '', 210, NULL, '');

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
(1, 'Admin', 'Admin', 'Admin', '0000-00-00 00:00:00', 'Minglanilla, Cebu', '0987456325', 'tes1@gmail.com', 'uploads/profile_pictures/profile_680f0dfedce27.png', '2025-04-07 10:22:27', '2025-03-15 02:13:37', NULL, NULL),
(2, 'Artist', 'User', 'Account', '1990-01-01 00:00:00', '123 Artist Street, Graphic City', '09123456789', 'artist@example.com', 'default.jpg', '2025-04-07 10:16:05', '2025-04-01 01:16:55', NULL, NULL),
(3, 'Super', 'User', 'Admin', '1980-10-20 00:00:00', '789 Super Street, Admin City', '09345678901', 'superadmin@example.com', 'default.jpg', '2025-04-01 09:16:55', '2025-04-01 01:16:55', NULL, NULL),
(11, 'manager', 'm', 'manageer', '2025-04-02 00:00:00', '123', '12', 'manager@gmail.com', 'profile_67f349b526afd.png', '0000-00-00 00:00:00', '2025-04-07 03:42:45', NULL, NULL),
(19, 'admin1', '1', 'admin1', '2025-04-08 00:00:00', 'admin1', '1', 'admin1@gmail.com', 'profile_6805c7ab15b94.png', '0000-00-00 00:00:00', '2025-04-21 04:20:59', NULL, NULL),
(22, 'a', 'a', 'a', '2025-04-08 00:00:00', 'a', '1', 'a@gmail.com', 'uploads/profile_pictures/profile_680c8f80282d3.png', '0000-00-00 00:00:00', '2025-04-26 07:47:12', NULL, NULL),
(23, 'sad', 's', 'sad', '2025-03-31 00:00:00', 's', '12', 's@gmail.com', 'uploads/profile_pictures/profile_680edf20c393e.png', '2025-04-28 09:51:28', '2025-04-26 08:06:45', NULL, NULL),
(24, 'is', 'i', 'protected', '2025-04-27 00:00:00', '123', '123', '12333@gmail.com', 'uploads/profile_pictures/profile_6815b884880d2.png', '0000-00-00 00:00:00', '2025-05-03 06:32:36', NULL, NULL),
(25, 'art', 'a', 'art', '2025-04-28 00:00:00', '123', '123', '123@gmail.com', 'uploads/profile_pictures/profile_6818028213764.png', '0000-00-00 00:00:00', '2025-05-05 00:12:50', NULL, NULL),
(26, 'test', 't', 'test', '2025-04-27 00:00:00', '123', '123', '123311231@gmail.com', 'uploads/profile_pictures/profile_681802b05a2d5.png', '0000-00-00 00:00:00', '2025-05-05 00:13:36', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `entity_id` (`entity_id`),
  ADD KEY `entity_type` (`entity_type`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=212;

--
-- AUTO_INCREMENT for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
