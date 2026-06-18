<?php
// This file checks for common exam portal errors
require 'includes/db.php';
require 'includes/functions.php';

echo "<h2>Exam Portal Error Check</h2>";

// Check 1: Verify exam_attempts table structure
$result = $pdo->query("DESCRIBE exam_attempts");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>exam_attempts table structure:</h3>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";

// Check 2: Verify exam_answers table structure
$result = $pdo->query("DESCRIBE exam_answers");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>exam_answers table structure:</h3>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
}
echo "</ul>";

// Check 3: Look for recent exam attempts with issues
$stmt = $pdo->query("SELECT ea.attempt_id, COUNT(*) as ans_count, COUNT(DISTINCT eq.question_id) as exp_count 
                     FROM exam_attempts ea 
                     LEFT JOIN exam_answers ea2 ON ea.id = ea2.attempt_id
                     LEFT JOIN exam_questions eq ON ea.exam_id = eq.exam_id
                     WHERE ea.status = 'in_progress'
                     GROUP BY ea.id
                     LIMIT 5");
$results = $stmt->fetchAll();
echo "<h3>Recent In-Progress Attempts:</h3>";
echo "<pre>";
print_r($results);
echo "</pre>";

// Check 4: Check if time calculation is causing issues
echo "<h3>Time Calculation Test:</h3>";
$time = time();
$started = date('Y-m-d H:i:s', $time - 300);
$elapsed = $time - strtotime($started);
echo "Current time: $time<br>";
echo "Started at: $started<br>";
echo "Elapsed: $elapsed seconds<br>";
?>
