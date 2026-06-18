<?php
/**
 * Create Test Student Account
 */

require_once __DIR__ . '/includes/db.php';

try {
    // Check if student exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role_id = 4");
    $stmt->execute(['student@example.com']);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "Student account already exists!\n";
    } else {
        // Get first trade
        $trade_result = $pdo->query('SELECT id FROM trades LIMIT 1');
        $trade = $trade_result->fetch(PDO::FETCH_ASSOC);
        $trade_id = $trade['id'] ?? 1;
        
        // Create student account
        $password = password_hash('password', PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role_id, trade_id, status, approval_status, email_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            'student@example.com',
            $password,
            'Test Student',
            4,  // student role_id
            $trade_id,
            'active',
            'approved',
            1   // email verified
        ]);
        
        echo "✅ Test Student Account Created!\n";
        echo "Email: student@example.com\n";
        echo "Password: password\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
