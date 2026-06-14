<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('moderator')) {
    $current_role = $_SESSION['role_name'] ?? $_SESSION['role'] ?? 'student';
    redirectDashboard($current_role);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            if ($action === 'delete') {
                $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Post permanently deleted.";
            } elseif ($action === 'hide') {
                $pdo->prepare("UPDATE community_posts SET status = 'hidden' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Post hidden from community.";
            } elseif ($action === 'restore') {
                $pdo->prepare("UPDATE community_posts SET status = 'active' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Post restored to active status.";
            } elseif ($action === 'lock') {
                $pdo->prepare("UPDATE community_posts SET is_locked = 1 WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Discussion thread locked.";
            }
        }
    }
    redirect('/moderator/community.php');
}

$query = "SELECT p.*, u.full_name, t.trade_name 
          FROM community_posts p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN trades t ON p.trade_id = t.id 
          ORDER BY p.created_at DESC";
$posts = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Community Moderation</h3>
    </div>
    <?php displayFlashMessages(); ?>
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="max-width:300px;">Post Title / Content</th><th>Author & Context</th><th>Metrics</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td style="max-width:300px;"><div class="fw-semibold text-truncate"><?= htmlspecialchars($post['title']) ?></div><div class="small text-muted text-truncate"><?= htmlspecialchars($post['content']) ?></div></td>
                        <td><div class="fw-bold text-dark small"><?= htmlspecialchars($post['full_name']) ?></div><div class="small text-muted"><?= htmlspecialchars($post['trade_name'] ?? 'Global') ?></div></td>
                        <td><span class="badge bg-light text-dark border me-1"><i data-lucide="thumbs-up" style="width:12px;"></i> <?= $post['likes_count'] ?></span><span class="badge bg-light text-dark border"><i data-lucide="message-square" style="width:12px;"></i> <?= $post['comments_count'] ?></span></td>
                        <td>
                            <?php if ($post['status']==='hidden'): ?><span class="badge bg-warning text-dark">Hidden</span><?php else: ?><span class="badge bg-success">Active</span><?php endif; ?>
                            <?php if ($post['is_locked']): ?><span class="badge bg-danger ms-1"><i data-lucide="lock" style="width:10px;"></i></span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/community/post_view.php?id=<?= $post['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">View</a>
                            <form method="POST" action="" class="d-inline"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <?php if ($post['status']==='active'): ?><button type="submit" name="action" value="hide" class="btn btn-sm btn-outline-warning me-1">Hide</button><?php else: ?><button type="submit" name="action" value="restore" class="btn btn-sm btn-outline-success me-1">Restore</button><?php endif; ?>
                            <?php if (!$post['is_locked']): ?><button type="submit" name="action" value="lock" class="btn btn-sm btn-outline-secondary me-1">Lock</button><?php endif; ?>
                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete?');">Delete</button></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>