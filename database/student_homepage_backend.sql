-- Student homepage backend schema
-- Run this in phpMyAdmin after selecting database `soe_portfolio`.

CREATE TABLE IF NOT EXISTS `student_homepage_profiles` (
  `profile_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `display_name` varchar(120) NOT NULL,
  `bio` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `uq_homepage_profile_student` (`student_id`),
  KEY `idx_homepage_profile_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `extracurricular_portfolios` (
  `portfolio_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `portfolio_key` varchar(64) NOT NULL,
  `title` varchar(120) NOT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`portfolio_id`),
  UNIQUE KEY `uq_extracurricular_per_student_key` (`student_id`, `portfolio_key`),
  KEY `idx_extracurricular_student_sort` (`student_id`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `extracurricular_portfolio_files` (
  `portfolio_id` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`portfolio_id`, `file_id`),
  KEY `idx_epf_file` (`file_id`),
  CONSTRAINT `fk_epf_portfolio` FOREIGN KEY (`portfolio_id`) REFERENCES `extracurricular_portfolios` (`portfolio_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_epf_file` FOREIGN KEY (`file_id`) REFERENCES `files` (`file_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
