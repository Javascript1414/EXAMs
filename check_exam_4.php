<?php
require_once 'includes/db.php';

$exam_id = 4;

// Check exam details
$stmt = $pdo->prepare("SELECT id, exam_name FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

echo "<h2>Exam ID 4 Analysis</h2>";
if ($exam) {
    echo "<p><strong>Exam Name:</strong> " . htmlspecialchars($exam['exam_name']) . "</p>";
} else {
    echo "<p style='color:red'>Exam not found!</p>";
    exit;
}

// Check questions
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$result = $stmt->fetch();
echo "<p><strong>Questions assigned:</strong> " . $result['cnt'] . "</p>";

if ($result['cnt'] == 0) {
    echo "<p style='color:red'><strong>❌ Problem: No questions assigned!</strong></p>";
}

// Check total questions in system
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM questions");
$qCount = $stmt->fetch();
echo "<p><strong>Total questions in system:</strong> " . $qCount['cnt'] . "</p>";
?>
