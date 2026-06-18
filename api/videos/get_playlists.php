<?php
/**
 * Video API - Get Playlists
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.course_id as playlist_id,
            c.course_name as name,
            COUNT(v.id) as video_count,
            c.created_at
        FROM courses c
        LEFT JOIN videos v ON c.course_id = v.course_id AND v.status = 'active'
        GROUP BY c.course_id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    
    $stmt->execute();
    $playlists = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'playlists' => $playlists]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
