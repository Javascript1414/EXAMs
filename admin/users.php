<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle User Actions (Block/Unblock/Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id > 0 && $user_id !== $_SESSION['user_id']) { // Prevent self-modification
            if ($action === 'block') {
                $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$user_id]);
                $_SESSION['success_message'] = "User has been blocked.";
            } elseif ($action === 'unblock') {
                $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$user_id]);
                $_SESSION['success_message'] = "User has been unblocked.";
            } elseif ($action === 'edit_role') {
                $role_id = (int)($_POST['role_id'] ?? 0);
                if ($role_id > 0) {
                    $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?")->execute([$role_id, $user_id]);
                    $_SESSION['success_message'] = "User role updated.";
                }
            }
        } else {
            $_SESSION['error_message'] = "Invalid action or permission denied.";
        }
    }
    redirect('/admin/users.php');
}

// Handle Search
$search = sanitizeInput($_GET['search'] ?? '');
$query = "SELECT u.id, u.full_name, u.email, u.phone, u.status, r.name as role_name, t.trade_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          LEFT JOIN trades t ON u.trade_id = t.id";

$params = [];
if (!empty($search)) {
    $query .= " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
    $likeSearch = "%$search%";
    $params = [$likeSearch, $likeSearch, $likeSearch];
}
$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch roles for edit modal
$roles = $pdo->query("SELECT id, name FROM roles")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Manage Users</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <!-- Search Form -->
        <form method="GET" action="" class="mb-4 d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>" style="max-width: 400px;">
            <button type="submit" class="btn btn-primary d-flex align-items-center"><i data-lucide="search" class="me-2" style="width:16px;"></i> Search</button>
            <?php if(!empty($search)): ?>
                <a href="users.php" class="btn btn-outline-secondary ms-2">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Trade</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></td>
                        <td>
                            <div class="small"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($user['phone']) ?></div>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($user['role_name'])) ?></span></td>
                        <td><?= htmlspecialchars($user['trade_name'] ?? 'N/A') ?></td>
                        <td>
                            <?php if($user['status'] === 'active'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Suspended</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openRoleModal(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">Edit Role</button>
                            
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <?php if ($user['status'] === 'active'): ?>
                                    <input type="hidden" name="action" value="block">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Block this user?');">Block</button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="unblock">
                                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Unblock this user?');">Unblock</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User Role: <span id="modal_user_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="edit_role">
                <input type="hidden" name="user_id" id="modal_user_id">
                <div class="mb-3">
                    <label class="form-label">Assign Role</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars(ucfirst($r['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Update Role</button></div>
        </form>
    </div>
</div>

<script>
function openRoleModal(userId, userName) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modal_user_name').innerText = userName;
    new bootstrap.Modal(document.getElementById('editRoleModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>