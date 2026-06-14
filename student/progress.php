<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$tradeStmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
$tradeStmt->execute([$_SESSION['user_id']]);
$userTrade = $tradeStmt->fetchColumn();

$query = "SELECT s.id, s.subject_name, 
            (SELECT COUNT(*) FROM study_materials sm WHERE sm.subject_id = s.id) as total_materials,
            (SELECT COUNT(*) FROM material_progress mp JOIN study_materials sm2 ON mp.material_id = sm2.id WHERE sm2.subject_id = s.id AND mp.user_id = ? AND mp.is_completed = 1) as completed_materials
          FROM subjects s 
          WHERE s.trade_id = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id'], $userTrade]);
$subjects = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4"><i data-lucide="trending-up" class="me-2 text-primary" style="width:28px;"></i> My Learning Progress</h3>
    
    <div class="row g-4">
        <?php foreach ($subjects as $s): 
            $total = $s['total_materials'];
            $comp = $s['completed_materials'];
            $perc = $total > 0 ? round(($comp / $total) * 100) : 0;
        ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($s['subject_name']) ?></h5>
                    <span class="badge <?= $perc === 100 ? 'bg-success' : 'bg-primary' ?> fs-6"><?= $perc ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar <?= $perc === 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $perc ?>%;" aria-valuenow="<?= $perc ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between text-muted small fw-semibold">
                    <span><?= $comp ?> Materials Completed</span>
                    <span><?= $total ?> Total Materials</span>
                </div>
                <?php if ($perc < 100 && $total > 0): ?>
                    <a href="materials.php?subject_id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary mt-3 w-100 fw-bold">Continue Learning</a>
                <?php elseif ($perc === 100 && $total > 0): ?>
                    <button class="btn btn-sm btn-success mt-3 w-100 fw-bold disabled"><i data-lucide="check" class="me-1" style="width:14px;"></i> Subject Completed</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($subjects)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">No subjects enrolled.</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>