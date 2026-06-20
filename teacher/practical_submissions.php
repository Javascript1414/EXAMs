<?php
/**
 * Teacher: View & Mark Practical Submissions
 * Teachers can review student submissions and assign marks
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';
require_once '../includes/certificate_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied - Teachers Only');
}

$teacher_id = $_SESSION['user_id'];
$trade_id = $_SESSION['trade_id'] ?? 0;
$message = '';
$message_type = '';

// Get all practical exams for this teacher's subjects
$stmt = $pdo->prepare("
    SELECT DISTINCT pe.*, s.subject_name, t.trade_name, 
           COUNT(DISTINCT ps.id) as total_submissions,
           COUNT(DISTINCT CASE WHEN pm.id IS NULL THEN ps.id END) as pending_marks
    FROM practical_exams pe
    JOIN subjects s ON pe.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    JOIN subject_teacher st ON s.id = st.subject_id
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id
    LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
    WHERE st.teacher_id = ? AND s.trade_id = ? AND pe.status = 'active'
    GROUP BY pe.id
    ORDER BY pe.submission_deadline DESC
");
$stmt->execute([$teacher_id, $trade_id]);
$practical_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle mark submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token';
        $message_type = 'danger';
    } else {
        if ($_POST['action'] === 'assign_marks') {
            $submission_id = (int)($_POST['submission_id'] ?? 0);
            $marks = (float)($_POST['marks'] ?? 0);
            $feedback = sanitizeInput($_POST['feedback'] ?? '');
            $result_status = sanitizeInput($_POST['result_status'] ?? 'pass');

            if (!$submission_id || $marks < 0) {
                $message = 'Invalid submission or marks';
                $message_type = 'danger';
            } else {
                try {
                    // Get practical exam details
                    $stmt = $pdo->prepare("
                        SELECT pe.practical_marks, ps.practical_exam_id, ps.student_id
                        FROM practical_submissions ps
                        JOIN practical_exams pe ON ps.practical_exam_id = pe.id
                        WHERE ps.id = ?
                    ");
                    $stmt->execute([$submission_id]);
                    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$submission) {
                        $message = 'Submission not found';
                        $message_type = 'danger';
                    } elseif ($marks > $submission['practical_marks']) {
                        $message = 'Marks exceed maximum (' . $submission['practical_marks'] . ')';
                        $message_type = 'danger';
                    } else {
                        // Insert or update marks
                        $stmt = $pdo->prepare("
                            INSERT INTO practical_marks 
                            (submission_id, marks_obtained, result_status, feedback, marked_by, marked_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                            ON DUPLICATE KEY UPDATE
                                marks_obtained = ?,
                                result_status = ?,
                                feedback = ?,
                                marked_by = ?,
                                marked_at = NOW()
                        ");
                        $stmt->execute([$submission_id, $marks, $result_status, $feedback, $teacher_id, 
                                       $marks, $result_status, $feedback, $teacher_id]);

                        // Check and generate certificate if marks are complete
                        $cert_result = checkAndIssueCertificate($submission['student_id'], $submission['practical_exam_id']);
                        
                        if ($cert_result && $cert_result['success']) {
                            $message = 'Marks assigned successfully! Certificate generated (ID: ' . $cert_result['certificate_id'] . ')';
                            
                            // Send email notifications
                            sendMarksNotificationEmail($submission['student_id'], $submission['practical_exam_id'], $marks, $feedback);
                            sendCertificateEmail($submission['student_id'], $cert_result['certificate_id']);
                        } else {
                            $message = 'Marks assigned successfully! (Certificate will be generated when all marks are complete)';
                        }
                        $message_type = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Practical Submissions - CITS LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f7fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-fluid { display: grid; grid-template-columns: 280px 1fr; min-height: 100vh; }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1.5rem;
            overflow-y: auto;
            position: fixed;
            width: 280px;
            height: 100vh;
        }
        
        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
        }
        
        .sidebar-menu { list-style: none; }
        .sidebar-menu li {
            margin: 0.75rem 0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        
        .sidebar-menu a.active {
            background: white;
            color: #667eea;
            font-weight: 600;
        }
        
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            grid-column: 2;
        }
        
        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .submission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f7fafc;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            transition: all 0.3s;
        }
        
        .submission-item:hover {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .submission-info {
            flex: 1;
        }
        
        .student-name {
            font-weight: 600;
            color: #2d3748;
        }
        
        .submission-date {
            font-size: 0.85rem;
            color: #718096;
        }
        
        .mark-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .mark-badge.pending {
            background: #fef5e7;
            color: #f39c12;
        }
        
        .mark-badge.completed {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .btn-mark {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-mark.primary {
            background: #667eea;
            color: white;
        }
        
        .btn-mark.primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .modal-backdrop {
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-graduation-cap me-2"></i>CITS LMS
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fas fa-chart-line"></i>Dashboard</a></li>
                <li><a href="practical_submissions.php" class="active"><i class="fas fa-file-upload"></i>Practical Submissions</a></li>
                <li><a href="my_subjects.php"><i class="fas fa-book"></i>My Subjects</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i>Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-file-upload me-2"></i>Mark Practical Submissions</h1>
                <div>
                    <span style="color: #718096;">Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Teacher') ?></span>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" role="alert">
                    <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= count($practical_exams) ?></div>
                    <div class="stat-label">Total Practicals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= array_reduce($practical_exams, function($sum, $exam) { 
                            return $sum + $exam['total_submissions']; 
                        }, 0) ?>
                    </div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= array_reduce($practical_exams, function($sum, $exam) { 
                            return $sum + $exam['pending_marks']; 
                        }, 0) ?>
                    </div>
                    <div class="stat-label">Pending Marks</div>
                </div>
            </div>
            
            <!-- Practical Exams -->
            <?php if (empty($practical_exams)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No practical exams found for your subjects.
                </div>
            <?php else: ?>
                <?php foreach ($practical_exams as $exam): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 style="margin: 0;">
                                <i class="fas fa-flask me-2"></i>
                                <?= htmlspecialchars($exam['title']) ?>
                                <span style="font-size: 0.85rem; margin-left: 1rem;">
                                    (<?= htmlspecialchars($exam['subject_name']) ?>)
                                </span>
                            </h5>
                            <small style="opacity: 0.9;">
                                Deadline: <?= date('M d, Y H:i', strtotime($exam['submission_deadline'])) ?> |
                                Marks: <?= $exam['practical_marks'] ?> | 
                                Submissions: <?= $exam['total_submissions'] ?> | 
                                Pending: <?= $exam['pending_marks'] ?>
                            </small>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get submissions for this practical
                            $stmt = $pdo->prepare("
                                SELECT ps.*, u.full_name, pm.marks_obtained, pm.result_status, pm.feedback
                                FROM practical_submissions ps
                                JOIN users u ON ps.student_id = u.id
                                LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
                                WHERE ps.practical_exam_id = ?
                                ORDER BY ps.submitted_at DESC
                            ");
                            $stmt->execute([$exam['id']]);
                            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($submissions)): ?>
                                <p class="text-muted"><i class="fas fa-inbox"></i> No submissions yet</p>
                            <?php else:
                                foreach ($submissions as $sub): ?>
                                    <div class="submission-item">
                                        <div class="submission-info">
                                            <div class="student-name">
                                                <i class="fas fa-user-circle me-2"></i>
                                                <?= htmlspecialchars($sub['full_name']) ?>
                                            </div>
                                            <div class="submission-date">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                Submitted: <?= date('M d, Y H:i', strtotime($sub['submitted_at'])) ?>
                                                <?php if ($sub['is_late']): ?>
                                                    <span style="color: #e74c3c;"><strong>(LATE)</strong></span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($sub['submission_file']): ?>
                                                <div style="margin-top: 0.5rem;">
                                                    <a href="../uploads/practical_submissions/<?= htmlspecialchars($sub['submission_file']) ?>" 
                                                       target="_blank" style="color: #667eea; text-decoration: none;">
                                                        <i class="fas fa-download me-1"></i>
                                                        <?= htmlspecialchars(pathinfo($sub['submission_file'], PATHINFO_BASENAME)) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align: right;">
                                            <?php if ($sub['marks_obtained'] !== null): ?>
                                                <div class="mark-badge completed">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    <?= $sub['marks_obtained'] ?> / <?= $exam['practical_marks'] ?> marks
                                                </div>
                                                <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                                    Status: <strong><?= ucfirst($sub['result_status']) ?></strong>
                                                </div>
                                            <?php else: ?>
                                                <div class="mark-badge pending">
                                                    <i class="fas fa-clock me-1"></i>Pending
                                                </div>
                                            <?php endif; ?>
                                            <button class="btn-mark primary mt-2" onclick="openMarkModal(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['full_name']) ?>', <?= $exam['practical_marks'] ?>, <?= $sub['marks_obtained'] ?? 'null' ?>)">
                                                <i class="fas fa-edit me-1"></i>Mark
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mark Modal -->
    <div class="modal fade" id="markModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Assign Marks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: brightness(0) invert(1);"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="assign_marks">
                        <input type="hidden" name="submission_id" id="submissionId">
                        
                        <div class="form-group">
                            <label class="form-label">Student Name</label>
                            <input type="text" id="studentName" class="form-control" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Marks Obtained <span style="color: #e74c3c;">*</span></label>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="number" name="marks" id="marksInput" class="form-control" min="0" step="0.5" required>
                                <span style="color: #718096; font-weight: 600;" id="maxMarks"></span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Result Status</label>
                            <select name="result_status" class="form-control">
                                <option value="pass">Pass</option>
                                <option value="fail">Fail</option>
                                <option value="pending_review">Pending Review</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Feedback (Optional)</label>
                            <textarea name="feedback" class="form-control" rows="3" placeholder="Add your feedback..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Marks
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const markModal = new bootstrap.Modal(document.getElementById('markModal'));
        
        function openMarkModal(submissionId, studentName, maxMarks, currentMarks) {
            document.getElementById('submissionId').value = submissionId;
            document.getElementById('studentName').value = studentName;
            document.getElementById('maxMarks').textContent = '/ ' + maxMarks + ' marks';
            document.getElementById('marksInput').max = maxMarks;
            document.getElementById('marksInput').value = currentMarks || '';
            markModal.show();
        }
    </script>
</body>
</html>
