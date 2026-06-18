<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

// Try to log in a test student
$email = 'csa.student.1@example.com';
$password = 'password123';

// Check if user exists
$stmt = $pdo->prepare("SELECT id, password, full_name, role_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Check password
    if (password_verify($password, $user['password'])) {
        // Set session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role_id'] = $user['role_id'];
        
        // Get role name
        $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
        $roleStmt->execute([$user['role_id']]);
        $role = $roleStmt->fetch();
        $_SESSION['role_name'] = $role['role_name'];
        
        echo "✅ Login successful! Redirecting to notes page...";
        header("Location: /EXAMs/student/notes.php");
        exit;
    } else {
        echo "❌ Password incorrect";
    }
} else {
    echo "❌ User not found. Trying to create test student...";
    
    // Create test student
    $hashedPassword = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 10]);
    // Get student role - check the database structure first
    $roleStmt = $pdo->query("SELECT id FROM roles LIMIT 1");
    $role = $roleStmt->fetch();
    
    if (!$role) {
        // Try alternate query
        $roleStmt = $pdo->query("SELECT id FROM roles WHERE id = 3");
        $role = $roleStmt->fetch();
    }
    
    if ($role) {
        $insertStmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role_id) 
            VALUES (?, ?, 'CSA Student 1', ?)
        ");
        $insertStmt->execute([$email, $hashedPassword, $role['id']]);
        $newUserId = $pdo->lastInsertId();
        
        // Assign to CSA trade
        $tradeStmt = $pdo->prepare("SELECT id FROM trades WHERE trade_name = 'CSA'");
        $tradeStmt->execute();
        $trade = $tradeStmt->fetch();
        
        if ($trade) {
            $assignStmt = $pdo->prepare("
                INSERT IGNORE INTO student_trades (student_id, trade_id) 
                VALUES (?, ?)
            ");
            $assignStmt->execute([$newUserId, $trade['id']]);
        }
        
        // Now log them in
        session_start();
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = 'CSA Student 1';
        $_SESSION['role_id'] = $role['id'];
        $_SESSION['role_name'] = 'student';
        
        echo "✅ Test student created and logged in! Redirecting to notes page...";
        header("Location: /EXAMs/student/notes.php");
        exit;
    }
}
?>
