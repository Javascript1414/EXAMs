-- Phase 20: Certificate System Enhancement
-- Ensure certificates table has all necessary fields

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- Check and update certificates table if needed
ALTER TABLE `certificates` 
ADD COLUMN IF NOT EXISTS `grade` VARCHAR(2) DEFAULT 'D' AFTER `percentage`,
ADD COLUMN IF NOT EXISTS `total_marks` DECIMAL(8,2) DEFAULT 0 AFTER `score`,
ADD COLUMN IF NOT EXISTS `obtained_marks` DECIMAL(8,2) DEFAULT 0 AFTER `total_marks`,
MODIFY COLUMN `status` ENUM('active', 'revoked', 'pending') DEFAULT 'pending';

-- Add index for faster queries
ALTER TABLE `certificates` ADD INDEX IF NOT EXISTS `idx_cert_status` (`status`);

SET FOREIGN_KEY_CHECKS=1;
