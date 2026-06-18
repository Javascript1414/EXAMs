<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Student Trades & Subjects Distribution</h2>";

$query = "
SELECT 
    u.id,
    u.full_name as student_name,
    COUNT(DISTINCT st.trade_id) as assigned_trades,
    GROUP_CONCAT(DISTINCT t.trade_name ORDER BY t.trade_name SEPARATOR ', ') as trades,
    COUNT(DISTINCT s.id) as total_subjects
FROM users u
LEFT JOIN student_trades st ON u.id = st.student_id
LEFT JOIN trades t ON st.trade_id = t.id
LEFT JOIN subjects s ON t.id = s.trade_id
GROUP BY u.id
HAVING assigned_trades > 0
ORDER BY total_subjects DESC
";

$stmt = $pdo->query($query);
$results = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Student</th><th>Trades Assigned</th><th>Trade Names</th><th>Total Subjects</th></tr>";

foreach($results as $row) {
    echo "<tr>";
    echo "<td>{$row['student_name']}</td>";
    echo "<td>{$row['assigned_trades']}</td>";
    echo "<td>{$row['trades']}</td>";
    echo "<td><strong>{$row['total_subjects']}</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Subjects Per Trade</h3>";
$query2 = "
SELECT 
    t.trade_name,
    COUNT(s.id) as subject_count
FROM trades t
LEFT JOIN subjects s ON t.id = s.trade_id
GROUP BY t.id
ORDER BY t.trade_name
";

$stmt2 = $pdo->query($query2);
$results2 = $stmt2->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Trade</th><th>Subjects</th></tr>";

foreach($results2 as $row) {
    echo "<tr>";
    echo "<td>{$row['trade_name']}</td>";
    echo "<td><strong>{$row['subject_count']}</strong></td>";
    echo "</tr>";
}

echo "</table>";
?>
