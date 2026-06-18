<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $trade_id = (int)($_POST['trade_id'] ?? 0);
            $subject_name = sanitizeInput($_POST['subject_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($trade_id > 0 && !empty($subject_name)) {
                $stmt = $pdo->prepare("INSERT INTO subjects (trade_id, subject_name, description) VALUES (?, ?, ?)");
                $stmt->execute([$trade_id, $subject_name, $description]);
                $_SESSION['success_message'] = "Subject added successfully.";
            } else {
                $_SESSION['error_message'] = "Trade selection and Subject Name are required.";
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $trade_id = (int)($_POST['trade_id'] ?? 0);
            $subject_name = sanitizeInput($_POST['subject_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($id > 0 && $trade_id > 0 && !empty($subject_name)) {
                $stmt = $pdo->prepare("UPDATE subjects SET trade_id = ?, subject_name = ?, description = ? WHERE id = ?");
                $stmt->execute([$trade_id, $subject_name, $description, $id]);
                $_SESSION['success_message'] = "Subject updated successfully.";
            } else {
                $_SESSION['error_message'] = "Invalid input for edit.";
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success_message'] = "Subject deleted successfully.";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Cannot delete subject. It may be in use by study materials or exams.";
                }
            }
        }
    }
    redirect('/admin/subjects.php');
}

// Handle Search & Filters
$search = sanitizeInput($_GET['search'] ?? '');
$filter_trade_id = (int)($_GET['trade_id'] ?? 0);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$subjects_per_page = 10;

$where_clause = "WHERE 1=1";
$count_params = [];
$query_params = [];

if (!empty($search)) {
    $where_clause .= " AND (s.subject_name LIKE ? OR s.description LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $query_params[] = "%$search%";
    $query_params[] = "%$search%";
}
if ($filter_trade_id > 0) {
    $where_clause .= " AND s.trade_id = ?";
    $count_params[] = $filter_trade_id;
    $query_params[] = $filter_trade_id;
}

// Count total subjects
$count_query = "SELECT COUNT(*) as total FROM subjects s JOIN trades t ON s.trade_id = t.id " . $where_clause;
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_subjects = $count_stmt->fetch()['total'];
$total_pages = ceil($total_subjects / $subjects_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $subjects_per_page;

$query = "SELECT s.*, t.trade_name 
          FROM subjects s 
          JOIN trades t ON s.trade_id = t.id 
          " . $where_clause . "
          ORDER BY s.subject_name ASC
          LIMIT ? OFFSET ?";
$query_params[] = $subjects_per_page;
$query_params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$subjects = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Manage Subjects</h3>
        <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Add New Subject
        </button>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <!-- Filters -->
        <form method="GET" action="" class="row g-3 align-items-end mb-4">
            <div class="col-md-5">
                <label class="form-label text-muted small mb-1">Search Subjects</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name or description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Filter by Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $filter_trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="search" style="width:16px;"></i> Search</button>
                <a href="subjects.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>

        <!-- Results Info -->
        <div class="mb-3 text-muted small">
            Showing <?= $total_subjects > 0 ? (($page - 1) * $subjects_per_page) + 1 : 0 ?> to <?= min($page * $subjects_per_page, $total_subjects) ?> of <?= $total_subjects ?> subjects
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Subject Name</th>
                        <th>Assigned Trade</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($subject['subject_name']) ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= htmlspecialchars($subject['trade_name']) ?></span></td>
                        <td class="text-muted"><?= htmlspecialchars($subject['description'] ?? 'No description') ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= $subject['id'] ?>, <?= $subject['trade_id'] ?>, '<?= htmlspecialchars(addslashes($subject['subject_name'])) ?>', '<?= htmlspecialchars(addslashes($subject['description'] ?? '')) ?>')">
                                Edit
                            </button>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $subject['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subjects)): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">No subjects found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <?php 
                // Previous button
                if ($page > 1): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="subjects.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_trade_id > 0 ? '&trade_id=' . $filter_trade_id : '' ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="subjects.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_trade_id > 0 ? '&trade_id=' . $filter_trade_id : '' ?>">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="subjects.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_trade_id > 0 ? '&trade_id=' . $filter_trade_id : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="subjects.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_trade_id > 0 ? '&trade_id=' . $filter_trade_id : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="subjects.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $filter_trade_id > 0 ? '&trade_id=' . $filter_trade_id : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Subject Modals (Combined Logic) -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="modalId">
                <div class="mb-3">
                    <label class="form-label">Assign to Trade *</label>
                    <select name="trade_id" id="modalTradeId" class="form-select" required>
                        <option value="">-- Select a Trade --</option>
                        <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Subject Name *</label><input type="text" name="subject_name" id="modalSubjectName" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="modalDescription" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary" id="modalBtn">Save Subject</button></div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, tradeId, name, desc) {
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = id;
    document.getElementById('modalTradeId').value = tradeId;
    document.getElementById('modalSubjectName').value = name;
    document.getElementById('modalDescription').value = desc;
    document.getElementById('modalTitle').innerText = 'Edit Subject';
    document.getElementById('modalBtn').innerText = 'Update Subject';
    new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
}

// Reset modal on close so "Add" works correctly next time
document.getElementById('addSubjectModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalTradeId').value = '';
    document.getElementById('modalSubjectName').value = '';
    document.getElementById('modalDescription').value = '';
    document.getElementById('modalTitle').innerText = 'Add New Subject';
    document.getElementById('modalBtn').innerText = 'Save Subject';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>