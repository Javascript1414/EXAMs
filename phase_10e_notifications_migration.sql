-- Phase 10e Migration: Enhance Notifications Table
-- Adds missing columns for advanced notification features

-- Add columns if they don't exist
ALTER TABLE `notifications` 
ADD COLUMN IF NOT EXISTS `notification_type` VARCHAR(50) DEFAULT 'general' AFTER `message`,
ADD COLUMN IF NOT EXISTS `target_type` VARCHAR(50) DEFAULT 'all' AFTER `notification_type`,
ADD COLUMN IF NOT EXISTS `target_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `target_type`,
ADD COLUMN IF NOT EXISTS `action_url` VARCHAR(255) NULL DEFAULT NULL AFTER `target_id`,
ADD COLUMN IF NOT EXISTS `icon` VARCHAR(50) DEFAULT 'bell' AFTER `action_url`,
ADD COLUMN IF NOT EXISTS `created_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `icon`,
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) DEFAULT 'sent' AFTER `created_by`;

-- Add foreign key constraint for created_by if it doesn't exist
SET @constraint_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_NAME = 'notifications' AND CONSTRAINT_NAME = 'fk_notifications_created_by'
);

SET @sql = IF(@constraint_exists = 0, 
    'ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL',
    'SELECT "Foreign key already exists"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create notification_recipients table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notification_recipients` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `notification_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_notification_user` (`notification_id`, `user_id`),
    INDEX `idx_user_read` (`user_id`, `is_read`),
    UNIQUE KEY `unique_notification_user` (`notification_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
