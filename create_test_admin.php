<?php
/**
 * Create Test Admin Account
 */

require_once __DIR__ . '/includes/db.php';

try {
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role_id = 2");
    $stmt->execute(['admin@example.com']);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "Admin account already exists!\n";
    } else {
        // Create admin account
        $password = password_hash('password', PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role_id, trade_id, status, approval_status, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'admin@example.com',
            $password,
            'Test Admin',
            2,  // admin role_id
            1,  // trade_id
            'active',
            'approved',
            1   // email verified
        ]);
        
        echo "✅ Test Admin Account Created!\n";
        echo "Email: admin@example.com\n";
        echo "Password: password\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
