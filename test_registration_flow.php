<?php
/**
 * Complete Registration Debug Test
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/otp_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

echo "<h2>🔍 Registration Debug Test</h2>";
echo "<hr>";

// Step 1: Check all helper functions
echo "<h3>1️⃣ Testing Functions:</h3>";

// Test generateOTP
try {
    $test_otp = generateOTP();
    echo "<p style='color: green;'>✅ generateOTP(): $test_otp</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ generateOTP() Error: " . $e->getMessage() . "</p>";
}

// Test createOTP
try {
    $test_user_id = 1; // assuming user 1 exists
    $created_otp = createOTP($pdo, $test_user_id, 'email_verification', 'both', 10);
    
    if ($created_otp) {
        echo "<p style='color: green;'>✅ createOTP(): $created_otp</p>";
        
        // Verify it's in database
        $check = $pdo->prepare("SELECT * FROM otp_verifications WHERE otp_code = ? ORDER BY id DESC LIMIT 1");
        $check->execute([$created_otp]);
        if ($check->rowCount() > 0) {
            $record = $check->fetch();
            echo "<p style='color: green;'>✅ OTP found in database:</p>";
            echo "<ul>";
            echo "<li>ID: " . $record['id'] . "</li>";
            echo "<li>Code: " . $record['otp_code'] . "</li>";
            echo "<li>Purpose: " . $record['purpose'] . "</li>";
            echo "<li>Channel: " . $record['channel'] . "</li>";
            echo "<li>Expires: " . $record['expires_at'] . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ OTP NOT in database!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ createOTP() returned false</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ createOTP() Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 2: Test Email Sending
echo "<h3>2️⃣ Testing Email Sending:</h3>";

try {
    $test_email = 'test_' . time() . '@test.com';
    $result = sendOTPEmail($test_email, '123456', 'Test User');
    
    if ($result) {
        echo "<p style='color: green;'>✅ sendOTPEmail() returned TRUE</p>";
        echo "<p style='color: orange;'>⚠️ Check actual inbox to confirm delivery</p>";
    } else {
        echo "<p style='color: red;'>❌ sendOTPEmail() returned FALSE</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ sendOTPEmail() Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 3: Simulate Full Registration
echo "<h3>3️⃣ Simulating Full Registration:</h3>";

try {
    $test_email = 'student_' . time() . '@test.com';
    $test_phone = '99' . rand(1000000000, 9999999999);
    
    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $check->execute([$test_email, $test_phone]);
    
    if ($check->rowCount() === 0) {
        // Get student role
        $role_stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
        $role_stmt->execute();
        $role = $role_stmt->fetch();
        
        if ($role) {
            echo "<p>Creating test user...</p>";
            
            // Create user
            $insert = $pdo->prepare("
                INSERT INTO users (role_id, full_name, email, phone, password, trade_id, status, email_verified) 
                VALUES (?, ?, ?, ?, ?, ?, 'inactive', FALSE)
            ");
            
            $insert->execute([
                $role['id'],
                'Test Student',
                $test_email,
                $test_phone,
                password_hash('Test@123456', PASSWORD_DEFAULT),
                1
            ]);
            
            $user_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ User created: ID $user_id</p>";
            
            // Generate OTP
            echo "<p>Generating OTP...</p>";
            $otp = createOTP($pdo, $user_id, 'email_verification', 'both', 10);
            
            if ($otp) {
                echo "<p style='color: green;'>✅ OTP Generated: $otp</p>";
                
                // Send email
                echo "<p>Sending email...</p>";
                $email_result = sendOTPEmail($test_email, $otp, 'Test Student');
                
                if ($email_result) {
                    echo "<p style='color: green;'>✅ Email sent successfully!</p>";
                    echo "<p style='color: blue;'><strong>Registration Test Successful!</strong></p>";
                    echo "<p>Email: $test_email</p>";
                    echo "<p>OTP: $otp</p>";
                    echo "<p>Check inbox for verification email</p>";
                } else {
                    echo "<p style='color: red;'>❌ Email sending failed</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ OTP generation failed</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Student role not found</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Email/Phone already exists</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2 style='color: blue;'>✅ Debug Complete</h2>";
?>
