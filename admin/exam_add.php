<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $trade_id = (int)($_POST['trade_id'] ?? 0);
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $exam_name = sanitizeInput($_POST['exam_name'] ?? '');
        $exam_type = sanitizeInput($_POST['exam_type'] ?? 'Practice Test');
        $duration_minutes = (int)($_POST['duration_minutes'] ?? 60);
        $total_marks = (float)($_POST['total_marks'] ?? 100);
        $passing_marks = (float)($_POST['passing_marks'] ?? 40);
        
        $negative = (int)($_POST['negative_marking_enabled'] ?? 0);
        $show_ans = (int)($_POST['show_correct_answers'] ?? 0);
        $show_exp = (int)($_POST['show_explanations'] ?? 0);
        $rand_q = (int)($_POST['random_question_order'] ?? 0);
        $rand_o = (int)($_POST['random_option_order'] ?? 0);

        if ($trade_id > 0 && $subject_id > 0 && !empty($exam_name) && $duration_minutes > 0 && $total_marks > 0 && $passing_marks > 0) {
            $stmt = $pdo->prepare("INSERT INTO exams (trade_id, subject_id, exam_name, exam_type, duration_minutes, total_marks, passing_marks, negative_marking_enabled, show_correct_answers, show_explanations, random_question_order, random_option_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$trade_id, $subject_id, $exam_name, $exam_type, $duration_minutes, $total_marks, $passing_marks, $negative, $show_ans, $show_exp, $rand_q, $rand_o, $_SESSION['user_id']]);
            $_SESSION['success_message'] = "Exam created! Next step: Assign questions to this exam.";
            redirect('/admin/exam_assign_questions.php?id=' . $pdo->lastInsertId());
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields properly.";
        }
    }
}

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, trade_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Create New Exam</h3>
        <a href="exams.php" class="btn btn-outline-secondary btn-sm">Cancel</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <form method="POST" action="" class="card p-4">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        
        <h5 class="fw-bold mb-3 border-bottom pb-2">1. Basic Information</h5>
        <div class="row mb-4">
            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Exam Name *</label><input type="text" name="exam_name" class="form-control" required placeholder="e.g. Mid-Term Assessment"></div>
            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Exam Type *</label>
                <select name="exam_type" class="form-select" required>
                    <option value="Practice Test">Practice Test</option>
                    <option value="Mock Test">Mock Test</option>
                    <option value="Module Test">Module Test</option>
                    <option value="Unit Test">Unit Test</option>
                    <option value="Final Test">Final Test</option>
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Trade *</label>
                <select name="trade_id" id="trade_id" class="form-select" required onchange="filterSubjects()">
                    <option value="">Select Trade...</option>
                    <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3"><label class="form-label fw-bold">Subject *</label>
                <select name="subject_id" id="subject_id" class="form-select" required>
                    <option value="">Select Subject...</option>
                    <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" data-trade="<?= $s['trade_id'] ?>" style="display:none;"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>

        <h5 class="fw-bold mb-3 border-bottom pb-2">2. Evaluation Rules</h5>
        <div class="row mb-4">
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Duration (Minutes) *</label><input type="number" name="duration_minutes" class="form-control" value="60" required></div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Total Marks *</label><input type="number" step="0.5" name="total_marks" class="form-control" value="100" required></div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Passing Marks *</label><input type="number" step="0.5" name="passing_marks" class="form-control" value="40" required></div>
        </div>

        <h5 class="fw-bold mb-3 border-bottom pb-2">3. Exam Settings</h5>
        <div class="row mb-4">
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Negative Marking</label>
                <select name="negative_marking_enabled" class="form-select"><option value="0">Disabled</option><option value="1">Enabled</option></select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Randomize Question Order</label>
                <select name="random_question_order" class="form-select"><option value="0">No</option><option value="1">Yes</option></select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Randomize Options Order</label>
                <select name="random_option_order" class="form-select"><option value="0">No</option><option value="1">Yes</option></select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Show Correct Answers Post-Exam</label>
                <select name="show_correct_answers" class="form-select"><option value="0">No</option><option value="1">Yes</option></select>
            </div>
            <div class="col-md-4 mb-3"><label class="form-label fw-bold">Show Explanations Post-Exam</label>
                <select name="show_explanations" class="form-select"><option value="0">No</option><option value="1">Yes</option></select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">Create Exam & Proceed to Questions</button>
    </form>
</div>
<script>
function filterSubjects() { const tradeId = document.getElementById('trade_id').value; document.querySelectorAll('#subject_id option').forEach(opt => { if (opt.value === "") return; opt.style.display = (opt.dataset.trade === tradeId || tradeId === "") ? 'block' : 'none'; }); document.getElementById('subject_id').value = ""; }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>