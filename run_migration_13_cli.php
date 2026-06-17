<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Read migration file
    $migrationFile = __DIR__ . '/phase_13_material_bookmarks_migration.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile");
    }

    $sql = file_get_contents($migrationFile);
    
    // Split queries by semicolon
    $queries = array_filter(array_map('trim', preg_split('/;(?=\s*(?:--|$|\n))/', $sql)));
    
    $successCount = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^\s*--/', $query)) {
            try {
                $pdo->exec($query);
                $successCount++;
                echo "✓ Query executed successfully\n";
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration Summary:\n";
    echo "Successful queries: " . $successCount . "\n";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "\n";
    } else {
        echo "✓ Migration completed successfully!\n";
    }
    
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
