-- Class creation request workflow migration for soe_portfolio
-- Run this in phpMyAdmin (SQL tab) on the target database.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `class_creation_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `idx_ccr_course` (`course_id`),
  KEY `idx_ccr_program` (`program_id`),
  KEY `idx_ccr_requested_by` (`requested_by_user_id`),
  KEY `idx_ccr_program_director` (`program_director_user_id`),
  KEY `idx_ccr_status` (`request_status`),
  KEY `idx_ccr_approved_class` (`approved_class_id`),
  CONSTRAINT `fk_ccr_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccr_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccr_requested_by` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccr_program_director` FOREIGN KEY (`program_director_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ccr_reviewed_by` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ccr_approved_class` FOREIGN KEY (`approved_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
