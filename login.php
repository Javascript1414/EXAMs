<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectDashboard($_SESSION['role_name']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0056D2 0%, #00d2ff 100%);
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
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.2);
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            color: white;
        }
        .gateway-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s ease;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .gateway-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            color: white;
        }
    </style>
</head>
<body>
    <div class="glass-card">
        <div class="mb-4">
            <i data-lucide="graduation-cap" style="width: 64px; height: 64px; color: #fff;"></i>
        </div>
        <h2 class="fw-bold mb-2"><?= APP_NAME ?></h2>
        <p class="mb-5 opacity-75">Please select your login portal to continue</p>

        <a href="student_login.php" class="gateway-btn">
            <i data-lucide="user" class="me-3"></i> Student Portal
        </a>
        
        <a href="staff_login.php" class="gateway-btn">
            <i data-lucide="shield" class="me-3"></i> Staff & Admin Portal
        </a>

        <div class="mt-4 pt-3 border-top border-light border-opacity-25">
            <p class="small opacity-75 mb-0">Don't have an account? <a href="register.php" class="text-white fw-bold">Register here</a></p>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>