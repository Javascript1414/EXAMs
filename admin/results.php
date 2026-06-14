<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$trade_id = (int)($_GET['trade_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);

$query = "SELECT r.*, u.full_name, u.email, e.exam_name, t.trade_name, s.subject_name 
          FROM results r 
          JOIN users u ON r.student_id = u.id 
          JOIN exams e ON r.exam_id = e.id 
          JOIN trades t ON e.trade_id = t.id 
          JOIN subjects s ON e.subject_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR e.exam_name LIKE ?)";
    $params[] = "%$search%";
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

$query .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Exam Results</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Search Student or Exam</label>
                <input type="text" name="search" class="form-control" placeholder="Search text..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Filter by Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>" <?= $trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Filter by Subject</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $subject_id === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="filter" style="width:16px;"></i> Filter</button>
                <a href="results.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Exam & Subject</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($r['full_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($r['email']) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($r['exam_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($r['subject_name']) ?> &bull; <?= htmlspecialchars($r['trade_name']) ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= (float)$r['obtained_marks'] ?> / <?= (float)$r['total_marks'] ?> (<?= (float)$r['percentage'] ?>%)</span></td>
                        <td><?= $r['is_passed'] ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>' ?></td>
                        <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($results)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No results found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>