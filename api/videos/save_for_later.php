<?php
/**
 * API - Save Video for Later
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$videoId = intval($data['video_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$videoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing video ID']);
    exit;
}

try {
    // Check if already saved
    $stmt = $pdo->prepare("
        SELECT id FROM video_saves 
        WHERE video_id = ? AND student_id = ?
    ");
    $stmt->execute([$videoId, $userId]);
    
    if ($stmt->fetch()) {
        // Already saved, remove it
        $stmt = $pdo->prepare("
            DELETE FROM video_saves 
            WHERE video_id = ? AND student_id = ?
        ");
        $stmt->execute([$videoId, $userId]);
        $saved = false;
    } else {
        // Save it
        $stmt = $pdo->prepare("
            INSERT INTO video_saves (video_id, student_id, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$videoId, $userId]);
        $saved = true;
    }
    
    echo json_encode([
        'success' => true,
        'saved' => $saved,
        'message' => $saved ? 'Video saved for later' : 'Video removed from saves'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
