<?php
/**
 * Teacher: Create Google Form Exam
 * Teachers can create Google Form exams for their assigned subjects
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// Check if user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied - Teachers Only');
}

$teacher_id = $_SESSION['user_id'];

// Get teacher's assigned subjects with Google Form permission
$subjects = $pdo->query("
    SELECT DISTINCT s.id, s.subject_name, t.trade_name
    FROM subjects s
    JOIN trades t ON s.trade_id = t.id
    JOIN google_form_exam_permissions gfep ON s.id = gfep.subject_id
    WHERE gfep.teacher_id = $teacher_id AND gfep.can_create_exams = 1
    ORDER BY t.trade_name, s.subject_name
")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'Invalid CSRF token';
        $message_type = 'danger';
    } else {
        try {
            $exam_title = trim($_POST['exam_title'] ?? '');
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $google_form_link = trim($_POST['google_form_link'] ?? '');
            $total_marks = (int)($_POST['total_marks'] ?? 100);
            $pass_marks = (int)($_POST['pass_marks'] ?? 40);
            $exam_date = trim($_POST['exam_date'] ?? '');
            $exam_time = trim($_POST['exam_time'] ?? '');
            $instructions = trim($_POST['instructions'] ?? '');

            // Validation
            if (empty($exam_title)) {
                throw new Exception('Exam title is required');
            }
            if ($subject_id <= 0) {
                throw new Exception('Please select a subject');
            }
            if (empty($google_form_link)) {
                throw new Exception('Google Form link is required');
            }
            if ($total_marks <= 0) {
                throw new Exception('Total marks must be greater than 0');
            }
            if ($pass_marks < 0 || $pass_marks > $total_marks) {
                throw new Exception('Pass marks must be between 0 and total marks');
            }
            if (empty($exam_date)) {
                throw new Exception('Exam date is required');
            }

            // Verify teacher has permission for this subject
            $permission = $pdo->prepare("
                SELECT id FROM google_form_exam_permissions
                WHERE teacher_id = ? AND subject_id = ? AND can_create_exams = 1
            ");
            $permission->execute([$teacher_id, $subject_id]);
            if (!$permission->fetch()) {
                throw new Exception('You do not have permission to create exams for this subject');
            }

            // Get subject and trade info
            $subject_info = $pdo->prepare("
                SELECT s.id, s.subject_name, s.trade_id FROM subjects s WHERE s.id = ?
            ");
            $subject_info->execute([$subject_id]);
            $subject = $subject_info->fetch(PDO::FETCH_ASSOC);

            if (!$subject) {
                throw new Exception('Subject not found');
            }

            // Insert exam
            $stmt = $pdo->prepare("
                INSERT INTO google_form_exams 
                (exam_title, subject_id, trade_id, google_form_link, total_marks, pass_marks, 
                 exam_date, exam_time, instructions, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            
            $stmt->execute([
                $exam_title,
                $subject_id,
                $subject['trade_id'],
                $google_form_link,
                $total_marks,
                $pass_marks,
                $exam_date,
                $exam_time ?: null,
                $instructions ?: null,
                $teacher_id
            ]);

            $exam_id = $pdo->lastInsertId();

            // Get all students assigned to this subject's trade
            $students = $pdo->prepare("
                SELECT DISTINCT u.id FROM users u
                WHERE u.role_id = (SELECT id FROM roles WHERE name = 'student')
                AND u.trade_id = ?
            ");
            $students->execute([$subject['trade_id']]);
            $student_list = $students->fetchAll(PDO::FETCH_ASSOC);

            // Create exam attempt records for all students
            $attempt_stmt = $pdo->prepare("
                INSERT INTO google_form_exam_attempts 
                (student_id, exam_id, subject_id, exam_title, exam_source)
                VALUES (?, ?, ?, ?, 'Google Form')
            ");

            foreach ($student_list as $student) {
                $attempt_stmt->execute([
                    $student['id'],
                    $exam_id,
                    $subject_id,
                    $exam_title
                ]);
            }

            $message = "✅ Google Form exam created successfully! (Draft Status)";
            $message_type = 'success';

            // Clear form
            $_POST = [];

        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Google Form Exam - Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
        }

        body {
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label .required {
            color: #f56565;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-text {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 40px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-cancel {
            border: 2px solid #e2e8f0;
            color: #718096;
            background: transparent;
            border-radius: 8px;
            padding: 10px 30px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #f7fafc;
        }

        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
            margin-bottom: 20px;
        }

        .alert-danger {
            border-left-color: #f56565;
            background-color: rgba(245, 101, 101, 0.1);
        }

        .alert-success {
            border-left-color: var(--success-color);
            background-color: rgba(72, 187, 120, 0.1);
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .info-box i {
            color: var(--primary-color);
            margin-right: 10px;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px 15px;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-submit, .btn-cancel {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/sidebar.php'; ?>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <i class="bi bi-file-earmark-plus" style="font-size: 2rem; color: var(--primary-color);"></i>
            <h1>Create Google Form Exam</h1>
        </div>

        <!-- Alert Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-google"></i> Google Form Exam Details</h2>
            </div>
            <div class="card-body">
                <?php if (count($subjects) === 0): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle"></i>
                        <strong>No Subjects Available</strong>
                        <p class="mb-0">You don't have Google Form exam creation permission for any subject yet. 
                        Please contact your administrator to grant you permission.</p>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <!-- Basic Information Section -->
                        <div class="form-section-title">
                            <i class="bi bi-info-circle"></i> Basic Information
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-book"></i>
                                Exam Title
                                <span class="required">*</span>
                            </label>
                            <input type="text" class="form-control" name="exam_title" 
                                   placeholder="e.g., Java Programming - Mid Term Exam"
                                   value="<?= htmlspecialchars($_POST['exam_title'] ?? '') ?>" required>
                            <small class="form-text">Enter a descriptive title for the exam</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-layers"></i>
                                Subject
                                <span class="required">*</span>
                            </label>
                            <select class="form-select" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>" 
                                            <?= isset($_POST['subject_id']) && $_POST['subject_id'] == $subj['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subj['subject_name']) ?> (<?= htmlspecialchars($subj['trade_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Google Form Section -->
                        <div class="form-section-title">
                            <i class="bi bi-link-45deg"></i> Google Form Details
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-link"></i>
                                Google Form Link
                                <span class="required">*</span>
                            </label>
                            <input type="url" class="form-control" name="google_form_link" 
                                   placeholder="https://forms.gle/... or https://docs.google.com/forms/..."
                                   value="<?= htmlspecialchars($_POST['google_form_link'] ?? '') ?>" required>
                            <small class="form-text">Paste the complete Google Form link here</small>
                        </div>

                        <!-- Marks Section -->
                        <div class="form-section-title">
                            <i class="bi bi-percent"></i> Marks Configuration
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-calculator"></i>
                                    Total Marks
                                    <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" name="total_marks" 
                                       value="<?= htmlspecialchars($_POST['total_marks'] ?? '100') ?>" 
                                       min="1" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-check-circle"></i>
                                    Pass Marks
                                    <span class="required">*</span>
                                </label>
                                <input type="number" class="form-control" name="pass_marks" 
                                       value="<?= htmlspecialchars($_POST['pass_marks'] ?? '40') ?>" 
                                       min="0" required>
                            </div>
                        </div>

                        <!-- Date & Time Section -->
                        <div class="form-section-title">
                            <i class="bi bi-calendar"></i> Schedule
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-calendar-event"></i>
                                    Exam Date
                                    <span class="required">*</span>
                                </label>
                                <input type="date" class="form-control" name="exam_date" 
                                       value="<?= htmlspecialchars($_POST['exam_date'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="bi bi-clock"></i>
                                    Exam Time (Optional)
                                </label>
                                <input type="time" class="form-control" name="exam_time" 
                                       value="<?= htmlspecialchars($_POST['exam_time'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Instructions Section -->
                        <div class="form-section-title">
                            <i class="bi bi-file-text"></i> Instructions
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="bi bi-chat-left-text"></i>
                                Exam Instructions (Optional)
                            </label>
                            <textarea class="form-control" name="instructions" 
                                      placeholder="e.g., This exam consists of 50 questions. You have 2 hours to complete it. Please read all instructions carefully before starting."><?= htmlspecialchars($_POST['instructions'] ?? '') ?></textarea>
                            <small class="form-text">Instructions will be shown to students before they access the exam</small>
                        </div>

                        <!-- Info Box -->
                        <div class="info-box">
                            <i class="bi bi-lightbulb"></i>
                            <strong>Note:</strong> This exam will be created as a DRAFT. 
                            You can review it before publishing. After publishing, students will see it in their dashboard.
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <button type="submit" class="btn-submit">
                                <i class="bi bi-check-circle"></i> Create Exam
                            </button>
                            <button type="reset" class="btn-cancel">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
