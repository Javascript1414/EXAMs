<?php
/**
 * Video API - Log Watch Event
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

try {
    $user_id = $_SESSION['user_id'];
    $video_id = (int)($_POST['video_id'] ?? 0);
    $watched_seconds = (int)($_POST['watched_seconds'] ?? 0);
    
    if (!$video_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Video ID required']);
        exit;
    }
    
    // Get video duration to calculate watch percentage
    $stmt = $pdo->prepare("SELECT duration FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit;
    }
    
    $watch_percentage = ($watched_seconds / $video['duration']) * 100;
    
    // Insert or update watch history
    $stmt = $pdo->prepare("
        INSERT INTO video_watch_history (video_id, student_id, watched_seconds, total_watched, watch_percentage)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            watched_seconds = VALUES(watched_seconds),
            total_watched = VALUES(total_watched),
            watch_percentage = VALUES(watch_percentage),
            last_watched_at = NOW()
    ");
    $stmt->execute([$video_id, $user_id, $watched_seconds, $watched_seconds, $watch_percentage]);
    
    // Update video view count
    $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ? LIMIT 1")->execute([$video_id]);
    
    echo json_encode(['success' => true, 'message' => 'Watch event logged']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
