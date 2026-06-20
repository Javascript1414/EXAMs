<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    // If role_name is missing from session, try to get it from database
    if (empty($_SESSION['role_name'])) {
        $stmt = $pdo->prepare("SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $_SESSION['role_name'] = $user['name'] ?? 'student';
    }
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
                SELECT u.id, u.password, u.full_name, u.status, u.approval_status, r.name AS role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['role_name'] !== 'student') {
                    $_SESSION['error_message'] = "This portal is for students. Please use the Staff Login.";
                } elseif ($user['approval_status'] === 'pending') {
                    $_SESSION['error_message'] = "Your account is pending admin approval. Please wait for verification.";
                } elseif ($user['approval_status'] === 'rejected') {
                    $_SESSION['error_message'] = "Your account has been rejected. Please contact support.";
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
                    $_SESSION['trade_id']   = $user['trade_id']; // Set student's trade for practical exams
                    
                    redirectDashboard($user['role_name']);
                }
            } else {
                $_SESSION['error_message'] = "Invalid email or password.";
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
    <title>Student Login - <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/student_login.css?v=<?= filemtime(__DIR__ . '/assets/css/student_login.css') ?>">
</head>
<body>
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
    <div class="login-container">
        <!-- LEFT PANEL - DIVINE THEME -->
        <div class="divine-panel">
            <div class="deco-lotus deco-om">🕉️</div>
            <div class="deco-lotus deco-prayer">🙏</div>
            
            <div class="divine-content">
                <div class="divine-icon">📚</div>
                
                <div>
                    <h1>STUDENT<br>PORTAL</h1>
                </div>
                
                <div class="divine-tagline">
                    "Hare Krishna Hare Krishna Krishna Krishna Hare Hare Hare Rama Hare Rama Rama Rama Hare Hare"<br>
                    <span style="font-size:16px; opacity:0.9;">Education is the Best Wealth</span>
                </div>
                
                <div class="divine-features">
                    <div class="feature-item">
                        <div class="feature-item-icon">🎓</div>
                        Quality Education
                    </div>
                    <div class="feature-item">
                        <div class="feature-item-icon">👨‍🏫</div>
                        Expert Mentors
                    </div>
                    <div class="feature-item">
                        <div class="feature-item-icon">📜</div>
                        Certification
                    </div>
                    <div class="feature-item">
                        <div class="feature-item-icon">💼</div>
                        Placement Support
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL - LOGIN FORM -->
        <div class="form-panel">
            <div class="login-form-wrapper">
                <div class="form-header">
                    <div class="form-header-icon">🔐</div>
                    <h2>Welcome Back!</h2>
                    <p>Sign in to your account</p>
                </div>

                <?php if (!empty($_SESSION['error_message'])): ?>
                    <div class="alert-message alert-danger">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="alert-message alert-success">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                    <div class="form-group">
                        <label>✉️ Email Address</label>
                        <input type="email" name="email" placeholder="student@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>🔐 Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-login">Sign In</button>
                </form>

                <div class="login-links">
                    <a href="login.php">← Back to Options</a>
                    <a href="forgot_password.php">🔑 Forgot Password?</a>
                    <a href="register.php">Create Account</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Random Gradient Colors
        const gradients = [
            'linear-gradient(135deg,#667eea 0%,#764ba2 50%,#f093fb 100%)',
            'linear-gradient(135deg,#f093fb 0%,#f5576c 100%)',
            'linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)',
            'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)',
            'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
            'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
            'linear-gradient(135deg,#a8edea 0%,#fed6e3 100%)',
            'linear-gradient(135deg,#ff9466 0%,#ff6b6b 100%)',
            'linear-gradient(135deg,#4158d0 0%,#c850c0 100%)',
            'linear-gradient(135deg,#0093e9 0%,#80d0c7 100%)',
            'linear-gradient(135deg,#fccb90 0%,#d57eeb 100%)',
            'linear-gradient(135deg,#ff6e7f 0%,#bfe9ff 100%)',
            'linear-gradient(135deg,#a1c4fd 0%,#c2e9fb 100%)',
            'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
            'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
            'linear-gradient(135deg,#a8edea 0%,#fed6e3 100%)',
            'linear-gradient(135deg,#ff9a56 0%,#ff6a88 100%)',
            'linear-gradient(135deg,#2e2e78 0%,#662d8c 100%)',
            'linear-gradient(135deg,#1fa2ff 0%,#12d8fa 100%)',
            'linear-gradient(135deg,#a370f0 0%,#6b24ea 100%)',
            'linear-gradient(135deg,#f43b47 0%,#453a94 100%)',
            'linear-gradient(135deg,#eb3b5a 0%,#fc5c65 100%)'
        ];

        function getRandomGradient() {
            return gradients[Math.floor(Math.random() * gradients.length)];
        }

        function applyRandomGradient() {
            const divinePanel = document.querySelector('.divine-panel');
            if (divinePanel) {
                divinePanel.style.background = getRandomGradient();
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