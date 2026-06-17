<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp_helper.php';
require_once __DIR__ . '/includes/email_helper.php';
require_once __DIR__ . '/includes/sms_helper.php';
require_once __DIR__ . '/vendor/autoload.php';  // PHPMailer

// CAPTCHA Generator Function
function generateCaptcha() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $captcha = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

// Initialize CAPTCHA in session
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateCaptcha();
}

// Handle CAPTCHA refresh via AJAX
if (isset($_GET['refresh_captcha']) && $_GET['refresh_captcha'] === '1') {
    $_SESSION['captcha'] = generateCaptcha();
    header('Content-Type: application/json');
    echo json_encode(['captcha' => $_SESSION['captcha']]);
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirectDashboard($_SESSION['role_name']);
}

// Auto-cleanup unverified users older than 24 hours
try {
    $pdo->exec("DELETE FROM users WHERE status = 'inactive' AND email_verified = FALSE AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
} catch (Exception $e) {
    // Silently fail cleanup - don't disrupt registration
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } elseif (strtoupper($_POST['captcha'] ?? '') !== $_SESSION['captcha']) {
        $errors[] = "Invalid CAPTCHA. Please try again.";
        $_SESSION['captcha'] = generateCaptcha();
    } else {
        // Required
        $full_name  = sanitizeInput($_POST['full_name'] ?? '');
        $email      = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone      = sanitizeInput($_POST['phone'] ?? '');
        $trade_id   = (int)($_POST['trade_id'] ?? 0);
        $password   = $_POST['password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        // Optional
        $gender         = sanitizeInput($_POST['gender'] ?? '');
        $date_of_birth  = sanitizeInput($_POST['date_of_birth'] ?? '');
        $address        = sanitizeInput($_POST['address'] ?? '');
        $batch          = sanitizeInput($_POST['batch'] ?? '');
        $institute_name = sanitizeInput($_POST['institute_name'] ?? '');
        $enrollment_no  = sanitizeInput($_POST['enrollment_no'] ?? '');

        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($trade_id)) {
            $errors[] = "All required fields must be filled out.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_pw) {
            $errors[] = "Passwords do not match.";
        } else {
            // Check if email or phone already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR phone = :phone");
            $stmt->execute(['email' => $email, 'phone' => $phone]);
            if ($stmt->fetch()) {
                $errors[] = "Email or Phone is already registered.";
            } else {
                // Get Student Role ID
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
                $roleStmt->execute();
                $role = $roleStmt->fetch();
                
                if (!$role) {
                    $errors[] = "Critical Error: Student role not found in database.";
                } else {
                    try {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Store plain password temporarily in session for email (will be used in verify_otp.php)
                        $_SESSION['temp_registration_password'] = $password;
                        
                        // Create temporary pending user (not verified yet)
                        $insertStmt = $pdo->prepare("
                            INSERT INTO users 
                            (role_id, full_name, email, phone, password, trade_id, gender, date_of_birth, address, batch, institute_name, enrollment_no, email_verified, status, approval_status) 
                            VALUES 
                            (:role_id, :full_name, :email, :phone, :password, :trade_id, :gender, :date_of_birth, :address, :batch, :institute_name, :enrollment_no, FALSE, 'inactive', 'pending')
                        ");
                        
                        $insertSuccess = $insertStmt->execute([
                            'role_id'        => $role['id'],
                            'full_name'      => $full_name,
                            'email'          => $email,
                            'phone'          => $phone,
                            'password'       => $hashed_password,
                            'trade_id'       => $trade_id,
                            'gender'         => $gender ?: null,
                            'date_of_birth'  => $date_of_birth ?: null,
                            'address'        => $address ?: null,
                            'batch'          => $batch ?: null,
                            'institute_name' => $institute_name ?: null,
                            'enrollment_no'  => $enrollment_no ?: null
                        ]);
                        
                        if ($insertSuccess) {
                            // Get newly created user ID
                            $user_id = $pdo->lastInsertId();
                            
                            // Generate OTP
                            $otp_code = createOTP($pdo, $user_id, 'email_verification', 'both', 10);
                            
                            if ($otp_code) {
                                // Send OTP via PHPMailer (Gmail SMTP)
                                try {
                                    require_once __DIR__ . '/includes/phpmailer_config.php';
                                    
                                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                                    $mail->isSMTP();
                                    $mail->Host = MAIL_HOST;
                                    $mail->SMTPAuth = true;
                                    $mail->Username = MAIL_USERNAME;
                                    $mail->Password = MAIL_PASSWORD;
                                    $mail->SMTPSecure = MAIL_ENCRYPTION;
                                    $mail->Port = MAIL_PORT;
                                    $mail->SMTPDebug = 0;
                                    
                                    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                                    $mail->addAddress($email, $full_name);
                                    $mail->isHTML(true);
                                    $mail->Subject = 'Your OTP Verification Code - ' . APP_NAME;
                                    
                                    $mail->Body = "
                                    <!DOCTYPE html>
                                    <html>
                                    <head>
                                        <style>
                                            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                                            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                                            .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                                            .otp-box { background: #f0f4ff; border: 2px solid #667eea; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                                            .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 4px; color: #667eea; font-family: 'Courier New', monospace; }
                                            .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; color: #856404; font-size: 13px; }
                                        </style>
                                    </head>
                                    <body>
                                        <div class='container'>
                                            <div class='header'>
                                                <h1>" . APP_NAME . "</h1>
                                                <p>Email Verification</p>
                                            </div>
                                            <div class='content'>
                                                <p>Hello <strong>$full_name</strong>,</p>
                                                <p>You have registered with " . APP_NAME . ". Please verify your email using the OTP code below:</p>
                                                <div class='otp-box'>
                                                    <div class='otp-code'>$otp_code</div>
                                                    <div style='color: #666; font-size: 14px; margin-top: 10px;'>Valid for 10 minutes</div>
                                                </div>
                                                <p>Enter this code on the verification page to complete your registration.</p>
                                                <div class='warning'>
                                                    <strong>⚠️ Security Notice:</strong><br>
                                                    • Never share this OTP with anyone<br>
                                                    • " . APP_NAME . " will never ask for your OTP via email<br>
                                                    • If you didn't register, please ignore this email
                                                </div>
                                                <p style='text-align: center; margin-top: 30px; color: #999; font-size: 12px;'>
                                                    " . APP_NAME . " | Secure Authentication System<br>
                                                    This is an automated email. Please do not reply.
                                                </p>
                                            </div>
                                        </div>
                                    </body>
                                    </html>
                                    ";
                                    
                                    $email_sent = $mail->send();
                                    
                                } catch (Exception $e) {
                                    // Fallback to mail() function if PHPMailer fails
                                    $email_sent = sendOTPEmail($email, $otp_code, $full_name);
                                }
                                
                                // Send OTP via SMS (if enabled)
                                $sms_sent = SMS_ENABLED ? sendOTPSMS($phone, $otp_code) : false;
                                
                                if ($email_sent || SMS_ENABLED) {
                                    // Store user data in session for next step
                                    $_SESSION['temp_user_id'] = $user_id;
                                    $_SESSION['temp_user_email'] = $email;
                                    $_SESSION['temp_user_phone'] = $phone;
                                    $_SESSION['registration_step'] = 'otp_verification';
                                    
                                    $_SESSION['success_message'] = "Registration successful! OTP sent to your email and phone. Please verify to complete registration.";
                                    redirect('/verify_otp.php?purpose=email_verification&user_id=' . $user_id);
                                } else {
                                    $errors[] = "Error sending OTP. Please try again.";
                                    // Delete the user record if OTP sending failed
                                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                                }
                            } else {
                                $errors[] = "Error generating OTP. Please try again.";
                                // Delete the user record if OTP generation failed
                                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                            }
                        } else {
                            $errors[] = "Registration failed. Please try again.";
                        }
                    } catch (PDOException $e) {
                        error_log("Registration Error: " . $e->getMessage());
                        $errors[] = "Database error. Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch trades for the dropdown
$tradesStmt = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name ASC");
$trades = $tradesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register_new.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
 <!-- <body style="font-family: Arial, sans-serif; background-color: #F3F4F6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px;">  -->
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
        <div class="main-container">
            <div class="left-panel">
                <div class="left-content">
                    <div class="left-logo">📚 EDUCARE</div>
                    <h1>Learn.<br>Grow.<br>Succeed.</h1>
                    <p class="left-tagline">Join thousands of students who start their journey towards a brighter future</p>
                    
                    <div class="why-join">
                        <div class="why-join-title">Why Join Us?</div>
                        <div class="why-join-items">
                            <div class="why-join-item">
                                <div class="why-join-icon">🎓</div>
                                <div class="why-join-text">Quality Education</div>
                            </div>
                            <div class="why-join-item">
                                <div class="why-join-icon">👨‍🏫</div>
                                <div class="why-join-text">Expert Mentors</div>
                            </div>
                            <div class="why-join-item">
                                <div class="why-join-icon">📜</div>
                                <div class="why-join-text">Certification</div>
                            </div>
                            <div class="why-join-item">
                                <div class="why-join-icon">💼</div>
                                <div class="why-join-text">Placement Support</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="left-stats">
                    <div class="stat-box">
                        <div class="stat-number">10k+</div>
                        <div class="stat-label">Users</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Days</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Instructors</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Courses</div>
                    </div>
                </div>
            </div>
            <div class="registration-container">
                <div class="decoration deco-laptop">💻</div>
                <div class="decoration deco-book">📚</div>
                <div class="decoration deco-plant">🪴</div>
                <div class="registration-form-wrapper">
                    <div class="form-header">
                        <div class="form-header-icon">👤</div>
                        <div>
                            <h2 class="registration-title">Student Registration</h2>
                        </div>
                    </div>
                    <p class="registration-subtitle">Create your account and start your learning journey</p>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?= implode('<br>', $errors) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="section-label">
                <div class="section-label-icon">📋</div>
                <span>Required Information</span>
            </div>
            <div class="form-group"><label><span class="form-field-icon">👤</span>Full Name *</label><input type="text" name="full_name" placeholder="Enter your full name" required></div>
            
            <div class="grid-2">
                <div class="form-group"><label><span class="form-field-icon">✉️</span>Email Address *</label><input type="email" name="email" placeholder="Enter your email address" required></div>
                <div class="form-group"><label><span class="form-field-icon">📱</span>Phone Number *</label><input type="text" name="phone" placeholder="Enter your phone number" required></div>
            </div>

            <div class="form-group">
                <label><span class="form-field-icon">📚</span>Trade / Course *</label>
                <select name="trade_id" required>
                    <option value="">-- Select a Trade --</option>
                    <?php foreach ($trades as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid-2">
                <div class="form-group"><label><span class="form-field-icon">🔐</span>Password *</label><input type="password" name="password" placeholder="Enter your password" required></div>
                <div class="form-group"><label><span class="form-field-icon">🔐</span>Confirm Password *</label><input type="password" name="confirm_password" placeholder="Confirm password" required></div>
            </div>

            <div class="section-label">
                <div class="section-label-icon">ℹ️</div>
                <span>Optional Information</span>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label><span class="form-field-icon">⚧️</span>Gender</label>
                    <select name="gender">
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="form-group"><label><span class="form-field-icon">📅</span>Date of Birth</label><input type="date" name="date_of_birth"></div>
            </div>

            <div class="form-group"><label><span class="form-field-icon">📍</span>Address</label><textarea name="address" rows="2" placeholder="Enter your complete address"></textarea></div>

            <div class="grid-2">
                <div class="form-group"><label><span class="form-field-icon">📌</span>Batch</label><input type="text" name="batch" placeholder="e.g., 2024"></div>
                <div class="form-group"><label><span class="form-field-icon">🏫</span>Institute Name</label><input type="text" name="institute_name" placeholder="Enter institute name"></div>
            </div>
            
            <div class="form-group"><label><span class="form-field-icon">🎓</span>Enrollment Number</label><input type="text" name="enrollment_no" placeholder="Enter enrollment number"></div>

            <div class="section-label">
                <div class="section-label-icon">🔒</div>
                <span>Security Verification</span>
            </div>

            <div class="form-group">
                <label><span class="form-field-icon">🖼️</span>Enter CAPTCHA *</label>
                <div style="display: flex; gap: 8px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 12px 16px; border-radius: 8px; font-weight: bold; font-size: 20px; letter-spacing: 4px; color: white; text-align: center; font-family: monospace; user-select: none;" id="captchaDisplay"><?= htmlspecialchars($_SESSION['captcha']) ?></div>
                    </div>
                    <button type="button" class="btn" style="padding: 10px 14px; background: #7c3aed; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s ease;" id="captchaRefresh" title="Refresh CAPTCHA">🔄 Refresh</button>
                </div>
            </div>

            <div class="form-group"><label><span class="form-field-icon">✏️</span>Enter Text Above *</label><input type="text" name="captcha" placeholder="Enter the CAPTCHA text shown above" required style="font-family: monospace; font-size: 16px; letter-spacing: 2px;"></div>

            <button type="submit" class="btn-register"><span class="btn-icon">→</span> Register Now</button>
                    </form>
                    
                    <p class="login-link">Already have an account? <a href="login.php">Log In</a></p>
                </div>
            </div>
        </div>

        <script>
            const gradients = [
                'linear-gradient(135deg,#667eea 0%,#764ba2 50%,#f093fb 100%)',
                'linear-gradient(135deg,#f093fb 0%,#f5576c 100%)',
                'linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)',
                'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)',
                'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
                'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
                'linear-gradient(135deg,#a8edea 0%,#fed6e3 100%)',
                'linear-gradient(135deg,#ff9a56 0%,#ff6a88 100%)',
                'linear-gradient(135deg,#2e2e78 0%,#662d8c 50%,#c74b8c 100%)',
                'linear-gradient(135deg,#667eea 0%,#764ba2 100%)',
                'linear-gradient(135deg,#f083fb 0%,#fe8c00 100%)',
                'linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)',
                'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)',
                'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
                'linear-gradient(135deg,#30cfd0 0%,#330867 100%)',
                'linear-gradient(135deg,#667eea 0%,#764ba2 100%)',
                'linear-gradient(135deg,#00c6ff 0%,#0072ff 100%)',
                'linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)',
                'linear-gradient(135deg,#fa709a 0%,#fee140 100%)',
                'linear-gradient(135deg,#a18cd1 0%,#fbc2eb 100%)',
                'linear-gradient(135deg,#4facfe 0%,#8e44ad 100%)',
                'linear-gradient(135deg,#ff416c 0%,#ff4b2b 100%)',
            ];

            function getRandomGradient() {
                return gradients[Math.floor(Math.random() * gradients.length)];
            }

            function applyRandomGradient() {
                const leftPanel = document.querySelector('.left-panel');
                if (leftPanel) {
                    leftPanel.style.background = getRandomGradient();
                }
            }

            window.addEventListener('load', applyRandomGradient);
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', applyRandomGradient);
            } else {
                applyRandomGradient();
            }

            // CAPTCHA Refresh Functionality
            document.getElementById('captchaRefresh').addEventListener('click', function(e) {
                e.preventDefault();
                fetch('?refresh_captcha=1')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('captchaDisplay').textContent = data.captcha;
                        document.querySelector('input[name="captcha"]').value = '';
                        document.querySelector('input[name="captcha"]').focus();
                    })
                    .catch(error => console.error('Error refreshing CAPTCHA:', error));
            });
        </script>
    </body>
</html>