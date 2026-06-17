<?php
require_once 'includes/db.php';

$attempt_id = 4; // From exam_attempt.php?id=4 - this is the attempt ID

// Check if attempt exists
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
$stmt->execute([$attempt_id]);
$attempt = $stmt->fetch();

echo "<h2>Attempt Details</h2>";
if ($attempt) {
    echo "<pre>";
    print_r($attempt);
    echo "</pre>";
} else {
    echo "Attempt not found!";
}

// Check for exam_answers for this attempt
echo "<h2>Questions in this attempt</h2>";
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_answers WHERE attempt_id = ?");
$stmt->execute([$attempt_id]);
$result = $stmt->fetch();
echo "Total questions: " . $result['cnt'] . "<br>";

if ($result['cnt'] == 0) {
    echo "<p style='color:red'><strong>ERROR: No questions assigned to this attempt!</strong></p>";
    echo "<p>The exam_answers table has no records for this attempt ID.</p>";
}
?>
