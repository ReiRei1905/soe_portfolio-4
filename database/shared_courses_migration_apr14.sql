-- Shared courses migration for soe_portfolio
-- Run this in phpMyAdmin (SQL tab) on the target database.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `program_course_links` (
  `program_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `linked_by_user_id` int(11) DEFAULT NULL,
  `linked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`program_id`, `course_id`),
  KEY `idx_pcl_course_id` (`course_id`),
  KEY `idx_pcl_linked_by` (`linked_by_user_id`),
  CONSTRAINT `fk_pcl_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pcl_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pcl_linked_by_user` FOREIGN KEY (`linked_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Backfill existing single-program courses into shared mapping table.
INSERT INTO `program_course_links` (`program_id`, `course_id`, `linked_by_user_id`)
SELECT c.`program_id`, c.`course_id`, NULL
FROM `courses` c
WHERE c.`program_id` IS NOT NULL
ON DUPLICATE KEY UPDATE
  `linked_at` = CURRENT_TIMESTAMP;

COMMIT;
