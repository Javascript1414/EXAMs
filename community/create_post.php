<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Resolve context Trade ID for isolation
$trade_id = null;
if (hasRole('student')) {
    $tradeStmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
    $tradeStmt->execute([$_SESSION['user_id']]);
    $trade_id = $tradeStmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $post_type = sanitizeInput($_POST['post_type'] ?? 'general');
        $admin_trade_id = !hasRole('student') ? (int)($_POST['trade_id'] ?? 0) : $trade_id;
        if ($admin_trade_id === 0) $admin_trade_id = null; // Global
        
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/community/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $fileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                    $image_path = 'uploads/community/' . $fileName;
                }
            } else {
                $_SESSION['error_message'] = "Invalid image format. Only JPG, PNG, GIF, WEBP allowed.";
            }
        }

        if (!empty($title) && !empty($content) && !isset($_SESSION['error_message'])) {
            $stmt = $pdo->prepare("INSERT INTO community_posts (user_id, trade_id, title, content, post_type, image_path) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $admin_trade_id, $title, $content, $post_type, $image_path]);
            $post_id = $pdo->lastInsertId();
            
            // Process Hashtags
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
                    if ($tagId) { $postTagInsert->execute([$post_id, $tagId]); }
                }
            }

            $_SESSION['success_message'] = "Post published to the community successfully.";
            redirect('/community/index.php');
        } else if (empty($_SESSION['error_message'])) {
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
        <h3 class="fw-bold text-dark mb-0">Create Post</h3>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <form method="POST" action="" enctype="multipart/form-data" class="card border-0 shadow-sm p-4 mx-auto" style="max-width: 800px;">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="mb-3"><label class="form-label fw-bold">Post Title *</label><input type="text" name="title" class="form-control" required placeholder="e.g. Need help understanding Ohm's Law"></div>
        <div class="row mb-3">
            <div class="col-md-6"><label class="form-label fw-bold">Post Type</label><select name="post_type" class="form-select"><option value="doubt">Doubt / Question</option><option value="discussion" selected>Discussion</option><option value="achievement">Achievement</option><option value="project">Project Showcase</option><option value="general">General Post</option><?php if(hasRole('superadmin') || hasRole('admin')): ?><option value="announcement">Announcement</option><?php endif; ?></select></div>
            <?php if(!hasRole('student')): ?><div class="col-md-6"><label class="form-label fw-bold">Target Trade Context</label><select name="trade_id" class="form-select"><option value="">Global Platform (All Trades)</option><?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?></select></div><?php else: ?><div class="col-md-6"><label class="form-label fw-bold">Target Trade</label><input type="text" class="form-control bg-light" value="Your Enrolled Trade" readonly></div><?php endif; ?>
        </div>
        <div class="mb-3"><label class="form-label fw-bold">Content *</label><textarea name="content" class="form-control" rows="8" required placeholder="Share your thoughts, ask a question, or use #hashtags..."></textarea></div>
        <div class="mb-4"><label class="form-label fw-bold">Attach Image (Optional)</label><input type="file" name="image" class="form-control" accept="image/*"></div>
        <button type="submit" class="btn btn-primary py-2 fw-bold w-100">Publish Post</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>