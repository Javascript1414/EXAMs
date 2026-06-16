-- =========================================================
-- PHASE 11: Material Ratings System
-- =========================================================
-- Database: exams_lms
-- Purpose: Add material ratings functionality
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;

-- Add missing columns to study_materials table
ALTER TABLE `study_materials`
ADD COLUMN `view_count` INT UNSIGNED DEFAULT 0 AFTER `youtube_url`,
ADD COLUMN `download_count` INT UNSIGNED DEFAULT 0 AFTER `view_count`,
ADD COLUMN `average_rating` DECIMAL(3,2) DEFAULT 0.00 AFTER `download_count`,
ADD COLUMN `is_featured` BOOLEAN DEFAULT FALSE AFTER `average_rating`;

-- Create material_ratings table
CREATE TABLE IF NOT EXISTS `material_ratings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `rating` INT UNSIGNED NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review_text` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`material_id`) REFERENCES `study_materials`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_material_rating` (`material_id`, `user_id`),
    INDEX `idx_material_id` (`material_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create trigger to update average_rating when ratings are inserted
DROP TRIGGER IF EXISTS `update_material_average_rating_after_insert`;
CREATE TRIGGER `update_material_average_rating_after_insert`
AFTER INSERT ON `material_ratings`
FOR EACH ROW
UPDATE `study_materials` SET `average_rating` = (SELECT ROUND(AVG(`rating`), 2) FROM `material_ratings` WHERE `material_id` = NEW.`material_id`) WHERE `id` = NEW.`material_id`;

-- Create trigger to update average_rating when ratings are updated
DROP TRIGGER IF EXISTS `update_material_average_rating_after_update`;
CREATE TRIGGER `update_material_average_rating_after_update`
AFTER UPDATE ON `material_ratings`
FOR EACH ROW
UPDATE `study_materials` SET `average_rating` = (SELECT ROUND(AVG(`rating`), 2) FROM `material_ratings` WHERE `material_id` = NEW.`material_id`) WHERE `id` = NEW.`material_id`;

-- Create trigger to update average_rating when ratings are deleted
DROP TRIGGER IF EXISTS `update_material_average_rating_after_delete`;
CREATE TRIGGER `update_material_average_rating_after_delete`
AFTER DELETE ON `material_ratings`
FOR EACH ROW
UPDATE `study_materials` SET `average_rating` = (SELECT ROUND(AVG(`rating`), 2) FROM `material_ratings` WHERE `material_id` = OLD.`material_id`) WHERE `id` = OLD.`material_id`;

SET FOREIGN_KEY_CHECKS=1;
