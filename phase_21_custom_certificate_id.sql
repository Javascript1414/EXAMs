-- Phase 21: Custom Certificate ID Format
-- Implements: COURSECODE/YY-YY/Y/REGISTRATION/EXAMSEQ

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- Add columns to certificates table
ALTER TABLE `certificates` 
ADD COLUMN IF NOT EXISTS `course_code` VARCHAR(20) AFTER `certificate_id`,
ADD COLUMN IF NOT EXISTS `academic_year` VARCHAR(10) AFTER `course_code`,
ADD COLUMN IF NOT EXISTS `student_registration` VARCHAR(50) AFTER `academic_year`,
ADD COLUMN IF NOT EXISTS `exam_sequence` INT UNSIGNED DEFAULT 1 AFTER `student_registration`,
ADD COLUMN IF NOT EXISTS `grade` VARCHAR(2) DEFAULT 'D' AFTER `percentage`,
ADD COLUMN IF NOT EXISTS `total_marks` DECIMAL(8,2) DEFAULT 0 AFTER `grade`,
ADD COLUMN IF NOT EXISTS `obtained_marks` DECIMAL(8,2) DEFAULT 0 AFTER `total_marks`;

-- Create index for tracking exam sequences
ALTER TABLE `certificates` ADD INDEX IF NOT EXISTS `idx_student_sequence` (`student_id`, `exam_sequence`);
ALTER TABLE `certificates` ADD INDEX IF NOT EXISTS `idx_cert_format` (`course_code`, `academic_year`, `student_registration`);

-- Add course_code to trades table (shortname like CITS, COPA, etc.)
ALTER TABLE `trades` 
ADD COLUMN IF NOT EXISTS `trade_code` VARCHAR(20) UNIQUE AFTER `trade_name`;

-- Update status enum to include pending
ALTER TABLE `certificates` 
MODIFY COLUMN `status` ENUM('active', 'revoked', 'pending') DEFAULT 'pending';

SET FOREIGN_KEY_CHECKS=1;

-- Populate trade codes if not set
UPDATE `trades` SET `trade_code` = 'CITS' WHERE `trade_code` IS NULL AND `trade_name` LIKE '%CITS%';
UPDATE `trades` SET `trade_code` = 'COPA' WHERE `trade_code` IS NULL AND `trade_name` LIKE '%COPA%';
UPDATE `trades` SET `trade_code` = 'DDVT' WHERE `trade_code` IS NULL AND `trade_name` LIKE '%DDVT%';
UPDATE `trades` SET `trade_code` = 'ACIT' WHERE `trade_code` IS NULL AND `trade_name` LIKE '%ACIT%';
