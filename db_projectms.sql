-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 03:55 AM
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
(5, 25, 'task_hidden', 204, 'assignment', 'Assignment ID: 204 hidden', '2025-05-05 00:23:34'),
(6, 25, 'task_unhidden', 204, 'assignment', 'Assignment ID: 204 unhidden', '2025-05-05 01:52:15'),
(7, 25, 'task_hidden', 204, 'assignment', 'Assignment ID: 204 hidden', '2025-05-05 01:52:19'),
(8, 25, 'task_unhidden', 203, 'assignment', 'Assignment ID: 203 unhidden', '2025-05-05 03:24:30'),
(9, 25, 'task_hidden', 203, 'assignment', 'Assignment ID: 203 hidden', '2025-05-05 03:24:36'),
(10, 24, 'task_hidden', 212, 'assignment', 'Assignment ID: 212 hidden', '2025-05-08 00:54:15'),
(11, 24, 'task_hidden', 217, 'assignment', 'Assignment ID: 217 hidden', '2025-05-08 00:54:17'),
(12, 24, 'task_hidden', 218, 'assignment', 'Assignment ID: 218 hidden', '2025-05-08 00:54:18'),
(13, 30, 'task_hidden', 319, 'assignment', 'Assignment ID: 319 hidden', '2025-05-21 02:12:57');

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
(1, 1, 'admin', '$2y$10$DArTariPXIzsKCNBbyIytOMgE92KymGb7NOanual3oRcU8kXFHmny', 'Admin', 'Active', 0, 0, '2025-03-15 02:16:10', NULL),
(3, 3, 'superadmin', 'superadmin123', 'Admin', 'Active', 0, 0, '2025-04-01 01:16:55', NULL),
(10, 11, 'manager', '$2y$10$K0wyTVi15t46N3Ke5lptUuzmQhDTeaXEPxOIFN1zqzB', 'Project Manager', 'Active', 0, 0, '2025-04-07 03:42:45', NULL),
(18, 19, 'admin1', '$2y$10$FsXHYlI4fPENMuU2Pqs/qeG1Xbygx2xAnxzYcn7KwdakeBSc/dbQa', 'Admin', 'Active', 0, 0, '2025-04-21 04:20:59', NULL),
(21, 22, 'a', '$2y$10$bI00Hg2dh3DKnJHiJRwyruWtaXpANy9Wque/Bw3JajT4WjREKC7OW', 'Project Manager', 'Active', 0, 0, '2025-04-26 07:47:12', NULL),
(22, 23, 's', '$2y$10$G0ROnH30b9cSW8QOTh.vAOvVUc5SpCtNXIecZ9/q6q3llIbcjzM6e', 'Project Manager', 'Active', 0, 0, '2025-04-26 08:06:45', NULL),
(24, 25, 'art', '$2y$10$XvQwPSi2APVKD6K/ckO.M.CHtyHxG4J./fK6f5dhHz/Whwox2..q2', 'Graphic Artist', 'Active', 0, 0, '2025-05-05 00:12:50', '2025-05-04 18:25:15'),
(25, 26, 'test', '$2y$10$s6Ym1SwPjlJY36n6Ldh6KeL83T.w6ot.s9b.PycV4xWxrjOm22I6e', 'Graphic Artist', 'Active', 0, 0, '2025-05-05 00:13:36', '2025-05-06 18:32:22'),
(27, 28, 'd', '$2y$10$pR0eFj1pEB6iJMqbysp8ru2cJgkaGL/Fb41x57joWXLY1Fi9.xZ6C', 'Graphic Artist', 'Active', 0, 0, '2025-05-09 01:14:11', NULL),
(28, 29, 'peter', '$2y$10$KEFo/svWYOZwWP3xOU4T4eViEAmiBN1xDFsPe6ERpAAuzTSbdnMiO', 'Graphic Artist', 'Blocked', 1, 0, '2025-05-09 03:17:37', '2025-05-20 21:14:38'),
(29, 30, 'paulo', '$2y$10$UW2xmbEZj1BPRKSRHETIXeoUc5T39iTjUaqc7hAMTrYpbmUDAXr4e', 'Graphic Artist', 'Active', 0, 0, '2025-05-09 03:19:07', NULL);

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
(27, 'Profile image', 'tes', 'Philippines', 'Profile@gmail.com', 'profile', 'uploads/company_logos/company_1745651781_680c884556b34.png', '2025-04-25', '2025-04-26 07:16:21', NULL),
(28, 'ABAQUITA FAMILY', 'sad', 'Philippines', '121213@gmail.com', 'sad', 'uploads/company_logos/company_1746580248_681ab31854d91.png', '2025-04-28', '2025-05-07 01:10:48', NULL),
(29, 'ASDSADASDASDASASDASDASGWAPO', '12312', 'UK', '12312312@gmail.com', '1123', '', '2025-04-28', '2025-05-07 02:31:58', '2025-05-07 02:32:08'),
(30, 'Antiqua and barbuda', '21', 'Antigua', 's@gmail.com', '123', NULL, '2025-04-29', '2025-05-08 02:29:09', NULL),
(32, 'afghanistan', '123', 'Afghanistan', '123312@gmail.com', '321', NULL, '2025-04-28', '2025-05-08 02:45:12', NULL);

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
  `date_updated` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `hidden` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_projects`
--

INSERT INTO `tbl_projects` (`project_id`, `project_title`, `company_id`, `description`, `date_arrived`, `deadline`, `priority`, `status_project`, `total_images`, `created_by`, `date_created`, `date_updated`, `hidden`) VALUES
(178, '', 32, '', '2025-04-28', '2025-05-28', 'medium', 'completed', 4, 1, '2025-05-21 00:21:09', '2025-05-21 02:08:43', 0),
(179, '', 30, '', '2025-04-27', '2025-05-14', 'medium', 'completed', 5, 1, '2025-05-21 02:09:17', '2025-05-22 00:35:41', 0),
(180, '', 28, '', '2025-04-22', '2025-05-22', 'medium', 'completed', 9, 1, '2025-05-21 02:11:03', '2025-05-21 03:22:02', 0);

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
(319, 178, 30, 'Color Correction', 3, 'completed', '2025-05-21 00:21:26', '2025-05-23 01:10:42', NULL, '2025-05-28', '', 0, 1, NULL),
(320, 179, 30, 'Final', 3, 'pending', '2025-05-21 02:09:27', '2025-05-23 01:12:13', NULL, '2025-05-28', '', 0, 0, NULL),
(321, 180, 30, 'Clipping Path', 4, 'completed', '2025-05-21 02:11:11', '2025-05-21 03:15:10', NULL, '2025-05-28', '', 0, 0, NULL),
(322, 179, 29, 'Final', 2, 'pending', '2025-05-21 03:12:07', '2025-05-23 01:12:15', NULL, '2025-05-13', '', 1, 0, '2025-05-21 03:13:38'),
(323, 180, 29, 'Retouch', 1, 'pending', '2025-05-21 03:22:02', '2025-05-23 00:24:08', NULL, '2025-05-22', '', 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_project_images`
--

CREATE TABLE `tbl_project_images` (
  `image_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `image_role` varchar(50) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_image` enum('available','pending','in_progress','finish','qa','completed') NOT NULL DEFAULT 'available',
  `assignment_id` int(11) DEFAULT NULL,
  `estimated_time` int(255) DEFAULT NULL,
  `redo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_project_images`
--

INSERT INTO `tbl_project_images` (`image_id`, `project_id`, `user_id`, `image_path`, `file_name`, `image_role`, `file_type`, `file_size`, `upload_date`, `status_image`, `assignment_id`, `estimated_time`, `redo`) VALUES
(589, 178, 0, 'images.jpg', NULL, 'Final', 'image/jpeg', 5374, '2025-05-21 00:21:09', 'completed', 319, 0, ''),
(590, 178, 0, 'pointing.png', NULL, 'Color Correction', 'image/png', 592377, '2025-05-21 00:21:09', '', NULL, 0, ''),
(591, 178, 0, '352778254_740761734721287_3557726020886904876_n.jpg', NULL, 'Retouch to Final', 'image/jpeg', 211312, '2025-05-21 00:22:17', 'completed', 319, 0, '1'),
(592, 178, 0, '356090891_744654457665348_9149358554064653126_n.jpg', NULL, '', 'image/jpeg', 581055, '2025-05-21 00:22:17', 'completed', 319, NULL, ''),
(593, 179, 0, '78e86477-4b88-431b-b733-b9aded082064.webp', NULL, 'Clipping Path', 'image/webp', 407458, '2025-05-21 02:09:17', '', 320, 0, '1'),
(594, 179, 0, '352778254_740761734721287_3557726020886904876_n.jpg', NULL, 'Retouch', 'image/jpeg', 211312, '2025-05-21 02:09:17', '', 322, 0, ''),
(595, 179, 0, '356090891_744654457665348_9149358554064653126_n.jpg', NULL, 'Color Correction', 'image/jpeg', 581055, '2025-05-21 02:09:17', '', 320, 0, ''),
(596, 179, 0, 'capcut.com_editor-graphic___action_from=my_draft&position=my_draft&from_page=work_space&enter_from=project&width=1080&height=1920&unit=px&space_id=7257438839772594178&space_type=2&raw_space_type=1&space_user_cnt=1&tab_nam.html', NULL, 'Retouch', 'text/html', 344677, '2025-05-21 02:09:17', '', 320, 0, ''),
(597, 179, 0, 'f6361q2buaib1.jpg', NULL, 'Final', 'image/jpeg', 21802, '2025-05-21 02:09:17', '', 322, NULL, ''),
(601, 180, 0, '78e86477-4b88-431b-b733-b9aded082064.webp', NULL, 'Color Correction', 'image/webp', 407458, '2025-05-21 02:11:03', 'completed', 321, 0, ''),
(602, 180, 0, '352778254_740761734721287_3557726020886904876_n.jpg', NULL, 'Clipping Path', 'image/jpeg', 211312, '2025-05-21 02:11:03', 'completed', 321, 0, ''),
(603, 180, 0, '356090891_744654457665348_9149358554064653126_n.jpg', NULL, 'Final', 'image/jpeg', 581055, '2025-05-21 02:11:03', 'completed', 321, 0, ''),
(610, 180, 0, '352778254_740761734721287_3557726020886904876_n.jpg', NULL, '', 'image/jpeg', 211312, '2025-05-21 03:15:07', '', 321, NULL, ''),
(611, 180, 0, '356090891_744654457665348_9149358554064653126_n.jpg', NULL, 'Retouch', 'image/jpeg', 581055, '2025-05-21 03:15:07', '', 323, NULL, ''),
(612, 180, 0, 'f6361q2buaib1.jpg', NULL, '', 'image/jpeg', 21802, '2025-05-21 03:15:07', 'available', NULL, NULL, ''),
(613, 180, 0, 'images.jpg', NULL, '', 'image/jpeg', 5374, '2025-05-21 03:15:07', 'available', NULL, NULL, ''),
(614, 180, 0, 'pointing.png', NULL, '', 'image/png', 592377, '2025-05-21 03:15:07', 'available', NULL, NULL, ''),
(615, 180, 0, 'Screenshot 2025-03-13 201759.png', NULL, '', 'image/png', 385868, '2025-05-21 03:15:07', 'available', NULL, NULL, '');

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
(25, 'art', 'a', 'art', '2025-04-28 00:00:00', '123', '123', '123@gmail.com', 'uploads/profile_pictures/profile_6818028213764.png', '0000-00-00 00:00:00', '2025-05-05 00:12:50', NULL, NULL),
(26, 'test', 't', 'test', '2025-04-27 00:00:00', '123', '123', '123311231@gmail.com', 'uploads/profile_pictures/profile_681802b05a2d5.png', '0000-00-00 00:00:00', '2025-05-05 00:13:36', NULL, NULL),
(28, 'd', 'd', 'd', '2025-04-27 00:00:00', 'd', '123', 'asdsad@gmail.com', 'uploads/profile_pictures/profile_681d56e35e38e.jpg', '0000-00-00 00:00:00', '2025-05-09 01:14:11', NULL, NULL),
(29, 'Peter', 'R', 'Repaso', '2025-04-27 00:00:00', '123', '123', 'repeaso@gmai.com', 'uploads/profile_pictures/profile_681d73d17ed85.jpg', '0000-00-00 00:00:00', '2025-05-09 03:17:37', NULL, NULL),
(30, 'Paulo', 'L', 'Abaquita', '2025-04-27 00:00:00', '123', '123', 'paulo@gmail.com', 'uploads/profile_pictures/profile_681d742b5fc87.jpg', '0000-00-00 00:00:00', '2025-05-09 03:19:07', NULL, NULL);

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
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_accounts`
--
ALTER TABLE `tbl_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `tbl_companies`
--
ALTER TABLE `tbl_companies`
  MODIFY `company_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_projects`
--
ALTER TABLE `tbl_projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `tbl_project_assignments`
--
ALTER TABLE `tbl_project_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT for table `tbl_project_images`
--
ALTER TABLE `tbl_project_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=616;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
