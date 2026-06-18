<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Check roles table
    $result = $pdo->query('DESCRIBE roles');
    echo "Roles Table Schema:\n";
    echo str_repeat("-", 50) . "\n";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\n\nRole Values:\n";
    echo str_repeat("-", 50) . "\n";
    $result = $pdo->query('SELECT * FROM roles');
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
