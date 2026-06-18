<?php
/**
 * API - Report Video
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$videoId = intval($data['video_id'] ?? 0);
$reason = sanitizeInput($data['reason'] ?? '');
$userId = $_SESSION['user_id'];

if (!$videoId || !$reason) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if already reported by this user
    $stmt = $pdo->prepare("
        SELECT id FROM video_reports
        WHERE video_id = ? AND reported_by = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$videoId, $userId]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'You have already reported this video recently'
        ]);
        exit;
    }
    
    // Insert report
    $stmt = $pdo->prepare("
        INSERT INTO video_reports (video_id, reported_by, reason, status, created_at)
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$videoId, $userId, $reason]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Video report submitted. Thank you!'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit report'
    ]);
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
