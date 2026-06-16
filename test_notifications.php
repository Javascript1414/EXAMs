<?php
// Quick test to verify notifications page works

ob_start();

try {
    // Simulate a login
    $_SESSION['user_id'] = 1;
    $_SESSION['role_name'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
    
    require_once __DIR__ . '/admin/notifications.php';
    
    $output = ob_get_clean();
    
    if (strpos($output, 'Fatal') !== false || strpos($output, 'PDOException') !== false) {
        echo "❌ Error found in notifications page\n";
        echo $output;
    } else {
        echo "✅ Notifications page loads successfully!\n";
        echo "Output length: " . strlen($output) . " bytes\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
?>
