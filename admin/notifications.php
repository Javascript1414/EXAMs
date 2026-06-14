<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification_helper.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $title = sanitizeInput($_POST['title'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $type = sanitizeInput($_POST['notification_type'] ?? 'general');
        $target_type = sanitizeInput($_POST['target_type'] ?? 'all');
        $target_id = !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null;
        $action_url = sanitizeInput($_POST['action_url'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? 'bell');
        
        if (empty($action_url)) $action_url = null;

        if (!empty($title) && !empty($message)) {
            sendNotification($pdo, $title, $message, $type, $target_type, $target_id, $action_url, $icon, $_SESSION['user_id']);
            $_SESSION['success_message'] = "Notification successfully dispatched to the target audience.";
            redirect('/admin/notifications.php');
        } else {
            $_SESSION['error_message'] = "Title and Message are required.";
        }
    }
}

// Fetch recent broadcast history
$query = "SELECT n.*, u.full_name as dispatcher_name, (SELECT COUNT(*) FROM notification_recipients WHERE notification_id = n.id) as delivery_count 
          FROM notifications n 
          LEFT JOIN users u ON n.created_by = u.id 
          ORDER BY n.created_at DESC LIMIT 50";
$history = $pdo->query($query)->fetchAll();

// Form Select Data
$roles = $pdo->query("SELECT id, name FROM roles")->fetchAll();
$trades = $pdo->query("SELECT id, trade_name FROM trades")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Notification System</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4">
        <div class="col-lg-5">
            <form method="POST" action="" class="card border-0 shadow-sm p-4">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Broadcast Notification</h5>
                
                <div class="mb-3"><label class="form-label fw-bold">Title *</label><input type="text" name="title" class="form-control" required placeholder="e.g. Server Maintenance Alert"></div>
                <div class="mb-3"><label class="form-label fw-bold">Message *</label><textarea name="message" class="form-control" rows="3" required placeholder="Details about the notification..."></textarea></div>
                
                <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label fw-bold">Type</label><select name="notification_type" class="form-select"><option value="general">General</option><option value="exam">Exam Alert</option><option value="study_material">Study Material</option><option value="system">System Admin</option></select></div>
                    <div class="col-md-6"><label class="form-label fw-bold">Icon</label><select name="icon" class="form-select"><option value="bell">Bell</option><option value="alert-circle">Alert / Warning</option><option value="info">Info / Tip</option><option value="book-open">Book / Material</option></select></div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6"><label class="form-label fw-bold">Target Audience *</label><select name="target_type" id="target_type" class="form-select" onchange="toggleTargetId()"><option value="all">Broadcast to All Users</option><option value="role">Specific Role</option><option value="trade">Specific Trade</option><option value="user">Specific User ID</option></select></div>
                    <div class="col-md-6" id="target_id_wrapper" style="display:none;"><label class="form-label fw-bold">Target ID *</label><input type="number" name="target_id" id="target_id" class="form-control" placeholder="Enter ID..."></div>
                </div>

                <div class="mb-4"><label class="form-label fw-bold">Action URL (Optional)</label><input type="text" name="action_url" class="form-control" placeholder="/student/exams.php"></div>
                
                <button type="submit" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center"><i data-lucide="send" class="me-2" style="width:18px;"></i> Dispatch Immediately</button>
            </form>
        </div>
        
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Recent Broadcast History</h5>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top"><tr><th>Message Context</th><th>Audience</th><th>Delivered</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><div class="fw-bold text-dark" style="font-size:0.9rem;"><?= htmlspecialchars($h['title']) ?></div><div class="small text-muted text-truncate" style="max-width:200px;"><?= htmlspecialchars($h['message']) ?></div></td>
                                <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($h['target_type']) ?> <?= $h['target_id'] ? ': '.$h['target_id'] : '' ?></span></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success border border-success"><?= $h['delivery_count'] ?> Users</span></td>
                                <td class="small text-muted"><?= date('M d, g:i A', strtotime($h['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($history)): ?><tr><td colspan="4" class="text-center py-4 text-muted">No broadcasts sent yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTargetId() {
    const type = document.getElementById('target_type').value;
    const wrapper = document.getElementById('target_id_wrapper');
    wrapper.style.display = (type === 'all') ? 'none' : 'block';
    if (type === 'all') document.getElementById('target_id').value = '';
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>