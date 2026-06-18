<?php
/**
 * Test Teacher Email Sending
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/phpmailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Teacher Email</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test { margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; }
        .success { border-left-color: #4caf50; background: #e8f5e9; }
        .error { border-left-color: #f44336; background: #ffebee; }
        .info { border-left-color: #2196f3; background: #e3f2fd; }
        code { background: #eee; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>

<div class="container">
    <h1>🧪 Teacher Email Test</h1>
    
    <?php
    
    echo "<div class='test info'>";
    echo "<h3>1️⃣ Configuration Check</h3>";
    echo "<p><strong>SMTP Host:</strong> <code>" . MAIL_HOST . "</code></p>";
    echo "<p><strong>SMTP Port:</strong> <code>" . MAIL_PORT . "</code></p>";
    echo "<p><strong>From Email:</strong> <code>" . MAIL_FROM_EMAIL . "</code></p>";
    echo "<p><strong>From Name:</strong> <code>" . MAIL_FROM_NAME . "</code></p>";
    echo "<p><strong>Use SMTP:</strong> <code>" . (MAIL_USE_SMTP ? 'YES' : 'NO') . "</code></p>";
    echo "</div>";
    
    // Test 2: Initialize mailer
    echo "<div class='test info'>";
    echo "<h3>2️⃣ Mailer Initialization</h3>";
    
    $mail = getMailer();
    if (!$mail) {
        echo "<div class='test error'>";
        echo "<p><strong>❌ Failed to initialize mailer!</strong></p>";
        echo "</div>";
    } else {
        echo "<p style='color: green;'><strong>✅ Mailer initialized successfully</strong></p>";
    }
    echo "</div>";
    
    // Test 3: Test sending to a real teacher
    echo "<div class='test info'>";
    echo "<h3>3️⃣ Sending Test Email</h3>";
    
    try {
        // Get a teacher from database
        $teacher_query = "SELECT id, email, full_name FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'teacher') LIMIT 1";
        $teacher_result = $pdo->query($teacher_query)->fetch(PDO::FETCH_ASSOC);
        
        if (!$teacher_result) {
            echo "<p style='color: orange;'><strong>⚠️ No teachers found in database. Creating test email...</strong></p>";
            $teacher_result = [
                'email' => 'test@example.com',
                'full_name' => 'Test Teacher'
            ];
        }
        
        echo "<p><strong>Recipient:</strong> {$teacher_result['full_name']} &lt;{$teacher_result['email']}&gt;</p>";
        
        if ($mail) {
            $mail->addAddress($teacher_result['email'], $teacher_result['full_name']);
            $mail->Subject = 'Welcome to ' . APP_NAME . ' - Teacher Account Created';
            
            $mail->isHTML(true);
            $mail->Body = "
            <h2>Welcome to " . APP_NAME . "!</h2>
            <p>Dear {$teacher_result['full_name']},</p>
            <p>Your teacher account has been successfully created by the administrator.</p>
            
            <h3>🔐 Login Credentials:</h3>
            <div style='background: #f0f4ff; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; margin: 15px 0;'>
                <p><strong>Email:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>{$teacher_result['email']}</code></p>
                <p><strong>Password:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>test_password_123</code></p>
            </div>
            
            <h3>📍 Login URL:</h3>
            <p><a href='" . BASE_URL . "/staff_login.php' style='color: #667eea; text-decoration: none;'>" . BASE_URL . "/staff_login.php</a></p>
            
            <p>Best regards,<br>" . APP_NAME . " Admin Team</p>
            ";
            
            $mail->AltBody = "Welcome to " . APP_NAME . "!";
            
            // Try to send
            if ($mail->send()) {
                echo "<div class='test success'>";
                echo "<p><strong>✅ Email sent successfully!</strong></p>";
                echo "<p>The welcome email should arrive in the teacher's inbox shortly.</p>";
                echo "</div>";
            } else {
                echo "<div class='test error'>";
                echo "<p><strong>❌ Email send failed</strong></p>";
                echo "<p><strong>Error:</strong> " . $mail->ErrorInfo . "</p>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='test error'>";
        echo "<p><strong>❌ Exception occurred:</strong></p>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
        error_log('Email test exception: ' . $e->getMessage());
    }
    
    echo "</div>";
    
    // Test 4: System info
    echo "<div class='test info'>";
    echo "<h3>4️⃣ System Information</h3>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
    echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Enabled' : '❌ Disabled') . "</p>";
    echo "<p><strong>cURL:</strong> " . (extension_loaded('curl') ? '✅ Enabled' : '❌ Disabled') . "</p>";
    echo "<p><strong>Mail Function:</strong> " . (function_exists('mail') ? '✅ Available' : '❌ Not available') . "</p>";
    echo "</div>";
    
    ?>
    
    <hr style="margin: 30px 0;">
    <p><a href="admin/add_teacher.php" style="display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">← Back to Add Teacher</a></p>
</div>

</body>
</html>

