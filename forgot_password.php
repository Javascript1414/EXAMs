<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp_helper.php';
require_once __DIR__ . '/includes/email_helper.php';
require_once __DIR__ . '/includes/sms_helper.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = sanitizeInput($_POST['email_or_phone'] ?? '');
    
    if (empty($email_or_phone)) {
        $errors[] = "Please enter your email address or phone number.";
    } else {
        // Check if input is email or phone
        $is_email = filter_var($email_or_phone, FILTER_VALIDATE_EMAIL);
        
        // Find user
        $userStmt = $pdo->prepare("
            SELECT id, full_name, email, phone 
            FROM users 
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");
        
        $userStmt->execute([$email_or_phone, $email_or_phone]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            $errors[] = "No account found with this email or phone number.";
        } else {
            // Invalidate any previous OTPs
            invalidateOTPs($pdo, $user['id'], 'password_reset');
            
            // Generate new OTP
            $otp_code = createOTP($pdo, $user['id'], 'password_reset', 'both', 10);
            
            if (!$otp_code) {
                $errors[] = "Error generating OTP. Please try again.";
            } else {
                // Send OTP via email
                $email_sent = sendOTPEmail($user['email'], $otp_code, $user['full_name']);
                
                // Send OTP via SMS (if enabled)
                $sms_sent = SMS_ENABLED ? sendOTPSMS($user['phone'], $otp_code) : false;
                
                if ($email_sent || $sms_sent) {
                    $_SESSION['temp_user_id'] = $user['id'];
                    $_SESSION['forgot_password_step'] = 'otp_verification';
                    
                    $_SESSION['success_message'] = "OTP sent to your email and phone. Please check and verify.";
                    redirect('/verify_otp.php?purpose=password_reset&user_id=' . $user['id']);
                } else {
                    $errors[] = "Error sending OTP. Please check your email configuration.";
                }
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
    <title>Forgot Password - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forgot_password.css?v=<?= filemtime(__DIR__ . '/assets/css/forgot_password.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Bubble Effects -->
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
    </div>

    <div class="forgot-container">
        <div class="forgot-header">
            <div class="forgot-header-icon">🔐</div>
            <h2>Forgot Password?</h2>
            <p>No worries, we'll help you reset it</p>
        </div>
        
        <div class="forgot-body">
            <a href="login.php" class="back-link">← Back to Login</a>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" class="me-2" style="width: 18px; display: inline;"></i>
                <?= implode('<br>', $errors) ?>
            </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i data-lucide="info" style="width: 16px; display: inline; margin-right: 8px;"></i>
                Enter your registered email address or phone number. We'll send you an OTP to verify your identity.
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email_or_phone">📧 Email Address or 📱 Phone Number *</label>
                    <input 
                        type="text" 
                        id="email_or_phone" 
                        name="email_or_phone" 
                        class="form-control" 
                        placeholder="example@email.com or 9876543210" 
                        required 
                        autofocus
                    >
                    <small class="text-muted">Use the email or phone number associated with your account</small>
                </div>
                
                <button type="submit" class="btn btn-reset">
                    <i data-lucide="send" style="width: 18px; display: inline; margin-right: 8px;"></i>
                    Send Recovery OTP
                </button>
            </form>
            
            <div class="login-link">
                <p style="margin-top: 30px; color: #666; font-size: 13px;">
                    Remembered your password? 
                    <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Random Gradient Colors Array
        const gradients = [
            'linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
            'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'linear-gradient(135deg, #ff9466 0%, #ff6b6b 100%)',
            'linear-gradient(135deg, #4158d0 0%, #c850c0 100%)',
            'linear-gradient(135deg, #0093e9 0%, #80d0c7 100%)',
            'linear-gradient(135deg, #fccb90 0%, #d57eeb 100%)',
            'linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%)',
            'linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
            'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
            'linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%)',
            'linear-gradient(135deg, #2e2e78 0%, #662d8c 100%)',
            'linear-gradient(135deg, #1fa2ff 0%, #12d8fa 100%)',
            'linear-gradient(135deg, #a370f0 0%, #6b24ea 100%)',
            'linear-gradient(135deg, #f43b47 0%, #453a94 100%)',
            'linear-gradient(135deg, #eb3b5a 0%, #fc5c65 100%)',
            'linear-gradient(135deg, #6bcf7f 0%, #4d96ff 100%)',
            'linear-gradient(135deg, #fa8231 0%, #f79f1f 100%)',
            'linear-gradient(135deg, #ee5a6f 0%, #f368e0 100%)',
            'linear-gradient(135deg, #485563 0%, #29323c 100%)',
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f5af19 0%, #f12711 100%)',
            'linear-gradient(135deg, #56ab2f 0%, #a8e063 100%)',
            'linear-gradient(135deg, #ff6b9d 0%, #c44569 100%)'
        ];

        // Get Random Gradient
        function getRandomGradient() {
            return gradients[Math.floor(Math.random() * gradients.length)];
        }

        // Apply Random Gradient to Body
        function applyRandomGradient() {
            const body = document.body;
            if (body) {
                body.style.background = getRandomGradient();
                body.style.backgroundAttachment = 'fixed';
            }
        }

        // Apply gradient on page load
        window.addEventListener('load', applyRandomGradient);
        
        // Apply gradient immediately if DOM is already ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyRandomGradient);
        } else {
            applyRandomGradient();
        }

        // Optional: Change color on button click
        const formElement = document.querySelector('form');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                // Allow form to submit normally
                // Just add animation class if needed
                const container = document.querySelector('.forgot-container');
                if (container) {
                    container.style.animation = 'none';
                    setTimeout(() => {
                        container.style.animation = 'slideUp 0.6s ease-out';
                    }, 10);
                }
            });
        }
    </script>
</body>
</html>
