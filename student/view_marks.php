<?php
/**
 * Student: View Total Marks (Theory + Practical)
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    http_response_code(403);
    die('Access Denied');
}

$student_id = $_SESSION['user_id'];
$trade_id = $_SESSION['trade_id'] ?? 0;

// Get all practical exams with combined marks
$stmt = $pdo->prepare("
    SELECT 
        pe.id, pe.title, pe.theory_marks, pe.practical_marks,
        (pe.theory_marks + pe.practical_marks) as total_marks,
        s.subject_name, t.trade_name,
        ps.id as submission_id, ps.submitted_at,
        COALESCE(pm.marks_obtained, 0) as practical_obtained,
        COALESCE(pm.result_status, 'pending') as practical_status,
        COALESCE(c.total_marks, 0) as cert_total,
        COALESCE(c.percentage, 0) as cert_percentage,
        COALESCE(c.is_passed, 0) as cert_passed,
        COALESCE(c.certificate_id, NULL) as certificate_id,
        (pe.theory_marks + COALESCE(pm.marks_obtained, 0)) as total_obtained
    FROM practical_exams pe
    JOIN subjects s ON pe.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id AND ps.student_id = ?
    LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
    LEFT JOIN certificates c ON pe.id = c.practical_exam_id AND c.student_id = ?
    WHERE t.id = ? AND pe.status = 'active'
    ORDER BY pe.submission_deadline DESC
");
$stmt->execute([$student_id, $student_id, $trade_id]);
$all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$stats = [
    'total' => count($all_results),
    'submitted' => 0,
    'marked' => 0,
    'passed' => 0,
    'total_marks_possible' => 0,
    'total_marks_obtained' => 0
];

foreach ($all_results as $result) {
    $stats['total_marks_possible'] += $result['total_marks'];
    if ($result['submission_id']) {
        $stats['submitted']++;
        if ($result['practical_obtained'] > 0 || $result['practical_status'] !== 'pending') {
            $stats['marked']++;
            $stats['total_marks_obtained'] += $result['total_obtained'];
        }
    }
    if ($result['certificate_id'] && $result['cert_passed']) {
        $stats['passed']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Marks - CITS LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f7fafc; font-family: 'Segoe UI'; }
        .header { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2rem; font-weight: 700; color: #667eea; }
        .stat-label { color: #718096; font-size: 0.9rem; margin-top: 0.5rem; }
        .marks-table { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .marks-table table { width: 100%; border-collapse: collapse; }
        .marks-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .marks-table th { padding: 1rem; font-weight: 600; text-align: left; }
        .marks-table td { padding: 1rem; border-bottom: 1px solid #e2e8f0; }
        .marks-table tbody tr:hover { background: #f7fafc; }
        .mark-box { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; background: #e7f3ff; color: #0066cc; font-weight: 600; }
        .mark-box.total { background: #d5f4e6; color: #27ae60; }
        .mark-box.pending { background: #fef5e7; color: #f39c12; }
        .badge-status { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .badge-pass { background: #d5f4e6; color: #27ae60; }
        .badge-fail { background: #fadbd8; color: #c0392b; }
        .badge-pending { background: #fef5e7; color: #f39c12; }
        .btn-download { padding: 0.4rem 0.8rem; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; text-decoration: none; }
        .btn-download:hover { background: #5568d3; }
    </style>
</head>
<body class="bg-light">
    <div class="container-lg py-4">
        <div class="header">
            <h1><i class="fas fa-chart-bar me-2"></i>My Total Marks</h1>
            <small style="color: #718096;">Practical + Theory = Total Score</small>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['submitted'] ?>/<?= $stats['total'] ?></div>
                <div class="stat-label">Submitted/Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['marked'] ?></div>
                <div class="stat-label">Marked</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['passed'] ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?= $stats['marked'] > 0 ? round(($stats['total_marks_obtained'] / ($stats['marked'] * (($stats['total_marks_possible'] / $stats['total']) ?? 1))) * 100) : 0 ?>%
                </div>
                <div class="stat-label">Average %</div>
            </div>
        </div>
        
        <!-- Marks Table -->
        <div class="marks-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Practical / Subject</th>
                        <th style="width: 10%;">Theory</th>
                        <th style="width: 10%;">Practical</th>
                        <th style="width: 10%;">Obtained</th>
                        <th style="width: 10%;">Total</th>
                        <th style="width: 10%;">%</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%;">Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_results)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-inbox" style="font-size: 2rem; color: #ccc;"></i>
                                <p style="color: #718096; margin-top: 0.5rem;">No practical exams yet</p>
                            </td>
                        </tr>
                    <?php else:
                        foreach ($all_results as $result):
                            $percentage = $result['total_marks'] > 0 ? round(($result['total_obtained'] / $result['total_marks']) * 100) : 0;
                            $status_class = 'pending';
                            $status_text = 'Pending';
                            
                            if ($result['submission_id'] && $result['practical_obtained'] > 0) {
                                $status_class = $result['practical_status'] === 'pass' ? 'pass' : 'fail';
                                $status_text = ucfirst($result['practical_status']);
                            } elseif (!$result['submission_id']) {
                                $status_text = 'Not Submitted';
                            }
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($result['subject_name']) ?></strong>
                                <br><small style="color: #718096;"><?= htmlspecialchars(substr($result['title'], 0, 40)) ?></small>
                            </td>
                            <td>
                                <div class="mark-box"><?= $result['theory_marks'] ?></div>
                            </td>
                            <td>
                                <div class="mark-box"><?= $result['practical_marks'] ?></div>
                            </td>
                            <td>
                                <div class="mark-box" style="background: #e7f3ff; color: #0066cc;">
                                    <?= $result['practical_obtained'] ?>
                                </div>
                            </td>
                            <td>
                                <div class="mark-box total"><?= $result['total_obtained'] ?> / <?= $result['total_marks'] ?></div>
                            </td>
                            <td>
                                <strong style="color: #667eea; font-size: 1.1rem;"><?= $percentage ?>%</strong>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($result['submission_id'] && $result['practical_obtained'] > 0): ?>
                                    <span class="badge-status badge-<?= $status_class ?>"><?= $status_text ?></span>
                                <?php else: ?>
                                    <span class="badge-status badge-pending"><?= $status_text ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($result['certificate_id']): ?>
                                    <a href="download_certificate.php?id=<?= $result['certificate_id'] ?>" class="btn-download">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.9rem;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Summary -->
        <div class="mt-4 p-3" style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <h6 style="color: #667eea; font-weight: 700;">How Your Marks are Calculated:</h6>
            <ul style="margin: 1rem 0; color: #555;">
                <li><strong>Theory Marks:</strong> Set by your institution (displayed in the Theory column)</li>
                <li><strong>Practical Marks:</strong> Assigned by your teacher after reviewing your submission</li>
                <li><strong>Total:</strong> Theory + Practical marks combined</li>
                <li><strong>Certificate:</strong> Generated automatically when both theory & practical marks are complete</li>
                <li><strong>Email:</strong> You'll receive an email notification with your total marks and certificate</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
