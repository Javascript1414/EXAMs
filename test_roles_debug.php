<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Testing deleted_users_archive roles ===\n\n";

// Check table structure
echo "1. Table Columns:\n";
$result = $pdo->query("DESCRIBE deleted_users_archive");
foreach($result->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

// Check roles in table
echo "\n2. Available Roles (non-restored):\n";
$roles = $pdo->query("SELECT DISTINCT role_name FROM deleted_users_archive WHERE restored_at IS NULL ORDER BY role_name");
$role_list = $roles->fetchAll(PDO::FETCH_ASSOC);

if (empty($role_list)) {
    echo "   No roles found (table might be empty)\n";
} else {
    foreach($role_list as $role) {
        echo "   - {$role['role_name']}\n";
    }
}

// Check total deleted users
echo "\n3. Total Deleted Users (non-restored):\n";
$count = $pdo->query("SELECT COUNT(*) as total FROM deleted_users_archive WHERE restored_at IS NULL")->fetch();
echo "   Count: {$count['total']}\n";

echo "\n✓ Debug complete\n";
?>
