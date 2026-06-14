<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

// Fetch all passed exams and associated certificates if they exist
$stmt = $pdo->prepare("
    SELECT r.id as result_id, r.obtained_marks, r.percentage, e.exam_name, t.trade_name, s.subject_name,
           c.certificate_id, c.status as cert_status
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    JOIN trades t ON e.trade_id = t.id 
    JOIN subjects s ON e.subject_id = s.id 
    LEFT JOIN certificates c ON r.id = c.result_id
    WHERE r.student_id = ? AND r.is_passed = 1 
    ORDER BY r.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$eligible_certs = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">My Certificates</h3>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4">
        <?php foreach ($eligible_certs as $cert): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm overflow-hidden border-top border-success border-4">
                    <div class="card-body p-4 text-center">
                        <i data-lucide="award" class="text-success mb-3" style="width: 48px; height: 48px;"></i>
                        <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cert['exam_name']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($cert['subject_name']) ?> &bull; <?= htmlspecialchars($cert['trade_name']) ?></p>
                        
                        <div class="bg-light p-2 rounded mb-4">
                            <span class="fw-bold text-dark fs-5"><?= (float)$cert['percentage'] ?>%</span>
                            <span class="text-muted small d-block">Score Achieved</span>
                        </div>

                        <?php if ($cert['cert_status'] === 'revoked'): ?>
                            <div class="alert alert-danger py-2 small fw-bold">Certificate Revoked</div>
                        <?php elseif ($cert['certificate_id']): ?>
                            <p class="small text-muted font-monospace mb-2">ID: <?= htmlspecialchars($cert['certificate_id']) ?></p>
                            <a href="certificate_view.php?id=<?= $cert['result_id'] ?>" target="_blank" class="btn btn-success w-100 fw-bold d-flex justify-content-center align-items-center">
                                <i data-lucide="download" class="me-2" style="width:16px;"></i> View & Download
                            </a>
                        <?php else: ?>
                            <a href="certificate_view.php?id=<?= $cert['result_id'] ?>" target="_blank" class="btn btn-primary w-100 fw-bold d-flex justify-content-center align-items-center">
                                <i data-lucide="sparkles" class="me-2" style="width:16px;"></i> Generate Certificate
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($eligible_certs)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">You haven't earned any certificates yet. Pass an exam to unlock yours!</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>