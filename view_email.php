<?php
require_once 'includes/db.php';

// Use BASE_URL instead of hardcoded localhost
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_path = dirname($_SERVER['SCRIPT_NAME']);
    define('BASE_URL', $protocol . $host . $base_path);
}

$cert_id = $_GET['cert_id'] ?? 2;

// Get certificate details
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE id = ?");
$stmt->execute([$cert_id]);
$cert = $stmt->fetch();

if (!$cert) {
    die("❌ Certificate not found!");
}

// Get student details
$stmt = $pdo->prepare("SELECT u.full_name, u.email, e.exam_name FROM users u JOIN exams e WHERE u.id = ? AND e.id = ?");
$stmt->execute([$cert['student_id'], $cert['exam_id']]);
$details = $stmt->fetch();

$attempt_id = $cert['id'];
// Use BASE_URL instead of hardcoded localhost
$cert_url = BASE_URL . "/student/certificate_view.php?id=" . $attempt_id;
$download_url = $cert_url . "&download=1";
$verify_url = BASE_URL . "/verify.php?code=" . urlencode($cert['verification_code']);

?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Email - Preview</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #333; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; min-height: 100vh; }
        .page-header { text-align: center; color: white; margin-bottom: 30px; }
        .page-header h1 { font-size: 28px; margin-bottom: 10px; }
        .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 30px; text-align: center; }
        .header h2 { margin: 0; font-size: 32px; }
        .header p { margin: 10px 0 0 0; font-size: 16px; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .content p { line-height: 1.6; margin-bottom: 15px; }
        .details { background: #f9f9f9; padding: 25px; border-radius: 5px; margin: 25px 0; border-left: 4px solid #667eea; }
        .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: bold; color: #667eea; }
        .detail-value { text-align: right; }
        .buttons { text-align: center; margin: 30px 0; display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; }
        .btn { display: inline-block; padding: 14px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; color: white; transition: all 0.3s; }
        .btn-primary { background: #667eea; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; transform: translateY(-2px); }
        .footer { text-align: center; color: #999; font-size: 13px; padding: 25px; border-top: 1px solid #eee; }
        .info-box { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .info-box strong { color: #0066cc; }
        .links { background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .links h4 { color: #667eea; margin-bottom: 10px; }
        .link-item { margin: 8px 0; }
        .link-item a { color: #667eea; text-decoration: none; word-break: break-all; }
        .link-item a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>📧 Certificate Email Preview</h1>
        <p>यह email soumyajitsantra699@gmail.com को भेजा जाएगा</p>
    </div>

    <div class="container">
        <div class="header">
            <h2>🎉 बधाई हो!</h2>
            <p>आपका Certificate तैयार है</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <strong>📧 To:</strong> soumyajitsantra699@gmail.com<br>
                <strong>👤 Name:</strong> <?= htmlspecialchars($details['full_name']) ?><br>
                <strong>⏰ Sent:</strong> <?= date('Y-m-d H:i:s') ?>
            </div>

            <p>प्रिय <strong><?= htmlspecialchars($details['full_name']) ?></strong>,</p>
            <p>आपने परीक्षा सफलतापूर्वक पास कर ली है! आपका Certificate तैयार है।</p>
            
            <div class="details">
                <h3 style="color: #667eea; margin-top: 0; margin-bottom: 15px;">📋 Certificate विवरण</h3>
                <div class="detail-row">
                    <span class="detail-label">Certificate ID:</span>
                    <span class="detail-value"><strong><?= htmlspecialchars($cert['certificate_id']) ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Exam:</span>
                    <span class="detail-value"><strong><?= htmlspecialchars($details['exam_name']) ?></strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">अंक (Marks):</span>
                    <span class="detail-value"><strong><?= $cert['score'] ?>/32</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">प्रतिशत (Percentage):</span>
                    <span class="detail-value"><strong><?= $cert['percentage'] ?>%</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ग्रेड (Grade):</span>
                    <span class="detail-value"><strong>A+</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Verification Code:</span>
                    <span class="detail-value"><strong style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($cert['verification_code']) ?></strong></span>
                </div>
            </div>
            
            <div class="buttons">
                <a href="<?= $cert_url ?>" class="btn btn-primary" target="_blank">👁️ Certificate देखें</a>
                <a href="<?= $download_url ?>" class="btn btn-success" target="_blank">⬇️ PDF Download करें</a>
                <a href="<?= $verify_url ?>" class="btn btn-secondary" target="_blank">✓ Verify करें</a>
            </div>
            
            <div class="details">
                <h4 style="color: #667eea; margin-top: 0;">📌 अगले कदम (Next Steps)</h4>
                <ul style="margin-left: 20px;">
                    <li>Certificate को online देखें</li>
                    <li>PDF download करके रखें</li>
                    <li>अपने achievement को share करें</li>
                    <li>Certificate को verify करें</li>
                </ul>
            </div>

            <div class="links">
                <h4>🔗 Direct Links (सीधे Links):</h4>
                <div class="link-item">
                    <strong>View Certificate:</strong><br>
                    <a href="<?= $cert_url ?>" target="_blank"><?= $cert_url ?></a>
                </div>
                <div class="link-item">
                    <strong>Download PDF:</strong><br>
                    <a href="<?= $download_url ?>" target="_blank"><?= $download_url ?></a>
                </div>
                <div class="link-item">
                    <strong>Verify Certificate:</strong><br>
                    <a href="<?= $verify_url ?>" target="_blank"><?= $verify_url ?></a>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2026 EXAMs Learning System. सर्वाधिकार सुरक्षित।<br>
            National Skill Training Institute, Kolkata<br>
            <strong>Certificate ID:</strong> <?= htmlspecialchars($cert['certificate_id']) ?></p>
        </div>
    </div>

    <div class="page-header" style="margin-top: 40px;">
        <h3>📧 Email भेजने के तरीके (How to Send Email):</h3>
        <p style="text-align: left; max-width: 700px; margin: 20px auto; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 5px;">
            <strong>OPTION 1: Gmail के साथ</strong><br>
            ✓ Gmail account खोलें<br>
            ✓ Security → App Passwords में जाएं<br>
            ✓ App Password generate करें<br>
            ✓ .env file में डालें<br><br>
            
            <strong>OPTION 2: Mailtrap (Testing)</strong><br>
            ✓ Mailtrap.io पर free account बनाएं<br>
            ✓ SMTP settings copy करें<br>
            ✓ config.php में setup करें<br><br>
            
            <strong>OPTION 3: SendGrid (Production)</strong><br>
            ✓ SendGrid.com पर account बनाएं<br>
            ✓ API Key generate करें<br>
            ✓ Database में store करें
        </p>
    </div>
</body>
</html>
