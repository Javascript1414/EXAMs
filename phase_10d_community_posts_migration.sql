-- ---------------------------------------------------------
-- PHASE 10D: ADD MISSING COLUMNS TO COMMUNITY_POSTS TABLE
-- Adds post_type and image_path columns required by community features
-- ---------------------------------------------------------

ALTER TABLE `community_posts` 
ADD COLUMN IF NOT EXISTS `post_type` VARCHAR(50) DEFAULT 'general' COMMENT 'Type of post: general, doubt, discussion, achievement, project, announcement' AFTER `content`,
ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Path to post image if any' AFTER `post_type`,
ADD COLUMN IF NOT EXISTS `is_solution` BOOLEAN DEFAULT FALSE COMMENT 'Whether this post is marked as solution (for doubt type)' AFTER `is_locked`;

-- Add indexes for better query performance
ALTER TABLE `community_posts` 
ADD INDEX IF NOT EXISTS `idx_post_type` (`post_type`),
ADD INDEX IF NOT EXISTS `idx_is_locked` (`is_locked`);

-- ---------------------------------------------------------
-- IMPORTANT: Run this migration in your database!
-- Execute in phpMyAdmin or MySQL client
-- ---------------------------------------------------------
