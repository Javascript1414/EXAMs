<?php
/**
 * API - Toggle Video Like
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$videoId = intval($data['video_id'] ?? 0);
$liked = boolval($data['liked'] ?? false);
$userId = $_SESSION['user_id'];

if (!$videoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing video ID']);
    exit;
}

try {
    if ($liked) {
        // Add like
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO video_likes (video_id, student_id, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$videoId, $userId]);
    } else {
        // Remove like
        $stmt = $pdo->prepare("
            DELETE FROM video_likes 
            WHERE video_id = ? AND student_id = ?
        ");
        $stmt->execute([$videoId, $userId]);
    }
    
    echo json_encode([
        'success' => true,
        'liked' => $liked
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
