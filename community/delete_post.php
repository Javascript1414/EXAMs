<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT image_path FROM community_posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $post = $stmt->fetch();
        
        if ($post) {
            if ($post['image_path'] && file_exists(__DIR__ . '/../' . $post['image_path'])) { unlink(__DIR__ . '/../' . $post['image_path']); }
            $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$id]);
            $_SESSION['success_message'] = "Post successfully deleted.";
        }
    }
}
redirect('/community/index.php');