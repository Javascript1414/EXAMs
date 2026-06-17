<?php
/**
 * ER Diagram Generator
 * Creates a visual ER diagram from database schema
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Get all tables
$tables_result = $pdo->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
$tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);

$tables_data = [];

foreach ($tables as $table) {
    // Get columns
    $columns_result = $pdo->query("DESCRIBE $table");
    $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
    
    // Get foreign keys
    $fk_result = $pdo->query("
        SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $foreign_keys = $fk_result->fetchAll(PDO::FETCH_ASSOC);
    
    $tables_data[$table] = [
        'columns' => $columns,
        'foreign_keys' => $foreign_keys
    ];
}

echo json_encode($tables_data, JSON_PRETTY_PRINT);
?>
