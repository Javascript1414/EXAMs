<?php
require_once __DIR__ . '/includes/db.php';

// Check if user is admin or superadmin
session_start();
if (!isset($_SESSION['user_id']) || (!isset($_SESSION['role_name']) || ($_SESSION['role_name'] !== 'superadmin' && $_SESSION['role_name'] !== 'admin'))) {
    die("Unauthorized access");
}

try {
    // Read migration file
    $migrationFile = __DIR__ . '/phase_11_material_ratings_migration.sql';
    
    if (!file_exists($migrationFile)) {
        die("Migration file not found: $migrationFile");
    }

    $sql = file_get_contents($migrationFile);
    
    // Split queries by semicolon, but handle trigger statements
    $queries = array_filter(array_map('trim', preg_split('/;(?=\s*(?:--|$|\n))/', $sql)));
    
    $successCount = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^\s*--/', $query)) {
            try {
                $pdo->exec($query);
                $successCount++;
                echo "✓ Query executed successfully<br>";
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
                echo "✗ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            }
        }
    }
    
    echo "<hr>";
    echo "<strong>Migration Summary:</strong><br>";
    echo "Successful queries: " . $successCount . "<br>";
    if (!empty($errors)) {
        echo "Errors: " . count($errors) . "<br>";
    } else {
        echo "<span style='color: green;'><strong>✓ Migration completed successfully!</strong></span>";
    }
    
} catch (Exception $e) {
    die("Migration failed: " . htmlspecialchars($e->getMessage()));
}
?>
