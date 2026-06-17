<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireRole('student');

// Delete ALL old attempts for exam_id = 4 (the exam, not attempt)
// First, let's find which exam_id the old attempt was for
$stmt = $pdo->prepare("SELECT exam_id FROM exam_attempts WHERE id = 4 LIMIT 1");
$stmt->execute();
$oldAttempt = $stmt->fetch();

if ($oldAttempt) {
    echo "<h2>Clearing Old Data</h2>";
    echo "<p>Old attempt (ID 4) was for Exam ID: " . $oldAttempt['exam_id'] . "</p>";
    
    // Delete the old attempt and its answers
    $pdo->prepare("DELETE FROM exam_answers WHERE attempt_id = 4")->execute();
    echo "<p>✓ Deleted exam answers</p>";
    
    $pdo->prepare("DELETE FROM exam_attempts WHERE id = 4")->execute();
    echo "<p>✓ Deleted old attempt</p>";
}

// Now redirect to exams page to start fresh
echo "<p style='color:green; font-weight:bold;'>✅ Old attempt cleared! Redirecting...</p>";
echo "<meta http-equiv='refresh' content='2;url=student/exams.php'>";
?>
