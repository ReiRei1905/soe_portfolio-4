-- Lists of Students management migration
-- Run in phpMyAdmin against your SOE portfolio database

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS student_lists (
    list_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    program_id INT NOT NULL,
    batch_name VARCHAR(120) NOT NULL,
    year_of_enrollment INT NOT NULL,
    created_by_user_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (list_id),
    UNIQUE KEY uq_student_lists_program_batch_year (program_id, batch_name, year_of_enrollment),
    KEY idx_student_lists_program_year (program_id, year_of_enrollment),
    KEY idx_student_lists_created_by (created_by_user_id),
    CONSTRAINT fk_student_lists_program
        FOREIGN KEY (program_id) REFERENCES programs(program_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_lists_created_by
        FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
