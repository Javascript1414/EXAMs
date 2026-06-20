<?php
// Import database.sql automatically
echo "Preparing to import database...\n";

$dbFile = __DIR__ . '/database.sql';

if (!file_exists($dbFile)) {
    die("❌ database.sql file not found!");
}

echo "✅ Found database.sql\n";
echo "File size: " . filesize($dbFile) . " bytes\n";
echo "\nTo import:\n";
echo "1. Open phpMyAdmin: http://localhost/phpmyadmin\n";
echo "2. Click 'Import' tab\n";
echo "3. Choose file: " . $dbFile . "\n";
echo "4. Click 'Go'\n";
?>
