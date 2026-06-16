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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="CITS LMS - Smart Learning, Bright Future">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime(__DIR__ . '/assets/css/login.css') ?>">
</head>
<body>
    <!-- FLOATING BUBBLES -->
    <div class="bubbles-container">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card-wrapper">
        <div class="gateway-card">
            <div class="gateway-card-icon">🎓</div>
            
            <h2><?= APP_NAME ?></h2>
            <p>Smart Learning, Bright Future</p>
            <p style="font-size:14px; opacity:0.85;">Please select your login portal to continue</p>

            <div class="portal-buttons">
                <a href="student_login.php" class="gateway-btn">
                    <div class="gateway-btn-icon">👨‍🎓</div>
                    <div class="gateway-btn-text">
                        Student Portal
                        <div class="gateway-btn-subtext">Access your courses, assignments and learning resources</div>
                    </div>
                </a>
                
                <a href="staff_login.php" class="gateway-btn">
                    <div class="gateway-btn-icon">👨‍💼</div>
                    <div class="gateway-btn-text">
                        Staff & Admin Portal
                        <div class="gateway-btn-subtext">Manage courses, analytics and system settings</div>
                    </div>
                </a>
            </div>

            <div class="divider"></div>

            <p class="footer-text">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Random Gradient Colors with Matching Card Colors
        const colorSchemes = [
            {
                bg: 'linear-gradient(135deg,#667eea 0%,#764ba2 50%,#f093fb 100%)',
                card: 'rgba(102,126,234,0.2)',
                border: 'rgba(240,147,251,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#f093fb 0%,#f5576c 100%)',
                card: 'rgba(240,147,251,0.2)',
                border: 'rgba(245,87,108,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)',
                card: 'rgba(79,172,254,0.2)',
                border: 'rgba(0,242,254,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)',
                card: 'rgba(67,233,123,0.2)',
                border: 'rgba(56,249,215,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
                card: 'rgba(250,112,154,0.2)',
                border: 'rgba(254,225,64,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
                card: 'rgba(48,207,208,0.2)',
                border: 'rgba(51,8,103,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#a8edea 0%,#fed6e3 100%)',
                card: 'rgba(168,237,234,0.2)',
                border: 'rgba(254,214,227,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#ff9466 0%,#ff6b6b 100%)',
                card: 'rgba(255,148,102,0.2)',
                border: 'rgba(255,107,107,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#4158d0 0%,#c850c0 100%)',
                card: 'rgba(65,88,208,0.2)',
                border: 'rgba(200,80,192,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#0093e9 0%,#80d0c7 100%)',
                card: 'rgba(0,147,233,0.2)',
                border: 'rgba(128,208,199,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#fccb90 0%,#d57eeb 100%)',
                card: 'rgba(252,203,144,0.2)',
                border: 'rgba(213,126,235,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#ff6e7f 0%,#bfe9ff 100%)',
                card: 'rgba(255,110,127,0.2)',
                border: 'rgba(191,233,255,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#a1c4fd 0%,#c2e9fb 100%)',
                card: 'rgba(161,196,253,0.2)',
                border: 'rgba(194,233,251,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
                card: 'rgba(250,112,154,0.2)',
                border: 'rgba(254,225,64,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
                card: 'rgba(48,207,208,0.2)',
                border: 'rgba(51,8,103,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#a8edea 0%,#fed6e3 100%)',
                card: 'rgba(168,237,234,0.2)',
                border: 'rgba(254,214,227,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#ff9a56 0%,#ff6a88 100%)',
                card: 'rgba(255,154,86,0.2)',
                border: 'rgba(255,106,136,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#2e2e78 0%,#662d8c 100%)',
                card: 'rgba(46,46,120,0.2)',
                border: 'rgba(102,45,140,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#1fa2ff 0%,#12d8fa 100%)',
                card: 'rgba(31,162,255,0.2)',
                border: 'rgba(18,216,250,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#a370f0 0%,#6b24ea 100%)',
                card: 'rgba(163,112,240,0.2)',
                border: 'rgba(107,36,234,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#f43b47 0%,#453a94 100%)',
                card: 'rgba(244,59,71,0.2)',
                border: 'rgba(69,58,148,0.4)'
            },
            {
                bg: 'linear-gradient(135deg,#eb3b5a 0%,#fc5c65 100%)',
                card: 'rgba(235,59,90,0.2)',
                border: 'rgba(252,92,101,0.4)'
            }
        ];

        function getRandomColorScheme() {
            return colorSchemes[Math.floor(Math.random() * colorSchemes.length)];
        }

        function applyRandomGradient() {
            const scheme = getRandomColorScheme();
            document.body.style.background = scheme.bg;
            
            const card = document.querySelector('.gateway-card');
            if (card) {
                card.style.background = scheme.card;
                card.style.borderColor = scheme.border;
                card.style.boxShadow = `0 25px 60px rgba(0,0,0,0.25),
                                         0 0 40px ${scheme.border} inset,
                                         0 0 1px rgba(255,255,255,0.5) inset`;
            }
        }

        window.addEventListener('load', applyRandomGradient);
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyRandomGradient);
        } else {
            applyRandomGradient();
        }
    </script>
</body>
</html>