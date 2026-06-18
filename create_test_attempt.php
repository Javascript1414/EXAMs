<?php
require 'includes/db.php';

// Get a student
$stmt = $pdo->query('SELECT id FROM users LIMIT 1 OFFSET 1');
$student = $stmt->fetch();
$student_id = $student['id'];

// Use exam 4 which has questions
$exam_id = 4;

// Create exam attempt
$stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, status, started_at) VALUES (?, ?, 'in_progress', NOW())");
$stmt->execute([$exam_id, $student_id]);
$attempt_id = $pdo->lastInsertId();

// Get questions for this exam
$stmt = $pdo->prepare("SELECT question_id FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

// Create exam_answers for each question
foreach ($questions as $q) {
    $stmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer_status) VALUES (?, ?, 'not_visited')");
    $stmt->execute([$attempt_id, $q['question_id']]);
}

echo "Exam attempt created successfully!<br>";
echo "Attempt ID: $attempt_id<br>";
echo "Student ID: $student_id<br>";
echo "Exam ID: $exam_id<br>";
echo "<br><a href='student/exam_attempt.php?id=$exam_id'>Click here to take exam</a>";
?>
