<?php
require 'config.php';
require 'includes/db.php';

echo "=== CHECKING QUESTIONS ===\n\n";

// Check questions table
$result = $pdo->query('SELECT COUNT(*) as count FROM questions');
$count = $result->fetch()['count'];
echo "Total Questions in DB: $count\n\n";

// Show recent questions
$result = $pdo->query('SELECT id, question_text, marks FROM questions ORDER BY id DESC LIMIT 5');
$rows = $result->fetchAll();
echo "Recent Questions:\n";
foreach($rows as $row) {
    echo "  - ID: " . $row['id'] . " | " . substr($row['question_text'], 0, 50) . "... | Marks: " . $row['marks'] . "\n";
}

echo "\n=== CHECKING EXAM 2 ===\n";

// Check exam_questions for exam_id = 2
$result = $pdo->query('SELECT COUNT(*) as count FROM exam_questions WHERE exam_id = 2');
$count = $result->fetch()['count'];
echo "Questions assigned to Exam 2: $count\n";

// Show which questions are assigned
$result = $pdo->query('SELECT question_id FROM exam_questions WHERE exam_id = 2');
$rows = $result->fetchAll();
if($rows) {
    echo "Question IDs: " . implode(', ', array_column($rows, 'question_id')) . "\n";
} else {
    echo "❌ NO QUESTIONS ASSIGNED!\n";
}

echo "\n=== SOLUTION ===\n";
echo "To fix this, run this SQL:\n\n";
echo "INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES\n";

// Get all questions to show insertion command
$result = $pdo->query('SELECT id FROM questions ORDER BY id DESC LIMIT 3');
$all_questions = $result->fetchAll();
$order = 1;
foreach($all_questions as $idx => $q) {
    echo "(2, " . $q['id'] . ", " . $order . ")" . ($idx < count($all_questions)-1 ? ",\n" : ";\n");
    $order++;
}
?>
