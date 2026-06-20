<?php
/**
 * Student: Download Certificate
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    http_response_code(403);
    die('Access Denied');
}

$certificate_id = $_GET['id'] ?? null;

if (!$certificate_id) {
    http_response_code(400);
    die('Certificate ID not provided');
}

// Verify student owns this certificate
$stmt = $pdo->prepare("
    SELECT c.*, s.subject_name, t.trade_name, u.full_name
    FROM certificates c
    JOIN subjects s ON c.subject_id = s.id
    JOIN trades t ON c.trade_id = t.id
    JOIN users u ON c.student_id = u.id
    WHERE c.certificate_id = ? AND c.student_id = ?
");
$stmt->execute([$certificate_id, $_SESSION['user_id']]);
$cert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cert) {
    http_response_code(404);
    die('Certificate not found');
}

// Check if PDF library is available, otherwise generate HTML
if (!file_exists('../vendor/autoload.php')) {
    // HTML Certificate
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Certificate - <?= htmlspecialchars($cert['full_name']) ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Georgia, serif; background: #f0f0f0; }
            .certificate-container {
                width: 100%;
                max-width: 900px;
                margin: 20px auto;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            }
            .certificate {
                background: white;
                padding: 60px;
                border: 8px solid #667eea;
                border-radius: 8px;
                text-align: center;
                position: relative;
            }
            .header { margin-bottom: 30px; }
            .header-title { font-size: 3rem; color: #667eea; font-weight: bold; }
            .header-subtitle { font-size: 1.5rem; color: #764ba2; margin-top: 10px; }
            .divider { 
                width: 100px;
                height: 3px;
                background: #667eea;
                margin: 20px auto;
            }
            .body { margin: 40px 0; line-height: 1.8; }
            .body .intro { font-size: 1.1rem; color: #333; margin-bottom: 20px; }
            .body .student-name { 
                font-size: 2rem;
                color: #667eea;
                font-weight: bold;
                margin: 20px 0;
                text-transform: uppercase;
            }
            .body .text { font-size: 1rem; color: #555; }
            .marks-details {
                background: #f0f7ff;
                padding: 20px;
                border-radius: 8px;
                margin: 30px 0;
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                text-align: center;
            }
            .mark-item { padding: 15px; }
            .mark-item .label { font-size: 0.9rem; color: #666; text-transform: uppercase; font-weight: bold; }
            .mark-item .value { font-size: 1.8rem; color: #667eea; font-weight: bold; margin-top: 8px; }
            .footer {
                margin-top: 50px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 100px;
                text-align: center;
            }
            .footer-item { border-top: 2px solid #333; padding-top: 10px; }
            .footer-item .label { font-size: 0.9rem; color: #555; }
            .footer-item .date { font-size: 0.9rem; color: #666; margin-top: 5px; }
            .cert-id { 
                font-size: 0.8rem;
                color: #999;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            .status-badge {
                display: inline-block;
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: bold;
                margin: 10px 0;
            }
            .status-badge.pass { background: #d5f4e6; color: #27ae60; }
            .status-badge.fail { background: #fadbd8; color: #c0392b; }
            .print-btn {
                margin: 20px 0;
                padding: 10px 20px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 1rem;
            }
            @media print {
                body { background: white; }
                .print-btn { display: none; }
                .certificate-container { max-width: 100%; margin: 0; padding: 0; background: white; }
            }
        </style>
    </head>
    <body>
        <div class="certificate-container">
            <div class="certificate">
                <div class="header">
                    <div class="header-title">Certificate of Achievement</div>
                    <div class="header-subtitle">Practical Exam Completion</div>
                </div>
                <div class="divider"></div>
                
                <div class="body">
                    <div class="intro">This is to certify that</div>
                    <div class="student-name"><?= htmlspecialchars($cert['full_name']) ?></div>
                    <div class="text">Has successfully completed the practical examination in</div>
                    <div style="font-size: 1.3rem; color: #667eea; font-weight: bold; margin: 15px 0;">
                        <?= htmlspecialchars($cert['subject_name']) ?>
                    </div>
                    <div class="text">Trade: <strong><?= htmlspecialchars($cert['trade_name']) ?></strong></div>
                    
                    <div class="marks-details">
                        <div class="mark-item">
                            <div class="label">Theory Marks</div>
                            <div class="value"><?= $cert['theory_marks'] ?></div>
                        </div>
                        <div class="mark-item">
                            <div class="label">Practical Marks</div>
                            <div class="value"><?= $cert['practical_marks'] ?></div>
                        </div>
                        <div class="mark-item">
                            <div class="label">Total Obtained</div>
                            <div class="value" style="color: #27ae60;"><?= $cert['total_marks'] ?></div>
                        </div>
                        <div class="mark-item">
                            <div class="label">Percentage</div>
                            <div class="value"><?= $cert['percentage'] ?>%</div>
                        </div>
                    </div>
                    
                    <div class="status-badge <?= $cert['is_passed'] ? 'pass' : 'fail' ?>">
                        <?= $cert['is_passed'] ? 'PASSED' : 'FAILED' ?>
                    </div>
                    
                    <div style="margin-top: 30px; color: #666;">
                        Issued on: <strong><?= date('F j, Y', strtotime($cert['issued_at'])) ?></strong>
                    </div>
                </div>
                
                <div class="footer">
                    <div class="footer-item">
                        <div class="label">Authorized Signature</div>
                        <div class="date">Institution Authority</div>
                    </div>
                    <div class="footer-item">
                        <div class="label">Official Seal</div>
                        <div class="date">CITS LMS</div>
                    </div>
                </div>
                
                <div class="cert-id">
                    <strong>Certificate ID:</strong> <?= htmlspecialchars($cert['certificate_id']) ?><br>
                    <strong>Verification:</strong> This certificate can be verified at citslms.com/verify
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button class="print-btn" onclick="window.print()">🖨️ Print Certificate</button>
            <a href="javascript:history.back()" style="margin-left: 10px; padding: 10px 20px; background: #999; color: white; border: none; border-radius: 5px; text-decoration: none; cursor: pointer;">← Go Back</a>
        </div>
        
        <script>
            // Update download record in database
            fetch('update_certificate_download.php?id=<?= $certificate_id ?>', {method: 'POST'});
        </script>
    </body>
    </html>
    <?php
} else {
    // Use TCPDF for PDF generation if available
    require_once '../vendor/autoload.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    
    $html = "
    <div style='text-align:center; padding: 40px;'>
        <h1 style='color: #667eea;'>Certificate of Achievement</h1>
        <h2 style='color: #764ba2;'>Practical Exam Completion</h2>
        <hr>
        <p style='font-size: 18px; margin: 30px 0;'>This is to certify that</p>
        <h2 style='color: #667eea;'>" . htmlspecialchars($cert['full_name']) . "</h2>
        <p style='font-size: 14px;'>Has successfully completed the practical examination in</p>
        <h3>" . htmlspecialchars($cert['subject_name']) . "</h3>
        <p>Trade: <strong>" . htmlspecialchars($cert['trade_name']) . "</strong></p>
        
        <table border='1' cellpadding='10' style='width: 100%; margin: 30px 0; text-align: center;'>
            <tr>
                <td><strong>Theory</strong><br>" . $cert['theory_marks'] . "</td>
                <td><strong>Practical</strong><br>" . $cert['practical_marks'] . "</td>
                <td><strong>Total</strong><br>" . $cert['total_marks'] . "</td>
                <td><strong>Percentage</strong><br>" . $cert['percentage'] . "%</td>
            </tr>
        </table>
        
        <h3 style='color: " . ($cert['is_passed'] ? '#27ae60' : '#c0392b') . ";'>" . ($cert['is_passed'] ? 'PASSED' : 'FAILED') . "</h3>
        
        <p style='margin-top: 40px; color: #666;'>
            Issued: " . date('F j, Y', strtotime($cert['issued_at'])) . "<br>
            Certificate ID: " . htmlspecialchars($cert['certificate_id']) . "
        </p>
    </div>";
    
    $pdf->writeHTML($html);
    $pdf->Output('certificate_' . $certificate_id . '.pdf', 'D');
}
?>
