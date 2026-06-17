<?php
/**
 * Video API - Log Watch Event
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    $video_id = $_POST['video_id'] ?? null;
    $watched_seconds = $_POST['watched_seconds'] ?? 0;
    
    if (!$video_id) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Video ID required']));
    }
    
    // Save watch event
    $stmt = $pdo->prepare("
        INSERT INTO video_watch_history (user_id, video_id, watched_seconds, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE watched_seconds = ?
    ");
    $stmt->execute([$user_id, $video_id, $watched_seconds, $watched_seconds]);
    
    echo json_encode(['success' => true, 'message' => 'Watch event logged']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
