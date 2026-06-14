<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_bookmark') {
    $material_id = (int)$_POST['material_id'];
    $pdo->prepare("DELETE FROM material_bookmarks WHERE user_id = ? AND material_id = ?")->execute([$_SESSION['user_id'], $material_id]);
    $_SESSION['success_message'] = "Bookmark removed.";
    redirect('/student/bookmarks.php');
}

$query = "SELECT sm.*, s.subject_name 
          FROM material_bookmarks mb
          JOIN study_materials sm ON mb.material_id = sm.id 
          JOIN subjects s ON sm.subject_id = s.id 
          WHERE mb.user_id = ? 
          ORDER BY mb.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$materials = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4"><i data-lucide="bookmark" class="me-2 text-primary" style="width:24px;"></i> My Bookmarks</h3>
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4">
        <?php foreach ($materials as $m): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm overflow-hidden position-relative">
                    <div class="bg-primary text-white p-2 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                        <span class="text-truncate fw-semibold"><?= htmlspecialchars($m['subject_name']) ?></span>
                        <span class="badge bg-light text-primary text-uppercase"><?= htmlspecialchars($m['material_type']) ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-dark text-truncate mb-2"><?= htmlspecialchars($m['title']) ?></h5>
                        <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($m['description']) ?></p>
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($m['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm flex-grow-1 me-2 d-flex justify-content-center align-items-center">
                                <i data-lucide="play-circle" class="me-2" style="width: 16px;"></i> View
                            </a>
                            <form method="POST" action="" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="remove_bookmark">
                                <input type="hidden" name="material_id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm px-3" title="Remove Bookmark">
                                    <i data-lucide="bookmark-minus" style="width: 16px;"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($materials)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">You haven't bookmarked any materials yet.</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>