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
$query = "SELECT c.*, u.full_name, u.email, e.exam_name 
          FROM certificates c 
          JOIN users u ON c.student_id = u.id 
          JOIN exams e ON c.exam_id = e.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (c.certificate_id LIKE ? OR c.verification_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $likeSearch = "%$search%";
    $params = [$likeSearch, $likeSearch, $likeSearch, $likeSearch];
}

$query .= " ORDER BY c.issued_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
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
                            <a href="<?= BASE_URL ?>/student/certificate_view.php?id=<?= $c['result_id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1">View</a>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <?php if($c['status'] === 'active'): ?><button type="submit" name="action" value="revoke" class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke this certificate?');">Revoke</button><?php endif; ?>
                                <?php if($c['status'] === 'revoked'): ?><button type="submit" name="action" value="reissue" class="btn btn-sm btn-outline-success" onclick="return confirm('Reactivate this certificate?');">Reissue</button><?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($certificates)): ?><tr><td colspan="5" class="text-center py-4 text-muted">No certificates found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>