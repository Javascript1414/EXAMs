<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Database Debug - Subjects & Trades</h2>";

try {
    // Get all trades
    $trades = $pdo->query("SELECT * FROM trades ORDER BY id")->fetchAll();
    echo "<h3>Trades (" . count($trades) . ")</h3>";
    echo "<table border='1' cellpadding='10'>";
    foreach ($trades as $t) {
        echo "<tr><td>{$t['id']}</td><td>{$t['trade_name']}</td></tr>";
    }
    echo "</table><br>";

    // Get all subjects
    $subjects = $pdo->query("SELECT s.*, t.trade_name FROM subjects s LEFT JOIN trades t ON s.trade_id = t.id ORDER BY s.trade_id, s.subject_name")->fetchAll();
    echo "<h3>Subjects (" . count($subjects) . ")</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Trade</th><th>Subject</th></tr>";
    foreach ($subjects as $s) {
        echo "<tr><td>{$s['id']}</td><td>{$s['trade_id']} - {$s['trade_name']}</td><td>{$s['subject_name']}</td></tr>";
    }
    echo "</table><br>";

    // Get notes count
    $notes = $pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
    echo "<h3>Notes in DB: " . $notes . "</h3>";

    // Get students
    $students = $pdo->query("SELECT id, full_name, trade_id, role FROM users WHERE role = 'student' LIMIT 10")->fetchAll();
    echo "<h3>Students (" . count($students) . ")</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Trade</th></tr>";
    foreach ($students as $s) {
        echo "<tr><td>{$s['id']}</td><td>{$s['full_name']}</td><td>{$s['trade_id']}</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
