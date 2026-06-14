<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$query = "SELECT p.*, u.full_name, r.name as role_name, t.trade_name 
          FROM community_posts p 
          JOIN users u ON p.user_id = u.id 
          JOIN roles r ON u.role_id = r.id
          LEFT JOIN trades t ON p.trade_id = t.id 
          WHERE p.status = 'active'
          ORDER BY p.views_count DESC, p.likes_count DESC 
          LIMIT 20";

$stmt = $pdo->prepare($query);
$stmt->execute();
$posts = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Trending Discussions</h3>
        <a href="create_post.php" class="btn btn-primary fw-bold d-flex align-items-center">
            <i data-lucide="plus" class="me-2" style="width: 18px;"></i> Create Post
        </a>
    </div>
    
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="index.php"><i data-lucide="list" style="width: 16px; margin-top:-2px;" class="me-1"></i> Feed</a></li>
        <li class="nav-item"><a class="nav-link active fw-semibold" href="trending.php"><i data-lucide="trending-up" style="width: 16px; margin-top:-2px;" class="me-1"></i> Trending</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="tags.php"><i data-lucide="hash" style="width: 16px; margin-top:-2px;" class="me-1"></i> Tags</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="my_posts.php"><i data-lucide="user" style="width: 16px; margin-top:-2px;" class="me-1"></i> My Posts</a></li>
    </ul>

    <div class="row g-4">
        <div class="col-lg-8 mx-auto">
            <?php foreach ($posts as $index => $post): ?>
                <div class="card border-0 shadow-sm mb-4 overflow-hidden position-relative">
                    <div class="position-absolute top-0 end-0 bg-warning text-dark px-3 py-1 fw-bold border-bottom-left-radius" style="border-bottom-left-radius: 8px; z-index: 10;">
                        #<?= $index + 1 ?> Trending
                    </div>
                    <div class="card-body p-4 pt-5">
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
                        
                        <p class="text-muted" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                        
                        <div class="d-flex border-top pt-3 mt-3">
                            <a href="view_post.php?id=<?= $post['id'] ?>" class="btn btn-light btn-sm fw-semibold text-muted me-2"><i data-lucide="thumbs-up" class="me-1" style="width:16px;"></i> <?= $post['likes_count'] ?> Likes</a>
                            <a href="view_post.php?id=<?= $post['id'] ?>#comments" class="btn btn-light btn-sm fw-semibold text-muted me-2"><i data-lucide="message-square" class="me-1" style="width:16px;"></i> <?= $post['comments_count'] ?> Comments</a>
                            <span class="btn btn-light btn-sm fw-semibold text-muted ms-auto pointer-events-none"><i data-lucide="eye" class="me-1" style="width:16px;"></i> <?= $post['views_count'] ?> Views</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($posts)): ?><div class="alert alert-light border p-5 text-center text-muted">Not enough data to determine trending posts.</div><?php endif; ?>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>