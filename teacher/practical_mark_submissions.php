<?php
/**
 * Teacher: Mark Practical Submissions
 * Teachers can review and mark student practical submissions
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

// Get teacher's practical exams
$exams = getTeacherPracticalExams($teacher_id);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Get submissions for marking
    if ($action === 'get_submissions') {
        $practical_exam_id = (int)$_POST['practical_exam_id'];
        
        // Verify teacher owns this exam
        $verify = $pdo->prepare("SELECT id FROM practical_exams WHERE id = ? AND created_by = ?");
        $verify->execute([$practical_exam_id, $teacher_id]);
        if (!$verify->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }

        $submissions = getPracticalSubmissionsForMarking($practical_exam_id, $teacher_id);
        echo json_encode(['status' => 'success', 'submissions' => $submissions]);
        exit;
    }

    // Save marks
    if ($action === 'save_marks') {
        $submission_id = (int)$_POST['submission_id'];
        $practical_exam_id = (int)$_POST['practical_exam_id'];
        $marks = (int)$_POST['marks'];
        $feedback = sanitizeInput($_POST['feedback'] ?? '');

        $result = markPracticalSubmission($submission_id, $practical_exam_id, $marks, $feedback, $teacher_id);
        echo json_encode($result);
        exit;
    }

    // Generate certificates after marking
    if ($action === 'generate_certificates') {
        $practical_exam_id = (int)$_POST['practical_exam_id'];

        try {
            // Get all students with marks
            $stmt = $pdo->prepare("
                SELECT DISTINCT ps.student_id, ps.exam_id, cer.id as result_id
                FROM practical_submissions ps
                JOIN practical_marks pm ON ps.id = pm.submission_id
                JOIN combined_exam_results cer ON cer.student_id = ps.student_id AND cer.exam_id = ps.exam_id
                WHERE ps.practical_exam_id = ? AND cer.result_status = 'pass'
            ");
            $stmt->execute([$practical_exam_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $generated = 0;
            foreach ($students as $student) {
                $result = generateCombinedExamCertificate($student['result_id'], $teacher_id);
                if ($result['success']) {
                    $generated++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "Certificates generated for $generated passing students",
                'generated' => $generated
            ]);
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

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Practical Submissions - Teacher</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
        }

        * { transition: all 0.3s ease; }
        body {
            background: linear-gradient(135deg, #f7fafc 0%, #f0f4ff 100%);
            padding: 30px 20px;
            min-height: 100vh;
        }

        .container-main {
            max-width: 1200px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 25px;
            position: relative;
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
        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }
        .exam-item {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .exam-item:hover {
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
            transform: translateY(-4px);
            border-color: var(--primary);
        }
        .exam-item.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.02) 100%);
        }
        .exam-title {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.2rem;
        }
        .exam-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-top: 12px;
            font-size: 0.85rem;
        }
        .exam-meta span {
            color: #718096;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .submissions-table {
            display: none;
            margin-top: 18px;
            animation: slideDown 0.3s ease;
        }
        .submissions-table.show {
            display: block;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead th {
            background: linear-gradient(135deg, #f7fafc 0%, #f0f4ff 100%);
            color: var(--primary);
            font-weight: 700;
            border: none;
            padding: 14px;
            text-transform: uppercase;
            font-size: 0.75rem;
        }
        .table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.03);
        }
        .table tbody td {
            padding: 12px;
            vertical-align: middle;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-submitted {
            background: rgba(74, 144, 226, 0.15);
            color: #4a90e2;
        }
        .status-marked {
            background: rgba(72, 187, 120, 0.15);
            color: var(--success);
        }
        .status-late {
            background: rgba(237, 137, 54, 0.15);
            color: var(--warning);
        }
        .btn {
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #38a169 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }
        .modal-content {
            background: white;
            border-radius: 14px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .modal-header {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-label {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 6px;
        }
        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            outline: none;
        }
        .alert {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-info {
            background: rgba(74, 144, 226, 0.1);
            color: #4a90e2;
            border-left: 4px solid #4a90e2;
        }

        @media (max-width: 768px) {
            .exam-meta {
                flex-direction: column;
                gap: 5px;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="page-header">
            <h1><i class="bi bi-check-circle"></i> Mark Practical Submissions</h1>
        </div>

        <?php if (empty($exams)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't created any practical exams yet.
                <a href="practical_create_exam.php" style="color: inherit; text-decoration: underline;">Create a practical exam</a>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="bi bi-list-check"></i> Your Practical Exams</h2>
                </div>
                <div class="card-body">
                    <?php foreach ($exams as $exam): ?>
                        <div class="exam-item" onclick="loadSubmissions(<?= $exam['id'] ?>, this)">
                            <div class="exam-title"><?= htmlspecialchars($exam['title']) ?></div>
                            <small style="color: #718096;">
                                <i class="bi bi-layers"></i> <?= htmlspecialchars($exam['subject_name']) ?> (<?= htmlspecialchars($exam['trade_name']) ?>)
                            </small>
                            <div class="exam-meta">
                                <span><strong>Theory:</strong> <?= $exam['theory_marks'] ?> marks</span>
                                <span><strong>Practical:</strong> <?= $exam['practical_marks'] ?> marks</span>
                                <span><strong>Students:</strong> <?= $exam['total_students'] ?></span>
                                <span><strong>Marked:</strong> <?= $exam['marked_count'] ?? 0 ?></span>
                                <span><strong>Deadline:</strong> <?= date('M d, Y H:i', strtotime($exam['submission_deadline'])) ?></span>
                            </div>

                            <div id="submissions-<?= $exam['id'] ?>" class="submissions-table">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Submitted</th>
                                            <th>Status</th>
                                            <th>Marks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="submissions-body-<?= $exam['id'] ?>">
                                        <tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>
                                    </tbody>
                                </table>
                                <button class="btn btn-success mt-3" onclick="generateCertificates(<?= $exam['id'] ?>)">
                                    <i class="fas fa-certificate"></i> Generate Certificates
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Marking Modal -->
    <div id="markingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Mark Practical Submission</div>
            <form id="markingForm">
                <div class="form-group">
                    <label class="form-label">Student Name</label>
                    <input type="text" class="form-control" id="studentName" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Submitted File</label>
                    <input type="text" class="form-control" id="submissionFile" readonly>
                    <a id="downloadLink" href="#" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-download"></i> Download
                    </a>
                </div>

                <div class="form-group">
                    <label class="form-label">Notes from Student</label>
                    <textarea class="form-control" id="studentNotes" readonly rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Marks Out of <span id="maxMarks">20</span> *</label>
                    <input type="number" class="form-control" id="marksInput" min="0" max="100" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Feedback</label>
                    <textarea class="form-control" id="feedbackInput" rows="4" placeholder="Provide detailed feedback..."></textarea>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-primary flex-grow-1" onclick="saveMarks()">
                        Save Marks
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const csrfToken = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
        let currentSubmissionId = null;
        let currentPracticalId = null;

        function loadSubmissions(practicalId, element) {
            const container = document.getElementById(`submissions-${practicalId}`);
            const body = document.getElementById(`submissions-body-${practicalId}`);

            if (container.classList.contains('show')) {
                container.classList.remove('show');
                element.classList.remove('active');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'get_submissions');
            formData.append('practical_exam_id', practicalId);
            formData.append('csrf_token', csrfToken);

            body.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    let html = '';
                    data.submissions.forEach(sub => {
                        const statusClass = sub.status === 'marked' ? 'status-marked' :
                                          sub.is_late ? 'status-late' : 'status-submitted';
                        const statusText = sub.status === 'marked' ? 'Marked' :
                                         sub.is_late ? 'Late Submission' : 'Submitted';
                        const marksDisplay = sub.marks_obtained !== null ? sub.marks_obtained : '-';

                        html += `<tr>
                            <td><strong>${sub.full_name}</strong><br><small>${sub.email}</small></td>
                            <td>${new Date(sub.submitted_at).toLocaleDateString()}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td><strong>${marksDisplay}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openMarkingModal(${sub.submission_id}, ${practicalId}, '${sub.full_name}', '${sub.submission_file}', '${sub.submission_notes || ''}')">
                                    <i class="fas fa-edit"></i> Mark
                                </button>
                            </td>
                        </tr>`;
                    });

                    body.innerHTML = html || '<tr><td colspan="5" class="text-center text-muted">No submissions yet</td></tr>';
                    container.classList.add('show');
                    element.classList.add('active');
                } else {
                    body.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${data.message}</td></tr>`;
                }
            });
        }

        function openMarkingModal(submissionId, practicalId, studentName, file, notes) {
            currentSubmissionId = submissionId;
            currentPracticalId = practicalId;

            document.getElementById('studentName').value = studentName;
            document.getElementById('submissionFile').value = file;
            document.getElementById('studentNotes').value = notes;
            document.getElementById('marksInput').value = '';
            document.getElementById('feedbackInput').value = '';
            document.getElementById('downloadLink').href = `/uploads/${file}`;
            document.getElementById('markingModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('markingModal').classList.remove('show');
        }

        function saveMarks() {
            const marks = document.getElementById('marksInput').value;
            const feedback = document.getElementById('feedbackInput').value;

            if (!marks) {
                alert('Please enter marks');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'save_marks');
            formData.append('submission_id', currentSubmissionId);
            formData.append('practical_exam_id', currentPracticalId);
            formData.append('marks', marks);
            formData.append('feedback', feedback);
            formData.append('csrf_token', csrfToken);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    closeModal();
                    location.reload();
                }
            });
        }

        function generateCertificates(practicalId) {
            if (!confirm('Generate certificates for all passing students?')) return;

            const formData = new FormData();
            formData.append('action', 'generate_certificates');
            formData.append('practical_exam_id', practicalId);
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
            });
        }

        // Close modal when clicking outside
        document.getElementById('markingModal').addEventListener('click', (e) => {
            if (e.target.id === 'markingModal') closeModal();
        });
    </script>
</body>
</html>
