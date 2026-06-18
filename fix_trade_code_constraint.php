<?php
/**
 * Fix Trade Code Constraint Issue
 * Remove problematic default and allow NULL for trade_code
 */

require_once __DIR__ . '/includes/db.php';

echo "🔧 Fixing trade_code constraint issue...\n";
echo str_repeat("=", 70) . "\n\n";

try {
    // Step 1: Drop the existing UNIQUE constraint if it exists
    echo "Step 1: Removing old UNIQUE constraint...\n";
    try {
        $pdo->query("ALTER TABLE trades DROP INDEX trade_code");
        echo "   ✅ Index removed\n";
    } catch (Exception $e) {
        echo "   ℹ️  Index didn't exist\n";
    }

    // Step 2: Modify the column to allow NULL and no default
    echo "Step 2: Modifying trade_code column...\n";
    $pdo->query("ALTER TABLE trades MODIFY COLUMN trade_code VARCHAR(20) UNIQUE NULL");
    echo "   ✅ Column modified\n";

    // Step 3: Update existing 'UNKNOWN' values to NULL
    echo "Step 3: Cleaning up existing data...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM trades WHERE trade_code = 'UNKNOWN'");
    $result = $stmt->fetch();
    $count = $result['cnt'];
    
    if ($count > 0) {
        $pdo->query("UPDATE trades SET trade_code = NULL WHERE trade_code = 'UNKNOWN'");
        echo "   ✅ Updated $count rows from 'UNKNOWN' to NULL\n";
    } else {
        echo "   ℹ️  No 'UNKNOWN' values found\n";
    }

    // Step 4: Add unique index on trade_code for non-NULL values
    echo "Step 4: Adding unique constraint for non-NULL values...\n";
    try {
        $pdo->query("ALTER TABLE trades ADD UNIQUE KEY uk_trade_code (trade_code)");
        echo "   ✅ Unique constraint added\n";
    } catch (Exception $e) {
        echo "   ✅ Constraint already exists\n";
    }

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "✅ Trade code constraint fixed successfully!\n\n";

    // Show current trades
    echo "📋 Current Trades:\n";
    echo str_repeat("-", 70) . "\n";
    $trades = $pdo->query("SELECT * FROM trades ORDER BY id")->fetchAll();
    foreach ($trades as $trade) {
        echo "  ID: {$trade['id']} | Name: {$trade['trade_name']} | Code: " . ($trade['trade_code'] ?? 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
