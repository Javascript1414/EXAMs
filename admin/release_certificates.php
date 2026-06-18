<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$results_per_page = 5;

$where_clause = "WHERE r.is_passed = 1";
$count_params = [];
$query_params = [];

if (!empty($search)) {
    $where_clause .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR e.exam_name LIKE ?)";
    $likeSearch = "%$search%";
    $count_params = [$likeSearch, $likeSearch, $likeSearch];
    $query_params = [$likeSearch, $likeSearch, $likeSearch];
}

if (!empty($status)) {
    if ($status === 'no_cert') {
        $where_clause .= " AND c.id IS NULL";
    } elseif ($status === 'has_cert') {
        $where_clause .= " AND c.id IS NOT NULL";
    }
}

// Count total results
$count_query = "SELECT COUNT(*) as total FROM results r JOIN exams e ON r.exam_id = e.id JOIN users u ON r.student_id = u.id LEFT JOIN certificates c ON r.id = c.result_id " . $where_clause;
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_results = $count_stmt->fetch()['total'];
$total_pages = ceil($total_results / $results_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $results_per_page;

$query = "SELECT r.*, e.exam_name, u.full_name, u.email, c.id as cert_id,
          CASE WHEN c.id IS NOT NULL THEN 'has_cert' ELSE 'no_cert' END as cert_status
          FROM results r 
          JOIN exams e ON r.exam_id = e.id 
          JOIN users u ON r.student_id = u.id 
          LEFT JOIN certificates c ON r.id = c.result_id 
          " . $where_clause . "
          ORDER BY r.created_at DESC
          LIMIT ? OFFSET ?";
$query_params[] = $results_per_page;
$query_params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$results = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">📜 Certificate Release Management</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="d-flex gap-2 flex-wrap">
            <input type="text" name="search" class="form-control" placeholder="Search by Student Name, Email or Exam..." value="<?= htmlspecialchars($search) ?>" style="max-width: 400px;">
            
            <select name="status" class="form-control" style="max-width: 200px;">
                <option value="">All Statuses</option>
                <option value="no_cert" <?= $status === 'no_cert' ? 'selected' : '' ?>>No Certificate Yet</option>
                <option value="has_cert" <?= $status === 'has_cert' ? 'selected' : '' ?>>Already Certified</option>
            </select>
            
            <button type="submit" class="btn btn-primary d-flex align-items-center"><i data-lucide="search" class="me-2" style="width:16px;"></i> Search</button>
            
            <?php if(!empty($search) || !empty($status)): ?>
                <a href="release_certificates.php" class="btn btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card p-4">
        <style>
            .table tbody td, .table thead th {
                font-size: 16px;
                padding: 12px 8px;
            }
            .table thead th {
                font-weight: 700;
                font-size: 17px;
            }
            .badge {
                font-size: 14px;
                padding: 6px 10px;
            }
            .btn-sm {
                font-size: 14px;
                padding: 6px 12px;
            }
        </style>
        <!-- Results Info -->
        <div class="mb-3 text-muted" style="font-size: 15px;">
            Showing <?= $total_results > 0 ? (($page - 1) * $results_per_page) + 1 : 0 ?> to <?= min($page * $results_per_page, $total_results) ?> of <?= $total_results ?> results
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Certificate Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['exam_name']) ?></td>
                        <td><span class="badge bg-success"><?= (float)$r['percentage'] ?>%</span></td>
                        <td>
                            <?php if ($r['cert_status'] === 'has_cert'): ?>
                                <span class="badge bg-success">✓ Issued</span>
                            <?php else: ?>
                                <span class="badge bg-warning">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($r['cert_status'] === 'no_cert'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin/certificate_actions.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="release">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Release certificate for this student?');">📜 Release Certificate</button>
                                </form>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/student/certificate_view.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">👁️ View</a>
                                <form method="POST" action="<?= BASE_URL ?>/admin/certificate_actions.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="cert_id" value="<?= $r['cert_id'] ?>">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="send_email">
                                    <button type="submit" class="btn btn-sm btn-info" onclick="return confirm('Send certificate to student email?');">📧 Email</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($results)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No passed exam results found.</td></tr>
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
                    <a class="page-link" href="release_certificates.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="release_certificates.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="release_certificates.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="release_certificates.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="release_certificates.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
