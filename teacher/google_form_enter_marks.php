<?php
/**
 * Teacher: Enter Marks for Google Form Exams
 * Teachers can enter marks obtained by students from Google Form responses
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';
require_once '../includes/google_form_functions.php';

// Check if user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied - Teachers Only');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get teacher's exams
$teacher_exams = $pdo->query("
    SELECT gfe.id, gfe.exam_title, gfe.subject_id, gfe.total_marks, gfe.pass_marks, 
           gfe.exam_date, gfe.status, s.subject_name, t.trade_name,
           COUNT(DISTINCT gfea.student_id) as total_students,
           SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered
    FROM google_form_exams gfe
    LEFT JOIN subjects s ON gfe.subject_id = s.id
    LEFT JOIN trades t ON s.trade_id = t.id
    LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
    WHERE gfe.created_by = $teacher_id
    GROUP BY gfe.id, gfe.exam_title, gfe.subject_id, gfe.total_marks, gfe.pass_marks,
             gfe.exam_date, gfe.status, s.subject_name, t.trade_name
    ORDER BY gfe.exam_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'get_students') {
        $exam_id = (int)$_POST['exam_id'];

        // Verify teacher owns this exam
        $exam_check = $pdo->prepare("
            SELECT id FROM google_form_exams WHERE id = ? AND created_by = ?
        ");
        $exam_check->execute([$exam_id, $teacher_id]);
        if (!$exam_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Exam not found or access denied']);
            exit;
        }

        // Get exam details
        $exam_stmt = $pdo->prepare("
            SELECT total_marks, pass_marks FROM google_form_exams WHERE id = ?
        ");
        $exam_stmt->execute([$exam_id]);
        $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);

        // Get students for this exam
        $students = $pdo->prepare("
            SELECT gfea.id as attempt_id, gfea.student_id, u.full_name, u.email,
                   gfea.marks_obtained, gfea.result_status, gfea.marks_entered_at
            FROM google_form_exam_attempts gfea
            JOIN users u ON gfea.student_id = u.id
            WHERE gfea.exam_id = ?
            ORDER BY u.full_name
        ");
        $students->execute([$exam_id]);
        $student_list = $students->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'exam' => $exam,
            'students' => $student_list
        ]);
        exit;
    }

    if ($action === 'save_marks') {
        $attempt_id = (int)$_POST['attempt_id'];
        $marks_obtained = isset($_POST['marks']) ? (int)$_POST['marks'] : null;

        if ($marks_obtained === null) {
            echo json_encode(['status' => 'error', 'message' => 'Marks are required']);
            exit;
        }

        try {
            // Get attempt details
            $attempt = $pdo->prepare("
                SELECT gfea.student_id, gfea.exam_id, gfe.total_marks, gfe.pass_marks
                FROM google_form_exam_attempts gfea
                JOIN google_form_exams gfe ON gfea.exam_id = gfe.id
                WHERE gfea.id = ?
            ");
            $attempt->execute([$attempt_id]);
            $attempt_data = $attempt->fetch(PDO::FETCH_ASSOC);

            if (!$attempt_data) {
                echo json_encode(['status' => 'error', 'message' => 'Attempt not found']);
                exit;
            }

            // Verify marks are within range
            if ($marks_obtained < 0 || $marks_obtained > $attempt_data['total_marks']) {
                echo json_encode(['status' => 'error', 
                    'message' => 'Marks must be between 0 and ' . $attempt_data['total_marks']]);
                exit;
            }

            // Determine result status
            $result_status = $marks_obtained >= $attempt_data['pass_marks'] ? 'pass' : 'fail';

            // Update marks
            $update = $pdo->prepare("
                UPDATE google_form_exam_attempts
                SET marks_obtained = ?, result_status = ?, marks_entered_by = ?, marks_entered_at = NOW()
                WHERE id = ?
            ");
            $update->execute([$marks_obtained, $result_status, $teacher_id, $attempt_id]);

            echo json_encode(['status' => 'success', 'message' => 'Marks saved successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'publish_exam') {
        $exam_id = (int)$_POST['exam_id'];

        try {
            $stmt = $pdo->prepare("
                UPDATE google_form_exams SET status = 'published' WHERE id = ? AND created_by = ?
            ");
            $stmt->execute([$exam_id, $teacher_id]);

            echo json_encode(['status' => 'success', 'message' => 'Exam published successfully']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'generate_certificates') {
        $exam_id = (int)$_POST['exam_id'];

        try {
            // Verify teacher owns this exam
            $exam_check = $pdo->prepare("
                SELECT id, subject_id FROM google_form_exams WHERE id = ? AND created_by = ?
            ");
            $exam_check->execute([$exam_id, $teacher_id]);
            $exam = $exam_check->fetch(PDO::FETCH_ASSOC);

            if (!$exam) {
                echo json_encode(['status' => 'error', 'message' => 'Exam not found or access denied']);
                exit;
            }

            // Generate certificates for passing students
            $result = generateGoogleFormCertificates($exam_id, $exam['subject_id'], $teacher_id);

            if ($result['success']) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => "Certificates generated successfully! ({$result['generated']} students)"
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $result['message']]);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
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
    <title>Enter Marks - Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
        }

        body {
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
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
            margin-bottom: 25px;
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
            padding: 25px;
        }

        .exam-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .exam-card:hover {
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .exam-card.active {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.05);
        }

        .exam-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .exam-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .exam-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-draft {
            background: rgba(237, 137, 54, 0.2);
            color: var(--warning-color);
        }

        .status-published {
            background: rgba(72, 187, 120, 0.2);
            color: var(--success-color);
        }

        .exam-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .exam-detail-item {
            font-size: 0.9rem;
            color: #718096;
        }

        .exam-detail-item strong {
            color: var(--primary-color);
        }

        .student-list {
            display: none;
        }

        .student-list.show {
            display: block;
        }

        .table {
            background: white;
            border-radius: 8px;
        }

        .table thead th {
            background-color: #f7fafc;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
        }

        .table tbody td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody tr:hover {
            background-color: #f7fafc;
        }

        .marks-input {
            width: 100px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: center;
        }

        .marks-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .result-pass {
            color: var(--success-color);
            font-weight: 600;
        }

        .result-fail {
            color: var(--danger-color);
            font-weight: 600;
        }

        .result-pending {
            color: #a0aec0;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #718096;
        }

        @media (max-width: 768px) {
            .exam-card-header {
                flex-direction: column;
            }

            .exam-details {
                grid-template-columns: repeat(2, 1fr);
            }

            .marks-input {
                width: 80px;
                font-size: 0.9rem;
            }

            .table {
                font-size: 0.9rem;
            }

            .table tbody td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/sidebar.php'; ?>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <i class="bi bi-pencil-square" style="font-size: 2rem; color: var(--primary-color);"></i>
            <h1>Enter Google Form Exam Marks</h1>
        </div>

        <!-- Exams List -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-list-check"></i> Your Google Form Exams</h2>
            </div>
            <div class="card-body">
                <?php if (count($teacher_exams) === 0): ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h3>No Exams Yet</h3>
                        <p>You haven't created any Google Form exams yet. 
                        <a href="google_form_create_exam.php">Create your first exam</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($teacher_exams as $exam): ?>
                        <div class="exam-card" onclick="toggleStudentList(<?= $exam['id'] ?>)">
                            <div class="exam-card-header">
                                <div>
                                    <div class="exam-title"><?= htmlspecialchars($exam['exam_title']) ?></div>
                                    <small style="color: #718096;">
                                        <i class="bi bi-layers"></i> <?= htmlspecialchars($exam['subject_name']) ?> 
                                        (<?= htmlspecialchars($exam['trade_name']) ?>)
                                    </small>
                                </div>
                                <span class="exam-status status-<?= $exam['status'] ?>">
                                    <?= ucfirst($exam['status']) ?>
                                </span>
                            </div>
                            <div class="exam-details">
                                <div class="exam-detail-item">
                                    <strong>Total Marks:</strong> <?= $exam['total_marks'] ?>
                                </div>
                                <div class="exam-detail-item">
                                    <strong>Pass Marks:</strong> <?= $exam['pass_marks'] ?>
                                </div>
                                <div class="exam-detail-item">
                                    <strong>Exam Date:</strong> <?= date('M d, Y', strtotime($exam['exam_date'])) ?>
                                </div>
                                <div class="exam-detail-item">
                                    <strong>Students:</strong> <?= $exam['total_students'] ?>
                                </div>
                                <div class="exam-detail-item">
                                    <strong>Marks Entered:</strong> <?= $exam['marks_entered'] ?? 0 ?>
                                </div>
                            </div>

                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-sm btn-outline-success" onclick="generateCertificates(<?= $exam['id'] ?>, event)">
                                    <i class="bi bi-award"></i> Generate Certificates
                                </button>
                            </div>
                            
                            <div id="student-list-<?= $exam['id'] ?>" class="student-list mt-4">
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';

        function toggleStudentList(examId) {
            const container = document.getElementById(`student-list-${examId}`);
            
            if (container.classList.contains('show')) {
                container.classList.remove('show');
                return;
            }

            // Load students
            const formData = new FormData();
            formData.append('action', 'get_students');
            formData.append('exam_id', examId);
            formData.append('csrf_token', csrfToken);

            container.innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>';

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const students = data.students;
                    const exam = data.exam;
                    let html = '<table class="table table-hover"><thead><tr><th>Student Name</th><th>Email</th><th>Marks Obtained</th><th>Result</th><th>Action</th></tr></thead><tbody>';
                    
                    students.forEach(student => {
                        const resultClass = student.result_status === 'pass' ? 'result-pass' : 
                                          student.result_status === 'fail' ? 'result-fail' : 'result-pending';
                        const resultText = student.result_status === 'pending' ? 'Pending' : 
                                         student.result_status === 'pass' ? 'PASS' : 'FAIL';
                        
                        html += `<tr>
                            <td><strong>${student.full_name}</strong></td>
                            <td><small>${student.email}</small></td>
                            <td>
                                <input type="number" class="marks-input" data-attempt-id="${student.attempt_id}" 
                                       min="0" max="${exam.total_marks}" 
                                       value="${student.marks_obtained || ''}" 
                                       placeholder="0-${exam.total_marks}">
                            </td>
                            <td><span class="${resultClass}">${resultText}</span></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="saveMarks(${student.attempt_id})">
                                    <i class="bi bi-check"></i> Save
                                </button>
                            </td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    container.innerHTML = html;
                    container.classList.add('show');
                } else {
                    container.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(err => {
                container.innerHTML = `<div class="alert alert-danger">Error loading students: ${err.message}</div>`;
            });
        }

        function saveMarks(attemptId) {
            const marksInput = document.querySelector(`input[data-attempt-id="${attemptId}"]`);
            const marks = marksInput.value;

            if (!marks || marks === '') {
                alert('Please enter marks');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_marks');
            formData.append('attempt_id', attemptId);
            formData.append('marks', marks);
            formData.append('csrf_token', csrfToken);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    marksInput.style.borderColor = '#48bb78';
                    setTimeout(() => {
                        marksInput.style.borderColor = '';
                    }, 2000);
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }

        function generateCertificates(examId, event) {
            event.stopPropagation();
            
            if (!confirm('Generate certificates for all passing students in this exam?')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'generate_certificates');
            formData.append('exam_id', examId);
            formData.append('csrf_token', csrfToken);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    location.reload();
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }
    </script>
</body>
</html>
