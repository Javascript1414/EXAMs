<?php
require_once __DIR__ . '/includes/db.php';

// Move FDGDG note from Trade 2 (CSA) to Trade 1 (General Education)
$stmt = $pdo->prepare("UPDATE notes SET trade_id = ? WHERE id = ?");
$stmt->execute([1, 1]); // Move note ID 1 (FDGDG) to trade_id 1 (General Education)

echo "<h2>✅ Fixed!</h2>";
echo "<p><strong>Updated:</strong> FDGDG note is now in General Education trade.</p>";
echo "<p>Student will now see both notes:</p>";
echo "<ul>";
echo "<li>✓ GRF (General Education)</li>";
echo "<li>✓ FDGDG (General Education)</li>";
echo "</ul>";

// Verify
$stmt = $pdo->query("
    SELECT n.title, t.trade_name 
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    ORDER BY n.id DESC
");
$notes = $stmt->fetchAll();

echo "<h3>All Notes After Fix:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Note</th><th>Trade</th></tr>";
foreach ($notes as $note) {
    echo "<tr><td>" . $note['title'] . "</td><td>" . $note['trade_name'] . "</td></tr>";
}
echo "</table>";
?>
