<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>Certificate System Migration Setup</h2>";

try {
    // Run migration SQL
    $migrationSQL = file_get_contents(__DIR__ . '/phase_20_certificate_enhancement.sql');
    
    // Split by semicolon and execute each query
    $queries = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
            echo "<p style='color: green;'>✅ Executed: " . substr($query, 0, 50) . "...</p>";
        }
    }
    
    // Update existing certificates with grade and marks if missing
    echo "<h3>Updating existing certificates...</h3>";
    
    $updateStmt = $pdo->query("
        UPDATE certificates c
        JOIN results r ON c.result_id = r.id
        SET 
            c.grade = CASE 
                WHEN r.percentage >= 90 THEN 'A+'
                WHEN r.percentage >= 80 THEN 'A'
                WHEN r.percentage >= 70 THEN 'B'
                WHEN r.percentage >= 60 THEN 'C'
                ELSE 'D'
            END,
            c.obtained_marks = r.obtained_marks,
            c.total_marks = r.total_marks
        WHERE c.obtained_marks = 0 OR c.total_marks = 0
    ");
    
    echo "<p style='color: green;'>✅ Updated existing certificate records</p>";
    
    // Check certificate count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM certificates");
    $count = $countStmt->fetchColumn();
    
    echo "<h3 style='color: green;'>Setup Complete!</h3>";
    echo "<p>Total certificates in system: <strong>$count</strong></p>";
    echo "<p><a href='/admin/release_certificates.php' class='btn btn-primary'>Go to Release Certificates</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
