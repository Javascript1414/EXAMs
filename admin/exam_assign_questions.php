<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$exam_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, t.trade_name, s.subject_name FROM exams e JOIN trades t ON e.trade_id = t.id JOIN subjects s ON e.subject_id = s.id WHERE e.id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) redirect('/admin/exams.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $method = $_POST['assignment_method'] ?? 'manual';
        
        // Clear existing questions for a fresh map
        $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$exam_id]);
        $insertStmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        
        $added = 0;
        if ($method === 'manual') {
            $q_ids = $_POST['question_ids'] ?? [];
            foreach ($q_ids as $qid) {
                $insertStmt->execute([$exam_id, (int)$qid]);
                $added++;
            }
            $_SESSION['success_message'] = "Manual assignment complete. {$added} questions mapped.";
        } elseif ($method === 'auto') {
            $easy_count = (int)($_POST['easy_count'] ?? 0);
            $medium_count = (int)($_POST['medium_count'] ?? 0);
            $hard_count = (int)($_POST['hard_count'] ?? 0);
            
            $fetchRandom = $pdo->prepare("SELECT id FROM questions WHERE subject_id = ? AND status = 'active' AND difficulty = ? ORDER BY RAND() LIMIT ?");
            foreach (['Easy' => $easy_count, 'Medium' => $medium_count, 'Hard' => $hard_count] as $diff => $limit) {
                if ($limit > 0) {
                    $fetchRandom->bindValue(1, $exam['subject_id'], PDO::PARAM_INT);
                    $fetchRandom->bindValue(2, $diff, PDO::PARAM_STR);
                    $fetchRandom->bindValue(3, $limit, PDO::PARAM_INT);
                    $fetchRandom->execute();
                    $res = $fetchRandom->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($res as $qid) {
                        $insertStmt->execute([$exam_id, $qid]);
                        $added++;
                    }
                }
            }
            $_SESSION['success_message'] = "Auto assignment complete. {$added} random questions mapped.";
        }
        redirect('/admin/exam_assign_questions.php?id=' . $exam_id);
    }
}

// Fetch all available active questions for this subject
$qStmt = $pdo->prepare("SELECT id, question_text, difficulty, marks FROM questions WHERE subject_id = ? AND status = 'active'");
$qStmt->execute([$exam['subject_id']]);
$allQuestions = $qStmt->fetchAll();

// Fetch currently assigned
$aStmt = $pdo->prepare("SELECT question_id FROM exam_questions WHERE exam_id = ?");
$aStmt->execute([$exam_id]);
$assignedIds = $aStmt->fetchAll(PDO::FETCH_COLUMN);

// Statistics for Auto assignment
$diffCounts = ['Easy' => 0, 'Medium' => 0, 'Hard' => 0];
foreach ($allQuestions as $q) { $diffCounts[$q['difficulty']]++; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Assign Questions</h3>
            <p class="text-muted mb-0"><?= htmlspecialchars($exam['exam_name']) ?> &bull; <?= htmlspecialchars($exam['subject_name']) ?></p>
        </div>
        <a href="exams.php" class="btn btn-outline-secondary btn-sm">Done / Back to Exams</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <ul class="nav nav-pills mb-4" id="assignmentTabs" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual" type="button" role="tab">Method 1: Manual Selection</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link bg-light text-dark ms-2 border" id="auto-tab" data-bs-toggle="pill" data-bs-target="#auto" type="button" role="tab">Method 2: Automatic Random</button></li>
        </ul>
        <div class="tab-content" id="assignmentTabsContent">
            
            <!-- Manual Assignment Tab -->
            <div class="tab-pane fade show active" id="manual" role="tabpanel">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="assignment_method" value="manual">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">Available Questions for <?= htmlspecialchars($exam['subject_name']) ?></h5>
                        <button type="submit" class="btn btn-primary" <?= empty($allQuestions) ? 'disabled' : '' ?>><i data-lucide="save" class="me-2" style="width: 16px;"></i> Save Checked Questions</button>
                    </div>
                    <?php if(empty($allQuestions)): ?>
                    <div class="alert alert-warning" role="alert">
                        <i data-lucide="alert-circle" class="me-2" style="width: 18px; display: inline;"></i>
                        <strong>No Questions Available</strong><br>
                        No active questions found for this subject. Please <a href="question_add.php" class="alert-link">add questions</a> or <a href="questions.php" class="alert-link">activate existing questions</a> first.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 50px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                    <th>Question Text</th>
                                    <th>Difficulty</th>
                                    <th>Marks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allQuestions as $q): ?>
                                <tr>
                                    <td><input type="checkbox" name="question_ids[]" value="<?= $q['id'] ?>" class="form-check-input q-check" <?= in_array($q['id'], $assignedIds) ? 'checked' : '' ?>></td>
                                    <td class="text-truncate" style="max-width: 400px;" title="<?= htmlspecialchars($q['question_text']) ?>"><?= htmlspecialchars($q['question_text']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $q['difficulty'] ?></span></td>
                                    <td><?= (float)$q['marks'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Automatic Assignment Tab -->
            <div class="tab-pane fade" id="auto" role="tabpanel">
                <?php if(empty($allQuestions)): ?>
                <div class="alert alert-warning" role="alert">
                    <i data-lucide="alert-circle" class="me-2" style="width: 18px; display: inline;"></i>
                    <strong>No Questions Available</strong><br>
                    Cannot generate exam map - no active questions found for this subject. Please <a href="question_add.php" class="alert-link">add questions</a> first.
                </div>
                <?php else: ?>
                <form method="POST" action="" class="bg-light p-4 rounded border">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="assignment_method" value="auto">
                    <h5 class="fw-bold mb-3 text-dark">Random Question Generator</h5>
                    <p class="text-muted small mb-4">Enter the quantity of questions you want randomly pulled from the active question bank for this subject. This will clear existing questions and generate a fresh exam map.</p>
                    <div class="row mb-4">
                        <div class="col-md-4"><label class="form-label fw-bold text-success">Easy Questions</label>
                            <input type="number" name="easy_count" class="form-control" min="0" max="<?= $diffCounts['Easy'] ?>" value="0"><small class="text-muted">Max Available: <?= $diffCounts['Easy'] ?></small></div>
                        <div class="col-md-4"><label class="form-label fw-bold text-warning">Medium Questions</label>
                            <input type="number" name="medium_count" class="form-control" min="0" max="<?= $diffCounts['Medium'] ?>" value="0"><small class="text-muted">Max Available: <?= $diffCounts['Medium'] ?></small></div>
                        <div class="col-md-4"><label class="form-label fw-bold text-danger">Hard Questions</label>
                            <input type="number" name="hard_count" class="form-control" min="0" max="<?= $diffCounts['Hard'] ?>" value="0"><small class="text-muted">Max Available: <?= $diffCounts['Hard'] ?></small></div>
                    </div>
                    <button type="submit" class="btn btn-success fw-bold" onclick="return confirm('This will overwrite any currently mapped questions for this exam. Continue?');"><i data-lucide="zap" class="me-2" style="width: 16px;"></i> Auto-Generate Exam Map</button>
                </form>
                <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function(e) { document.querySelectorAll('.q-check').forEach(cb => cb.checked = e.target.checked); });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>