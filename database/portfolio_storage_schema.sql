-- SOE Portfolio storage schema for assessment/projects/certificates
-- Run this in phpMyAdmin against database: soe_portfolio

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS portfolio_categories (
    category_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_key VARCHAR(32) NOT NULL,
    category_label VARCHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_portfolio_categories_key (category_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO portfolio_categories (category_key, category_label)
VALUES
    ('assessment', 'Assessments'),
    ('projects', 'Projects'),
    ('certificates', 'Certificates/Awards')
ON DUPLICATE KEY UPDATE
    category_label = VALUES(category_label),
    updated_at = CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS portfolio_folders (
    folder_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    parent_folder_id BIGINT UNSIGNED NULL,
    folder_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (folder_id),
    KEY idx_portfolio_folders_student_category (student_id, category_id),
    KEY idx_portfolio_folders_parent (parent_folder_id),
    CONSTRAINT fk_portfolio_folders_category
        FOREIGN KEY (category_id) REFERENCES portfolio_categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_portfolio_folders_parent
        FOREIGN KEY (parent_folder_id) REFERENCES portfolio_folders(folder_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uq_portfolio_folder_name_per_scope (student_id, category_id, parent_folder_id, folder_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_files (
    file_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    folder_id BIGINT UNSIGNED NULL,
    original_file_name VARCHAR(255) NOT NULL,
    stored_file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(1024) NOT NULL,
    mime_type VARCHAR(127) NOT NULL DEFAULT 'application/octet-stream',
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (file_id),
    KEY idx_portfolio_files_student_category (student_id, category_id),
    KEY idx_portfolio_files_folder (folder_id),
    CONSTRAINT fk_portfolio_files_category
        FOREIGN KEY (category_id) REFERENCES portfolio_categories(category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_portfolio_files_folder
        FOREIGN KEY (folder_id) REFERENCES portfolio_folders(folder_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
