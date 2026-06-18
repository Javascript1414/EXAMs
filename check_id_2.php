<?php
require 'includes/db.php';

$id = 2;

echo "=== CHECKING ID 2 ===<br><br>";

// Check if it's a user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($user) {
    echo "<h3>User ID 2:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}

// Check exam attempts for user 2
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE student_id = ?");
$stmt->execute([$id]);
$attempts = $stmt->fetchAll();

echo "<h3>Exam Attempts for User 2:</h3>";
if ($attempts) {
    foreach ($attempts as $att) {
        echo "<pre>";
        print_r($att);
        echo "</pre>";
    }
} else {
    echo "No attempts found<br>";
}

// Check exam with id 2
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch();

if ($exam) {
    echo "<h3>Exam ID 2:</h3>";
    echo "<pre>";
    print_r($exam);
    echo "</pre>";
    
    // Check questions in this exam
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_questions WHERE exam_id = ?");
    $stmt->execute([$id]);
    $qcount = $stmt->fetch();
    echo "<br>Questions in Exam 2: " . $qcount['cnt'] . "<br>";
}

// Check exam_attempts with id 2
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
$stmt->execute([$id]);
$attempt = $stmt->fetch();

if ($attempt) {
    echo "<h3>Exam Attempt ID 2:</h3>";
    echo "<pre>";
    print_r($attempt);
    echo "</pre>";
    
    // Check answers
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM exam_answers WHERE attempt_id = ?");
    $stmt->execute([$id]);
    $acount = $stmt->fetch();
    echo "Answers for this attempt: " . $acount['cnt'] . "<br>";
}
?>
