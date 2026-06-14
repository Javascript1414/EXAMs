<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$exam_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, s.subject_name, (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as q_count FROM exams e JOIN subjects s ON e.subject_id = s.id WHERE e.id = ? AND e.status = 'published'");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    $_SESSION['error_message'] = "Exam not found or unavailable.";
    redirect('/student/exams.php');
}

// Check if attempt exists
$check = $pdo->prepare("SELECT status FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
$check->execute([$exam_id, $_SESSION['user_id']]);
$attemptStatus = $check->fetchColumn();

if ($attemptStatus === 'submitted') {
    $_SESSION['error_message'] = "You have already completed this exam.";
    redirect('/student/exams.php');
} elseif ($attemptStatus === 'in_progress') {
    redirect('/student/exam_attempt.php?id=' . $exam_id);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm mx-auto" style="max-width: 800px;">
        <div class="bg-primary text-white p-4 rounded-top">
            <h3 class="fw-bold mb-1">Exam Instructions</h3>
            <p class="mb-0 opacity-75"><?= htmlspecialchars($exam['exam_name']) ?> &bull; <?= htmlspecialchars($exam['subject_name']) ?></p>
        </div>
        <div class="card-body p-4">
            <h5 class="fw-bold border-bottom pb-2 mb-3">Please read the following instructions carefully:</h5>
            <ul class="mb-4 text-muted" style="line-height: 1.8;">
                <li>The duration of this examination is <strong><?= $exam['duration_minutes'] ?> minutes</strong>.</li>
                <li>There are a total of <strong><?= $exam['q_count'] ?> questions</strong> carrying <?= (float)$exam['total_marks'] ?> marks.</li>
                <li>Passing criteria: <strong><?= (float)$exam['passing_marks'] ?> marks</strong>.</li>
                <?php if ($exam['negative_marking_enabled']): ?>
                    <li class="text-danger fw-semibold">Negative marking is ENABLED. Incorrect answers will result in a deduction of marks.</li>
                <?php else: ?>
                    <li class="text-success fw-semibold">Negative marking is DISABLED. There is no penalty for wrong answers.</li>
                <?php endif; ?>
                <li>You can navigate between questions using the 'Next' and 'Previous' buttons.</li>
                <li>Questions can be 'Marked for Review' to revisit them later.</li>
                <li>The exam will <strong>auto-submit</strong> when the timer reaches zero.</li>
                <li>Do not refresh or close the browser window. Doing so may submit the test prematurely.</li>
            </ul>

            <h6 class="fw-bold">Color Palette Legend:</h6>
            <div class="d-flex flex-wrap gap-3 mb-4 text-muted small">
                <div class="d-flex align-items-center"><div style="width:20px;height:20px;background:#6c757d;" class="me-2 rounded"></div> Not Visited</div>
                <div class="d-flex align-items-center"><div style="width:20px;height:20px;background:#dc3545;" class="me-2 rounded"></div> Not Answered</div>
                <div class="d-flex align-items-center"><div style="width:20px;height:20px;background:#198754;" class="me-2 rounded"></div> Answered</div>
                <div class="d-flex align-items-center"><div style="width:20px;height:20px;background:#6f42c1;" class="me-2 rounded"></div> Marked for Review</div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="agreeCheck" onchange="document.getElementById('startBtn').disabled = !this.checked;">
                <label class="form-check-label text-dark fw-semibold" for="agreeCheck">
                    I have read and understood the instructions. I am ready to begin the exam.
                </label>
            </div>

            <form method="POST" action="exam_start.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                <button type="submit" id="startBtn" class="btn btn-primary px-5 py-2 fw-bold w-100" disabled>Start Examination</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>