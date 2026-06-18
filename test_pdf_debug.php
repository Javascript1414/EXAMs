<?php
/**
 * Debug PDF Preview System
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Test 1: Check if notes exist
try {
    requireLogin();
    
    $studentId = $_SESSION['user_id'] ?? null;
    if (!$studentId) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }
    
    // Get student's assigned trades
    $query = "SELECT trade_id FROM student_trades WHERE student_id = ? AND status = 'active'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$studentId]);
    $assigned_trades = $stmt->fetchAll();
    
    echo "=== PDF PREVIEW DEBUG ===\n\n";
    echo "1. STUDENT INFO\n";
    echo "   Student ID: $studentId\n";
    echo "   Assigned Trades: " . count($assigned_trades) . "\n";
    foreach ($assigned_trades as $trade) {
        echo "     - Trade ID: " . $trade['trade_id'] . "\n";
    }
    
    // Get notes
    echo "\n2. NOTES FROM DATABASE\n";
    $trade_ids = array_column($assigned_trades, 'trade_id');
    if (!empty($trade_ids)) {
        $placeholders = implode(',', array_fill(0, count($trade_ids), '?'));
        $query = "SELECT n.*, s.subject_name, t.trade_name FROM notes n
                  JOIN trades t ON n.trade_id = t.id
                  JOIN subjects s ON n.subject_id = s.id
                  WHERE n.trade_id IN ($placeholders) AND n.status = 'active'
                  LIMIT 5";
        $stmt = $pdo->prepare($query);
        $stmt->execute($trade_ids);
        $notes = $stmt->fetchAll();
        
        echo "   Total Notes: " . count($notes) . "\n";
        foreach ($notes as $note) {
            echo "   - Note ID: " . $note['id'] . "\n";
            echo "     Title: " . htmlspecialchars($note['title']) . "\n";
            echo "     File: " . htmlspecialchars($note['file_path']) . "\n";
            
            // Check if file exists
            $full_path = __DIR__ . '/' . $note['file_path'];
            $file_exists = file_exists($full_path);
            $file_readable = is_readable($full_path);
            $file_size = file_exists($full_path) ? filesize($full_path) : 0;
            
            echo "     File Exists: " . ($file_exists ? 'YES' : 'NO') . "\n";
            echo "     File Readable: " . ($file_readable ? 'YES' : 'NO') . "\n";
            echo "     File Size: " . ($file_size > 0 ? ($file_size / 1024 / 1024) . ' MB' : '0 bytes') . "\n";
        }
    } else {
        echo "   No assigned trades!\n";
    }
    
    // Test 3: Test API endpoints
    echo "\n3. API ENDPOINTS TEST\n";
    if (!empty($notes)) {
        $note = $notes[0];
        $check_url = BASE_URL . '/api/check-pdf.php?file=' . urlencode($note['file_path']);
        $serve_url = BASE_URL . '/api/serve-pdf.php?file=' . urlencode($note['file_path']);
        
        echo "   Check URL: $check_url\n";
        echo "   Serve URL: $serve_url\n";
        
        // Test check-pdf endpoint
        echo "\n   Testing /api/check-pdf.php:\n";
        $ch = curl_init($check_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE => 'PHPSESSID=' . session_id(),
        ]);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "     HTTP Status: $http_code\n";
        echo "     Response: " . substr($result, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
