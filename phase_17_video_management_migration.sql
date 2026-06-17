-- Phase 17: Video Management System
-- Creates videos table for admin video uploads and student viewing

CREATE TABLE IF NOT EXISTS `videos` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `video_name` VARCHAR(255) NOT NULL,
    `description` LONGTEXT,
    `course_id` BIGINT UNSIGNED NOT NULL,
    `instructor_id` BIGINT UNSIGNED NOT NULL,
    `video_file` VARCHAR(255) NOT NULL COMMENT 'Path to uploaded video file',
    `thumbnail` VARCHAR(255) COMMENT 'Path to thumbnail image',
    `duration` INT UNSIGNED COMMENT 'Video duration in seconds',
    `views` INT UNSIGNED DEFAULT 0,
    `rating` DECIMAL(2, 1) DEFAULT 4.5,
    `total_ratings` INT UNSIGNED DEFAULT 0,
    `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_course_id` (`course_id`),
    INDEX `idx_instructor_id` (`instructor_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video Watch History Table
CREATE TABLE IF NOT EXISTS `video_watch_history` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `video_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `watched_seconds` INT UNSIGNED DEFAULT 0,
    `total_watched` INT UNSIGNED DEFAULT 0 COMMENT 'Total seconds watched',
    `watch_percentage` DECIMAL(3, 2) DEFAULT 0.00,
    `last_watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_video_student` (`video_id`, `student_id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_last_watched` (`last_watched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video Ratings Table
CREATE TABLE IF NOT EXISTS `video_ratings` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `video_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `rating` TINYINT UNSIGNED NOT NULL COMMENT '1-5 stars',
    `comment` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_video_rating` (`video_id`, `student_id`),
    FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_video_id` (`video_id`),
    INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample videos for demonstration
INSERT IGNORE INTO `videos` (`id`, `video_name`, `description`, `course_id`, `instructor_id`, `video_file`, `thumbnail`, `duration`, `views`, `rating`, `total_ratings`, `status`) VALUES
(1, 'Mathematics Fundamentals', 'Learn basic math concepts from scratch', 1, 1, '/uploads/videos/math-101.mp4', '/uploads/videos/thumbs/math-101.jpg', 3600, 156, 4.7, 23, 'active'),
(2, 'Physics: Motion and Forces', 'Understanding motion and Newton laws', 2, 1, '/uploads/videos/physics-101.mp4', '/uploads/videos/thumbs/physics-101.jpg', 4200, 98, 4.5, 18, 'active'),
(3, 'Chemistry: Atomic Structure', 'Explore the structure of atoms', 3, 1, '/uploads/videos/chemistry-101.mp4', '/uploads/videos/thumbs/chemistry-101.jpg', 3900, 67, 4.6, 12, 'active'),
(4, 'Biology: Cell Biology', 'Understanding cell structure and function', 4, 1, '/uploads/videos/biology-101.mp4', '/uploads/videos/thumbs/biology-101.jpg', 4500, 145, 4.8, 31, 'active'),
(5, 'English Literature: Shakespeare', 'Analysis of Shakespeare works', 5, 1, '/uploads/videos/english-101.mp4', '/uploads/videos/thumbs/english-101.jpg', 5100, 89, 4.4, 15, 'active');

SET FOREIGN_KEY_CHECKS=1;
