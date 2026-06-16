<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$success_message = '';

// Check if user has verified OTP
if (!isset($_SESSION['temp_reset_user_id'])) {
    $_SESSION['error_message'] = "Please verify your OTP first.";
    redirect('/forgot_password.php');
}

$user_id = $_SESSION['temp_reset_user_id'];
$token = sanitizeInput($_GET['token'] ?? '');

// Verify token
if ($token !== ($_SESSION['temp_reset_token'] ?? '')) {
    $errors[] = "Invalid or expired token.";
}

// Fetch user data
$userStmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    redirect('/forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $errors[] = "Both password fields are required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        // Check password strength
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter and one number.";
        } else {
            try {
                // Hash and update password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET password = ?, password_last_changed = NOW() 
                    WHERE id = ?
                ");
                
                $updateStmt->execute([$hashed_password, $user_id]);
                
                // Clear temporary session data
                unset($_SESSION['temp_reset_user_id']);
                unset($_SESSION['temp_reset_token']);
                unset($_SESSION['forgot_password_step']);
                
                $_SESSION['success_message'] = "Password reset successfully! You can now log in with your new password.";
                redirect('/login.php');
                
            } catch (PDOException $e) {
                error_log("Password Reset Error: " . $e->getMessage());
                $errors[] = "Error resetting password. Please try again.";
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
    <title>Reset Password - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register_new.css?v=<?= filemtime(__DIR__ . '/assets/css/register_new.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .reset-container { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 500px; width: 90%; }
        .reset-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
        .reset-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 10px; }
        .reset-header p { font-size: 14px; opacity: 0.9; }
        .reset-body { padding: 40px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; color: #333; margin-bottom: 8px; display: block; }
        .form-group input { border-radius: 8px; border: 1px solid #ddd; padding: 12px; font-size: 14px; width: 100%; }
        .form-group input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .password-strength { margin-top: 8px; }
        .strength-bar { height: 4px; border-radius: 2px; background: #ddd; overflow: hidden; }
        .strength-fill { height: 100%; background: #dc3545; transition: all 0.3s ease; }
        .strength-text { font-size: 12px; margin-top: 4px; }
        .btn-reset { width: 100%; padding: 12px; font-size: 16px; font-weight: 600; border-radius: 8px; margin-top: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; }
        .btn-reset:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .requirements { background: #f8f9fa; border-radius: 8px; padding: 15px; margin: 20px 0; font-size: 13px; }
        .requirements h6 { font-weight: 600; color: #333; margin-bottom: 10px; }
        .req-item { color: #666; margin: 5px 0; display: flex; align-items: center; }
        .req-item.met { color: #28a745; }
        .req-item i { margin-right: 8px; }
        .alert { border-radius: 8px; border: none; }
        .back-link { color: #667eea; text-decoration: none; font-size: 14px; margin-bottom: 20px; display: inline-block; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h2>Create New Password</h2>
            <p>Set a strong password for your account</p>
        </div>
        
        <div class="reset-body">
            <a href="javascript:history.back()" class="back-link">← Go Back</a>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" class="me-2" style="width: 18px; display: inline;"></i>
                <?= implode('<br>', $errors) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter new password" 
                        required
                    >
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill" style="width: 0%;"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Confirm password" 
                        required
                    >
                </div>
                
                <div class="requirements">
                    <h6>Password Requirements:</h6>
                    <div class="req-item" id="req-length">
                        <i data-lucide="circle" style="width: 14px;"></i>
                        At least 8 characters
                    </div>
                    <div class="req-item" id="req-uppercase">
                        <i data-lucide="circle" style="width: 14px;"></i>
                        At least one uppercase letter (A-Z)
                    </div>
                    <div class="req-item" id="req-number">
                        <i data-lucide="circle" style="width: 14px;"></i>
                        At least one number (0-9)
                    </div>
                    <div class="req-item" id="req-match">
                        <i data-lucide="circle" style="width: 14px;"></i>
                        Passwords match
                    </div>
                </div>
                
                <button type="submit" class="btn btn-reset" id="submitBtn" disabled>
                    <i data-lucide="lock" style="width: 18px; display: inline; margin-right: 8px;"></i>
                    Reset Password
                </button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const submitBtn = document.getElementById('submitBtn');
        
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            number: document.getElementById('req-number'),
            match: document.getElementById('req-match')
        };
        
        function validatePassword() {
            const pwd = passwordInput.value;
            const confirm = confirmInput.value;
            
            const checks = {
                length: pwd.length >= 8,
                uppercase: /[A-Z]/.test(pwd),
                number: /[0-9]/.test(pwd),
                match: pwd && pwd === confirm
            };
            
            // Update requirement indicators
            Object.keys(checks).forEach(key => {
                if (checks[key]) {
                    requirements[key].classList.add('met');
                } else {
                    requirements[key].classList.remove('met');
                }
            });
            
            // Update strength bar
            let strength = 0;
            Object.values(checks).forEach(check => {
                if (check) strength += 25;
            });
            
            strengthFill.style.width = strength + '%';
            
            let color = '#dc3545';
            let text = 'Weak';
            
            if (strength >= 75) {
                color = '#28a745';
                text = 'Strong';
            } else if (strength >= 50) {
                color = '#ffc107';
                text = 'Fair';
            }
            
            strengthFill.style.background = color;
            strengthText.textContent = text;
            
            // Enable submit button if all requirements met
            const allMet = Object.values(checks).every(check => check);
            submitBtn.disabled = !allMet;
        }
        
        passwordInput.addEventListener('input', validatePassword);
        confirmInput.addEventListener('input', validatePassword);
        
        lucide.createIcons();
    </script>
</body>
</html>
