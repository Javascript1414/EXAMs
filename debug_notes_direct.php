<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2 style='font-family: Arial'>📋 Database Diagnostic Report</h2>";

// Check notes table
echo "<h3>All Notes in Database:</h3>";
$stmt = $pdo->query("
    SELECT n.id, n.title, n.trade_id, n.subject_id, n.status, 
           t.trade_name, s.subject_name, u.full_name
    FROM notes n
    LEFT JOIN trades t ON n.trade_id = t.id
    LEFT JOIN subjects s ON n.subject_id = s.id
    LEFT JOIN users u ON n.uploaded_by = u.id
    ORDER BY n.id DESC
");
$notes = $stmt->fetchAll();

if (empty($notes)) {
    echo "<p style='color: red;'><strong>❌ No notes found in database!</strong></p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-family: Arial;'>";
    echo "<tr style='background: #4CAF50; color: white;'>";
    echo "<th>ID</th><th>Title</th><th>Trade (ID)</th><th>Subject (ID)</th><th>Status</th><th>Uploader</th>";
    echo "</tr>";
    
    $rowNum = 0;
    foreach ($notes as $note) {
        $rowNum++;
        $bgcolor = ($rowNum % 2 == 0) ? "#f9f9f9" : "white";
        $statusColor = ($note['status'] === 'active') ? "green" : "orange";
        
        echo "<tr style='background: $bgcolor;'>";
        echo "<td>" . $note['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($note['title']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($note['trade_name'] ?? 'N/A') . " (ID: " . $note['trade_id'] . ")</td>";
        echo "<td>" . htmlspecialchars($note['subject_name'] ?? 'N/A') . " (ID: " . $note['subject_id'] . ")</td>";
        echo "<td style='color: $statusColor; font-weight: bold;'>" . strtoupper($note['status']) . "</td>";
        echo "<td>" . htmlspecialchars($note['full_name'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total Notes: " . count($notes) . "</strong></p>";
}

// Check student users
echo "<h3>Student Users and Their Trades:</h3>";
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.trade_id, t.trade_name
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    LIMIT 10
");
$students = $stmt->fetchAll();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-family: Arial;'>";
echo "<tr style='background: #2196F3; color: white;'>";
echo "<th>ID</th><th>Name</th><th>Trade (ID)</th>";
echo "</tr>";

foreach ($students as $student) {
    echo "<tr>";
    echo "<td>" . $student['id'] . "</td>";
    echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($student['trade_name'] ?? 'N/A') . " (ID: " . $student['trade_id'] . ")</td>";
    echo "</tr>";
}
echo "</table>";

// Check trades
echo "<h3>All Trades:</h3>";
$stmt = $pdo->query("SELECT id, trade_name FROM trades ORDER BY id");
$trades = $stmt->fetchAll();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-family: Arial;'>";
echo "<tr style='background: #FF9800; color: white;'>";
echo "<th>ID</th><th>Trade Name</th>";
echo "</tr>";

foreach ($trades as $trade) {
    echo "<tr>";
    echo "<td>" . $trade['id'] . "</td>";
    echo "<td>" . htmlspecialchars($trade['trade_name']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
