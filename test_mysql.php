<?php
// Test database connection
echo "Testing MySQL Connection...\n\n";

// Try connection
try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
    echo "✅ MySQL Connected!\n";
    echo "MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";
    
    // Check if exams_lms exists
    $result = $pdo->query("SHOW DATABASES LIKE 'exams_lms'")->fetchAll();
    if (!empty($result)) {
        echo "✅ Database 'exams_lms' exists\n";
    } else {
        echo "❌ Database 'exams_lms' does NOT exist\n";
        echo "Creating database...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS exams_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database created!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ MySQL Error: " . $e->getMessage() . "\n";
    echo "Make sure MySQL is running!\n";
}
?>
