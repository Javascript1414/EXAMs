<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Table Structures</h2>";

foreach(['users', 'exams', 'subjects', 'trades'] as $table) {
    echo "<h3>$table</h3><pre>";
    $result = $pdo->query("DESCRIBE $table");
    $cols = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo $col['Field'] . " -> " . $col['Type'] . " (NULL: " . $col['Null'] . ")\n";
    }
    echo "</pre>";
}
?>
