<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$exam_id = (int)($_GET['id'] ?? ($_POST['exam_id'] ?? 0));

// Fetch active attempt
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
$stmt->execute([$exam_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['status'] !== 'in_progress') {
    redirect('/student/exams.php');
}

// Fetch Exam Config
$exam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$exam->execute([$exam_id]);
$examConfig = $exam->fetch();

// Calculate Time Remaining
$timeElapsed = time() - strtotime($attempt['started_at']);
$timeTotal = $examConfig['duration_minutes'] * 60;
$timeRemaining = max(0, $timeTotal - $timeElapsed);

if ($timeRemaining <= 0) {
    redirect('/student/exam_submit.php?id=' . $attempt['id']);
}

// AJAX: Auto Save Answer Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    header('Content-Type: application/json');
    
    $question_id = (int)$_POST['question_id'];
    $answer_status = sanitizeInput($_POST['answer_status']);
    $selected_idx = $_POST['selected_idx'] !== '' ? (int)$_POST['selected_idx'] : null;
    
    // Get the option_order to map visual index back to real option letter
    $optOrder = 'ABCD';
    
    $realOption = null;
    if ($selected_idx !== null && $optOrder && isset($optOrder[$selected_idx])) {
        $realOption = $optOrder[$selected_idx];
    }

    $updateStmt = $pdo->prepare("UPDATE exam_answers SET selected_answer = ?, answer_status = ? WHERE attempt_id = ? AND question_id = ?");
    $success = $updateStmt->execute([$realOption, $answer_status, $attempt['id'], $question_id]);
    
    echo json_encode(['success' => $success, 'real_option' => $realOption]);
    exit;
}

// Fetch all mapped questions and current answers
$qQuery = "SELECT ea.question_id, ea.answer_status, ea.selected_answer, 
                  q.question_text, q.question_type, q.option_a, q.option_b, q.option_c, q.option_d, q.marks 
           FROM exam_answers ea 
           JOIN questions q ON ea.question_id = q.id 
           WHERE ea.attempt_id = ? ORDER BY ea.id ASC"; // Keep original random insertion order
$qStmt = $pdo->prepare($qQuery);
$qStmt->execute([$attempt['id']]);
$data = $qStmt->fetchAll();

// Transform data for JSON JS payload
$questionsJson = [];
foreach ($data as $i => $row) {
    // Map visual index
    $orderStr = 'ABCD';
    $optionsMap = ['A' => $row['option_a'], 'B' => $row['option_b'], 'C' => $row['option_c'], 'D' => $row['option_d']];
    
    $visualOptions = [];
    $selectedIndex = null;
    
    for ($j = 0; $j < strlen($orderStr); $j++) {
        $letter = $orderStr[$j];
        if ($optionsMap[$letter] !== null) {
            $visualOptions[] = $optionsMap[$letter];
            if ($row['selected_answer'] === $letter) {
                $selectedIndex = $j;
            }
        }
    }

    $questionsJson[] = [
        'q_id' => $row['question_id'],
        'text' => $row['question_text'],
        'type' => $row['question_type'],
        'marks' => $row['marks'],
        'options' => $visualOptions,
        'status' => $row['answer_status'],
        'selected_idx' => $selectedIndex
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Player - <?= htmlspecialchars($examConfig['exam_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #F3F4F6; user-select: none; }
        .palette-btn { width: 40px; height: 40px; border-radius: 6px; font-weight: bold; border: 1px solid #ddd; margin: 4px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: #fff; }
        .status-not_visited { background: #6c757d; border-color: #6c757d; }
        .status-not_answered { background: #dc3545; border-color: #dc3545; }
        .status-answered { background: #198754; border-color: #198754; }
        .status-marked_for_review { background: #6f42c1; border-color: #6f42c1; }
        .palette-btn.active-q { box-shadow: 0 0 0 3px rgba(0,0,0,0.3); border-color: #000; }
        .option-box { cursor: pointer; padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; transition: all 0.2s; }
        .option-box:hover { background: #f9fafb; border-color: #d1d5db; }
        .option-box.selected { border-color: #0056D2; background: #eff6ff; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark px-4 py-2 sticky-top shadow-sm">
        <span class="navbar-brand mb-0 h1 fw-bold"><?= htmlspecialchars($examConfig['exam_name']) ?></span>
        <div class="d-flex align-items-center">
            <div class="bg-light text-dark px-3 py-1 rounded fw-bold d-flex align-items-center me-3 fs-5">
                <i data-lucide="clock" class="me-2 text-danger"></i> <span id="timerDisplay">00:00:00</span>
            </div>
            <button class="btn btn-outline-light btn-sm me-2" onclick="toggleFullscreen()"><i data-lucide="maximize" style="width:16px;"></i></button>
            <form action="exam_submit.php" method="POST" id="submitForm"><input type="hidden" name="id" value="<?= $attempt['id'] ?>"><button type="submit" class="btn btn-danger fw-bold" onclick="return confirm('Are you sure you want to final submit?');">Finish Exam</button></form>
        </div>
    </nav>

    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Main Question Area -->
            <div class="col-md-9 mb-3">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center p-3">
                        <h5 class="mb-0 fw-bold text-primary">Question <span id="qNum">1</span></h5>
                        <span class="badge bg-light text-dark border">Marks: <span id="qMarks">1.0</span></span>
                    </div>
                    <div class="card-body p-4" style="min-height: 350px;">
                        <h5 id="qText" class="mb-4 text-dark" style="line-height:1.6;"></h5>
                        <div id="optionsContainer"></div>
                    </div>
                    <div class="card-footer bg-light p-3 border-top d-flex justify-content-between flex-wrap gap-2">
                        <div>
                            <button class="btn btn-outline-secondary px-4 fw-semibold" onclick="navQuestion('prev')">Previous</button>
                            <button class="btn btn-outline-warning px-4 fw-semibold ms-2" onclick="markForReview()">Mark for Review & Next</button>
                        </div>
                        <div>
                            <button class="btn btn-outline-danger px-4 fw-semibold me-2" onclick="clearResponse()">Clear Response</button>
                            <button class="btn btn-success px-5 fw-bold" onclick="saveAndNext()">Save & Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Palette -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom p-3 fw-bold">Question Palette</div>
                    <div class="card-body p-3">
                        <div id="paletteGrid" class="d-flex flex-wrap mb-4"></div>
                        <hr>
                        <div class="small text-muted">
                            <div class="d-flex align-items-center mb-2"><div class="palette-btn status-answered m-0 me-2" style="width:20px;height:20px;"></div> Answered</div>
                            <div class="d-flex align-items-center mb-2"><div class="palette-btn status-not_answered m-0 me-2" style="width:20px;height:20px;"></div> Not Answered</div>
                            <div class="d-flex align-items-center mb-2"><div class="palette-btn status-marked_for_review m-0 me-2" style="width:20px;height:20px;"></div> Marked for Review</div>
                            <div class="d-flex align-items-center"><div class="palette-btn status-not_visited m-0 me-2" style="width:20px;height:20px;"></div> Not Visited</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        let questions = <?= json_encode($questionsJson) ?>;
        let currentIdx = 0;
        let timeRemaining = <?= $timeRemaining ?>;
        const examId = <?= $exam_id ?>;

        // Timer Logic
        setInterval(() => {
            if (timeRemaining <= 0) { document.getElementById('submitForm').submit(); return; }
            timeRemaining--;
            let h = Math.floor(timeRemaining / 3600).toString().padStart(2, '0');
            let m = Math.floor((timeRemaining % 3600) / 60).toString().padStart(2, '0');
            let s = (timeRemaining % 60).toString().padStart(2, '0');
            document.getElementById('timerDisplay').innerText = `${h}:${m}:${s}`;
        }, 1000);

        function renderPalette() {
            const grid = document.getElementById('paletteGrid');
            grid.innerHTML = '';
            questions.forEach((q, i) => {
                let btn = document.createElement('div');
                btn.className = `palette-btn status-${q.status} ${i === currentIdx ? 'active-q' : ''}`;
                btn.innerText = i + 1;
                btn.onclick = () => jumpToQuestion(i);
                grid.appendChild(btn);
            });
        }

        function renderQuestion() {
            const q = questions[currentIdx];
            if (q.status === 'not_visited') updateState('not_answered', q.selected_idx, false);
            
            document.getElementById('qNum').innerText = currentIdx + 1;
            document.getElementById('qMarks').innerText = q.marks;
            document.getElementById('qText').innerText = q.text;
            
            const container = document.getElementById('optionsContainer');
            container.innerHTML = '';
            q.options.forEach((opt, idx) => {
                let div = document.createElement('div');
                div.className = `option-box ${q.selected_idx === idx ? 'selected' : ''}`;
                div.innerHTML = `<div class="d-flex align-items-center"><input type="radio" name="opt" class="form-check-input me-3" style="transform: scale(1.2);" ${q.selected_idx === idx ? 'checked' : ''}> <span class="fs-5">${opt}</span></div>`;
                div.onclick = () => { q.selected_idx = idx; renderQuestion(); };
                container.appendChild(div);
            });
            renderPalette();
        }

        function updateState(status, selIdx, navigate = true) {
            questions[currentIdx].status = status;
            questions[currentIdx].selected_idx = selIdx;
            let fd = new FormData();
            fd.append('ajax_save', '1'); fd.append('question_id', questions[currentIdx].q_id); fd.append('answer_status', status); fd.append('selected_idx', selIdx !== null ? selIdx : '');
            fetch('', { method: 'POST', body: fd });
            if (navigate) { currentIdx = (currentIdx + 1) % questions.length; renderQuestion(); } else { renderPalette(); }
        }

        function saveAndNext() { updateState(questions[currentIdx].selected_idx !== null ? 'answered' : 'not_answered', questions[currentIdx].selected_idx); }
        function markForReview() { updateState('marked_for_review', questions[currentIdx].selected_idx); }
        function clearResponse() { questions[currentIdx].selected_idx = null; updateState('not_answered', null, false); renderQuestion(); }
        function navQuestion(dir) { if(dir==='prev') currentIdx = (currentIdx - 1 + questions.length) % questions.length; renderQuestion(); }
        function jumpToQuestion(idx) { currentIdx = idx; renderQuestion(); }
        function toggleFullscreen() { if (!document.fullscreenElement) document.documentElement.requestFullscreen(); else if (document.exitFullscreen) document.exitFullscreen(); }
        
        renderQuestion();
    </script>
</body>
</html>