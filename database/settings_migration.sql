-- SQL Migration for Settings - Profile Picture Functionality
-- Execute this in your PhpMyAdmin SQL tab for the soe_portfolio database

-- Create faculty_profiles table with profile_picture_path
CREATE TABLE IF NOT EXISTS `faculty_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `profile_picture_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `uk_faculty_id` (`faculty_id`),
  CONSTRAINT `fk_faculty_profiles_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
