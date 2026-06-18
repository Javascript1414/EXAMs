<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔧 Creating Student Trades Junction Table</h2>";

// Create student_trades table
$sql = "
CREATE TABLE IF NOT EXISTS student_trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT(20) UNSIGNED NOT NULL,
    trade_id INT(10) UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT(20) UNSIGNED,
    
    UNIQUE KEY unique_student_trade (student_id, trade_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    echo "<p style='color: green;'><strong>✅ student_trades table created!</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: orange;'><strong>⚠️ Table already exists or error: " . $e->getMessage() . "</strong></p>";
}

// Migrate existing data: Add current trade_id to student_trades for all students
echo "<h3>Migrating Existing Student Trades...</h3>";

$stmt = $pdo->query("
    SELECT DISTINCT u.id, u.trade_id 
    FROM users u 
    WHERE u.trade_id IS NOT NULL AND u.role_id = 3
");
$students = $stmt->fetchAll();

$migrated = 0;
foreach ($students as $student) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM student_trades WHERE student_id = ? AND trade_id = ?");
    $check->execute([$student['id'], $student['trade_id']]);
    
    if ($check->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO student_trades (student_id, trade_id) VALUES (?, ?)");
        $insert->execute([$student['id'], $student['trade_id']]);
        $migrated++;
    }
}

echo "<p>✅ Migrated $migrated students' existing trades to junction table.</p>";

// Show final status
echo "<h3>✅ Migration Complete!</h3>";
echo "<p>Now students can have MULTIPLE trades assigned!</p>";

// Verify
$count = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM student_trades")->fetchColumn();
echo "<p><strong>Total students with trades: $count</strong></p>";

?>
