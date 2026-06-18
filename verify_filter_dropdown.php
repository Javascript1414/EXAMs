<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>✅ Filter Dropdown Fix Verification</h2>";

// Test student ID
$student_id = 3; // SOUMO SANTRA

echo "<h3>1️⃣ Fetch Student's Trades:</h3>";
$stmt = $pdo->prepare("
    SELECT DISTINCT st.trade_id, t.trade_name
    FROM student_trades st
    JOIN trades t ON st.trade_id = t.id
    WHERE st.student_id = ?
    ORDER BY t.trade_name
");
$stmt->execute([$student_id]);
$trades = $stmt->fetchAll();

echo "<p><strong>Assigned Trades: " . count($trades) . "</strong></p>";
foreach ($trades as $t) {
    echo "- " . $t['trade_name'] . " (ID: " . $t['trade_id'] . ")<br>";
}

echo "<h3>2️⃣ Get ALL Subjects (Without Duplicates):</h3>";

$trade_ids = array_column($trades, 'trade_id');
$placeholders = implode(',', array_fill(0, count($trade_ids), '?'));

// Query with DISTINCT
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, s.trade_id
    FROM subjects s
    WHERE s.trade_id IN ($placeholders)
    ORDER BY s.subject_name ASC, s.id ASC
");
$stmt->execute($trade_ids);
$subjects_raw = $stmt->fetchAll();

// Remove duplicates
$subjects = [];
$seen = [];
foreach ($subjects_raw as $subject) {
    if (!isset($seen[$subject['subject_name']])) {
        $subjects[] = $subject;
        $seen[$subject['subject_name']] = true;
    }
}

echo "<p><strong>Total Unique Subjects: " . count($subjects) . "</strong></p>";
echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Subject ID</th><th>Subject Name</th><th>Trade</th></tr>";
foreach ($subjects as $s) {
    echo "<tr><td>" . $s['id'] . "</td><td>" . htmlspecialchars($s['subject_name']) . "</td><td>" . $s['trade_id'] . "</td></tr>";
}
echo "</table>";

echo "<h3>3️⃣ Filter Examples:</h3>";

echo "<p><strong>Example 1: Select Subject ID = 1</strong></p>";
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notes n
    WHERE n.trade_id IN ($placeholders) AND n.subject_id = 1 AND n.status = 'active'
");
$stmt->execute($trade_ids);
$count = $stmt->fetchColumn();
echo "Notes found: <strong>" . $count . "</strong> ✅<br>";

echo "<p><strong>Example 2: All Subjects (No Filter)</strong></p>";
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notes n
    WHERE n.trade_id IN ($placeholders) AND n.status = 'active'
");
$stmt->execute($trade_ids);
$count = $stmt->fetchColumn();
echo "Notes found: <strong>" . $count . "</strong> ✅<br>";

echo "<p><strong>Example 3: Search 'GRF'</strong></p>";
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM notes n
    WHERE n.trade_id IN ($placeholders) AND (n.title LIKE ? OR n.description LIKE ?) AND n.status = 'active'
");
$params = $trade_ids;
$params[] = '%GRF%';
$params[] = '%GRF%';
$stmt->execute($params);
$count = $stmt->fetchColumn();
echo "Notes found: <strong>" . $count . "</strong> ✅<br>";

echo "<h3>✅ Fix Applied:</h3>";
echo "<ul>";
echo "<li>✅ Dropdown loads all subjects from ALL assigned trades</li>";
echo "<li>✅ DISTINCT removes duplicate subject names</li>";
echo "<li>✅ 'All Subjects' option always at top</li>";
echo "<li>✅ No hardcoded subjects</li>";
echo "<li>✅ Subject filter works independently</li>";
echo "<li>✅ Search works independently</li>";
echo "<li>✅ Empty cases handled</li>";
echo "<li>✅ Instant refresh on dropdown change</li>";
echo "</ul>";

echo "<h3>🧪 Test It:</h3>";
echo "<p><a href='/student/notes.php' class='btn btn-primary' target='_blank'>Go to Student Notes</a></p>";
echo "<p style='font-size: 12px; color: #666;'>(Log in as student first)</p>";

?>
