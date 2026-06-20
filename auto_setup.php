<?php
/**
 * Complete Database Setup & Auto-Starter
 * This file will:
 * 1. Connect to MySQL
 * 2. Create database if needed
 * 3. Import SQL file
 */

set_time_limit(300);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>CITS LMS - Database Setup</title>
    <style>
        body { font-family: Arial; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 8px; }
        .step { margin: 1rem 0; padding: 1rem; background: #0f172a; border-left: 4px solid #6366f1; }
        .success { border-left-color: #10b981; }
        .error { border-left-color: #ef4444; }
        .warning { border-left-color: #f59e0b; }
        pre { background: #000; padding: 1rem; overflow-x: auto; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Database Setup</h1>";

// Try to connect
$connected = false;
$pdo = null;

echo "<div class='step warning'>
    <strong>[1] Connecting to MySQL...</strong><br>
    Trying: localhost, user: root, password: (empty)
</div>";

try {
    // Create PDO connection without database first
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $connected = true;
    echo "<div class='step success'>✅ MySQL Connection Successful!</div>";
} catch (PDOException $e) {
    echo "<div class='step error'>
        ❌ MySQL Connection Failed<br>
        Error: " . htmlspecialchars($e->getMessage()) . "<br>
        <strong>Solution:</strong> Please start MySQL in XAMPP Control Panel
    </div>";
    echo "</div></body></html>";
    exit;
}

// Create database
echo "<div class='step warning'><strong>[2] Creating Database...</strong></div>";
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS exams_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='step success'>✅ Database 'exams_lms' created/exists</div>";
    
    // Select database
    $pdo->exec("USE exams_lms");
} catch (Exception $e) {
    echo "<div class='step error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Check tables
echo "<div class='step warning'><strong>[3] Checking Tables...</strong></div>";
try {
    $result = $pdo->query("SHOW TABLES")->fetchAll();
    $tableCount = count($result);
    
    if ($tableCount > 0) {
        echo "<div class='step success'>✅ Found " . $tableCount . " tables</div>";
        echo "<div class='step'>Tables: <br>";
        foreach ($result as $row) {
            $tableName = array_values($row)[0];
            echo "• " . htmlspecialchars($tableName) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='step warning'>⚠️ No tables found - importing database.sql...</div>";
        
        // Import database.sql
        $sqlFile = __DIR__ . '/database.sql';
        
        if (file_exists($sqlFile)) {
            echo "<div class='step'><strong>Found:</strong> " . htmlspecialchars($sqlFile) . "</div>";
            
            // Read and execute SQL
            $sql = file_get_contents($sqlFile);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $executed = 0;
            $errors = [];
            
            foreach ($statements as $statement) {
                if (empty($statement)) continue;
                
                try {
                    $pdo->exec($statement);
                    $executed++;
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            echo "<div class='step success'>
                ✅ Imported " . $executed . " SQL statements<br>
                Errors: " . count($errors) . "
            </div>";
            
            if (!empty($errors)) {
                echo "<div class='step error'><pre>" . htmlspecialchars(implode("\n", array_slice($errors, 0, 5))) . "</pre></div>";
            }
        } else {
            echo "<div class='step error'>❌ database.sql not found at: " . htmlspecialchars($sqlFile) . "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='step error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Test queries
echo "<div class='step warning'><strong>[4] Testing Queries...</strong></div>";
try {
    $users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0;
    $exams = $pdo->query("SELECT COUNT(*) as count FROM exams")->fetch()['count'] ?? 0;
    
    echo "<div class='step success'>
        ✅ Users: " . $users . "<br>
        ✅ Exams: " . $exams . "
    </div>";
} catch (Exception $e) {
    echo "<div class='step warning'>⚠️ Some tables may not exist yet</div>";
}

echo "
<div class='step success' style='border-left-color: #10b981; text-align: center;'>
    <h2>✅ DATABASE READY!</h2>
    <p>You can now use the application</p>
    <p><a href='student_login.php' style='color: #6366f1; text-decoration: none;'>→ Go to Student Login</a></p>
</div>

</div>
</body>
</html>";
?>
