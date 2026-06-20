<?php
/**
 * Teacher: Create Practical Exam
 * Teachers can create practical exams with custom theory/practical marks split
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';

// Check if user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied - Teachers Only');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get teacher's subjects
$subjects = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, t.id as trade_id, t.trade_name
    FROM subjects s
    JOIN trades t ON s.trade_id = t.id
    WHERE s.trade_id IN (
        SELECT DISTINCT trade_id FROM users WHERE id = ?
    )
    ORDER BY t.trade_name, s.subject_name
");
$subjects->execute([$teacher_id]);
$teacher_subjects = $subjects->fetchAll(PDO::FETCH_ASSOC);

// Get available theory exams created by this teacher
$exams = $pdo->prepare("
    SELECT DISTINCT e.id, e.exam_name, s.subject_name, e.total_marks, e.created_at
    FROM exams e
    JOIN subjects s ON e.subject_id = s.id
    WHERE e.created_by = ? AND e.exam_type = 'theory'
    ORDER BY e.created_at DESC, e.exam_name
");
$exams->execute([$teacher_id]);
$available_exams = $exams->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token';
        $message_type = 'danger';
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $theory_marks = (int)($_POST['theory_marks'] ?? 80);
        $practical_marks = (int)($_POST['practical_marks'] ?? 20);
        $practical_pass_marks = (int)($_POST['practical_pass_marks'] ?? 10);
        $submission_deadline = $_POST['submission_deadline'] ?? '';
        $evaluation_instructions = sanitizeInput($_POST['evaluation_instructions'] ?? '');
        
        // Validation
        if (!$title) {
            $message = 'Title is required';
            $message_type = 'danger';
        } elseif (!$subject_id) {
            $message = 'Subject is required';
            $message_type = 'danger';
        } elseif ($theory_marks < 1 || $practical_marks < 1) {
            $message = 'Theory and practical marks must be at least 1';
            $message_type = 'danger';
        } elseif ($practical_pass_marks > $practical_marks) {
            $message = 'Pass marks cannot exceed practical marks';
            $message_type = 'danger';
        } elseif (!$submission_deadline) {
            $message = 'Submission deadline is required';
            $message_type = 'danger';
        } else {
            // Get trade_id from subject
            $subj_stmt = $pdo->prepare("SELECT trade_id FROM subjects WHERE id = ?");
            $subj_stmt->execute([$subject_id]);
            $subj = $subj_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$subj) {
                $message = 'Invalid subject selected';
                $message_type = 'danger';
            } else {
                // Create practical exam
                $result = createPracticalExam([
                    'exam_id' => $exam_id,
                    'subject_id' => $subject_id,
                    'trade_id' => $subj['trade_id'],
                    'title' => $title,
                    'description' => $description,
                    'theory_marks' => $theory_marks,
                    'practical_marks' => $practical_marks,
                    'practical_pass_marks' => $practical_pass_marks,
                    'submission_deadline' => $submission_deadline,
                    'evaluation_instructions' => $evaluation_instructions,
                    'created_by' => $teacher_id
                ]);
                
                if ($result['success']) {
                    $message = 'Practical exam created successfully!';
                    $message_type = 'success';
                    $_POST = []; // Clear form
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Practical Exam - Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
            --info: #4a90e2;
            --light: #f7fafc;
            --border: #e2e8f0;
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-main {
            max-width: 900px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }

        .card-title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .card-body {
            padding: 35px;
            background: white;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .form-control::placeholder {
            color: #cbd5e0;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .marks-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin: 25px 0;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #f0f4ff 100%);
            border-radius: 12px;
            border-left: 5px solid var(--primary);
        }

        .marks-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .marks-item label {
            font-size: 0.95rem;
            color: #718096;
            margin-bottom: 10px;
            display: block;
            font-weight: 600;
        }

        .marks-display {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .marks-input {
            width: 100%;
            text-align: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 700;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            color: white;
        }

        .btn-lg {
            padding: 16px 32px;
            font-size: 1.1rem;
        }

        .btn-outline-secondary {
            border: 2px solid var(--border);
            color: #718096;
        }

        .btn-outline-secondary:hover {
            background-color: #f7fafc;
        }

        .alert {
            border: none;
            border-radius: 12px;
            margin-bottom: 25px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: rgba(72, 187, 120, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: rgba(245, 101, 101, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .info-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            border-left: 5px solid var(--primary);
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .info-box strong {
            color: var(--primary);
        }

        .deadline-info {
            font-size: 0.9rem;
            color: #718096;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 30px;
        }

        .mt-3 {
            margin-top: 20px;
        }

        .w-100 {
            width: 100%;
        }

        @media (max-width: 768px) {
            .marks-row {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 20px;
            }

            .card-header {
                padding: 20px;
            }

            .card-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title"><i class="bi bi-plus-circle"></i> Create Practical Exam</h1>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" role="alert">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    <strong>📌 Note:</strong> Practical exams work with theory exams. Students will submit practical work here, you mark it, and certificates are generated from combined theory + practical marks.
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label">Practical Title *</label>
                        <input type="text" class="form-control" name="title" placeholder="e.g., Welding Practical - Lap Joint" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                    </div>

                    <!-- Subject -->
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select class="form-select" name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($teacher_subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($subject['subject_name']) ?> (<?= htmlspecialchars($subject['trade_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Link Theory Exam -->
                    <div class="form-group">
                        <label class="form-label">Link Theory Exam (Optional)</label>
                        <select class="form-select" name="exam_id">
                            <option value="0">-- No Theory Exam (Standalone Practical) --</option>
                            <?php foreach ($available_exams as $exam): ?>
                                <option value="<?= $exam['id'] ?>" <?= isset($_POST['exam_id']) && $_POST['exam_id'] == $exam['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($exam['exam_name']) ?> - <?= htmlspecialchars($exam['subject_name']) ?> (<?= $exam['total_marks'] ?> marks)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Select a theory exam to link marks together. Leave blank for standalone practical exam.</small>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Describe what students need to do..." style="resize: vertical;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Marks Configuration -->
                    <div class="form-group">
                        <label class="form-label">Marks Configuration *</label>
                        <div class="marks-row">
                            <div class="marks-item">
                                <label>Theory Marks</label>
                                <input type="number" class="form-control marks-input" name="theory_marks" id="theory_marks" min="1" max="100" value="<?= htmlspecialchars($_POST['theory_marks'] ?? 80) ?>" onchange="updateTotal()" required>
                            </div>
                            <div class="marks-item">
                                <label>Practical Marks</label>
                                <input type="number" class="form-control marks-input" name="practical_marks" id="practical_marks" min="1" max="100" value="<?= htmlspecialchars($_POST['practical_marks'] ?? 20) ?>" onchange="updateTotal()" required>
                            </div>
                            <div class="marks-item">
                                <label>Total Marks</label>
                                <div class="marks-display" id="total_display">100</div>
                            </div>
                        </div>
                    </div>

                    <!-- Practical Pass Marks -->
                    <div class="form-group">
                        <label class="form-label">Practical Pass Marks *</label>
                        <input type="number" class="form-control" name="practical_pass_marks" min="1" placeholder="Minimum marks to pass practical" value="<?= htmlspecialchars($_POST['practical_pass_marks'] ?? 10) ?>" required>
                        <small class="text-muted">Students must score at least this much in practical to pass</small>
                    </div>

                    <!-- Submission Deadline -->
                    <div class="form-group">
                        <label class="form-label">Submission Deadline *</label>
                        <input type="datetime-local" class="form-control" name="submission_deadline" value="<?= htmlspecialchars($_POST['submission_deadline'] ?? '') ?>" required>
                        <div class="deadline-info">Students can upload practical work before this deadline</div>
                    </div>

                    <!-- Evaluation Instructions -->
                    <div class="form-group">
                        <label class="form-label">Evaluation Instructions</label>
                        <textarea class="form-control" name="evaluation_instructions" rows="4" placeholder="What should you look for when evaluating this practical?" style="resize: vertical;"><?= htmlspecialchars($_POST['evaluation_instructions'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-check-circle"></i> Create Practical Exam
                        </button>
                    </div>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-3">
                    <a href="google_form_enter_marks.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Marks
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTotal() {
            const theory = parseInt(document.getElementById('theory_marks').value) || 0;
            const practical = parseInt(document.getElementById('practical_marks').value) || 0;
            document.getElementById('total_display').textContent = theory + practical;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', updateTotal);
    </script>
</body>
</html>
