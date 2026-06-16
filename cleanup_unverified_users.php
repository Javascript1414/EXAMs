<?php
/**
 * Cleanup Unverified Users
 * Delete users who haven't verified OTP within X hours
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>🧹 Cleanup Unverified Users</h2>";
echo "<hr>";

try {
    // Delete users that are still inactive after 24 hours (unverified)
    $cleanup_hours = 24;
    
    $stmt = $pdo->prepare("
        SELECT id, email, phone, created_at 
        FROM users 
        WHERE status = 'inactive' 
        AND email_verified = FALSE 
        AND created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
    ");
    
    $stmt->execute([$cleanup_hours]);
    $unverified_users = $stmt->fetchAll();
    
    echo "<p>Found <strong>" . count($unverified_users) . "</strong> unverified users older than $cleanup_hours hours</p>";
    
    if (count($unverified_users) > 0) {
        echo "<p>Users to delete:</p>";
        echo "<ul>";
        foreach ($unverified_users as $user) {
            echo "<li>" . $user['email'] . " (Created: " . $user['created_at'] . ")</li>";
        }
        echo "</ul>";
        
        // Delete the unverified users
        $delete_stmt = $pdo->prepare("
            DELETE FROM users 
            WHERE status = 'inactive' 
            AND email_verified = FALSE 
            AND created_at < ?
        ");
        
        $delete_stmt->execute([$cleanup_time]);
        $deleted = $delete_stmt->rowCount();
        
        echo "<p style='color: green;'><strong>✅ Deleted $deleted unverified users</strong></p>";
    } else {
        echo "<p style='color: blue;'>No unverified users to cleanup</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This script should run periodically (via CRON job)</p>";
echo "<p><strong>Recommended:</strong> Run daily to cleanup users who registered but didn't verify email</p>";
?>
