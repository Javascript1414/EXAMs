<?php
require_once __DIR__ . '/includes/db.php';

// Get first student
$student = $pdo->query("SELECT * FROM users WHERE role = 'student' LIMIT 1")->fetch();

if (!$student) {
    die("No student found");
}

$trade_id = $student['trade_id'];

// Count subjects for this trade
$count_result = $pdo->query("SELECT COUNT(*) as cnt FROM subjects WHERE trade_id = $trade_id");
$count_data = $count_result->fetch();
$total = $count_data['cnt'];

echo "STUDENT: " . htmlspecialchars($student['full_name']) . "\n";
echo "TRADE ID: " . $trade_id . "\n";
echo "TOTAL SUBJECTS: " . $total . "\n\n";

if ($total > 0) {
    $subjects = $pdo->query("SELECT * FROM subjects WHERE trade_id = $trade_id ORDER BY subject_name")->fetchAll();
    
    echo "SUBJECTS:\n";
    foreach ($subjects as $idx => $s) {
        echo ($idx + 1) . ". " . $s['subject_name'] . "\n";
    }
} else {
    echo "NO SUBJECTS FOUND!\n";
}
?>
