-- ---------------------------------------------------------
-- Phase 8 Database Migration: Subjects and Study Materials
-- Database: exams_lms
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- 1. Subjects Table
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    INDEX `idx_trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Study Materials Table
CREATE TABLE IF NOT EXISTS `study_materials` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `material_type` ENUM('pdf', 'note', 'video', 'youtube') NOT NULL,
    `file_path` VARCHAR(255) NULL,
    `youtube_url` VARCHAR(255) NULL,
    `uploaded_by` BIGINT UNSIGNED NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trade_id` (`trade_id`),
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_material_type` (`material_type`),
    INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;