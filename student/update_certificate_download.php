<?php
/**
 * Update Certificate Download Record
 * Called when student downloads certificate
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$certificate_id = $_GET['id'] ?? null;

if (!$certificate_id) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Certificate ID not provided']));
}

try {
    // Update download record
    $stmt = $pdo->prepare("
        UPDATE certificates 
        SET downloaded_at = NOW()
        WHERE certificate_id = ? AND student_id = ? AND downloaded_at IS NULL
    ");
    $stmt->execute([$certificate_id, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
