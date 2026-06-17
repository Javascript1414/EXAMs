<?php
/**
 * Chat API - Get Conversations
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    $conversations = [
        ['conversation_id' => 1, 'user_name' => 'John Doe', 'user_id' => 2, 'last_message' => 'When is the exam?', 'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')), 'status' => 'online', 'unread' => 2],
        ['conversation_id' => 2, 'user_name' => 'Sarah Admin', 'user_id' => 3, 'last_message' => 'Your assignment was reviewed', 'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')), 'status' => 'offline', 'unread' => 0],
        ['conversation_id' => 3, 'user_name' => 'Study Group', 'user_id' => 4, 'last_message' => 'Let\'s meet tomorrow at 3pm', 'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'status' => 'online', 'unread' => 1]
    ];
    
    echo json_encode(['success' => true, 'conversations' => $conversations]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
