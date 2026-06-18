<?php
require 'includes/db.php';

$exam_id = 4;

// Delete old incomplete attempt
$stmt = $pdo->prepare("DELETE FROM exam_attempts WHERE exam_id = ? AND student_id = 2");
$stmt->execute([$exam_id]);

// Get questions for exam
$stmt = $pdo->prepare("SELECT question_id FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

// Create new exam attempt
$stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, status, started_at) VALUES (?, 2, 'in_progress', NOW())");
$stmt->execute([$exam_id]);
$attempt_id = $pdo->lastInsertId();

// Create exam_answers for all questions
foreach ($questions as $q) {
    $stmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer_status) VALUES (?, ?, 'not_visited')");
    $stmt->execute([$attempt_id, $q['question_id']]);
}

echo "✅ Exam attempt created!<br>";
echo "Attempt ID: $attempt_id<br>";
echo "Questions added: " . count($questions) . "<br>";
echo "<br><a href='student/exam_attempt.php?id=$exam_id'>✓ Click to start exam</a>";
?>
