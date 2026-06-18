-- YouTube-Style Video Cards - Database Schema
-- Adds tables and columns for likes, saves, downloads, and reports

-- Table for video likes
CREATE TABLE IF NOT EXISTS video_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_video_student (video_id, student_id),
    INDEX idx_video (video_id),
    INDEX idx_student (student_id)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Tracks video likes by students';

-- Table for saved videos (Watch Later)
CREATE TABLE IF NOT EXISTS video_saves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    student_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_save (video_id, student_id),
    INDEX idx_video (video_id),
    INDEX idx_student (student_id)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Tracks saved videos for watch later';

-- Table for download logs
CREATE TABLE IF NOT EXISTS video_downloads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    student_id INT NOT NULL,
    downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_video_student (video_id, student_id),
    INDEX idx_downloaded_at (downloaded_at)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Tracks video downloads';

-- Table for video reports (abuse/copyright)
CREATE TABLE IF NOT EXISTS video_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    video_id INT NOT NULL,
    reported_by INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    INDEX idx_video (video_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Tracks reported videos for moderation';

-- Add columns to videos table for enhanced card display
ALTER TABLE videos
ADD COLUMN IF NOT EXISTS thumbnail VARCHAR(255) COMMENT 'Path to video thumbnail image',
ADD COLUMN IF NOT EXISTS video_quality VARCHAR(10) DEFAULT '1080' COMMENT 'Video quality (240, 360, 480, 720, 1080, 4k)',
ADD COLUMN IF NOT EXISTS instructor_verified BOOLEAN DEFAULT FALSE COMMENT 'Is instructor verified',
ADD COLUMN IF NOT EXISTS likes_count INT DEFAULT 0 COMMENT 'Number of likes (cached)',
ADD COLUMN IF NOT EXISTS saves_count INT DEFAULT 0 COMMENT 'Number of saves (cached)';

-- Add index to videos table for verified instructors
ALTER TABLE videos
ADD INDEX IF NOT EXISTS idx_instructor_verified (instructor_verified, created_at);

-- Update video watch history to include quality info
ALTER TABLE video_watch_history
ADD COLUMN IF NOT EXISTS quality_played VARCHAR(10) COMMENT 'Quality played (240, 360, 480, 720, 1080)',
ADD COLUMN IF NOT EXISTS device_type VARCHAR(50) COMMENT 'Device type (mobile, tablet, desktop)';

-- Create view for video stats (simplified without foreign keys)
CREATE OR REPLACE VIEW video_with_stats AS
SELECT 
    v.id,
    v.video_name,
    v.description,
    v.duration,
    v.views,
    v.rating,
    v.created_at,
    COALESCE((SELECT COUNT(*) FROM video_likes vl WHERE vl.video_id = v.id), 0) as likes_count,
    COALESCE((SELECT COUNT(*) FROM video_saves vs WHERE vs.video_id = v.id), 0) as saves_count,
    COALESCE((SELECT COUNT(*) FROM video_reports vr WHERE vr.video_id = v.id AND vr.status = 'pending'), 0) as pending_reports,
    u.full_name as instructor,
    v.instructor_verified,
    c.course_name as course
FROM videos v
LEFT JOIN users u ON v.instructor_id = u.id
LEFT JOIN courses c ON v.course_id = c.course_id
WHERE v.status = 'active';
