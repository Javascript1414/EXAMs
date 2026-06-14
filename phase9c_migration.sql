-- ---------------------------------------------------------
-- Phase 9C Database Migration: Exam Engine
-- Database: exams_lms
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS `exams` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `exam_name` VARCHAR(255) NOT NULL,
    `exam_type` ENUM('Practice Test', 'Mock Test', 'Module Test', 'Unit Test', 'Final Test') NOT NULL DEFAULT 'Practice Test',
    `duration_minutes` INT UNSIGNED NOT NULL,
    `total_marks` DECIMAL(8,2) NOT NULL,
    `passing_marks` DECIMAL(8,2) NOT NULL,
    `negative_marking_enabled` BOOLEAN NOT NULL DEFAULT FALSE,
    `show_correct_answers` BOOLEAN NOT NULL DEFAULT FALSE,
    `show_explanations` BOOLEAN NOT NULL DEFAULT FALSE,
    `random_question_order` BOOLEAN NOT NULL DEFAULT FALSE,
    `random_option_order` BOOLEAN NOT NULL DEFAULT FALSE,
    `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'draft',
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trade_subject` (`trade_id`, `subject_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_questions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `section_id` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_exam_question` (`exam_id`, `question_id`),
    INDEX `idx_section_id` (`section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;