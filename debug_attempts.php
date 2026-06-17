<?php
require_once 'includes/db.php';

$exam_id = 3;  // From the debug output
$student_id = 3;  // Logged-in student

// Check for exam attempts
echo "<h2>All attempts for Exam ID $exam_id and Student ID $student_id</h2>";
$stmt = $pdo->prepare("SELECT id, status, started_at, submitted_at FROM exam_attempts WHERE exam_id = ? AND student_id = ? ORDER BY id DESC");
$stmt->execute([$exam_id, $student_id]);
$attempts = $stmt->fetchAll();

if (empty($attempts)) {
    echo "No attempts found!";
} else {
    foreach ($attempts as $attempt) {
        echo "Attempt ID {$attempt['id']}: Status = {$attempt['status']}<br>";
    }
}

// Check if there's an active attempt
echo "<h2>Active (in_progress) attempt:</h2>";
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ? AND status = 'in_progress'");
$stmt->execute([$exam_id, $student_id]);
$active = $stmt->fetch();

if ($active) {
    echo "Active Attempt ID: " . $active['id'] . "<br>";
    // Check questions
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_answers WHERE attempt_id = ?");
    $stmt->execute([$active['id']]);
    $qCount = $stmt->fetch();
    echo "Questions: " . $qCount['cnt'] . "<br>";
} else {
    echo "<p style='color:red'><strong>NO ACTIVE ATTEMPT!</strong></p>";
    echo "<p>There is no in_progress attempt for this exam. Need to start/create one.</p>";
}
?>
