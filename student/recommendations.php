<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$tradeStmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
$tradeStmt->execute([$_SESSION['user_id']]);
$userTrade = $tradeStmt->fetchColumn();

// Fetch top rated or featured materials in student's trade not yet viewed
$query = "SELECT sm.*, s.subject_name 
          FROM study_materials sm 
          JOIN subjects s ON sm.subject_id = s.id 
          WHERE sm.trade_id = ? 
          AND sm.id NOT IN (SELECT material_id FROM material_progress WHERE user_id = ?)
          ORDER BY sm.is_featured DESC, sm.average_rating DESC, sm.view_count DESC 
          LIMIT 8";
$stmt = $pdo->prepare($query);
$stmt->execute([$userTrade, $_SESSION['user_id']]);
$materials = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4"><i data-lucide="sparkles" class="me-2 text-warning" style="width:28px;"></i> Recommended For You</h3>
    <div class="row g-4">
        <?php foreach ($materials as $m): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm overflow-hidden position-relative <?= $m['is_featured'] ? 'border-warning border-2' : '' ?>">
                    <?php if ($m['is_featured']): ?><div class="position-absolute top-0 end-0 bg-warning text-dark px-2 py-1 small fw-bold" style="border-bottom-left-radius: 6px; z-index: 10;"><i data-lucide="star" style="width:12px; margin-top:-2px;" class="me-1"></i> Featured</div><?php endif; ?>
                    <div class="bg-primary text-white p-2 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                        <span class="text-truncate fw-semibold"><?= htmlspecialchars($m['subject_name']) ?></span>
                        <span class="badge bg-light text-primary text-uppercase"><?= htmlspecialchars($m['material_type']) ?></span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-dark text-truncate mb-2"><?= htmlspecialchars($m['title']) ?></h5>
                        <div class="d-flex align-items-center mb-3 text-warning small fw-bold">
                            <i data-lucide="star" class="me-1" style="width:14px; fill: currentColor;"></i> <?= $m['average_rating'] > 0 ? number_format($m['average_rating'], 1) : 'New' ?>
                        </div>
                        <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars($m['description']) ?></p>
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($m['file_path']) ?>" target="_blank" class="btn btn-primary btn-sm w-100 fw-bold d-flex justify-content-center align-items-center">
                                <i data-lucide="play-circle" class="me-2" style="width: 16px;"></i> Start Learning
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($materials)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">You're all caught up! Explore the main study materials page.</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>