<?php
/**
 * Check Foreign Key Constraints Status
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check foreign key status
    $result = $pdo->query('SELECT @@FOREIGN_KEY_CHECKS as status')->fetch(PDO::FETCH_ASSOC);
    echo "<h2>Foreign Key Checks Status: " . ($result['status'] ? '✅ ENABLED' : '❌ DISABLED') . "</h2>\n\n";
    
    // Get all foreign keys with referential constraints
    $sql = "SELECT 
                kcu.CONSTRAINT_NAME,
                kcu.TABLE_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
             FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
             LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
             WHERE kcu.TABLE_SCHEMA = '".$db."' 
             AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME";
    
    $fks = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>\n";
    
    if (count($fks) > 0) {
        echo "FOREIGN KEYS FOUND (" . count($fks) . "):\n";
        echo "================================================================\n\n";
        foreach ($fks as $fk) {
            echo "📌 Table: " . $fk['TABLE_NAME'] . "\n";
            echo "   Column: " . $fk['COLUMN_NAME'] . "\n";
            echo "   → References: " . $fk['REFERENCED_TABLE_NAME'] . "." . $fk['REFERENCED_COLUMN_NAME'] . "\n";
            echo "   On Update: " . $fk['UPDATE_RULE'] . "\n";
            echo "   On Delete: " . $fk['DELETE_RULE'] . "\n";
            echo "   Constraint: " . $fk['CONSTRAINT_NAME'] . "\n";
            echo "\n";
        }
    } else {
        echo "❌ NO FOREIGN KEYS FOUND!\n";
    }
    
    echo "================================================================\n\n";
    
    // Check database settings
    echo "DATABASE SETTINGS:\n";
    $settings = $pdo->query("SHOW VARIABLES LIKE '%FOREIGN%'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings as $setting) {
        echo "  " . $setting['Variable_name'] . " = " . $setting['Value'] . "\n";
    }
    
    echo "</pre>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error: " . $e->getMessage() . "</h3>";
}
?>
