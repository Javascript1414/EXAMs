<?php
/**
 * Teacher: Manage Exam Questions
 * Add questions to exam created by this teacher
 * Can only add questions to own exams for assigned subjects
 */

require_once '../config.php';
require_once '../includes/db.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied: Teachers only');
}

$teacher_id = $_SESSION['user_id'];
$exam_id = (int)($_GET['exam_id'] ?? 0);

if (!$exam_id) {
    die('Exam ID required');
}

// CRITICAL: Verify this teacher created this exam AND teaches this subject
$verify_exam = $pdo->prepare("
    SELECT e.id, e.exam_name, e.subject_id, s.subject_name, t.trade_name,
           (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
    FROM exams e
    JOIN subjects s ON e.subject_id = s.id
    JOIN trades t ON e.trade_id = t.id
    WHERE e.id = ? 
    AND e.created_by = ?
    AND e.subject_id IN (
        SELECT st.subject_id FROM subject_teacher st WHERE st.teacher_id = ?
    )
    LIMIT 1
");
$verify_exam->execute([$exam_id, $teacher_id, $teacher_id]);
$exam = $verify_exam->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    http_response_code(403);
    die('You do not have permission to manage this exam');
}

$subject_id = $exam['subject_id'];

// Get available questions for this subject (from question bank)
$questions_query = $pdo->prepare("
    SELECT q.id, q.question_text, q.marks, 
           IF(eq.id IS NOT NULL, 1, 0) as is_added
    FROM questions q
    LEFT JOIN exam_questions eq ON q.id = eq.question_id AND eq.exam_id = ?
    WHERE q.subject_id = ? AND q.status = 'active'
    ORDER BY q.marks DESC, q.id DESC
");
$questions_query->execute([$exam_id, $subject_id]);
$available_questions = $questions_query->fetchAll(PDO::FETCH_ASSOC);

// Get already added questions
$added_query = $pdo->prepare("
    SELECT eq.id, eq.question_id, q.question_text, q.marks, eq.created_at
    FROM exam_questions eq
    JOIN questions q ON eq.question_id = q.id
    WHERE eq.exam_id = ?
    ORDER BY eq.marks DESC
");
$added_query->execute([$exam_id]);
$added_questions = $added_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate total marks
$total_added_marks = array_sum(array_column($added_questions, 'marks'));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_question') {
        try {
            $question_id = (int)($_POST['question_id'] ?? 0);
            
            if (!$question_id) throw new Exception('Question ID required');

            // Verify question belongs to this subject
            $check_q = $pdo->prepare("SELECT id FROM questions WHERE id = ? AND subject_id = ? LIMIT 1");
            $check_q->execute([$question_id, $subject_id]);
            if (!$check_q->fetch()) {
                throw new Exception('Invalid question for this subject');
            }

            // Add question to exam
            $add = $pdo->prepare("
                INSERT IGNORE INTO exam_questions (exam_id, question_id, created_at, updated_at)
                VALUES (?, ?, NOW(), NOW())
            ");
            $add->execute([$exam_id, $question_id]);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Question added']);
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'remove_question') {
        try {
            $exam_question_id = (int)($_POST['exam_question_id'] ?? 0);
            
            $remove = $pdo->prepare("DELETE FROM exam_questions WHERE id = ? AND exam_id = ?");
            $remove->execute([$exam_question_id, $exam_id]);

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Question removed']);
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exam Questions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        h2 { color: #333; margin-bottom: 15px; font-size: 1.3em; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover { background: #f9f9f9; }
        .btn {
            background: #667eea;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn:hover { background: #5568d3; }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover { background: #c82333; }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover { background: #218838; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #0c5460; }
        .marks-badge {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .total-marks {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Manage Exam Questions</h1>
            <p><?= htmlspecialchars($exam['exam_name']) ?> - <?= htmlspecialchars($exam['subject_name']) ?></p>
        </div>

        <div class="card">
            <div class="alert alert-info">
                <strong>📌 Info:</strong> You can only add questions from your subject's question bank to this exam. Questions are managed by the admin.
            </div>

            <div class="total-marks">
                Total Marks Added: <span id="totalMarks"><?= $total_added_marks ?></span> marks
            </div>
        </div>

        <div class="grid">
            <!-- Available Questions -->
            <div class="card">
                <h2>❓ Available Questions (<?= count($available_questions) ?>)</h2>
                <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">Click to add questions to exam</p>

                <?php if ($available_questions): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Marks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($available_questions as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($q['question_text'], 0, 50)) ?>...</td>
                            <td><span class="marks-badge"><?= $q['marks'] ?></span></td>
                            <td>
                                <?php if ($q['is_added']): ?>
                                    <span style="color: #28a745; font-weight: 600;">✓ Added</span>
                                <?php else: ?>
                                    <button class="btn btn-success" onclick="addQuestion(<?= $q['id'] ?>)">Add</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color: #666;">No questions available in the question bank for this subject.</p>
                <?php endif; ?>
            </div>

            <!-- Added Questions -->
            <div class="card">
                <h2>✓ Questions Added (<?= count($added_questions) ?>)</h2>
                <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">Questions in this exam</p>

                <?php if ($added_questions): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Marks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($added_questions as $q): ?>
                        <tr>
                            <td><?= htmlspecialchars(substr($q['question_text'], 0, 50)) ?>...</td>
                            <td><span class="marks-badge"><?= $q['marks'] ?></span></td>
                            <td>
                                <button class="btn btn-danger" onclick="removeQuestion(<?= $q['id'] ?>)">Remove</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color: #666;">No questions added yet. Add questions from the left panel.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function addQuestion(questionId) {
            const formData = new FormData();
            formData.append('action', 'add_question');
            formData.append('question_id', questionId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        function removeQuestion(examQuestionId) {
            if (confirm('Remove this question?')) {
                const formData = new FormData();
                formData.append('action', 'remove_question');
                formData.append('exam_question_id', examQuestionId);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
