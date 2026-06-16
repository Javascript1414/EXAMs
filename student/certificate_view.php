<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
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
        $cert_id = 'CERT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
        $verify_code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        
        $insert = $pdo->prepare("INSERT INTO certificates (certificate_id, student_id, exam_id, result_id, score, percentage, verification_code, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $generated_by = $_SESSION['user_id'] ?? null;
        $insert->execute([$cert_id, $result['student_id'], $result['exam_id'], $result_id, $result['obtained_marks'], $result['percentage'], $verify_code, $generated_by]);
        
        // Flag result table
        $pdo->prepare("UPDATE results SET certificate_generated = 1 WHERE id = ?")->execute([$result_id]);
        
        // Fetch newly generated
        $certStmt->execute([$result_id]);
        $certificate = $certStmt->fetch();
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
        .cert-border { border: 12px double #0056D2; height: 100%; box-sizing: border-box; padding: 40px; text-align: center; position: relative; background: #fffaf0; }
        .title { font-size: 48px; color: #0056D2; margin-bottom: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
        .subtitle { font-size: 20px; color: #4b5563; margin-bottom: 40px; letter-spacing: 1px; }
        .presented-to { font-size: 18px; color: #6b7280; font-style: italic; margin-bottom: 10px; }
        .student-name { font-size: 42px; color: #111827; font-weight: bold; border-bottom: 2px solid #d1d5db; display: inline-block; padding-bottom: 5px; margin-bottom: 30px; }
        .reason { font-size: 18px; color: #4b5563; margin-bottom: 20px; line-height: 1.6; }
        .course-name { font-size: 26px; color: #0056D2; font-weight: bold; margin-bottom: 40px; }
        .footer-grid { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 50px; padding: 0 40px; }
        .sig-box { text-align: center; width: 200px; border-top: 1px solid #000; padding-top: 10px; font-family: 'Arial', sans-serif; font-size: 14px; color: #374151; }
        .meta-data { position: absolute; bottom: 15px; left: 15px; text-align: left; font-family: 'Arial', sans-serif; font-size: 11px; color: #9ca3af; }
        .qr-box { position: absolute; bottom: 30px; right: 40px; text-align: center; font-family: 'Arial', sans-serif; font-size: 12px; }
        .qr-box img { width: 100px; height: 100px; margin-bottom: 5px; }
        @media print {
            body { background: #fff; margin: 0; }
            .cert-container { box-shadow: none; width: 100%; height: 100%; page-break-after: avoid; }
            .no-print { display: none; }
        }
        .btn-print { position: fixed; top: 20px; right: 20px; background: #0056D2; color: #fff; padding: 10px 20px; border: none; font-size: 16px; border-radius: 5px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: Arial; }
        .btn-print:hover { background: #0044a8; }
    </style>
</head>
<body>
    <button class="no-print btn-print" onclick="window.print()">Print / Save PDF</button>
    
    <div class="cert-container">
        <div class="cert-border">
            <div class="title">Certificate of Achievement</div>
            <div class="subtitle">This verifies the successful completion of the assessment</div>
            
            <div class="presented-to">Proudly presented to</div>
            <div class="student-name"><?= htmlspecialchars($result['full_name']) ?></div>
            
            <div class="reason">For passing the official examination and demonstrating proficiency in</div>
            <div class="course-name"><?= htmlspecialchars($result['exam_name']) ?><br><span style="font-size: 18px; color: #4b5563; font-weight: normal;"><?= htmlspecialchars($result['subject_name']) ?> &bull; <?= htmlspecialchars($result['trade_name']) ?></span></div>
            
            <div class="footer-grid">
                <div class="sig-box">Issue Date<br><strong style="color:#000; font-size:16px;"><?= date('F j, Y', strtotime($certificate['issued_at'])) ?></strong></div>
                <div class="sig-box">Score Achieved<br><strong style="color:#000; font-size:16px;"><?= (float)$certificate['percentage'] ?>%</strong></div>
            </div>

            <div class="meta-data">Certificate ID: <?= htmlspecialchars($certificate['certificate_id']) ?><br>Verify at: <?= BASE_URL ?>/verify.php</div>
            <div class="qr-box">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($verify_url) ?>" alt="QR Code">
                <br>Scan to Verify
            </div>
        </div>
    </div>
</body>
</html>