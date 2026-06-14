-- ---------------------------------------------------------
-- Phase 9A Database Migration: Question Bank
-- Database: exams_lms
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `questions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `question_type` ENUM('mcq', 'true_false') NOT NULL DEFAULT 'mcq',
    `question_text` TEXT NOT NULL,
    `option_a` TEXT NOT NULL,
    `option_b` TEXT NOT NULL,
    `option_c` TEXT NULL,
    `option_d` TEXT NULL,
    `correct_answer` ENUM('A', 'B', 'C', 'D') NOT NULL,
    `explanation` TEXT NULL,
    `difficulty` ENUM('Easy', 'Medium', 'Hard') NOT NULL DEFAULT 'Medium',
    `marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `negative_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trade_subject` (`trade_id`, `subject_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `question_import_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `total_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `imported_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `duplicate_rows` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;