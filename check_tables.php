<?php
require_once __DIR__ . '/includes/db.php';

// Check if table exists
$result = $pdo->query("SHOW TABLES LIKE 'material_bookmarks'")->fetch();

echo "Table exists: ";
var_dump($result);

// List all tables
echo "\n\nAll tables in database:\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "- " . $table . "\n";
}
?>
