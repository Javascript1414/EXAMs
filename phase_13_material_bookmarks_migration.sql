-- =========================================================
-- PHASE 13: Material Bookmarks System
-- =========================================================
-- Database: exams_lms
-- Purpose: Add material bookmarks functionality for students
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;

-- Create material_bookmarks table
CREATE TABLE IF NOT EXISTS `material_bookmarks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `study_materials`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_material_bookmark` (`user_id`, `material_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_material_id` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
