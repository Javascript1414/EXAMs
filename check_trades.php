<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Get first trade
    $result = $pdo->query('SELECT id FROM trades LIMIT 1');
    $trade = $result->fetch(PDO::FETCH_ASSOC);
    
    if ($trade) {
        echo "First trade ID: {$trade['id']}\n";
    } else {
        echo "No trades found in database\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
