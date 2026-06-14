-- ---------------------------------------------------------
-- COMPLETE DATABASE SETUP FOR EXAMS LMS
-- Database: exams_lms
-- ---------------------------------------------------------

-- Create Database
CREATE DATABASE IF NOT EXISTS `exams_lms` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- ---------------------------------------------------------
-- 1. ROLES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. TRADES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_name` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. USERS TABLE (WITH SECURITY & SCHEDULING COLUMNS)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `trade_id` INT UNSIGNED NOT NULL,
    `profile_photo` VARCHAR(255) NULL,
    `gender` ENUM('Male', 'Female', 'Other', 'Prefer not to say') NULL,
    `date_of_birth` DATE NULL,
    `address` TEXT NULL,
    `batch` VARCHAR(100) NULL,
    `institute_name` VARCHAR(255) NULL,
    `enrollment_no` VARCHAR(100) NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `email_verified` BOOLEAN DEFAULT FALSE,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    `password_last_changed` TIMESTAMP NULL DEFAULT NULL,
    `failed_login_attempts` INT UNSIGNED DEFAULT 0,
    `lockout_until` TIMESTAMP NULL DEFAULT NULL,
    `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE RESTRICT,
    INDEX `idx_email` (`email`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_role_id` (`role_id`),
    INDEX `idx_trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4. OTP VERIFICATIONS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `otp_verifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `otp_code` VARCHAR(10) NOT NULL,
    `purpose` ENUM('email_verification', 'password_reset') NOT NULL DEFAULT 'email_verification',
    `expires_at` TIMESTAMP NOT NULL,
    `is_used` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_otp` (`user_id`, `otp_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5. LOGIN LOGS TABLE (Security Auditing)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `status` ENUM('success', 'failed', 'locked') NOT NULL,
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_login_user` (`user_id`),
    INDEX `idx_login_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 6. NOTIFICATIONS TABLE (WITH ACTION URL & TARGETING)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` VARCHAR(255) NULL DEFAULT NULL,
    `trade_id` INT UNSIGNED NULL DEFAULT NULL,
    `subject_id` INT UNSIGNED NULL DEFAULT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 7. SUBJECTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    INDEX `idx_trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 8. STUDY MATERIALS TABLE (WITH CATEGORY)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `study_materials` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NULL DEFAULT NULL,
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

-- ---------------------------------------------------------
-- 9. STUDY MATERIAL PROGRESS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `study_material_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `last_position` VARCHAR(50) NULL,
    `is_completed` BOOLEAN DEFAULT FALSE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `study_materials`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_material` (`user_id`, `material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 10. QUESTIONS TABLE
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 11. QUESTION IMPORT LOGS TABLE
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 12. EXAMS TABLE (WITH SCHEDULING & PUBLISHING)
-- ---------------------------------------------------------
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
    `scheduled_start_time` DATETIME NULL DEFAULT NULL,
    `scheduled_end_time` DATETIME NULL DEFAULT NULL,
    `published_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `published_at` DATETIME NULL DEFAULT NULL,
    `created_by` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`published_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_trade_subject` (`trade_id`, `subject_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_exams_schedule` (`scheduled_start_time`, `scheduled_end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 13. EXAM QUESTIONS TABLE
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 14. EXAM ATTEMPTS TABLE (WITH PROCTORING & TRACKING)
-- ---------------------------------------------------------
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
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` TEXT NULL,
    `last_saved_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 15. EXAM ANSWERS TABLE
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 16. RESULTS TABLE
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- 17. CERTIFICATES TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` VARCHAR(100) NOT NULL UNIQUE,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `result_id` BIGINT UNSIGNED NOT NULL,
    `score` DECIMAL(8,2) NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verification_code` VARCHAR(100) NOT NULL UNIQUE,
    `generated_by` BIGINT UNSIGNED NULL,
    `status` ENUM('active', 'revoked') DEFAULT 'active',
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`result_id`) REFERENCES `results`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_cert_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 18. COMMUNITY POSTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `community_posts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `trade_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `views_count` INT UNSIGNED DEFAULT 0,
    `is_locked` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    INDEX `idx_post_trade` (`trade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 19. COMMUNITY COMMENTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `community_comments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 20. COMMUNITY REPORTS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `community_reports` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` BIGINT UNSIGNED NOT NULL,
    `post_id` BIGINT UNSIGNED NULL,
    `comment_id` BIGINT UNSIGNED NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'reviewed', 'dismissed') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`comment_id`) REFERENCES `community_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 21. ANALYTICS USER PROGRESS TABLE
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS `analytics_user_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `is_completed` BOOLEAN DEFAULT FALSE,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_subject` (`user_id`, `subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- SEED DATA
-- ---------------------------------------------------------

-- Seed Roles
INSERT IGNORE INTO `roles` (`id`, `name`) VALUES 
(1, 'superadmin'),
(2, 'admin'),
(3, 'moderator'),
(4, 'student');

-- Seed Sample Trade
INSERT IGNORE INTO `trades` (`id`, `trade_name`, `description`) VALUES 
(1, 'General Education', 'Default general education trade.');

-- Seed Subject
INSERT IGNORE INTO `subjects` (`id`, `trade_id`, `subject_name`, `description`) VALUES 
(1, 1, 'General Knowledge', 'General knowledge subject');

-- Seed Default Super Admin (Password is: password)
INSERT IGNORE INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `trade_id`, `role_id`, `status`, `email_verified`) VALUES 
(1, 'System SuperAdmin', 'superadmin@example.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 'active', 1);

SET FOREIGN_KEY_CHECKS=1;

-- ---------------------------------------------------------
-- DATABASE SETUP COMPLETE!
-- ---------------------------------------------------------
