<?php
require 'config.php';
require 'includes/db.php';

echo "<pre style='background:#f5f5f5; padding:15px; border-radius:5px; font-family:monospace;'>";
echo "🔧 FIXING EXAM 2 QUESTIONS DISPLAY BUG\n\n";

// Step 1: Get last 3 questions added
echo "STEP 1: Fetching latest questions from database...\n";
$stmt = $pdo->query("SELECT id FROM questions ORDER BY id DESC LIMIT 3");
$questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($questions)) {
    echo "❌ ERROR: No questions found in database!\n";
    echo "Please add questions first using /admin/question_add.php\n";
    exit;
}

echo "✅ Found " . count($questions) . " questions:\n";
foreach ($questions as $q) {
    echo "   • Question ID: $q\n";
}

// Step 2: Clear existing exam_questions for this exam
echo "\nSTEP 2: Clearing old exam assignments...\n";
$pdo->prepare("DELETE FROM exam_questions WHERE exam_id = 2")->execute();
echo "✅ Cleared!\n";

// Step 3: Link questions to exam
echo "\nSTEP 3: Linking questions to Exam 2...\n";
$stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (?, ?, ?)");
$order = 1;
foreach ($questions as $q_id) {
    $stmt->execute([2, $q_id, $order]);
    echo "✅ Linked Question $q_id to Exam 2 (Order: $order)\n";
    $order++;
}

// Step 4: Verify
echo "\nSTEP 4: Verifying in database...\n";
$result = $pdo->query("SELECT COUNT(*) as count FROM exam_questions WHERE exam_id = 2");
$count = $result->fetch()['count'];
echo "✅ Questions now assigned to Exam 2: $count\n";

// Step 5: Success message
if ($count >= 3) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ ✅ ✅ SUCCESS! Questions are now linked! ✅ ✅ ✅\n";
    echo str_repeat("=", 50) . "\n";
    echo "\n🚀 NEXT STEPS:\n";
    echo "   1. Go back to exam\n";
    echo "   2. Press F5 to refresh\n";
    echo "   3. Start exam again - Questions will NOW show!\n";
} else {
    echo "\n⚠️ Something went wrong. Check database manually.\n";
}

echo "</pre>";
?>
