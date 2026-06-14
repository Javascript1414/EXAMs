<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$stmt = $pdo->prepare("
    SELECT r.*, e.exam_name, e.passing_marks, t.trade_name, s.subject_name 
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    JOIN trades t ON e.trade_id = t.id 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE r.student_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$results = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">My Exam Results</h3>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4">
        <?php foreach ($results as $r): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm overflow-hidden <?= $r['is_passed'] ? 'border-bottom border-success border-4' : 'border-bottom border-danger border-4' ?>">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-light text-primary mb-2"><?= htmlspecialchars($r['subject_name']) ?></span>
                                <h5 class="fw-bold mb-0 text-truncate text-dark" style="max-width:200px;" title="<?= htmlspecialchars($r['exam_name']) ?>"><?= htmlspecialchars($r['exam_name']) ?></h5>
                            </div>
                            <?php if ($r['is_passed']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success p-2"><i data-lucide="check-circle" style="width:14px;"></i> Pass</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger p-2"><i data-lucide="x-circle" style="width:14px;"></i> Fail</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3 border-top border-bottom py-3">
                            <div class="text-center"><h4 class="fw-bold text-primary mb-0"><?= (float)$r['obtained_marks'] ?></h4><small class="text-muted">Score</small></div>
                            <div class="text-center"><h4 class="fw-bold text-dark mb-0"><?= (float)$r['percentage'] ?>%</h4><small class="text-muted">Percentage</small></div>
                            <div class="text-center"><h4 class="fw-bold text-dark mb-0"><?= (float)$r['total_marks'] ?></h4><small class="text-muted">Total Marks</small></div>
                        </div>
                        
                        <a href="result_view.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary w-100 fw-bold">View Detailed Analysis</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($results)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">You haven't completed any exams yet.</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>