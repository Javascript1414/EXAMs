<?php
/**
 * Admin Maintenance Mode Control Panel
 * Allows admins to toggle maintenance mode and manage deployments
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin'])) {
    http_response_code(403);
    die('Access Denied - Admins Only');
}

$maintenance_config_file = '../config/maintenance.php';
$config = require $maintenance_config_file;

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Security token expired';
        $message_type = 'danger';
    } else {
        $action = sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'toggle_maintenance') {
            // Toggle maintenance mode
            $new_status = $_POST['maintenance_mode'] === 'on' ? 'true' : 'false';
            $message_text = $_POST['maintenance_message'] ?? 'System Maintenance in Progress';
            $estimated_time = $_POST['estimated_time'] ?? '5-10 minutes';
            $details = $_POST['details'] ?? 'We are updating with new features.';
            
            // Create new config
            $new_config = <<<PHP
<?php
return [
    'maintenance_mode' => $new_status,
    'maintenance_message' => '$message_text',
    'maintenance_details' => '$details',
    'maintenance_estimated_time' => '$estimated_time',
    'show_countdown' => true,
    'show_admin_panel' => true,
    'allowed_ips' => [
        '127.0.0.1',
        'localhost',
    ],
    'last_maintenance' => ''' . date('Y-m-d H:i:s') . ''',
    'next_scheduled_maintenance' => null,
];
?>
PHP;
            
            // Backup current config
            $backup_dir = '../backups/config_backups';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $backup_file = $backup_dir . '/maintenance_' . date('Y-m-d_H-i-s') . '.php';
            copy($maintenance_config_file, $backup_file);
            
            // Update config
            if (file_put_contents($maintenance_config_file, $new_config)) {
                // Log the action
                error_log("Maintenance mode " . ($new_status === 'true' ? 'ENABLED' : 'DISABLED') . " by " . $_SESSION['full_name'] . " at " . date('Y-m-d H:i:s'));
                
                $message = 'Maintenance mode ' . ($new_status === 'true' ? 'ENABLED' : 'DISABLED') . ' successfully!';
                $message_type = 'success';
                
                // Reload config
                $config = require $maintenance_config_file;
            } else {
                $message = 'Error updating configuration file';
                $message_type = 'danger';
            }
        } elseif ($action === 'clear_backups') {
            // Clear old backup files
            $backup_dir = '../backups/config_backups';
            if (is_dir($backup_dir)) {
                $files = array_diff(scandir($backup_dir), ['.', '..']);
                $deleted = 0;
                foreach ($files as $file) {
                    if (unlink($backup_dir . '/' . $file)) {
                        $deleted++;
                    }
                }
                $message = "Cleared $deleted backup files";
                $message_type = 'success';
            }
        }
    }
}

// Get backup files
$backup_dir = '../backups/config_backups';
$backups = [];
if (is_dir($backup_dir)) {
    $files = array_diff(scandir($backup_dir), ['.', '..']);
    rsort($files);
    foreach ($files as $file) {
        $backups[] = [
            'name' => $file,
            'time' => filemtime($backup_dir . '/' . $file),
            'size' => filesize($backup_dir . '/' . $file)
        ];
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1><i class="fas fa-wrench"></i> Maintenance Mode Control</h1>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($message_type) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Current Status -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-circle"></i> Current Status</h5>
                </div>
                <div class="card-body">
                    <div class="status-box <?= $config['maintenance_mode'] ? 'bg-danger' : 'bg-success' ?> text-white p-4 rounded text-center mb-3">
                        <h2 class="mb-2">
                            <?= $config['maintenance_mode'] ? '🔴 MAINTENANCE' : '🟢 LIVE' ?>
                        </h2>
                        <p class="mb-0"><?= $config['maintenance_mode'] ? 'System is in maintenance mode' : 'System is live and accepting users' ?></p>
                    </div>
                    
                    <div class="info-box">
                        <p><strong>Message:</strong> <?= htmlspecialchars($config['maintenance_message']) ?></p>
                        <p><strong>Details:</strong> <?= htmlspecialchars($config['maintenance_details']) ?></p>
                        <p><strong>Est. Time:</strong> <?= htmlspecialchars($config['maintenance_estimated_time']) ?></p>
                        <?php if ($config['last_maintenance']): ?>
                            <p><strong>Last Update:</strong> <?= date('M d, Y H:i:s', strtotime($config['last_maintenance'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toggle Control -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-toggle-on"></i> Toggle Maintenance</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="toggle_maintenance">
                        
                        <div class="mb-3">
                            <label class="form-label">Enable Maintenance Mode</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceToggle" value="on" <?= $config['maintenance_mode'] ? 'checked' : '' ?> style="width: 50px; height: 25px;">
                                <label class="form-check-label" for="maintenanceToggle">
                                    <?= $config['maintenance_mode'] ? 'ON (Maintenance Active)' : 'OFF (Live Mode)' ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maintenance Title</label>
                            <input type="text" name="maintenance_message" class="form-control" value="<?= htmlspecialchars($config['maintenance_message']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message to Users</label>
                            <textarea name="details" class="form-control" rows="3" required><?= htmlspecialchars($config['maintenance_details']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Estimated Time</label>
                            <input type="text" name="estimated_time" class="form-control" value="<?= htmlspecialchars($config['maintenance_estimated_time']) ?>" placeholder="e.g., 5-10 minutes">
                        </div>
                        
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-save"></i> Update Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Workflow -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-rocket"></i> Deployment Workflow</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">🔄 Safe Update Process:</h6>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item">Click <strong>Toggle</strong> → Enable Maintenance</li>
                                <li class="list-group-item">Update message & estimated time</li>
                                <li class="list-group-item">Click <strong>Update Settings</strong></li>
                                <li class="list-group-item">Users see maintenance page</li>
                                <li class="list-group-item">Deploy new files/features</li>
                                <li class="list-group-item">Test everything thoroughly</li>
                                <li class="list-group-item">Disable maintenance mode</li>
                                <li class="list-group-item">Users can access again ✓</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">✨ Benefits:</h6>
                            <ul class="list-group">
                                <li class="list-group-item">✅ Users know system is updating</li>
                                <li class="list-group-item">✅ Shows estimated time</li>
                                <li class="list-group-item">✅ Prevents login errors</li>
                                <li class="list-group-item">✅ Professional appearance</li>
                                <li class="list-group-item">✅ No lost data</li>
                                <li class="list-group-item">✅ Easy rollback if needed</li>
                                <li class="list-group-item">✅ Admins can test before opening</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Files -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Configuration Backups (<?= count($backups) ?>)</h5>
                        <?php if (count($backups) > 0): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="action" value="clear_backups">
                                <button type="submit" class="btn btn-sm btn-light" onclick="return confirm('Clear all backups?');">
                                    <i class="fas fa-trash"></i> Clear All
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($backups)): ?>
                        <p class="text-muted">No backup files yet</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Filename</th>
                                        <th>Date</th>
                                        <th>Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($backup['name']) ?></code></td>
                                            <td><?= date('M d, Y H:i:s', $backup['time']) ?></td>
                                            <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .status-box {
        border-radius: 15px;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.8; }
    }
    
    .info-box {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }
    
    .info-box p {
        margin: 8px 0;
        font-size: 14px;
    }
    
    .list-group-item {
        border-left: 4px solid #667eea;
        padding-left: 15px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
