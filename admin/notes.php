<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Only admins and superadmins can manage notes
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle Add / Delete / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $trade_id = (int)($_POST['trade_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $uploaded_by = $_SESSION['user_id'];

            if ($trade_id && $subject_id && $title && isset($_FILES['note_file'])) {
                if ($_FILES['note_file']['error'] === UPLOAD_ERR_OK) {
                    // Validate file type (PDF only)
                    $allowed_types = ['application/pdf'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $_FILES['note_file']['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($mime_type, $allowed_types)) {
                        $_SESSION['error_message'] = "Only PDF files are allowed.";
                    } elseif ($_FILES['note_file']['size'] > 10 * 1024 * 1024) { // 10MB limit
                        $_SESSION['error_message'] = "File size must be less than 10MB.";
                    } else {
                        $uploadDir = __DIR__ . '/../uploads/notes/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        
                        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['note_file']['name']));
                        $fullPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['note_file']['tmp_name'], $fullPath)) {
                            // Verify file actually exists before saving to database
                            if (!file_exists($fullPath)) {
                                $_SESSION['error_message'] = "File upload verification failed - file not found after upload.";
                                unlink($fullPath); // Try to clean up
                            } else {
                                // Verify file is readable and is a valid PDF
                                if (!is_readable($fullPath)) {
                                    $_SESSION['error_message'] = "Uploaded file is not readable.";
                                    unlink($fullPath);
                                } else {
                                    $file_path = 'uploads/notes/' . $fileName;
                                    $stmt = $pdo->prepare("
                                        INSERT INTO notes (trade_id, subject_id, title, description, file_path, uploaded_by, status)
                                        VALUES (?, ?, ?, ?, ?, ?, 'active')
                                    ");
                                    if ($stmt->execute([$trade_id, $subject_id, $title, $description, $file_path, $uploaded_by])) {
                                        $_SESSION['success_message'] = "✓ Note uploaded successfully. File: " . $fileName;
                                        error_log("[NOTES] PDF Uploaded: $file_path | User: {$_SESSION['user_id']} | Size: " . filesize($fullPath) . " bytes");
                                    } else {
                                        $_SESSION['error_message'] = "Failed to save note record to database.";
                                        unlink($fullPath);
                                    }
                                }
                            }
                        } else {
                            $_SESSION['error_message'] = "File upload to server failed.";
                            error_log("[NOTES] Upload failed - move_uploaded_file() returned false. Temp: {$_FILES['note_file']['tmp_name']}");
                        }
                    }
                } else {
                    $_SESSION['error_message'] = "Please select a valid PDF file.";
                }
            } else {
                $_SESSION['error_message'] = "Please fill in all required fields and select a file.";
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT file_path, title FROM notes WHERE id = ?");
                $stmt->execute([$id]);
                $note = $stmt->fetch();
                
                if ($note) {
                    $filePath = __DIR__ . '/../' . $note['file_path'];
                    
                    // Delete file if it exists
                    if (!empty($note['file_path'])) {
                        if (file_exists($filePath)) {
                            if (unlink($filePath)) {
                                error_log("[NOTES] File deleted: {$note['file_path']}");
                            } else {
                                error_log("[NOTES] Failed to delete file: $filePath - Permission denied");
                            }
                        } else {
                            error_log("[NOTES] File not found during deletion: $filePath - Note ID: $id");
                        }
                    }
                    
                    // Delete database record
                    $pdo->prepare("DELETE FROM notes WHERE id = ?")->execute([$id]);
                    $_SESSION['success_message'] = "✓ Note deleted successfully.";
                }
            }
        } elseif ($action === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT status FROM notes WHERE id = ?");
                $stmt->execute([$id]);
                $note = $stmt->fetch();
                if ($note) {
                    $new_status = $note['status'] === 'active' ? 'inactive' : 'active';
                    $pdo->prepare("UPDATE notes SET status = ? WHERE id = ?")->execute([$new_status, $id]);
                    $_SESSION['success_message'] = "Note status updated.";
                }
            }
        }
    }
    redirect('/admin/notes.php');
}

// Fetch Data
$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, trade_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT n.*, t.trade_name, s.subject_name, u.full_name as uploaded_by_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.uploaded_by = u.id
    ORDER BY n.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$notes = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT COUNT(*) FROM notes");
$totalNotes = $totalStmt->fetchColumn();
$totalPages = ceil($totalNotes / $limit);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">📚 Study Notes</h3>
        <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addNoteModal">
            <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Upload Note
        </button>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Trade / Subject</th>
                            <th>Uploaded By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i data-lucide="inbox" style="width: 48px; height: 48px; opacity: 0.3;"></i>
                                    <p class="mt-2">No notes uploaded yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notes as $note): ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong><?php echo htmlspecialchars($note['title']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($note['description'] ?? '', 0, 50)); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($note['trade_name']); ?></span>
                                        <br>
                                        <small><?php echo htmlspecialchars($note['subject_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($note['uploaded_by_name']); ?></td>
                                    <td><small><?php echo date('M d, Y', strtotime($note['created_at'])); ?></small></td>
                                    <td>
                                        <span class="badge bg-<?php echo $note['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($note['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-info" onclick="viewNote(<?php echo htmlspecialchars(json_encode($note)); ?>)" title="View Details">
                                                <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" onclick="toggleStatus(<?php echo $note['id']; ?>)" title="Toggle Status">
                                                <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteNote(<?php echo $note['id']; ?>)" title="Delete">
                                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-center py-3" aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=1">First</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title">Upload Study Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Trade <span class="text-danger">*</span></label>
                        <select name="trade_id" id="tradeSelect" class="form-select" required onchange="updateSubjects()">
                            <option value="">Select Trade...</option>
                            <?php foreach ($trades as $trade): ?>
                                <option value="<?php echo $trade['id']; ?>"><?php echo htmlspecialchars($trade['trade_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="subjectSelect" class="form-select" required>
                            <option value="">Select Subject...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Chapter 5 - Advanced Concepts" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Add description..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">PDF File <span class="text-danger">*</span></label>
                        <input type="file" name="note_file" class="form-control" accept=".pdf" required>
                        <small class="text-muted">Max 10MB - PDF only</small>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="upload" style="width: 16px; height: 16px; display: inline;"></i> Upload Note
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const allSubjects = <?php echo json_encode($subjects); ?>;
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;

function updateSubjects() {
    const tradeId = document.getElementById('tradeSelect').value;
    const subjectSelect = document.getElementById('subjectSelect');
    subjectSelect.innerHTML = '<option value="">Select Subject...</option>';
    
    if (tradeId) {
        allSubjects.filter(s => s.trade_id == tradeId).forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.subject_name;
            subjectSelect.appendChild(option);
        });
    }
}

function deleteNote(id) {
    if (confirm('Are you sure you want to delete this note?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleStatus(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="id" value="${id}">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    `;
    document.body.appendChild(form);
    form.submit();
}

function viewNote(note) {
    document.getElementById('viewTitle').textContent = note.title;
    document.getElementById('viewTrade').textContent = note.trade_name;
    document.getElementById('viewSubject').textContent = note.subject_name;
    document.getElementById('viewUploadedBy').textContent = note.uploaded_by_name;
    document.getElementById('viewDate').textContent = new Date(note.created_at).toLocaleDateString();
    document.getElementById('viewDescription').textContent = note.description || 'No description';
    document.getElementById('viewStatus').textContent = note.status.charAt(0).toUpperCase() + note.status.slice(1);
    document.getElementById('viewStatus').className = 'badge bg-' + (note.status === 'active' ? 'success' : 'danger');
    
    // Generate file URLs using serve-pdf endpoint and direct download
    const directFileUrl = BASE_URL + '/' + note.file_path;
    const serveFileUrl = BASE_URL + '/api/serve-pdf.php?file=' + encodeURIComponent(note.file_path);
    
    console.log('=== PDF PREVIEW DEBUG ===');
    console.log('File Path from DB:', note.file_path);
    console.log('Direct URL:', directFileUrl);
    console.log('Serve URL:', serveFileUrl);
    console.log('Title:', note.title);
    
    // Download link uses serve-pdf endpoint
    document.getElementById('viewFile').href = serveFileUrl;
    document.getElementById('viewFile').download = note.title + '.pdf';
    
    // Verify PDF file exists before loading preview (30 second timeout)
    const pdfPreviewElement = document.getElementById('pdfPreview');
    const previewContainer = pdfPreviewElement.parentElement;
    
    // Show loading indicator with enhanced feedback
    let loadingSeconds = 0;
    const loadingTimer = setInterval(() => {
        loadingSeconds++;
        const loaderText = previewContainer.querySelector('.spinner-border')?.parentElement;
        if (loaderText) loaderText.textContent = 'Loading PDF preview... (' + loadingSeconds + 's)';
    }, 1000);
    
    previewContainer.innerHTML = `
        <div class="alert alert-info mb-0" style="display: flex; align-items: center; gap: 10px;">
            <div class="spinner-border spinner-border-sm" role="status" style="width: 20px; height: 20px;">
                <span class="visually-hidden">Loading PDF...</span>
            </div>
            <span>Loading PDF preview... (0s)</span>
        </div>
    `;
    
    // 30 second timeout for PDF loading
    const timeoutHandle = setTimeout(() => {
        clearInterval(loadingTimer);
        if (document.getElementById('pdfPreview')?.parentElement === previewContainer) {
            previewContainer.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <strong><i data-lucide="clock" style="width: 18px; height: 18px; display: inline;"></i> Loading Timeout</strong><br>
                    <p style="margin: 10px 0 0 0; font-size: 0.9rem;">
                        The PDF is taking too long to load (>30 seconds). The file may be very large or the server is slow.
                    </p>
                    <a href="${serveFileUrl}" class="btn btn-sm btn-primary mt-3" download>
                        <i data-lucide="download" style="width: 14px; height: 14px; display: inline;"></i> Download PDF Instead
                    </a>
                </div>
            `;
        }
    }, 30000);
    
    // Check if PDF file exists using API endpoint
    fetch(BASE_URL + '/api/check-pdf.php?file=' + encodeURIComponent(note.file_path), { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
            console.log('PDF Check Result:', data);
            clearInterval(loadingTimer);
            clearTimeout(timeoutHandle);
            
            if (data.success) {
                // File exists, load it in iframe using serve-pdf endpoint
                previewContainer.innerHTML = `
                    <iframe id="pdfPreview" 
                            style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;"
                            frameborder="0"
                            src="${serveFileUrl}#toolbar=1&navpanes=0&scrollbar=1"></iframe>
                `;
            } else {
                // File doesn't exist, show error with fallback
                previewContainer.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <strong><i data-lucide="alert-circle" style="width: 18px; height: 18px; display: inline;"></i> PDF Preview Unavailable</strong><br>
                        <p style="margin: 10px 0 0 0; font-size: 0.9rem;">
                            The PDF file could not be accessed for preview. The file may have been deleted or moved.
                        </p>
                        <details style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 0.85rem;">
                            <summary style="cursor: pointer; font-weight: 500;">Error Details</summary>
                            <pre style="margin: 10px 0 0 0; overflow-x: auto; background: #fff; padding: 8px; border-radius: 3px;">Error: ${data.error}
File Path: ${data.file_path || 'N/A'}</pre>
                        </details>
                        <a href="${serveFileUrl}" class="btn btn-sm btn-primary mt-3" download>
                            <i data-lucide="download" style="width: 14px; height: 14px; display: inline;"></i> Download PDF
                        </a>
                    </div>
                `;
                console.error('PDF preview error:', data.error);
            }
        })
        .catch(error => {
            console.error('PDF check failed:', error);
            clearInterval(loadingTimer);
            clearTimeout(timeoutHandle);
            // Fallback: load PDF directly via serve-pdf endpoint
            previewContainer.innerHTML = `
                <iframe id="pdfPreview" 
                        style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;"
                        frameborder="0"
                        src="${serveFileUrl}#toolbar=1&navpanes=0"></iframe>
                <div style="font-size: 0.85rem; color: #666; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                    <i data-lucide="info" style="width: 14px; height: 14px; display: inline;"></i> 
                    If PDF doesn't display, <a href="${serveFileUrl}" download>download it here</a>
                </div>
            `;
        });
    
    const modal = new bootstrap.Modal(document.getElementById('viewNoteModal'));
    modal.show();
}
</script>

<!-- View Note Modal -->
<div class="modal fade" id="viewNoteModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title" id="viewTitle">Note Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="detailsTab" data-bs-toggle="tab" data-bs-target="#detailsContent" type="button" role="tab">
                            <i data-lucide="info" style="width: 16px; height: 16px; display: inline;"></i> Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="previewTab" data-bs-toggle="tab" data-bs-target="#previewContent" type="button" role="tab">
                            <i data-lucide="file-text" style="width: 16px; height: 16px; display: inline;"></i> Preview
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="detailsContent" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Trade:</strong></p>
                                <p id="viewTrade" class="text-muted">-</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Subject:</strong></p>
                                <p id="viewSubject" class="text-muted">-</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Uploaded By:</strong></p>
                                <p id="viewUploadedBy" class="text-muted">-</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date:</strong></p>
                                <p id="viewDate" class="text-muted">-</p>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <p><strong>Status:</strong></p>
                            <span id="viewStatus" class="badge bg-success">Active</span>
                        </div>
                        
                        <div class="mb-3">
                            <p><strong>Description:</strong></p>
                            <p id="viewDescription" class="text-muted bg-light p-3 rounded">-</p>
                        </div>
                        
                        <div class="mb-3">
                            <p><strong>File:</strong></p>
                            <a id="viewFile" href="#" download class="btn btn-primary btn-sm">
                                <i data-lucide="download" style="width: 16px; height: 16px; display: inline;"></i> Download PDF
                            </a>
                        </div>
                    </div>
                    
                    <!-- Preview Tab -->
                    <div class="tab-pane fade" id="previewContent" role="tabpanel">
                        <div class="alert alert-info mb-3">
                            <i data-lucide="info" style="width: 16px; height: 16px; display: inline;"></i> 
                            PDF Preview
                        </div>
                        <iframe id="pdfPreview" 
                                style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;"
                                frameborder="0"></iframe>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
