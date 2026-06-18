<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>📊 Multiple Trades Assignment Investigation</h2>";

// Check subject_teacher table
echo "<h3>Subject_Teacher Table Structure:</h3>";
$result = $pdo->query("DESCRIBE subject_teacher")->fetchAll();
echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #2196F3; color: white;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
foreach ($result as $col) {
    echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td><td>" . $col['Null'] . "</td><td>" . $col['Key'] . "</td></tr>";
}
echo "</table>";

// Check subject_teacher data
echo "<h3>Sample Subject-Teacher Mappings:</h3>";
$result = $pdo->query("
    SELECT st.*, s.subject_name, t.trade_name, u.full_name
    FROM subject_teacher st
    LEFT JOIN subjects s ON st.subject_id = s.id
    LEFT JOIN trades t ON s.trade_id = t.id
    LEFT JOIN users u ON st.teacher_id = u.id
    LIMIT 10
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #FF9800; color: white;'><th>Teacher ID</th><th>Teacher Name</th><th>Subject</th><th>Trade</th></tr>";
foreach ($result as $row) {
    echo "<tr><td>" . $row['teacher_id'] . "</td><td>" . ($row['full_name'] ?? 'N/A') . "</td><td>" . ($row['subject_name'] ?? 'N/A') . "</td><td>" . ($row['trade_name'] ?? 'N/A') . "</td></tr>";
}
echo "</table>";

// Check if there's a user_trades or student_trades table
echo "<h3>Searching for User-Trades Junction Table:</h3>";
$result = $pdo->query("SHOW TABLES LIKE '%student%'")->fetchAll();
if (empty($result)) {
    echo "<p style='color: red;'><strong>❌ NO student_trades junction table found!</strong></p>";
    echo "<p>System currently only supports ONE trade_id per user (in users table).</p>";
} else {
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}

// Check current student with their assigned trades
echo "<h3>Current Student Trades (if any have multiple):</h3>";
$result = $pdo->query("
    SELECT id, full_name, trade_id, role_id 
    FROM users 
    LIMIT 5
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Name</th><th>Trade ID</th><th>Role ID</th></tr>";
foreach ($result as $row) {
    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['full_name'] . "</td><td>" . $row['trade_id'] . "</td><td>" . $row['role_id'] . "</td></tr>";
}
echo "</table>";
?>
