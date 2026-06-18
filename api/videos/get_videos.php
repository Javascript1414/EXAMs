<?php
/**
 * Video API - Get Videos
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
        WHERE v.status = 'active'
        ORDER BY v.created_at DESC
        LIMIT 20
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $videos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'videos' => $videos
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch videos: ' . $e->getMessage()
    ]);
}
