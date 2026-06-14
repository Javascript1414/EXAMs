<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) redirect('/community/index.php');

$stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$post = $stmt->fetch();
if (!$post) {
    $_SESSION['error_message'] = "Unauthorized or Post Not Found.";
    redirect('/community/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $post_type = sanitizeInput($_POST['post_type'] ?? 'general');
        $trade_id = (int)($_POST['trade_id'] ?? 0);
        if ($trade_id === 0) $trade_id = null;
        
        if (!empty($title) && !empty($content)) {
            $upd = $pdo->prepare("UPDATE community_posts SET trade_id = ?, title = ?, content = ?, post_type = ? WHERE id = ? AND user_id = ?");
            $upd->execute([$trade_id, $title, $content, $post_type, $id, $_SESSION['user_id']]);
            
            // Synchronize Tags changes
            $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $matches);
            if (!empty($matches[1])) {
                $tags = array_unique($matches[1]);
                $tagInsert = $pdo->prepare("INSERT IGNORE INTO community_tags (tag_name) VALUES (?)");
                $tagSelect = $pdo->prepare("SELECT id FROM community_tags WHERE tag_name = ?");
                $postTagInsert = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                foreach($tags as $tag) {
                    $tagName = strtolower($tag);
                    $tagInsert->execute([$tagName]);
                    $tagSelect->execute([$tagName]);
                    $tagId = $tagSelect->fetchColumn();
                    if ($tagId) { $postTagInsert->execute([$id, $tagId]); }
                }
            }

            $_SESSION['success_message'] = "Post updated successfully.";
            redirect('/community/view_post.php?id=' . $id);
        } else {
            $_SESSION['error_message'] = "Title and Content are required.";
        }
    }
}

$trades = [];
if (!hasRole('student')) {
    $trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Edit Post</h3>
        <a href="view_post.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <form method="POST" action="" class="card border-0 shadow-sm p-4 mx-auto" style="max-width: 800px;">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="mb-3"><label class="form-label fw-bold">Post Title *</label><input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($post['title']) ?>"></div>
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label fw-bold">Post Type</label>
                <select name="post_type" class="form-select">
                    <option value="doubt" <?= $post['post_type']==='doubt'?'selected':'' ?>>Doubt / Question</option><option value="discussion" <?= $post['post_type']==='discussion'?'selected':'' ?>>Discussion</option><option value="achievement" <?= $post['post_type']==='achievement'?'selected':'' ?>>Achievement</option><option value="project" <?= $post['post_type']==='project'?'selected':'' ?>>Project Showcase</option><option value="general" <?= $post['post_type']==='general'?'selected':'' ?>>General Post</option>
                </select>
            </div>
            <?php if(!hasRole('student')): ?>
                <div class="col-md-6"><label class="form-label fw-bold">Target Trade Context</label>
                    <select name="trade_id" class="form-select"><option value="">Global Platform (All Trades)</option><?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>" <?= $post['trade_id']==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?></select>
                </div>
            <?php else: ?>
                <div class="col-md-6"><label class="form-label fw-bold">Target Trade</label><input type="text" class="form-control bg-light" value="Your Enrolled Trade" readonly><input type="hidden" name="trade_id" value="<?= $post['trade_id'] ?>"></div>
            <?php endif; ?>
        </div>
        <div class="mb-4"><label class="form-label fw-bold">Content *</label><textarea name="content" class="form-control" rows="8" required><?= htmlspecialchars($post['content']) ?></textarea></div>
        <button type="submit" class="btn btn-primary py-2 fw-bold w-100">Update Post</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>