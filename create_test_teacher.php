<?php
/**
 * Create Test Teacher Account
 */

require_once __DIR__ . '/includes/db.php';

try {
    // Get teacher role ID
    $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'teacher'");
    $roleStmt->execute();
    $role = $roleStmt->fetch();
    $teacher_role_id = $role['id'] ?? 3;  // Default to 3 if not found
    
    // Get first trade
    $tradeStmt = $pdo->prepare("SELECT id FROM trades LIMIT 1");
    $tradeStmt->execute();
    $trade = $tradeStmt->fetch();
    $trade_id = $trade['id'] ?? 1;
    
    // Check if teacher already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role_id = ?");
    $stmt->execute(['teacher@example.com', $teacher_role_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "✅ Teacher account already exists!\n";
        echo "Email: teacher@example.com\n";
        echo "Password: teacher123\n";
    } else {
        // Create teacher account
        $password = password_hash('teacher123', PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role_id, trade_id, status, approval_status, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'teacher@example.com',
            $password,
            'Test Teacher',
            $teacher_role_id,
            $trade_id,
            'active',
            'approved',
            1   // email verified
        ]);
        
        echo "✅ Test Teacher Account Created!\n";
        echo "Email: teacher@example.com\n";
        echo "Password: teacher123\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
