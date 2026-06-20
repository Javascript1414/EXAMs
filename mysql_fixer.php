<?php
/**
 * XAMPP MySQL Connection Fixer
 * This script auto-detects and fixes database connection issues
 */

echo "=================================\n";
echo "XAMPP MySQL Connection Fixer\n";
echo "=================================\n\n";

// Test 1: Basic MySQL Connection
echo "[1] Testing MySQL Connection...\n";
try {
    // Try default XAMPP credentials
    $connections = [
        ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
        ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
        ['host' => 'localhost:3306', 'user' => 'root', 'pass' => ''],
    ];
    
    $connected = false;
    $pdo = null;
    
    foreach ($connections as $conn) {
        try {
            $dsn = "mysql:host=" . str_replace(':3306', '', $conn['host']);
            $pdo = new PDO($dsn, $conn['user'], $conn['pass']);
            $connected = true;
            echo "✅ Connected using: " . $conn['host'] . " with user: " . $conn['user'] . "\n";
            break;
        } catch (Exception $e) {
            continue;
        }
    }
    
    if (!$connected) {
        echo "❌ Could not connect to MySQL\n";
        echo "Make sure MySQL is running!\n";
        die();
    }
    
    // Test 2: Check if database exists
    echo "\n[2] Checking database 'exams_lms'...\n";
    $result = $pdo->query("SHOW DATABASES LIKE 'exams_lms'")->fetchAll();
    
    if (empty($result)) {
        echo "⚠️  Database does not exist, creating...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS exams_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database 'exams_lms' created!\n";
    } else {
        echo "✅ Database 'exams_lms' exists\n";
    }
    
    // Test 3: Import database.sql if needed
    echo "\n[3] Checking tables...\n";
    $pdo->exec("USE exams_lms");
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    $tableCount = count($tables);
    
    if ($tableCount === 0) {
        echo "⚠️  No tables found. Need to import database.sql\n";
        echo "✅ To import:\n";
        echo "   1. Visit: http://localhost/phpmyadmin\n";
        echo "   2. Select 'exams_lms' database\n";
        echo "   3. Click 'Import' tab\n";
        echo "   4. Choose: " . __DIR__ . "/database.sql\n";
        echo "   5. Click 'Go'\n";
    } else {
        echo "✅ Found " . $tableCount . " tables\n";
        
        // List tables
        echo "\nTables in database:\n";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "   - " . $tableName . "\n";
        }
    }
    
    // Test 4: Test query
    echo "\n[4] Testing sample query...\n";
    try {
        $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
        echo "✅ Users table: " . $result['count'] . " records\n";
    } catch (Exception $e) {
        echo "⚠️  Users table query failed (table may not exist yet)\n";
    }
    
    echo "\n=================================\n";
    echo "✅ MySQL Connection is WORKING!\n";
    echo "=================================\n";
    echo "\nNext steps:\n";
    echo "1. Visit: http://localhost/EXAMs/student_login.php\n";
    echo "2. Or visit: http://localhost/EXAMs/staff_login.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTROUBLESHOOTING:\n";
    echo "1. Make sure MySQL is running\n";
    echo "2. Open XAMPP Control Panel\n";
    echo "3. Click 'Start' next to MySQL\n";
    echo "4. Refresh this page\n";
}
?>
