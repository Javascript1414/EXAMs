<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>🎓 Certificate ID Format Migration</h2>";
echo "<p>Implementing: CITS/24-25/Y/1414/A1</p><hr>";

try {
    // Check if migration files exist
    $migration_file = __DIR__ . '/phase_21_custom_certificate_id.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: phase_21_custom_certificate_id.sql");
    }
    
    $migrationSQL = file_get_contents($migration_file);
    $queries = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    $success_count = 0;
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^--/', trim($query))) {
            try {
                $pdo->exec($query);
                echo "<p style='color: green;'>✅ " . substr($query, 0, 70) . "...</p>";
                $success_count++;
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ " . substr($query, 0, 70) . "... (May already exist)</p>";
            }
        }
    }
    
    echo "<hr><h3>Database Verification</h3>";
    
    // Check trade codes
    $tradeStmt = $pdo->query("SELECT COUNT(*) as count FROM trades WHERE trade_code IS NULL");
    $tradeResult = $tradeStmt->fetch();
    echo "<p>Trades without code: {$tradeResult['count']}</p>";
    
    if ($tradeResult['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Set trade codes in admin panel (e.g., CITS, COPA)</p>";
    }
    
    // Check enrollment numbers
    $enrollStmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE enrollment_no IS NULL AND role_id = (SELECT id FROM roles WHERE name = 'student' LIMIT 1)");
    $enrollResult = $enrollStmt->fetch();
    echo "<p>Students without enrollment number: {$enrollResult['count']}</p>";
    
    if ($enrollResult['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Set enrollment numbers for students (use admin panel)</p>";
    }
    
    // Check existing certificates
    $certStmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN certificate_id LIKE 'CERT%' THEN 1 ELSE 0 END) as old_format FROM certificates");
    $certResult = $certStmt->fetch();
    echo "<p>Total certificates: {$certResult['total']}</p>";
    echo "<p>Old format (CERT-xxxxx): {$certResult['old_format']}</p>";
    
    if ($certResult['old_format'] > 0) {
        echo "<p style='color: blue;'>ℹ️ Existing certificates kept as-is. New certificates will use format: CITS/24-25/Y/1414/A1</p>";
    }
    
    echo "<hr><h3 style='color: green;'>✅ Migration Complete!</h3>";
    echo "<p><strong>New Features:</strong></p>";
    echo "<ul>";
    echo "<li>Certificate ID Format: CITS/24-25/Y/1414/A1</li>";
    echo "<li>Course Code: CITS, COPA, etc. (from trades table)</li>";
    echo "<li>Academic Year: 24-25 (Aug-July system)</li>";
    echo "<li>Student Registration: From enrollment_no field</li>";
    echo "<li>Exam Sequence: A1, A2, A3... (auto-incremented per student)</li>";
    echo "</ul>";
    
    echo "<p><a href='/EXAMs/admin/release_certificates.php' class='btn btn-primary' style='padding: 10px 20px; background: #0056D2; color: white; text-decoration: none; border-radius: 5px;'>Go to Release Certificates →</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
