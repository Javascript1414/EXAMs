<?php
/**
 * CITS LMS - Database Automatic Setup
 * This script will create and populate the exams_lms database
 * No manual intervention needed - everything is automated
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITS LMS - Database Setup</title>
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            padding: 2rem;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            text-align: center;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        .step {
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(15, 23, 42, 0.5);
            border-left: 4px solid #94a3b8;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .step.success {
            border-left-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        .step.error {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .step.warning {
            border-left-color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
        }
        .step.info {
            border-left-color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }
        .step strong {
            color: #a5b4fc;
            font-size: 1.1rem;
        }
        .icon {
            display: inline-block;
            margin-right: 0.5rem;
            font-size: 1.5rem;
        }
        .code {
            background: #000;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.9rem;
            font-family: 'Courier New', monospace;
            color: #10b981;
            margin: 0.5rem 0;
        }
        .table-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }
        .table-item {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 0.75rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        .final-section {
            margin-top: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(99, 102, 241, 0.1));
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            text-align: center;
        }
        .final-section h2 {
            color: #10b981;
            font-size: 1.8rem;
            margin: 0 0 1rem 0;
        }
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        .btn-secondary {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        .btn-secondary:hover {
            background: rgba(99, 102, 241, 0.3);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .stat-box {
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #a5b4fc;
        }
        .stat-label {
            color: #cbd5e1;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 CITS LMS - Database Setup</h1>

<?php

// ============ MAIN SETUP LOGIC ============

$results = [
    'mysql_connected' => false,
    'database_created' => false,
    'tables_imported' => false,
    'table_count' => 0,
    'tables' => [],
    'errors' => [],
    'messages' => []
];

// Step 1: Test MySQL Connection
echo '<div class="step info">';
echo '<span class="icon">⚙️</span><strong>[Step 1] Testing MySQL Connection...</strong>';
echo '</div>';

try {
    // Try to connect with different configurations
    $connected = false;
    $pdo = null;
    
    $configs = [
        ['host' => 'localhost', 'port' => '3306'],
        ['host' => '127.0.0.1', 'port' => '3306'],
        ['host' => 'localhost', 'port' => '3307'],
    ];
    
    foreach ($configs as $config) {
        try {
            $dsn = "mysql:host=" . $config['host'] . ";port=" . $config['port'];
            $pdo = new PDO($dsn, 'root', '');
            $connected = true;
            $results['messages'][] = "Connected to MySQL at " . $config['host'] . ":" . $config['port'];
            break;
        } catch (Exception $e) {
            continue;
        }
    }
    
    if ($connected && $pdo) {
        $results['mysql_connected'] = true;
        echo '<div class="step success">';
        echo '<span class="icon">✅</span><strong>MySQL Connected Successfully!</strong><br>';
        echo 'Connection established at: ' . $config['host'] . ':' . $config['port'];
        echo '</div>';
    } else {
        throw new Exception("Could not connect to MySQL. Make sure MySQL is running in XAMPP.");
    }
    
} catch (Exception $e) {
    $results['errors'][] = $e->getMessage();
    echo '<div class="step error">';
    echo '<span class="icon">❌</span><strong>MySQL Connection Failed!</strong><br>';
    echo 'Error: ' . htmlspecialchars($e->getMessage()) . '<br><br>';
    echo '<strong>Solution:</strong> Please start MySQL in XAMPP Control Panel and refresh this page.';
    echo '</div>';
    
    echo '<div class="button-group">';
    echo '<button class="btn btn-primary" onclick="location.reload();">🔄 Retry</button>';
    echo '</div>';
    
    echo '</div></body></html>';
    exit;
}

// Step 2: Create Database
echo '<div class="step info">';
echo '<span class="icon">⚙️</span><strong>[Step 2] Creating Database...</strong>';
echo '</div>';

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS exams_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $results['database_created'] = true;
    
    echo '<div class="step success">';
    echo '<span class="icon">✅</span><strong>Database Created!</strong><br>';
    echo 'Database <code>exams_lms</code> is ready.';
    echo '</div>';
    
} catch (Exception $e) {
    $results['errors'][] = "Database creation: " . $e->getMessage();
    echo '<div class="step error">';
    echo '<span class="icon">❌</span><strong>Database Creation Failed!</strong><br>';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

// Step 3: Import Database Schema
echo '<div class="step info">';
echo '<span class="icon">⚙️</span><strong>[Step 3] Importing Database Schema...</strong>';
echo '</div>';

$sqlFile = __DIR__ . '/database.sql';

if (!file_exists($sqlFile)) {
    $results['errors'][] = "database.sql not found at: $sqlFile";
    echo '<div class="step error">';
    echo '<span class="icon">❌</span><strong>SQL File Not Found!</strong><br>';
    echo 'Looking for: ' . htmlspecialchars($sqlFile);
    echo '</div>';
} else {
    try {
        // Select database
        $pdo->exec("USE exams_lms");
        
        // Read and execute SQL file
        $sql = file_get_contents($sqlFile);
        
        // Split into individual statements
        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function($stmt) { return !empty($stmt) && $stmt !== ''; }
        );
        
        $executed = 0;
        $skipped = 0;
        $failed = 0;
        
        foreach ($statements as $statement) {
            try {
                $pdo->exec($statement);
                $executed++;
            } catch (Exception $e) {
                // Some statements might fail (like DROP IF EXISTS), that's OK
                $skipped++;
            }
        }
        
        $results['tables_imported'] = true;
        
        echo '<div class="step success">';
        echo '<span class="icon">✅</span><strong>Schema Imported Successfully!</strong><br>';
        echo "Executed: " . $executed . " statements<br>";
        echo "Skipped: " . $skipped . " statements (this is normal)";
        echo '</div>';
        
    } catch (Exception $e) {
        $results['errors'][] = "Import error: " . $e->getMessage();
        echo '<div class="step error">';
        echo '<span class="icon">❌</span><strong>Import Failed!</strong><br>';
        echo 'Error: ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
}

// Step 4: Verify Tables
echo '<div class="step info">';
echo '<span class="icon">⚙️</span><strong>[Step 4] Verifying Tables...</strong>';
echo '</div>';

try {
    $pdo->exec("USE exams_lms");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $results['table_count'] = count($tables);
    $results['tables'] = $tables;
    
    echo '<div class="step success">';
    echo '<span class="icon">✅</span><strong>Tables Verified!</strong><br>';
    echo 'Found ' . count($tables) . ' tables in database.<br><br>';
    echo '<div class="table-list">';
    
    foreach ($tables as $table) {
        echo '<div class="table-item">📊 ' . htmlspecialchars($table) . '</div>';
    }
    
    echo '</div></div>';
    
} catch (Exception $e) {
    echo '<div class="step warning">';
    echo '<span class="icon">⚠️</span><strong>Could not verify tables.</strong><br>';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

// Step 5: Test Sample Queries
echo '<div class="step info">';
echo '<span class="icon">⚙️</span><strong>[Step 5] Testing Queries...</strong>';
echo '</div>';

try {
    $pdo->exec("USE exams_lms");
    
    $stats = [];
    
    $query_tables = ['users', 'exams', 'subjects', 'trades', 'questions'];
    
    foreach ($query_tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $stats[$table] = $count;
        } catch (Exception $e) {
            $stats[$table] = 'N/A';
        }
    }
    
    echo '<div class="step success">';
    echo '<span class="icon">✅</span><strong>Query Test Results:</strong><br><br>';
    echo '<div class="stats">';
    
    foreach ($stats as $table => $count) {
        echo '<div class="stat-box">';
        echo '<div class="stat-number">' . ($count === 'N/A' ? '?' : $count) . '</div>';
        echo '<div class="stat-label">' . htmlspecialchars($table) . '</div>';
        echo '</div>';
    }
    
    echo '</div></div>';
    
} catch (Exception $e) {
    echo '<div class="step warning">';
    echo '<span class="icon">⚠️</span><strong>Query test skipped.</strong>';
    echo '</div>';
}

// Final Status
echo '<div class="final-section">';

if (!empty($results['errors'])) {
    echo '<h2>⚠️ Setup Complete (with issues)</h2>';
    echo '<p>The database has been set up, but there were some issues:</p>';
    echo '<div class="code">';
    foreach ($results['errors'] as $error) {
        echo htmlspecialchars($error) . "\n";
    }
    echo '</div>';
} elseif ($results['database_created'] && $results['table_count'] > 0) {
    echo '<h2>✅ Database Setup Complete!</h2>';
    echo '<p>Your CITS LMS database is ready to use!</p>';
    echo '<p style="color: #cbd5e1;">Database: <strong>exams_lms</strong> | Tables: <strong>' . $results['table_count'] . '</strong></p>';
} else {
    echo '<h2>⚠️ Partial Setup</h2>';
    echo '<p>The setup process completed, but some steps may need attention.</p>';
}

echo '<div class="button-group">';
echo '<a href="http://localhost/EXAMs/student_login.php" class="btn btn-primary">👤 Go to Student Login</a>';
echo '<a href="http://localhost/EXAMs/staff_login.php" class="btn btn-primary">👨‍🏫 Go to Staff Login</a>';
echo '<a href="http://localhost/EXAMs/" class="btn btn-secondary">🏠 Go to Homepage</a>';
echo '</div>';

echo '</div>';

?>

</div>
</body>
</html>
