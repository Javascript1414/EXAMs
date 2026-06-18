<?php
require 'includes/db.php';

// Check the active attempt for exam 2
$stmt = $pdo->prepare("SELECT ea.*, e.duration_minutes FROM exam_attempts ea JOIN exams e ON ea.exam_id = e.id WHERE ea.exam_id = 2 AND ea.student_id = 2");
$stmt->execute();
$attempt = $stmt->fetch();

if ($attempt) {
    echo "<h3>Exam 2 Attempt Details:</h3>";
    echo "Attempt ID: " . $attempt['id'] . "<br>";
    echo "Started At: " . $attempt['started_at'] . "<br>";
    echo "Duration: " . $attempt['duration_minutes'] . " minutes<br>";
    
    $now = time();
    $started = strtotime($attempt['started_at']);
    $elapsed = $now - $started;
    $totalSeconds = $attempt['duration_minutes'] * 60;
    $remaining = max(0, $totalSeconds - $elapsed);
    
    echo "<br>Current Time: " . date('Y-m-d H:i:s') . "<br>";
    echo "Started Time: " . date('Y-m-d H:i:s', $started) . "<br>";
    echo "Elapsed Seconds: " . $elapsed . "<br>";
    echo "Total Duration Seconds: " . $totalSeconds . "<br>";
    echo "Remaining Seconds: " . $remaining . "<br>";
    
    if ($remaining <= 0) {
        echo "<br><strong style='color:red;'>⚠️ TIME EXPIRED!</strong> Exam should be auto-submitted.";
    }
} else {
    echo "No active attempt found for exam 2";
}
?>
