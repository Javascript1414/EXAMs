-- Phase 23: Practical Exam Management System
-- Database Migration for Practical Exams with Theory + Practical Split Marks

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- 1. Practical Exams Table
CREATE TABLE IF NOT EXISTS `practical_exams` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `trade_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` LONGTEXT NULL,
    `theory_marks` INT NOT NULL DEFAULT 80,
    `practical_marks` INT NOT NULL DEFAULT 20,
    `total_marks` INT NOT NULL DEFAULT 100,
    `practical_pass_marks` INT NOT NULL DEFAULT 10,
    `submission_deadline` DATETIME NOT NULL,
    `evaluation_instructions` LONGTEXT NULL,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('draft', 'active', 'closed') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_exam_id` (`exam_id`),
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_trade_id` (`trade_id`),
    INDEX `idx_created_by` (`created_by`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Practical Submissions Table
CREATE TABLE IF NOT EXISTS `practical_submissions` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practical_exam_id` INT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NULL,
    `submission_file` VARCHAR(255) NOT NULL,
    `submission_link` VARCHAR(500) NULL,
    `submission_notes` LONGTEXT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `is_late` BOOLEAN DEFAULT FALSE,
    `status` ENUM('submitted', 'under_review', 'marked', 'rejected') DEFAULT 'submitted',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_submission` (`practical_exam_id`, `student_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_exam_id` (`exam_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Practical Marks Table
CREATE TABLE IF NOT EXISTS `practical_marks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `submission_id` BIGINT UNSIGNED NOT NULL,
    `practical_exam_id` INT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NULL,
    `marks_obtained` INT NULL,
    `result_status` ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
    `feedback` LONGTEXT NULL,
    `marked_by` BIGINT UNSIGNED NOT NULL,
    `marked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`submission_id`) REFERENCES `practical_submissions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`marked_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_practical_mark` (`submission_id`, `exam_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_result_status` (`result_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Combined Results Table (Theory + Practical)
CREATE TABLE IF NOT EXISTS `combined_exam_results` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `practical_exam_id` INT UNSIGNED NULL,
    `theory_marks` INT NULL,
    `theory_percentage` DECIMAL(5, 2) NULL,
    `practical_marks` INT NULL,
    `practical_percentage` DECIMAL(5, 2) NULL,
    `total_marks` INT NULL,
    `total_percentage` DECIMAL(5, 2) NULL,
    `result_status` ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
    `certificate_generated` BOOLEAN DEFAULT FALSE,
    `generated_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE SET NULL,
    UNIQUE KEY `unique_combined_result` (`student_id`, `exam_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_certificate_generated` (`certificate_generated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Update Certificates Table for Combined Exams
-- NOTE: These columns may already exist, so we skip if they do
-- ALTER TABLE `certificates` 
-- ADD COLUMN `is_combined_exam` BOOLEAN DEFAULT FALSE,
-- ADD COLUMN `combined_result_id` BIGINT UNSIGNED NULL;

-- 6. Practical Statistics Table
CREATE TABLE IF NOT EXISTS `practical_exam_stats` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `practical_exam_id` INT UNSIGNED NOT NULL,
    `total_students` INT DEFAULT 0,
    `submissions_received` INT DEFAULT 0,
    `submissions_pending` INT DEFAULT 0,
    `marked_submissions` INT DEFAULT 0,
    `pass_count` INT DEFAULT 0,
    `fail_count` INT DEFAULT 0,
    `average_practical_marks` DECIMAL(5, 2) DEFAULT 0.00,
    `average_combined_marks` DECIMAL(5, 2) DEFAULT 0.00,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_practical_stats` (`practical_exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
