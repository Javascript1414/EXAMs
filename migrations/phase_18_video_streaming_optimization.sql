-- Video Streaming Optimization - Database Schema Updates
-- Adds bandwidth tracking and streaming statistics

-- Check and add estimated_bandwidth column if not exists
ALTER TABLE video_watch_history
ADD COLUMN IF NOT EXISTS estimated_bandwidth FLOAT DEFAULT NULL COMMENT 'Estimated user bandwidth in bytes/sec';

-- Check and add last_bandwidth_test column if not exists  
ALTER TABLE video_watch_history
ADD COLUMN IF NOT EXISTS last_bandwidth_test TIMESTAMP DEFAULT NULL COMMENT 'Last time bandwidth was tested';

-- Check and add quality_used column if not exists
ALTER TABLE video_watch_history
ADD COLUMN IF NOT EXISTS quality_used VARCHAR(20) DEFAULT 'auto' COMMENT 'Video quality used for playback (240,360,480,720,1080)';

-- Check and add buffering_events column if not exists
ALTER TABLE video_watch_history
ADD COLUMN IF NOT EXISTS buffering_events INT DEFAULT 0 COMMENT 'Number of buffering events during playback';

-- Create index for bandwidth queries
ALTER TABLE video_watch_history
ADD INDEX IF NOT EXISTS idx_bandwidth_recent (student_id, last_bandwidth_test);

-- Create a new table for streaming metrics (optional, for detailed analytics)
CREATE TABLE IF NOT EXISTS video_streaming_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    video_id INT NOT NULL,
    session_id VARCHAR(100),
    initial_bandwidth FLOAT,
    avg_bandwidth FLOAT,
    peak_bandwidth FLOAT,
    quality_switches INT DEFAULT 0,
    buffering_count INT DEFAULT 0,
    total_buffering_time INT DEFAULT 0,
    playback_duration INT,
    completion_percentage FLOAT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    INDEX idx_student_video (student_id, video_id),
    INDEX idx_started_at (started_at)
) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add comments to video_watch_history table
ALTER TABLE video_watch_history COMMENT = 'Tracks student video watch history with streaming metrics';

-- Log the migration
INSERT INTO system_logs (action, details, created_at)
VALUES ('video_streaming_optimization', 'Added bandwidth tracking columns and streaming metrics table', NOW())
ON DUPLICATE KEY UPDATE created_at = NOW();
