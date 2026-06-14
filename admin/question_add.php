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
        $type = sanitizeInput($_POST['question_type'] ?? 'mcq');
        $text = sanitizeInput($_POST['question_text'] ?? '');
        $optA = sanitizeInput($_POST['option_a'] ?? '');
        $optB = sanitizeInput($_POST['option_b'] ?? '');
        $optC = sanitizeInput($_POST['option_c'] ?? '');
        $optD = sanitizeInput($_POST['option_d'] ?? '');
        $correct = sanitizeInput($_POST['correct_answer'] ?? '');
        $explanation = sanitizeInput($_POST['explanation'] ?? '');
        $difficulty = sanitizeInput($_POST['difficulty'] ?? 'Medium');
        $marks = (float)($_POST['marks'] ?? 1.00);
        $negative = (float)($_POST['negative_marks'] ?? 0.00);
        $status = sanitizeInput($_POST['status'] ?? 'active');

        if ($type === 'true_false') {
            $optA = 'True';
            $optB = 'False';
            $optC = null;
            $optD = null;
            if (!in_array($correct, ['A', 'B'])) $correct = 'A';
        }

        if ($trade_id > 0 && $subject_id > 0 && !empty($text) && !empty($optA) && !empty($optB) && !empty($correct)) {
            $stmt = $pdo->prepare("INSERT INTO questions (trade_id, subject_id, question_type, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, marks, negative_marks, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$trade_id, $subject_id, $type, $text, $optA, $optB, $optC, $optD, $correct, $explanation ?: null, $difficulty, $marks, $negative, $status, $_SESSION['user_id']]);
            $_SESSION['success_message'] = "Question added successfully.";
            redirect('/admin/questions.php');
        } else {
            $_SESSION['error_message'] = "Please fill in all required fields.";
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
        <h3 class="fw-bold text-dark mb-0">Add Single Question</h3>
        <a href="questions.php" class="btn btn-outline-secondary btn-sm">Back to Question Bank</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="row mb-4">
                <div class="col-md-3"><label class="form-label fw-bold">Trade *</label>
                    <select name="trade_id" id="trade_id" class="form-select" required onchange="filterSubjects()">
                        <option value="">Select Trade...</option>
                        <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">Subject *</label>
                    <select name="subject_id" id="subject_id" class="form-select" required>
                        <option value="">Select Subject...</option>
                        <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" data-trade="<?= $s['trade_id'] ?>" style="display:none;"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">Question Type *</label>
                    <select name="question_type" id="question_type" class="form-select" required>
                        <option value="mcq">Multiple Choice (MCQ)</option>
                        <option value="true_false">True / False</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">Status *</label>
                    <select name="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Question Text *</label>
                <textarea name="question_text" class="form-control" rows="4" required></textarea>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Option A *</label><input type="text" name="option_a" id="opt_a" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Option B *</label><input type="text" name="option_b" id="opt_b" class="form-control" required></div>
                <div class="col-md-6 mb-3" id="opt_c_group"><label class="form-label fw-bold">Option C</label><input type="text" name="option_c" id="opt_c" class="form-control"></div>
                <div class="col-md-6 mb-3" id="opt_d_group"><label class="form-label fw-bold">Option D</label><input type="text" name="option_d" id="opt_d" class="form-control"></div>
            </div>

            <div class="row mb-4 bg-light p-3 rounded border">
                <div class="col-md-3"><label class="form-label fw-bold text-success">Correct Answer *</label>
                    <select name="correct_answer" class="form-select border-success" required>
                        <option value="A">Option A</option><option value="B">Option B</option><option value="C" class="mcq-only">Option C</option><option value="D" class="mcq-only">Option D</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">Difficulty *</label>
                    <select name="difficulty" class="form-select" required>
                        <option value="Easy">Easy</option><option value="Medium" selected>Medium</option><option value="Hard">Hard</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label fw-bold">Marks *</label><input type="number" step="0.25" name="marks" class="form-control" value="1.00" required></div>
                <div class="col-md-3"><label class="form-label fw-bold">Negative Marks</label><input type="number" step="0.25" name="negative_marks" class="form-control" value="0.00" required></div>
            </div>

            <div class="mb-4"><label class="form-label fw-bold">Explanation (Optional)</label><textarea name="explanation" class="form-control" rows="2" placeholder="Visible to students after the exam if enabled..."></textarea></div>
            
            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold">Save Question</button>
        </form>
    </div>
</div>

<script>
function filterSubjects() {
    const tradeId = document.getElementById('trade_id').value;
    document.querySelectorAll('#subject_id option').forEach(opt => {
        if (opt.value === "") return;
        opt.style.display = (opt.dataset.trade === tradeId || tradeId === "") ? 'block' : 'none';
    });
    document.getElementById('subject_id').value = "";
}
document.getElementById('question_type').addEventListener('change', function() {
    const isTF = (this.value === 'true_false');
    document.getElementById('opt_c_group').style.display = isTF ? 'none' : 'block';
    document.getElementById('opt_d_group').style.display = isTF ? 'none' : 'block';
    document.querySelectorAll('.mcq-only').forEach(el => el.style.display = isTF ? 'none' : 'block');
    document.getElementById('opt_a').value = isTF ? 'True' : ''; document.getElementById('opt_a').readOnly = isTF;
    document.getElementById('opt_b').value = isTF ? 'False' : ''; document.getElementById('opt_b').readOnly = isTF;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>