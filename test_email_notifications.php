<?php
/**
 * Email Notification System - Testing & Verification Script
 * 
 * This script tests the email notification system without affecting production data
 * Run this file in browser: http://localhost/EXAMs/test_email_notifications.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/notification_emails.php';

// Only allow for testing environment or logged-in admins
$is_admin = isset($_SESSION['user_id']) && (hasRole('admin') || hasRole('superadmin'));
$is_dev = ENVIRONMENT === 'development';

if (!$is_admin && !$is_dev) {
    die('<h2>❌ Access Denied</h2><p>Only admins or in development mode</p>');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notification System - Test & Verification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .section {
            margin-bottom: 40px;
            border-bottom: 1px solid #eee;
            padding-bottom: 30px;
        }
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        .test-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .test-box h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .test-box p {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .result {
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            font-size: 13px;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .result.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }
        .status-online {
            background: #28a745;
        }
        .status-offline {
            background: #dc3545;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 4px;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-card .label {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12px;
        }
        table th {
            background: #f9f9f9;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        table tr:hover {
            background: #f9f9f9;
        }
        .code {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
            margin-top: 15px;
        }
        .label-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 5px;
        }
        .badge-sent {
            background: #d4edda;
            color: #155724;
        }
        .badge-failed {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Notification System</h1>
            <p>Testing & Verification Panel</p>
        </div>

        <div class="content">
            <!-- System Status Section -->
            <div class="section">
                <h2>🔍 System Status</h2>
                
                <div class="test-box">
                    <h3>Configuration Check</h3>
                    <?php
                    // Check PHPMailer config
                    $checks = [
                        'PHPMailer Library' => file_exists(__DIR__ . '/vendor/autoload.php'),
                        'Config File' => file_exists(__DIR__ . '/includes/phpmailer_config.php'),
                        'Notification Emails Helper' => file_exists(__DIR__ . '/includes/notification_emails.php'),
                        'API Endpoint' => file_exists(__DIR__ . '/api/email_notifications.php'),
                        'Database Connection' => isset($pdo),
                    ];

                    foreach ($checks as $name => $status):
                    ?>
                        <p>
                            <span class="status-indicator <?= $status ? 'status-online' : 'status-offline' ?>"></span>
                            <strong><?= $name ?>:</strong>
                            <span style="color: <?= $status ? '#28a745' : '#dc3545' ?>">
                                <?= $status ? '✅ OK' : '❌ MISSING' ?>
                            </span>
                        </p>
                    <?php endforeach; ?>
                </div>

                <!-- Email Statistics -->
                <div class="test-box">
                    <h3>📊 Email Statistics</h3>
                    <?php
                    try {
                        // Ensure table exists
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS email_notifications (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                user_id INT,
                                email VARCHAR(255) NOT NULL,
                                notification_type ENUM('registration', 'approval', 'rejection', 'reset_password', 'otp') NOT NULL,
                                status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
                                error_message LONGTEXT,
                                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                retry_count INT DEFAULT 0,
                                INDEX idx_user_id (user_id),
                                INDEX idx_email (email),
                                INDEX idx_type (notification_type),
                                INDEX idx_status (status),
                                INDEX idx_sent_at (sent_at)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                        ");

                        // Get stats
                        $totalSent = $pdo->query("SELECT COUNT(*) as count FROM email_notifications WHERE status = 'sent'")->fetch()['count'] ?? 0;
                        $totalFailed = $pdo->query("SELECT COUNT(*) as count FROM email_notifications WHERE status = 'failed'")->fetch()['count'] ?? 0;
                        $totalPending = $pdo->query("SELECT COUNT(*) as count FROM email_notifications WHERE status = 'pending'")->fetch()['count'] ?? 0;
                        
                        // By type
                        $byType = $pdo->query("
                            SELECT notification_type, COUNT(*) as count 
                            FROM email_notifications 
                            GROUP BY notification_type
                        ")->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="value" style="color: #28a745;"><?= $totalSent ?></div>
                                <div class="label">Emails Sent</div>
                            </div>
                            <div class="stat-card">
                                <div class="value" style="color: #dc3545;"><?= $totalFailed ?></div>
                                <div class="label">Failed</div>
                            </div>
                            <div class="stat-card">
                                <div class="value" style="color: #ffc107;"><?= $totalPending ?></div>
                                <div class="label">Pending</div>
                            </div>
                        </div>

                        <h4 style="margin-top: 20px; color: #333; font-size: 14px;">By Type:</h4>
                        <table>
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byType as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['notification_type']) ?></td>
                                    <td><?= $row['count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php
                    } catch (Exception $e) {
                    ?>
                        <div class="result error">
                            ❌ Database Error: <?= htmlspecialchars($e->getMessage()) ?>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Manual Test Section -->
            <div class="section">
                <h2>🧪 Manual Email Tests</h2>

                <div class="test-box">
                    <h3>Test 1: Send Registration Email</h3>
                    <p>Send a test registration notification email with credentials</p>

                    <form method="POST" style="margin-top: 15px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="test_email" value="test@example.com" required>
                            </div>
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="test_name" value="Test Student" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>User ID *</label>
                                <input type="number" name="test_user_id" value="9999" required>
                            </div>
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="text" name="test_password" value="TestPassword123" required>
                            </div>
                        </div>
                        <button type="submit" name="test_action" value="registration" class="btn">📧 Send Registration Email</button>
                    </form>

                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['test_action'] === 'registration') {
                        $email = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
                        $name = sanitizeInput($_POST['test_name']);
                        $user_id = (int)$_POST['test_user_id'];
                        $password = sanitizeInput($_POST['test_password']);

                        $result = sendRegistrationNotificationEmail($email, $name, $user_id, $password);

                        echo '<div class="result ' . ($result ? 'success' : 'error') . '">';
                        echo ($result ? '✅ EMAIL SENT SUCCESSFULLY!' : '❌ FAILED TO SEND EMAIL');
                        echo '<br><small>Check the email inbox or spam folder. Email logged to database.</small>';
                        echo '</div>';
                    }
                    ?>
                </div>

                <div class="test-box">
                    <h3>Test 2: Send Approval Email</h3>
                    <p>Send a test account approval notification email</p>

                    <form method="POST" style="margin-top: 15px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="test_email_approval" value="test@example.com" required>
                            </div>
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="test_name_approval" value="Test Student" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>User ID *</label>
                            <input type="number" name="test_user_id_approval" value="9999" required>
                        </div>
                        <button type="submit" name="test_action" value="approval" class="btn">📧 Send Approval Email</button>
                    </form>

                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['test_action'] === 'approval') {
                        $email = filter_var($_POST['test_email_approval'], FILTER_SANITIZE_EMAIL);
                        $name = sanitizeInput($_POST['test_name_approval']);
                        $user_id = (int)$_POST['test_user_id_approval'];

                        $result = sendApprovalNotificationEmail($email, $name, $user_id);

                        echo '<div class="result ' . ($result ? 'success' : 'error') . '">';
                        echo ($result ? '✅ EMAIL SENT SUCCESSFULLY!' : '❌ FAILED TO SEND EMAIL');
                        echo '<br><small>Check the email inbox or spam folder. Email logged to database.</small>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Recent Emails Section -->
            <div class="section">
                <h2>📬 Recent Email Notifications</h2>

                <?php
                try {
                    $stmt = $pdo->prepare("
                        SELECT * FROM email_notifications 
                        ORDER BY sent_at DESC 
                        LIMIT 20
                    ");
                    $stmt->execute();
                    $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($emails)):
                    ?>
                        <div class="result info">
                            ℹ️ No email notifications yet. Send a test email above.
                        </div>
                    <?php
                    else:
                    ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emails as $email): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($email['email'], 0, 30)) ?></td>
                                    <td><?= htmlspecialchars($email['notification_type']) ?></td>
                                    <td>
                                        <span class="label-badge <?= $email['status'] === 'sent' ? 'badge-sent' : 'badge-failed' ?>">
                                            <?= htmlspecialchars($email['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($email['sent_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php
                    endif;
                } catch (Exception $e) {
                ?>
                    <div class="result error">
                        ❌ Error: <?= htmlspecialchars($e->getMessage()) ?>
                    </div>
                <?php
                }
                ?>
            </div>

            <!-- Documentation Section -->
            <div class="section">
                <h2>📖 Documentation</h2>

                <div class="test-box">
                    <h3>System Files</h3>
                    <ul style="margin-left: 20px;">
                        <li><strong>/includes/notification_emails.php</strong> - Core email functions</li>
                        <li><strong>/includes/phpmailer_config.php</strong> - SMTP configuration</li>
                        <li><strong>/api/email_notifications.php</strong> - Email management API</li>
                        <li><strong>/verify_otp.php</strong> - Registration email trigger</li>
                        <li><strong>/admin/users.php</strong> - Approval email trigger</li>
                        <li><strong>EMAIL_NOTIFICATION_SYSTEM.md</strong> - Full documentation</li>
                    </ul>
                </div>

                <div class="test-box">
                    <h3>API Endpoints</h3>
                    <div class="code">
GET  /api/email_notifications.php?action=get_logs
GET  /api/email_notifications.php?action=get_stats
POST /api/email_notifications.php (action=resend_approval&user_id=123)
                    </div>
                </div>

                <div class="test-box">
                    <h3>Database Query</h3>
                    <div class="code">
SELECT * FROM email_notifications ORDER BY sent_at DESC LIMIT 20;
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
