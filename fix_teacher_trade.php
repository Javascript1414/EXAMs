<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->prepare('UPDATE users SET trade_id = ? WHERE email = ?');
$stmt->execute([2, 'teacher@example.com']);

echo "✅ Teacher reassigned to Trade 2 (CSA)<br>";

// Verify
$teacher = $pdo->query("SELECT id, trade_id FROM users WHERE email = 'teacher@example.com'")->fetch();
echo "Teacher ID: " . $teacher['id'] . " - Trade: " . $teacher['trade_id'] . "<br>";

echo "<br><a href='teacher/practical_create_exam.php'>Go to Create Practical Exam →</a>";
?>
