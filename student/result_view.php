<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$result_id = (int)($_GET['id'] ?? 0);

// Fetch Result and Configuration
$stmt = $pdo->prepare("
    SELECT r.*, e.exam_name, e.show_correct_answers, e.show_explanations, e.duration_minutes, t.trade_name, s.subject_name, ea.time_taken_seconds 
    FROM results r 
    JOIN exams e ON r.exam_id = e.id 
    JOIN exam_attempts ea ON r.attempt_id = ea.id
    JOIN trades t ON e.trade_id = t.id 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE r.id = ? AND r.student_id = ?
");
$stmt->execute([$result_id, $_SESSION['user_id']]);
$result = $stmt->fetch();

if (!$result) {
    $_SESSION['error_message'] = "Result not found.";
    redirect('/student/results.php');
}

// Calculate Rank dynamically for this exam
$rankStmt = $pdo->prepare("
    SELECT rank FROM (
        SELECT attempt_id, RANK() OVER (PARTITION BY exam_id ORDER BY obtained_marks DESC) as rank
        FROM results
        WHERE exam_id = ?
    ) as ranked_results WHERE attempt_id = ?
");
$rankStmt->execute([$result['exam_id'], $result['attempt_id']]);
$userRank = $rankStmt->fetchColumn() ?: '-';

// Fetch Answers Breakdown if allowed
$answers = [];
if ($result['show_correct_answers']) {
    $ansStmt = $pdo->prepare("
        SELECT ea.selected_answer, ea.is_correct, ea.answer_status, 
               q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer, q.explanation, q.marks, q.negative_marks
        FROM exam_answers ea
        JOIN questions q ON ea.question_id = q.id
        WHERE ea.attempt_id = ?
        ORDER BY ea.id ASC
    ");
    $ansStmt->execute([$result['attempt_id']]);
    $answers = $ansStmt->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Detailed Analysis</h3>
        <a href="results.php" class="btn btn-outline-secondary btn-sm">Back to Results</a>
    </div>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="bg-primary text-white p-4 rounded-top d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($result['exam_name']) ?></h4>
                <p class="mb-0 opacity-75"><?= htmlspecialchars($result['subject_name']) ?> &bull; <?= htmlspecialchars($result['trade_name']) ?></p>
            </div>
            <?php if ($result['is_passed']): ?>
                <div class="bg-white text-success px-4 py-2 rounded fw-bold fs-5 shadow-sm"><i data-lucide="check-circle" class="me-2 text-success"></i> PASSED</div>
            <?php else: ?>
                <div class="bg-white text-danger px-4 py-2 rounded fw-bold fs-5 shadow-sm"><i data-lucide="x-circle" class="me-2 text-danger"></i> FAILED</div>
            <?php endif; ?>
        </div>
        
        <div class="card-body p-4 row g-4 text-center">
            <div class="col-md-3 border-end">
                <h2 class="fw-bold text-primary mb-0"><?= (float)$result['obtained_marks'] ?> <span class="fs-6 text-muted">/ <?= (float)$result['total_marks'] ?></span></h2>
                <small class="text-muted text-uppercase fw-semibold">Score</small>
            </div>
            <div class="col-md-3 border-end">
                <h2 class="fw-bold text-dark mb-0"><?= (float)$result['percentage'] ?>%</h2>
                <small class="text-muted text-uppercase fw-semibold">Percentage</small>
            </div>
            <div class="col-md-3 border-end">
                <h2 class="fw-bold text-dark mb-0"><?= floor($result['time_taken_seconds']/60) ?>m <?= $result['time_taken_seconds']%60 ?>s</h2>
                <small class="text-muted text-uppercase fw-semibold">Time Taken</small>
            </div>
            <div class="col-md-3">
                <h2 class="fw-bold text-dark mb-0">#<?= $userRank ?></h2>
                <small class="text-muted text-uppercase fw-semibold">Batch Rank</small>
            </div>
        </div>
    </div>

    <?php if ($result['show_correct_answers']): ?>
        <h5 class="fw-bold mb-3 border-bottom pb-2">Question Breakdown</h5>
        <?php foreach ($answers as $idx => $ans): ?>
            <div class="card shadow-sm border-0 mb-3 <?= $ans['is_correct'] ? 'border-start border-success border-4' : ($ans['selected_answer'] === null ? 'border-start border-secondary border-4' : 'border-start border-danger border-4') ?>">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold mb-0">Q<?= $idx + 1 ?>. <?= nl2br(htmlspecialchars($ans['question_text'])) ?></h6>
                        <div class="text-end">
                            <span class="badge bg-light text-dark border">Marks: <?= (float)$ans['marks'] ?></span>
                            <?php if ($ans['is_correct']): ?>
                                <span class="badge bg-success ms-1">+<?= (float)$ans['marks'] ?></span>
                            <?php elseif ($ans['selected_answer'] === null): ?>
                                <span class="badge bg-secondary ms-1">0.00</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-1">-<?= (float)$ans['negative_marks'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <?php foreach(['A', 'B', 'C', 'D'] as $opt): if(!empty($ans['option_'.strtolower($opt)])): ?>
                            <div class="col-md-6">
                                <div class="p-2 border rounded <?= $ans['correct_answer'] === $opt ? 'bg-success bg-opacity-10 border-success text-success fw-bold' : ($ans['selected_answer'] === $opt ? 'bg-danger bg-opacity-10 border-danger text-danger text-decoration-line-through' : 'bg-light text-muted') ?>">
                                    <?= $opt ?>. <?= htmlspecialchars($ans['option_'.strtolower($opt)]) ?>
                                    <?php if ($ans['correct_answer'] === $opt) echo '<i data-lucide="check" class="float-end" style="width:16px;"></i>'; ?>
                                    <?php if ($ans['selected_answer'] === $opt && $ans['selected_answer'] !== $ans['correct_answer']) echo '<i data-lucide="x" class="float-end" style="width:16px;"></i>'; ?>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                    
                    <?php if ($result['show_explanations'] && !empty($ans['explanation'])): ?>
                        <div class="alert alert-info py-2 px-3 mb-0 small"><i data-lucide="info" class="me-2" style="width:14px;"></i> <strong>Explanation:</strong> <?= nl2br(htmlspecialchars($ans['explanation'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-warning border text-center text-dark"><i data-lucide="lock" class="me-2 mb-1" style="width: 18px;"></i> Question breakdown and correct answers have been hidden for this exam by the administrator.</div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>