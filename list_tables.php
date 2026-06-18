<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Get all tables
    $result = $pdo->query('SHOW TABLES');
    echo "Tables in database:\n";
    while($row = $result->fetch(PDO::FETCH_NUM)) {
        echo "- {$row[0]}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
