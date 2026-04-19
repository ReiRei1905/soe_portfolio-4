-- Class membership workflow migration for faculty invite + student enroll approval.
-- Run this in phpMyAdmin against the `soe_portfolio` database.

CREATE TABLE IF NOT EXISTS `class_students` (
  `class_student_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`class_student_id`),
  UNIQUE KEY `ux_class_student` (`class_id`, `student_id`),
  KEY `idx_class_students_class_status` (`class_id`, `status`),
  KEY `idx_class_students_student_status` (`student_id`, `status`),
  KEY `idx_class_students_invited_by` (`invited_by_user_id`),
  KEY `idx_class_students_approved_by` (`approved_by_user_id`),
  CONSTRAINT `fk_class_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_class_students_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_class_students_invited_by` FOREIGN KEY (`invited_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_class_students_approved_by` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional seed cleanup: if you need to clear all membership records while testing, run this manually.
-- DELETE FROM class_students;
