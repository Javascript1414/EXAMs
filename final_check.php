<?php
$pdo = new PDO('mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "==========================================\n";
echo "FINAL DATABASE CHECK\n";
echo "==========================================\n\n";

// Query 1: exam_questions for exam 2
echo "1. exam_questions (exam_id = 2):\n";
echo "---\n";
try {
    $result = $pdo->query("SELECT * FROM exam_questions WHERE exam_id = 2");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Total records: " . count($rows) . "\n";
    if (count($rows) > 0) {
        foreach ($rows as $row) {
            echo "   - ID: {$row['id']}, Question ID: {$row['question_id']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Query 2: exam_answers count
echo "\n2. exam_answers COUNT (attempt_id = 2):\n";
echo "---\n";
try {
    $result = $pdo->query("SELECT COUNT(*) as total_answers FROM exam_answers WHERE attempt_id = 2");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "   Total: " . $row['total_answers'] . "\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// Query 3: exam_answers details
echo "\n3. exam_answers DETAILS (attempt_id = 2):\n";
echo "---\n";
try {
    $result = $pdo->query("SELECT ea.id, ea.question_id, ea.answer_status FROM exam_answers ea WHERE ea.attempt_id = 2");
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "   Total records: " . count($rows) . "\n";
    if (count($rows) > 0) {
        foreach ($rows as $row) {
            echo "   - ID: {$row['id']}, Question: {$row['question_id']}, Status: {$row['answer_status']}\n";
        }
    } else {
        echo "   ❌ EMPTY - No exam_answers records!\n";
    }
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n==========================================\n";
echo "DIAGNOSIS:\n";
echo "==========================================\n";

// Final check
$eq_result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
$eq_count = $eq_result->fetch(PDO::FETCH_ASSOC)['cnt'];

$ea_result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_answers WHERE attempt_id = 2");
$ea_count = $ea_result->fetch(PDO::FETCH_ASSOC)['cnt'];

if ($eq_count > 0 && $ea_count == 0) {
    echo "❌ PROBLEM:\n";
    echo "   - exam_questions: $eq_count linked (GOOD ✅)\n";
    echo "   - exam_answers: $ea_count (EMPTY ❌)\n";
    echo "\n   WHY: exam_start.php nahi chala!\n";
    echo "\n   FIX: Manually insert into exam_answers\n";
} elseif ($eq_count > 0 && $ea_count > 0) {
    echo "✅ ALL GOOD:\n";
    echo "   - exam_questions: $eq_count linked ✅\n";
    echo "   - exam_answers: $ea_count created ✅\n";
    echo "\n   Questions SHOULD display now!\n";
} else {
    echo "❌ CRITICAL:\n";
    echo "   - exam_questions: $eq_count (EMPTY!)\n";
    echo "   - exam_answers: $ea_count (EMPTY!)\n";
}

?>
