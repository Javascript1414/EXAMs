<?php
/**
 * Mail Function Test Script
 * Test email sending via PHPMailer and mail()
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/phpmailer_config.php';
require_once __DIR__ . '/includes/phpmailer_email_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'admin') {
    die("Access Denied! Admin only.");
}

$test_email = sanitizeInput($_POST['test_email'] ?? '');
$test_method = sanitizeInput($_POST['test_method'] ?? 'phpmailer');
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($test_email)) {
    if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $result = ['success' => false, 'message' => 'Invalid email address'];
    } else {
        if ($test_method === 'phpmailer') {
            $result = testPHPMailer($test_email);
        } else {
            $result = testMailFunction($test_email);
        }
    }
}

function testPHPMailer($email) {
    try {
        $mail = getMailer();
        
        if (!$mail) {
            return [
                'success' => false,
                'method' => 'PHPMailer',
                'message' => 'Mailer initialization failed'
            ];
        }
        
        // Test email
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Test Email from ' . APP_NAME . ' - PHPMailer';
        $mail->Body = '
            <div style="font-family: Arial; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h2 style="color: #667eea;">Email Test Successful! ✅</h2>
                <p>This is a test email sent via <strong>PHPMailer</strong> on ' . date('Y-m-d H:i:s') . '</p>
                <p style="color: #666; font-size: 12px;">If you received this, your SMTP configuration is working correctly.</p>
            </div>
        ';
        $mail->AltBody = 'Test email from ' . APP_NAME;
        
        if ($mail->send()) {
            return [
                'success' => true,
                'method' => 'PHPMailer',
                'message' => '✅ Email sent successfully via PHPMailer!',
                'host' => MAIL_HOST,
                'port' => MAIL_PORT,
                'encryption' => MAIL_ENCRYPTION,
                'recipient' => $email
            ];
        } else {
            return [
                'success' => false,
                'method' => 'PHPMailer',
                'message' => 'Failed: ' . $mail->ErrorInfo,
                'error' => $mail->ErrorInfo
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'method' => 'PHPMailer',
            'message' => 'Exception: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

function testMailFunction($email) {
    try {
        $subject = 'Test Email from ' . APP_NAME . ' - mail()';
        
        $body = '
            <html>
            <head>
                <style>
                    body { font-family: Arial; }
                    .container { padding: 20px; background: #f9f9f9; border-radius: 8px; }
                    h2 { color: #667eea; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2>Email Test Successful! ✅</h2>
                    <p>This is a test email sent via <strong>mail()</strong> function on ' . date('Y-m-d H:i:s') . '</p>
                    <p style="color: #666; font-size: 12px;">If you received this, your server mail configuration is working.</p>
                </div>
            </body>
            </html>
        ';
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . APP_NAME . " <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        
        if (mail($email, $subject, $body, $headers)) {
            return [
                'success' => true,
                'method' => 'mail()',
                'message' => '✅ Email sent successfully via mail() function!',
                'recipient' => $email,
                'note' => 'Check spam folder if not in inbox'
            ];
        } else {
            return [
                'success' => false,
                'method' => 'mail()',
                'message' => 'mail() function returned false. Check server configuration.',
                'error' => 'mail() failed'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'method' => 'mail()',
            'message' => 'Exception: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f9f9f9; padding: 40px 20px; }
        .test-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .test-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .test-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; color: #333; }
        .form-group input { border-radius: 6px; }
        .btn-test { width: 100%; padding: 12px; font-weight: 600; border-radius: 6px; }
        .result-box { margin-top: 20px; padding: 15px; border-radius: 8px; }
        .result-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .result-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .result-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .config-info { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; margin: 20px 0; border-radius: 4px; font-size: 13px; }
        .config-item { margin: 8px 0; }
        .config-item strong { color: #1565c0; }
        .btn-group { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-group button { flex: 1; }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h2>📧 Email Testing Console</h2>
            <p>Test your email configuration</p>
        </div>
        
        <div class="test-body">
            <div class="config-info">
                <strong>⚙️ Current Configuration:</strong>
                <div class="config-item">
                    <strong>SMTP Host:</strong> <?= MAIL_HOST ?>
                </div>
                <div class="config-item">
                    <strong>SMTP Port:</strong> <?= MAIL_PORT ?>
                </div>
                <div class="config-item">
                    <strong>Username:</strong> <?= substr(MAIL_USERNAME, 0, 5) . '****' . substr(MAIL_USERNAME, -8) ?>
                </div>
                <div class="config-item">
                    <strong>Encryption:</strong> <?= strtoupper(MAIL_ENCRYPTION) ?>
                </div>
                <div class="config-item">
                    <strong>SMTP Enabled:</strong> <?= MAIL_USE_SMTP ? '✅ Yes' : '❌ No' ?>
                </div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="test_email">Test Email Address *</label>
                    <input type="email" id="test_email" name="test_email" class="form-control" placeholder="your-email@example.com" required>
                    <small class="text-muted">Email to receive test message</small>
                </div>
                
                <div class="form-group">
                    <label for="test_method">Testing Method *</label>
                    <select id="test_method" name="test_method" class="form-control">
                        <option value="phpmailer">PHPMailer (SMTP)</option>
                        <option value="mail">mail() Function</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-test">
                    <i data-lucide="send" style="width: 18px; display: inline; margin-right: 8px;"></i>
                    Send Test Email
                </button>
            </form>
            
            <?php if ($result): ?>
            <div class="result-box <?= $result['success'] ? 'result-success' : 'result-error' ?>">
                <strong>
                    <?= $result['success'] ? '✅ Success' : '❌ Failed' ?>
                    (<?= $result['method'] ?? 'Unknown' ?>)
                </strong>
                <p style="margin-top: 10px;">
                    <?= $result['message'] ?>
                </p>
                
                <?php if (isset($result['recipient'])): ?>
                <small style="display: block; margin-top: 10px;">
                    <strong>Recipient:</strong> <?= htmlspecialchars($result['recipient']) ?>
                </small>
                <?php endif; ?>
                
                <?php if (isset($result['host'])): ?>
                <small style="display: block; margin-top: 5px;">
                    <strong>Server:</strong> <?= $result['host'] ?>:<?= $result['port'] ?> (<?= $result['encryption'] ?>)
                </small>
                <?php endif; ?>
                
                <?php if (isset($result['note'])): ?>
                <small style="display: block; margin-top: 10px; font-style: italic;">
                    📝 Note: <?= $result['note'] ?>
                </small>
                <?php endif; ?>
                
                <?php if (isset($result['error']) && !$result['success']): ?>
                <div style="margin-top: 15px; padding: 10px; background: rgba(0,0,0,0.1); border-radius: 4px; font-family: monospace; font-size: 12px;">
                    <strong>Error Details:</strong><br>
                    <?= htmlspecialchars($result['error']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="result-box result-info" style="margin-top: 20px;">
                <strong>💡 Tips:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li>Check spam folder for test emails</li>
                    <li>PHPMailer uses SMTP (more reliable)</li>
                    <li>mail() depends on server configuration</li>
                    <li>Gmail requires App Password (not regular password)</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
