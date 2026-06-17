<?php
/**
 * Chat API - Get Messages
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    $conversation_id = $_GET['conversation_id'] ?? 1;
    
    $messages = [
        ['message_id' => 1, 'sender_id' => 2, 'sender_name' => 'John Doe', 'content' => 'When is the exam scheduled?', 'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes')), 'is_own' => false],
        ['message_id' => 2, 'sender_id' => $user_id, 'sender_name' => 'You', 'content' => 'Next Friday at 2 PM', 'timestamp' => date('Y-m-d H:i:s', strtotime('-28 minutes')), 'is_own' => true],
        ['message_id' => 3, 'sender_id' => 2, 'sender_name' => 'John Doe', 'content' => 'Thanks! Are you also giving it?', 'timestamp' => date('Y-m-d H:i:s', strtotime('-25 minutes')), 'is_own' => false],
        ['message_id' => 4, 'sender_id' => $user_id, 'sender_name' => 'You', 'content' => 'Yes, see you there!', 'timestamp' => date('Y-m-d H:i:s', strtotime('-20 minutes')), 'is_own' => true]
    ];
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
