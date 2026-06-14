<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectDashboard($_SESSION['role_name']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token. Please try again.";
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error_message'] = "Please enter both email and password.";
        } else {
            $stmt = $pdo->prepare("
                SELECT u.id, u.password, u.full_name, u.status, r.name AS role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role_name'] === 'student') {
                    $_SESSION['error_message'] = "This portal is for Staff. Please use the Student Login.";
                } elseif ($user['status'] !== 'active') {
                    $_SESSION['error_message'] = "Your account is " . htmlspecialchars($user['status']) . ". Please contact support.";
                } else {
                    $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id");
                    $updateStmt->execute(['id' => $user['id']]);
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['full_name']  = $user['full_name'];
                    $_SESSION['role_name']  = $user['role_name'];
                    $_SESSION['role']       = $user['role_name']; // Compatibility fallback
                    
                    redirectDashboard($user['role_name']);
                }
            } else {
                $_SESSION['error_message'] = "Invalid credentials or unauthorized access.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #00d2ff 100%); /* Slightly darker gradient for staff */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            color: white;
        }
        .glass-input {
            background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: white;
        }
        .glass-input::placeholder { color: rgba(255, 255, 255, 0.6); }
        .glass-input:focus { background: rgba(255, 255, 255, 0.2); border-color: rgba(255, 255, 255, 0.5); color: white; box-shadow: none; }
        .glass-btn {
            background: #fff; color: #1e293b; font-weight: 600; border: none; padding: 12px; border-radius: 8px; transition: all 0.3s;
        }
        .glass-btn:hover { background: #f8f9fa; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); color: #1e293b; }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="text-center mb-4">
            <i data-lucide="shield" style="width: 48px; height: 48px; color: #fff;"></i>
            <h3 class="fw-bold mt-2">Staff & Admin</h3>
            <p class="opacity-75 small">Secure administration portal</p>
        </div>
        
        <?php displayFlashMessages(); ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="mb-3"><label class="form-label small fw-bold">Email Address</label><input type="email" name="email" class="form-control glass-input" placeholder="admin@example.com" required></div>
            <div class="mb-4"><label class="form-label small fw-bold">Password</label><input type="password" name="password" class="form-control glass-input" placeholder="••••••••" required></div>
            <button type="submit" class="w-100 glass-btn">Log In Securely</button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="login.php" class="text-white text-decoration-none small opacity-75 border-bottom border-light pb-1"><i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Back to Options</a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>