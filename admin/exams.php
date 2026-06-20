<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            if ($action === 'delete') {
                $pdo->prepare("DELETE FROM exams WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Exam deleted successfully.";
            } elseif ($action === 'publish') {
                $pdo->prepare("UPDATE exams SET status = 'published' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Exam published successfully.";
            } elseif ($action === 'close') {
                $pdo->prepare("UPDATE exams SET status = 'closed' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Exam closed successfully.";
            }
        }
    }
    redirect('/admin/exams.php');
}

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$trade_id = (int)($_GET['trade_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);
$teacher_id = (int)($_GET['teacher_id'] ?? 0);

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$exams_per_page = 10;

$where_clause = "WHERE 1=1";
$count_params = [];
$query_params = [];

if (!empty($search)) {
    $where_clause .= " AND e.exam_name LIKE ?";
    $count_params[] = "%$search%";
    $query_params[] = "%$search%";
}
if ($trade_id > 0) {
    $where_clause .= " AND e.trade_id = ?";
    $count_params[] = $trade_id;
    $query_params[] = $trade_id;
}
if ($subject_id > 0) {
    $where_clause .= " AND e.subject_id = ?";
    $count_params[] = $subject_id;
    $query_params[] = $subject_id;
}
if ($teacher_id > 0) {
    $where_clause .= " AND e.created_by = ?";
    $count_params[] = $teacher_id;
    $query_params[] = $teacher_id;
}

// Count total exams
$count_query = "SELECT COUNT(*) as total FROM exams e 
                JOIN trades t ON e.trade_id = t.id 
                JOIN subjects s ON e.subject_id = s.id 
                JOIN users u ON e.created_by = u.id " . $where_clause;
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_exams = $count_stmt->fetch()['total'];
$total_pages = ceil($total_exams / $exams_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $exams_per_page;

$query = "SELECT e.*, t.trade_name, s.subject_name, u.full_name as teacher_name, u.email as teacher_email,
          (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count 
          FROM exams e 
          JOIN trades t ON e.trade_id = t.id 
          JOIN subjects s ON e.subject_id = s.id 
          JOIN users u ON e.created_by = u.id " . $where_clause . "
          ORDER BY e.created_at DESC
          LIMIT ? OFFSET ?";
$query_params[] = $exams_per_page;
$query_params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$exams = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();
$teachers = $pdo->query("SELECT u.id, u.full_name FROM users u WHERE u.role_id = (SELECT id FROM roles WHERE name = 'teacher') ORDER BY u.full_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Manage Exams</h3>
        <div class="d-flex gap-2">
            <a href="google_form_exams.php" class="btn btn-outline-info btn-sm d-inline-flex align-items-center">
                <i data-lucide="globe" class="me-2" style="width: 16px; height: 16px;"></i> Google Form Exams
            </a>
            <a href="exam_add.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Create Exam
            </a>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Search Exam Name</label>
                <input type="text" name="search" class="form-control" placeholder="Search text..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>" <?= $trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Subject</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $subject_id === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Teacher</label>
                <select name="teacher_id" class="form-select">
                    <option value="">All Teachers</option>
                    <?php foreach($teachers as $teacher): ?><option value="<?= $teacher['id'] ?>" <?= $teacher_id === $teacher['id'] ? 'selected' : '' ?>><?= htmlspecialchars($teacher['full_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="filter" style="width:16px;"></i> Filter</button>
                <a href="exams.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <!-- Results Info -->
        <div class="mb-3 text-muted small">
            Showing <?= $total_exams > 0 ? (($page - 1) * $exams_per_page) + 1 : 0 ?> to <?= min($page * $exams_per_page, $total_exams) ?> of <?= $total_exams ?> exams
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Exam Info</th>
                        <th>Configuration</th>
                        <th>Questions</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $e): ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($e['exam_name']) ?> <span class="badge bg-secondary ms-1"><?= htmlspecialchars($e['exam_type']) ?></span></div>
                            <div class="small text-muted"><?= htmlspecialchars($e['subject_name']) ?> &bull; <?= htmlspecialchars($e['trade_name']) ?></div>
                            <div class="small text-muted mt-2" style="background:#f0f0f0; padding:5px; border-radius:4px;">
                                <i style="width: 12px; height: 12px;">👨‍🏫</i> 
                                <strong><?= htmlspecialchars($e['teacher_name']) ?></strong><br>
                                <span style="font-size: 0.8em;">📧 <?= htmlspecialchars($e['teacher_email']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="small text-dark"><i data-lucide="clock" class="me-1" style="width: 14px; height: 14px;"></i> <?= $e['duration_minutes'] ?> Mins</div>
                            <div class="small text-muted"><i data-lucide="check-circle" class="me-1" style="width: 14px; height: 14px;"></i> Pass: <?= (float)$e['passing_marks'] ?> / <?= (float)$e['total_marks'] ?></div>
                        </td>
                        <td><span class="badge bg-info text-dark"><?= $e['question_count'] ?> Assigned</span></td>
                        <td>
                            <?php $badge = match($e['status']) { 'published' => 'bg-success', 'closed' => 'bg-danger', default => 'bg-warning text-dark' }; ?>
                            <span class='badge <?= $badge ?> text-uppercase'><?= htmlspecialchars($e['status']) ?></span>
                        </td>
                        <td class="text-end">
                            <a href="exam_assign_questions.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-success me-1">Assign Questions</a>
                            <a href="exam_edit.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                <?php if($e['status'] === 'draft'): ?><button type="submit" name="action" value="publish" class="btn btn-sm btn-outline-success me-1" onclick="return confirm('Publish this exam to students?');">Publish</button><?php endif; ?>
                                <?php if($e['status'] === 'published'): ?><button type="submit" name="action" value="close" class="btn btn-sm btn-outline-warning me-1" onclick="return confirm('Close this exam? Students will no longer be able to take it.');">Close</button><?php endif; ?>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this exam?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($exams)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No exams found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center mb-0">
                <?php 
                // Previous button
                if ($page > 1): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="exams.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="exams.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="exams.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="exams.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="exams.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>