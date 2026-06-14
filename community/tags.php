<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$query = "SELECT t.id, t.tag_name, COUNT(pt.post_id) as post_count 
          FROM community_tags t 
          JOIN post_tags pt ON t.id = pt.tag_id 
          GROUP BY t.id 
          ORDER BY post_count DESC LIMIT 50";
$tags = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Explore Topics</h3>
        <a href="create_post.php" class="btn btn-primary fw-bold d-flex align-items-center"><i data-lucide="plus" class="me-2" style="width: 18px;"></i> Create Post</a>
    </div>
    
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm border">
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="index.php"><i data-lucide="list" style="width: 16px; margin-top:-2px;" class="me-1"></i> Feed</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="trending.php"><i data-lucide="trending-up" style="width: 16px; margin-top:-2px;" class="me-1"></i> Trending</a></li>
        <li class="nav-item"><a class="nav-link active fw-semibold" href="tags.php"><i data-lucide="hash" style="width: 16px; margin-top:-2px;" class="me-1"></i> Tags</a></li>
        <li class="nav-item"><a class="nav-link text-muted fw-semibold" href="my_posts.php"><i data-lucide="user" style="width: 16px; margin-top:-2px;" class="me-1"></i> My Posts</a></li>
    </ul>

    <div class="card border-0 shadow-sm p-4">
        <h5 class="fw-bold mb-4">Popular Tags</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($tags as $tag): ?>
                <a href="index.php?search=<?= urlencode('#' . $tag['tag_name']) ?>" class="btn btn-light border text-decoration-none d-flex align-items-center">
                    <span class="fw-bold text-primary me-2">#<?= htmlspecialchars($tag['tag_name']) ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= $tag['post_count'] ?> posts</span>
                </a>
            <?php endforeach; ?>
            <?php if(empty($tags)): ?>
                <div class="alert alert-light border text-center text-muted w-100">
                    No tags have been created yet. Tags will populate automatically as users categorize their posts.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>