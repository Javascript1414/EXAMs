<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Both Admins and Moderators can manage Study Materials
if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle Add / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $trade_id = (int)($_POST['trade_id'] ?? 0);
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $material_type = sanitizeInput($_POST['material_type'] ?? '');
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $file_path = null;
            $youtube_url = null;

            if ($trade_id && $subject_id && $title && $material_type) {
                if ($material_type === 'youtube') {
                    $youtube_url = sanitizeInput($_POST['youtube_url'] ?? '');
                } elseif (in_array($material_type, ['pdf', 'video', 'note'])) {
                    if (isset($_FILES['material_file']) && $_FILES['material_file']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../uploads/materials/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        
                        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', basename($_FILES['material_file']['name']));
                        if (move_uploaded_file($_FILES['material_file']['tmp_name'], $uploadDir . $fileName)) {
                            $file_path = 'uploads/materials/' . $fileName;
                        } else {
                            $_SESSION['error_message'] = "File upload failed.";
                        }
                    } else {
                        $_SESSION['error_message'] = "Please select a valid file.";
                    }
                }

                if (($file_path || $youtube_url) && !isset($_SESSION['error_message'])) {
                    $stmt = $pdo->prepare("INSERT INTO study_materials (trade_id, subject_id, material_type, title, description, file_path, youtube_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$trade_id, $subject_id, $material_type, $title, $description, $file_path, $youtube_url]);
                    $_SESSION['success_message'] = "Study material added successfully.";
                }
            } else {
                $_SESSION['error_message'] = "Please fill in all required fields.";
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $stmt = $pdo->prepare("SELECT file_path, material_type FROM study_materials WHERE id = ?");
                $stmt->execute([$id]);
                $material = $stmt->fetch();
                
                if ($material) {
                    // Remove physical file if not youtube
                    if ($material['material_type'] !== 'youtube' && !empty($material['file_path']) && file_exists(__DIR__ . '/../' . $material['file_path'])) {
                        unlink(__DIR__ . '/../' . $material['file_path']);
                    }
                    $pdo->prepare("DELETE FROM study_materials WHERE id = ?")->execute([$id]);
                    $_SESSION['success_message'] = "Study material deleted.";
                }
            }
        }
    }
    redirect('/admin/materials.php');
}

// Fetch Data
$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, trade_id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();
$materials = $pdo->query("SELECT sm.*, t.trade_name, s.subject_name FROM study_materials sm JOIN trades t ON sm.trade_id = t.id JOIN subjects s ON sm.subject_id = s.id ORDER BY sm.created_at DESC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Study Materials</h3>
        <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
            <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Upload Material
        </button>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Trade / Subject</th>
                        <th>Type</th>
                        <th>Date Added</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= htmlspecialchars($m['title']) ?><br>
                            <small class="text-muted fw-normal"><?= htmlspecialchars(substr($m['description'] ?? '', 0, 50)) ?>...</small>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= htmlspecialchars($m['trade_name']) ?></span><br>
                            <small class="text-muted"><?= htmlspecialchars($m['subject_name']) ?></small>
                        </td>
                        <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($m['material_type']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                        <td class="text-end">
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this material?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($materials)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No study materials found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Study Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Trade *</label>
                        <select name="trade_id" id="trade_id" class="form-select" required onchange="filterSubjects()">
                            <option value="">Select Trade</option>
                            <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" id="subject_id" class="form-select" required>
                            <option value="">Select Subject</option>
                            <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" data-trade="<?= $s['trade_id'] ?>" style="display:none;"><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type *</label>
                    <select name="material_type" id="type_select" class="form-select" required>
                        <option value="pdf">PDF Document</option>
                        <option value="note">Notes / Text</option>
                        <option value="video">MP4 Video</option>
                        <option value="youtube">YouTube Embed</option>
                    </select>
                </div>

                <div class="mb-3" id="file_group"><label class="form-label">Upload File *</label><input type="file" name="material_file" class="form-control"></div>
                <div class="mb-3" id="youtube_group" style="display:none;"><label class="form-label">YouTube URL *</label><input type="url" name="youtube_url" class="form-control" placeholder="https://www.youtube.com/watch?v=..."></div>
                
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Material</button></div>
        </form>
    </div>
</div>

<script>
function filterSubjects() {
    const tradeId = document.getElementById('trade_id').value;
    const options = document.getElementById('subject_id').querySelectorAll('option');
    options.forEach(opt => {
        if (opt.value === "") return;
        opt.style.display = (opt.dataset.trade === tradeId || tradeId === "") ? 'block' : 'none';
    });
    document.getElementById('subject_id').value = "";
}
document.getElementById('type_select').addEventListener('change', function() {
    const isYt = (this.value === 'youtube');
    document.getElementById('file_group').style.display = isYt ? 'none' : 'block';
    document.getElementById('youtube_group').style.display = isYt ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>