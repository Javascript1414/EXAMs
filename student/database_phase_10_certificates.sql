-- ---------------------------------------------------------
-- Phase 10 Database Migration: Certificates System
-- Database: exams_lms
-- ---------------------------------------------------------

USE `exams_lms`;

SET FOREIGN_KEY_CHECKS=0;

-- Drop legacy structure if it exists from the architecture freeze
DROP TABLE IF EXISTS `certificates`;

CREATE TABLE `certificates` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `certificate_id` VARCHAR(100) NOT NULL UNIQUE,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `exam_id` BIGINT UNSIGNED NOT NULL,
    `result_id` BIGINT UNSIGNED NOT NULL,
    `score` DECIMAL(8,2) NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL,
    `issued_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `verification_code` VARCHAR(100) NOT NULL UNIQUE,
    `generated_by` BIGINT UNSIGNED NULL,
    `status` ENUM('active', 'revoked') DEFAULT 'active',
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`result_id`) REFERENCES `results`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`generated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_cert_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;