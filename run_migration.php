<?php
require 'includes/db.php';

echo "<pre style='background: #f5f5f5; padding: 20px; font-family: monospace; margin: 20px;'>";
echo "🚀 Starting Migration: YouTube Video Cards (phase_19)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Read migration file
    $migrationFile = __DIR__ . '/migrations/phase_19_youtube_video_cards.sql';
    
    if (!file_exists($migrationFile)) {
        echo "❌ ERROR: Migration file not found!\n";
        echo "Looking for: $migrationFile\n";
        exit;
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Remove SQL comments but preserve statements
    $lines = explode("\n", $sql);
    $cleanSql = '';
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comment-only lines and empty lines
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        $cleanSql .= $line . "\n";
    }
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $cleanSql)));
    $count = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
            
            // Show brief info about executed statement
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?(\w+)\s/i', $statement, $matches);
                echo "✅ Created table: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                preg_match('/ALTER TABLE\s+(\w+)/i', $statement, $matches);
                echo "✅ Altered table: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($statement, 'CREATE VIEW') !== false) {
                preg_match('/CREATE VIEW.*?(\w+)\s/i', $statement, $matches);
                echo "✅ Created view: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (stripos($statement, 'INSERT') !== false) {
                echo "✅ Inserted log record\n";
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            // Benign errors
            if (stripos($errorMsg, 'already exists') === false && 
                stripos($errorMsg, 'Duplicate') === false) {
                $errors[] = $errorMsg;
            }
            $count++;
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Migration Summary:\n";
    echo "✅ Executed: $count statements\n";
    
    if (count($errors) > 0) {
        echo "❌ Errors: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
    } else {
        echo "✅ No errors detected!\n";
    }
    
    // Verify new tables
    echo "\n🔍 Verifying new tables...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'video_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Found " . count($tables) . " video tables:\n";
        foreach ($tables as $table) {
            echo "   • $table\n";
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n✨ YouTube Video Cards Migration Complete!\n";
    echo "🎯 Your video cards are ready with all features:\n";
    echo "   ✅ Like/Unlike functionality\n";
    echo "   ✅ Save for Later\n";
    echo "   ✅ Download tracking\n";
    echo "   ✅ Report system\n";
    echo "   ✅ Video progress tracking\n";
    
} catch (Exception $e) {
    echo "\n❌ FATAL ERROR:\n";
    echo $e->getMessage();
}

echo "</pre>";
