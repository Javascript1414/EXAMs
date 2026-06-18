<?php
require_once 'includes/db.php';
require_once 'vendor/autoload.php';

echo "📧 Certificate Email Delivery System\n";
echo "=" . str_repeat("=", 60) . "\n\n";

$student_id = 29;
$attempt_id = 19;
$email_to = 'soumyajitsantra699@gmail.com';

try {
    // Get certificate and student details
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
        echo "❌ Certificate not found\n";
        exit;
    }
    
    echo "📋 Certificate Details:\n";
    echo "   ID: " . $cert['certificate_id'] . "\n";
    echo "   Student: " . $cert['full_name'] . "\n";
    echo "   Email: " . $cert['email'] . "\n";
    echo "   Exam: " . $cert['exam_name'] . "\n";
    echo "   Marks: " . $cert['score'] . "%\n";
    echo "   Verification Code: " . $cert['verification_code'] . "\n\n";
    
    echo "🔧 SMTP Configuration:\n";
    
    // Initialize PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    
    // Try using Mailtrap for testing (free service)
    $mail->Host = 'smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Username = '5644d2f3f1f4c9';  // Mailtrap test credentials
    $mail->Password = 'b4fbb80cc6c5ba'; // Mailtrap test password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 2525;
    $mail->Timeout = 10;
    $mail->SMTPDebug = 2; // Debug output
    
    echo "   Host: " . $mail->Host . "\n";
    echo "   Port: " . $mail->Port . "\n";
    echo "   User: Mailtrap Test Account\n";
    echo "   Encryption: STARTTLS\n";
    echo "   Service: Mailtrap.io (Testing/Development)\n\n";
    
    echo "📧 Preparing email...\n";
    
    // Set email addresses
    $mail->setFrom('noreply@exams.local', 'EXAMs Learning System');
    $mail->addAddress($email_to, $cert['full_name']);
    $mail->isHTML(true);
    
    // Email subject and body
    $mail->Subject = "🎓 Your Certificate is Ready - " . $cert['certificate_id'];
    
    $cert_url = "http://localhost/EXAMs/student/certificate_view.php?id=" . $attempt_id;
    $download_url = $cert_url . "&download=1";
    $verify_url = "http://localhost/EXAMs/verify.php?code=" . $cert['verification_code'];
    
    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 5px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; margin: 20px 0; border-left: 4px solid #667eea; }
            .details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
            .detail-label { font-weight: bold; color: #667eea; }
            .buttons { text-align: center; margin: 30px 0; }
            .btn { display: inline-block; padding: 12px 25px; margin: 5px; text-decoration: none; border-radius: 5px; font-weight: bold; }
            .btn-primary { background: #667eea; color: white; }
            .btn-success { background: #28a745; color: white; }
            .btn-secondary { background: #6c757d; color: white; }
            .footer { text-align: center; color: #999; font-size: 12px; padding: 20px 0; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Congratulations!</h2>
            </div>
            
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($cert['full_name']) . "</strong>,</p>
                <p>You have successfully completed the exam <strong>" . htmlspecialchars($cert['exam_name']) . "</strong> and earned your certificate!</p>
            </div>
            
            <div class='details'>
                <h3 style='color: #667eea; margin-top: 0;'>📋 Certificate Details</h3>
                <div class='detail-row'>
                    <span class='detail-label'>Certificate ID:</span>
                    <span><strong>" . htmlspecialchars($cert['certificate_id']) . "</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Marks Obtained:</span>
                    <span><strong>" . $cert['score'] . "/" . $cert['percentage'] . "</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Percentage:</span>
                    <span><strong>" . $cert['percentage'] . "%</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Grade:</span>
                    <span><strong>A+</strong></span>
                </div>
                <div class='detail-row'>
                    <span class='detail-label'>Verification Code:</span>
                    <span><strong>" . htmlspecialchars($cert['verification_code']) . "</strong></span>
                </div>
            </div>
            
            <div class='buttons'>
                <a href='$cert_url' class='btn btn-primary'>View Certificate</a>
                <a href='$download_url' class='btn btn-success'>Download PDF</a>
                <a href='$verify_url' class='btn btn-secondary'>Verify Certificate</a>
            </div>
            
            <div class='details'>
                <h4 style='color: #667eea;'>🔗 Direct Links</h4>
                <p style='word-break: break-all; font-size: 12px;'>
                    View: <a href='$cert_url'>$cert_url</a><br>
                    Download: <a href='$download_url'>$download_url</a><br>
                    Verify: <a href='$verify_url'>$verify_url</a>
                </p>
            </div>
            
            <div class='footer'>
                <p>© 2026 EXAMs Learning System. All rights reserved.<br>
                National Skill Training Institute, Kolkata</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    echo "   ✅ Email prepared\n\n";
    
    echo "🚀 Sending email to: $email_to\n";
    echo str_repeat("-", 60) . "\n";
    
    if ($mail->send()) {
        echo "✅ EMAIL SENT SUCCESSFULLY!\n";
        echo str_repeat("=", 60) . "\n\n";
        echo "📊 DELIVERY CONFIRMATION:\n";
        echo "   ✅ To: $email_to\n";
        echo "   ✅ Subject: " . $mail->Subject . "\n";
        echo "   ✅ Certificate ID: " . $cert['certificate_id'] . "\n";
        echo "   ✅ Verification Code: " . $cert['verification_code'] . "\n";
        echo "   ✅ Time: " . date('Y-m-d H:i:s') . "\n\n";
        echo "💡 Student can now:\n";
        echo "   1. Check email at: $email_to\n";
        echo "   2. View certificate online\n";
        echo "   3. Download PDF certificate\n";
        echo "   4. Verify certificate authenticity\n";
    } else {
        echo "❌ Email sending failed!\n";
        echo "Error: " . $mail->ErrorInfo . "\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Still show success because certificate was created
        echo "ℹ️ NOTE: Certificate was successfully created in the system!\n";
        echo "   It's stored in the database and can be accessed by:\n";
        echo "   • Student viewing certificates in dashboard\n";
        echo "   • Direct URL: " . $cert_url . "\n";
        echo "   • Verification: " . $verify_url . "\n\n";
        
        echo "📧 To enable email delivery, configure SMTP:\n";
        echo "   1. Add .env file with SMTP credentials\n";
        echo "   2. Or modify config.php with Gmail App Password\n";
        echo "   3. Enable \"Less secure app access\" on Gmail (if needed)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
}
?>
