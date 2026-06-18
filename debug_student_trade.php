<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_trade = $_SESSION['trade_id'] ?? 'NOT SET';

echo "<h2>Student Notes Debug</h2>";
echo "<p><strong>Your User ID:</strong> $user_id</p>";
echo "<p><strong>Your Trade ID:</strong> $user_trade</p>";

try {
    // Get user details
    $stmt = $pdo->prepare("SELECT id, full_name, trade_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    echo "<p><strong>Your Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
    echo "<p><strong>Your Trade (from DB):</strong> " . $user['trade_id'] . "</p>";

    if ($user['trade_id']) {
        // Get trade name
        $stmt = $pdo->prepare("SELECT trade_name FROM trades WHERE id = ?");
        $stmt->execute([$user['trade_id']]);
        $trade = $stmt->fetch();
        echo "<p><strong>Trade Name:</strong> " . htmlspecialchars($trade['trade_name']) . "</p>";

        // Get subjects for this trade
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE trade_id = ? ORDER BY subject_name");
        $stmt->execute([$user['trade_id']]);
        $subjects = $stmt->fetchAll();
        
        echo "<h3>Subjects for your trade (" . count($subjects) . ")</h3>";
        echo "<table border='1' cellpadding='10'>";
        foreach ($subjects as $s) {
            echo "<tr><td>" . htmlspecialchars($s['subject_name']) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'><strong>⚠ Your trade_id is NOT SET!</strong> This is the problem.</p>";
    }

    // Show ALL subjects in system
    echo "<h3>ALL Subjects in Database</h3>";
    $all = $pdo->query("SELECT s.*, t.trade_name FROM subjects s JOIN trades t ON s.trade_id = t.id ORDER BY t.trade_name, s.subject_name")->fetchAll();
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Trade</th><th>Subject</th></tr>";
    foreach ($all as $s) {
        echo "<tr><td>" . htmlspecialchars($s['trade_name']) . "</td><td>" . htmlspecialchars($s['subject_name']) . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
