-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 01, 2026 at 08:36 AM
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
(3, 'certificates', 'Certificates/Awards', '2026-03-24 03:33:06', '2026-03-24 03:33:06'),
(4, 'other_files', 'External Files', '2026-04-17 22:31:01', '2026-04-17 22:52:24');

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
(30, 54, 'PROGLOD CpE-261 Term 1 2026-2027', '1', '2026', '2027', '2026-04-06 01:45:07', '2026-04-06 01:45:07', NULL),
(31, 57, 'DATCOMS CPE-231 Term 3 2025-2026', '3', '2025', '2026', '2026-04-09 12:36:43', '2026-04-09 12:36:43', NULL),
(33, 44, 'OBJPROG CPE-200 Term 1 2010-2011', '1', '2010', '2011', '2026-04-14 06:15:32', '2026-04-14 06:15:32', NULL),
(34, 56, 'SAMPCOR SAMPLE SECTION Term 1 2010-2011', '1', '2010', '2011', '2026-04-14 06:33:19', '2026-04-14 06:33:19', NULL),
(36, 58, 'CALCONE CpE-231/CE-231 Term 1 2023-2024', '1', '2023', '2024', '2026-04-14 07:24:19', '2026-04-14 07:24:19', NULL),
(37, 60, 'COMAROR CpE-231 Term 3 2025-2026', '3', '2025', '2026', '2026-04-24 00:29:31', '2026-04-24 00:29:31', NULL),
(38, 61, 'COMNETS CpE-231 Term 3 2025-2026', '3', '2025', '2026', '2026-05-13 02:48:11', '2026-05-13 02:48:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class_creation_requests`
--

CREATE TABLE `class_creation_requests` (
  `request_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `term_number` tinyint(1) NOT NULL,
  `start_year` year(4) NOT NULL,
  `end_year` year(4) NOT NULL,
  `requested_by_user_id` int(11) NOT NULL,
  `program_director_user_id` int(11) NOT NULL,
  `request_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `approved_class_id` int(11) DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_creation_requests`
--

INSERT INTO `class_creation_requests` (`request_id`, `course_id`, `program_id`, `class_name`, `term_number`, `start_year`, `end_year`, `requested_by_user_id`, `program_director_user_id`, `request_status`, `reviewed_by_user_id`, `reviewed_at`, `approved_class_id`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 44, 1, 'OBJPROG CPE-200 Term 1 2010-2011', 1, '2010', '2011', 141, 130, 'approved', 130, '2026-04-14 14:15:32', 33, NULL, '2026-04-14 06:11:23', '2026-04-14 06:15:32'),
(2, 56, 2, 'SAMPCOR Sample Class Term 1 2010-2011', 1, '2010', '2011', 141, 141, 'rejected', 141, '2026-04-14 14:23:16', NULL, '', '2026-04-14 06:18:37', '2026-04-14 06:23:16'),
(3, 56, 2, 'SAMPCOR SAMPLE SECTION Term 1 2010-2011', 1, '2010', '2011', 141, 141, 'rejected', 141, '2026-04-14 14:33:19', 34, 'wrong assignment of professor', '2026-04-14 06:31:14', '2026-04-14 06:33:19'),
(5, 58, 1, 'CALCONE CpE-231/CE-231 Term 1 2023-2024', 1, '2023', '2024', 141, 130, 'approved', 130, '2026-04-14 15:24:19', 36, NULL, '2026-04-14 07:04:27', '2026-04-14 07:24:19');

-- --------------------------------------------------------

--
-- Table structure for table `class_difficulty_ratings`
--

CREATE TABLE `class_difficulty_ratings` (
  `rating_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `difficulty_rating` enum('easy','normal','hard') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_difficulty_ratings`
--

INSERT INTO `class_difficulty_ratings` (`rating_id`, `class_id`, `student_id`, `difficulty_rating`, `created_at`, `updated_at`) VALUES
(1, 31, 39, 'hard', '2026-04-19 15:46:11', '2026-04-19 15:46:13'),
(3, 38, 39, 'hard', '2026-05-13 10:58:51', '2026-05-13 11:04:09'),
(11, 33, 39, 'hard', '2026-05-18 00:07:33', '2026-05-18 00:07:33');

-- --------------------------------------------------------

--
-- Table structure for table `class_invite_links`
--

CREATE TABLE `class_invite_links` (
  `invite_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_by_user_id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_invite_links`
--

INSERT INTO `class_invite_links` (`invite_id`, `class_id`, `token`, `created_by_user_id`, `is_active`, `created_at`, `expires_at`, `last_used_at`, `used_count`) VALUES
(1, 36, 'f5dc6c62c6e727df9cc704ede4650e13', 141, 1, '2026-05-29 02:15:43', '2026-06-12 10:15:43', NULL, 0);

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
(12, 23, 'Laboratory Exercise 2', 50, '.pdf', '2026-04-06 09:46:53', '2026-04-06 09:46:53'),
(13, 31, 'Quiz 1', 50, '.png/.jpg', '2026-04-17 08:07:16', '2026-04-17 08:07:16'),
(14, 31, 'Quiz 2', 50, '.png/.jpg', '2026-04-17 08:07:34', '2026-04-17 08:07:34'),
(15, 31, 'Quiz 3', 50, '.png/.jpg', '2026-04-17 08:07:50', '2026-04-17 08:07:50'),
(16, 31, 'Midterms', 50, '.png/.jpg', '2026-04-17 08:08:08', '2026-04-17 08:08:08'),
(17, 38, 'Output 1', 50, '.png/.jpg', '2026-05-13 10:55:58', '2026-05-13 10:55:58'),
(18, 38, 'Output 2', 50, '.png/.jpg', '2026-05-13 10:56:09', '2026-05-13 10:56:09'),
(19, 38, 'Output 3', 50, '.docx', '2026-05-13 10:56:27', '2026-05-13 10:56:27'),
(20, 33, 'HAR', 50, '.docx', '2026-05-17 21:05:13', '2026-05-17 21:05:13'),
(21, 33, 'Sample Output again ito', 50, '.png/.jpg', '2026-05-17 23:05:24', '2026-05-17 23:05:24'),
(22, 24, 'Seatwork 2', 25, '.png/.jpg', '2026-05-19 00:26:49', '2026-05-19 00:26:49'),
(23, 24, 'Seatwork 3', 25, '.png/.jpg', '2026-05-19 00:27:21', '2026-05-19 00:27:21');

-- --------------------------------------------------------

--
-- Table structure for table `class_portfolio_reviews`
--

CREATE TABLE `class_portfolio_reviews` (
  `review_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `final_grade` decimal(3,1) NOT NULL,
  `final_percentage` decimal(5,2) NOT NULL,
  `reviewed_by_user_id` int(11) NOT NULL,
  `reviewed_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_portfolio_reviews`
--

INSERT INTO `class_portfolio_reviews` (`review_id`, `class_id`, `student_id`, `decision`, `final_grade`, `final_percentage`, `reviewed_by_user_id`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 31, 39, 'approved', 3.0, 90.00, 141, '2026-04-19 15:01:02', '2026-04-19 13:02:57', '2026-04-19 15:01:02'),
(4, 38, 39, 'approved', 4.0, 90.00, 141, '2026-05-17 20:59:04', '2026-05-13 11:07:49', '2026-05-17 20:59:04');

-- --------------------------------------------------------

--
-- Table structure for table `class_portfolio_submissions`
--

CREATE TABLE `class_portfolio_submissions` (
  `portfolio_submission_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('submitted','undone') NOT NULL DEFAULT 'submitted',
  `submitted_at` datetime DEFAULT NULL,
  `undone_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_portfolio_submissions`
--

INSERT INTO `class_portfolio_submissions` (`portfolio_submission_id`, `class_id`, `student_id`, `status`, `submitted_at`, `undone_at`, `created_at`, `updated_at`) VALUES
(1, 31, 39, 'submitted', '2026-04-19 14:44:44', NULL, '2026-04-19 10:46:51', '2026-04-19 14:44:44'),
(5, 38, 39, 'submitted', '2026-05-17 20:57:39', NULL, '2026-05-13 11:05:42', '2026-05-17 20:57:39'),
(10, 33, 39, 'undone', '2026-05-17 23:51:12', '2026-05-18 00:06:30', '2026-05-17 23:48:01', '2026-05-18 00:06:30'),
(12, 24, 39, 'submitted', '2026-05-19 00:43:13', NULL, '2026-05-19 00:43:13', '2026-05-19 00:43:13');

-- --------------------------------------------------------

--
-- Table structure for table `class_professor_assignments`
--

CREATE TABLE `class_professor_assignments` (
  `class_id` int(11) NOT NULL,
  `professor_user_id` int(11) NOT NULL,
  `assigned_by_user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_professor_assignments`
--

INSERT INTO `class_professor_assignments` (`class_id`, `professor_user_id`, `assigned_by_user_id`, `assigned_at`) VALUES
(24, 141, 130, '2026-05-29 02:14:50'),
(31, 141, 130, '2026-04-09 14:58:30'),
(33, 141, 130, '2026-04-14 06:15:32'),
(36, 141, 130, '2026-04-14 07:24:19'),
(37, 135, 130, '2026-04-24 00:29:58'),
(38, 141, 135, '2026-05-13 02:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `class_students`
--

CREATE TABLE `class_students` (
  `class_student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invitation_source` enum('requested','invited') NOT NULL DEFAULT 'requested',
  `status` enum('pending','approved','removed','rejected') NOT NULL DEFAULT 'pending',
  `invited_by_user_id` int(11) DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `invited_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_students`
--

INSERT INTO `class_students` (`class_student_id`, `class_id`, `student_id`, `invitation_source`, `status`, `invited_by_user_id`, `approved_by_user_id`, `requested_at`, `invited_at`, `approved_at`, `removed_at`, `created_at`, `updated_at`) VALUES
(1, 31, 39, 'requested', 'approved', 141, 141, '2026-04-18 05:33:53', NULL, '2026-04-18 05:35:14', NULL, '2026-04-17 08:53:13', '2026-04-18 05:35:14'),
(2, 31, 45, 'invited', 'removed', 141, 141, NULL, '2026-04-17 08:58:58', '2026-04-17 08:58:58', '2026-04-18 05:04:58', '2026-04-17 08:58:58', '2026-04-18 05:04:58'),
(6, 38, 47, 'invited', 'approved', 141, 141, NULL, '2026-05-13 10:52:01', '2026-05-13 10:52:01', NULL, '2026-05-13 10:52:01', '2026-05-13 10:52:01'),
(7, 38, 39, 'invited', 'approved', 141, 141, NULL, '2026-05-13 10:52:54', '2026-05-13 10:52:54', NULL, '2026-05-13 10:52:54', '2026-05-13 10:52:54'),
(8, 33, 39, 'invited', 'approved', 141, 141, NULL, '2026-05-17 21:03:27', '2026-05-17 21:03:27', NULL, '2026-05-17 21:03:27', '2026-05-17 21:03:27'),
(9, 24, 39, 'requested', 'approved', NULL, 141, '2026-05-19 00:28:06', NULL, '2026-05-19 00:29:11', NULL, '2026-05-19 00:28:06', '2026-05-19 00:29:11');

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
(44, 1, 'Object Oriented Programming', 'OBJPROG'),
(47, 1, 'Logic Circuit and Design Lecture', 'LOGCDES'),
(54, 1, 'Programming Logic and Design', 'PROGLOD'),
(55, 2, 'har', 'HAR'),
(56, 2, 'Sample Course', 'SAMPCOR'),
(57, 1, 'Data Communications', 'DATCOMS'),
(58, 1, 'Calculus 1', 'CALCONE'),
(59, 2, 'Calculus 1', 'CALCONE'),
(60, 1, 'Computer Architecture and Organiztion Lecture', 'COMAROR'),
(61, 1, 'Computer Networks and Security', 'COMNETS');

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
(10, 39, 'sample-portfolio-2', 'Sample Portfolio 2', 5, 0, '2026-03-31 01:02:33', '2026-03-31 01:02:33'),
(11, 39, 'other_files', 'Top external files', 6, 1, '2026-04-17 22:50:34', '2026-04-17 22:50:34'),
(12, 45, 'other_files', 'Top external files', 1, 1, '2026-04-17 22:50:34', '2026-04-17 22:50:34'),
(14, 47, 'projects', 'Top projects', 1, 1, '2026-04-20 00:59:05', '2026-04-20 00:59:05'),
(15, 47, 'certificates', 'Top certificates/awards', 2, 1, '2026-04-20 00:59:05', '2026-04-20 00:59:05'),
(16, 47, 'assessments', 'Top assessments', 3, 1, '2026-04-20 00:59:05', '2026-04-20 00:59:05'),
(17, 47, 'other_files', 'Top external files', 4, 1, '2026-04-20 00:59:05', '2026-04-20 00:59:05'),
(18, 48, 'projects', 'Top projects', 1, 1, '2026-04-20 01:03:40', '2026-04-20 01:03:40'),
(19, 48, 'certificates', 'Top certificates/awards', 2, 1, '2026-04-20 01:03:40', '2026-04-20 01:03:40'),
(20, 48, 'assessments', 'Top assessments', 3, 1, '2026-04-20 01:03:40', '2026-04-20 01:03:40'),
(21, 48, 'other_files', 'Top external files', 4, 1, '2026-04-20 01:03:40', '2026-04-20 01:03:40');

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
(37, 135, 'Eiyuu', '', 'Angelo', '', '202400001', 3, 'eiyuuangelo@gmail.com', 'executive director', '$2y$10$cIXoJQv4cUNO0Vjpk.OYDeitLTRars1VhOZIOe5EtCtW1A.J0QzQK'),
(39, 130, 'Nelo', '', 'Angelo', '', '202300001', 1, 'neloangelo4123@gmail.com', 'program director', '$2y$10$m.xo01H8HigWvFoquVFVTubf/t2uLR4t/sjcYtXwHtNErxUpkl4kK'),
(43, 141, 'Hero', '', 'Joestar', '', '202400002', 1, 'herojoestar@gmail.com', 'professor', '$2y$10$DjWBM3g6LUKe3wdpvxlJg.SJtXELlHmO5IzZQ8e9jDym9VMU7QXKi');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_profiles`
--

CREATE TABLE `faculty_profiles` (
  `profile_id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `feedback_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_role` varchar(64) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `screenshot_name` varchar(255) DEFAULT NULL,
  `status` enum('new','reviewed','resolved') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedbacks`
--

INSERT INTO `feedbacks` (`feedback_id`, `user_id`, `user_role`, `user_email`, `subject`, `message`, `screenshot_path`, `screenshot_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 141, 'professor', 'herojoestar@gmail.com', 'Test feedback 2', 'test feed back number 2', NULL, NULL, 'new', '2026-05-18 21:58:02', '2026-05-18 21:58:02'),
(2, 141, 'professor', 'herojoestar@gmail.com', 'Test feedback 2', 'test feed back number 2', NULL, NULL, 'new', '2026-05-18 21:58:08', '2026-05-18 21:58:08'),
(3, 120, 'student', 'rttorio@student.apc.edu.ph', 'test feedback 3', 'hello this is testing feedback 3', NULL, NULL, 'new', '2026-05-18 22:08:36', '2026-05-18 22:08:36');

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
(15, 39, 1, NULL, 'RADIATIONstudcopy (1).pdf', 'pf_69cb1cbb348281.91212399.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\assessment\\pf_69cb1cbb348281.91212399.pdf', 'application/pdf', 2105548, '2026-03-31 01:00:43', '2026-03-31 01:00:43'),
(16, 39, 4, NULL, 'sample_img.jpg', 'pf_69e2b503a41111.91396958.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\other_files\\pf_69e2b503a41111.91396958.jpg', 'image/jpeg', 5868, '2026-04-17 22:32:35', '2026-04-17 22:32:35'),
(17, 39, 2, NULL, 'Portfolio - Grading Rubric v2 1.xlsx', 'pf_6a1901e1dab224.48411707.xlsx', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_uploads\\student_39\\projects\\pf_6a1901e1dab224.48411707.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 118515, '2026-05-29 03:02:57', '2026-05-29 03:02:57');

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
(31, 130, 'Access revoked: Eiyuu Angelo (Executive Director) is now set to Not Verified.', 1, '2026-04-09 03:41:01'),
(32, 135, 'Your account has been approved as Executive Director by the admin.', 1, '2026-04-09 03:43:49'),
(33, 136, 'Welcome Hero Joestar, you have officially logged in the system.', 0, '2026-04-09 03:47:51'),
(34, 136, 'Your account has been approved as Program Director by the admin.', 0, '2026-04-09 03:48:49'),
(35, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 04:00:19'),
(36, 137, 'Your account has been approved as Admin by the admin.', 1, '2026-04-09 04:00:53'),
(37, 130, 'Your account access as Admin has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 04:36:59'),
(38, 137, 'Access revoked: Nelo Angelo (Admin) is now set to Not Verified.', 1, '2026-04-09 04:36:59'),
(39, 130, 'Access revoked: Nelo Angelo (Admin) is now set to Not Verified.', 1, '2026-04-09 04:36:59'),
(40, 130, 'Your account has been approved as Professor by the admin.', 1, '2026-04-09 04:37:26'),
(41, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 04:38:15'),
(42, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 05:22:16'),
(43, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 05:35:58'),
(44, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 05:44:41'),
(45, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 06:06:16'),
(46, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 06:19:05'),
(47, 139, 'Welcome Hero Joestar, you have officially logged in the system.', 0, '2026-04-09 06:31:35'),
(48, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-09 06:38:26'),
(49, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-09 06:38:36'),
(50, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 08:54:09'),
(51, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 08:55:18'),
(52, 130, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 08:56:59'),
(53, 135, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 08:56:59'),
(54, 137, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 08:56:59'),
(55, 130, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-09 08:57:10'),
(56, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 09:02:00'),
(57, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 09:03:12'),
(58, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 09:04:12'),
(59, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 09:08:26'),
(60, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-09 09:14:26'),
(61, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 09:18:08'),
(62, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 09:22:08'),
(63, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-09 09:24:03'),
(64, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 09:30:01'),
(65, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 09:41:20'),
(66, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 09:41:42'),
(67, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-09 09:42:10'),
(68, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 11:52:25'),
(69, 130, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 12:27:51'),
(70, 135, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 12:27:51'),
(71, 137, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 12:27:51'),
(72, 130, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-09 12:28:02'),
(73, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 12:31:12'),
(74, 130, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 12:54:04'),
(75, 130, 'Access revoked: Nelo Angelo (Program Director) is now set to Not Verified.', 1, '2026-04-09 12:54:04'),
(76, 137, 'Access revoked: Nelo Angelo (Program Director) is now set to Not Verified.', 1, '2026-04-09 12:54:04'),
(77, 130, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-09 12:54:33'),
(78, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 12:55:18'),
(79, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 12:58:54'),
(80, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 14:32:15'),
(81, 130, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-09 14:33:32'),
(82, 135, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 14:33:32'),
(83, 137, 'Access revoked: Nelo Angelo (Professor) is now set to Not Verified.', 1, '2026-04-09 14:33:32'),
(84, 130, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-09 14:33:41'),
(85, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 14:34:10'),
(86, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 14:41:36'),
(87, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 14:43:18'),
(88, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-09 14:58:51'),
(89, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-09 23:53:42'),
(90, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-09 23:54:36'),
(91, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-09 23:59:23'),
(92, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-11 07:47:49'),
(93, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-11 07:52:09'),
(94, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-11 07:54:38'),
(95, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-11 07:57:16'),
(96, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-11 08:11:44'),
(97, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-14 00:01:05'),
(98, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 00:02:16'),
(99, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-14 00:03:56'),
(100, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 00:04:19'),
(101, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 00:06:01'),
(102, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 00:38:51'),
(103, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-14 00:42:45'),
(104, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 00:43:03'),
(105, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 00:43:03'),
(106, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 00:46:28'),
(107, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 00:46:57'),
(108, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-14 00:47:22'),
(109, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 00:48:10'),
(110, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 04:48:18'),
(111, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 05:17:16'),
(112, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-14 05:48:35'),
(117, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:03:27'),
(118, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:03:27'),
(119, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 06:04:01'),
(120, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:06:41'),
(121, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:14:50'),
(122, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:15:02'),
(123, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:16:49'),
(124, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:17:14'),
(125, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:19:14'),
(126, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:19:56'),
(127, 130, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:19:56'),
(128, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:19:56'),
(129, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 06:20:00'),
(130, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:20:17'),
(131, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:20:58'),
(132, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:22:58'),
(133, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:23:39'),
(134, 141, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:23:39'),
(135, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:23:39'),
(136, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 06:23:43'),
(137, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:23:58'),
(138, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:26:20'),
(139, 141, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:26:20'),
(140, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:26:20'),
(141, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 06:26:43'),
(142, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:30:27'),
(143, 141, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:30:27'),
(144, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:30:27'),
(145, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 06:30:30'),
(146, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:32:04'),
(147, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:32:25'),
(148, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:32:50'),
(149, 141, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:32:50'),
(150, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:32:50'),
(151, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 06:32:54'),
(152, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:34:12'),
(153, 141, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:34:12'),
(154, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 06:34:12'),
(155, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 06:34:26'),
(156, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 06:41:19'),
(157, 141, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:41:19'),
(158, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 06:41:19'),
(159, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 06:41:21'),
(160, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:41:49'),
(161, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 06:42:12'),
(162, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 06:43:38'),
(163, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 07:02:33'),
(164, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:03:59'),
(165, 141, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:03:59'),
(166, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:03:59'),
(167, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 07:04:02'),
(168, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:05:30'),
(169, 141, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:05:30'),
(170, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:05:30'),
(171, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 07:05:35'),
(172, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 07:06:10'),
(173, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:07:07'),
(174, 130, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:07:07'),
(175, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:07:07'),
(176, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 07:07:11'),
(177, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 07:07:37'),
(178, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 07:08:51'),
(179, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:10:11'),
(180, 130, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:10:11'),
(181, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:10:11'),
(182, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 07:10:14'),
(183, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 07:10:26'),
(184, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:13:25'),
(185, 141, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:13:25'),
(186, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:13:25'),
(187, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 07:13:28'),
(188, 141, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:22:55'),
(189, 141, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:22:55'),
(190, 137, 'Access revoked: Hero Joestar (Professor) is now set to Not Verified.', 1, '2026-04-14 07:22:55'),
(191, 141, 'Your account has been approved as Program Director by the admin.', 1, '2026-04-14 07:23:02'),
(192, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-14 07:24:06'),
(193, 141, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-04-14 07:26:59'),
(194, 130, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:26:59'),
(195, 137, 'Access revoked: Hero Joestar (Program Director) is now set to Not Verified.', 1, '2026-04-14 07:26:59'),
(196, 141, 'Your account has been approved as Professor by the admin.', 1, '2026-04-14 07:27:45'),
(197, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-14 07:28:08'),
(198, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 00:23:29'),
(199, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 00:56:00'),
(200, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 16:46:26'),
(201, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 17:14:14'),
(202, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 17:19:21'),
(203, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 17:24:05'),
(204, 127, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 0, '2026-04-17 21:04:58'),
(205, 120, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:05:01'),
(206, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:05:41'),
(207, 120, 'You were invited to join class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:06:24'),
(208, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:07:08'),
(209, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:12:11'),
(210, 120, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:12:25'),
(211, 120, 'You were invited to join class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:13:23'),
(212, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:13:54'),
(213, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:17:51'),
(214, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:18:16'),
(215, 120, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:18:26'),
(216, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:18:47'),
(217, 141, 'Reimon Zaryl Torio requested to join your class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:19:35'),
(218, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:20:28'),
(219, 120, 'Your enrollment request for class DATCOMS CPE-231 Term 3 2025-2026 was approved.', 1, '2026-04-17 21:20:58'),
(220, 120, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:27:22'),
(221, 120, 'You were invited to join class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:27:39'),
(222, 120, 'You were removed from class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:27:49'),
(223, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:28:14'),
(224, 141, 'Reimon Zaryl Torio requested to join your class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:28:23'),
(225, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:30:32'),
(226, 120, 'Your enrollment request for class DATCOMS CPE-231 Term 3 2025-2026 was rejected.', 1, '2026-04-17 21:30:44'),
(227, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:32:23'),
(228, 141, 'Reimon Zaryl Torio requested to join your class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:32:55'),
(229, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:33:14'),
(230, 120, 'Your enrollment request for class DATCOMS CPE-231 Term 3 2025-2026 was rejected.', 1, '2026-04-17 21:33:25'),
(231, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 21:33:45'),
(232, 141, 'Reimon Zaryl Torio requested to join your class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-17 21:33:53'),
(233, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:34:32'),
(234, 120, 'Your enrollment request for class DATCOMS CPE-231 Term 3 2025-2026 was approved.', 1, '2026-04-17 21:35:14'),
(235, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-17 21:39:24'),
(236, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 22:00:20'),
(237, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-17 22:54:42'),
(238, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 00:27:36'),
(239, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 00:33:24'),
(240, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 01:01:05'),
(241, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 01:52:34'),
(242, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 01:58:52'),
(243, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 02:02:49'),
(244, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 02:04:42'),
(245, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 02:07:06'),
(246, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 02:19:16'),
(247, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 02:19:45'),
(248, 141, 'Reimon Zaryl Torio has submitted a portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 02:46:51'),
(249, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 02:47:29'),
(250, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 02:52:45'),
(251, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 02:52:53'),
(252, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 02:53:28'),
(253, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 03:28:56'),
(254, 141, 'Reimon Zaryl Torio has submitted a portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 03:29:15'),
(255, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 03:29:27'),
(256, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 04:37:59'),
(257, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 04:53:24'),
(258, 120, 'Professor Hero Joestar, has rejected your submitted portfolio by the following reasons:\n\"why are you sending images of speed....\" please thoroughly review what needs to be submited portfolio for your revision', 1, '2026-04-19 05:02:57'),
(259, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 05:15:10'),
(260, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 05:18:14'),
(261, 141, 'Reimon Zaryl Torio has submitted a portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 05:18:29'),
(262, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 05:18:41'),
(263, 120, 'Professor Hero Joestar, has already approved your submitted portfolio with the following details:\nFinal Percentage: 67%\nFinal Grade: R', 1, '2026-04-19 05:26:13'),
(264, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 06:43:50'),
(265, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 06:44:38'),
(266, 141, 'Reimon Zaryl Torio has submitted a portfolio in class DATCOMS CPE-231 Term 3 2025-2026.', 1, '2026-04-19 06:44:44'),
(267, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 06:44:53'),
(268, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 06:45:00'),
(269, 120, 'Professor Hero Joestar, has already approved your submitted portfolio with the following details:\nFinal Percentage: 90%\nFinal Grade: 3.0', 1, '2026-04-19 07:01:02'),
(270, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 07:36:18'),
(271, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 07:55:50'),
(272, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 13:46:49'),
(273, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 14:07:30'),
(274, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 15:06:00'),
(275, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 15:06:06'),
(276, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-19 15:06:14'),
(277, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 15:11:26'),
(278, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 15:44:29'),
(279, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 15:53:37'),
(280, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 23:45:31'),
(281, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-19 23:54:19'),
(282, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-19 23:59:41'),
(283, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-20 00:06:05'),
(284, 127, 'Welcome Kharljasper Baclagan, you have officially logged in the system.', 0, '2026-04-20 00:55:44'),
(285, 142, 'Welcome Ronin Abonita, you have officially logged in the system.', 0, '2026-04-20 00:59:05'),
(286, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-20 00:59:35'),
(287, 143, 'Welcome Denbert Veloria, you have officially logged in the system.', 0, '2026-04-20 01:03:40'),
(288, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-20 01:03:54'),
(289, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-20 20:52:03'),
(290, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-20 20:56:05'),
(291, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-20 20:59:09'),
(292, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-20 21:04:46'),
(293, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-20 21:07:27'),
(294, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-20 21:10:32'),
(295, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-23 20:54:55'),
(296, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-23 23:56:46'),
(297, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-24 00:02:23'),
(298, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-24 00:06:51'),
(299, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-24 00:25:54'),
(300, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-24 00:27:58'),
(301, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-24 00:30:25'),
(302, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-24 00:31:27'),
(303, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-04-24 00:32:21'),
(304, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-24 00:33:12'),
(305, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-24 00:34:43'),
(306, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-24 00:38:37'),
(307, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-24 00:40:54'),
(308, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-27 23:47:51'),
(309, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-27 23:57:52'),
(310, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-04-28 00:10:18'),
(311, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-28 00:20:01'),
(312, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-04-28 03:10:06'),
(313, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-04-29 01:05:43'),
(314, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-12 21:29:55'),
(315, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-12 21:34:56'),
(316, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-12 23:23:56'),
(317, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-12 23:24:48'),
(318, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-12 23:24:57'),
(319, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-13 01:02:44'),
(320, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 01:03:18'),
(321, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 01:04:02'),
(322, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 01:04:52'),
(323, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:00:00'),
(324, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:00:28'),
(325, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:00:51'),
(326, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 02:08:13'),
(327, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-13 02:24:56'),
(328, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:25:05'),
(329, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-13 02:27:30'),
(330, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 02:27:59'),
(331, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 02:28:16'),
(332, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:29:19'),
(333, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 02:31:15'),
(334, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:33:23'),
(335, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-13 02:35:37'),
(336, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 02:36:08'),
(337, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-13 02:50:31'),
(338, 142, 'You were invited to join class COMNETS CpE-231 Term 3 2025-2026.', 0, '2026-05-13 02:52:01'),
(339, 120, 'You were invited to join class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-13 02:52:54'),
(340, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:53:08'),
(341, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-13 02:55:07'),
(342, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 02:57:00'),
(343, 141, 'Reimon Zaryl Torio has submitted a portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-13 03:05:42'),
(344, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-13 03:06:35'),
(345, 120, 'Professor Hero Joestar, has rejected your submitted portfolio by the following reasons:\n\"wrong submission of output 2\" please thoroughly review what needs to be submited portfolio for your revision', 1, '2026-05-13 03:07:49'),
(346, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 03:08:16'),
(347, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-13 03:08:31'),
(348, 141, 'Reimon Zaryl Torio has submitted a portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-13 03:09:56'),
(349, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-13 03:10:30'),
(350, 120, 'Professor Hero Joestar, has already approved your submitted portfolio with the following details:\nFinal Percentage: 90%\nFinal Grade: 4.0', 1, '2026-05-13 03:11:27'),
(351, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-13 03:15:27'),
(352, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-13 03:25:34'),
(353, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 10:18:57'),
(354, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 10:19:56'),
(355, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 10:41:23'),
(356, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 10:57:09'),
(357, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 11:14:16'),
(358, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 11:25:32'),
(359, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 11:46:57'),
(360, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 11:48:32'),
(361, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 11:49:58'),
(362, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 11:50:06'),
(363, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 11:54:15'),
(364, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 11:56:29'),
(365, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:11:45'),
(366, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:12:20'),
(367, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:12:39'),
(368, 141, 'Reimon Zaryl Torio has submitted a portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:13:21'),
(369, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 12:13:52'),
(370, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 12:14:01'),
(371, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:15:02'),
(372, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:15:37'),
(373, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:18:03'),
(374, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:18:36'),
(375, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 12:31:19'),
(376, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 12:31:29'),
(377, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 12:32:34'),
(378, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 12:38:10'),
(379, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:56:50'),
(380, 141, 'Reimon Zaryl Torio has submitted a portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:56:58'),
(381, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:57:15'),
(382, 141, 'Reimon Zaryl Torio has submitted a portfolio in class COMNETS CpE-231 Term 3 2025-2026.', 1, '2026-05-17 12:57:39'),
(383, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 12:58:05'),
(384, 120, 'Professor Hero Joestar, has already approved your submitted portfolio with the following details:\nFinal Percentage: 90%\nFinal Grade: 4.0', 1, '2026-05-17 12:59:04'),
(385, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 13:01:02'),
(386, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 13:01:47'),
(387, 120, 'You are added to the OBJPROG CPE-200 Term 1 2010-2011 class.', 1, '2026-05-17 13:03:27'),
(388, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 13:03:44'),
(389, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 13:04:38'),
(390, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 13:04:53'),
(391, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 13:04:58'),
(392, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 13:05:23'),
(393, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 15:04:33'),
(394, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 15:05:37'),
(395, 141, 'Reimon Zaryl Torio has submitted a portfolio in class OBJPROG CPE-200 Term 1 2010-2011.', 1, '2026-05-17 15:48:01'),
(396, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class OBJPROG CPE-200 Term 1 2010-2011.', 1, '2026-05-17 15:48:11'),
(397, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 15:50:36'),
(398, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 15:51:02'),
(399, 141, 'Reimon Zaryl Torio has submitted a portfolio in class OBJPROG CPE-200 Term 1 2010-2011.', 1, '2026-05-17 15:51:12'),
(400, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 15:51:26'),
(401, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 16:06:17'),
(402, 141, 'Reimon Zaryl Torio has undo their submission of portfolio in class OBJPROG CPE-200 Term 1 2010-2011.', 1, '2026-05-17 16:06:30'),
(403, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-17 16:07:52'),
(404, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-17 16:08:09'),
(405, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 16:40:12'),
(406, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 16:40:30'),
(407, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 16:50:26'),
(408, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 16:50:54'),
(409, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 16:51:24'),
(410, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 16:53:01'),
(411, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 16:53:35'),
(412, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:06:58'),
(413, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 17:15:08'),
(414, 135, 'Your account access as Program Director has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-05-17 17:15:31'),
(415, 137, 'Access revoked: Eiyuu Angelo (Program Director) is now set to Not Verified.', 0, '2026-05-17 17:15:31'),
(416, 135, 'Your account has been approved as Executive Director by the admin.', 1, '2026-05-17 17:15:40'),
(417, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:15:56'),
(418, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 17:20:50'),
(419, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:24:09'),
(420, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 17:27:05'),
(421, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:33:25'),
(422, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-17 17:34:05'),
(423, 135, 'Your account access as Professor has been officially revoked by the admin. If you think this is a mistake, please contact the admin.', 1, '2026-05-17 17:34:17'),
(424, 137, 'Access revoked: Eiyuu Angelo (Professor) is now set to Not Verified.', 0, '2026-05-17 17:34:17'),
(425, 135, 'Your account has been approved as Executive Director by the admin.', 1, '2026-05-17 17:34:21'),
(426, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:34:35'),
(427, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-17 17:39:52'),
(428, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:42:16'),
(429, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:44:56'),
(430, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 1, '2026-05-17 17:59:15'),
(431, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 07:35:49'),
(432, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 15:58:03'),
(433, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 15:59:01'),
(434, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 15:59:45'),
(435, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:00:14'),
(436, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:01:05'),
(437, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 16:04:02'),
(438, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:05:32'),
(439, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:06:35'),
(440, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 16:09:16'),
(441, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:10:48'),
(442, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:11:58'),
(443, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 16:13:04'),
(444, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:15:26'),
(445, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 16:16:06'),
(446, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 16:16:54'),
(447, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 16:17:33'),
(448, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:19:08'),
(449, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:19:43'),
(450, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:24:43'),
(451, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 16:25:45');
INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(452, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:26:14'),
(453, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 16:27:41'),
(454, 141, 'Reimon Zaryl Torio requested to join your class LOGCDES CpE-231 Term 1 2024-2025.', 1, '2026-05-18 16:28:06'),
(455, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:28:32'),
(456, 120, 'You are added to the LOGCDES CpE-231 Term 1 2024-2025 class.', 1, '2026-05-18 16:29:11'),
(457, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 16:29:38'),
(458, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 16:30:50'),
(459, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:42:50'),
(460, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 16:43:03'),
(461, 141, 'Reimon Zaryl Torio has submitted a portfolio in class LOGCDES CpE-231 Term 1 2024-2025.', 1, '2026-05-18 16:43:13'),
(462, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 16:43:40'),
(463, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 17:07:23'),
(464, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 17:08:05'),
(465, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 17:12:05'),
(466, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:19:43'),
(467, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:20:28'),
(468, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 17:23:04'),
(469, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:25:02'),
(470, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:30:48'),
(471, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-18 17:31:01'),
(472, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-18 17:32:26'),
(473, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 17:32:43'),
(474, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:32:55'),
(475, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 17:37:19'),
(476, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 17:49:34'),
(477, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 17:53:15'),
(478, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 18:01:39'),
(479, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 18:05:33'),
(480, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 18:06:40'),
(481, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 18:12:01'),
(482, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 18:15:35'),
(483, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-18 21:27:00'),
(484, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 21:37:43'),
(485, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 21:38:12'),
(486, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-18 21:51:28'),
(487, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 21:56:18'),
(488, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 21:57:05'),
(489, 137, 'New feedback submitted by Hero Joestar (professor). Subject: Test feedback 2.', 0, '2026-05-18 21:58:02'),
(490, 137, 'New feedback submitted by Hero Joestar (professor). Subject: Test feedback 2.', 0, '2026-05-18 21:58:08'),
(491, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-18 22:01:42'),
(492, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-18 22:07:36'),
(493, 137, 'New feedback submitted by Reimon Zaryl Torio (student). Subject: test feedback 3.', 0, '2026-05-18 22:08:36'),
(494, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-18 22:09:40'),
(495, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-18 22:10:42'),
(496, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-29 01:28:42'),
(497, 135, 'Welcome Eiyuu Angelo, you have officially logged in the system.', 0, '2026-05-29 01:29:11'),
(498, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-29 01:29:45'),
(499, 137, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-29 01:30:10'),
(500, 130, 'Welcome Nelo Angelo, you have officially logged in the system.', 1, '2026-05-29 01:30:21'),
(501, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 1, '2026-05-29 02:15:16'),
(502, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-29 02:18:26'),
(503, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-29 02:19:04'),
(504, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-29 02:49:58'),
(505, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 1, '2026-05-29 03:00:47'),
(506, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-29 03:02:40'),
(507, 120, 'Welcome Reimon Zaryl Torio, you have officially logged in the system.', 0, '2026-05-30 04:43:29'),
(508, 141, 'Welcome Hero Joestar, you have officially logged in the system.', 0, '2026-05-30 04:43:44');

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
(9, 12, 39, 50.00, 'Rei Activity 1 Softlab.pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_12_39_6f762310155b.pdf', 'application/pdf', 184543, NULL, 'submitted', '2026-04-06 13:14:17', NULL, NULL, '2026-04-06 13:14:17', '2026-04-06 13:14:17'),
(10, 13, 39, 50.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_13_39_cbafb98ecc9d.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-04-17 09:22:19', NULL, NULL, '2026-04-17 09:02:14', '2026-04-17 09:22:19'),
(12, 16, 39, 50.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_16_39_a5830cb3309c.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-04-19 10:45:31', NULL, NULL, '2026-04-19 10:45:31', '2026-04-19 10:45:31'),
(13, 14, 39, 50.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_14_39_8fcdbe98066a.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-04-19 10:46:12', NULL, NULL, '2026-04-19 10:46:12', '2026-04-19 10:46:12'),
(14, 15, 39, 50.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_15_39_fb878f2313f3.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-04-19 10:46:49', NULL, NULL, '2026-04-19 10:46:49', '2026-04-19 10:46:49'),
(15, 17, 39, 30.00, 'gura_icon.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_17_39_71b4eb82c915.jpg', 'image/jpeg', 31878, NULL, 'submitted', '2026-05-13 10:57:35', NULL, NULL, '2026-05-13 10:57:35', '2026-05-13 10:57:35'),
(16, 19, 39, 30.00, 'Group3-FinalProjectAct.docx', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_19_39_d7ea0b5314ea.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 43327, NULL, 'submitted', '2026-05-17 20:57:37', NULL, NULL, '2026-05-13 11:05:32', '2026-05-17 20:57:37'),
(17, 18, 39, 30.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_18_39_088548b5918b.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-05-13 11:05:34', NULL, NULL, '2026-05-13 11:05:34', '2026-05-13 11:05:34'),
(20, 21, 39, 50.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_21_39_6491c792597e.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-05-17 23:25:02', NULL, NULL, '2026-05-17 23:25:02', '2026-05-17 23:25:02'),
(21, 20, 39, 0.00, 'No Output Submitted', '', '', 0, NULL, 'undone', '2026-05-17 23:47:59', '2026-05-18 00:06:38', NULL, '2026-05-17 23:25:09', '2026-05-18 00:06:38'),
(27, 23, 39, 25.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_23_39_0c218061a3a7.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-05-19 00:41:24', NULL, NULL, '2026-05-19 00:41:24', '2026-05-19 00:41:24'),
(28, 22, 39, 25.00, 'sample_img.jpg', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_22_39_dac26cbff2c5.jpg', 'image/jpeg', 5868, NULL, 'submitted', '2026-05-19 00:42:06', NULL, NULL, '2026-05-19 00:42:06', '2026-05-19 00:42:06'),
(29, 7, 39, 25.00, 'invoice_INV-63456 (1).pdf', 'C:\\Users\\HEROJO~1\\AppData\\Local\\Temp\\soe_portfolio_class_submissions\\39\\output_7_39_2ae5407c72b2.pdf', 'application/pdf', 370930, NULL, 'submitted', '2026-05-19 00:42:34', NULL, NULL, '2026-05-19 00:42:34', '2026-05-19 00:42:34');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(191) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `code_hash` varchar(255) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  `code_verified_at` datetime DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`id`, `email`, `token_hash`, `code_hash`, `code_expires_at`, `code_verified_at`, `expires_at`, `created_at`, `used_at`) VALUES
(6, 'rttorio@student.apc.edu.ph', '$2y$10$KCyg8hk4r6oO5UjSSy0eYO4kwhmbhomWzJ1BKgUTeUJ/kDu6ftKMa', NULL, NULL, NULL, '2026-04-20 02:28:29', '2026-04-19 23:58:29', '2026-04-20 07:59:29'),
(7, 'rttorio@student.apc.edu.ph', '$2y$10$Z0mRVx9iO8SLU3cgRr/64u07UO3l72.4XFNBKLzeAFU7QDHK6iZyO', NULL, NULL, NULL, '2026-04-20 02:35:17', '2026-04-20 00:05:17', '2026-04-20 08:05:57'),
(9, 'rttorio@student.apc.edu.ph', '$2y$10$EbhC7RkCHlLkqWhMflPN6.DGrVLbrnLOB2xroGiBLBw605UEx7Cc6', NULL, NULL, NULL, '2026-04-20 02:54:34', '2026-04-20 00:24:34', NULL),
(11, 'neloangelo4123@gmail.com', '$2y$10$X7SfjnOgHgNRN.gjcf2ajOYxekBRQGHaA.Wnw5Hj1Hg.dcMEs/gXe', NULL, NULL, NULL, '2026-04-20 03:05:57', '2026-04-20 00:35:57', NULL),
(12, 'kmbaclagan@student.apc.edu.ph', '$2y$10$XslD6GeSVV3OZPulzrnfg..NOSKZNFtaMThMJwNYMv5OZMrcg0WJe', '$2y$10$AUDKF8eM9..ZuZVjGVdieuqrCAZLNFkO5uET/Vc5E2j9gRfxvM5q6', '2026-04-20 02:56:10', '2026-04-20 08:54:13', '2026-04-20 03:16:10', '2026-04-20 00:46:10', '2026-04-20 08:55:24');

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
(47, 'Chemical Engineering');

-- --------------------------------------------------------

--
-- Table structure for table `program_course_links`
--

CREATE TABLE `program_course_links` (
  `program_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `linked_by_user_id` int(11) DEFAULT NULL,
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_course_links`
--

INSERT INTO `program_course_links` (`program_id`, `course_id`, `linked_by_user_id`, `linked_at`) VALUES
(1, 44, NULL, '2026-04-14 00:36:03'),
(1, 47, NULL, '2026-04-14 00:36:03'),
(1, 54, NULL, '2026-04-14 00:36:03'),
(1, 57, NULL, '2026-04-14 00:36:03'),
(1, 58, 130, '2026-04-14 00:39:33'),
(1, 60, 130, '2026-04-24 00:28:43'),
(1, 61, 135, '2026-05-13 02:46:49'),
(2, 55, NULL, '2026-04-14 00:36:03'),
(2, 56, NULL, '2026-04-14 00:36:03'),
(2, 59, 141, '2026-04-14 04:46:48'),
(3, 30, NULL, '2026-04-14 00:36:03'),
(3, 31, NULL, '2026-04-14 00:36:03'),
(3, 32, NULL, '2026-04-14 00:36:03'),
(3, 33, NULL, '2026-04-14 00:36:03'),
(3, 35, NULL, '2026-04-14 00:36:03'),
(3, 36, NULL, '2026-04-14 00:36:03');

-- --------------------------------------------------------

--
-- Table structure for table `program_director_assignments`
--

CREATE TABLE `program_director_assignments` (
  `program_id` int(10) NOT NULL,
  `program_director_user_id` int(11) NOT NULL,
  `assigned_by_user_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_director_assignments`
--

INSERT INTO `program_director_assignments` (`program_id`, `program_director_user_id`, `assigned_by_user_id`, `assigned_at`) VALUES
(1, 130, 135, '2026-05-17 17:38:41'),
(2, 141, 135, '2026-04-14 00:47:34'),
(3, 135, 135, '2026-05-17 17:37:55');

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
(6, 20, 'magsubmit kayo by the end of term', '2025-11-03 07:34:13', NULL),
(9, 31, 'all students must required to submit all their recorded output results (Quiz 1-4, Midterms and Finals)', '2026-04-19 00:32:58', NULL);

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
(45, 127, 'Kharljasper', 'Mistar', 'Baclagan', '', '2023', '2023140160', 1, 'kmbaclagan@student.apc.edu.ph', '$2y$10$q6IjxkAe8N.1fUC0yRAcHOmVsBHlflky3TAhD2yGHgzt10P7jOWnW'),
(47, 142, 'Ronin', '', 'Abonita', '', '2023', '2023140011', 1, 'rnabonita@student.apc.edu.ph', '$2y$10$3h.prUeAuAS1BI2N92neROUUw0wlNnK1FLzPxzjv8/vpgFK7rIDw2'),
(48, 143, 'Denbert', 'Javier', 'Veloria', '', '2023', '2023140196', 1, 'djveloria@student.apc.edu.ph', '$2y$10$fQKXU.87TGFXrd2DchP84Oi3WeK.W3i9Y4RNkkde76S46FwUJXAjy');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_homepage_profiles`
--

INSERT INTO `student_homepage_profiles` (`profile_id`, `student_id`, `display_name`, `bio`, `created_at`, `updated_at`, `profile_picture_path`) VALUES
(1, 120, 'Reimon Zaryl Torio', 'Helo Ebriwan', '2026-03-31 00:11:26', '2026-03-31 00:11:26', NULL),
(2, 39, '', NULL, '2026-05-12 23:19:16', '2026-05-18 21:38:31', 'images/user_images/student_120_1779140311.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `student_lists`
--

CREATE TABLE `student_lists` (
  `list_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_name` varchar(120) NOT NULL,
  `enrollment_year` year(4) NOT NULL,
  `created_by_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_lists`
--

INSERT INTO `student_lists` (`list_id`, `program_id`, `batch_name`, `enrollment_year`, `created_by_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 'Cpe-231', '2023', 130, '2026-04-19 14:22:55', '2026-04-19 14:36:47'),
(2, 3, 'ber', '2020', 130, '2026-04-19 14:23:39', '2026-04-19 14:23:39'),
(3, 2, 'CE-something', '2020', 130, '2026-04-19 14:44:31', '2026-04-19 14:44:31'),
(4, 1, 'CpE-2020 SAMPLE', '2020', 130, '2026-04-19 14:47:15', '2026-04-19 14:51:04'),
(9, 35, 'sample', '2023', 130, '2026-04-19 14:51:35', '2026-04-19 14:51:35'),
(10, 1, 'CpE-211', '2021', 130, '2026-05-17 16:44:05', '2026-05-17 16:44:05'),
(11, 3, 'ECE-231', '2023', 135, '2026-05-17 17:18:44', '2026-05-17 17:18:44'),
(12, 3, 'ECE-211', '2021', 135, '2026-05-17 17:39:34', '2026-05-17 17:39:34');

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
  `is_verified` tinyint(1) DEFAULT 0,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `role_type`, `created_at`, `status`, `is_verified`, `profile_picture`) VALUES
(120, 'Reimon Zaryl', 'Torio', 'rttorio@student.apc.edu.ph', '$2y$10$Z6tQ46vGRQN/dZ2IsKMAmOo/nfcOrsyjKX9SuCR6bHN4ipfqKaut.', 'student', '2025-10-08 02:24:32', 1, 1, 'student_120_1778642842.jpeg'),
(127, 'Kharljasper', 'Baclagan', 'kmbaclagan@student.apc.edu.ph', '$2y$10$lSEo8jPInd.oYs..3SE4b.LW3Sp.Tvt1jTTH2E0GPLL6Lhjq4FZn2', 'student', '2025-11-03 06:22:08', 1, 1, NULL),
(130, 'Nelo', 'Angelo', 'neloangelo4123@gmail.com', '$2y$10$m.xo01H8HigWvFoquVFVTubf/t2uLR4t/sjcYtXwHtNErxUpkl4kK', 'faculty', '2026-04-05 11:02:51', 1, 1, NULL),
(135, 'Eiyuu', 'Angelo', 'eiyuuangelo@gmail.com', '$2y$10$cIXoJQv4cUNO0Vjpk.OYDeitLTRars1VhOZIOe5EtCtW1A.J0QzQK', 'faculty', '2026-04-09 03:39:30', 1, 1, NULL),
(137, 'Reimon Zaryl', 'Torio', 'reimonzaryltorio@gmail.com', '$2y$10$bY1ByfAMT5.NyxeBSSgkQuSe9g34YQlZT5TFqOtZpbgAZPRwtXFji', 'admin', '2026-04-09 03:58:25', 1, 1, NULL),
(141, 'Hero', 'Joestar', 'herojoestar@gmail.com', '$2y$10$DjWBM3g6LUKe3wdpvxlJg.SJtXELlHmO5IzZQ8e9jDym9VMU7QXKi', 'faculty', '2026-04-09 06:37:48', 1, 1, 'faculty_141_1779139013.jpeg'),
(142, 'Ronin', 'Abonita', 'rnabonita@student.apc.edu.ph', '$2y$10$3h.prUeAuAS1BI2N92neROUUw0wlNnK1FLzPxzjv8/vpgFK7rIDw2', 'student', '2026-04-20 00:58:27', 1, 0, NULL),
(143, 'Denbert', 'Veloria', 'djveloria@student.apc.edu.ph', '$2y$10$fQKXU.87TGFXrd2DchP84Oi3WeK.W3i9Y4RNkkde76S46FwUJXAjy', 'student', '2026-04-20 01:02:49', 1, 0, NULL);

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
  ADD UNIQUE KEY `uq_admins_user_id` (`user_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `class_creation_requests`
--
ALTER TABLE `class_creation_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `idx_ccr_course` (`course_id`),
  ADD KEY `idx_ccr_program` (`program_id`),
  ADD KEY `idx_ccr_requested_by` (`requested_by_user_id`),
  ADD KEY `idx_ccr_program_director` (`program_director_user_id`),
  ADD KEY `idx_ccr_status` (`request_status`),
  ADD KEY `idx_ccr_approved_class` (`approved_class_id`),
  ADD KEY `fk_ccr_reviewed_by` (`reviewed_by_user_id`);

--
-- Indexes for table `class_difficulty_ratings`
--
ALTER TABLE `class_difficulty_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `uq_class_student_rating` (`class_id`,`student_id`),
  ADD KEY `idx_cdr_class_id` (`class_id`),
  ADD KEY `idx_cdr_student_id` (`student_id`);

--
-- Indexes for table `class_invite_links`
--
ALTER TABLE `class_invite_links`
  ADD PRIMARY KEY (`invite_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `class_outputs`
--
ALTER TABLE `class_outputs`
  ADD PRIMARY KEY (`output_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `class_portfolio_reviews`
--
ALTER TABLE `class_portfolio_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `uq_class_student_review` (`class_id`,`student_id`),
  ADD KEY `idx_cpr_class` (`class_id`),
  ADD KEY `idx_cpr_student` (`student_id`),
  ADD KEY `idx_cpr_reviewer` (`reviewed_by_user_id`);

--
-- Indexes for table `class_portfolio_submissions`
--
ALTER TABLE `class_portfolio_submissions`
  ADD PRIMARY KEY (`portfolio_submission_id`),
  ADD UNIQUE KEY `uq_class_student` (`class_id`,`student_id`),
  ADD KEY `idx_cps_student_id` (`student_id`),
  ADD KEY `idx_cps_class_id` (`class_id`);

--
-- Indexes for table `class_professor_assignments`
--
ALTER TABLE `class_professor_assignments`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `idx_cpa_professor` (`professor_user_id`),
  ADD KEY `idx_cpa_assigned_by` (`assigned_by_user_id`);

--
-- Indexes for table `class_students`
--
ALTER TABLE `class_students`
  ADD PRIMARY KEY (`class_student_id`),
  ADD UNIQUE KEY `ux_class_student` (`class_id`,`student_id`),
  ADD KEY `idx_class_students_class_status` (`class_id`,`status`),
  ADD KEY `idx_class_students_student_status` (`student_id`,`status`),
  ADD KEY `idx_class_students_invited_by` (`invited_by_user_id`),
  ADD KEY `idx_class_students_approved_by` (`approved_by_user_id`);

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
  ADD UNIQUE KEY `uq_faculty_user_id` (`user_id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `uk_faculty_id` (`faculty_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `idx_feedback_user` (`user_id`),
  ADD KEY `idx_feedback_status` (`status`),
  ADD KEY `idx_feedback_role` (`user_role`);

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
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_email` (`email`),
  ADD KEY `idx_password_reset_expires` (`expires_at`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `program_course_links`
--
ALTER TABLE `program_course_links`
  ADD PRIMARY KEY (`program_id`,`course_id`),
  ADD KEY `idx_pcl_course_id` (`course_id`),
  ADD KEY `idx_pcl_linked_by` (`linked_by_user_id`);

--
-- Indexes for table `program_director_assignments`
--
ALTER TABLE `program_director_assignments`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `uq_pd_user_one_program` (`program_director_user_id`),
  ADD KEY `idx_pda_assigned_by` (`assigned_by_user_id`);

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
  ADD UNIQUE KEY `uq_students_user_id` (`user_id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `student_homepage_profiles`
--
ALTER TABLE `student_homepage_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `uq_homepage_profile_student` (`student_id`),
  ADD KEY `idx_homepage_profile_student` (`student_id`);

--
-- Indexes for table `student_lists`
--
ALTER TABLE `student_lists`
  ADD PRIMARY KEY (`list_id`),
  ADD UNIQUE KEY `uq_student_list_program_batch_year` (`program_id`,`batch_name`,`enrollment_year`),
  ADD KEY `idx_student_lists_program` (`program_id`),
  ADD KEY `idx_student_lists_created_by` (`created_by_user_id`);

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
  MODIFY `category_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `class_creation_requests`
--
ALTER TABLE `class_creation_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_difficulty_ratings`
--
ALTER TABLE `class_difficulty_ratings`
  MODIFY `rating_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `class_invite_links`
--
ALTER TABLE `class_invite_links`
  MODIFY `invite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_outputs`
--
ALTER TABLE `class_outputs`
  MODIFY `output_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `class_portfolio_reviews`
--
ALTER TABLE `class_portfolio_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `class_portfolio_submissions`
--
ALTER TABLE `class_portfolio_submissions`
  MODIFY `portfolio_submission_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `class_students`
--
ALTER TABLE `class_students`
  MODIFY `class_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `extracurricular_portfolios`
--
ALTER TABLE `extracurricular_portfolios`
  MODIFY `portfolio_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `feedback_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `file_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `folder_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=509;

--
-- AUTO_INCREMENT for table `output_submissions`
--
ALTER TABLE `output_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `requirements`
--
ALTER TABLE `requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `student_homepage_profiles`
--
ALTER TABLE `student_homepage_profiles`
  MODIFY `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_lists`
--
ALTER TABLE `student_lists`
  MODIFY `list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=144;

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
-- Constraints for table `class_creation_requests`
--
ALTER TABLE `class_creation_requests`
  ADD CONSTRAINT `fk_ccr_approved_class` FOREIGN KEY (`approved_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_program_director` FOREIGN KEY (`program_director_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_requested_by` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ccr_reviewed_by` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `class_difficulty_ratings`
--
ALTER TABLE `class_difficulty_ratings`
  ADD CONSTRAINT `fk_cdr_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cdr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_outputs`
--
ALTER TABLE `class_outputs`
  ADD CONSTRAINT `class_outputs_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `class_portfolio_reviews`
--
ALTER TABLE `class_portfolio_reviews`
  ADD CONSTRAINT `fk_cpr_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cpr_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cpr_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_portfolio_submissions`
--
ALTER TABLE `class_portfolio_submissions`
  ADD CONSTRAINT `fk_cps_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cps_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_professor_assignments`
--
ALTER TABLE `class_professor_assignments`
  ADD CONSTRAINT `fk_cpa_assigned_by` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cpa_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cpa_prof_user` FOREIGN KEY (`professor_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_students`
--
ALTER TABLE `class_students`
  ADD CONSTRAINT `fk_class_students_approved_by` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_class_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_class_students_invited_by` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_class_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

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
-- Constraints for table `faculty_profiles`
--
ALTER TABLE `faculty_profiles`
  ADD CONSTRAINT `fk_faculty_profiles_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD CONSTRAINT `fk_feedback_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
-- Constraints for table `program_course_links`
--
ALTER TABLE `program_course_links`
  ADD CONSTRAINT `fk_pcl_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pcl_linked_by_user` FOREIGN KEY (`linked_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pcl_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `program_director_assignments`
--
ALTER TABLE `program_director_assignments`
  ADD CONSTRAINT `fk_pda_assigned_by` FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pda_pd_user` FOREIGN KEY (`program_director_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pda_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

--
-- Constraints for table `student_lists`
--
ALTER TABLE `student_lists`
  ADD CONSTRAINT `fk_student_lists_created_by` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_lists_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
