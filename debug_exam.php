<?php
require 'includes/db.php';

$exam_id = 4;

// Check exam_answers
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_answers WHERE id = (SELECT id FROM exam_attempts WHERE exam_id = ? LIMIT 1)");
$stmt->execute([$exam_id]);
$r1 = $stmt->fetch();
echo "exam_answers count: " . $r1['cnt'] . "<br>";

// Check exam_questions
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$r2 = $stmt->fetch();
echo "exam_questions count: " . $r2['cnt'] . "<br>";

// Check what questions are linked to this exam
$stmt = $pdo->prepare("SELECT eq.id, eq.question_id, q.question_text FROM exam_questions eq JOIN questions q ON eq.question_id = q.id WHERE eq.exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();
echo "Questions in exam_questions for exam $exam_id:<br>";
foreach ($questions as $q) {
    echo "  - ID: " . $q['question_id'] . " | Text: " . $q['question_text'] . "<br>";
}
?>
