<?php
require_once __DIR__ . '/includes/db.php';

// Get any student's trade
$student = $pdo->query("SELECT id, full_name, trade_id FROM users WHERE role='student' LIMIT 1")->fetch();
$trade_id = $student['trade_id'];

// Count subjects
$count = $pdo->query("SELECT COUNT(*) FROM subjects WHERE trade_id = $trade_id")->fetchColumn();

// Get all subjects
$subjects = $pdo->query("SELECT subject_name FROM subjects WHERE trade_id = $trade_id ORDER BY subject_name")->fetchAll();

echo "=== SUBJECT VERIFICATION ===\n\n";
echo "Student: " . $student['full_name'] . "\n";
echo "Trade ID: " . $trade_id . "\n";
echo "Total Subjects: " . $count . "\n\n";

if ($count > 0) {
    echo "Subject List:\n";
    foreach ($subjects as $s) {
        echo "  - " . $s['subject_name'] . "\n";
    }
} else {
    echo "❌ NO SUBJECTS FOUND!\n";
}
?>
