<?php
/**
 * Video Player Page - Opens in new frame/window
 * Displays selected video with full details
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
requireLogin();

// Get video ID from URL
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$video_id) {
    die('No video selected');
}

// Fetch video details from database
require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $pdo->prepare("
        SELECT 
            v.id as video_id,
            v.video_name as title,
            v.description,
            v.video_file,
            v.duration,
            v.views,
            v.rating,
            v.total_ratings,
            v.created_at,
            c.course_name as course,
            u.full_name as instructor,
            COALESCE(wh.watch_percentage, 0) as progress
        FROM videos v
        LEFT JOIN courses c ON v.course_id = c.course_id
        LEFT JOIN users u ON v.instructor_id = u.id
        LEFT JOIN video_watch_history wh ON v.id = wh.video_id AND wh.student_id = ?
        WHERE v.id = ? AND v.status = 'active'
    ");
    
    $stmt->execute([$_SESSION['user_id'], $video_id]);
    $video = $stmt->fetch();
    
    if (!$video) {
        die('Video not found');
    }
    
    // Update views count
    $updateStmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $updateStmt->execute([$video_id]);
    
} catch (PDOException $e) {
    die('Error loading video: ' . htmlspecialchars($e->getMessage()));
}

// Format duration
function formatDuration($seconds) {
    if (!$seconds || $seconds <= 0) return '0:00';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = floor($seconds % 60);
    
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }
    return sprintf('%d:%02d', $minutes, $secs);
}

$page_title = htmlspecialchars($video['title']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f6f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: #333;
        }

        body[data-theme="dark"] {
            background: #0f0f0f;
            color: #fff;
        }

        .video-player-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 16px;
        }

        .video-player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        body[data-theme="dark"] .video-player-header {
            border-bottom-color: #333;
        }

        .player-back-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            transition: all 0.2s ease;
        }

        body[data-theme="dark"] .player-back-btn {
            border-color: #444;
            color: #fff;
        }

        .player-back-btn:hover {
            background: #e8eaed;
        }

        body[data-theme="dark"] .player-back-btn:hover {
            background: #2a2a2a;
        }

        .player-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
        }

        body[data-theme="dark"] .player-close-btn {
            color: #aaa;
        }

        .player-close-btn:hover {
            color: #000;
        }

        body[data-theme="dark"] .player-close-btn:hover {
            color: #fff;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        .video-player {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
        }

        .video-info {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        body[data-theme="dark"] .video-info {
            background: #1a1a1a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .video-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #222;
        }

        body[data-theme="dark"] .video-title {
            color: #fff;
        }

        .video-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        body[data-theme="dark"] .video-meta {
            border-bottom-color: #333;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-label {
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }

        body[data-theme="dark"] .meta-label {
            color: #aaa;
        }

        .meta-value {
            font-size: 14px;
            font-weight: 500;
            color: #222;
        }

        body[data-theme="dark"] .meta-value {
            color: #fff;
        }

        .video-description {
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }

        body[data-theme="dark"] .video-description {
            color: #ccc;
        }

        .video-course {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }

        body[data-theme="dark"] .video-course {
            color: #aaa;
        }

        @media (max-width: 768px) {
            .video-player-page {
                padding: 8px;
            }

            .video-player-header {
                margin-bottom: 12px;
                padding-bottom: 12px;
            }

            .video-title {
                font-size: 20px;
            }

            .video-meta {
                gap: 12px;
                margin-bottom: 12px;
                padding-bottom: 12px;
            }

            .video-info {
                padding: 12px;
                margin-bottom: 12px;
            }

            .player-back-btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="video-player-page">
        <!-- Header with Back Button -->
        <div class="video-player-header">
            <button class="player-back-btn" onclick="window.history.back()">← Back to Videos</button>
            <button class="player-close-btn" onclick="window.close()">✕</button>
        </div>

        <!-- Video Player -->
        <div class="video-container">
            <?php if ($video['video_file'] && strpos($video['video_file'], 'youtube:') === 0): ?>
                <!-- YouTube Video -->
                <?php 
                    $youtubeId = substr($video['video_file'], 8);
                ?>
                <iframe class="video-player" 
                    src="https://www.youtube.com/embed/<?= htmlspecialchars($youtubeId) ?>?autoplay=1&rel=0&modestbranding=1&fs=1" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            <?php else: ?>
                <!-- Regular MP4 Video with Adaptive Streaming -->
                <video id="adaptiveVideoPlayer" class="video-player" controls>
                    <source src="<?= BASE_URL ?>/api/videos/stream.php?id=<?= intval($video['video_id']) ?>&quality=auto" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                
                <!-- Quality Status Indicator -->
                <div id="qualityIndicator" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #fff; padding: 8px 12px; border-radius: 4px; font-size: 12px; z-index: 10; display: none;">
                    <span id="qualityText">Loading...</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Video Information -->
        <div class="video-info">
            <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>

            <div class="video-meta">
                <div class="meta-item">
                    <span class="meta-label">Channel:</span>
                    <span class="meta-value"><?= htmlspecialchars($video['instructor'] ?? 'Unknown') ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Views:</span>
                    <span class="meta-value"><?= number_format($video['views'] ?? 0) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Duration:</span>
                    <span class="meta-value"><?= formatDuration($video['duration']) ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Rating:</span>
                    <span class="meta-value">⭐ <?= round($video['rating'] ?? 0, 1) ?>/5</span>
                </div>
            </div>

            <p class="video-description"><?= htmlspecialchars($video['description'] ?? 'No description available') ?></p>
            
            <?php if (!empty($video['course'])): ?>
                <p class="video-course">📚 Course: <?= htmlspecialchars($video['course']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/adaptive-streaming.js"></script>
    <script>
        // Set BASE_URL for adaptive streaming
        window.BASE_URL = '<?= BASE_URL ?>';
        
        // Initialize adaptive streaming when video player is ready
        document.addEventListener('DOMContentLoaded', async function() {
            // Apply theme
            const theme = localStorage.getItem('theme') || 'light';
            document.body.setAttribute('data-theme', theme);
            
            // Initialize adaptive streaming for local videos
            const videoPlayer = document.getElementById('adaptiveVideoPlayer');
            if (videoPlayer) {
                const videoId = <?= intval($video['video_id']) ?>;
                const streamer = new AdaptiveVideoStreamer(videoId);
                await streamer.init(videoPlayer);
                
                // Show quality indicator
                const indicator = document.getElementById('qualityIndicator');
                const qualityText = document.getElementById('qualityText');
                
                if (indicator && qualityText) {
                    indicator.style.display = 'block';
                    
                    // Update quality display
                    const updateQualityDisplay = () => {
                        const info = streamer.getQualityInfo();
                        const qualityLabel = info.profiles[info.current]?.label || 'Auto';
                        const bandwidth = (info.bandwidth * 8 / 1000000).toFixed(1);
                        qualityText.textContent = `${qualityLabel} • ${bandwidth} Mbps`;
                    };
                    
                    updateQualityDisplay();
                    videoPlayer.addEventListener('play', updateQualityDisplay);
                    
                    // Monitor quality changes
                    setInterval(updateQualityDisplay, 5000);
                }
                
                // Store streamer instance globally for debugging
                window.currentStreamer = streamer;
            }
        });
    </script>
</body>
</html>
