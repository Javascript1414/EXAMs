<?php
/**
 * Student: View & Submit Practical Exams - Grid Layout
 * Students can view practical exams and upload their work
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    http_response_code(403);
    die('Access Denied - Students Only');
}

$student_id = $_SESSION['user_id'];
$trade_id = $_SESSION['trade_id'] ?? 0;
$message = '';
$message_type = '';

// Clear any leftover messages from previous attempts
if (isset($_SESSION['practical_message'])) {
    $message = $_SESSION['practical_message'];
    $message_type = $_SESSION['practical_message_type'] ?? 'danger';
    unset($_SESSION['practical_message']);
    unset($_SESSION['practical_message_type']);
}

if (!$trade_id) {
    $stmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $trade_id = $user['trade_id'] ?? 0;
}

$all_practicals = getStudentPracticalExams($student_id, $trade_id);

$page = max(1, (int)($_GET['page'] ?? 1));
$items_per_page = 6;
$total_items = count($all_practicals);
$total_pages = ceil($total_items / $items_per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;
$practicals = array_slice($all_practicals, $offset, $items_per_page);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['practical_message'] = 'Invalid CSRF token';
        $_SESSION['practical_message_type'] = 'danger';
    } else {
        $practical_exam_id = (int)($_POST['practical_exam_id'] ?? 0);
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        $notes = sanitizeInput($_POST['submission_notes'] ?? '');

        if (!$practical_exam_id) {
            $_SESSION['practical_message'] = 'Invalid practical exam';
            $_SESSION['practical_message_type'] = 'danger';
        } elseif (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['practical_message'] = 'File upload failed';
            $_SESSION['practical_message_type'] = 'danger';
        } else {
            $upload_dir = '../uploads/practical_submissions/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file = $_FILES['submission_file'];
            
            // Check file size (50MB = 52428800 bytes)
            if ($file['size'] > 52428800) {
                $_SESSION['practical_message'] = 'File size exceeds 50MB limit';
                $_SESSION['practical_message_type'] = 'danger';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $timestamp = time();
                $filename = "practical_{$practical_exam_id}_{$student_id}_{$timestamp}.{$ext}";
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $result = submitPractical($practical_exam_id, $student_id, $exam_id, $filename, null, $notes);

                    if ($result['success']) {
                        $_SESSION['practical_message'] = $result['message'];
                        $_SESSION['practical_message_type'] = 'success';
                    } else {
                        $_SESSION['practical_message'] = $result['message'];
                        $_SESSION['practical_message_type'] = 'danger';
                        unlink($filepath);
                    }
                } else {
                    $_SESSION['practical_message'] = 'Could not save file';
                    $_SESSION['practical_message_type'] = 'danger';
                }
            }
        }
    }
    
    // Redirect to clean page load
    header("Location: {$_SERVER['PHP_SELF']}" . ($page > 1 ? "?page=$page" : ""), true, 303);
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<style>
    .practicals-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    @media (max-width: 1200px) {
        .practicals-grid { grid-template-columns: repeat(2, 1fr); }
    }
    
    @media (max-width: 768px) {
        .practicals-grid { grid-template-columns: 1fr; }
    }
    
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.2rem;
        font-weight: 700;
        border: none;
    }

    .card-body { padding: 1.5rem; }

    .exam-subject {
        color: #718096;
        font-size: 0.85rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .marks-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .mark-item {
        background: #f7fafc;
        padding: 0.75rem;
        border-radius: 8px;
        border-left: 3px solid #667eea;
    }

    .mark-label {
        font-size: 0.7rem;
        color: #718096;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .mark-value {
        font-size: 1rem;
        font-weight: 700;
        color: #2d3748;
    }

    .deadline {
        font-size: 0.85rem;
        color: #4a5568;
        margin-bottom: 0.75rem;
    }

    .deadline-warn { color: #e53e3e !important; font-weight: 700; }

    .status-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
    }

    .badge-pending { background: #fed7d7; color: #c53030; }
    .badge-submitted { background: #bee3f8; color: #2c5282; }
    .badge-marked { background: #c6f6d5; color: #22543d; }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.2rem;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-submit:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

    .marks-display {
        background: #e6f0ff;
        border-left: 4px solid #667eea;
        padding: 0.75rem;
        border-radius: 6px;
        font-size: 0.85rem;
        margin-top: 0.75rem;
    }

    .marks-display strong { color: #667eea; }

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

    .modal.show { display: flex; animation: fadeIn 0.3s ease; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 600px;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .upload-area {
        border: 2px dashed #667eea;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        background: rgba(102, 126, 234, 0.05);
        margin: 1.5rem 0;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover { background: rgba(102, 126, 234, 0.1); border-color: #764ba2; }
    .upload-area.dragover { background: rgba(102, 126, 234, 0.15); border-color: #764ba2; transform: scale(1.02); }
</style>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">Available Practical Exams</h3>
    
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" role="alert" style="margin-bottom: 1.5rem;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($total_items > 0): ?>
    <div class="mb-3 text-muted small">
        Showing <?= ($offset + 1) ?> to <?= min($offset + $items_per_page, $total_items) ?> of <?= $total_items ?> practicals
    </div>
    <?php endif; ?>

    <?php if (empty($all_practicals)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 1rem; display: block;"></i>
                <h5 style="color: #4a5568; font-weight: 700;">No Practical Exams Yet</h5>
                <p style="color: #718096; margin-top: 0.5rem;">Your teachers will create practical exams for you to complete.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="practicals-grid">
            <?php foreach ($practicals as $p): ?>
                <div>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-hammer me-2"></i><?= htmlspecialchars($p['title']) ?>
                        </div>
                        <div class="card-body">
                            <div class="exam-subject">
                                <i class="fas fa-book me-1"></i><?= htmlspecialchars($p['subject_name']) ?>
                                <br>
                                <small style="color: #a0aec0;"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($p['trade_name']) ?></small>
                            </div>

                            <div class="marks-grid">
                                <div class="mark-item">
                                    <div class="mark-label">Theory</div>
                                    <div class="mark-value"><?= $p['theory_marks'] ?></div>
                                </div>
                                <div class="mark-item">
                                    <div class="mark-label">Practical</div>
                                    <div class="mark-value"><?= $p['practical_marks'] ?></div>
                                </div>
                            </div>

                            <div class="deadline <?= strtotime($p['submission_deadline']) < time() ? 'deadline-warn' : '' ?>">
                                <i class="fas fa-calendar-alt me-1"></i><?= date('M d, Y H:i', strtotime($p['submission_deadline'])) ?>
                            </div>

                            <?php if ($p['submission_status'] === 'submitted'): ?>
                                <span class="status-badge badge-submitted"><i class="fas fa-check me-1"></i>Submitted</span>
                            <?php elseif ($p['mark_result_status']): ?>
                                <span class="status-badge badge-marked"><i class="fas fa-star me-1"></i>Marked</span>
                            <?php else: ?>
                                <span class="status-badge badge-pending"><i class="fas fa-clock me-1"></i>Pending</span>
                            <?php endif; ?>

                            <?php if ($p['marks_obtained'] !== null): ?>
                                <div class="marks-display">
                                    <strong>Score:</strong> <?= $p['marks_obtained'] ?>/<?= $p['practical_marks'] ?>
                                    <?php if ($p['feedback']): ?>
                                        <br><strong>Feedback:</strong> <?= htmlspecialchars(substr($p['feedback'], 0, 60)) ?>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($p['submission_status'] !== 'submitted'): ?>
                                <button class="btn-submit" onclick="openUploadModal(<?= $p['id'] ?>, <?= isset($p['exam_id']) && $p['exam_id'] ? $p['exam_id'] : 0 ?>, '<?= htmlspecialchars(addslashes($p['title'])) ?>')">
                                    <i class="fas fa-cloud-upload-alt me-1"></i>Submit Work
                                </button>
                            <?php else: ?>
                                <button class="btn-submit" disabled>
                                    <i class="fas fa-hourglass-half me-1"></i>Awaiting Marks
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-5 d-flex justify-content-center">
            <ul class="pagination">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">← Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
                    echo "<li class=\"page-item " . ($i == $page ? 'active' : '') . "\"><a class=\"page-link\" href=\"?page=$i\">$i</a></li>";
                } ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>">Next →</a>
                </li>
            </ul>
        </nav>
        <div class="text-center text-muted small mt-3 mb-5">
            Page <?= $page ?> of <?= $total_pages ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h4 style="color: #667eea; margin-bottom: 1rem; font-weight: 700;">
            <i class="fas fa-cloud-upload-alt me-2"></i>Submit Practical Work
        </h4>
        <p id="practicalTitle" style="font-weight: 600; color: #718096; margin-bottom: 1.5rem; font-size: 0.95rem;"></p>

        <form id="uploadForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="practical_exam_id" id="practicalExamId">
            <input type="hidden" name="exam_id" id="examId">

            <div class="form-group">
                <label style="font-weight: 700; color: #667eea; margin-bottom: 0.5rem; display: block;">
                    <i class="fas fa-file-upload me-1"></i>Upload File *
                </label>
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: #667eea; margin-bottom: 0.5rem; display: block;"></i>
                    <p style="margin: 0.5rem 0; font-weight: 600; color: #2d3748;">Click to upload or drag file here</p>
                    <small style="color: #718096;">Any file type accepted (Max 50MB)</small>
                    <input type="file" name="submission_file" id="fileInput" style="display: none;" required>
                </div>
                <div id="fileInfo" style="margin-top: 0.75rem; color: #48bb78; font-weight: 700; display: none;">
                    <i class="fas fa-check-circle me-1"></i><span></span>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 700; color: #667eea; margin-bottom: 0.5rem; display: block;">Notes (Optional)</label>
                <textarea name="submission_notes" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 6px; font-family: inherit;" placeholder="Add any notes..."></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="submit" class="btn-submit" style="flex: 1; margin-top: 0;">
                    <i class="fas fa-check-circle me-1"></i>Submit
                </button>
                <button type="button" onclick="closeUploadModal()" style="flex: 1; padding: 0.6rem 1.2rem; background: #e2e8f0; color: #4a5568; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openUploadModal(id, examId, title) {
        document.getElementById('practicalExamId').value = id;
        document.getElementById('examId').value = examId;
        document.getElementById('practicalTitle').textContent = title;
        document.getElementById('uploadModal').classList.add('show');
    }

    function closeUploadModal() {
        document.getElementById('uploadModal').classList.remove('show');
    }

    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');

    uploadArea.addEventListener('click', () => fileInput.click());
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files;
        showFileName();
    });

    fileInput.addEventListener('change', showFileName);

    function showFileName() {
        if (fileInput.files.length > 0) {
            fileInfo.querySelector('span').textContent = fileInput.files[0].name;
            fileInfo.style.display = 'flex';
        }
    }

    document.getElementById('uploadModal').addEventListener('click', (e) => {
        if (e.target.id === 'uploadModal') closeUploadModal();
    });
</script>

<?php require_once '../includes/footer.php'; ?>
