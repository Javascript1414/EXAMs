<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Check user_profiles table
echo "=== USER_PROFILES TABLE ===\n";
try {
    $result = $pdo->query('DESCRIBE user_profiles')->fetchAll();
    foreach ($result as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== EXAM_ATTEMPTS TABLE ===\n";
try {
    $result = $pdo->query('DESCRIBE exam_attempts')->fetchAll();
    foreach ($result as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== MATERIAL_BOOKMARKS TABLE ===\n";
try {
    $result = $pdo->query('DESCRIBE material_bookmarks')->fetchAll();
    foreach ($result as $column) {
        echo $column['Field'] . " - " . $column['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
