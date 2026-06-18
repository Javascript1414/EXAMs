<?php
require 'config.php';

echo "Certificates Table Columns:\n";
echo str_repeat("=", 50) . "\n";

$stmt = $pdo->query('DESCRIBE certificates');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")";
    if ($col['Null'] === 'NO') echo " NOT NULL";
    if (!empty($col['Default'])) echo " DEFAULT " . $col['Default'];
    echo "\n";
}
