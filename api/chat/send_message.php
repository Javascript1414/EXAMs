<?php
/**
 * Chat API - Send Message
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    $conversation_id = $_POST['conversation_id'] ?? null;
    $message_content = $_POST['message'] ?? '';
    
    if (empty($message_content)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Message cannot be empty']));
    }
    
    // Save message to database
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$conversation_id, $user_id, $message_content]);
    
    echo json_encode([
        'success' => true,
        'message_id' => $pdo->lastInsertId(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
