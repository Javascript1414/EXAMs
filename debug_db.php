<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h3>Database Check</h3>";

// Check trades
echo "<h4>Trades:</h4>";
$trades = $pdo->query('SELECT id, trade_name FROM trades')->fetchAll();
if (empty($trades)) {
    echo "No trades found<br>";
} else {
    foreach ($trades as $t) {
        echo "ID: " . $t['id'] . " - " . $t['trade_name'] . "<br>";
    }
}

// Check subjects
echo "<h4>Subjects:</h4>";
$subjects = $pdo->query('SELECT id, subject_name, trade_id FROM subjects')->fetchAll();
if (empty($subjects)) {
    echo "No subjects found<br>";
} else {
    foreach ($subjects as $s) {
        echo "ID: " . $s['id'] . " - " . $s['subject_name'] . " (Trade: " . $s['trade_id'] . ")<br>";
    }
}

// Check teacher
echo "<h4>Teacher (teacher@example.com):</h4>";
$teacher = $pdo->query("SELECT id, trade_id FROM users WHERE email = 'teacher@example.com'")->fetch();
if ($teacher) {
    echo "ID: " . $teacher['id'] . " - Trade ID: " . $teacher['trade_id'] . "<br>";
} else {
    echo "Teacher not found<br>";
}
?>
