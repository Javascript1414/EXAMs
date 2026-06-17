<?php
require_once 'includes/db.php';

$exam_id = 3;

// Get all available questions
$stmt = $pdo->query("SELECT id FROM questions");
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Adding Questions to Exam $exam_id</h2>";
echo "Found " . count($questions) . " questions to add.<br><br>";

// Insert into exam_questions
$insert = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");

foreach ($questions as $qid) {
    $insert->execute([$exam_id, $qid]);
    echo "✓ Added Question $qid<br>";
}

echo "<br><p style='color:green; font-weight:bold;'>✓ SUCCESS! Questions are now assigned to the exam!</p>";

// Verify
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$result = $stmt->fetch();
echo "<p><strong>Verified: Exam now has " . $result['cnt'] . " questions</strong></p>";
?>
