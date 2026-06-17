<?php
$pdo = new PDO('mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== DATABASE CHECK ===\n\n";

// Check questions
$result = $pdo->query('SELECT COUNT(*) as cnt FROM questions');
$q_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "1. Total Questions in DB: $q_count\n";

if ($q_count > 0) {
    $result = $pdo->query('SELECT id, question_text FROM questions ORDER BY id DESC LIMIT 3');
    $questions = $result->fetchAll();
    echo "   Latest 3 questions:\n";
    foreach ($questions as $q) {
        echo "     - ID: " . $q['id'] . " | " . substr($q['question_text'], 0, 40) . "\n";
    }
}

// Check exam_questions for exam 2
echo "\n2. Exam 2 - Linked Questions:\n";
$result = $pdo->query('SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2');
$eq_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Total linked: $eq_count\n";

if ($eq_count > 0) {
    $result = $pdo->query('SELECT question_id FROM exam_questions WHERE exam_id = 2');
    $linked = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "   Question IDs: " . implode(", ", $linked) . "\n";
}

// Check exam attempts
echo "\n3. Exam Attempts for Exam 2:\n";
$result = $pdo->query('SELECT COUNT(*) as cnt FROM exam_attempts WHERE exam_id = 2');
$attempts = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Total attempts: $attempts\n";

echo "\n=== DIAGNOSIS ===\n";
if ($q_count > 0 && $eq_count == 0) {
    echo "❌ PROBLEM FOUND:\n";
    echo "   - Questions ARE in database ($q_count total)\n";
    echo "   - But NOT linked to Exam 2\n\n";
    echo "✅ SOLUTION:\n";
    echo "   Insert into exam_questions table\n";
} elseif ($eq_count > 0) {
    echo "✅ Questions ARE linked to Exam 2!\n";
    if ($attempts == 0) {
        echo "⚠️  No attempts yet. Try starting exam fresh.\n";
    } else {
        echo "✅ Attempts exist: $attempts\n";
    }
} else {
    echo "❌ NO QUESTIONS IN DATABASE!\n";
}
?>
