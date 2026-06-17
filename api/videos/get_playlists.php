<?php
/**
 * Video API - Get Playlists
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $playlists = [
        ['playlist_id' => 1, 'name' => 'Mathematics Learning Path', 'video_count' => 12, 'created_at' => date('Y-m-d')],
        ['playlist_id' => 2, 'name' => 'Science Fundamentals', 'video_count' => 8, 'created_at' => date('Y-m-d', strtotime('-1 week'))],
        ['playlist_id' => 3, 'name' => 'Programming Basics', 'video_count' => 15, 'created_at' => date('Y-m-d', strtotime('-2 weeks'))],
        ['playlist_id' => 4, 'name' => 'English Skills', 'video_count' => 6, 'created_at' => date('Y-m-d', strtotime('-3 weeks'))]
    ];
    
    echo json_encode(['success' => true, 'playlists' => $playlists]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
