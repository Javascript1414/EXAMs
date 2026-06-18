<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

// Find notes with subject-trade mismatch
$stmt = $pdo->query("
    SELECT n.id, n.title, n.trade_id, n.subject_id,
           s.subject_name, s.trade_id as subject_trade_id,
           t.trade_name, t2.trade_name as subject_trade_name
    FROM notes n
    LEFT JOIN subjects s ON n.subject_id = s.id
    LEFT JOIN trades t ON n.trade_id = t.id
    LEFT JOIN trades t2 ON s.trade_id = t2.id
    WHERE s.trade_id != n.trade_id
");
$mismatches = $stmt->fetchAll();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Notes Subject-Trade Mismatch</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 5px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error { color: #d32f2f; }
        .success { color: #388e3c; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
        button { padding: 10px 20px; background: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #1976D2; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Fix Subject-Trade Mismatch</h1>
";

if (empty($mismatches)) {
    echo "<div class='card'><p class='success'>✅ No subject-trade mismatches found!</p></div>";
} else {
    echo "<div class='card'><h3 class='error'>❌ Found " . count($mismatches) . " mismatch(es)</h3>";
    echo "<table>
        <tr>
            <th>Note ID</th>
            <th>Note Title</th>
            <th>Current Trade</th>
            <th>Subject</th>
            <th>Subject's Actual Trade</th>
            <th>Action</th>
        </tr>";
    
    foreach ($mismatches as $m) {
        echo "<tr>
            <td><strong>{$m['id']}</strong></td>
            <td>{$m['title']}</td>
            <td><span class='error'>{$m['trade_name']}</span></td>
            <td>{$m['subject_name']}</td>
            <td><span class='success'>{$m['subject_trade_name']}</span></td>
            <td>
                <form method='POST' style='display: inline;'>
                    <input type='hidden' name='note_id' value='{$m['id']}'>
                    <input type='hidden' name='new_trade_id' value='{$m['subject_trade_id']}'>
                    <button type='submit' name='action' value='fix'>Fix</button>
                </form>
            </td>
        </tr>";
    }
    echo "</table></div>";
}

// Handle fix action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'fix') {
    $note_id = (int)$_POST['note_id'];
    $new_trade_id = (int)$_POST['new_trade_id'];
    
    $stmt = $pdo->prepare("UPDATE notes SET trade_id = ? WHERE id = ?");
    if ($stmt->execute([$new_trade_id, $note_id])) {
        echo "<div class='card'><p class='success'>✅ Note updated successfully! Trade ID changed to {$new_trade_id}</p></div>";
        // Reload page
        header("Refresh: 2");
    } else {
        echo "<div class='card'><p class='error'>❌ Failed to update note</p></div>";
    }
}

echo "</div>
</body>
</html>";
?>
