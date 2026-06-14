-- ---------------------------------------------------------
-- Phase 9D Database Migration: Exam Attempts & Results
-- Database: exams_lms
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `results`;
DROP TABLE IF EXISTS `exam_answers`;
DROP TABLE IF EXISTS `exam_attempts`;

CREATE TABLE IF NOT EXISTS `exam_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `submitted_at` TIMESTAMP NULL DEFAULT NULL,
    `score` DECIMAL(8,2) DEFAULT 0.00,
    `percentage` DECIMAL(5,2) DEFAULT 0.00,
    `status` ENUM('in_progress', 'submitted', 'abandoned') DEFAULT 'in_progress',
    `time_taken_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_answers` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` BIGINT UNSIGNED NOT NULL,
    `question_id` BIGINT UNSIGNED NOT NULL,
    `selected_answer` ENUM('A', 'B', 'C', 'D') NULL,
    `is_correct` BOOLEAN DEFAULT FALSE,
    `answer_status` ENUM('not_visited', 'not_answered', 'answered', 'marked_for_review') DEFAULT 'not_visited',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_attempt_question` (`attempt_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `results` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `total_marks` DECIMAL(8,2) NOT NULL,
    `obtained_marks` DECIMAL(8,2) NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL,
    `is_passed` BOOLEAN NOT NULL,
    `rank` INT UNSIGNED NULL,
    `certificate_generated` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;