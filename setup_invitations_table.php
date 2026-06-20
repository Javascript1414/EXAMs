<?php
/**
 * Diagnose and fix practical_exam_invitations table creation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    echo "<h2>🔍 Database Diagnostic Report</h2>";
    
    // Check if practical_exams table exists
    echo "<h3>1️⃣  Checking practical_exams table...</h3>";
    try {
        $result = $pdo->query("SELECT COUNT(*) as count FROM practical_exams");
        $count = $result->fetch()['count'];
        echo "<p style='color: green;'>✅ practical_exams table exists ($count exams)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ practical_exams table NOT found</p>";
    }
    
    // Check if users table exists
    echo "<h3>2️⃣  Checking users table...</h3>";
    try {
        $result = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch()['count'];
        echo "<p style='color: green;'>✅ users table exists ($count users)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ users table NOT found</p>";
    }
    
    // Try to drop old table if exists
    echo "<h3>3️⃣  Cleaning up old table...</h3>";
    try {
        $pdo->exec("DROP TABLE IF EXISTS practical_exam_invitations");
        echo "<p style='color: green;'>✅ Old table dropped (if it existed)</p>";
    } catch (Exception $e) {
        echo "<p>⚠️  Note: " . $e->getMessage() . "</p>";
    }
    
    // Create table WITHOUT foreign keys first
    echo "<h3>4️⃣  Creating practical_exam_invitations table...</h3>";
    $create_sql = "CREATE TABLE `practical_exam_invitations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `practical_exam_id` INT NOT NULL,
        `invitation_code` VARCHAR(64) NOT NULL UNIQUE,
        `invitation_url` VARCHAR(255),
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` DATETIME,
        `max_uses` INT DEFAULT NULL,
        `used_count` INT DEFAULT 0,
        `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
        KEY `idx_code` (`invitation_code`),
        KEY `idx_exam_id` (`practical_exam_id`),
        KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_sql);
    echo "<p style='color: green;'>✅ Table created successfully (without foreign keys for now)</p>";
    
    // Verify structure
    echo "<h3>5️⃣  Table Structure:</h3>";
    echo "<pre>";
    $columns_stmt = $pdo->query("DESCRIBE practical_exam_invitations");
    $columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        printf("%-25s %-35s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] === 'YES' ? '(nullable)' : '(required)'
        );
    }
    echo "</pre>";
    
    echo "<h3 style='color: green;'>✅ Setup Complete!</h3>";
    echo "<p>You can now generate exam links.</p>";
    echo "<p><a href='admin/practical_exams.php' class='btn btn-primary btn-lg' style='margin-top: 20px;'>Go to Admin Panel</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Error Code: " . htmlspecialchars($e->getCode()) . "</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 700px; margin-top: 30px; }
        h2 { color: #667eea; margin-bottom: 30px; font-weight: bold; }
        h3 { color: #333; margin-top: 20px; font-weight: 600; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Practical Exam Management - Database Setup</h1>
    </div>
</body>
</html>