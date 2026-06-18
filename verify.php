<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$code = sanitizeInput($_GET['code'] ?? '');
$certData = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($code)) {
    $searchCode = !empty($code) ? $code : sanitizeInput($_POST['verification_code'] ?? '');
    
    if (empty($searchCode)) {
        $error = "Please enter a valid verification code.";
    } else {
        $stmt = $pdo->prepare("
            SELECT c.*, u.full_name, e.exam_name, t.trade_name 
            FROM certificates c 
            JOIN users u ON c.student_id = u.id 
            JOIN exams e ON c.exam_id = e.id 
            JOIN trades t ON e.trade_id = t.id 
            WHERE c.verification_code = ? OR c.certificate_id = ?
        ");
        $stmt->execute([$searchCode, $searchCode]);
        $certData = $stmt->fetch();
        
        if (!$certData) {
            $error = "No certificate found matching that code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 font-monospace">
    <div class="card border-0 shadow p-5 w-100" style="max-width: 600px; font-family: 'Inter', sans-serif;">
        <div class="text-center mb-4"><i data-lucide="shield-check" class="text-primary mb-2" style="width: 50px; height: 50px;"></i><h3 class="fw-bold">Certificate Verification</h3><p class="text-muted">Enter the Unique Certificate ID or Verification Code below to validate authenticity.</p></div>
        
        <form method="POST" action="verify.php" class="d-flex mb-4">
            <input type="text" name="verification_code" class="form-control form-control-lg me-2" placeholder="e.g. CERT-A1B2C3D4..." required value="<?= htmlspecialchars($code) ?>">
            <button type="submit" class="btn btn-primary px-4 fw-bold">Verify</button>
        </form>

        <?php if ($error): ?><div class="alert alert-danger text-center fw-semibold"><i data-lucide="x-circle" class="me-2" style="width:18px;"></i> <?= $error ?></div><?php endif; ?>

        <?php if ($certData): ?>
            <?php if ($certData['status'] === 'revoked'): ?>
                <div class="alert alert-danger text-center fw-bold fs-5"><i data-lucide="alert-triangle" class="me-2 text-danger"></i> CERTIFICATE REVOKED</div>
            <?php else: ?>
                <div class="alert alert-success border-success text-center">
                    <h4 class="fw-bold text-success mb-3"><i data-lucide="check-circle" class="me-2" style="width: 24px; height: 24px;"></i> VALID CERTIFICATE</h4>
                    <div class="text-dark fs-5 fw-semibold mb-1"><?= htmlspecialchars($certData['full_name']) ?></div>
                    <div class="text-muted mb-3"><?= htmlspecialchars($certData['exam_name']) ?> &bull; <?= htmlspecialchars($certData['trade_name']) ?></div>
                    <div class="row text-center mt-4 border-top pt-3"><div class="col-6 border-end"><small class="text-muted text-uppercase d-block">Score Achieved</small><span class="fw-bold fs-5"><?= (float)$certData['percentage'] ?>%</span></div><div class="col-6"><small class="text-muted text-uppercase d-block">Issue Date</small><span class="fw-bold fs-5"><?= date('M d, Y', strtotime($certData['issued_at'])) ?></span></div></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>lucide.createIcons();</script>
</body></html>