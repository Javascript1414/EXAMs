<?php
/**
 * Comprehensive PHPMailer Test
 * Diagnose Gmail SMTP issues
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/phpmailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

echo "<h2>📧 PHPMailer Gmail SMTP Test</h2>";
echo "<hr>";

// Step 1: Configuration Check
echo "<h3>1️⃣ Configuration Check:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . MAIL_HOST . "</li>";
echo "<li><strong>Port:</strong> " . MAIL_PORT . "</li>";
echo "<li><strong>Encryption:</strong> " . MAIL_ENCRYPTION . "</li>";
echo "<li><strong>Username:</strong> " . substr(MAIL_USERNAME, 0, 5) . "***@gmail.com</li>";
echo "<li><strong>From:</strong> " . MAIL_FROM_NAME . " &lt;" . MAIL_FROM_EMAIL . "&gt;</li>";
echo "</ul>";

echo "<hr>";

// Step 2: Connection Test
echo "<h3>2️⃣ SMTP Connection Test:</h3>";

try {
    $mail = new PHPMailer(true);
    
    // Enable debug
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) { 
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; margin: 5px 0; font-size: 12px;'>" . htmlspecialchars($str) . "</pre>";
    };
    
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->Port = MAIL_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    
    echo "<p>Attempting connection...</p>";
    
    // Test connection
    if ($mail->smtpConnect()) {
        echo "<p style='color: green;'>✅ <strong>SMTP Connection Successful!</strong></p>";
        $mail->smtpClose();
    } else {
        echo "<p style='color: red;'>❌ Connection Failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Connection Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// Step 3: Send Test Email
echo "<h3>3️⃣ Sending Test Email:</h3>";

$test_to_email = 'soumosantra588@gmail.com'; // Send to the configured email address
$test_subject = 'EDUCARE - Test Email - ' . date('Y-m-d H:i:s');

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = 0;
    
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($test_to_email);
    $mail->isHTML(true);
    $mail->Subject = $test_subject;
    
    $mail->Body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px;'>
            <h1>✅ PHPMailer Test Successful!</h1>
        </div>
        <div style='background: white; padding: 20px; margin-top: 10px; border: 1px solid #ddd; border-radius: 8px;'>
            <p>Hello,</p>
            <p>This test email confirms that PHPMailer SMTP is working correctly!</p>
            <p><strong>Test Details:</strong></p>
            <ul>
                <li>Server: " . MAIL_HOST . "</li>
                <li>Port: " . MAIL_PORT . "</li>
                <li>Encryption: " . MAIL_ENCRYPTION . "</li>
                <li>Timestamp: " . date('Y-m-d H:i:s') . "</li>
            </ul>
            <p>If you received this email, Gmail SMTP configuration is working!</p>
            <hr>
            <p style='color: #999; font-size: 12px;'>
                EDUCARE LMS | Automated Test Email
            </p>
        </div>
    </body>
    </html>
    ";
    
    if ($mail->send()) {
        echo "<p style='color: green;'><strong>✅ Email Sent Successfully!</strong></p>";
        echo "<p><strong>To:</strong> $test_to_email</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p style='color: blue;'><strong>Check your Gmail inbox or spam folder for this test email!</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Email sending failed</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    if (isset($mail)) {
        echo "<p><strong>Error Info:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
}

echo "<hr>";

// Step 4: Troubleshooting
echo "<h3>🔧 Troubleshooting Steps:</h3>";
echo "<p>If email is not arriving:</p>";
echo "<ol>";
echo "<li><strong>Check Spam Folder:</strong> Gmail sometimes filters emails to spam</li>";
echo "<li><strong>Check Gmail Less Secure App:</strong> Go to <a href='https://myaccount.google.com/lesssecureapps' target='_blank'>https://myaccount.google.com/lesssecureapps</a> and enable it</li>";
echo "<li><strong>Use App Password:</strong> The password configured (" . substr(MAIL_PASSWORD, 0, 4) . "...) should be 16-character App Password</li>";
echo "<li><strong>Check Port 587:</strong> If blocked, try Port 465 (SSL instead of TLS)</li>";
echo "<li><strong>Two-Factor Authentication:</strong> Must be enabled for App Passwords to work</li>";
echo "</ol>";

echo "<hr>";

echo "<h2 style='color: blue;'>✅ Test Complete</h2>";
echo "<p>If ✅ all tests passed, registration emails should work!</p>";
?>
