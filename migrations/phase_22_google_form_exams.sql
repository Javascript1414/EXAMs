-- Phase 22: Google Form Exam Management System
-- Database Migration for Google Form Exams Feature

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- 1. Google Form Exams Table
CREATE TABLE IF NOT EXISTS `google_form_exams` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_title` VARCHAR(255) NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `trade_id` INT UNSIGNED NOT NULL,
    `google_form_link` TEXT NOT NULL,
    `total_marks` INT NOT NULL DEFAULT 100,
    `pass_marks` INT NOT NULL DEFAULT 40,
    `exam_date` DATE NOT NULL,
    `exam_time` TIME NULL,
    `instructions` LONGTEXT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    `show_results` BOOLEAN DEFAULT TRUE,
    `show_answers` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_trade_id` (`trade_id`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_exam_date` (`exam_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Google Form Exam Attempts Table
CREATE TABLE IF NOT EXISTS `google_form_exam_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `exam_title` VARCHAR(255) NOT NULL,
    `exam_source` VARCHAR(50) DEFAULT 'Google Form',
    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `marks_obtained` INT NULL,
    `marks_entered_by` BIGINT UNSIGNED NULL,
    `marks_entered_at` TIMESTAMP NULL,
    `result_status` ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `google_form_exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`marks_entered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_exam_id` (`exam_id`),
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_result_status` (`result_status`),
    INDEX `idx_marks_obtained` (`marks_obtained`),
    UNIQUE KEY `unique_attempt` (`student_id`, `exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Google Form Exam Teacher Permissions Table
CREATE TABLE IF NOT EXISTS `google_form_exam_permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `can_create_exams` BOOLEAN DEFAULT TRUE,
    `can_enter_marks` BOOLEAN DEFAULT TRUE,
    `granted_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`granted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_permission` (`teacher_id`, `subject_id`),
    INDEX `idx_teacher_id` (`teacher_id`),
    INDEX `idx_subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Update Certificates Table to Include Google Form Exams
ALTER TABLE `certificates` 
ADD COLUMN `exam_source` VARCHAR(50) DEFAULT 'Regular' AFTER `subject_id`;

-- 5. Add Google Form Exam Stats Table
CREATE TABLE IF NOT EXISTS `google_form_exam_stats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT UNSIGNED NOT NULL,
    `total_students` INT DEFAULT 0,
    `students_appeared` INT DEFAULT 0,
    `marks_entered` INT DEFAULT 0,
    `certificates_generated` INT DEFAULT 0,
    `average_marks` DECIMAL(5, 2) DEFAULT 0.00,
    `pass_count` INT DEFAULT 0,
    `fail_count` INT DEFAULT 0,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `google_form_exams`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_exam_stats` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
