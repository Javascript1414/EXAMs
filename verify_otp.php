<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp_helper.php';
require_once __DIR__ . '/includes/email_helper.php';
require_once __DIR__ . '/includes/notification_emails.php';

$errors = [];
$success_message = '';
$purpose = sanitizeInput($_GET['purpose'] ?? 'email_verification'); // email_verification or password_reset
$user_id = (int)($_GET['user_id'] ?? $_SESSION['temp_user_id'] ?? 0);

// Validate purpose and user_id
if (!in_array($purpose, ['email_verification', 'password_reset'])) {
    $purpose = 'email_verification';
}

if (!$user_id) {
    redirect('/register.php');
}

// Fetch user data
$userStmt = $pdo->prepare("SELECT id, full_name, email, phone FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

if (!$user) {
    $errors[] = "User not found.";
}

// Handle Resend OTP request
if (isset($_GET['resend']) && $_GET['resend'] == 1) {
    if ($user) {
        // Check rate limit - only allow resend if last OTP is older than 2 minutes
        $lastOTPStmt = $pdo->prepare("
            SELECT created_at FROM otp_verifications 
            WHERE user_id = ? AND purpose = ? 
            ORDER BY created_at DESC LIMIT 1
        ");
        $lastOTPStmt->execute([$user_id, $purpose]);
        $lastOTP = $lastOTPStmt->fetch();
        
        $can_resend = true;
        $wait_time = 0;
        
        if ($lastOTP) {
            $last_created = strtotime($lastOTP['created_at']);
            $now = time();
            $time_diff = $now - $last_created;
            $rate_limit = 120; // 2 minutes
            
            if ($time_diff < $rate_limit) {
                $can_resend = false;
                $wait_time = $rate_limit - $time_diff;
            }
        }
        
        if (!$can_resend) {
            $errors[] = "Please wait " . $wait_time . " seconds before resending OTP.";
        } else {
            // Delete old OTPs
            $deleteStmt = $pdo->prepare("DELETE FROM otp_verifications WHERE user_id = ? AND purpose = ?");
            $deleteStmt->execute([$user_id, $purpose]);
            
            // Create new OTP
            $new_otp = createOTP($pdo, $user_id, $purpose, 'both', 10);
            
            if ($new_otp) {
                // Send OTP via email
                $email_sent = sendOTPEmail($user['email'], $new_otp, $user['full_name']);
                
                if ($email_sent) {
                    $success_message = "✅ New OTP has been sent to your email! Check your inbox.";
                    error_log("OTP Resent to User ID: {$user_id}, Email: {$user['email']}");
                } else {
                    $errors[] = "OTP created but failed to send. Please try again.";
                    error_log("OTP Resend Email Failed for User ID: {$user_id}");
                }
            } else {
                $errors[] = "Failed to generate new OTP. Please try again.";
                error_log("OTP Generation Failed for User ID: {$user_id}");
            }
        }
    }
}

// Check for pending OTP
$pending_otp = getPendingOTP($pdo, $user_id, $purpose);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = sanitizeInput($_POST['otp_code'] ?? '');
    
    if (empty($entered_otp)) {
        $errors[] = "Please enter the OTP.";
    } else {
        // Check attempt limits
        $attempt_check = checkOTPAttempts($pdo, $user_id);
        if (!$attempt_check['success']) {
            $errors[] = $attempt_check['message'];
        } else {
            // Verify OTP
            $verify_result = verifyOTP($pdo, $user_id, $entered_otp, $purpose);
            
            if ($verify_result['success']) {
                resetOTPAttempts($pdo, $user_id);
                
                if ($purpose === 'email_verification') {
                    // Mark email as verified and activate account
                    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, status = 'active' WHERE id = ?");
                    $updateStmt->execute([$user_id]);
                    
                    // Send registration notification email with credentials
                    // Get the password from session (it was hashed before inserting to DB)
                    $password_display = $_SESSION['temp_registration_password'] ?? 'Your temporary password';
                    
                    // Send email
                    $email_sent = sendRegistrationNotificationEmail(
                        $user['email'],
                        $user['full_name'],
                        $user_id,
                        $password_display
                    );
                    
                    // Log for debugging
                    error_log('Registration Email Status: ' . ($email_sent ? 'SENT' : 'FAILED') . ' for User ID: ' . $user_id);
                    
                    $_SESSION['success_message'] = "Email verified successfully! You can now log in. Registration details have been sent to your email.";
                    redirect('/login.php');
                } else if ($purpose === 'password_reset') {
                    // Redirect to reset password page
                    $_SESSION['temp_reset_user_id'] = $user_id;
                    $_SESSION['temp_reset_token'] = bin2hex(random_bytes(16));
                    redirect('/reset_password.php?token=' . $_SESSION['temp_reset_token']);
                }
            } else {
                incrementOTPAttempts($pdo, $user_id);
                $errors[] = $verify_result['message'];
            }
        }
    }
}

// Prepare display variables
$maskedEmail = substr($user['email'] ?? '', 0, 3) . '****' . substr($user['email'] ?? '', -4);
$maskedPhone = substr($user['phone'] ?? '', 0, 2) . '****' . substr($user['phone'] ?? '', -3);
$purpose_label = ($purpose === 'password_reset') ? 'Password Reset' : 'Email Verification';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register_new.css?v=<?= filemtime(__DIR__ . '/assets/css/register_new.css') ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .otp-container { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 500px; width: 90%; }
        .otp-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px 20px; text-align: center; }
        .otp-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 10px; }
        .otp-header p { font-size: 14px; opacity: 0.9; }
        .otp-body { padding: 40px; }
        .otp-input-group { margin: 30px 0; }
        .otp-input-group label { font-weight: 600; color: #333; margin-bottom: 12px; display: block; }
        .otp-code-input { font-size: 24px; letter-spacing: 10px; font-weight: bold; font-family: 'Courier New', monospace; text-align: center; }
        .otp-info { background: #f0f4ff; border-left: 4px solid #667eea; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 13px; color: #555; }
        .otp-resend { text-align: center; margin-top: 20px; }
        .otp-resend a { color: #667eea; text-decoration: none; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .otp-resend a:hover { text-decoration: underline; }
        .countdown { color: #dc3545; font-weight: 600; }
        .btn-verify { width: 100%; padding: 12px; font-size: 16px; font-weight: 600; border-radius: 8px; margin-top: 20px; }
        .alert { border-radius: 8px; border: none; }
        .back-link { color: #667eea; text-decoration: none; font-size: 14px; margin-bottom: 20px; display: inline-block; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-header">
            <h2>Verify OTP</h2>
            <p><?= $purpose_label ?></p>
        </div>
        
        <div class="otp-body">
            <a href="javascript:history.back()" class="back-link">← Go Back</a>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" class="me-2" style="width: 18px; display: inline;"></i>
                <?= implode('<br>', $errors) ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i data-lucide="check-circle" class="me-2" style="width: 18px; display: inline;"></i>
                <?= $success_message ?>
            </div>
            <?php endif; ?>
            
            <div class="otp-info">
                <strong>OTP Sent To:</strong><br>
                📧 Email: <?= htmlspecialchars($maskedEmail) ?><br>
                📱 SMS: <?= htmlspecialchars($maskedPhone) ?><br>
                <small style="color: #999; margin-top: 5px; display: block;">Check both your email and SMS for the OTP code</small>
            </div>
            
            <form method="POST" action="">
                <div class="otp-input-group">
                    <label for="otp_code">Enter 6-Digit OTP *</label>
                    <input type="text" id="otp_code" name="otp_code" class="form-control otp-code-input" maxlength="6" placeholder="000000" required autofocus pattern="[0-9]{6}" inputmode="numeric">
                    <small class="text-muted" style="display: block; margin-top: 8px; text-align: center;">Valid for 10 minutes</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-verify">
                    <i data-lucide="check" style="width: 18px; display: inline; margin-right: 8px;"></i>
                    Verify OTP
                </button>
            </form>
            
            <div class="otp-resend">
                <p style="margin: 20px 0; color: #666; font-size: 13px;">
                    Didn't receive the OTP?
                </p>
                <a href="?purpose=<?= htmlspecialchars($purpose) ?>&user_id=<?= $user_id ?>&resend=1" id="resend-btn" onclick="return handleResendClick(event)">
                    <i data-lucide="refresh-cw" style="width: 14px; display: inline; margin-right: 5px;"></i>
                    <span id="resend-text">Resend OTP</span>
                    <span id="resend-timer" style="display: none;"> in <span id="timer-count">60</span>s</span>
                </a>
                <div id="resend-message" style="margin-top: 10px; font-size: 12px;"></div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto format OTP input to only allow numbers
        document.getElementById('otp_code').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Resend OTP timer and state management
        let resendDisabled = false;
        let resendCountdown = 0;
        
        function handleResendClick(e) {
            if (resendDisabled) {
                e.preventDefault();
                return false;
            }
            
            // Disable button for 60 seconds
            resendDisabled = true;
            resendCountdown = 60;
            const resendBtn = document.getElementById('resend-btn');
            const resendText = document.getElementById('resend-text');
            const resendTimer = document.getElementById('resend-timer');
            const timerCount = document.getElementById('timer-count');
            
            resendBtn.style.pointerEvents = 'none';
            resendBtn.style.opacity = '0.5';
            resendText.style.display = 'none';
            resendTimer.style.display = 'inline';
            
            // Update countdown
            const countdownInterval = setInterval(function() {
                resendCountdown--;
                timerCount.textContent = resendCountdown;
                
                if (resendCountdown <= 0) {
                    clearInterval(countdownInterval);
                    resendDisabled = false;
                    resendBtn.style.pointerEvents = 'auto';
                    resendBtn.style.opacity = '1';
                    resendText.style.display = 'inline';
                    resendTimer.style.display = 'none';
                }
            }, 1000);
            
            // Allow the link to proceed
            return true;
        }
        
        // OTP timer
        let timeLeft = 600; // 10 minutes
        function updateTimer() {
            if (timeLeft > 0) {
                timeLeft--;
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                // Update timer display if needed
            } else {
                document.querySelector('.countdown').textContent = 'OTP Expired';
            }
        }
        setInterval(updateTimer, 1000);
        
        lucide.createIcons();
    </script>
</body>
</html>
