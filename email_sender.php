<?php
/**
 * Certificate Email Delivery System
 * यह सभी methods के साथ काम करता है
 */

require_once 'includes/db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "📧 EMAIL DELIVERY SYSTEM\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$student_id = 29;
$attempt_id = 19;
$email_to = 'soumyajitsantra699@gmail.com';

// Get certificate details
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name, u.email, e.exam_name
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    JOIN exams e ON c.exam_id = e.id
    WHERE c.student_id = ? AND c.exam_id = 10
    ORDER BY c.id DESC
    LIMIT 1
");
$stmt->execute([$student_id]);
$cert = $stmt->fetch();

if (!$cert) {
    echo "❌ Certificate not found!\n";
    exit;
}

// Email content
$cert_url = "http://localhost/EXAMs/student/certificate_view.php?id=" . $attempt_id;
$download_url = $cert_url . "&download=1";
$verify_url = "http://localhost/EXAMs/verify.php?code=" . $cert['verification_code'];

$email_html = "
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; color: #333; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h2 { margin: 0; font-size: 28px; }
        .content { padding: 30px 20px; }
        .details { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #667eea; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .detail-label { font-weight: bold; color: #667eea; }
        .buttons { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; padding: 12px 25px; margin: 8px; text-decoration: none; border-radius: 5px; font-weight: bold; color: white; }
        .btn-primary { background: #667eea; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        .footer { text-align: center; color: #999; font-size: 12px; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>🎉 बधाई हो!</h2>
            <p style='margin: 10px 0 0 0; font-size: 16px;'>आपका Certificate तैयार है</p>
        </div>
        
        <div class='content'>
            <p>प्रिय <strong>" . htmlspecialchars($cert['full_name']) . "</strong>,</p>
            <p>आपने परीक्षा सफलतापूर्वक पास कर ली है! आपका Certificate तैयार है।</p>
            
            <div class='details'>
                <h3 style='color: #667eea; margin-top: 0;'>📋 Certificate विवरण</h3>
                <div class='detail-row'>
                    <span class='detail-label'>Certificate ID:</span>
                    <span><strong>" . htmlspecialchars($cert['certificate_id']) . "</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Exam:</span>
                    <span><strong>" . htmlspecialchars($cert['exam_name']) . "</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>अंक (Marks):</span>
                    <span><strong>" . $cert['score'] . "/32</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>प्रतिशत (Percentage):</span>
                    <span><strong>" . $cert['percentage'] . "%</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>ग्रेड (Grade):</span>
                    <span><strong>A+</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Verification Code:</span>
                    <span><strong style='font-family: monospace;'>" . htmlspecialchars($cert['verification_code']) . "</strong></span>
                </div>
            </div>
            
            <div class='buttons'>
                <a href='$cert_url' class='btn btn-primary'>👁️ Certificate देखें</a>
                <a href='$download_url' class='btn btn-success'>⬇️ PDF Download करें</a>
                <a href='$verify_url' class='btn btn-secondary'>✓ Verify करें</a>
            </div>
            
            <div class='details'>
                <h4 style='color: #667eea; margin-top: 0;'>📌 अगले कदम (Next Steps)</h4>
                <ul>
                    <li>Certificate को online देखें</li>
                    <li>PDF download करके रखें</li>
                    <li>अपने achievement को share करें</li>
                    <li>Certificate को verify करें</li>
                </ul>
            </div>
        </div>
        
        <div class='footer'>
            <p>© 2026 EXAMs Learning System. सर्वाधिकार सुरक्षित।<br>
            National Skill Training Institute, Kolkata<br>
            <strong>Certificate ID:</strong> " . htmlspecialchars($cert['certificate_id']) . "</p>
        </div>
    </div>
</body>
</html>
";

echo "📋 OPTION 1: REAL SMTP भेजने के लिए\n";
echo str_repeat("-", 70) . "\n";
echo "अगर SMTP configured है तो यह काम करेगा:\n\n";

// Try to send via SMTP
$mail = new PHPMailer(true);

try {
    // Configure based on what's available
    $mail->isSMTP();
    
    // Check if Gmail credentials are set
    $gmail_user = getenv('GMAIL_USER') ?: 'your-email@gmail.com';
    $gmail_pass = getenv('GMAIL_PASS') ?: 'your-app-password';
    
    if ($gmail_user !== 'your-email@gmail.com' && $gmail_pass !== 'your-app-password') {
        // Gmail configuration
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $gmail_user;
        $mail->Password = $gmail_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        echo "✅ Gmail SMTP configured\n";
    } else {
        // Fallback to Mailtrap
        $mail->Host = 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '5644d2f3f1f4c9';
        $mail->Password = 'b4fbb80cc6c5ba';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 2525;
        echo "✅ Mailtrap configured\n";
    }
    
    $mail->setFrom('noreply@exams.local', 'EXAMs Learning System');
    $mail->addAddress($email_to, $cert['full_name']);
    $mail->isHTML(true);
    $mail->Subject = "🎓 Your Certificate is Ready - " . $cert['certificate_id'];
    $mail->Body = $email_html;
    $mail->Timeout = 5;
    
    if ($mail->send()) {
        echo "✅ EMAIL SENT SUCCESSFULLY!\n\n";
        echo "📧 DELIVERY DETAILS:\n";
        echo "   To: $email_to\n";
        echo "   Subject: " . $mail->Subject . "\n";
        echo "   Status: SENT ✅\n";
        exit;
    } else {
        throw new Exception("Email send failed");
    }
    
} catch (Exception $e) {
    echo "⚠️ SMTP Error: " . $e->getMessage() . "\n\n";
}

// FALLBACK: Save email as HTML file
echo "\n" . str_repeat("=", 70) . "\n";
echo "📋 OPTION 2: FILE में SAVE करना\n";
echo str_repeat("-", 70) . "\n";

if (!is_dir('emails')) {
    mkdir('emails', 0755, true);
}

$email_file = 'emails/certificate_' . $cert['id'] . '_' . time() . '.html';
file_put_contents($email_file, $email_html);

echo "✅ Email HTML file save हो गई:\n";
echo "   Location: $email_file\n";
echo "   Size: " . strlen($email_html) . " bytes\n\n";

// OPTION 3: Display email in browser
echo str_repeat("=", 70) . "\n";
echo "📧 OPTION 3: Browser में देखें\n";
echo str_repeat("-", 70) . "\n";
echo "Click करें: view_email.php?cert_id=" . $cert['id'] . "\n\n";

// Summary
echo str_repeat("=", 70) . "\n";
echo "✅ EMAIL READY - DELIVERY OPTIONS:\n";
echo str_repeat("=", 70) . "\n\n";

echo "📊 Certificate Details:\n";
echo "   ✅ To: $email_to\n";
echo "   ✅ Certificate ID: " . $cert['certificate_id'] . "\n";
echo "   ✅ Marks: " . $cert['score'] . "/32 (" . $cert['percentage'] . "%)\n";
echo "   ✅ Verification: " . $cert['verification_code'] . "\n\n";

echo "🔗 Student Links:\n";
echo "   1. View: " . $cert_url . "\n";
echo "   2. Download: " . $download_url . "\n";
echo "   3. Verify: " . $verify_url . "\n\n";

echo "💡 SMTP CONFIGURE करने के लिए:\n";
echo "   1. Gmail के लिए:\n";
echo "      - App Password generate करें\n";
echo "      - .env file बनाएं:\n";
echo "        GMAIL_USER=your-email@gmail.com\n";
echo "        GMAIL_PASS=your-app-password\n\n";
echo "   2. Mailtrap के लिए:\n";
echo "      - Mailtrap.io पर free account बनाएं\n";
echo "      - SMTP settings copy करें\n\n";
echo "   3. Production के लिए:\n";
echo "      - SendGrid/AWS SES/Mailgun use करें\n";

?>
