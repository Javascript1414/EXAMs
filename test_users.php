<?php
// Check database users
require_once 'config.php';

try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=exams_lms', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get users
    $users = $pdo->query("SELECT id, username, email, role_id, created_at FROM users LIMIT 20")->fetchAll();
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Users - CITS LMS</title>
    <style>
        body { font-family: Arial; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 1000px; margin: 0 auto; background: #1e293b; padding: 2rem; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #334155; }
        th { background: #334155; font-weight: bold; }
        tr:hover { background: #0f172a; }
        .btn { padding: 0.5rem 1rem; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Test Users in Database</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['role_id'] . "</td>";
        echo "<td>" . substr($user['created_at'], 0, 10) . "</td>";
        echo "</tr>";
    }
    
    echo "        </tbody>
    </table>
    <p style='margin-top: 2rem;'>
        Try logging in with any of these usernames and password: <strong>password</strong>
    </p>
    <p>
        <a href='student_login.php' style='color: #6366f1;'>→ Go to Student Login</a>
    </p>
</div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
