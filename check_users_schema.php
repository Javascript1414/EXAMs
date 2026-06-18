<?php
require_once __DIR__ . '/includes/db.php';

try {
    $result = $pdo->query('DESCRIBE users');
    echo "Users Table Schema:\n";
    echo str_repeat("-", 50) . "\n";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
