<?php
require_once 'includes/db.php';

$exam_id = 4;

echo "<h2>🔧 Fixing Exam ID 4</h2>";

// Step 1: Get all available questions
$stmt = $pdo->query("SELECT id FROM questions");
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<p>Step 1: Found " . count($questions) . " questions in system</p>";

// Step 2: Clear old questions from exam 4
$pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$exam_id]);
echo "<p>Step 2: ✓ Cleared old questions from exam</p>";

// Step 3: Assign all questions to exam 4
$insert = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
foreach ($questions as $qid) {
    $insert->execute([$exam_id, $qid]);
}
echo "<p>Step 3: ✓ Assigned " . count($questions) . " questions to exam 4</p>";

// Step 4: Clear old submitted attempts
$stmt = $pdo->prepare("DELETE FROM exam_attempts WHERE exam_id = ? AND status = 'submitted'");
$stmt->execute([$exam_id]);
$affected = $stmt->rowCount();
echo "<p>Step 4: ✓ Cleared $affected old submitted attempt(s)</p>";

// Step 5: Verify
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$result = $stmt->fetch();

echo "<br><p style='color:green; font-weight:bold; font-size:16px;'>✅ EXAM 4 COMPLETELY FIXED!</p>";
echo "<p><strong>Exam 4 now has " . $result['cnt'] . " questions ready to go!</strong></p>";
echo "<p><a href='student/exams.php' class='btn btn-primary' style='margin-top:10px;'>Go to Exams</a></p>";
?>
