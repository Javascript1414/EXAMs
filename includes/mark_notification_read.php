<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$pdo->prepare("UPDATE notification_recipients SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?")->execute([$id, $_SESSION['user_id']]);
echo json_encode(['success' => true]);
?>