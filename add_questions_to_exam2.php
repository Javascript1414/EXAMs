<?php
require 'includes/db.php';

echo "<h2>Adding Questions to Exam 2...</h2>";

// Get all questions
$stmt = $pdo->prepare("SELECT id FROM questions");
$stmt->execute();
$questions = $stmt->fetchAll();

if (empty($questions)) {
    echo "❌ No questions found in database!";
    exit;
}

// Clear existing exam_questions for exam 2
$stmt = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = 2");
$stmt->execute();
echo "Cleared old questions<br>";

// Add all questions to exam 2
foreach ($questions as $q) {
    $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
    $result = $stmt->execute([2, $q['id']]);
    if ($result) {
        echo "✅ Added Q" . $q['id'] . " to Exam 2<br>";
    } else {
        echo "❌ Failed to add Q" . $q['id'] . "<br>";
    }
}

echo "<br><strong>Done!</strong><br>";
echo "<a href='check_exam2_questions.php'>← Go Back</a> | ";
echo "<a href='student/exam_attempt.php?id=2'>📝 Try Exam 2</a>";
?>
