-- =====================================================================
-- Phase 16: Student Settings & Preferences System
-- Database: exams_lms
-- Purpose: Add notification settings, preferences, activity logs, and deletion requests
-- =====================================================================

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- =====================================================================
-- 1. EXTEND login_logs TABLE - Add device and browser information
-- =====================================================================
ALTER TABLE `login_logs` ADD COLUMN IF NOT EXISTS `browser` VARCHAR(255) NULL DEFAULT NULL AFTER `user_agent`;
ALTER TABLE `login_logs` ADD COLUMN IF NOT EXISTS `device` VARCHAR(255) NULL DEFAULT NULL AFTER `browser`;
ALTER TABLE `login_logs` ADD COLUMN IF NOT EXISTS `logout_time` TIMESTAMP NULL DEFAULT NULL AFTER `login_time`;

-- =====================================================================
-- 2. STUDENT NOTIFICATION SETTINGS TABLE
-- =====================================================================
CREATE TABLE IF NOT EXISTS `student_notification_settings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `exam_reminder` BOOLEAN DEFAULT TRUE COMMENT 'Exam reminder notifications',
    `result_notification` BOOLEAN DEFAULT TRUE COMMENT 'Result notifications',
    `system_notification` BOOLEAN DEFAULT TRUE COMMENT 'System-wide notifications',
    `email_notifications` BOOLEAN DEFAULT TRUE COMMENT 'Email notifications',
    `sms_notifications` BOOLEAN DEFAULT FALSE COMMENT 'SMS notifications',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. STUDENT PREFERENCES TABLE
-- =====================================================================
CREATE TABLE IF NOT EXISTS `student_preferences` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `theme` ENUM('light', 'dark', 'auto') DEFAULT 'light' COMMENT 'UI theme preference',
    `dashboard_view` ENUM('grid', 'list', 'compact') DEFAULT 'grid' COMMENT 'Dashboard layout view',
    `language` VARCHAR(10) DEFAULT 'en' COMMENT 'Preferred language',
    `timezone` VARCHAR(50) DEFAULT 'Asia/Kolkata' COMMENT 'User timezone',
    `items_per_page` INT DEFAULT 10 COMMENT 'Pagination items per page',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. ACCOUNT DELETION REQUESTS TABLE
-- =====================================================================
CREATE TABLE IF NOT EXISTS `account_deletion_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL COMMENT 'Reason for deletion request',
    `feedback` TEXT NULL COMMENT 'Additional feedback',
    `status` ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending' COMMENT 'Request status',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When request was reviewed',
    `reviewed_by` BIGINT UNSIGNED NULL COMMENT 'Admin who reviewed request',
    `rejection_reason` TEXT NULL COMMENT 'Reason for rejection if applicable',
    `completion_notes` TEXT NULL COMMENT 'Notes when completed',
    `data_archived` BOOLEAN DEFAULT FALSE COMMENT 'Whether user data was backed up',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_requested_at` (`requested_at`),
    KEY `unique_pending_request` (`student_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. STUDENT ACTIVITY LOGS TABLE
-- =====================================================================
CREATE TABLE IF NOT EXISTS `student_activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `activity_type` ENUM(
        'exam_attempted',
        'exam_completed',
        'certificate_downloaded',
        'material_viewed',
        'material_downloaded',
        'material_bookmarked',
        'material_rated',
        'profile_updated',
        'password_changed',
        'login',
        'logout',
        'notification_opened',
        'community_post_created',
        'community_comment_created'
    ) NOT NULL,
    `description` VARCHAR(500) NULL,
    `related_entity_type` VARCHAR(50) NULL COMMENT 'Type of related entity (exam, material, certificate, etc.)',
    `related_entity_id` BIGINT UNSIGNED NULL COMMENT 'ID of related entity',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `metadata` JSON NULL COMMENT 'Additional data as JSON',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_activity_type` (`activity_type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_related_entity` (`related_entity_type`, `related_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. DATA EXPORT REQUESTS TABLE
-- =====================================================================
CREATE TABLE IF NOT EXISTS `data_export_requests` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `export_type` ENUM('full', 'profile', 'activity', 'results', 'certificates', 'materials') DEFAULT 'full',
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'expired') DEFAULT 'pending',
    `file_path` VARCHAR(500) NULL COMMENT 'Path to exported file',
    `file_size` BIGINT NULL COMMENT 'Size in bytes',
    `download_count` INT DEFAULT 0,
    `expires_at` TIMESTAMP NULL COMMENT 'Expiration date for download link',
    `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `error_message` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_requested_at` (`requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Set Foreign Key Checks Back On
-- =====================================================================
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================================
-- Insert Default Preferences for Existing Users (Optional)
-- =====================================================================
-- Uncomment to auto-create preferences for all existing students
/*
INSERT IGNORE INTO `student_notification_settings` (student_id, exam_reminder, result_notification, system_notification)
SELECT id, TRUE, TRUE, TRUE FROM users WHERE role_id = 4;

INSERT IGNORE INTO `student_preferences` (student_id, theme, dashboard_view, language, timezone)
SELECT id, 'light', 'grid', 'en', 'Asia/Kolkata' FROM users WHERE role_id = 4;
*/

-- =====================================================================
-- Verify Tables Created
-- =====================================================================
-- Run this to verify:
-- SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
-- WHERE TABLE_SCHEMA='exams_lms' AND TABLE_NAME IN 
-- ('student_notification_settings', 'student_preferences', 'account_deletion_requests', 
--  'student_activity_logs', 'data_export_requests', 'login_logs');
