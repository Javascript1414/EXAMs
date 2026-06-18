<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>All Students in System</h2>";

$query = "
SELECT 
    u.id,
    u.full_name,
    u.email,
    COUNT(DISTINCT st.trade_id) as trades_count,
    GROUP_CONCAT(DISTINCT t.trade_name ORDER BY t.trade_name SEPARATOR ', ') as trades
FROM users u
LEFT JOIN student_trades st ON u.id = st.student_id
LEFT JOIN trades t ON st.trade_id = t.id
WHERE u.role_id = (SELECT id FROM roles WHERE role_name LIKE '%student%' LIMIT 1)
GROUP BY u.id
ORDER BY u.full_name
";

$stmt = $pdo->query($query);
$students = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>#</th><th>Name</th><th>Email</th><th>Trades</th><th>Count</th></tr>";

$count = 1;
foreach($students as $s) {
    $trades = $s['trades'] ? $s['trades'] : '<span style="color:red;">No Trades</span>';
    echo "<tr>";
    echo "<td>{$count}</td>";
    echo "<td><strong>{$s['full_name']}</strong></td>";
    echo "<td>{$s['email']}</td>";
    echo "<td>{$trades}</td>";
    echo "<td>{$s['trades_count']}</td>";
    echo "</tr>";
    $count++;
}

echo "</table>";
?>
