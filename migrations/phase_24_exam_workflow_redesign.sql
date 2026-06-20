-- Phase 24: Exam Workflow Redesign
-- Simplify Theory-Practical Exam Linking
-- Add publishing workflow and tracking columns

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- 1. Add new columns to practical_exams for linking and publishing
ALTER TABLE practical_exams 
ADD COLUMN theory_exam_id BIGINT UNSIGNED NULL COMMENT 'Foreign key to exams table for theory exam' AFTER exam_id,
ADD COLUMN published BOOLEAN DEFAULT FALSE COMMENT 'Whether exam is published for students' AFTER status,
ADD COLUMN published_at TIMESTAMP NULL COMMENT 'When exam was published' AFTER published,
ADD UNIQUE KEY `unique_theory_exam_id` (`theory_exam_id`) COMMENT 'One practical per theory exam';

-- 2. Create index for theory exam linking
ALTER TABLE practical_exams 
ADD INDEX `idx_theory_exam_id` (`theory_exam_id`),
ADD FOREIGN KEY (`theory_exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE;

-- 3. Add constraint to exams table to mark them as published (if not exists)
ALTER TABLE exams 
ADD COLUMN published BOOLEAN DEFAULT FALSE COMMENT 'Whether exam is published for students' 
AFTER status;

-- 4. Add column to track if practical marks are required for certificate
ALTER TABLE certificates 
ADD COLUMN requires_practical_marks BOOLEAN DEFAULT TRUE COMMENT 'Whether this certificate requires practical marks' 
AFTER exam_id;

SET FOREIGN_KEY_CHECKS=1;
