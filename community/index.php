<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$search = sanitizeInput($_GET['search'] ?? '');
$type_filter = sanitizeInput($_GET['type'] ?? '');

$where = "p.status = 'active'";
$params = [];

// Enforce Trade Isolation for Students, Global Access for Admins
if (hasRole('student')) {
    $tradeStmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
    $tradeStmt->execute([$_SESSION['user_id']]);
    $user_trade_id = $tradeStmt->fetchColumn();
    $where .= " AND (p.trade_id = ? OR p.trade_id IS NULL)"; // NULL = Global Announcement
    $params[] = $user_trade_id;
}

if (!empty($search)) {
    // If search is a hashtag, filter by tags table
    if (str_starts_with($search, '#')) {
        $tagSearch = strtolower(substr($search, 1));
        $where .= " AND p.id IN (SELECT pt.post_id FROM post_tags pt JOIN community_tags ct ON pt.tag_id = ct.id WHERE ct.tag_name LIKE ?)";
        $params[] = "%$tagSearch%";
    } else {
        $where .= " AND (p.title LIKE ? OR p.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
}
if (!empty($type_filter)) {
    $where .= " AND p.post_type = ?";
    $params[] = $type_filter;
}

$query = "SELECT p.*, u.full_name, r.name as role_name, t.trade_name 
          FROM community_posts p 
          JOIN users u ON p.user_id = u.id 
          JOIN roles r ON u.role_id = r.id
          LEFT JOIN trades t ON p.trade_id = t.id 
          WHERE $where 
          ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Global Community</h3>
        <a href="create_post.php" class="btn btn-primary fw-bold d-flex align-items-center">
            <i data-lucide="plus" class="me-2" style="width: 18px;"></i> Create Post
        </a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Sub-navigation -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
        <li class="nav-item"><a class="nav-link active fw-semibold" href="index.php"><i data-lucide="list" style="width: 16px; margin-top:-2px;" class="me-1"></i> Feed</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="trending.php"><i data-lucide="trending-up" style="width: 16px; margin-top:-2px;" class="me-1"></i> Trending</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="tags.php"><i data-lucide="hash" style="width: 16px; margin-top:-2px;" class="me-1"></i> Tags</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="my_posts.php"><i data-lucide="user" style="width: 16px; margin-top:-2px;" class="me-1"></i> My Posts</a></li>
    </ul>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Posts Feed -->
            <?php foreach ($posts as $post): ?>
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <?php if ($post['post_type'] === 'announcement'): ?>
                        <div class="bg-danger text-white px-3 py-1 fw-bold text-uppercase" style="font-size: 0.8rem;"><i data-lucide="megaphone" style="width:14px; margin-top:-2px;" class="me-1"></i> Official Announcement</div>
                    <?php elseif ($post['post_type'] === 'achievement'): ?>
                        <div class="bg-warning text-dark px-3 py-1 fw-bold text-uppercase" style="font-size: 0.8rem;"><i data-lucide="star" style="width:14px; margin-top:-2px;" class="me-1"></i> Community Achievement</div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle me-3" style="width: 45px; height: 45px; font-size: 1.2rem; background: <?= $post['role_name'] === 'student' ? '#0056D2' : '#dc3545' ?>;">
                                <?= strtoupper(substr($post['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($post['full_name']) ?> <span class="badge bg-light text-muted ms-1 border text-capitalize"><?= $post['role_name'] ?></span></h6>
                                <small class="text-muted"><?= htmlspecialchars($post['trade_name'] ?? 'Global Platform') ?> &bull; <?= timeElapsedString($post['created_at']) ?></small>
                            </div>
                        </div>
                        
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mb-2 text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($post['post_type']) ?></span>
                        <h5 class="fw-bold text-dark mb-2"><a href="view_post.php?id=<?= $post['id'] ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($post['title']) ?></a></h5>
                        
                        <p class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        
                        <?php if ($post['image_path']): ?>
                            <div class="mt-3 mb-3 bg-light rounded overflow-hidden text-center" style="max-height: 400px;">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($post['image_path']) ?>" alt="Post Image" class="img-fluid object-fit-contain" style="max-height: 400px;">
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex border-top pt-3 mt-3">
                            <a href="view_post.php?id=<?= $post['id'] ?>" class="btn btn-light btn-sm fw-semibold text-muted me-2"><i data-lucide="thumbs-up" class="me-1" style="width:16px;"></i> <?= $post['likes_count'] ?> Likes</a>
                            <a href="view_post.php?id=<?= $post['id'] ?>#comments" class="btn btn-light btn-sm fw-semibold text-muted me-2"><i data-lucide="message-square" class="me-1" style="width:16px;"></i> <?= $post['comments_count'] ?> Comments</a>
                            <span class="btn btn-light btn-sm fw-semibold text-muted ms-auto pointer-events-none"><i data-lucide="eye" class="me-1" style="width:16px;"></i> <?= $post['views_count'] ?> Views</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($posts)): ?><div class="alert alert-light border p-5 text-center text-muted">No posts found in the community feed. Be the first to start a discussion!</div><?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 sticky-top" style="top: 20px;">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Filter Feed</h5>
                <form method="GET" action="">
                    <div class="mb-3"><input type="text" name="search" class="form-control bg-light border-0" placeholder="Search or #tag..." value="<?= htmlspecialchars($search) ?>"></div>
                    <div class="mb-3"><select name="type" class="form-select bg-light border-0"><option value="">All Post Types</option><option value="doubt" <?= $type_filter === 'doubt' ? 'selected' : '' ?>>Questions & Doubts</option><option value="discussion" <?= $type_filter === 'discussion' ? 'selected' : '' ?>>Discussions</option><option value="achievement" <?= $type_filter === 'achievement' ? 'selected' : '' ?>>Achievements</option><option value="project" <?= $type_filter === 'project' ? 'selected' : '' ?>>Project Showcases</option></select></div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Apply Filters</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>