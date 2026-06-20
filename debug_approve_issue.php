<?php
require 'config.php';
require 'includes/db.php';

// Check current session user
echo "=== SESSION INFO ===\n";
session_start();
echo "Current Admin User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Current Admin Role: " . ($_SESSION['role_name'] ?? 'NOT SET') . "\n";
echo "\n";

// Check users with ID 1 and 2
echo "=== DATABASE USERS ===\n";
$stmt = $pdo->query("SELECT id, full_name, email, role_id FROM users WHERE id IN (1,2) ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "ID: " . $user['id'] . " | Name: " . $user['full_name'] . " | Email: " . $user['email'] . " | Role ID: " . $user['role_id'] . "\n";
}

// Check if there are duplicate IDs
echo "\n=== CHECKING FOR DUPLICATE IDs ===\n";
$dupStmt = $pdo->query("SELECT id, COUNT(*) as count FROM users GROUP BY id HAVING count > 1");
$dups = $dupStmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($dups)) {
    echo "✓ No duplicate IDs found\n";
} else {
    echo "✗ DUPLICATE IDs FOUND:\n";
    foreach ($dups as $dup) {
        echo "  ID " . $dup['id'] . " appears " . $dup['count'] . " times\n";
    }
}

// Get user being approved
echo "\n=== APPROVAL INFO ===\n";
$approveStmt = $pdo->query("SELECT id, full_name, email, approval_status FROM users WHERE full_name LIKE '%SOUMYAJIT%'");
$approveUser = $approveStmt->fetch(PDO::FETCH_ASSOC);
if ($approveUser) {
    echo "User to Approve:\n";
    echo "  ID: " . $approveUser['id'] . "\n";
    echo "  Name: " . $approveUser['full_name'] . "\n";
    echo "  Email: " . $approveUser['email'] . "\n";
    echo "  Status: " . $approveUser['approval_status'] . "\n";
} else {
    echo "✗ SOUMYAJIT SANTRA not found\n";
}
?>
