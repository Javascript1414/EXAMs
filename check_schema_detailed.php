<?php
/**
 * CHECK DATABASE SCHEMA IN DETAIL
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Database Schema Detailed Check</h1>";
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>\n";
    
    // Get all tables
    $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db."' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "TOTAL TABLES: " . count($tables) . "\n";
    echo "================================================================\n\n";
    
    // Check key tables
    $key_tables = [
        'study_materials',
        'exam_questions',
        'exam_attempts',
        'results',
        'certificates',
        'users'
    ];
    
    foreach ($key_tables as $table) {
        if (in_array($table, $tables)) {
            echo "TABLE: $table ✅\n";
            
            // Get columns
            $columns = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."' ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                echo "  - " . str_pad($col['COLUMN_NAME'], 25) . " " . str_pad($col['DATA_TYPE'], 15) . " " . ($col['IS_NULLABLE'] === 'NO' ? 'NOT NULL' : 'NULL') . " " . ($col['COLUMN_KEY'] ? $col['COLUMN_KEY'] : '') . "\n";
            }
            
            echo "\n";
        } else {
            echo "TABLE: $table ❌ MISSING\n\n";
        }
    }
    
    // Check if study_materials has an id column
    echo "CHECKING study_materials PRIMARY KEY:\n";
    try {
        $pk_check = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = 'study_materials' AND CONSTRAINT_NAME = 'PRIMARY'")->fetch();
        if ($pk_check) {
            echo "✅ Primary Key: " . $pk_check['COLUMN_NAME'] . "\n\n";
        } else {
            echo "❌ No Primary Key found\n\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
