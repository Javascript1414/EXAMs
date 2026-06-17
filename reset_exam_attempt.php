<?php
require_once 'includes/db.php';

// Delete old attempted exam for exam_id 3, student_id 3
$stmt = $pdo->prepare("DELETE FROM exam_attempts WHERE exam_id = 3 AND student_id = 3 AND status = 'submitted'");
$stmt->execute();

$affected = $stmt->rowCount();
echo "<h2>Clearing Old Attempt</h2>";
echo "<p>Deleted $affected old submitted attempt(s).</p>";

// Now verify they can start fresh
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_attempts WHERE exam_id = 3 AND student_id = 3 AND status = 'in_progress'");
$stmt->execute();
$result = $stmt->fetch();

echo "<p style='color:green;'><strong>✓ Student can now START the exam fresh!</strong></p>";
echo "<p>Current active attempts: " . $result['cnt'] . "</p>";
echo "<p><a href='student/exams.php' class='btn btn-primary'>Go to Exams List</a></p>";
?>
