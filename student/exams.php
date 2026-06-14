<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

// Fetch user's assigned trade
$stmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userTrade = $stmt->fetchColumn();

// Fetch published exams for this trade
$query = "SELECT e.*, s.subject_name, 
            (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count,
            (SELECT status FROM exam_attempts ea WHERE ea.exam_id = e.id AND ea.student_id = ? ORDER BY id DESC LIMIT 1) as attempt_status
          FROM exams e 
          JOIN subjects s ON e.subject_id = s.id 
          WHERE e.trade_id = ? AND e.status = 'published'
          ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id'], $userTrade]);
$exams = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">Available Exams</h3>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4">
        <?php foreach ($exams as $e): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="bg-primary text-white p-3">
                        <span class="badge bg-light text-primary mb-2"><?= htmlspecialchars($e['exam_type']) ?></span>
                        <h5 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($e['exam_name']) ?></h5>
                        <small class="opacity-75"><?= htmlspecialchars($e['subject_name']) ?></small>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small"><i data-lucide="clock" style="width:14px;"></i> <?= $e['duration_minutes'] ?> Mins</span>
                            <span class="text-muted small"><i data-lucide="check-circle" style="width:14px;"></i> <?= (float)$e['passing_marks'] ?> / <?= (float)$e['total_marks'] ?> Pass</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted small"><i data-lucide="help-circle" style="width:14px;"></i> <?= $e['question_count'] ?> Questions</span>
                            <span class="text-muted small"><i data-lucide="alert-triangle" style="width:14px;"></i> <?= $e['negative_marking_enabled'] ? 'Negative Marking' : 'No Negative Marks' ?></span>
                        </div>
                        
                        <?php if ($e['attempt_status'] === 'submitted'): ?>
                            <button class="btn btn-secondary w-100 disabled">Already Attempted</button>
                        <?php elseif ($e['attempt_status'] === 'in_progress'): ?>
                            <a href="exam_attempt.php?id=<?= $e['id'] ?>" class="btn btn-warning w-100 fw-bold">Resume Attempt</a>
                        <?php else: ?>
                            <a href="exam_instructions.php?id=<?= $e['id'] ?>" class="btn btn-primary w-100 fw-bold">Start Exam</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($exams)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">No exams are currently available for your trade.</div></div><?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>