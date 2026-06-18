<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/certificate_generator.php';
requireLogin();

$result_id = (int)($_GET['id'] ?? 0);

// Validate Result Ownership (or allow Admin/Moderator to view)
$query = "SELECT r.*, e.exam_name, t.trade_name, s.subject_name, u.full_name 
          FROM results r 
          JOIN exams e ON r.exam_id = e.id 
          JOIN trades t ON e.trade_id = t.id 
          JOIN subjects s ON e.subject_id = s.id 
          JOIN users u ON r.student_id = u.id 
          WHERE r.id = ?";

$params = [$result_id];
if (hasRole('student')) {
    $query .= " AND r.student_id = ?";
    $params[] = $_SESSION['user_id'];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$result = $stmt->fetch();

if (!$result || !$result['is_passed']) {
    die("Invalid Certificate Request or Exam Not Passed.");
}

// Check if Certificate exists
$certStmt = $pdo->prepare("SELECT * FROM certificates WHERE result_id = ?");
$certStmt->execute([$result_id]);
$certificate = $certStmt->fetch();

// Auto-Generate if missing
if (!$certificate) {
    try {
        $result_data = [
            'obtained_marks' => $result['obtained_marks'],
            'total_marks' => $result['total_marks'],
            'percentage' => $result['percentage'],
            'created_at' => $result['created_at'] ?? date('Y-m-d H:i:s')
        ];
        
        $cert_result = insertCertificate($pdo, $result['student_id'], $result['exam_id'], $result_id, $result_data, $_SESSION['user_id'] ?? null);
        
        if ($cert_result && $cert_result['success']) {
            // Fetch newly generated certificate
            $certStmt->execute([$result_id]);
            $certificate = $certStmt->fetch();
        } else {
            die("Error generating certificate. Please try again or contact support.");
        }
    } catch (PDOException $e) {
        error_log("Certificate Generation Error: " . $e->getMessage());
        die("Error generating certificate. Please try again or contact support.");
    }
}

if ($certificate['status'] === 'revoked') {
    die("<div style='text-align:center; padding: 50px; font-family: Arial;'><h3 style='color: #dc3545;'>Certificate Revoked</h3><p>This certificate has been revoked by the administration.</p></div>");
}

if (!$certificate || !isset($certificate['certificate_id'])) {
    die("<div style='text-align:center; padding: 50px; font-family: Arial;'><h3 style='color: #dc3545;'>Error</h3><p>Certificate could not be retrieved. Please contact support.</p></div>");
}

$verify_url = BASE_URL . '/verify.php?code=' . urlencode($certificate['verification_code']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Achievement - <?= htmlspecialchars($result['full_name']) ?></title>
    <style>
        body { background: #e5e7eb; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; font-family: 'Georgia', serif; }
        .cert-container { background: #fff; width: 1000px; height: 700px; padding: 40px; box-sizing: border-box; position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
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
        .qr-box { position: absolute; bottom: 30px; right: 40px; text-align: center; }
        .qr-box img { width: 90px; height: 90px; }
        .qr-text { font-size: 11px; color: #666; margin-top: 5px; font-weight: bold; }
        @media print {
            body { background: #fff; margin: 0; }
            .cert-container { box-shadow: none; width: 100%; height: 100%; }
            .no-print { display: none; }
        }
        .btn-print { position: fixed; top: 20px; right: 20px; background: #0056D2; color: #fff; padding: 10px 20px; border: none; font-size: 16px; border-radius: 5px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: Arial; z-index: 1000; }
        .btn-print:hover { background: #0044a8; }
    </style>
</head>
<body>
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 1000;">
        <a href="<?= BASE_URL ?>/student/certificate_download.php?id=<?= $result_id ?>" class="btn-print" style="background: #28a745; text-decoration: none;">↓ Download PDF</a>
        <button class="btn-print" onclick="window.print()" style="background: #0056D2;">🖨️ Print</button>
    </div>
    
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
                <strong><?= date('d M Y', strtotime($certificate['issued_at'])) ?></strong>
            </div>

            <!-- Certification Statement -->
            <div class="certify-text">This is to certify that</div>
            
            <!-- Student Name -->
            <div class="student-name"><?= htmlspecialchars($result['full_name']) ?></div>

            <!-- Achievement Text -->
            <div class="achievement-text">
                has successfully appeared and passed the examination conducted by National Skill Training Institute, Kolkata.
            </div>

            <!-- Details Table -->
            <table class="details-table">
                <tr>
                    <td class="details-label">EXAM TYPE</td>
                    <td class="details-value">: <?= htmlspecialchars($result['exam_name']) ?></td>
                </tr>
                <tr>
                    <td class="details-label">MARKS OBTAINED</td>
                    <td class="details-value">: <?= (float)($certificate['obtained_marks'] ?? $result['obtained_marks']) ?> / <?= (float)($certificate['total_marks'] ?? $result['total_marks']) ?></td>
                </tr>
                <tr>
                    <td class="details-label">PERCENTAGE</td>
                    <td class="details-value">: <?= (float)$result['percentage'] ?>%</td>
                </tr>
                <tr>
                    <td class="details-label">GRADE</td>
                    <td class="details-value">: <?= htmlspecialchars($certificate['grade'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td class="details-label">CERTIFICATE ID</td>
                    <td class="details-value">: <?= htmlspecialchars($certificate['certificate_id']) ?></td>
                </tr>
            </table>

            <!-- Footer with Signature -->
            <div class="footer-row">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-text">Administrator</div>
                </div>
                <div style="text-align: center;">
                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='none' stroke='%23003d82' stroke-width='2'/%3E%3Cpath d='M50 20 L65 35 L50 50 L35 35 Z' fill='%23003d82'/%3E%3Crect x='40' y='55' width='20' height='25' fill='%23003d82'/%3E%3C/svg%3E" style="width: 40px; height: 40px;" alt="Institute Logo">
                </div>
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <div class="sig-text">Authorized Officer</div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($verify_url) ?>" alt="QR Code">
                <div class="qr-text">Scan to Verify Certificate</div>
            </div>
        </div>
    </div>
</body>
</html>