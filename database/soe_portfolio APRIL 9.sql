-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2026 at 06:05 AM
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
-- Database: `soe_portfolio`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_categories`
--

CREATE TABLE `academic_categories` (
  `category_id` int(10) UNSIGNED NOT NULL,
  `category_key` varchar(32) NOT NULL,
  `category_label` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_categories`
--

INSERT INTO `academic_categories` (`category_id`, `category_key`, `category_label`, `created_at`, `updated_at`) VALUES
(1, 'assessment', 'Assessments', '2026-03-24 03:33:06', '2026-03-24 03:33:06'),
(2, 'projects', 'Projects', '2026-03-24 03:33:06', '2026-03-24 03:33:06'),
(3, 'certificates', 'Certificates/Awards', '2026-03-24 03:33:06', '2026-03-24 03:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `password`, `id_number`) VALUES
(20, 130, 'Nelo', '', 'Angelo', '', 'neloangelo4123@gmail.com', '$2y$10$fmmlucZ59pVit3O3IIQcQeRp3cwwx8bCeUP9i8GqR/NU4MPUcf8YC', '202300001'),
(21, 137, 'Reimon Zaryl', 'Tarraya', 'Torio', '', 'reimonzaryltorio@gmail.com', '$2y$10$bY1ByfAMT5.NyxeBSSgkQuSe9g34YQlZT5TFqOtZpbgAZPRwtXFji', '202300000');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `term_number` enum('1','2','3') NOT NULL,
  `start_year` year(4) NOT NULL,
  `end_year` year(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deadline_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `course_id`, `class_name`, `term_number`, `start_year`, `end_year`, `created_at`, `updated_at`, `deadline_at`) VALUES
(1, 16, 'ROBROA CpE-231', '1', '2024', '2025', '2025-05-15 08:16:27', '2025-05-15 08:16:27', NULL),
(2, 11, 'Programming Logic and Design CPE-231', '1', '2023', '2025', '2025-05-15 08:19:38', '2025-05-15 08:19:38', NULL),
(3, 21, 'Object Programming', '2', '2023', '2024', '2025-05-15 08:31:36', '2025-05-15 08:31:36', NULL),
(4, 16, 'ROBPROA CpE-241', '3', '2024', '2025', '2025-05-15 10:00:45', '2025-05-15 10:00:45', NULL),
(6, 17, 'test something', '2', '2010', '2011', '2025-05-16 02:28:39', '2025-05-16 02:28:39', NULL),
(7, 18, 'test somethign 1', '1', '2010', '2011', '2025-05-16 02:30:30', '2025-05-16 02:30:30', NULL),
(8, 20, 'test 3', '3', '2024', '2025', '2025-05-21 07:33:41', '2025-05-21 07:33:41', NULL),
(11, 37, 'Object Programming', '2', '2023', '2024', '2025-05-29 00:30:20', '2025-05-30 00:31:53', '2025-05-30 08:31:00'),
(15, 43, 'PROGLOD  CpE-231 Term 1 2023-2024', '1', '2023', '2024', '2025-10-14 07:24:03', '2025-11-10 13:00:55', NULL),
(20, 49, 'Logic Circuit and Design Laboratory LOGCLB CpE-231 Term 1 2024-2025', '1', '2024', '2025', '2025-11-03 07:29:27', '2025-11-03 07:30:08', NULL),
(23, 44, 'CpE-231', '2', '2023', '2024', '2025-11-08 14:40:31', '2025-11-08 14:40:31', NULL),
(24, 47, 'LOGCDES CpE-231 Term 1 2024-2025', '1', '2024', '2025', '2025-11-08 15:14:12', '2025-11-08 15:18:45', NULL),
(27, 54, 'PROGLOD CpE-231 Term 1 2023-2024', '1', '2023', '2024', '2025-11-11 03:59:56', '2025-11-11 03:59:56', NULL),
(28, 54, 'PROGLOD CpE-241 Term 1 2024-2025', '1', '2024', '2025', '2025-11-14 07:35:17', '2025-11-14 07:35:17', NULL),
(29, 54, 'PROGLOD CpE-251 Term 1 2025-2026', '1', '2025', '2026', '2026-03-13 00:03:54', '2026-03-13 00:03:54', NULL),
(30, 54, 'PROGLOD CpE-261 Term 1 2026-2027', '1', '2026', '2027', '2026-04-06 01:45:07', '2026-04-06 01:45:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_outputs`
--

CREATE TABLE `class_outputs` (
  `output_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `output_name` varchar(255) NOT NULL,
  `total_score` int(11) NOT NULL,
  `required_file_format` varchar(20) NOT NULL DEFAULT '.pdf',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_outputs`
--

INSERT INTO `class_outputs` (`output_id`, `class_id`, `output_name`, `total_score`, `required_file_format`, `created_at`, `updated_at`) VALUES
(1, 11, 'Assessment 1', 50, '.pdf', '2025-05-30 04:50:57', '2025-05-30 04:50:57'),
(2, 11, 'Assessment 2', 50, '.pdf', '2025-05-30 04:54:11', '2025-05-30 05:06:53'),
(4, 11, 'assessment 3', 50, '.pdf', '2025-05-30 08:30:49', '2025-05-30 08:30:49'),
(5, 20, 'Seatwork 1', 20, '.pdf', '2025-11-03 15:32:20', '2025-11-03 15:32:20'),
(7, 24, 'Seatwork 1', 25, '.pdf', '2025-11-14 15:37:37', '2025-11-14 15:37:37'),
(8, 23, 'Laboratory Exercise 1', 50, '.pdf', '2026-03-13 08:44:18', '2026-03-13 08:44:18'),
(9, 23, 'Sample Output Name', 50, '.pdf', '2026-03-30 06:55:06', '2026-03-30 06:55:06'),
(10, 23, 'Sample Draft', 100, '.docx', '2026-03-30 09:36:55', '2026-03-30 09:36:55'),
(11, 23, 'Sample Ulet', 100, '.pdf', '2026-03-31 08:57:55', '2026-04-01 04:46:03'),
(12, 23, 'Laboratory Exercise 2', 50, '.pdf', '2026-04-06 09:46:53', '2026-04-06 09:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `program_id`, `course_name`, `course_code`) VALUES
(30, 3, 'hatdgo', NULL),
(31, 3, 'sd', NULL),
(32, 3, 'sasdasd', NULL),
(33, 3, 'test', NULL),
(35, 3, 'Hatdog', NULL),
(36, 3, 'har', NULL),
(38, 40, 'test', NULL),
(40, 40, 'hatdog', NULL),
(44, 1, 'Object Oriented Programming', 'OBJPROG'),
(47, 1, 'Logic Circuit and Design Lecture', 'LOGCDES'),
(54, 1, 'Programming Logic and Design', 'PROGLOD'),
(55, 2, 'har', 'HAR');

-- --------------------------------------------------------

--
-- Table structure for table `extracurricular_portfolios`
--

CREATE TABLE `extracurricular_portfolios` (
  `portfolio_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `portfolio_key` varchar(64) NOT NULL,
  `title` varchar(120) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `extracurricular_portfolios`
--

INSERT INTO `extracurricular_portfolios` (`portfolio_id`, `student_id`, `portfolio_key`, `title`, `sort_order`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 120, 'projects', 'Top projects', 1, 1, '2026-03-31 00:08:21', '2026-03-31 00:08:21'),
(2, 120, 'certificates', 'Top certificates/awards', 2, 1, '2026-03-31 00:08:21', '2026-03-31 00:08:21'),
(3, 120, 'assessments', 'Top assessments', 3, 1, '2026-03-31 00:08:21', '2026-03-31 00:08:21'),
(4, 120, 'sample-top-portfolio-1', 'Sample Top Portfolio 1', 4, 0, '2026-03-31 00:08:52', '2026-03-31 00:08:52'),
(5, 120, 'sample-extra-curricular-portfolio', 'Sample Extracurricular Portfolio', 5, 0, '2026-03-31 00:10:10', '2026-03-31 00:10:37'),
(6, 39, 'projects', 'Top projects', 1, 1, '2026-03-31 00:41:00', '2026-03-31 00:41:00'),
(7, 39, 'certificates', 'Top certificates/awards', 2, 1, '2026-03-31 00:41:00', '2026-03-31 00:41:00'),
(8, 39, 'assessments', 'Top assessments', 3, 1, '2026-03-31 00:41:00', '2026-03-31 00:41:00'),
(10, 39, 'sample-portfolio-2', 'Sample Portfolio 2', 5, 0, '2026-03-31 01:02:33', '2026-03-31 01:02:33');

-- --------------------------------------------------------

--
-- Table structure for table `extracurricular_portfolio_files`
--

CREATE TABLE `extracurricular_portfolio_files` (
  `portfolio_id` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `extracurricular_portfolio_files`
--

INSERT INTO `extracurricular_portfolio_files` (`portfolio_id`, `file_id`, `created_at`) VALUES
(8, 13, '2026-04-06 05:25:12'),
(8, 15, '2026-04-06 05:25:12'),
(10, 13, '2026-03-31 01:12:11'),
(10, 14, '2026-03-31 01:12:11'),
(10, 15, '2026-03-31 01:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `id_number` varchar(9) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `faculty_role` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `id_number`, `program_id`, `email`, `faculty_role`, `password`) VALUES
(37, 135, 'Eiyuu', '', 'Angelo', '', '202400001', 1, 'eiyuuangelo@gmail.com', 'executive director', '$2y$10$cIXoJQv4cUNO0Vjpk.OYDeitLTRars1VhOZIOe5EtCtW1A.J0QzQK'),
(38, 136, 'Hero', '', 'Joestar', '', '202400002', 1, 'herojoestar@gmail.com', 'program director', '$2y$10$HrmWzfauOU77.mV4xw6NWu9SUbZWeexOae.X9RrEgHynGY9H9xnqa');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `folder_id` bigint(20) UNSIGNED DEFAULT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_name` varchar(255) NOT NULL,
  `file_path` varchar(1024) NOT NULL,
  `mime_type` varchar(127) NOT NULL DEFAULT 'application/octet-stream',
  `file_size` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`file_id`, `student_id`, `category_id`, `folder_id`, `original_file_name`, `stored_file_name`, `file_path`, `mime_type`, `file_size`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'Torio Load Analysis.pdf', 'pf_69c27bfb65db23.40656464.pdf', 'uploads/portfolio/student_1/assessment/pf_69c27bfb65db23.40656464.pdf', 'application/pdf', 182916, '2026-03-24 11:56:43', '2026-03-24 11:56:43'),
(2, 1, 1, 1, 'ASS2a_Torio, Reimon.pdf', 'pf_69c27c2f22afd2.30104209.pdf', 'uploads/portfolio/student_1/assessment/pf_69c27c2f22afd2.30104209.pdf', 'application/pdf', 28139, '2026-03-24 11:57:35', '2026-03-24 11:57:35'),
(3, 1, 1, NULL, 'folder raw', 'virtual_69c5cfc28af969.62543796', 'uploads/portfolio/virtual/virtual_69c5cfc28af969.62543796', 'application/octet-stream', 0, '2026-03-27 00:30:58', '2026-03-27 00:30:58'),
(4, 1, 1, NULL, 'what', 'virtual_69c5cfff249a20.53235759', 'uploads/portfolio/virtual/virtual_69c5cfff249a20.53235759', 'application/octet-stream', 0, '2026-03-27 00:31:59', '2026-03-27 00:31:59'),
(5, 1, 1, NULL, 'Midterm Healths.xlsx', 'pf_69c5d078d5d5e5.94512970.xlsx', 'uploads/portfolio/student_1/assessment/pf_69c5d078d5d5e5.94512970.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 13887, '2026-03-27 00:34:00', '2026-03-27 00:34:00'),
(6, 1, 1, NULL, 'invoice_INV-63074.pdf', 'pf_69c997bde8b558.67577337.pdf', '', 'application/pdf', 373476, '2026-03-29 21:21:01', '2026-03-29 21:21:01'),
(7, 1, 1, NULL, 'raspberry cheatsheet.txt', 'pf_69c998301855f4.63650906.txt', '', 'text/plain', 4008, '2026-03-29 21:22:56', '2026-03-29 21:22:56'),
(8, 1, 1, NULL, 'invoice_INV-63074.pdf', 'pf_69c99ddaf3c703.53459693.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_1\\assessment\\pf_69c99ddaf3c703.53459693.pdf', 'application/pdf', 373476, '2026-03-29 21:47:07', '2026-03-29 21:47:07'),
(9, 1, 1, NULL, 'raspberry cheatsheet.txt', 'pf_69c99ea5e971c1.47858896.txt', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_1\\assessment\\pf_69c99ea5e971c1.47858896.txt', 'text/plain', 4008, '2026-03-29 21:50:29', '2026-03-29 21:50:29'),
(10, 1, 1, NULL, 'Midterm Healths.xlsx', 'pf_69c9a2f0750247.13628425.xlsx', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_1\\assessment\\pf_69c9a2f0750247.13628425.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 13887, '2026-03-29 22:08:48', '2026-03-29 22:08:48'),
(11, 1, 1, NULL, 'IoT Based Earthquake Evacuation System March 28 another updated BACK UP.docx', 'pf_69c9d259c7c6f8.49838896.docx', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_1\\assessment\\pf_69c9d259c7c6f8.49838896.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 11001035, '2026-03-30 01:31:05', '2026-03-30 01:31:05'),
(12, 1, 1, NULL, 'ASS2b_Torio, Reimon.pdf', 'pf_69ca0f90d42500.60691002.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_1\\assessment\\pf_69ca0f90d42500.60691002.pdf', 'application/pdf', 150397, '2026-03-30 05:52:16', '2026-03-30 05:52:16'),
(13, 39, 1, NULL, 'invoice_INV-63074.pdf', 'pf_69cb186647f1c8.88319605.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\assessment\\pf_69cb186647f1c8.88319605.pdf', 'application/pdf', 373476, '2026-03-31 00:42:14', '2026-03-31 00:42:14'),
(14, 39, 1, NULL, 'sample_img.jpg', 'pf_69cb1941d0b245.33944838.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\assessment\\pf_69cb1941d0b245.33944838.jpg', 'image/jpeg', 5868, '2026-03-31 00:45:53', '2026-03-31 00:45:53'),
(15, 39, 1, NULL, 'RADIATIONstudcopy (1).pdf', 'pf_69cb1cbb348281.91212399.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\assessment\\pf_69cb1cbb348281.91212399.pdf', 'application/pdf', 2105548, '2026-03-31 01:00:43', '2026-03-31 01:00:43');

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `folder_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`folder_id`, `student_id`, `category_id`, `folder_name`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Sample Folder', '2026-03-24 03:38:48', '2026-03-24 03:38:48'),
(2, 1, 2, 'Sample folder 2', '2026-03-24 03:40:06', '2026-03-24 03:40:06'),
(3, 1, 3, 'Sample Folder 3', '2026-03-24 11:52:28', '2026-03-24 11:52:28'),
(4, 1, 1, 'sample folder nested', '2026-03-27 00:21:25', '2026-03-27 00:21:25'),
(5, 1, 1, 'folder real', '2026-03-27 00:31:17', '2026-03-27 00:31:17'),
(6, 1, 2, 'Folder ko sa projects', '2026-03-27 00:33:15', '2026-03-27 00:33:15'),
(7, 1, 1, 'folder ko sa assesments', '2026-03-27 00:35:20', '2026-03-27 00:35:20'),
(8, 39, 1, 'Sample 3', '2026-04-06 05:20:26', '2026-04-06 05:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-06 19:51:42'),
(2, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-06 19:52:21'),
(3, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:04:54'),
(4, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:04:58'),
(5, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:00'),
(6, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:19'),
(7, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:20'),
(8, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:20'),
(9, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:20'),
(10, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:21'),
(11, 130, 'Hello Nelo Angelo, you have been approved by the owner of the system.', 1, '2026-04-06 20:05:22'),
(12, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-06 20:07:07'),
(13, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-06 20:27:26'),
(14, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-06 20:33:37'),
(15, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-06 20:40:39'),
(16, 132, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-07 01:03:40'),
(17, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-07 01:05:01'),
(18, 132, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 01:31:57'),
(19, 132, 'Hello Eiyuu Angelo, you have been approved by the owner of the system.', 1, '2026-04-09 01:41:33'),
(20, 132, 'Hello Eiyuu Angelo, you have been approved by the owner of the system.', 1, '2026-04-09 02:19:50'),
(21, 132, 'Hello Eiyuu Angelo, you have been approved by the owner of the system.', 1, '2026-04-09 02:25:30'),
(22, 132, 'Hello Eiyuu Angelo, you have been approved by the owner of the system.', 1, '2026-04-09 02:25:46'),
(23, 132, 'Hello Eiyuu Angelo, your account is currently set to pending approval.', 1, '2026-04-09 03:02:53'),
(24, 133, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 03:07:59'),
(25, 133, 'Hello Eiyuu Angelo, you have been approved by the owner of the system.', 1, '2026-04-09 03:08:48'),
(26, 133, 'Hello Eiyuu Angelo, your account is currently set to pending approval.', 0, '2026-04-09 03:09:57'),
(27, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 03:40:01'),
(28, 135, 'Your account has been approved as Executive Director by the admin.', 1, '2026-04-09 03:40:23'),
(29, 135, 'Your account access as Executive Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 03:41:01'),
(30, 135, 'Access revoked: Eiyuu Angelo (Executive Director) is now set to Not Verified.', 1, '2026-04-09 03:41:01'),
(31, 130, 'Access revoked: Eiyuu Angelo (Executive Director) is now set to Not Verified.', 0, '2026-04-09 03:41:01'),
(32, 135, 'Your account has been approved as Executive Director by the admin.', 1, '2026-04-09 03:43:49'),
(33, 136, 'Welcome Hero Joestar, you have officially logged in the system.', 0, '2026-04-09 03:47:51'),
(34, 136, 'Your account has been approved as Program Director by the admin.', 0, '2026-04-09 03:48:49'),
(35, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-04-09 04:00:19'),
(36, 137, 'Your account has been approved as Admin by the admin.', 0, '2026-04-09 04:00:53');

-- --------------------------------------------------------

--
-- Table structure for table `output_submissions`
--

CREATE TABLE `output_submissions` (
  `submission_id` int(11) NOT NULL,
  `output_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_score` decimal(7,2) DEFAULT NULL,
  `submitted_file_name` varchar(255) DEFAULT NULL,
  `submitted_file_path` text DEFAULT NULL,
  `submitted_file_mime` varchar(120) DEFAULT NULL,
  `submitted_file_size` bigint(20) DEFAULT NULL,
  `professor_score` decimal(7,2) DEFAULT NULL,
  `status` enum('draft','submitted','undone','graded') NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `undone_at` datetime DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `output_submissions`
--

INSERT INTO `output_submissions` (`submission_id`, `output_id`, `student_id`, `student_score`, `submitted_file_name`, `submitted_file_path`, `submitted_file_mime`, `submitted_file_size`, `professor_score`, `status`, `submitted_at`, `undone_at`, `graded_at`, `created_at`, `updated_at`) VALUES
(8, 11, 39, 10.00, 'Job Interview Rubrics.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_11_39_aaa26b5f7d9f.pdf', 'application/pdf', 216813, NULL, 'submitted', '2026-04-01 04:44:10', NULL, NULL, '2026-04-01 04:44:10', '2026-04-01 04:44:10'),
(9, 12, 39, 50.00, 'Rei Activity 1 Softlab.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_12_39_6f762310155b.pdf', 'application/pdf', 184543, NULL, 'submitted', '2026-04-06 13:14:17', NULL, NULL, '2026-04-06 13:14:17', '2026-04-06 13:14:17');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(10) NOT NULL,
  `program_name` varchar(300) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`) VALUES
(1, 'Computer Engineering'),
(2, 'Civil Engineering'),
(3, 'Electronics Engineering'),
(35, 'Mechanical Engineering'),
(36, 'Software Engineering'),
(40, 'HARHARharharhar12345'),
(46, 'harhar');

-- --------------------------------------------------------

--
-- Table structure for table `requirements`
--

CREATE TABLE `requirements` (
  `requirement_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `requirement_desc` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requirements`
--

INSERT INTO `requirements` (`requirement_id`, `class_id`, `requirement_desc`, `created_at`, `updated_at`) VALUES
(1, 11, 'submit all requrements', '2025-05-29 08:58:09', '2025-05-30 08:30:15'),
(6, 20, 'magsubmit kayo by the end of term', '2025-11-03 07:34:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `year_of_enrollment` year(4) DEFAULT NULL,
  `id_number` varchar(10) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `year_of_enrollment`, `id_number`, `program_id`, `email`, `password`) VALUES
(39, 120, 'Reimon Zaryl', 'Tarraya', 'Torio', '', '2023', '2023140265', 1, 'rttorio@student.apc.edu.ph', '$2y$10$2sqBSx9kyvRPbrCRtquFwO.om5se1u8.SsW5KF81HeHXhRWRSHoE2'),
(45, 127, 'Kharljasper', 'Mistar', 'Baclagan', '', '2023', '2023140160', 1, 'kmbaclagan@student.apc.edu.ph', '$2y$10$q6IjxkAe8N.1fUC0yRAcHOmVsBHlflky3TAhD2yGHgzt10P7jOWnW');

-- --------------------------------------------------------

--
-- Table structure for table `student_homepage_profiles`
--

CREATE TABLE `student_homepage_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `bio` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_homepage_profiles`
--

INSERT INTO `student_homepage_profiles` (`profile_id`, `student_id`, `display_name`, `bio`, `created_at`, `updated_at`) VALUES
(1, 120, 'Reimon Zaryl Torio', 'Helo Ebriwan', '2026-03-31 00:11:26', '2026-03-31 00:11:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role_type` enum('student','faculty','admin') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role_type`, `created_at`, `status`, `is_verified`) VALUES
(120, 'Reimon Zaryl', 'Torio', 'rttorio@student.apc.edu.ph', '$2y$10$WLWUB.7T2uQiF1Bmm2sf8OSlMos4rr79U/am50KaLfSr1JazcE.W6', 'student', '2025-10-08 02:24:32', 1, 1),
(127, 'Kharljasper', 'Baclagan', 'kmbaclagan@student.apc.edu.ph', '$2y$10$q6IjxkAe8N.1fUC0yRAcHOmVsBHlflky3TAhD2yGHgzt10P7jOWnW', 'student', '2025-11-03 06:22:08', 1, 1),
(130, 'Nelo', 'Angelo', 'neloangelo4123@gmail.com', '$2y$10$m.xo01H8HigWvFoquVFVTubf/t2uLR4t/sjcYtXwHtNErxUpkl4kK', 'admin', '2026-04-05 11:02:51', 1, 1),
(135, 'Eiyuu', 'Angelo', 'eiyuuangelo@gmail.com', '$2y$10$cIXoJQv4cUNO0Vjpk.OYDeitLTRars1VhOZIOe5EtCtW1A.J0QzQK', 'faculty', '2026-04-09 03:39:30', 1, 1),
(136, 'Hero', 'Joestar', 'herojoestar@gmail.com', '$2y$10$HrmWzfauOU77.mV4xw6NWu9SUbZWeexOae.X9RrEgHynGY9H9xnqa', 'faculty', '2026-04-09 03:46:52', 1, 1),
(137, 'Reimon Zaryl', 'Torio', 'reimonzaryltorio@gmail.com', '$2y$10$bY1ByfAMT5.NyxeBSSgkQuSe9g34YQlZT5TFqOtZpbgAZPRwtXFji', 'admin', '2026-04-09 03:58:25', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_categories`
--
ALTER TABLE `academic_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `uq_portfolio_categories_key` (`category_key`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD KEY `fk_admin_user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `class_outputs`
--
ALTER TABLE `class_outputs`
  ADD PRIMARY KEY (`output_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `extracurricular_portfolios`
--
ALTER TABLE `extracurricular_portfolios`
  ADD PRIMARY KEY (`portfolio_id`),
  ADD UNIQUE KEY `uq_extracurricular_per_student_key` (`student_id`,`portfolio_key`),
  ADD KEY `idx_extracurricular_student_sort` (`student_id`,`sort_order`);

--
-- Indexes for table `extracurricular_portfolio_files`
--
ALTER TABLE `extracurricular_portfolio_files`
  ADD PRIMARY KEY (`portfolio_id`,`file_id`),
  ADD KEY `idx_epf_file` (`file_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `fk_faculty_user_id` (`user_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`file_id`),
  ADD KEY `idx_portfolio_files_student_category` (`student_id`,`category_id`),
  ADD KEY `idx_portfolio_files_folder` (`folder_id`),
  ADD KEY `fk_portfolio_files_category` (`category_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`folder_id`),
  ADD UNIQUE KEY `uq_portfolio_folder_name_per_scope` (`student_id`,`category_id`,`folder_name`),
  ADD KEY `idx_portfolio_folders_student_category` (`student_id`,`category_id`),
  ADD KEY `fk_portfolio_folders_category` (`category_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `output_submissions`
--
ALTER TABLE `output_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD UNIQUE KEY `ux_output_student` (`output_id`,`student_id`),
  ADD UNIQUE KEY `uniq_output_student` (`output_id`,`student_id`),
  ADD KEY `idx_output` (`output_id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `requirements`
--
ALTER TABLE `requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `fk_student_user_id` (`user_id`);

--
-- Indexes for table `student_homepage_profiles`
--
ALTER TABLE `student_homepage_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `uq_homepage_profile_student` (`student_id`),
  ADD KEY `idx_homepage_profile_student` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_categories`
--
ALTER TABLE `academic_categories`
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `class_outputs`
--
ALTER TABLE `class_outputs`
  MODIFY `output_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `extracurricular_portfolios`
--
ALTER TABLE `extracurricular_portfolios`
  MODIFY `portfolio_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `folder_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `output_submissions`
--
ALTER TABLE `output_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `student_homepage_profiles`
--
ALTER TABLE `student_homepage_profiles`
  MODIFY `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_admin_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_outputs`
--
ALTER TABLE `class_outputs`
  ADD CONSTRAINT `class_outputs_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE;

--
-- Constraints for table `extracurricular_portfolio_files`
--
ALTER TABLE `extracurricular_portfolio_files`
  ADD CONSTRAINT `fk_epf_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_epf_portfolio` FOREIGN KEY (`portfolio_id`) REFERENCES `extracurricular_portfolios` (`portfolio_id`) ON DELETE CASCADE;

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `faculty_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_faculty_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `fk_portfolio_files_category` FOREIGN KEY (`category_id`) REFERENCES `academic_categories` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_portfolio_files_folder` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`folder_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `fk_portfolio_folders_category` FOREIGN KEY (`category_id`) REFERENCES `academic_categories` (`category_id`) ON UPDATE CASCADE;

--
-- Constraints for table `output_submissions`
--
ALTER TABLE `output_submissions`
  ADD CONSTRAINT `fk_os_output` FOREIGN KEY (`output_id`) REFERENCES `class_outputs` (`output_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_os_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `requirements`
--
ALTER TABLE `requirements`
  ADD CONSTRAINT `requirements_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
