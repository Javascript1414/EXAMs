<?php
require_once 'includes/db.php';

echo "<h2>Debug Exam 4 Status</h2>";

// Check all attempts for exam_id 4
$stmt = $pdo->prepare("SELECT id, status, student_id FROM exam_attempts WHERE exam_id = 4 ORDER BY id DESC");
$stmt->execute();
$attempts = $stmt->fetchAll();

echo "<p><strong>All attempts for exam_id 4:</strong></p>";
if (empty($attempts)) {
    echo "<p style='color:green'>✓ No attempts found</p>";
} else {
    foreach ($attempts as $att) {
        echo "<p>Attempt ID: " . $att['id'] . " | Status: " . $att['status'] . " | Student: " . $att['student_id'] . "</p>";
    }
}

// Check in_progress specifically
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_attempts WHERE exam_id = 4 AND status = 'in_progress'");
$stmt->execute();
$result = $stmt->fetch();

echo "<p><strong>In-progress attempts: " . $result['cnt'] . "</strong></p>";

if ($result['cnt'] > 0) {
    echo "<p style='color:red'><strong>Need to clear these!</strong></p>";
    $pdo->prepare("DELETE FROM exam_attempts WHERE exam_id = 4 AND status = 'in_progress'")->execute();
    echo "<p>✓ Deleted in_progress attempts</p>";
}
?>
