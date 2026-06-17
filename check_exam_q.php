<?php
require_once 'includes/db.php';

$exam_id = 3;

// Check for questions in exam_questions
echo "<h2>Questions for Exam ID $exam_id</h2>";
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$result = $stmt->fetch();
echo "<p><strong>Total questions assigned: " . $result['cnt'] . "</strong></p>";

if ($result['cnt'] == 0) {
    echo "<p style='color:red'><strong>❌ PROBLEM FOUND: No questions assigned to this exam!</strong></p>";
    echo "<p>Questions need to be added to the <code>exam_questions</code> table.</p>";
    
    // Check if there are any questions in the system
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM questions");
    $qCount = $stmt->fetch();
    echo "<p>Total questions in system: " . $qCount['cnt'] . "</p>";
    
    if ($qCount['cnt'] > 0) {
        echo "<p style='color:green'>✓ There ARE questions in the system that can be assigned.</p>";
    }
} else {
    echo "<p style='color:green'>✓ Questions are properly assigned.</p>";
}
?>
