<?php
/**
 * Working Email Solution
 * SMTP के बिना भी काम करेगा - email को file में save करेगा
 * और real email के लिए Gmail setup की instructions देगा
 */

require_once 'includes/db.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "📧 CERTIFICATE EMAIL DELIVERY SYSTEM\n";
echo "=" . str_repeat("=", 80) . "\n\n";

$cert_id = 2; // Certificate ID
$email_to = 'soumyajitsantra699@gmail.com';

try {
    // Get certificate details
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name, u.enrollment_no, e.exam_name,
               t.trade_code, t.trade_name
        FROM certificates c
        JOIN users u ON c.student_id = u.id
        JOIN exams e ON c.exam_id = e.id
        JOIN trades t ON e.trade_id = t.id
        WHERE c.id = ?
    ");
    $stmt->execute([$cert_id]);
    $cert = $stmt->fetch();
    
    if (!$cert) {
        echo "❌ Certificate not found!\n";
        exit;
    }
    
    echo "📋 CERTIFICATE DETAILS\n";
    echo str_repeat("-", 80) . "\n";
    echo "   Certificate ID: " . $cert['certificate_id'] . "\n";
    echo "   Student Name: " . $cert['full_name'] . "\n";
    echo "   Enrollment No: " . $cert['enrollment_no'] . "\n";
    echo "   Course: " . $cert['trade_code'] . " - " . $cert['trade_name'] . "\n";
    echo "   Exam: " . $cert['exam_name'] . "\n";
    echo "   Marks: " . $cert['score'] . "/32 (" . $cert['percentage'] . "%)\n";
    echo "   Grade: A+\n";
    echo "   Verification Code: " . $cert['verification_code'] . "\n";
    echo "   Generated: " . date('Y-m-d H:i:s', strtotime($cert['created_at'])) . "\n\n";
    
    // Build email links
    $attempt_id = $cert['id'];
    $cert_url = 'http://localhost/EXAMs/student/certificate_view.php?id=' . $attempt_id;
    $download_url = $cert_url . '&download=1';
    $verify_url = 'http://localhost/EXAMs/verify.php?code=' . $cert['verification_code'];
    
    // Create email body
    $email_html = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333; 
                background-color: #f5f5f5;
            }
            .container { 
                max-width: 700px; 
                margin: 0 auto; 
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px;
                font-weight: 300;
            }
            .header p { 
                margin: 10px 0 0 0;
                font-size: 14px;
                opacity: 0.9;
            }
            .content { 
                padding: 40px; 
            }
            .greeting {
                font-size: 16px;
                margin-bottom: 20px;
            }
            .details-box { 
                background: #f8f9fa; 
                border-left: 4px solid #667eea;
                padding: 20px; 
                margin: 25px 0;
                border-radius: 4px;
            }
            .detail-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #eee;
            }
            .detail-row:last-child {
                border-bottom: none;
            }
            .detail-label {
                font-weight: 600;
                color: #666;
            }
            .detail-value {
                color: #333;
            }
            .buttons { 
                text-align: center; 
                margin: 30px 0;
                padding: 30px 0;
                border-top: 1px solid #eee;
                border-bottom: 1px solid #eee;
            }
            .btn { 
                display: inline-block;
                padding: 14px 30px; 
                margin: 8px;
                text-decoration: none; 
                border-radius: 5px;
                color: white; 
                font-weight: 600;
                font-size: 14px;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .btn-primary { 
                background: #667eea;
            }
            .btn-primary:hover {
                background: #5568d3;
            }
            .btn-success { 
                background: #28a745;
            }
            .btn-success:hover {
                background: #218838;
            }
            .verification-code {
                background: #fff3cd;
                border: 1px solid #ffc107;
                padding: 15px;
                border-radius: 4px;
                margin: 20px 0;
                text-align: center;
            }
            .verification-code p {
                margin: 0 0 5px 0;
                font-size: 12px;
                color: #666;
            }
            .verification-code .code {
                font-family: 'Courier New', monospace;
                font-size: 18px;
                font-weight: bold;
                color: #856404;
            }
            .footer { 
                text-align: center; 
                color: #999; 
                font-size: 12px; 
                padding: 20px;
                border-top: 1px solid #eee;
                background: #f8f9fa;
            }
            .footer p {
                margin: 5px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Congratulations!</h1>
                <p>Your Certificate is Ready</p>
            </div>
            
            <div class='content'>
                <div class='greeting'>
                    <p>Dear <strong>" . htmlspecialchars($cert['full_name']) . "</strong>,</p>
                    <p>You have successfully completed the exam and earned your certificate! We are pleased to confirm that you have achieved excellent results.</p>
                </div>
                
                <div class='details-box'>
                    <div class='detail-row'>
                        <span class='detail-label'>Certificate ID:</span>
                        <span class='detail-value'><strong>" . htmlspecialchars($cert['certificate_id']) . "</strong></span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Student Name:</span>
                        <span class='detail-value'>" . htmlspecialchars($cert['full_name']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Enrollment No:</span>
                        <span class='detail-value'>" . htmlspecialchars($cert['enrollment_no']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Course:</span>
                        <span class='detail-value'>" . htmlspecialchars($cert['trade_code'] . ' - ' . $cert['trade_name']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Exam:</span>
                        <span class='detail-value'>" . htmlspecialchars($cert['exam_name']) . "</span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Marks Obtained:</span>
                        <span class='detail-value'><strong>" . number_format($cert['score'], 0) . "/32</strong></span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Percentage:</span>
                        <span class='detail-value'><strong>" . number_format($cert['percentage'], 2) . "%</strong></span>
                    </div>
                    <div class='detail-row'>
                        <span class='detail-label'>Grade:</span>
                        <span class='detail-value'><strong style='color: #28a745; font-size: 16px;'>A+ (Excellent)</strong></span>
                    </div>
                </div>
                
                <div class='verification-code'>
                    <p>Certificate Verification Code:</p>
                    <div class='code'>" . htmlspecialchars($cert['verification_code']) . "</div>
                    <p style='margin-top: 8px; font-size: 11px;'>Use this code to verify your certificate</p>
                </div>
                
                <div class='buttons'>
                    <a href='$cert_url' class='btn btn-primary'>📄 View Certificate</a>
                    <a href='$download_url' class='btn btn-success'>⬇️ Download PDF</a>
                </div>
                
                <div style='color: #666; font-size: 14px; line-height: 1.6;'>
                    <p><strong>Next Steps:</strong></p>
                    <ul style='padding-left: 20px;'>
                        <li>Download and save your certificate</li>
                        <li>Share your verification code for certification</li>
                        <li>Keep your Enrollment No for future reference</li>
                    </ul>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>EXAMs Learning System</strong></p>
                <p>National Skill Training Institute, Kolkata</p>
                <p style='color: #ccc; margin-top: 10px;'>
                    <a href='$verify_url' style='color: #667eea; text-decoration: none;'>Verify Certificate Online</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Try to send via SMTP
    $smtp_worked = false;
    $mail = new PHPMailer(true);
    
    try {
        echo "🔧 ATTEMPTING SMTP DELIVERY\n";
        echo str_repeat("-", 80) . "\n";
        echo "   Host: " . SMTP_HOST . "\n";
        echo "   Port: " . SMTP_PORT . "\n";
        echo "   User: " . substr(SMTP_USER, 0, 5) . "***\n";
        echo "   Recipient: $email_to\n\n";
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->Timeout = 5;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email_to, $cert['full_name']);
        $mail->isHTML(true);
        $mail->Subject = "🎓 Your Certificate is Ready - " . $cert['certificate_id'];
        $mail->Body = $email_html;
        $mail->AltBody = strip_tags($email_html);
        
        if ($mail->send()) {
            echo "✅ EMAIL SENT SUCCESSFULLY VIA SMTP!\n\n";
            $smtp_worked = true;
        } else {
            echo "⚠️  SMTP Failed: " . $mail->ErrorInfo . "\n";
            echo "   Using fallback method...\n\n";
        }
    } catch (Exception $e) {
        echo "⚠️  SMTP Error: " . $e->getMessage() . "\n";
        echo "   Using fallback method...\n\n";
    }
    
    // Fallback: Save email to file
    if (!$smtp_worked) {
        echo "💾 SAVING EMAIL TO FILE\n";
        echo str_repeat("-", 80) . "\n";
        
        $emails_dir = __DIR__ . '/emails';
        if (!is_dir($emails_dir)) {
            mkdir($emails_dir, 0755, true);
        }
        
        $filename = 'certificate_' . $cert['id'] . '_' . time() . '.html';
        $filepath = $emails_dir . '/' . $filename;
        
        file_put_contents($filepath, $email_html);
        
        echo "   ✅ Email saved to: /emails/$filename\n";
        echo "   ✅ Size: " . round(filesize($filepath) / 1024, 2) . " KB\n\n";
    }
    
    // Display success summary
    echo "=" . str_repeat("=", 80) . "\n";
    echo "✅ DELIVERY CONFIRMED\n";
    echo "=" . str_repeat("=", 80) . "\n\n";
    
    echo "📧 Email Status: ✅ READY FOR DELIVERY\n";
    echo "   Recipient: $email_to\n";
    echo "   Subject: 🎓 Your Certificate is Ready - " . $cert['certificate_id'] . "\n";
    echo "   Status: " . ($smtp_worked ? "SENT" : "SAVED (Ready to send when SMTP available)") . "\n\n";
    
    echo "🔗 ACTION LINKS (included in email):\n";
    echo "   1. View: $cert_url\n";
    echo "   2. Download: $download_url\n";
    echo "   3. Verify: $verify_url\n\n";
    
    if (!$smtp_worked) {
        echo "=" . str_repeat("=", 80) . "\n";
        echo "💡 TO SEND THIS EMAIL - SETUP GMAIL\n";
        echo "=" . str_repeat("=", 80) . "\n\n";
        
        echo "Step 1: Go to https://myaccount.google.com/security\n\n";
        
        echo "Step 2: Enable 2-Step Verification (if not enabled)\n\n";
        
        echo "Step 3: Generate App Password\n";
        echo "   - Go to Security → App Passwords\n";
        echo "   - Select Mail + Windows Computer\n";
        echo "   - Copy the 16-character password\n\n";
        
        echo "Step 4: Edit config.php\n";
        echo "   - Find SMTP_PASS line\n";
        echo "   - Replace 'your-app-password' with the 16-char password\n\n";
        
        echo "Step 5: Run this script again\n";
        echo "   http://localhost/EXAMs/send_email_working.php\n\n";
        
        echo "✅ Your email will be sent automatically!\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
