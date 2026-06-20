<?php
require 'config.php';
require 'includes/db.php';

echo "Admin Password Reset Tool\n";
echo "=======================\n\n";

$email = 'admin@example.com';

// Check if user exists
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Error: User with email '{$email}' not found\n";
    exit;
}

// Generate new password
$new_password = 'Admin@123456';
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update password
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashed_password, $user['id']]);

echo "✓ Password reset successful!\n\n";
echo "User: {$user['full_name']}\n";
echo "Email: {$email}\n";
echo "New Password: {$new_password}\n\n";
echo "⚠️  Make sure to:\n";
echo "   1. Log in with the new password\n";
echo "   2. Change it to a secure password in your account settings\n";
?>
