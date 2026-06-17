<?php
$pdo = new PDO('mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=========================================\n";
echo "EXAM SYSTEM VERIFICATION REPORT\n";
echo "=========================================\n\n";

// 1. Check Exam Status
echo "1. EXAM 2 STATUS:\n";
$result = $pdo->query("SELECT id, exam_name, status, duration_minutes FROM exams WHERE id = 2");
$exam = $result->fetch(PDO::FETCH_ASSOC);
if ($exam) {
    echo "   ✅ Name: {$exam['exam_name']}\n";
    echo "   ✅ Status: {$exam['status']}\n";
    echo "   ✅ Duration: {$exam['duration_minutes']} minutes\n";
} else {
    echo "   ❌ Exam not found!\n";
}

// 2. Check Questions Linked
echo "\n2. QUESTIONS LINKED TO EXAM 2:\n";
$result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
$eq_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Total: $eq_count\n";

$result = $pdo->query("SELECT eq.id, eq.question_id, q.question_text FROM exam_questions eq 
                       JOIN questions q ON eq.question_id = q.id 
                       WHERE eq.exam_id = 2");
$questions = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($questions as $q) {
    echo "   ✓ Q{$q['question_id']}: " . substr($q['question_text'], 0, 50) . "...\n";
}

// 3. Check Questions Table
echo "\n3. QUESTIONS IN DATABASE:\n";
$result = $pdo->query("SELECT id, question_text, question_type FROM questions ORDER BY id DESC LIMIT 5");
$all_questions = $result->fetchAll(PDO::FETCH_ASSOC);
echo "   Total Questions: " . count($all_questions) . "\n";
foreach ($all_questions as $q) {
    echo "   - ID {$q['id']}: {$q['question_text']}\n";
}

// 4. Check Exam Attempts
echo "\n4. EXAM ATTEMPTS FOR EXAM 2:\n";
$result = $pdo->query("SELECT id, student_id, status, started_at FROM exam_attempts WHERE exam_id = 2");
$attempts = $result->fetchAll(PDO::FETCH_ASSOC);
echo "   Total attempts: " . count($attempts) . "\n";
if (count($attempts) > 0) {
    foreach ($attempts as $a) {
        echo "   - Attempt {$a['id']}: Student {$a['student_id']}, Status: {$a['status']}\n";
    }
}

// 5. Check Exam Answers
echo "\n5. EXAM ANSWERS CREATED:\n";
$result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_answers 
                       WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE exam_id = 2)");
$ans_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Total answers: $ans_count\n";

if ($ans_count > 0) {
    $result = $pdo->query("SELECT ea.question_id, ea.answer_status FROM exam_answers ea 
                           WHERE ea.attempt_id IN (SELECT id FROM exam_attempts WHERE exam_id = 2)");
    $answers = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($answers as $a) {
        echo "   - Q{$a['question_id']}: {$a['answer_status']}\n";
    }
}

// Final Diagnosis
echo "\n=========================================\n";
echo "DIAGNOSIS:\n";
echo "=========================================\n";

if ($eq_count > 0 && $ans_count > 0) {
    echo "✅ ALL SYSTEMS GO!\n";
    echo "   - Questions linked: $eq_count\n";
    echo "   - Attempt created: " . count($attempts) . "\n";
    echo "   - Answers initialized: $ans_count\n";
    echo "\n🎉 READY TO DISPLAY QUESTIONS!\n";
} elseif ($eq_count > 0 && count($attempts) > 0 && $ans_count == 0) {
    echo "⚠️  PARTIAL ISSUE:\n";
    echo "   - Questions linked: $eq_count ✓\n";
    echo "   - Attempts exist: " . count($attempts) . " ✓\n";
    echo "   - But NO exam_answers created ❌\n";
    echo "\n📝 FIX: Need to create exam_answers for attempts\n";
} else {
    echo "❌ PROBLEMS FOUND:\n";
    echo "   - Questions linked: $eq_count\n";
    echo "   - Attempts: " . count($attempts) . "\n";
    echo "   - Answers: $ans_count\n";
}

?>
