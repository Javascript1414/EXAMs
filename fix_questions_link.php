<?php
$pdo = new PDO('mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "==========================================\n";
echo "LINKING QUESTIONS TO EXAM 2\n";
echo "==========================================\n\n";

// Question IDs: 1, 2, 3
$question_ids = [1, 2, 3];
$exam_id = 2;

echo "Step 1: Delete old exam_questions for exam 2 (if any)...\n";
$delete = $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
$delete->execute([$exam_id]);
echo "   ✅ Deleted: " . $delete->rowCount() . " records\n";

echo "\nStep 2: Insert new exam_questions...\n";
$insert = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");

foreach ($question_ids as $qid) {
    $insert->execute([$exam_id, $qid]);
    echo "   ✅ Linked: Exam 2 ← Question $qid\n";
}

echo "\nStep 3: Verify...\n";
$verify = $pdo->query("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
$count = $verify->fetch(PDO::FETCH_ASSOC)['cnt'];
echo "   Total linked: $count\n";

echo "\nStep 4: Delete old attempt (to start fresh)...\n";
$delete_attempt = $pdo->prepare("DELETE FROM exam_attempts WHERE exam_id = ?");
$delete_attempt->execute([$exam_id]);
echo "   ✅ Deleted: " . $delete_attempt->rowCount() . " attempt(s)\n";

echo "\n==========================================\n";
echo "✅ ALL DONE!\n";
echo "==========================================\n\n";

echo "NEXT STEPS:\n";
echo "1. Logout from browser (or clear cache)\n";
echo "2. Login again\n";
echo "3. Go to Exams page\n";
echo "4. Click 'Start Exam' on Exam 2\n";
echo "5. Questions should appear! 🎉\n";

?>
