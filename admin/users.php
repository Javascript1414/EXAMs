<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_emails.php';
require_once __DIR__ . '/../includes/user_deletion_functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle User Actions (Block/Unblock/Edit/Approve/Reject)
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
            } elseif ($action === 'approve') {
                try {
                    // Get user details for email
                    $userDetailStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                    $userDetailStmt->execute([$user_id]);
                    $userDetail = $userDetailStmt->fetch();
                    
                    // Update approval status
                    $pdo->prepare("UPDATE users SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$_SESSION['user_id'], $user_id]);
                    
                    // Log approval
                    $pdo->prepare("INSERT INTO admin_approvals_log (user_id, admin_id, action, ip_address, user_agent) VALUES (?, ?, 'approved', ?, ?)")->execute([
                        $user_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']
                    ]);
                    
                    // Send approval email notification
                    if ($userDetail) {
                        $email_sent = sendApprovalNotificationEmail(
                            $userDetail['email'],
                            $userDetail['full_name'],
                            $user_id
                        );
                        
                        error_log('Approval Email Status: ' . ($email_sent ? 'SENT' : 'FAILED') . ' for User ID: ' . $user_id);
                    }
                    
                    $_SESSION['success_message'] = "Student has been approved. Notification email sent.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error approving student: " . $e->getMessage();
                    error_log('Student Approval Error: ' . $e->getMessage());
                }
            } elseif ($action === 'reject') {
                try {
                    $reason = sanitizeInput($_POST['rejection_reason'] ?? '');
                    $pdo->prepare("UPDATE users SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?")->execute([
                        $_SESSION['user_id'], $reason, $user_id
                    ]);
                    // Log rejection
                    $pdo->prepare("INSERT INTO admin_approvals_log (user_id, admin_id, action, reason, ip_address, user_agent) VALUES (?, ?, 'rejected', ?, ?, ?)")->execute([
                        $user_id, $_SESSION['user_id'], $reason, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']
                    ]);
                    $_SESSION['success_message'] = "Student has been rejected.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error rejecting student.";
                }
            } elseif ($action === 'delete' && hasRole('superadmin')) {
                try {
                    $deletion_reason = sanitizeInput($_POST['deletion_reason'] ?? '');
                    archiveUserBeforeDeletion($user_id, $deletion_reason, $_SESSION['user_id']);
                    $_SESSION['success_message'] = "User has been permanently deleted and archived.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
                    error_log('User Deletion Error: ' . $e->getMessage());
                }
            }
        } else {
            $_SESSION['error_message'] = "Invalid action or permission denied.";
        }
    }
    redirect('/admin/users.php');
}

// Handle Search and Pagination
$search = sanitizeInput($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

// Get paginated users
$pagination = getUsersPaginated($page, $per_page, $search);
$users = $pagination['records'];
$total_pages = $pagination['total_pages'];
$total_records = $pagination['total_records'];
$current_page = $pagination['current_page'];

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
        
        <!-- Results Info -->
        <div class="mb-3 text-muted small">
            Showing <?= count($users) > 0 ? (($current_page - 1) * $per_page) + 1 : 0 ?> 
            to <?= min($current_page * $per_page, $total_records) ?> 
            of <?= $total_records ?> users
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Trade</th>
                        <th>Status</th>
                        <th>Approval</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr <?php if($user['approval_status'] === 'pending') { echo 'style="background-color: rgba(255, 193, 7, 0.1);"'; } ?>>
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
                        <td>
                            <?php if($user['approval_status'] === 'pending'): ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">⏳ Pending</span>
                            <?php elseif($user['approval_status'] === 'approved'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">✓ Approved</span>
                            <?php elseif($user['approval_status'] === 'rejected'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">✗ Rejected</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if($user['approval_status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success me-1" onclick="approveStudent(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                    <i data-lucide="check" style="width:14px; height:14px;" class="me-1"></i>Approve
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="rejectStudent(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                    <i data-lucide="x" style="width:14px; height:14px;" class="me-1"></i>Reject
                                </button>
                            <?php else: ?>
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
                                
                                <?php if(hasRole('superadmin')): ?>
                                    <button class="btn btn-sm btn-outline-dark" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                        <i data-lucide="trash-2" style="width:14px; height:14px;" class="me-1"></i>Delete
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Previous Button -->
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Previous</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">First</span></li>
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif;
                
                for ($i = $start_page; $i <= $end_page; $i++):
                    $active = ($i === $current_page) ? 'active' : '';
                ?>
                    <li class="page-item <?= $active ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor;
                
                if ($end_page < $total_pages): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                
                <!-- Next Button -->
                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Last</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Next</span></li>
                    <li class="page-item disabled"><span class="page-link">Last</span></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
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

function approveStudent(userId, userName) {
    if (confirm(`Approve student: ${userName}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectStudent(userId, userName) {
    const reason = prompt(`Reject student: ${userName}\n\nEnter reason for rejection (optional):`);
    if (reason !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="user_id" value="${userId}">
            <input type="hidden" name="rejection_reason" value="${reason}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteUser(userId, userName) {
    const reason = prompt(`⚠️ PERMANENTLY DELETE: ${userName}\n\nEnter reason for deletion:\n\n(This action cannot be undone. The user will be archived.)`);
    if (reason !== null && reason.trim() !== '') {
        if (confirm('Are you absolutely sure? This will permanently delete the user account.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="deletion_reason" value="${reason}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    } else if (reason !== null) {
        alert('Please enter a reason for deletion.');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>