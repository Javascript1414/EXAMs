<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$post_id = (int)($_GET['id'] ?? 0);

// Increment View Count
$pdo->prepare("UPDATE community_posts SET views_count = views_count + 1 WHERE id = ?")->execute([$post_id]);

// Fetch Post Details
$stmt = $pdo->prepare("SELECT p.*, u.full_name, r.name as role_name, t.trade_name 
                       FROM community_posts p 
                       JOIN users u ON p.user_id = u.id 
                       JOIN roles r ON u.role_id = r.id 
                       LEFT JOIN trades t ON p.trade_id = t.id 
                       WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    $_SESSION['error_message'] = "Post not found or has been removed.";
    redirect('/community/index.php');
}

// Verify Trade Access Isolation for Students
if (hasRole('student') && $post['trade_id'] !== null) {
    $tradeStmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
    $tradeStmt->execute([$_SESSION['user_id']]);
    if ($tradeStmt->fetchColumn() != $post['trade_id']) {
        redirect('/community/index.php');
    }
}

// Handle Interactions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'like') {
            $checkLike = $pdo->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
            $checkLike->execute([$post_id, $_SESSION['user_id']]);
            if ($checkLike->fetch()) {
                $pdo->prepare("DELETE FROM community_likes WHERE post_id = ? AND user_id = ?")->execute([$post_id, $_SESSION['user_id']]);
                $pdo->prepare("UPDATE community_posts SET likes_count = likes_count - 1 WHERE id = ?")->execute([$post_id]);
            } else {
                $pdo->prepare("INSERT INTO community_likes (post_id, user_id) VALUES (?, ?)")->execute([$post_id, $_SESSION['user_id']]);
                $pdo->prepare("UPDATE community_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$post_id]);
            }
            redirect('/community/view_post.php?id=' . $post_id);
        } elseif ($action === 'comment' && !$post['is_locked']) {
            $comment_text = sanitizeInput($_POST['comment'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            
            if (!empty($comment_text)) {
                $pdo->prepare("INSERT INTO community_comments (post_id, user_id, comment, parent_comment_id) VALUES (?, ?, ?, ?)")->execute([$post_id, $_SESSION['user_id'], $comment_text, $parent_id]);
                $pdo->prepare("UPDATE community_posts SET comments_count = comments_count + 1 WHERE id = ?")->execute([$post_id]);
                $_SESSION['success_message'] = "Comment posted.";
            }
            redirect('/community/view_post.php?id=' . $post_id . '#comments');
        } elseif ($action === 'mark_solution' && $_SESSION['user_id'] == $post['user_id'] && $post['post_type'] === 'doubt') {
            $comment_id = (int)($_POST['comment_id'] ?? 0);
            $pdo->prepare("UPDATE community_comments SET is_solution = 0 WHERE post_id = ?")->execute([$post_id]);
            $pdo->prepare("UPDATE community_comments SET is_solution = 1 WHERE id = ? AND post_id = ?")->execute([$comment_id, $post_id]);
            $_SESSION['success_message'] = "Marked as Solution!";
            redirect('/community/view_post.php?id=' . $post_id . '#comments');
        }
    }
}

// Check Like State
$likeCheck = $pdo->prepare("SELECT id FROM community_likes WHERE post_id = ? AND user_id = ?");
$likeCheck->execute([$post_id, $_SESSION['user_id']]);
$userLiked = $likeCheck->fetch() ? true : false;

// Fetch Comments (Solutions first, then oldest to newest)
$commentStmt = $pdo->prepare("SELECT c.*, u.full_name, r.name as role_name FROM community_comments c JOIN users u ON c.user_id = u.id JOIN roles r ON u.role_id = r.id WHERE c.post_id = ? ORDER BY c.is_solution DESC, c.created_at ASC");
$commentStmt->execute([$post_id]);
$comments = $commentStmt->fetchAll();

// Map nested comments (1 level deep)
$threadMap = ['parents' => [], 'replies' => []];
foreach ($comments as $c) {
    if ($c['parent_comment_id'] === null) { $threadMap['parents'][] = $c; }
    else { $threadMap['replies'][$c['parent_comment_id']][] = $c; }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="text-decoration-none text-muted fw-semibold"><i data-lucide="arrow-left" style="width:16px;"></i> Back to Feed</a>
        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
            <div>
                <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-outline-primary me-2">Edit</a>
                <form method="POST" action="delete_post.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="id" value="<?= $post['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this post?');">Delete</button></form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Main Post -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <?php if ($post['post_type'] === 'announcement'): ?><div class="bg-danger text-white px-3 py-1 fw-bold text-uppercase" style="font-size: 0.8rem;"><i data-lucide="megaphone" style="width:14px; margin-top:-2px;" class="me-1"></i> Official Announcement</div><?php endif; ?>
        <?php if ($post['is_locked']): ?><div class="bg-warning text-dark px-3 py-1 fw-bold text-uppercase" style="font-size: 0.8rem;"><i data-lucide="lock" style="width:14px; margin-top:-2px;" class="me-1"></i> Discussion Locked by Moderator</div><?php endif; ?>
        
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center mb-4 border-bottom pb-3">
                <div class="avatar-circle me-3" style="width: 50px; height: 50px; font-size: 1.4rem; background: <?= $post['role_name'] === 'student' ? '#0056D2' : '#dc3545' ?>;"><?= strtoupper(substr($post['full_name'], 0, 1)) ?></div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($post['full_name']) ?> <span class="badge bg-light text-muted ms-1 border text-capitalize"><?= $post['role_name'] ?></span></h5>
                    <small class="text-muted"><?= htmlspecialchars($post['trade_name'] ?? 'Global Platform') ?> &bull; <?= date('F j, Y g:i A', strtotime($post['created_at'])) ?></small>
                </div>
            </div>
            
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mb-3 text-uppercase"><?= htmlspecialchars($post['post_type']) ?></span>
            <h2 class="fw-bold text-dark mb-3"><?= htmlspecialchars($post['title']) ?></h2>
            
            <!-- Convert hashtags to bold blue text visually -->
            <div class="mb-4" style="font-size: 1.1rem; line-height: 1.8; color: #374151;">
                <?= nl2br(preg_replace('/#(\w+)/', '<strong class="text-primary">#$1</strong>', htmlspecialchars($post['content']))) ?>
            </div>
            
            <?php if ($post['image_path']): ?>
                <div class="mb-4 bg-light rounded text-center"><img src="<?= BASE_URL ?>/<?= htmlspecialchars($post['image_path']) ?>" alt="Attached Image" class="img-fluid rounded shadow-sm"></div>
            <?php endif; ?>
            
            <div class="d-flex align-items-center border-top pt-4">
                <form method="POST" action="" class="me-3">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="action" value="like">
                    <button type="submit" class="btn <?= $userLiked ? 'btn-primary' : 'btn-outline-secondary' ?> fw-bold px-4 d-flex align-items-center"><i data-lucide="thumbs-up" class="me-2" style="width:18px;"></i> <?= $userLiked ? 'Liked' : 'Like' ?> (<?= $post['likes_count'] ?>)</button>
                </form>
                <span class="text-muted fw-semibold d-flex align-items-center"><i data-lucide="message-square" class="me-2" style="width:18px;"></i> <?= $post['comments_count'] ?> Comments</span>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-4" id="comments">Discussion (<?= $post['comments_count'] ?>)</h5>
    
    <?php if (!$post['is_locked']): ?>
    <div class="card border-0 shadow-sm p-4 mb-4 bg-light">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="action" value="comment">
            <textarea name="comment" class="form-control bg-white mb-3" rows="3" required placeholder="Add to the discussion..."></textarea>
            <div class="text-end"><button type="submit" class="btn btn-primary fw-bold px-4">Post Comment</button></div>
        </form>
    </div>
    <?php endif; ?>

    <?php foreach ($threadMap['parents'] as $c): ?>
        <div class="card border-0 shadow-sm mb-3 <?= $c['is_solution'] ? 'border-start border-success border-4' : '' ?>">
            <div class="card-body p-4">
                <?php if ($c['is_solution']): ?><div class="badge bg-success mb-2 fs-6"><i data-lucide="check-circle" style="width:14px; margin-top:-2px;" class="me-1"></i> Accepted Solution</div><?php endif; ?>
                <div class="d-flex align-items-center mb-2">
                    <div class="fw-bold text-dark me-2"><?= htmlspecialchars($c['full_name']) ?> <span class="badge bg-light text-muted border text-capitalize"><?= $c['role_name'] ?></span></div>
                    <small class="text-muted"><?= timeElapsedString($c['created_at']) ?></small>
                </div>
                <p class="mb-2" style="color: #4b5563;"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                
                <div class="d-flex align-items-center gap-3">
                    <?php if (!$post['is_locked']): ?><button class="btn btn-sm btn-link text-decoration-none text-muted p-0" onclick="document.getElementById('reply-form-<?= $c['id'] ?>').classList.toggle('d-none')">Reply</button><?php endif; ?>
                    <?php if ($post['user_id'] == $_SESSION['user_id'] && $post['post_type'] === 'doubt' && !$c['is_solution']): ?>
                        <form method="POST" action="" class="m-0"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="action" value="mark_solution"><input type="hidden" name="comment_id" value="<?= $c['id'] ?>"><button type="submit" class="btn btn-sm btn-link text-decoration-none text-success p-0">Mark as Solution</button></form>
                    <?php endif; ?>
                </div>
                
                <div id="reply-form-<?= $c['id'] ?>" class="d-none mt-3">
                    <form method="POST" action=""><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="action" value="comment"><input type="hidden" name="parent_id" value="<?= $c['id'] ?>"><textarea name="comment" class="form-control form-control-sm mb-2" rows="2" required placeholder="Write a reply..."></textarea><button type="submit" class="btn btn-sm btn-secondary">Submit Reply</button></form>
                </div>
                
                <?php if (isset($threadMap['replies'][$c['id']])): foreach ($threadMap['replies'][$c['id']] as $r): ?>
                    <div class="ms-4 mt-3 ps-3 border-start border-2">
                        <div class="d-flex align-items-center mb-1">
                            <div class="fw-bold text-dark me-2 small"><?= htmlspecialchars($r['full_name']) ?> <span class="badge bg-light text-muted border text-capitalize" style="font-size:0.6rem;"><?= $r['role_name'] ?></span></div>
                            <small class="text-muted" style="font-size:0.7rem;"><?= timeElapsedString($r['created_at']) ?></small>
                        </div>
                        <p class="mb-0 small" style="color: #4b5563;"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>lucide.createIcons();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>