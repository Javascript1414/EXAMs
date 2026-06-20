<?php
/**
 * Deployment Helper Dashboard
 * Quick access to deployment tools and status
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/safe_deployment.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin'])) {
    http_response_code(403);
    die('Access Denied');
}

$config = require '../config/maintenance.php';
$deployment = getSafeDeployment();
$backups = $deployment->listBackups(20);
$log_entries = $deployment->getLog(30);

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <h1><i class="fas fa-rocket"></i> Deployment Dashboard</h1>
    
    <!-- Quick Status -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card <?= $config['maintenance_mode'] ? 'border-danger' : 'border-success' ?>">
                <div class="card-body text-center">
                    <h3><?= $config['maintenance_mode'] ? '🔴' : '🟢' ?></h3>
                    <p class="mb-0"><strong><?= $config['maintenance_mode'] ? 'MAINTENANCE' : 'LIVE' ?></strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?= count($backups) ?></h3>
                    <p class="mb-0"><strong>Backups</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h3><?= count($log_entries) ?></h3>
                    <p class="mb-0"><strong>Recent Logs</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="maintenance_control.php" class="btn btn-primary me-2">
                        <i class="fas fa-toggle-on"></i> Toggle Maintenance
                    </a>
                    <a href="#" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#backupModal">
                        <i class="fas fa-download"></i> Manage Backups
                    </a>
                    <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#logsModal">
                        <i class="fas fa-file-alt"></i> View Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Checklist -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Pre-Deployment</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><input type="checkbox"> [ ] Enable maintenance mode</li>
                        <li><input type="checkbox"> [ ] Update user message</li>
                        <li><input type="checkbox"> [ ] Set estimated time</li>
                        <li><input type="checkbox"> [ ] Deploy new files</li>
                        <li><input type="checkbox"> [ ] Run migrations</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Post-Deployment</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li><input type="checkbox"> [ ] Test as admin</li>
                        <li><input type="checkbox"> [ ] Check error logs</li>
                        <li><input type="checkbox"> [ ] Verify database</li>
                        <li><input type="checkbox"> [ ] Disable maintenance</li>
                        <li><input type="checkbox"> [ ] Monitor for issues</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backups Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5>File Backups</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($backup['name']) ?></code></td>
                                <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                <td><?= date('M d H:i', $backup['date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5>Deployment Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre><code><?php foreach (array_reverse($log_entries) as $entry) echo htmlspecialchars($entry); ?></code></pre>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
