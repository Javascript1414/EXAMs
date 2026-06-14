-- ---------------------------------------------------------
-- PHASE 10C: CREATE NOTIFICATION_RECIPIENTS TABLE
-- Adds the missing notification_recipients table required by the notification system
-- ---------------------------------------------------------

CREATE TABLE IF NOT EXISTS `notification_recipients` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `notification_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_recipient` (`notification_id`, `user_id`),
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- ADDITIONAL: Modify existing notifications table structure
-- Remove old columns if they exist and add icon column if needed
-- ---------------------------------------------------------

ALTER TABLE `notifications` ADD COLUMN IF NOT EXISTS `icon` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Font Awesome icon class' AFTER `message`;

-- ---------------------------------------------------------
-- IMPORTANT: Run this migration in your database!
-- Execute in phpMyAdmin or MySQL client
-- ---------------------------------------------------------
