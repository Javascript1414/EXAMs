<?php
/**
 * Admin/Moderator: Practical Exam Management Dashboard
 * Simplified workflow: Create → Link → Publish → Review Submissions
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';
require_once '../includes/exam_invitation_functions.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin', 'moderator', 'teacher'])) {
    http_response_code(403);
    die('Access Denied');
}

$message = '';
$message_type = '';
$user_id = $_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token expired';
        $message_type = 'danger';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        switch ($action) {
            case 'create_practical_exam':
                // Validate inputs
                $title = sanitizeInput($_POST['title'] ?? '');
                $subject_id = (int)($_POST['subject_id'] ?? 0);
                $practical_marks = (int)($_POST['practical_marks'] ?? 20);
                $submission_deadline = $_POST['submission_deadline'] ?? '';
                
                if (!$title || !$subject_id || !$practical_marks || !$submission_deadline) {
                    $message = 'All fields are required';
                    $message_type = 'danger';
                } else {
                    // Get trade_id from subject
                    $subj_stmt = $pdo->prepare("SELECT trade_id FROM subjects WHERE id = ?");
                    $subj_stmt->execute([$subject_id]);
                    $subj = $subj_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$subj) {
                        $message = 'Invalid subject';
                        $message_type = 'danger';
                    } else {
                        $result = createPracticalExam([
                            'subject_id' => $subject_id,
                            'trade_id' => $subj['trade_id'],
                            'title' => $title,
                            'description' => sanitizeInput($_POST['description'] ?? ''),
                            'theory_marks' => 80,
                            'practical_marks' => $practical_marks,
                            'practical_pass_marks' => (int)($_POST['practical_pass_marks'] ?? 10),
                            'submission_deadline' => $submission_deadline,
                            'evaluation_instructions' => sanitizeInput($_POST['instructions'] ?? ''),
                            'created_by' => $user_id
                        ]);
                        
                        if ($result['success']) {
                            $message = 'Practical exam created! Next: Link to a theory exam';
                            $message_type = 'success';
                        } else {
                            $message = $result['message'];
                            $message_type = 'danger';
                        }
                    }
                }
                break;
                
            case 'link_theory_exam':
                $practical_id = (int)($_POST['practical_exam_id'] ?? 0);
                $theory_id = (int)($_POST['theory_exam_id'] ?? 0);
                
                if (!$practical_id || !$theory_id) {
                    $message = 'Please select both exams';
                    $message_type = 'danger';
                } else {
                    $result = linkPracticalToTheoryExam($practical_id, $theory_id, $user_id);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'publish_exam':
                $practical_id = (int)($_POST['practical_exam_id'] ?? 0);
                
                if (!$practical_id) {
                    $message = 'Exam not found';
                    $message_type = 'danger';
                } else {
                    $result = publishPracticalExam($practical_id, $user_id);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'unpublish_exam':
                $practical_id = (int)($_POST['practical_exam_id'] ?? 0);
                
                if (!$practical_id) {
                    $message = 'Exam not found';
                    $message_type = 'danger';
                } else {
                    $result = unpublishPracticalExam($practical_id, $user_id);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'danger';
                }
                break;
                
            case 'edit_exam':
                $practical_id = (int)($_POST['practical_exam_id'] ?? 0);
                $title = sanitizeInput($_POST['title'] ?? '');
                $practical_marks = (int)($_POST['practical_marks'] ?? 0);
                $deadline = $_POST['submission_deadline'] ?? '';
                
                if (!$practical_id || !$title || !$practical_marks || !$deadline) {
                    $message = 'All fields are required';
                    $message_type = 'danger';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE practical_exams 
                        SET title = ?, description = ?, practical_marks = ?,
                            practical_pass_marks = ?, submission_deadline = ?,
                            evaluation_instructions = ?
                        WHERE id = ? AND created_by = ?
                    ");
                    
                    $stmt->execute([
                        $title,
                        sanitizeInput($_POST['description'] ?? ''),
                        $practical_marks,
                        (int)($_POST['practical_pass_marks'] ?? 10),
                        $deadline,
                        sanitizeInput($_POST['instructions'] ?? ''),
                        $practical_id,
                        $user_id
                    ]);
                    
                    $message = 'Exam updated successfully';
                    $message_type = 'success';
                }
                break;
                
            case 'delete_exam':
                $practical_id = (int)($_POST['practical_exam_id'] ?? 0);
                
                if (!$practical_id) {
                    $message = 'Exam not found';
                    $message_type = 'danger';
                } else {
                    // Check for submissions
                    $check = $pdo->prepare("SELECT COUNT(*) FROM practical_submissions WHERE practical_exam_id = ?");
                    $check->execute([$practical_id]);
                    
                    if ($check->fetchColumn() > 0) {
                        $message = 'Cannot delete: Students have already submitted. Unpublish instead.';
                        $message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM practical_exams WHERE id = ? AND created_by = ?");
                        $stmt->execute([$practical_id, $user_id]);
                        $message = 'Exam deleted successfully';
                        $message_type = 'success';
                    }
                }
                break;
        }
    }
}

// Fetch all practical exams
$exams_stmt = $pdo->prepare("
    SELECT pe.id, pe.title, pe.theory_exam_id, pe.subject_id, pe.practical_marks,
           pe.submission_deadline, pe.status, pe.published, pe.published_at, pe.created_at,
           s.subject_name, e.exam_name as theory_exam_name,
           COUNT(DISTINCT ps.student_id) as total_submissions,
           SUM(CASE WHEN ps.status = 'submitted' THEN 1 ELSE 0 END) as pending_submissions
    FROM practical_exams pe
    LEFT JOIN subjects s ON pe.subject_id = s.id
    LEFT JOIN exams e ON pe.theory_exam_id = e.id
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id
    WHERE pe.created_by = ?
    GROUP BY pe.id
    ORDER BY pe.created_at DESC
");
$exams_stmt->execute([$user_id]);
$practical_exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available theory exams for linking
$theory_exams_stmt = $pdo->prepare("
    SELECT id, exam_name, total_marks, exam_type, status
    FROM exams
    WHERE exam_type = 'theory'
    ORDER BY created_at DESC
");
$theory_exams_stmt->execute();
$available_theory_exams = $theory_exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subjects for create form
$subjects_stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, t.trade_name
    FROM subjects s
    LEFT JOIN trades t ON s.trade_id = t.id
    ORDER BY t.trade_name, s.subject_name
");
$subjects_stmt->execute();
$all_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-file-clipboard"></i> Practical Exams Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
                    <i class="fas fa-plus"></i> Create New Practical Exam
                </button>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Exams Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-gradient">
            <h5 class="card-title mb-0">
                <i class="fas fa-list"></i> Your Practical Exams (<?= count($practical_exams) ?>)
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($practical_exams)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No practical exams created yet. 
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
                        Create one now
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Exam Title</th>
                                <th>Theory Exam Link</th>
                                <th>Practical Marks</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Submissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($practical_exams as $exam): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($exam['title']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($exam['subject_name']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($exam['theory_exam_id']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i>
                                                <?= htmlspecialchars($exam['theory_exam_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-link"></i> Needs Linking
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $exam['practical_marks'] ?> marks</td>
                                    <td><?= date('M d, Y H:i', strtotime($exam['submission_deadline'])) ?></td>
                                    <td>
                                        <?php if ($exam['published']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-eye"></i> Published
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-lock"></i> Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($exam['total_submissions'] > 0): ?>
                                            <span class="badge bg-info"><?= $exam['total_submissions'] ?> submitted</span>
                                            <?php if ($exam['pending_submissions'] > 0): ?>
                                                <br><span class="badge bg-warning text-dark"><?= $exam['pending_submissions'] ?> pending</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No submissions</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (!$exam['theory_exam_id'] && !$exam['published']): ?>
                                                <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#linkModal<?= $exam['id'] ?>" title="Link to theory exam">
                                                    <i class="fas fa-link"></i> Link
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($exam['theory_exam_id'] && !$exam['published']): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="publish_exam">
                                                    <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                                                    <button type="submit" class="btn btn-success" title="Publish exam">
                                                        <i class="fas fa-paper-plane"></i> Publish
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($exam['published']): ?>
                                                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#viewModal<?= $exam['id'] ?>" title="View submissions">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="unpublish_exam">
                                                    <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                                                    <button type="submit" class="btn btn-secondary" title="Unpublish exam">
                                                        <i class="fas fa-times"></i> Unpublish
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $exam['id'] ?>" title="Edit exam">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $exam['id'] ?>" title="Delete exam">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Exam Modal -->
<div class="modal fade" id="createExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5><i class="fas fa-plus-circle"></i> Create Practical Exam</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create_practical_exam">
                    
                    <div class="mb-3">
                        <label class="form-label">Exam Title *</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g., Practical Lab Work">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" class="form-control" required>
                            <option value="">-- Select Subject --</option>
                            <?php foreach ($all_subjects as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['trade_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Practical Marks *</label>
                                <input type="number" name="practical_marks" class="form-control" value="20" min="1" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pass Marks *</label>
                                <input type="number" name="practical_pass_marks" class="form-control" value="10" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Submission Deadline *</label>
                        <input type="datetime-local" name="submission_deadline" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Exam description (optional)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Evaluation Instructions</label>
                        <textarea name="instructions" class="form-control" rows="3" placeholder="How to evaluate this practical (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link Theory Exam Modals -->
<?php foreach ($practical_exams as $exam): ?>
    <?php if (!$exam['theory_exam_id'] && !$exam['published']): ?>
        <div class="modal fade" id="linkModal<?= $exam['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-info text-white">
                            <h5><i class="fas fa-link"></i> Link to Theory Exam</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <input type="hidden" name="action" value="link_theory_exam">
                            <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                            
                            <div class="alert alert-info">
                                <strong><?= htmlspecialchars($exam['title']) ?></strong> will be linked to a theory exam.
                                Students will take both exams together.
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Select Theory Exam *</label>
                                <select name="theory_exam_id" class="form-control" required>
                                    <option value="">-- Select Theory Exam --</option>
                                    <?php foreach ($available_theory_exams as $t): ?>
                                        <option value="<?= $t['id'] ?>">
                                            <?= htmlspecialchars($t['exam_name']) ?> 
                                            (<?= $t['total_marks'] ?> marks)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-link"></i> Link Exams
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Edit Exam Modals -->
<?php foreach ($practical_exams as $exam): ?>
    <div class="modal fade" id="editModal<?= $exam['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning">
                        <h5><i class="fas fa-edit"></i> Edit Practical Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="edit_exam">
                        <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Exam Title *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($exam['title']) ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Practical Marks *</label>
                                    <input type="number" name="practical_marks" class="form-control" value="<?= $exam['practical_marks'] ?>" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Pass Marks *</label>
                                    <input type="number" name="practical_pass_marks" class="form-control" value="10" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Submission Deadline *</label>
                            <input type="datetime-local" name="submission_deadline" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($exam['submission_deadline'])) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Delete Exam Modals -->
<?php foreach ($practical_exams as $exam): ?>
    <div class="modal fade" id="deleteModal<?= $exam['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
                    <div class="modal-header bg-danger text-white">
                        <h5><i class="fas fa-trash"></i> Delete Exam</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="delete_exam">
                        <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                        
                        <p class="mb-0">
                            Delete <strong><?= htmlspecialchars($exam['title']) ?></strong>?
                            <?php if ($exam['total_submissions'] > 0): ?>
                                <div class="alert alert-danger mt-3">
                                    <strong>Cannot delete:</strong> Students have already submitted (<?= $exam['total_submissions'] ?> submissions).
                                </div>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <?php if ($exam['total_submissions'] === 0): ?>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Permanently
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
    :root {
        --primary: #667eea;
        --secondary: #764ba2;
        --success: #48bb78;
        --danger: #f56565;
        --warning: #ed8936;
        --info: #4299e1;
    }
    
    .bg-gradient {
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        color: white;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }
    
    .badge {
        font-size: 0.85rem;
        padding: 0.5rem 0.75rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.35rem 0.6rem;
        font-size: 0.8rem;
    }
    
    .modal-content {
        border: none;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        border: none;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
