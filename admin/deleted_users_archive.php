<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/user_deletion_functions.php';
requireLogin();

// Only superadmin can access
if (!hasRole('superadmin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Ensure archive table exists
ensureDeletedUsersArchiveTableExists();

// Handle Restore Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $archive_id = (int)($_POST['archive_id'] ?? 0);
        
        if ($archive_id > 0) {
            if ($action === 'restore') {
                try {
                    restoreUserFromArchive($archive_id, $_SESSION['user_id']);
                    $_SESSION['success_message'] = "User has been successfully restored to active database.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Error restoring user: " . $e->getMessage();
                    error_log('User Restoration Error: ' . $e->getMessage());
                }
            }
        } else {
            $_SESSION['error_message'] = "Invalid archive ID.";
        }
    }
    redirect('/admin/deleted_users_archive.php');
}

// Get filters
$page = max(1, (int)($_GET['page'] ?? 1));
$search = sanitizeInput($_GET['search'] ?? '');
$role_filter = sanitizeInput($_GET['role'] ?? '');
$from_date = sanitizeInput($_GET['from_date'] ?? '');
$to_date = sanitizeInput($_GET['to_date'] ?? '');

$filters = [
    'search' => $search,
    'role_name' => $role_filter,
    'from_date' => $from_date,
    'to_date' => $to_date
];

// Get paginated deleted users
$per_page = 10;
$pagination = getDeletedUsersPaginated($page, $per_page, $filters);
$deleted_users = $pagination['records'];
$total_pages = $pagination['total_pages'];
$total_records = $pagination['total_records'];
$current_page = $pagination['current_page'];

// Get unique roles for filter dropdown
$roles_result = $pdo->query("SELECT DISTINCT role_name FROM deleted_users_archive WHERE restored_at IS NULL ORDER BY role_name");
$available_roles = $roles_result ? $roles_result->fetchAll(PDO::FETCH_ASSOC) : [];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">🗂️ Deleted Users Archive</h3>
        <a href="/admin/users.php" class="btn btn-secondary btn-sm">← Back to Users</a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <!-- Filters -->
        <form method="GET" action="" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php foreach($available_roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['role_name']) ?>" 
                                    <?= $role_filter === $role['role_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" placeholder="From Date"
                           value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" placeholder="To Date"
                           value="<?= htmlspecialchars($to_date) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>
        
        <!-- Results Info -->
        <div class="mb-3 text-muted small">
            Showing <?= count($deleted_users) > 0 ? (($current_page - 1) * $per_page) + 1 : 0 ?> 
            to <?= min($current_page * $per_page, $total_records) ?> 
            of <?= $total_records ?> deleted users
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Deleted By</th>
                        <th>Deleted At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_users as $user): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($user['full_name']) ?></td>
                        <td>
                            <div class="small"><?= htmlspecialchars($user['email']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($user['phone']) ?></div>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($user['role_name'])) ?></span></td>
                        <td>
                            <?php 
                            $status_badge = match($user['account_status']) {
                                'active' => '<span class="badge bg-success bg-opacity-10 text-success border border-success">Active</span>',
                                'suspended' => '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Suspended</span>',
                                'inactive' => '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Inactive</span>',
                                default => '<span class="badge bg-secondary">Unknown</span>'
                            };
                            echo $status_badge;
                            ?>
                        </td>
                        <td><?= htmlspecialchars($user['deleted_by_admin_name'] ?? 'Unknown Admin') ?></td>
                        <td class="small text-muted"><?= date('M d, Y H:i', strtotime($user['deleted_at'])) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-info me-1" onclick="viewDeletedUserDetails(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                <i data-lucide="eye" style="width:14px; height:14px;" class="me-1"></i>View
                            </button>
                            <button class="btn btn-sm btn-success" onclick="restoreUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                <i data-lucide="rotate-ccw" style="width:14px; height:14px;" class="me-1"></i>Restore
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($deleted_users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No deleted users in archive.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?= buildQueryString($search, $role_filter, $from_date, $to_date) ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?><?= buildQueryString($search, $role_filter, $from_date, $to_date) ?>">Previous</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">First</span></li>
                    <li class="page-item disabled"><span class="page-link">Previous</span></li>
                <?php endif; ?>
                
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
                        <a class="page-link" href="?page=<?= $i ?><?= buildQueryString($search, $role_filter, $from_date, $to_date) ?>"><?= $i ?></a>
                    </li>
                <?php endfor;
                
                if ($end_page < $total_pages): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?><?= buildQueryString($search, $role_filter, $from_date, $to_date) ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $total_pages ?><?= buildQueryString($search, $role_filter, $from_date, $to_date) ?>">Last</a>
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

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deleted User Details: <span id="details_user_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="details_content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="confirmRestore()">Restore User</button>
            </div>
        </div>
    </div>
</div>

<script>
let current_archive_id = null;

function viewDeletedUserDetails(archiveId, userName) {
    current_archive_id = archiveId;
    document.getElementById('details_user_name').innerText = userName;
    
    // Fetch archived user data from backend
    fetch('deleted_users_archive.php?action=get_details&id=' + archiveId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                const originalData = data.original_data;
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Full Name:</strong><br>${escapeHtml(user.full_name)}<br><br>
                            <strong>Email:</strong><br>${escapeHtml(user.email)}<br><br>
                            <strong>Phone:</strong><br>${escapeHtml(user.phone)}<br><br>
                            <strong>Role:</strong><br>${escapeHtml(user.role_name)}<br><br>
                            <strong>Trade:</strong><br>${escapeHtml(user.trade_name || 'N/A')}<br><br>
                        </div>
                        <div class="col-md-6">
                            <strong>Account Status:</strong><br>${escapeHtml(user.account_status)}<br><br>
                            <strong>Approval Status:</strong><br>${escapeHtml(user.approval_status)}<br><br>
                            <strong>Registered On:</strong><br>${formatDate(user.registration_date)}<br><br>
                            <strong>Last Login:</strong><br>${user.last_login ? formatDate(user.last_login) : 'Never'}<br><br>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Deleted By Admin:</strong><br>${escapeHtml(user.deleted_by_admin_name || 'Unknown')}<br><br>
                            <strong>Deleted On:</strong><br>${formatDateTime(user.deleted_at)}<br><br>
                        </div>
                        <div class="col-md-6">
                            <strong>Deletion Reason:</strong><br>${user.deletion_reason ? escapeHtml(user.deletion_reason) : '(No reason provided)'}<br><br>
                        </div>
                    </div>
                `;
                
                document.getElementById('details_content').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewDetailsModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}

function confirmRestore() {
    if (current_archive_id && confirm('Restore this user to the active database?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="restore">
            <input type="hidden" name="archive_id" value="${current_archive_id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function restoreUser(archiveId, userName) {
    if (confirm(`Restore user: ${userName}?\n\nThis will recreate the user in the active database.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="restore">
            <input type="hidden" name="archive_id" value="${archiveId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + 
           date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}
</script>

<?php 
// Handle AJAX request for user details
if (isset($_GET['action']) && $_GET['action'] === 'get_details') {
    // Ensure archive table exists
    ensureDeletedUsersArchiveTableExists();
    
    $archive_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT d.*, a.full_name as deleted_by_admin_name FROM deleted_users_archive d LEFT JOIN users a ON d.deleted_by_admin_id = a.id WHERE d.id = ?");
    $stmt->execute([$archive_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $original_data = json_decode($user['original_user_data'], true);
        echo json_encode([
            'success' => true,
            'user' => $user,
            'original_data' => $original_data
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

function buildQueryString($search, $role, $from_date, $to_date) {
    $params = [];
    if (!empty($search)) $params[] = 'search=' . urlencode($search);
    if (!empty($role)) $params[] = 'role=' . urlencode($role);
    if (!empty($from_date)) $params[] = 'from_date=' . urlencode($from_date);
    if (!empty($to_date)) $params[] = 'to_date=' . urlencode($to_date);
    return $params ? '&' . implode('&', $params) : '';
}

require_once __DIR__ . '/../includes/footer.php'; 
?>
