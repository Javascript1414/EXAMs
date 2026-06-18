<?php
/**
 * Teacher: Create Exam for Assigned Subjects
 * Only shows subjects that are assigned to this teacher
 * Only this teacher can create exams for their subjects
 */

require_once '../config.php';
require_once '../includes/db.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied: Teachers only');
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned subjects ONLY
$subjects_query = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, t.trade_name, t.id as trade_id
    FROM subject_teacher st
    JOIN subjects s ON st.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    WHERE st.teacher_id = ?
    ORDER BY t.trade_name, s.subject_name
");
$subjects_query->execute([$teacher_id]);
$assigned_subjects = $subjects_query->fetchAll(PDO::FETCH_ASSOC);

if (!$assigned_subjects) {
    die('You have no subjects assigned. Contact admin to assign subjects.');
}

// Handle exam creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_exam') {
    try {
        $subject_id = (int)$_POST['subject_id'] ?? 0;
        $exam_name = $_POST['exam_name'] ?? '';
        $exam_date = $_POST['exam_date'] ?? '';
        $total_marks = (int)($_POST['total_marks'] ?? 0);
        $passing_marks = (int)($_POST['passing_marks'] ?? 0);
        $exam_type = $_POST['exam_type'] ?? 'standard';
        $time_limit = (int)($_POST['time_limit'] ?? 60);

        // Validate input
        if (empty($exam_name) || empty($subject_id) || empty($exam_date) || $total_marks <= 0) {
            throw new Exception('All fields are required');
        }

        // CRITICAL: Verify this teacher teaches this subject
        $verify_subject = $pdo->prepare("
            SELECT st.id FROM subject_teacher st
            WHERE st.subject_id = ? AND st.teacher_id = ?
            LIMIT 1
        ");
        $verify_subject->execute([$subject_id, $teacher_id]);
        if (!$verify_subject->fetch()) {
            throw new Exception('You do not have permission to create exams for this subject');
        }

        // Get trade_id for this subject
        $get_trade = $pdo->prepare("SELECT trade_id FROM subjects WHERE id = ? LIMIT 1");
        $get_trade->execute([$subject_id]);
        $subject_info = $get_trade->fetch(PDO::FETCH_ASSOC);
        $trade_id = $subject_info['trade_id'] ?? null;

        // Create exam
        $create_exam = $pdo->prepare("
            INSERT INTO exams (
                trade_id, subject_id, exam_name, exam_date, 
                total_marks, passing_marks, exam_type, time_limit,
                created_by, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', NOW(), NOW())
        ");

        $create_exam->execute([
            $trade_id,
            $subject_id,
            $exam_name,
            $exam_date,
            $total_marks,
            $passing_marks,
            $exam_type,
            $time_limit,
            $teacher_id
        ]);

        $exam_id = $pdo->lastInsertId();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'Exam created successfully!',
            'exam_id' => $exam_id,
            'redirect' => BASE_URL . "/teacher/manage_exam_questions.php?exam_id=$exam_id"
        ]);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// Get teacher's exams
$exams_query = $pdo->prepare("
    SELECT e.id, e.exam_name, e.exam_date, e.total_marks, e.status,
           s.subject_name, t.trade_name,
           (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
           (SELECT COUNT(*) FROM exam_results WHERE exam_id = e.id) as attempt_count
    FROM exams e
    JOIN subjects s ON e.subject_id = s.id
    JOIN trades t ON e.trade_id = t.id
    WHERE e.created_by = ? AND e.subject_id IN (
        SELECT st.subject_id FROM subject_teacher st WHERE st.teacher_id = ?
    )
    ORDER BY e.exam_date DESC
");
$exams_query->execute([$teacher_id, $teacher_id]);
$my_exams = $exams_query->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - Teacher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1000px; margin: 0 auto; }
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn:hover { background: #5568d3; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #f5c6cb; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
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
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-draft { background: #e0e0e0; color: #666; }
        .status-published { background: #d4edda; color: #155724; }
        .link { color: #667eea; text-decoration: none; cursor: pointer; }
        .link:hover { text-decoration: underline; }
        h2 { color: #333; margin-bottom: 15px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Create Exam</h1>
            <p>Create exams for your assigned subjects</p>
        </div>

        <div class="card">
            <h2>✍️ Create New Exam</h2>
            
            <div class="warning">
                <strong>📌 Note:</strong> You can only create exams for subjects assigned to you. If you can't find your subject here, contact your administrator.
            </div>

            <form id="createExamForm">
                <input type="hidden" name="action" value="create_exam">

                <div class="grid">
                    <div class="form-group">
                        <label>Subject *</label>
                        <select name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($assigned_subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>">
                                    <?= htmlspecialchars($subject['subject_name']) ?> (<?= htmlspecialchars($subject['trade_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Exam Name *</label>
                        <input type="text" name="exam_name" placeholder="e.g., Mid-Term Exam" required>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Exam Date *</label>
                        <input type="datetime-local" name="exam_date" required>
                    </div>

                    <div class="form-group">
                        <label>Exam Type</label>
                        <select name="exam_type">
                            <option value="standard">Standard</option>
                            <option value="quiz">Quiz</option>
                            <option value="midterm">Mid-Term</option>
                            <option value="final">Final</option>
                        </select>
                    </div>
                </div>

                <div class="grid">
                    <div class="form-group">
                        <label>Total Marks *</label>
                        <input type="number" name="total_marks" min="1" placeholder="e.g., 100" required>
                    </div>

                    <div class="form-group">
                        <label>Passing Marks *</label>
                        <input type="number" name="passing_marks" min="0" placeholder="e.g., 40" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Time Limit (minutes)</label>
                    <input type="number" name="time_limit" min="5" placeholder="e.g., 60" value="60">
                </div>

                <button type="submit" class="btn">📝 Create Exam</button>
            </form>

            <div id="message"></div>
        </div>

        <?php if ($my_exams): ?>
        <div class="card">
            <h2>📚 Your Exams (<?= count($my_exams) ?>)</h2>

            <table>
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Subject</th>
                        <th>Trade</th>
                        <th>Date</th>
                        <th>Questions</th>
                        <th>Attempts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_exams as $exam): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($exam['exam_name']) ?></strong></td>
                        <td><?= htmlspecialchars($exam['subject_name']) ?></td>
                        <td><?= htmlspecialchars($exam['trade_name']) ?></td>
                        <td><?= $exam['exam_date'] ?></td>
                        <td><?= $exam['question_count'] ?></td>
                        <td><?= $exam['attempt_count'] ?></td>
                        <td><span class="status-badge status-<?= $exam['status'] ?>"><?= ucfirst($exam['status']) ?></span></td>
                        <td>
                            <a class="link" href="manage_exam_questions.php?exam_id=<?= $exam['id'] ?>">Manage</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('createExamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                const msgDiv = document.getElementById('message');
                if (data.status === 'success') {
                    msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    setTimeout(() => window.location.href = data.redirect, 1500);
                } else {
                    msgDiv.innerHTML = '<div class="alert alert-error">' + data.message + '</div>';
                }
            })
            .catch(err => {
                document.getElementById('message').innerHTML = '<div class="alert alert-error">Error: ' + err.message + '</div>';
            });
        });
    </script>
</body>
</html>
