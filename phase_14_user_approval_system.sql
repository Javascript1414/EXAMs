-- Phase 14: User Approval System
-- Adds approval workflow for student registration

-- 1. Add approval_status to users table
ALTER TABLE `users` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `status`;
ALTER TABLE `users` ADD COLUMN `approved_by` BIGINT UNSIGNED NULL AFTER `approval_status`;
ALTER TABLE `users` ADD COLUMN `approved_at` TIMESTAMP NULL AFTER `approved_by`;
ALTER TABLE `users` ADD COLUMN `rejection_reason` TEXT NULL AFTER `approved_at`;

-- Add foreign key for approved_by
ALTER TABLE `users` ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- 2. Create user_profiles table for additional profile information
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
    `bio` TEXT NULL,
    `profile_photo_path` VARCHAR(500) NULL,
    `phone_verified` BOOLEAN DEFAULT FALSE,
    `phone_verified_at` TIMESTAMP NULL,
    `aadhaar_number` VARCHAR(20) NULL,
    `father_name` VARCHAR(150) NULL,
    `mother_name` VARCHAR(150) NULL,
    `emergency_contact` VARCHAR(20) NULL,
    `emergency_contact_name` VARCHAR(150) NULL,
    `social_media_links` JSON NULL,
    `skills` JSON NULL,
    `certifications` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create admin_approvals_log table for audit trail
CREATE TABLE IF NOT EXISTS `admin_approvals_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `admin_id` BIGINT UNSIGNED NOT NULL,
    `action` ENUM('approved', 'rejected', 'resubmitted') NOT NULL,
    `reason` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
