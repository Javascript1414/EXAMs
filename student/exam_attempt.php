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

// Fetch Student Information
$studentStmt = $pdo->prepare("
    SELECT u.full_name, u.phone, u.created_at, t.trade_name,
           up.profile_photo_path
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$studentStmt->execute([$_SESSION['user_id']]);
$studentInfo = $studentStmt->fetch();

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

    // First, check if exam_answers record exists
    $checkStmt = $pdo->prepare("SELECT id FROM exam_answers WHERE attempt_id = ? AND question_id = ?");
    $checkStmt->execute([$attempt['id'], $question_id]);
    $existingAnswer = $checkStmt->fetch();

    if ($existingAnswer) {
        // Update existing
        $updateStmt = $pdo->prepare("UPDATE exam_answers SET selected_answer = ?, answer_status = ? WHERE attempt_id = ? AND question_id = ?");
        $success = $updateStmt->execute([$realOption, $answer_status, $attempt['id'], $question_id]);
    } else {
        // Create new
        $insertStmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, selected_answer, answer_status) VALUES (?, ?, ?, ?)");
        $success = $insertStmt->execute([$attempt['id'], $question_id, $realOption, $answer_status]);
    }
    
    echo json_encode(['success' => $success, 'real_option' => $realOption]);
    exit;
}

// Fetch all mapped questions and current answers
$qQuery = "SELECT ea.question_id, ea.answer_status, ea.selected_answer, 
                  q.question_text, q.question_type, q.option_a, q.option_b, q.option_c, q.option_d, q.marks 
           FROM exam_answers ea 
           JOIN questions q ON ea.question_id = q.id 
           WHERE ea.attempt_id = ? ORDER BY ea.id ASC";
$qStmt = $pdo->prepare($qQuery);
$qStmt->execute([$attempt['id']]);
$data = $qStmt->fetchAll();

// If no data, try fallback
if (empty($data)) {
    $fallbackQuery = "SELECT eq.question_id, 'not_visited' as answer_status, NULL as selected_answer,
                            q.question_text, q.question_type, q.option_a, q.option_b, q.option_c, q.option_d, q.marks
                     FROM exam_questions eq
                     JOIN questions q ON eq.question_id = q.id
                     WHERE eq.exam_id = ?
                     ORDER BY eq.id ASC";
    $fallbackStmt = $pdo->prepare($fallbackQuery);
    $fallbackStmt->execute([$exam_id]);
    $data = $fallbackStmt->fetchAll();
}

// Transform data for JSON
$questionsJson = [];
foreach ($data as $i => $row) {
    $orderStr = 'ABCD';
    $optionsMap = ['A' => $row['option_a'], 'B' => $row['option_b'], 'C' => $row['option_c'], 'D' => $row['option_d']];
    
    $visualOptions = [];
    $selectedIndex = null;
    
    for ($j = 0; $j < strlen($orderStr); $j++) {
        $letter = $orderStr[$j];
        if (!empty($optionsMap[$letter])) {
            $visualOptions[] = $optionsMap[$letter];
            if ($row['selected_answer'] === $letter) {
                $selectedIndex = $j;
            }
        }
    }

    if (!empty($visualOptions)) {
        $questionsJson[] = [
            'q_id' => $row['question_id'],
            'text' => $row['question_text'] ?? 'Question text not found',
            'type' => $row['question_type'] ?? 'mcq',
            'marks' => $row['marks'] ?? 1,
            'options' => $visualOptions,
            'status' => $row['answer_status'] ?? 'not_visited',
            'selected_idx' => $selectedIndex
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Player - <?= htmlspecialchars($examConfig['exam_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-size: 16px; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #e8e8e8; }

        /* STICKY HEADER */
        .exam-header {
            background: linear-gradient(135deg, #1a5490 0%, #2b6ba6 100%);
            color: white;
            padding: 18px 28px;
            display: grid;
            grid-template-columns: 1.5fr auto 1fr;
            align-items: center;
            gap: 24px;
            box-shadow: 0 6px 24px rgba(26, 84, 144, 0.25);
            position: sticky;
            top: 0;
            z-index: 1000;
            min-height: 70px;
        }

        .exam-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
        }

        .timer-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .timer-section > span {
            font-size: 15px;
            font-weight: 500;
        }

        .timer-box {
            background: white;
            color: #1a5490;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
            min-width: 120px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-family: 'Courier New', monospace;
        }

        .timer-box.warning {
            background: #fff3cd;
            color: #856404;
        }

        .timer-box.critical {
            background: #f8d7da;
            color: #721c24;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .header-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-header {
            padding: 9px 18px;
            font-size: 13px;
            font-weight: 600;
            border: 2px solid rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.12);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 40px;
            backdrop-filter: blur(10px);
        }

        .btn-header:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.6);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        .btn-submit {
            background: #d32f2f;
            border-color: #d32f2f;
            min-width: 130px;
        }

        .btn-submit:hover {
            background: #c62828;
        }

        /* MAIN LAYOUT */
        .exam-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 14px;
            padding: 14px;
            height: calc(100vh - 104px);
            overflow: hidden;
        }

        /* LEFT: QUESTION PANEL */
        .question-panel {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .q-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8e9eb 100%);
            border-bottom: 2px solid #ddd;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .q-title {
            font-size: 19px;
            font-weight: 700;
            color: #1a5490;
        }

        .q-marks {
            font-size: 14px;
            font-weight: 600;
            color: #f57c00;
            background: linear-gradient(135deg, #fff3cd 0%, #ffe8a6 100%);
            padding: 6px 14px;
            border-radius: 20px;
            box-shadow: 0 2px 6px rgba(245, 124, 0, 0.15);
        }

        .q-content {
            flex: 1;
            padding: 24px 26px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            background: #fafbfc;
        }

        .q-text {
            font-size: 17px;
            font-weight: 500;
            color: #212529;
            line-height: 1.7;
            letter-spacing: 0.3px;
        }

        .options-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .option-item {
            display: flex;
            align-items: flex-start;
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            min-height: 65px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }

        .option-item:hover {
            background: linear-gradient(135deg, #f0f7ff 0%, #e8f4ff 100%);
            border-color: #1a5490;
            box-shadow: 0 6px 16px rgba(26, 84, 144, 0.15);
            transform: translateX(6px) translateY(-2px);
        }

        .option-item.selected {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3f6ff 100%);
            border: 2px solid #1a5490;
            box-shadow: 0 0 0 4px rgba(26, 84, 144, 0.08), inset 0 0 0 1px #1a5490;
        }

        .option-radio {
            margin-right: 14px;
            margin-top: 2px;
            flex-shrink: 0;
            cursor: pointer;
            width: 22px;
            height: 22px;
            accent-color: #1a5490;
        }

        .option-text {
            font-size: 17px;
            color: #333;
            line-height: 1.6;
            word-break: break-word;
            flex: 1;
        }

        .q-footer {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8e9eb 100%);
            border-top: 2px solid #ddd;
            padding: 16px 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            position: sticky;
            bottom: 0;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.04);
        }

        .btn-action {
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid #999;
            background: #e8e8e8;
            color: #333;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 40px;
            min-width: 90px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .btn-action:hover {
            background: linear-gradient(135deg, #d8d8d8 0%, #c8c8c8 100%);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }

        .btn-action:active {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.12);
        }

        .btn-action.primary {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            border-color: #1b5e20;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.2);
        }

        .btn-action.primary:hover {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3820 100%);
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.3);
        }

        .btn-action.warning {
            background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
            color: white;
            border-color: #e65100;
            box-shadow: 0 2px 8px rgba(245, 124, 0, 0.2);
        }

        .btn-action.warning:hover {
            background: linear-gradient(135deg, #e65100 0%, #d84315 100%);
            box-shadow: 0 6px 16px rgba(245, 124, 0, 0.3);
        }

        .btn-action.danger {
            background: linear-gradient(135deg, #c62828 0%, #b71c1c 100%);
            color: white;
            border-color: #b71c1c;
            box-shadow: 0 2px 8px rgba(198, 40, 40, 0.2);
        }

        .btn-action.danger:hover {
            background: linear-gradient(135deg, #b71c1c 0%, #8b0000 100%);
            box-shadow: 0 6px 16px rgba(198, 40, 40, 0.3);
        }

        /* RIGHT COLUMN */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
        }

        /* CANDIDATE CARD */
        .candidate-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .candidate-header {
            font-size: 13px;
            font-weight: 700;
            color: #1a5490;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ddd;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a5490 0%, #2b6ba6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 36px;
            font-weight: bold;
            margin: 0 auto 12px;
            border: 3px solid #1a5490;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(26, 84, 144, 0.3);
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-name {
            font-weight: 700;
            font-size: 16px;
            color: #212529;
            margin-bottom: 10px;
            word-break: break-word;
            line-height: 1.3;
        }

        .candidate-info {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .info-row {
            margin-bottom: 6px;
        }

        /* PALETTE CARD */
        .palette-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .palette-header {
            font-size: 13px;
            font-weight: 700;
            color: #1a5490;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #ddd;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .palette-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
            margin-bottom: 12px;
        }

        .palette-btn {
            aspect-ratio: 1;
            border: 2px solid #999;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 46px;
        }

        .palette-btn:hover {
            transform: scale(1.08) translateY(-2px);
            box-shadow: 0 6px 14px rgba(0,0,0,0.2);
        }

        .palette-btn:active {
            transform: scale(0.96);
        }

        .palette-btn.answered {
            background: #4caf50;
            color: white;
            border-color: #2e7d32;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .palette-btn.not_answered {
            background: #f44336;
            color: white;
            border-color: #c62828;
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }

        .palette-btn.marked_for_review {
            background: #9c27b0;
            color: white;
            border-color: #6a1b9a;
            box-shadow: 0 2px 8px rgba(156, 39, 176, 0.3);
        }

        .palette-btn.not_visited {
            background: #999;
            color: white;
            border-color: #666;
            box-shadow: 0 2px 8px rgba(153, 153, 153, 0.3);
        }

        .palette-btn.current {
            box-shadow: inset 0 0 0 3px white, 0 0 0 3px #333, 0 4px 12px rgba(0,0,0,0.3);
            font-weight: bold;
            transform: scale(1.08);
        }

        .palette-legend {
            font-size: 12px;
            line-height: 1.6;
            border-top: 2px solid #ddd;
            padding-top: 10px;
        }

        .legend-row {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 5px;
        }

        .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            flex-shrink: 0;
            border: 1px solid #999;
        }

        .legend-dot.answered { background: #4caf50; border-color: #2e7d32; }
        .legend-dot.not_answered { background: #f44336; border-color: #c62828; }
        .legend-dot.marked_for_review { background: #9c27b0; border-color: #6a1b9a; }
        .legend-dot.not_visited { background: #999; border-color: #666; }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #1a5490;
            border-radius: 10px;
            border: 2px solid #f1f1f1;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0d3255;
        }

        /* RESPONSIVE - TABLET */
        @media (max-width: 1200px) {
            .exam-container {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
                height: calc(100vh - 120px);
            }

            .right-panel {
                flex-direction: row;
                height: auto;
                max-height: 220px;
                gap: 14px;
            }

            .palette-grid {
                grid-template-columns: repeat(8, 1fr);
            }

            .candidate-card {
                flex: 0 0 300px;
                min-width: 280px;
            }

            .palette-card {
                flex: 1;
                min-width: 300px;
            }
        }

        /* RESPONSIVE - MOBILE */
        @media (max-width: 768px) {
            .exam-header {
                grid-template-columns: 1fr;
                text-align: center;
                padding: 14px 16px;
                gap: 12px;
                min-height: auto;
            }

            .exam-title {
                font-size: 19px;
            }

            .timer-section {
                justify-content: center;
            }

            .timer-box {
                font-size: 17px;
                padding: 10px 16px;
            }

            .timer-section > span {
                font-size: 14px;
            }

            .header-actions {
                justify-content: center;
            }

            .btn-header {
                font-size: 13px;
                padding: 8px 14px;
                min-height: 40px;
            }

            .exam-container {
                grid-template-columns: 1fr;
                grid-template-rows: 1fr auto;
                padding: 12px;
                gap: 12px;
                height: calc(100vh - 100px);
            }

            .right-panel {
                flex-direction: column;
                max-height: none;
            }

            .palette-grid {
                grid-template-columns: repeat(6, 1fr);
            }

            .q-content {
                padding: 18px 16px;
                gap: 16px;
            }

            .q-title {
                font-size: 17px;
            }

            .q-text {
                font-size: 16px;
                line-height: 1.6;
            }

            .option-text {
                font-size: 16px;
            }

            .options-list {
                gap: 12px;
            }

            .option-item {
                padding: 14px 16px;
                min-height: 60px;
            }

            .btn-action {
                font-size: 13px;
                padding: 10px 14px;
                min-height: 40px;
                min-width: 80px;
                flex: 1;
                min-width: calc(50% - 6px);
            }
        }

        /* RESPONSIVE - SMALL PHONE */
        @media (max-width: 480px) {
            .exam-header {
                grid-template-columns: 1fr;
                padding: 12px;
            }

            .exam-title {
                font-size: 16px;
            }

            .timer-box {
                font-size: 16px;
                min-width: 100px;
                padding: 8px 12px;
            }

            .timer-section > span {
                font-size: 14px;
            }

            .q-title {
                font-size: 17px;
            }

            .q-marks {
                font-size: 13px;
            }

            .q-text {
                font-size: 15px;
            }

            .option-text {
                font-size: 15px;
            }

            .q-text {
                font-size: 16px;
            }

            .option-text {
                font-size: 15px;
            }

            .palette-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 6px;
            }

            .palette-btn {
                font-size: 12px;
                min-height: 44px;
            }

            .candidate-avatar {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }

            .candidate-name {
                font-size: 15px;
            }

            .candidate-info {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="exam-header">
        <div class="exam-title">📝 <?= htmlspecialchars($examConfig['exam_name']) ?></div>
        
        <div class="timer-section">
            <span style="color: white; font-size: 12px;">Time Left:</span>
            <div class="timer-box" id="timerBox">
                <span id="timerDisplay">00:00:00</span>
            </div>
        </div>
        
        <div class="header-actions">
            <button class="btn-header" onclick="toggleFullscreen()">⛶ Fullscreen</button>
            <form action="exam_submit.php" method="POST" id="submitForm" style="display: inline;">
                <input type="hidden" name="id" value="<?= $attempt['id'] ?>">
                <button type="submit" class="btn-header btn-submit" onclick="return confirm('Submit your exam now?');">Submit Exam</button>
            </form>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="exam-container">
        <!-- LEFT: QUESTION PANEL -->
        <div class="question-panel">
            <div class="q-header">
                <div class="q-title">Question <span id="qNum">1</span> of <span id="qTotal">0</span></div>
                <div class="q-marks">Marks: <span id="qMarks">0</span></div>
            </div>

            <div class="q-content">
                <div class="q-text" id="qText"></div>
                <div class="options-list" id="optionsContainer"></div>
            </div>

            <div class="q-footer">
                <button class="btn-action" onclick="navQuestion('prev')">← Previous</button>
                <button class="btn-action warning" onclick="markForReview()">⚠ Mark for Review</button>
                <button class="btn-action danger" onclick="clearResponse()">✕ Clear</button>
                <button class="btn-action primary" onclick="saveAndNext()">Next →</button>
            </div>
        </div>

        <!-- RIGHT: SIDE PANEL -->
        <div class="right-panel">
            <!-- CANDIDATE INFO -->
            <div class="candidate-card">
                <div class="candidate-header">Candidate Info</div>
                <div class="candidate-avatar">
                    <?php 
                    if ($studentInfo['profile_photo_path']) {
                        echo '<img src="' . htmlspecialchars($studentInfo['profile_photo_path']) . '" alt="Profile">';
                    } else {
                        echo substr($studentInfo['full_name'], 0, 1);
                    }
                    ?>
                </div>
                <div class="candidate-name"><?= htmlspecialchars($studentInfo['full_name']) ?></div>
                <div class="candidate-info">
                    <div class="info-row">📅 <?= date('d/m/Y', strtotime($attempt['started_at'])) ?></div>
                    <div class="info-row">📱 <?= htmlspecialchars($studentInfo['phone'] ?? 'N/A') ?></div>
                    <?php if ($studentInfo['trade_name']): ?>
                    <div class="info-row">🎓 <?= htmlspecialchars($studentInfo['trade_name']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PALETTE -->
            <div class="palette-card">
                <div class="palette-header">Question Palette</div>
                <div class="palette-grid" id="paletteGrid"></div>

                <div class="palette-legend">
                    <div class="legend-row">
                        <div class="legend-dot answered"></div>
                        <span>Answered</span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-dot not_answered"></div>
                        <span>Not Answered</span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-dot marked_for_review"></div>
                        <span>Review</span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-dot not_visited"></div>
                        <span>Not Visited</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let questions = <?= json_encode($questionsJson) ?>;
        let currentIdx = 0;
        let timeRemaining = <?= $timeRemaining ?>;
        let isUpdating = false;

        console.log('Questions loaded:', questions.length);
        
        // Global error handler
        window.addEventListener('error', (event) => {
            console.error('GLOBAL ERROR:', event.error);
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('PROMISE REJECTION:', event.reason);
        });

        // TIMER
        setInterval(() => {
            if (timeRemaining <= 0) {
                document.getElementById('submitForm').submit();
                return;
            }
            timeRemaining--;
            let h = Math.floor(timeRemaining / 3600).toString().padStart(2, '0');
            let m = Math.floor((timeRemaining % 3600) / 60).toString().padStart(2, '0');
            let s = (timeRemaining % 60).toString().padStart(2, '0');
            document.getElementById('timerDisplay').innerText = `${h}:${m}:${s}`;

            const timerBox = document.getElementById('timerBox');
            if (timeRemaining <= 300) {
                timerBox.classList.add('critical');
                timerBox.classList.remove('warning');
            } else if (timeRemaining <= 900) {
                timerBox.classList.add('warning');
                timerBox.classList.remove('critical');
            } else {
                timerBox.classList.remove('warning', 'critical');
            }
        }, 1000);

        // RENDER PALETTE
        function renderPalette() {
            const grid = document.getElementById('paletteGrid');
            grid.innerHTML = '';
            questions.forEach((q, i) => {
                let btn = document.createElement('button');
                btn.className = `palette-btn ${q.status} ${i === currentIdx ? 'current' : ''}`;
                btn.innerText = i + 1;
                btn.onclick = (e) => { e.preventDefault(); jumpToQuestion(i); };
                grid.appendChild(btn);
            });
        }

        // RENDER QUESTION
        function renderQuestion() {
            try {
                if (!questions || questions.length === 0) return;
                if (currentIdx < 0 || currentIdx >= questions.length) currentIdx = 0;
                
                const q = questions[currentIdx];
                if (!q) return;
                
                // Only auto-update if not already updating
                if (q.status === 'not_visited' && !isUpdating) {
                    updateState('not_answered', q.selected_idx, false);
                    return;
                }

                document.getElementById('qNum').innerText = currentIdx + 1;
                document.getElementById('qTotal').innerText = questions.length;
                document.getElementById('qMarks').innerText = q.marks || 0;
                document.getElementById('qText').innerText = q.text || '';

                const container = document.getElementById('optionsContainer');
                container.innerHTML = '';
                if (q.options && q.options.length > 0) {
                    q.options.forEach((opt, idx) => {
                        let label = document.createElement('label');
                        label.className = 'option-item' + (q.selected_idx === idx ? ' selected' : '');
                        label.innerHTML = '<input type="radio" class="option-radio" name="opt" ' + (q.selected_idx === idx ? 'checked' : '') + ' onchange="selectOption(' + idx + ')"><span class="option-text">' + opt + '</span>';
                        container.appendChild(label);
                    });
                }
                renderPalette();
            } catch (err) {
                console.error('ERROR renderQuestion:', err);
            }
        }

        function selectOption(idx) {
            try {
                if (questions && questions[currentIdx]) {
                    questions[currentIdx].selected_idx = idx;
                    renderQuestion();
                }
            } catch (err) {
                console.error('selectOption error:', err);
            }
        }

        function updateState(status, selIdx, navigate = true) {
            try {
                if (isUpdating) return;
                isUpdating = true;
                
                if (!questions[currentIdx]) {
                    isUpdating = false;
                    return;
                }
                
                questions[currentIdx].status = status;
                questions[currentIdx].selected_idx = selIdx;
                let fd = new FormData();
                fd.append('ajax_save', '1');
                fd.append('question_id', questions[currentIdx].q_id);
                fd.append('answer_status', status);
                fd.append('selected_idx', selIdx !== null ? selIdx : '');
                
                fetch('', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(() => {
                        isUpdating = false;
                        if (navigate) {
                            currentIdx = (currentIdx + 1) % questions.length;
                        }
                        renderQuestion();
                        renderPalette();
                    })
                    .catch(err => {
                        console.error('Save error:', err);
                        isUpdating = false;
                    });
            } catch (err) {
                console.error('updateState error:', err);
                isUpdating = false;
            }
        }

        function saveAndNext() {
            try {
                if (questions && questions[currentIdx]) {
                    const sel = questions[currentIdx].selected_idx;
                    updateState(sel !== null ? 'answered' : 'not_answered', sel);
                }
            } catch (err) {
                console.error('saveAndNext error:', err);
            }
        }

        function markForReview() {
            try {
                if (questions && questions[currentIdx]) {
                    updateState('marked_for_review', questions[currentIdx].selected_idx);
                }
            } catch (err) {
                console.error('markForReview error:', err);
            }
        }

        function clearResponse() {
            try {
                if (questions && questions[currentIdx]) {
                    questions[currentIdx].selected_idx = null;
                    updateState('not_answered', null, false);
                    renderQuestion();
                }
            } catch (err) {
                console.error('clearResponse error:', err);
            }
        }

        function navQuestion(dir) {
            try {
                if (!questions || questions.length === 0) return;
                if (dir === 'prev') {
                    currentIdx = (currentIdx - 1 + questions.length) % questions.length;
                } else {
                    currentIdx = (currentIdx + 1) % questions.length;
                }
                renderQuestion();
            } catch (err) {
                console.error('navQuestion error:', err);
            }
        }

        function jumpToQuestion(idx) {
            try {
                if (questions && idx >= 0 && idx < questions.length) {
                    currentIdx = idx;
                    renderQuestion();
                }
            } catch (err) {
                console.error('jumpToQuestion error:', err);
            }
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(e => console.log(e));
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }

        renderQuestion();
    </script>
</body>
</html>
