<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Testing student/notes.php Requirements</h2>";

// Simulate a student user
$_SESSION['user_id'] = 3; // SOUMO SANTRA (student)
$_SESSION['role_name'] = 'student';

echo "<h3>1. Check if user exists:</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user) {
    echo "<p style='color: green;'>✅ User found: " . $user['full_name'] . "</p>";
    echo "<p>Role ID: " . $user['role_id'] . " | Trade ID: " . $user['trade_id'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ User NOT found</p>";
}

echo "<h3>2. Check student_trades table:</h3>";
$stmt = $pdo->prepare("
    SELECT st.*, t.trade_name 
    FROM student_trades st
    JOIN trades t ON st.trade_id = t.id
    WHERE st.student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$trades = $stmt->fetchAll();
if (empty($trades)) {
    echo "<p style='color: red;'>❌ NO trades assigned!</p>";
} else {
    echo "<p style='color: green;'>✅ Trades assigned: " . count($trades) . "</p>";
    foreach ($trades as $trade) {
        echo "- " . $trade['trade_name'] . "<br>";
    }
}

echo "<h3>3. Test SQL Query for Notes:</h3>";
if (!empty($trades)) {
    $trade_ids = array_column($trades, 'trade_id');
    $placeholders = implode(',', array_fill(0, count($trade_ids), '?'));
    
    $query = "
        SELECT n.*, s.subject_name, t.trade_name
        FROM notes n
        JOIN trades t ON n.trade_id = t.id
        JOIN subjects s ON n.subject_id = s.id
        WHERE n.trade_id IN ($placeholders) AND n.status = 'active'
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($trade_ids);
    $notes = $stmt->fetchAll();
    
    echo "<p><strong>Total Notes Found:</strong> " . count($notes) . "</p>";
    
    if (count($notes) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr style='background: #4CAF50; color: white;'><th>Title</th><th>Trade</th><th>Subject</th></tr>";
        foreach ($notes as $note) {
            echo "<tr><td>" . $note['title'] . "</td><td>" . $note['trade_name'] . "</td><td>" . $note['subject_name'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No notes found for this student's trades</p>";
    }
}

echo "<h3>4. Test by actually loading page (try iframe):</h3>";
echo "<p>If page loads, you should see debug info below.</p>";
echo "<iframe src='/student/notes.php' width='100%' height='400'></iframe>";
?>
