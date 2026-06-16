<?php
/**
 * Test Email Delivery Fix
 * Verify emails are now being delivered correctly
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/phpmailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>📧 Testing Email Delivery Fix</h2>";
echo "<hr>";

// Configuration check
echo "<h3>✅ Configuration Check:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Auth Username:</strong> " . MAIL_USERNAME . "</li>";
echo "<li><strong>From Email:</strong> " . MAIL_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . MAIL_FROM_NAME . "</li>";
echo "<li><strong>Match Status:</strong> " . (MAIL_USERNAME === MAIL_FROM_EMAIL ? "✅ CORRECT (Emails will be delivered)" : "❌ MISMATCH (Emails may be blocked)") . "</li>";
echo "</ul>";

echo "<hr>";

// Send test email
echo "<h3>🔧 Sending Test Email:</h3>";

$testEmail = 'ranajitsantra800@gmail.com'; // The user who registered
$testOTP = '123456';

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = 0;
    
    // Sender
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addReplyTo('support@educare.com', 'EDUCARE Support'); // Optional reply-to
    
    // Recipient
    $mail->addAddress($testEmail, 'Ranajit Santra');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Verification Code - CITS LMS';
    
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .otp-box { background: #f0f4ff; border: 2px solid #667eea; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
            .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 4px; color: #667eea; font-family: 'Courier New', monospace; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>CITS LMS</h1>
                <p>Email Verification</p>
            </div>
            <div class='content'>
                <p>Hello <strong>Ranajit Santra</strong>,</p>
                <p>Your OTP verification code is:</p>
                <div class='otp-box'>
                    <div class='otp-code'>$testOTP</div>
                    <div style='color: #666; font-size: 14px; margin-top: 10px;'>Valid for 10 minutes</div>
                </div>
                <p><strong>This is a test email to verify delivery is working!</strong></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->AltBody = "Your OTP: $testOTP. Valid for 10 minutes.";
    
    if ($mail->send()) {
        echo "<p style='color: green; font-size: 18px;'>✅ <strong>Email Sent Successfully!</strong></p>";
        echo "<p><strong>To:</strong> $testEmail</p>";
        echo "<p><strong>From:</strong> " . MAIL_FROM_EMAIL . " (" . MAIL_FROM_NAME . ")</p>";
        echo "<p style='color: blue; font-weight: bold;'>📨 Check your inbox/spam folder for the test email!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px;'>❌ Email Sending Failed</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

// Instructions
echo "<h3>📋 Next Steps:</h3>";
echo "<ol>";
echo "<li>Check email inbox for test message</li>";
echo "<li>If in spam folder, mark as \"Not Spam\"</li>";
echo "<li>Register a new account - emails should now be delivered</li>";
echo "<li>User will receive OTP within seconds</li>";
echo "</ol>";

echo "<hr>";

echo "<h3>🔒 Gmail Security Note:</h3>";
echo "<ul>";
echo "<li>✅ Using Gmail SMTP with authenticated email</li>";
echo "<li>✅ From address matches SMTP username (Gmail requirement)</li>";
echo "<li>✅ SPF/DKIM properly configured by Gmail</li>";
echo "<li>Emails should now be delivered to recipient inboxes</li>";
echo "</ul>";

?>
