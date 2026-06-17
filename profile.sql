-- Profile Tables for User Approval System
-- This file creates tables for user profiles and approval workflow

-- 1. Add approval columns to users table (if they don't exist)
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `status`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `approved_by` BIGINT UNSIGNED NULL AFTER `approval_status`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL AFTER `approved_by`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT NULL AFTER `approved_at`;

-- 2. Create user_profiles table for extended profile information
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `bio` TEXT NULL COMMENT 'Short biography or about me section',
    `profile_photo_path` VARCHAR(500) NULL COMMENT 'Path to profile photo in uploads',
    `cover_photo_path` VARCHAR(500) NULL COMMENT 'Path to cover photo in uploads',
    `phone_verified` BOOLEAN DEFAULT FALSE,
    `phone_verified_at` TIMESTAMP NULL,
    `aadhaar_number` VARCHAR(20) NULL,
    `father_name` VARCHAR(150) NULL,
    `mother_name` VARCHAR(150) NULL,
    `emergency_contact` VARCHAR(20) NULL,
    `emergency_contact_name` VARCHAR(150) NULL,
    `social_media_links` JSON NULL COMMENT 'JSON array of social media profiles',
    `skills` JSON NULL COMMENT 'JSON array of skills',
    `certifications` JSON NULL COMMENT 'JSON array of certifications',
    `about_education` TEXT NULL,
    `about_experience` TEXT NULL,
    `website` VARCHAR(255) NULL,
    `location` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    FULLTEXT INDEX `ft_bio` (`bio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create admin_approvals_log table for audit trail
CREATE TABLE IF NOT EXISTS `admin_approvals_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `admin_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('approved', 'rejected', 'resubmitted') NOT NULL,
    `reason` TEXT NULL COMMENT 'Reason for approval or rejection',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create verification_documents table for storing proof of identity
CREATE TABLE IF NOT EXISTS `verification_documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `document_type` ENUM('aadhaar', 'pan', 'voter_id', 'license', 'passport', 'other') NOT NULL,
    `document_path` VARCHAR(500) NOT NULL,
    `verification_status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    `verified_by` BIGINT UNSIGNED NULL,
    `verified_at` TIMESTAMP NULL,
    `rejection_reason` TEXT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_document_type` (`document_type`),
    INDEX `idx_verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
