<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_settings_functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get all settings
$notification_settings = getNotificationSettings($user_id);

// Ensure notification_settings is an array
if (!is_array($notification_settings)) {
    $notification_settings = [
        'exam_reminder' => 1,
        'result_notification' => 1,
        'system_notification' => 1,
        'email_notifications' => 1,
        'sms_notifications' => 0
    ];
}

$preferences = getStudentPreferences($user_id);

// Ensure preferences is an array
if (!is_array($preferences)) {
    $preferences = [
        'theme' => 'light',
        'dashboard_view' => 'grid',
        'language' => 'en',
        'timezone' => 'Asia/Kolkata',
        'items_per_page' => 10
    ];
}

$deletion_request = getDeletionRequestStatus($user_id);
$export_requests = getDataExportRequests($user_id);

// Get current active tab
$current_tab = $_GET['tab'] ?? 'notifications';

include __DIR__ . '/../includes/header.php';
?>

<style>
    * {
        box-sizing: border-box;
    }

    .settings-container {
        max-width: 1000px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .settings-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .settings-header h1 {
        margin: 0;
        font-size: 2em;
    }

    .settings-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
    }

    .settings-nav {
        display: flex;
        border-bottom: 2px solid #f0f0f0;
        background: #fafafa;
        overflow-x: auto;
    }

    .settings-nav button {
        flex: 1;
        padding: 15px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        white-space: nowrap;
    }

    .settings-nav button:hover {
        color: #667eea;
        background: white;
    }

    .settings-nav button.active {
        color: #667eea;
        border-bottom-color: #667eea;
        background: white;
    }

    .settings-content {
        padding: 30px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .setting-group {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #f0f0f0;
    }

    .setting-group:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .setting-group h3 {
        color: #333;
        margin: 0 0 15px 0;
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 12px;
    }

    .setting-item-label {
        flex: 1;
    }

    .setting-item-label .label-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .setting-item-label .label-desc {
        font-size: 13px;
        color: #666;
    }

    .toggle-switch {
        position: relative;
        width: 50px;
        height: 28px;
        background: #ddd;
        border-radius: 14px;
        cursor: pointer;
        transition: background 0.3s;
        border: none;
        padding: 0;
    }

    .toggle-switch.active {
        background: #667eea;
    }

    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: left 0.3s;
    }

    .toggle-switch.active::after {
        left: 24px;
    }

    .select-control {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .table thead {
        background: #f8f9fa;
    }

    .table th, .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .table th {
        font-weight: 600;
        color: #333;
    }

    .table td {
        font-size: 14px;
        color: #666;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #764ba2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-danger {
        background: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: inherit;
        font-size: 14px;
        resize: vertical;
        min-height: 100px;
    }

    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .current-session {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .current-session h4 {
        margin: 0 0 15px 0;
    }

    .session-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .session-detail {
        background: rgba(255,255,255,0.1);
        padding: 12px;
        border-radius: 6px;
    }

    .session-detail-label {
        font-size: 12px;
        opacity: 0.8;
        margin-bottom: 5px;
    }

    .session-detail-value {
        font-weight: 600;
        font-size: 14px;
    }

    .no-data {
        text-align: center;
        padding: 30px;
        color: #999;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 5px;
        margin-top: 20px;
    }

    .pagination a, .pagination span {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        color: #667eea;
    }

    .pagination a:hover {
        background: #f0f0f0;
    }

    .pagination .active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .settings-nav {
            flex-wrap: wrap;
        }

        .settings-nav button {
            flex: 0 1 auto;
        }

        .session-details {
            grid-template-columns: 1fr;
        }

        .settings-content {
            padding: 20px;
        }
    }
</style>

<div class="settings-container">
    <!-- Header -->
    <div class="settings-header">
        <h1>⚙️ Settings</h1>
        <p>Manage your account, preferences, and privacy settings</p>
    </div>

    <!-- Tab Navigation -->
    <div class="settings-nav">
        <button class="settings-tab-btn <?= $current_tab == 'notifications' ? 'active' : '' ?>" onclick="switchTab('notifications')">
            🔔 Notifications
        </button>
        <button class="settings-tab-btn <?= $current_tab == 'security' ? 'active' : '' ?>" onclick="switchTab('security')">
            🔐 Security
        </button>
        <button class="settings-tab-btn <?= $current_tab == 'preferences' ? 'active' : '' ?>" onclick="switchTab('preferences')">
            🎨 Preferences
        </button>
        <button class="settings-tab-btn <?= $current_tab == 'activity' ? 'active' : '' ?>" onclick="switchTab('activity')">
            📊 Activity
        </button>
        <button class="settings-tab-btn <?= $current_tab == 'privacy' ? 'active' : '' ?>" onclick="switchTab('privacy')">
            🔒 Privacy
        </button>
    </div>

    <!-- Content Area -->
    <div class="settings-content">
        <!-- Notifications Tab -->
        <div id="notifications-tab" class="tab-content <?= $current_tab == 'notifications' ? 'active' : '' ?>">
            <h2>🔔 Notification Settings</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="setting-group">
                <h3>📨 Email Notifications</h3>
                
                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">Exam Reminders</div>
                        <div class="label-desc">Get notified about upcoming exams</div>
                    </div>
                    <button class="toggle-switch <?= $notification_settings['exam_reminder'] ? 'active' : '' ?>" 
                            onclick="updateNotificationSetting('exam_reminder', this)"></button>
                </div>

                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">Result Notifications</div>
                        <div class="label-desc">Get notified when exam results are published</div>
                    </div>
                    <button class="toggle-switch <?= $notification_settings['result_notification'] ? 'active' : '' ?>" 
                            onclick="updateNotificationSetting('result_notification', this)"></button>
                </div>

                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">System Notifications</div>
                        <div class="label-desc">Important system announcements and updates</div>
                    </div>
                    <button class="toggle-switch <?= $notification_settings['system_notification'] ? 'active' : '' ?>" 
                            onclick="updateNotificationSetting('system_notification', this)"></button>
                </div>

                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">Email Notifications</div>
                        <div class="label-desc">All notifications via email</div>
                    </div>
                    <button class="toggle-switch <?= $notification_settings['email_notifications'] ? 'active' : '' ?>" 
                            onclick="updateNotificationSetting('email_notifications', this)"></button>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div id="security-tab" class="tab-content <?= $current_tab == 'security' ? 'active' : '' ?>">
            <h2>🔐 Login & Security</h2>

            <?php 
            $current_session = getCurrentSession($user_id);
            if ($current_session): 
            ?>
                <div class="current-session">
                    <h4>✅ Current Session (Active)</h4>
                    <div class="session-details">
                        <div class="session-detail">
                            <div class="session-detail-label">Login Time</div>
                            <div class="session-detail-value"><?= date('M d, Y H:i', strtotime($current_session['login_time'])) ?></div>
                        </div>
                        <div class="session-detail">
                            <div class="session-detail-label">Device</div>
                            <div class="session-detail-value"><?= htmlspecialchars($current_session['device'] ?? 'Unknown') ?></div>
                        </div>
                        <div class="session-detail">
                            <div class="session-detail-label">Browser</div>
                            <div class="session-detail-value"><?= htmlspecialchars($current_session['browser'] ?? 'Unknown') ?></div>
                        </div>
                        <div class="session-detail">
                            <div class="session-detail-label">IP Address</div>
                            <div class="session-detail-value"><?= htmlspecialchars($current_session['ip_address']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="setting-group">
                <h3>🔑 Password Management</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    Manage your password and account security.
                </p>
                <a href="<?= BASE_URL ?>/student/change_password.php" class="btn btn-primary">
                    🔄 Change Password
                </a>
            </div>

            <div class="setting-group">
                <h3>📱 Login History</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    View all devices and locations where you're logged in.
                </p>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Login Time</th>
                            <th>Device</th>
                            <th>Browser</th>
                            <th>IP Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $login_history = getLoginHistoryPaginated($user_id, 1, 10);
                        if (!empty($login_history['data'])):
                            foreach ($login_history['data'] as $entry):
                        ?>
                            <tr>
                                <td><?= date('M d, Y H:i', strtotime($entry['login_time'])) ?></td>
                                <td><?= htmlspecialchars($entry['device'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($entry['browser'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($entry['ip_address']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $entry['status'] == 'success' ? 'success' : ($entry['status'] == 'failed' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($entry['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach;
                        else:
                        ?>
                            <tr>
                                <td colspan="5" class="no-data">No login history</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Preferences Tab -->
        <div id="preferences-tab" class="tab-content <?= $current_tab == 'preferences' ? 'active' : '' ?>">
            <h2>🎨 Display Preferences</h2>

            <div class="setting-group">
                <h3>🎨 Theme</h3>
                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">Dark Mode</div>
                        <div class="label-desc">Use dark theme for the interface</div>
                    </div>
                    <select class="select-control" onchange="updatePreference('theme', this.value)">
                        <option value="light" <?= $preferences['theme'] == 'light' ? 'selected' : '' ?>>Light Mode</option>
                        <option value="dark" <?= $preferences['theme'] == 'dark' ? 'selected' : '' ?>>Dark Mode</option>
                        <option value="auto" <?= $preferences['theme'] == 'auto' ? 'selected' : '' ?>>Auto (System)</option>
                    </select>
                </div>
            </div>

            <div class="setting-group">
                <h3>📊 Dashboard Layout</h3>
                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">View Style</div>
                        <div class="label-desc">Choose how content is displayed</div>
                    </div>
                    <select class="select-control" onchange="updatePreference('dashboard_view', this.value)">
                        <option value="grid" <?= $preferences['dashboard_view'] == 'grid' ? 'selected' : '' ?>>Grid View</option>
                        <option value="list" <?= $preferences['dashboard_view'] == 'list' ? 'selected' : '' ?>>List View</option>
                        <option value="compact" <?= $preferences['dashboard_view'] == 'compact' ? 'selected' : '' ?>>Compact View</option>
                    </select>
                </div>
            </div>

            <div class="setting-group">
                <h3>🌍 Localization</h3>
                <div class="setting-item">
                    <div class="setting-item-label">
                        <div class="label-title">Language</div>
                        <div class="label-desc">Choose your preferred language</div>
                    </div>
                    <select class="select-control" onchange="updatePreference('language', this.value)">
                        <option value="en" <?= $preferences['language'] == 'en' ? 'selected' : '' ?>>English</option>
                        <option value="hi" <?= $preferences['language'] == 'hi' ? 'selected' : '' ?>>Hindi</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Activity Tab -->
        <div id="activity-tab" class="tab-content <?= $current_tab == 'activity' ? 'active' : '' ?>">
            <h2>📊 Activity Log</h2>

            <div class="setting-group">
                <p style="color: #666; margin-bottom: 15px;">
                    View all your recent activities including exams, downloads, and profile updates.
                </p>
                <?php
                $activity = getActivityHistoryPaginated($user_id, 1, 10);
                if (!empty($activity['data'])):
                ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Activity</th>
                                <th>Description</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity['data'] as $entry): ?>
                                <tr>
                                    <td><strong><?= ucwords(str_replace('_', ' ', $entry['activity_type'])) ?></strong></td>
                                    <td><?= htmlspecialchars($entry['description'] ?? '') ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($entry['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php
                else:
                ?>
                    <div class="no-data">📭 No activity yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Privacy Tab -->
        <div id="privacy-tab" class="tab-content <?= $current_tab == 'privacy' ? 'active' : '' ?>">
            <h2>🔒 Privacy & Data</h2>

            <div class="setting-group">
                <h3>📥 Download My Data</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    Download all your personal data in a format you can save and use elsewhere.
                </p>
                <button class="btn btn-primary" onclick="requestDataExport('full')">
                    📥 Download My Data (ZIP)
                </button>

                <?php if (!empty($export_requests)): ?>
                    <div style="margin-top: 20px;">
                        <h4>Recent Export Requests</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($export_requests as $req): ?>
                                    <tr>
                                        <td><?= ucfirst($req['export_type']) ?></td>
                                        <td><span class="badge badge-<?= $req['status'] == 'completed' ? 'success' : ($req['status'] == 'failed' ? 'danger' : 'warning') ?>"><?= ucfirst($req['status']) ?></span></td>
                                        <td><?= date('M d, Y', strtotime($req['requested_at'])) ?></td>
                                        <td>
                                            <?php if ($req['status'] == 'completed'): ?>
                                                <a href="<?= BASE_URL ?>/student/settings_ajax.php?action=download_export&id=<?= $req['id'] ?>" class="btn btn-sm btn-primary">Download</a>
                                            <?php elseif ($req['status'] == 'pending' || $req['status'] == 'processing'): ?>
                                                <span style="color: #999;">Processing...</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="setting-group">
                <h3>❌ Account Deletion</h3>
                <p style="color: #666; margin-bottom: 15px;">
                    <strong>⚠️ Warning:</strong> This action is permanent and cannot be undone. All your data will be deleted.
                </p>

                <?php if ($deletion_request && $deletion_request['status'] != 'completed'): ?>
                    <div class="alert alert-info">
                        📋 <strong>Deletion Request Status:</strong> <?= ucfirst($deletion_request['status']) ?><br>
                        <small>Requested on: <?= date('M d, Y H:i', strtotime($deletion_request['requested_at'])) ?></small>
                        <?php if ($deletion_request['status'] == 'pending'): ?>
                            <br><button class="btn btn-sm btn-secondary" onclick="cancelDeletion()" style="margin-top: 10px;">Cancel Request</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <button class="btn btn-danger" onclick="showDeletionForm()">
                        🗑️ Request Account Deletion
                    </button>

                    <div id="deletion-form" style="display:none; margin-top: 20px; padding: 20px; background: #fff3cd; border-radius: 8px;">
                        <h4>Confirm Account Deletion</h4>
                        <p style="color: #666; margin-bottom: 15px;">
                            Please tell us why you want to delete your account. This helps us improve our service.
                        </p>
                        <form onsubmit="submitDeletion(event)">
                            <div class="form-group">
                                <label>Reason for deletion *</label>
                                <textarea id="deletion_reason" name="reason" required placeholder="Please describe your reason..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Additional feedback (optional)</label>
                                <textarea id="deletion_feedback" name="feedback" placeholder="Any additional comments..."></textarea>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-danger">🗑️ Confirm Deletion</button>
                                <button type="button" class="btn btn-secondary" onclick="hideDeletionForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(t => t.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tab + '-tab').classList.add('active');
    
    // Update active button
    const buttons = document.querySelectorAll('.settings-tab-btn');
    buttons.forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update URL
    window.history.replaceState({}, '', '?tab=' + tab);
}

function updateNotificationSetting(field, element) {
    const isActive = element.classList.contains('active');
    element.classList.toggle('active');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= BASE_URL ?>/student/settings_ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showNotification('Setting updated!', 'success');
            } else {
                element.classList.toggle('active');
                showNotification(response.message || 'Error updating setting', 'error');
            }
        }
    };
    
    xhr.send('action=update_notification&' + field + '=' + (!isActive ? '1' : '0'));
}

function updatePreference(field, value) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= BASE_URL ?>/student/settings_ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showNotification('Preference updated!', 'success');
                
                // Update user preferences globally with proper field mapping
                if (window.userPreferences) {
                    if (field === 'dashboard_view') {
                        window.userPreferences.dashboardView = value;
                    } else {
                        window.userPreferences[field] = value;
                    }
                }
                
                // Dispatch custom event for theme application
                const eventField = field === 'dashboard_view' ? 'dashboardView' : field;
                const event = new CustomEvent('preferenceUpdated', {
                    detail: { field: eventField, value: value }
                });
                document.dispatchEvent(event);
            } else {
                showNotification(response.message || 'Error updating preference', 'error');
            }
        }
    };
    
    xhr.send('action=update_preference&' + field + '=' + encodeURIComponent(value));
}

function requestDataExport(type) {
    if (confirm('Request data export? You will receive an email when ready.')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= BASE_URL ?>/student/settings_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                showNotification(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            }
        };
        
        xhr.send('action=request_export&type=' + type);
    }
}

function showDeletionForm() {
    document.getElementById('deletion-form').style.display = 'block';
    document.getElementById('deletion_reason').focus();
}

function hideDeletionForm() {
    document.getElementById('deletion-form').style.display = 'none';
}

function submitDeletion(event) {
    event.preventDefault();
    
    if (!confirm('⚠️ This action is permanent and cannot be undone. Are you absolutely sure?')) {
        return;
    }
    
    const reason = document.getElementById('deletion_reason').value;
    const feedback = document.getElementById('deletion_feedback').value;
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '<?= BASE_URL ?>/student/settings_ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            showNotification(response.message, response.success ? 'success' : 'error');
            if (response.success) {
                setTimeout(() => location.reload(), 1500);
            }
        }
    };
    
    xhr.send('action=request_deletion&reason=' + encodeURIComponent(reason) + '&feedback=' + encodeURIComponent(feedback));
}

function cancelDeletion() {
    if (confirm('Cancel deletion request?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?= BASE_URL ?>/student/settings_ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                showNotification(response.message, response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            }
        };
        
        xhr.send('action=cancel_deletion');
    }
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass}`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '10000';
    alert.style.minWidth = '300px';
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
