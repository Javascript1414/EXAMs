-- ---------------------------------------------------------
-- Master Architecture Freeze Migration
-- Database: exams_lms
-- Description: Adds all future-critical tables and columns 
-- for Scheduling, Community, Analytics, Certificates & Security.
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- =========================================================
-- 1. ALTER EXISTING TABLES (Extending for Future Features)
-- =========================================================

-- Users: Security Enhancements
ALTER TABLE `users` 
ADD COLUMN `password_last_changed` TIMESTAMP NULL DEFAULT NULL AFTER `last_login`,
ADD COLUMN `failed_login_attempts` INT UNSIGNED DEFAULT 0 AFTER `password_last_changed`,
ADD COLUMN `lockout_until` TIMESTAMP NULL DEFAULT NULL AFTER `failed_login_attempts`,
ADD COLUMN `two_factor_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `lockout_until`;

-- Exams: Scheduling & Publishing Workflow
ALTER TABLE `exams`
ADD COLUMN `scheduled_start_time` DATETIME NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `scheduled_end_time` DATETIME NULL DEFAULT NULL AFTER `scheduled_start_time`,
ADD COLUMN `published_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `scheduled_end_time`,
ADD COLUMN `published_at` DATETIME NULL DEFAULT NULL AFTER `published_by`;

ALTER TABLE `exams` ADD CONSTRAINT `fk_exams_published_by` FOREIGN KEY (`published_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;
CREATE INDEX `idx_exams_schedule` ON `exams` (`scheduled_start_time`, `scheduled_end_time`);

-- Exam Attempts: Proctoring & Tracking
ALTER TABLE `exam_attempts`
ADD COLUMN `ip_address` VARCHAR(45) NULL DEFAULT NULL AFTER `status`,
ADD COLUMN `user_agent` TEXT NULL DEFAULT NULL AFTER `ip_address`,
ADD COLUMN `last_saved_at` TIMESTAMP NULL DEFAULT NULL AFTER `user_agent`;

-- Notifications: Action URLs & Bulk Targeting
ALTER TABLE `notifications`
ADD COLUMN `action_url` VARCHAR(255) NULL DEFAULT NULL AFTER `message`,
ADD COLUMN `trade_id` INT UNSIGNED NULL DEFAULT NULL AFTER `action_url`,
ADD COLUMN `subject_id` INT UNSIGNED NULL DEFAULT NULL AFTER `trade_id`;

ALTER TABLE `notifications` ADD CONSTRAINT `fk_notif_trade` FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE;
ALTER TABLE `notifications` ADD CONSTRAINT `fk_notif_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE;

-- Study Materials: Enhancements
ALTER TABLE `study_materials`
ADD COLUMN `category` VARCHAR(100) NULL DEFAULT NULL AFTER `title`;

-- =========================================================
-- 2. CREATE NEW TABLES (For Future Modules)
-- =========================================================

-- Security: Login Auditing
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

-- Certificates System
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `trade_id` INT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NULL,
    `certificate_code` VARCHAR(100) NOT NULL UNIQUE,
    `pdf_url` VARCHAR(255) NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('valid', 'revoked') DEFAULT 'valid',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE SET NULL,
    INDEX `idx_cert_code` (`certificate_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trade-Based Community Forum
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

CREATE TABLE IF NOT EXISTS `community_comments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `community_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- LMS Enhancements
CREATE TABLE IF NOT EXISTS `study_material_progress` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `last_position` VARCHAR(50) NULL, -- For video timestamps or PDF pages
    `is_completed` BOOLEAN DEFAULT FALSE,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `study_materials`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_material` (`user_id`, `material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics
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

SET FOREIGN_KEY_CHECKS=1;