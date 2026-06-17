<?php
/**
 * Video API - Get Watch History
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $history = [
        ['video_id' => 1, 'title' => 'Introduction to Calculus', 'duration' => 1200, 'watched' => 900, 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 days'))],
        ['video_id' => 2, 'title' => 'Photosynthesis Explained', 'duration' => 900, 'watched' => 450, 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 days'))],
        ['video_id' => 3, 'title' => 'English Grammar Basics', 'duration' => 1500, 'watched' => 1500, 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 week'))],
        ['video_id' => 4, 'title' => 'Python Programming 101', 'duration' => 2400, 'watched' => 1200, 'timestamp' => date('Y-m-d H:i:s', strtotime('-3 days'))]
    ];
    
    echo json_encode(['success' => true, 'history' => $history]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
