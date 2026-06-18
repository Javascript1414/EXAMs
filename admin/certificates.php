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
            if ($action === 'revoke') {
                $pdo->prepare("UPDATE certificates SET status = 'revoked' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Certificate revoked successfully.";
            } elseif ($action === 'reissue') {
                $pdo->prepare("UPDATE certificates SET status = 'active' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Certificate reactivated successfully.";
            }
        }
    }
    redirect('/admin/certificates.php');
}

$search = sanitizeInput($_GET['search'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$certs_per_page = 5;

$where_clause = "WHERE 1=1";
$count_params = [];
$query_params = [];

if (!empty($search)) {
    $where_clause .= " AND (c.certificate_id LIKE ? OR c.verification_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $likeSearch = "%$search%";
    $count_params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
    $query_params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
}

// Count total certificates
$count_query = "SELECT COUNT(*) as total FROM certificates c JOIN users u ON c.student_id = u.id JOIN exams e ON c.exam_id = e.id " . $where_clause;
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_certs = $count_stmt->fetch()['total'];
$total_pages = ceil($total_certs / $certs_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $certs_per_page;

$query = "SELECT c.*, u.full_name, u.email, e.exam_name 
          FROM certificates c 
          JOIN users u ON c.student_id = u.id 
          JOIN exams e ON c.exam_id = e.id 
          " . $where_clause . "
          ORDER BY c.issued_at DESC
          LIMIT ? OFFSET ?";
$query_params[] = $certs_per_page;
$query_params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($query_params);
$certificates = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Certificate Management</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by Cert ID, Verification Code, Student Name or Email..." value="<?= htmlspecialchars($search) ?>" style="max-width: 500px;">
            <button type="submit" class="btn btn-primary d-flex align-items-center"><i data-lucide="search" class="me-2" style="width:16px;"></i> Search</button>
            <?php if(!empty($search)): ?>
                <a href="certificates.php" class="btn btn-outline-secondary ms-2">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card p-4">
        <!-- Results Info -->
        <div class="mb-3 text-muted small">
            Showing <?= $total_certs > 0 ? (($page - 1) * $certs_per_page) + 1 : 0 ?> to <?= min($page * $certs_per_page, $total_certs) ?> of <?= $total_certs ?> certificates
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Certificate ID</th>
                        <th>Student Details</th>
                        <th>Exam Name</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificates as $c): ?>
                    <tr>
                        <td><span class="fw-bold font-monospace"><?= htmlspecialchars($c['certificate_id']) ?></span></td>
                        <td><div class="fw-semibold text-dark"><?= htmlspecialchars($c['full_name']) ?></div><div class="small text-muted"><?= htmlspecialchars($c['email']) ?></div></td>
                        <td><div class="text-dark"><?= htmlspecialchars($c['exam_name']) ?></div><div class="small text-muted"><?= (float)$c['percentage'] ?>% Score</div></td>
                        <td><?= $c['status'] === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Revoked</span>' ?></td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <a href="<?= BASE_URL ?>/student/certificate_view.php?id=<?= $c['result_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View Certificate">👁️ View</a>
                                <form method="POST" action="<?= BASE_URL ?>/admin/certificate_actions.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="cert_id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="id" value="<?= $c['result_id'] ?>">
                                    <?php if($c['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="send_email" class="btn btn-sm btn-outline-info" title="Send via Email" onclick="return confirm('Send this certificate to the student?');">📧 Email</button>
                                    <?php endif; ?>
                                </form>
                                <form method="POST" action="" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <?php if($c['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="revoke" class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke this certificate?');">🚫 Revoke</button>
                                    <?php endif; ?>
                                    <?php if($c['status'] === 'revoked'): ?>
                                        <button type="submit" name="action" value="reissue" class="btn btn-sm btn-outline-success" onclick="return confirm('Reactivate this certificate?');">✓ Reissue</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($certificates)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No certificates found.</td></tr><?php endif; ?>
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
                    <a class="page-link" href="certificates.php?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="certificates.php?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="certificates.php?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="certificates.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="certificates.php?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>