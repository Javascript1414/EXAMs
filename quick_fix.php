<?php
// Direct database check
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Database connected!\n\n";
    
    // Check questions
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM questions");
    $q_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Questions in DB: $q_count\n";
    
    // Check exam 2
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
    $eq_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Exam 2 has questions: $eq_count\n";
    
    if ($q_count > 0 && $eq_count == 0) {
        echo "\n⚠️  Problem: Questions exist but not linked to Exam 2!\n";
        echo "\nFixing now...\n";
        
        // Get last 3 questions
        $result = $pdo->query("SELECT id FROM questions ORDER BY id DESC LIMIT 3");
        $questions = $result->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Questions found: " . implode(", ", $questions) . "\n";
        
        // Link them
        $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES (?, ?, ?)");
        $idx = 1;
        foreach ($questions as $qid) {
            $stmt->execute([2, $qid, $idx]);
            echo "✅ Linked Q$qid\n";
            $idx++;
        }
        
        // Verify
        $result = $pdo->query("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = 2");
        $new_count = $result->fetch(PDO::FETCH_ASSOC)['cnt'];
        echo "\n✅ SUCCESS! Exam 2 now has $new_count questions!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
