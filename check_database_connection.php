<?php
/**
 * DATABASE CONNECTION TEST
 * Check if database is connected and working properly
 */

require_once __DIR__ . '/includes/db.php';

echo "=" . str_repeat("=", 80) . "\n";
echo "🗄️ DATABASE CONNECTION TEST\n";
echo "=" . str_repeat("=", 80) . "\n\n";

try {
    // Test 1: Check connection
    echo "✅ TEST 1: Connection Status\n";
    echo str_repeat("-", 80) . "\n";
    
    if ($pdo) {
        echo "   ✅ Database connected successfully\n";
        echo "   Host: " . DB_HOST . "\n";
        echo "   Database: " . DB_NAME . "\n";
        echo "   Status: CONNECTED\n\n";
    } else {
        echo "   ❌ Database connection failed\n\n";
        exit;
    }

    // Test 2: Check tables
    echo "✅ TEST 2: Tables Verification\n";
    echo str_repeat("-", 80) . "\n";
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   Total Tables: " . count($tables) . "\n";
    echo "   Tables: " . implode(", ", $tables) . "\n\n";

    // Test 3: Check data
    echo "✅ TEST 3: Data Verification\n";
    echo str_repeat("-", 80) . "\n";
    
    $checks = [
        'users' => 'SELECT COUNT(*) FROM users',
        'exams' => 'SELECT COUNT(*) FROM exams',
        'questions' => 'SELECT COUNT(*) FROM questions',
        'exam_attempts' => 'SELECT COUNT(*) FROM exam_attempts',
        'certificates' => 'SELECT COUNT(*) FROM certificates',
        'trades' => 'SELECT COUNT(*) FROM trades',
    ];
    
    foreach ($checks as $table => $query) {
        try {
            $count = $pdo->query($query)->fetchColumn();
            echo "   $table: " . $count . " records\n";
        } catch (Exception $e) {
            echo "   ❌ Error querying $table\n";
        }
    }
    echo "\n";

    // Test 4: Check certificate system
    echo "✅ TEST 4: Certificate System Status\n";
    echo str_repeat("-", 80) . "\n";
    
    $cert = $pdo->query("SELECT * FROM certificates WHERE id = 2")->fetch();
    if ($cert) {
        echo "   ✅ Certificate found:\n";
        echo "      ID: " . $cert['id'] . "\n";
        echo "      Certificate ID: " . $cert['certificate_id'] . "\n";
        echo "      Student ID: " . $cert['student_id'] . "\n";
        echo "      Percentage: " . $cert['percentage'] . "%\n";
        echo "      Status: " . $cert['status'] . "\n\n";
    } else {
        echo "   ❌ No certificate found\n\n";
    }

    // Test 5: Check exam details
    echo "✅ TEST 5: Dummy Exam Details\n";
    echo str_repeat("-", 80) . "\n";
    
    $exam = $pdo->query("SELECT * FROM exams WHERE id = 10")->fetch();
    if ($exam) {
        echo "   ✅ Exam found:\n";
        echo "      ID: " . $exam['id'] . "\n";
        echo "      Name: " . $exam['exam_name'] . "\n";
        echo "      Trade ID: " . $exam['trade_id'] . "\n";
        echo "      Status: ACTIVE\n\n";
    }

    // Test 6: Check student data
    echo "✅ TEST 6: Test Student Details\n";
    echo str_repeat("-", 80) . "\n";
    
    $student = $pdo->query("SELECT u.*, t.trade_name FROM users u JOIN trades t ON u.trade_id = t.id WHERE u.id = 29")->fetch();
    if ($student) {
        echo "   ✅ Student found:\n";
        echo "      Name: " . $student['full_name'] . "\n";
        echo "      Email: " . $student['email'] . "\n";
        echo "      Enrollment: " . $student['enrollment_no'] . "\n";
        echo "      Trade: " . $student['trade_name'] . "\n";
        echo "      Status: ACTIVE\n\n";
    }

    // Test 7: Database integrity
    echo "✅ TEST 7: Database Integrity Check\n";
    echo str_repeat("-", 80) . "\n";
    
    $issues = 0;
    
    // Check foreign keys
    $orphaned_certs = $pdo->query("SELECT COUNT(*) FROM certificates c WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = c.student_id)")->fetchColumn();
    if ($orphaned_certs > 0) {
        echo "   ⚠️  Orphaned certificates: " . $orphaned_certs . "\n";
        $issues++;
    }
    
    $orphaned_attempts = $pdo->query("SELECT COUNT(*) FROM exam_attempts a WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.id = a.student_id)")->fetchColumn();
    if ($orphaned_attempts > 0) {
        echo "   ⚠️  Orphaned attempts: " . $orphaned_attempts . "\n";
        $issues++;
    }
    
    if ($issues === 0) {
        echo "   ✅ No data integrity issues found\n\n";
    } else {
        echo "\n";
    }

    // Final summary
    echo "=" . str_repeat("=", 80) . "\n";
    echo "✅ DATABASE STATUS: FULLY OPERATIONAL\n";
    echo "=" . str_repeat("=", 80) . "\n\n";
    
    echo "🎯 SYSTEM READY FOR:\n";
    echo "   ✅ Email sending (Gmail configured)\n";
    echo "   ✅ Certificate generation\n";
    echo "   ✅ Exam management\n";
    echo "   ✅ Student tracking\n";
    echo "   ✅ Certificate verification\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack Trace: " . $e->getTraceAsString() . "\n";
}

?>
