<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/certificate_generator.php';
requireLogin();

$result_id = (int)($_GET['id'] ?? 0);

// Validate Result Ownership
$query = "SELECT r.*, e.exam_name, t.trade_name, s.subject_name, u.full_name 
          FROM results r 
          JOIN exams e ON r.exam_id = e.id 
          JOIN trades t ON e.trade_id = t.id 
          JOIN subjects s ON e.subject_id = s.id 
          JOIN users u ON r.student_id = u.id 
          WHERE r.id = ? AND r.student_id = ? AND r.is_passed = 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$result_id, $_SESSION['user_id']]);
$result = $stmt->fetch();

if (!$result) {
    die("Invalid Certificate Request or Exam Not Passed.");
}

// Get Certificate
$certStmt = $pdo->prepare("SELECT * FROM certificates WHERE result_id = ? AND status = 'active'");
$certStmt->execute([$result_id]);
$certificate = $certStmt->fetch();

if (!$certificate) {
    die("Certificate not available or has been revoked.");
}

// Generate PDF
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

$verify_url = BASE_URL . '/verify.php?code=' . urlencode($certificate['verification_code']);

// HTML Content for Certificate
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; font-family: "Georgia", serif; background: #fff; }
        .cert-container { width: 1000px; height: 700px; padding: 40px; box-sizing: border-box; position: relative; }
        .cert-border { border: 8px double #003d82; height: 100%; box-sizing: border-box; padding: 40px; text-align: center; position: relative; background: #fafafa; }
        .cert-corner { position: absolute; width: 20px; height: 20px; border: 2px solid #003d82; }
        .cert-corner.tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
        .cert-corner.tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
        .cert-corner.bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
        .cert-corner.br { bottom: 10px; right: 10px; border-left: none; border-top: none; }
        .institution-name { font-size: 24px; color: #003d82; font-weight: bold; margin-bottom: 5px; }
        .institution-city { font-size: 16px; color: #555; margin-bottom: 20px; }
        .cert-title { font-size: 48px; color: #003d82; font-weight: bold; letter-spacing: 3px; margin: 20px 0; }
        .cert-subtitle { font-size: 16px; color: #666; margin-bottom: 20px; letter-spacing: 2px; }
        .date-issued { position: absolute; top: 60px; right: 60px; font-size: 12px; color: #666; }
        .date-issued-label { font-size: 10px; color: #999; }
        .certify-text { font-size: 16px; color: #333; margin: 20px 0; }
        .student-name { font-size: 48px; color: #003d82; font-weight: bold; font-style: italic; margin: 15px 0; border-bottom: 2px solid #003d82; padding-bottom: 10px; }
        .achievement-text { font-size: 14px; color: #333; line-height: 1.8; margin: 20px 0; }
        .details-table { width: 100%; margin: 25px 0; border-collapse: collapse; font-size: 13px; }
        .details-table td { padding: 8px 15px; border-bottom: 1px solid #ddd; }
        .details-label { text-align: left; font-weight: bold; color: #003d82; width: 30%; }
        .details-value { text-align: left; color: #333; }
        .footer-row { display: flex; justify-content: space-around; align-items: flex-end; margin-top: 30px; padding: 0 20px; }
        .sig-box { text-align: center; width: 150px; }
        .sig-line { border-top: 1px solid #000; height: 40px; margin-bottom: 5px; }
        .sig-text { font-size: 11px; color: #333; font-weight: bold; }
    </style>
</head>
<body>
    <div class="cert-container">
        <div class="cert-border">
            <div class="cert-corner tl"></div>
            <div class="cert-corner tr"></div>
            <div class="cert-corner bl"></div>
            <div class="cert-corner br"></div>
            
            <!-- Header -->
            <div style="margin-bottom: 10px;">
                <div class="institution-name">National Skill Training Institute</div>
                <div class="institution-city">Kolkata</div>
            </div>

            <div class="cert-title">CERTIFICATE</div>
            <div class="cert-subtitle">OF ACHIEVEMENT</div>

            <div class="date-issued">
                <div class="date-issued-label">Date of Issue:</div>
                <strong>' . date('d M Y', strtotime($certificate['issued_at'])) . '</strong>
            </div>

            <!-- Certification Statement -->
            <div class="certify-text">This is to certify that</div>
            
            <!-- Student Name -->
            <div class="student-name">' . htmlspecialchars($result['full_name']) . '</div>

            <!-- Achievement Text -->
            <div class="achievement-text">
                has successfully appeared and passed the examination conducted by National Skill Training Institute, Kolkata.
            </div>

            <!-- Details Table -->
            <table class="details-table">
                <tr>
                    <td class="details-label">EXAM TYPE</td>
                    <td class="details-value">: ' . htmlspecialchars($result['exam_name']) . '</td>
                </tr>
                <tr>
                    <td class="details-label">MARKS OBTAINED</td>
                    <td class="details-value">: ' . (float)$certificate['obtained_marks'] . ' / ' . (float)$certificate['total_marks'] . '</td>
                </tr>
                <tr>
                    <td class="details-label">PERCENTAGE</td>
                    <td class="details-value">: ' . (float)$certificate['percentage'] . '%</td>
                </tr>
                <tr>
                    <td class="details-label">GRADE</td>
                    <td class="details-value">: ' . htmlspecialchars($certificate['grade'] ?? 'D') . '</td>
                </tr>
                <tr>
                    <td class="details-label">CERTIFICATE ID</td>
                    <td class="details-value">: ' . htmlspecialchars($certificate['certificate_id']) . '</td>
                </tr>
            </table>

            <!-- Footer with Signature -->
            <div class="footer-row">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-text">Administrator</div>
                </div>
                <div style="text-align: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" style="width: 40px; height: 40px;">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#003d82" stroke-width="2"/>
                        <path d="M50 20 L65 35 L50 50 L35 35 Z" fill="#003d82"/>
                        <rect x="40" y="55" width="20" height="25" fill="#003d82"/>
                    </svg>
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-text">Authorized Officer</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
';

// Create PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output PDF
$filename = 'Certificate_' . $certificate['certificate_id'] . '_' . date('Y-m-d') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $dompdf->output();
exit;
