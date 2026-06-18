<?php
/**
 * Simple Email Sending Script
 * config.php से SMTP settings read करता है
 */

require_once 'includes/db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "📧 SENDING CERTIFICATE EMAIL\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$cert_id = 2; // Last created certificate
$email_to = 'soumyajitsantra699@gmail.com';

try {
    // Get certificate details
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name, e.exam_name
        FROM certificates c
        JOIN users u ON c.student_id = u.id
        JOIN exams e ON c.exam_id = e.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cert_id]);
    $cert = $stmt->fetch();
    
    if (!$cert) {
        echo "❌ Certificate not found!\n";
        exit;
    }
    
    echo "📋 Certificate Details:\n";
    echo "   ID: " . $cert['certificate_id'] . "\n";
    echo "   Student: " . $cert['full_name'] . "\n";
    echo "   Exam: " . $cert['exam_name'] . "\n";
    echo "   Marks: " . $cert['score'] . "/32 (" . $cert['percentage'] . "%)\n\n";
    
    // Initialize PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->Timeout = 10;
    
    echo "🔧 SMTP Configuration:\n";
    echo "   Host: " . SMTP_HOST . "\n";
    echo "   Port: " . SMTP_PORT . "\n";
    echo "   User: " . substr(SMTP_USER, 0, 5) . "***\n\n";
    
    // Prepare email
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($email_to, $cert['full_name']);
    $mail->isHTML(true);
    
    $attempt_id = $cert['id'];
    $cert_url = BASE_URL . "/student/certificate_view.php?id=" . $attempt_id;
    $download_url = $cert_url . "&download=1";
    $verify_url = BASE_URL . "/verify.php?code=" . $cert['verification_code'];
    
    $mail->Subject = "🎓 Your Certificate is Ready - " . $cert['certificate_id'];
    
    $mail->Body = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 5px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .details { background: white; padding: 20px; border-left: 4px solid #667eea; margin: 20px 0; }
            .buttons { text-align: center; margin: 30px 0; }
            .btn { display: inline-block; padding: 12px 25px; margin: 5px; text-decoration: none; border-radius: 5px; color: white; font-weight: bold; }
            .btn-primary { background: #667eea; }
            .btn-success { background: #28a745; }
            .footer { text-align: center; color: #999; font-size: 12px; padding: 20px 0; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🎉 Congratulations!</h2>
                <p>Your Certificate is Ready</p>
            </div>
            
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($cert['full_name']) . "</strong>,</p>
                <p>You have successfully completed the exam and earned your certificate!</p>
                
                <div class='details'>
                    <h3>📋 Certificate Details</h3>
                    <p><strong>Certificate ID:</strong> " . htmlspecialchars($cert['certificate_id']) . "</p>
                    <p><strong>Exam:</strong> " . htmlspecialchars($cert['exam_name']) . "</p>
                    <p><strong>Marks:</strong> " . $cert['score'] . "/32</p>
                    <p><strong>Percentage:</strong> " . $cert['percentage'] . "%</p>
                    <p><strong>Grade:</strong> A+</p>
                    <p><strong>Verification Code:</strong> " . htmlspecialchars($cert['verification_code']) . "</p>
                </div>
                
                <div class='buttons'>
                    <a href='$cert_url' class='btn btn-primary'>View Certificate</a>
                    <a href='$download_url' class='btn btn-success'>Download PDF</a>
                </div>
            </div>
            
            <div class='footer'>
                <p>© 2026 EXAMs Learning System<br>National Skill Training Institute, Kolkata</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    echo "📧 Sending email to: $email_to\n";
    echo str_repeat("-", 70) . "\n";
    
    if ($mail->send()) {
        echo "✅ EMAIL SENT SUCCESSFULLY!\n\n";
        echo "📊 Delivery Details:\n";
        echo "   ✅ To: $email_to\n";
        echo "   ✅ Subject: " . $mail->Subject . "\n";
        echo "   ✅ Certificate ID: " . $cert['certificate_id'] . "\n";
        echo "   ✅ Status: SENT ✅\n";
        echo "   ✅ Time: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "🔗 Links in Email:\n";
        echo "   • View: $cert_url\n";
        echo "   • Download: $download_url\n";
        echo "   • Verify: $verify_url\n";
    } else {
        echo "❌ Email sending failed!\n";
        echo "Error: " . $mail->ErrorInfo . "\n\n";
        
        echo "💡 Troubleshooting:\n";
        echo "   1. Check SMTP credentials in config.php\n";
        echo "   2. If Gmail: Use 16-char App Password (not regular password)\n";
        echo "   3. If Mailtrap: Sign up free at mailtrap.io\n";
        echo "   4. Test internet connection\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
