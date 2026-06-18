<?php
/**
 * Check Admin Account
 * Verifies if admin account exists, creates one if missing
 */

require_once 'config.php';
require_once 'includes/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Admin Account</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .admin-list {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover { background: #f5f5f5; }
        .success {
            background: #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #4caf50;
            margin: 15px 0;
        }
        .warning {
            background: #ffe0b2;
            color: #e65100;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ff9800;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Admin Account Check</h1>
        
        <?php
        try {
            // Get admin and superadmin users
            $admin_query = $pdo->query("
                SELECT u.id, u.full_name, u.email, u.phone, u.email_verified, u.approval_status, r.name as role_name
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE r.name IN ('admin', 'superadmin')
                ORDER BY u.created_at DESC
            ");
            $admins = $admin_query->fetchAll(PDO::FETCH_ASSOC);
            
            if ($admins && count($admins) > 0) {
                echo '<div class="success">';
                echo '<strong>✅ Admin accounts found! (' . count($admins) . ' total)</strong>';
                echo '</div>';
                
                echo '<div class="admin-list">';
                echo '<h2>Admin/SuperAdmin Users:</h2>';
                echo '<table>';
                echo '<tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Verified</th><th>Approved</th></tr>';
                
                foreach ($admins as $admin) {
                    echo '<tr>';
                    echo '<td>' . $admin['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($admin['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($admin['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($admin['phone'] ?? '-') . '</td>';
                    echo '<td><strong>' . ucfirst($admin['role_name']) . '</strong></td>';
                    echo '<td>' . ($admin['email_verified'] ? '✅ Yes' : '❌ No') . '</td>';
                    echo '<td>' . ucfirst($admin['approval_status'] ?? 'pending') . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                echo '</div>';
                
                echo '<div style="background: #e3f2fd; padding: 15px; border-radius: 6px; border-left: 4px solid #2196f3; margin: 20px 0;">';
                echo '<strong>Next Step:</strong> Use one of these accounts to login at <strong>staff_login.php</strong>';
                echo '</div>';
                
            } else {
                echo '<div class="warning">';
                echo '<strong>⚠️ No admin accounts found!</strong>';
                echo '</div>';
                
                echo '<p>Creating a default admin account for you...</p>';
                
                // Create default admin account
                try {
                    // Get or create admin role
                    $admin_role = $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$admin_role) {
                        $pdo->exec("INSERT INTO roles (name) VALUES ('admin')");
                        $admin_role_id = $pdo->lastInsertId();
                    } else {
                        $admin_role_id = $admin_role['id'];
                    }
                    
                    // Create admin user
                    $hashed_password = password_hash('password', PASSWORD_BCRYPT);
                    $insert_admin = $pdo->prepare("
                        INSERT INTO users (full_name, email, phone, password, role_id, email_verified, approval_status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, 1, 'approved', NOW(), NOW())
                    ");
                    
                    $insert_admin->execute([
                        'Admin User',
                        'admin@example.com',
                        '1234567890',
                        $hashed_password,
                        $admin_role_id
                    ]);
                    
                    echo '<div class="success">';
                    echo '<strong>✅ Admin account created successfully!</strong><br>';
                    echo '<strong>Email:</strong> admin@example.com<br>';
                    echo '<strong>Password:</strong> password<br>';
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="warning">';
                    echo '<strong>❌ Error creating admin account:</strong> ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="warning">';
            echo '<strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <button class="button" onclick="window.location.href='staff_login.php'">Go to Staff Login</button>
        <button class="button" onclick="window.location.href='COMPLETE_SETUP_GUIDE.php'" style="background: #666;">Back to Setup Guide</button>
    </div>
</body>
</html>
