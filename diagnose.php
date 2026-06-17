<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';
require 'includes/db.php';

echo "=== DATABASE DIAGNOSTIC ===\n\n";

// 1. Check questions table
echo "1. CHECKING QUESTIONS TABLE\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM questions");
$count = $result->fetch()['count'];
echo "   Total questions: $count\n";

if ($count > 0) {
    $result = $pdo->query("SELECT id, question_text FROM questions ORDER BY id DESC LIMIT 3");
    $rows = $result->fetchAll();
    echo "   Latest 3 questions:\n";
    foreach ($rows as $row) {
        echo "     - ID: {$row['id']} | Text: " . substr($row['question_text'], 0, 40) . "\n";
    }
}

// 2. Check exam_questions table
echo "\n2. CHECKING EXAM_QUESTIONS TABLE\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM exam_questions WHERE exam_id = 2");
$count = $result->fetch()['count'];
echo "   Questions assigned to Exam 2: $count\n";

if ($count > 0) {
    $result = $pdo->query("SELECT question_id FROM exam_questions WHERE exam_id = 2");
    $rows = $result->fetchAll();
    echo "   Question IDs: " . implode(", ", array_column($rows, 'question_id')) . "\n";
} else {
    echo "   ⚠️  NO QUESTIONS ASSIGNED TO EXAM 2!\n";
}

// 3. Check exam_answers table
echo "\n3. CHECKING EXAM_ANSWERS TABLE\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM exam_answers");
$count = $result->fetch()['count'];
echo "   Total exam answers: $count\n";

$result = $pdo->query("SELECT COUNT(*) as count FROM exam_answers WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE exam_id = 2)");
$count = $result->fetch()['count'];
echo "   Answers for Exam 2 attempts: $count\n";

// 4. Check exams table
echo "\n4. CHECKING EXAMS TABLE\n";
$result = $pdo->query("SELECT id, exam_name, status FROM exams WHERE id = 2");
$exam = $result->fetch();
if ($exam) {
    echo "   Exam 2: " . $exam['exam_name'] . " | Status: " . $exam['status'] . "\n";
} else {
    echo "   ❌ EXAM 2 NOT FOUND!\n";
}

// 5. Check exam attempts
echo "\n5. CHECKING EXAM ATTEMPTS\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM exam_attempts WHERE exam_id = 2");
$count = $result->fetch()['count'];
echo "   Attempts for Exam 2: $count\n";

if ($count > 0) {
    $result = $pdo->query("SELECT id, student_id, status FROM exam_attempts WHERE exam_id = 2 ORDER BY id DESC LIMIT 1");
    $attempt = $result->fetch();
    echo "   Latest attempt ID: " . $attempt['id'] . " | Status: " . $attempt['status'] . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "RECOMMENDATION:\n";

$result = $pdo->query("SELECT COUNT(*) as count FROM exam_questions WHERE exam_id = 2");
$eq_count = $result->fetch()['count'];

if ($eq_count == 0) {
    echo "❌ Problem: No questions assigned to Exam 2!\n";
    echo "✅ Solution: Run this SQL:\n\n";
    
    $result = $pdo->query("SELECT id FROM questions ORDER BY id DESC LIMIT 3");
    $questions = $result->fetchAll();
    
    if (!empty($questions)) {
        echo "INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES\n";
        foreach ($questions as $idx => $q) {
            echo "(2, " . $q['id'] . ", " . ($idx + 1) . ")" . ($idx < count($questions)-1 ? ",\n" : ";\n");
        }
    }
} else {
    echo "✅ Questions are assigned! If still not showing:\n";
    echo "   1. Delete existing exam attempts\n";
    echo "   2. Try starting exam fresh\n";
    echo "   3. Check browser console for JS errors\n";
}
?>
