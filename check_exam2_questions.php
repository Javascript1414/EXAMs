<?php
require 'includes/db.php';

echo "<h3>Exam 2 - Questions Status</h3>";

// Check exam 2 details
$stmt = $pdo->prepare("SELECT id, exam_name FROM exams WHERE id = 2");
$stmt->execute();
$exam = $stmt->fetch();
echo "Exam Name: " . $exam['exam_name'] . "<br>";

// Check how many questions are in exam_questions for exam 2
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
$stmt->execute();
$count = $stmt->fetch();
echo "Questions linked: " . $count['cnt'] . "<br><br>";

// Check all questions in database
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM questions");
$stmt->execute();
$total = $stmt->fetch();
echo "Total questions in database: " . $total['cnt'] . "<br>";

// List all questions
$stmt = $pdo->prepare("SELECT id, question_text FROM questions LIMIT 10");
$stmt->execute();
$questions = $stmt->fetchAll();
echo "<h4>Available Questions:</h4>";
echo "<ul>";
foreach ($questions as $q) {
    echo "<li>Q" . $q['id'] . ": " . substr($q['question_text'], 0, 50) . "...</li>";
}
echo "</ul>";

// Check which exams have questions
$stmt = $pdo->prepare("SELECT DISTINCT exam_id, COUNT(*) as cnt FROM exam_questions GROUP BY exam_id");
$stmt->execute();
$examsWithQ = $stmt->fetchAll();
echo "<h4>Other Exams with Questions:</h4>";
foreach ($examsWithQ as $e) {
    echo "Exam " . $e['exam_id'] . ": " . $e['cnt'] . " questions<br>";
}

echo "<br><h4>Action:</h4>";
echo "Add questions to Exam 2? <a href='add_questions_to_exam2.php' style='background: #2e7d32; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none;'>Add Questions</a>";
?>
