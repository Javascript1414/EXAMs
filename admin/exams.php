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

$query = "SELECT e.*, t.trade_name, s.subject_name, (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count 
          FROM exams e 
          JOIN trades t ON e.trade_id = t.id 
          JOIN subjects s ON e.subject_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND e.exam_name LIKE ?";
    $params[] = "%$search%";
}
if ($trade_id > 0) {
    $query .= " AND e.trade_id = ?";
    $params[] = $trade_id;
}
if ($subject_id > 0) {
    $query .= " AND e.subject_id = ?";
    $params[] = $subject_id;
}

$query .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Manage Exams</h3>
        <a href="exam_add.php" class="btn btn-primary btn-sm d-inline-flex align-items-center">
            <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Create Exam
        </a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Search Exam Name</label>
                <input type="text" name="search" class="form-control" placeholder="Search text..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>" <?= $trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Subject</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $subject_id === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="filter" style="width:16px;"></i> Filter</button>
                <a href="exams.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
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
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>