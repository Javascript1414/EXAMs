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
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$results_per_page = 10;

$base_query = "SELECT r.*, u.full_name, u.email, e.exam_name, t.trade_name, t.id as trade_id_val, s.subject_name 
               FROM results r 
               JOIN users u ON r.student_id = u.id 
               JOIN exams e ON r.exam_id = e.id 
               JOIN trades t ON e.trade_id = t.id 
               JOIN subjects s ON e.subject_id = s.id 
               WHERE 1=1";
$params = [];

if (!empty($search)) {
    $base_query .= " AND (u.full_name LIKE ? OR e.exam_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($trade_id > 0) {
    $base_query .= " AND e.trade_id = ?";
    $params[] = $trade_id;
}
if ($subject_id > 0) {
    $base_query .= " AND e.subject_id = ?";
    $params[] = $subject_id;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM results r 
                JOIN users u ON r.student_id = u.id 
                JOIN exams e ON r.exam_id = e.id 
                JOIN trades t ON e.trade_id = t.id 
                JOIN subjects s ON e.subject_id = s.id WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND (u.full_name LIKE ? OR e.exam_name LIKE ?)";
}
if ($trade_id > 0) {
    $count_query .= " AND e.trade_id = ?";
}
if ($subject_id > 0) {
    $count_query .= " AND e.subject_id = ?";
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_results = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_results / $results_per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $results_per_page;

$query = $base_query . " ORDER BY t.trade_name, r.created_at DESC LIMIT ? OFFSET ?";
$params[] = $results_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_results = $stmt->fetchAll();

// Group results by trade
$results_by_trade = [];
foreach ($all_results as $r) {
    $trade_name = $r['trade_name'];
    if (!isset($results_by_trade[$trade_name])) {
        $results_by_trade[$trade_name] = [];
    }
    $results_by_trade[$trade_name][] = $r;
}

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
        <?php if (empty($results_by_trade)): ?>
            <div class="text-center py-5 text-muted">
                <p>No results found matching your criteria.</p>
            </div>
        <?php else: ?>
            <!-- Trade-wise Accordion -->
            <div class="accordion" id="tradeAccordion">
                <?php $accordion_index = 0; foreach ($results_by_trade as $trade_name => $results): $accordion_index++; ?>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $accordion_index !== 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $accordion_index ?>" aria-expanded="<?= $accordion_index === 1 ? 'true' : 'false' ?>" aria-controls="collapse<?= $accordion_index ?>">
                            <span class="fw-semibold"><?= htmlspecialchars($trade_name) ?></span>
                            <span class="badge bg-primary ms-2"><?= count($results) ?></span>
                        </button>
                    </h2>
                    <div id="collapse<?= $accordion_index ?>" class="accordion-collapse collapse <?= $accordion_index === 1 ? 'show' : '' ?>" data-bs-parent="#tradeAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
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
                                                <div class="small text-muted"><?= htmlspecialchars($r['subject_name']) ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= (float)$r['obtained_marks'] ?> / <?= (float)$r['total_marks'] ?> (<?= (float)$r['percentage'] ?>%)</span></td>
                                            <td><?= $r['is_passed'] ? '<span class="badge bg-success">Passed</span>' : '<span class="badge bg-danger">Failed</span>' ?></td>
                                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card p-4 mt-4">
        <nav aria-label="Page navigation">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small">
                    Showing <?= (($page - 1) * $results_per_page) + 1 ?> to <?= min($page * $results_per_page, $total_results) ?> of <?= $total_results ?> results
                </div>
            </div>
            <ul class="pagination justify-content-center mb-0">
                <?php 
                // Previous button
                if ($page > 1): 
                    $prev_params = ['page' => $page - 1];
                    if (!empty($search)) $prev_params['search'] = $search;
                    if ($trade_id > 0) $prev_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $prev_params['subject_id'] = $subject_id;
                ?>
                <li class="page-item">
                    <a class="page-link" href="results.php?<?= http_build_query($prev_params) ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="results.php">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): 
                    $page_params = ['page' => $i];
                    if (!empty($search)) $page_params['search'] = $search;
                    if ($trade_id > 0) $page_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $page_params['subject_id'] = $subject_id;
                ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="results.php?<?= http_build_query($page_params) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="results.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                    $next_params = ['page' => $page + 1];
                    if (!empty($search)) $next_params['search'] = $search;
                    if ($trade_id > 0) $next_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $next_params['subject_id'] = $subject_id;
                ?>
                <li class="page-item">
                    <a class="page-link" href="results.php?<?= http_build_query($next_params) ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>