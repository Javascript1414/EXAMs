<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Check roles table
    $result = $pdo->query('SELECT role_id, role_name FROM roles');
    echo "Roles:\n";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['role_id']} - {$row['role_name']}\n";
    }
    
    // Check existing users
    echo "\n\nExisting Admin Users:\n";
    $result = $pdo->query('
        SELECT u.id, u.email, u.full_name, r.role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.role_id 
        WHERE r.role_name IN ("admin", "superadmin")
    ');
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "Email: {$row['email']} - {$row['full_name']} ({$row['role_name']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
