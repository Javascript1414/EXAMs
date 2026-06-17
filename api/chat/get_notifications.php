<?php
/**
 * Chat API - Get Notifications
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    $notifications = [
        ['notification_id' => 1, 'type' => 'message', 'title' => 'New Message', 'message' => 'John sent you a message', 'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')), 'read' => false],
        ['notification_id' => 2, 'type' => 'exam', 'title' => 'Exam Scheduled', 'message' => 'New exam available: Mathematics', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'read' => false],
        ['notification_id' => 3, 'type' => 'material', 'title' => 'New Material', 'message' => 'New study material added: Chapter 5', 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'read' => true],
        ['notification_id' => 4, 'type' => 'achievement', 'title' => 'Achievement', 'message' => 'You unlocked: Quick Learner', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')), 'read' => true]
    ];
    
    echo json_encode(['success' => true, 'notifications' => $notifications]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
