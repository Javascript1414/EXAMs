<?php
/**
 * Video API - Get Watch History
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

try {
    $stmt = $pdo->prepare("
        SELECT 
            v.id as video_id,
            v.video_name as title,
            v.duration,
            wh.total_watched as watched,
            wh.last_watched_at as timestamp
        FROM video_watch_history wh
        LEFT JOIN videos v ON wh.video_id = v.id
        WHERE wh.student_id = ?
        ORDER BY wh.last_watched_at DESC
        LIMIT 10
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $history = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'history' => $history]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
