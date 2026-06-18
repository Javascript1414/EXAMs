<?php
require_once __DIR__ . '/includes/db.php';

// Get the first trade
$trade = $pdo->query("SELECT id, trade_name FROM trades LIMIT 1")->fetch();
$trade_id = $trade['id'];

$sample_subjects = [
    'General Knowledge',
    'Mathematics',
    'English',
    'Science',
    'History',
    'Computer Science',
    'Physics',
    'Chemistry',
    'Biology',
    'Economics'
];

echo "<h2>Adding Sample Subjects</h2>";
echo "<p><strong>Trade:</strong> " . htmlspecialchars($trade['trade_name']) . " (ID: $trade_id)</p>";

$added = 0;
$skipped = 0;

foreach ($sample_subjects as $subject_name) {
    try {
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? AND subject_name = ?");
        $check->execute([$trade_id, $subject_name]);
        
        if ($check->fetch()) {
            echo "<p style='color: orange;'>⚠ $subject_name - Already exists</p>";
            $skipped++;
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (trade_id, subject_name, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$trade_id, $subject_name]);
            echo "<p style='color: green;'>✓ $subject_name - Added successfully</p>";
            $added++;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ $subject_name - Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Summary:</strong></p>";
echo "<p>✓ Added: $added subjects</p>";
echo "<p>⚠ Already existed: $skipped subjects</p>";

// Show all subjects now
$all_subjects = $pdo->query("SELECT * FROM subjects WHERE trade_id = ? ORDER BY subject_name", [$trade_id])->fetchAll();
echo "<h3>All Subjects Now (" . count($all_subjects) . ")</h3>";
echo "<ul>";
foreach ($all_subjects as $s) {
    echo "<li>" . htmlspecialchars($s['subject_name']) . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='/student/notes.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>← Go back to Study Notes</a></p>";
?>
