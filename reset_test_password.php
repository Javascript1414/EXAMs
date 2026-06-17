<?php
require_once 'includes/db.php';

$email = 'soumo1301@gmail.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Set a test password
    $newPassword = 'test123456';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $updateStmt->execute([$hashedPassword, $user['id']]);
    echo "✓ Password reset to: $newPassword\n";
    echo "Email: $email\n";
} else {
    echo "User not found\n";
}
?>
