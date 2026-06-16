<?php
/**
 * OTP Helper Functions
 * Handles OTP generation, validation, and management
 */

/**
 * Generate a random 6-digit OTP
 */
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Create OTP record in database
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $purpose Purpose of OTP (email_verification, phone_verification, password_reset)
 * @param string $channel Channel (email, sms, both)
 * @param int $expiry_minutes Minutes until OTP expires
 * @return string Generated OTP code
 */
function createOTP($pdo, $user_id, $purpose = 'email_verification', $channel = 'both', $expiry_minutes = 10) {
    try {
        $otp_code = generateOTP();
        
        // Use database time for consistency with MySQL's NOW()
        $stmt = $pdo->prepare("
            INSERT INTO otp_verifications (user_id, otp_code, purpose, channel, expires_at, is_used, created_at) 
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?, NOW())
        ");
        
        $result = $stmt->execute([$user_id, $otp_code, $purpose, $channel, $expiry_minutes, 0]);
        
        if ($result) {
            return $otp_code;
        } else {
            // Log error details
            $errorInfo = $stmt->errorInfo();
            error_log("OTP Creation SQL Error: " . json_encode($errorInfo));
            return false;
        }
    } catch (PDOException $e) {
        error_log("OTP Creation Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify OTP code
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $otp_code OTP code entered by user
 * @param string $purpose Purpose of OTP
 * @return array Status array with success flag and message
 */
function verifyOTP($pdo, $user_id, $otp_code, $purpose = 'email_verification') {
    try {
        // Check if OTP exists, matches, not expired, and not already used
        // Use database time comparison for timezone consistency
        $stmt = $pdo->prepare("
            SELECT id, otp_code, expires_at, is_used 
            FROM otp_verifications 
            WHERE user_id = ? 
            AND otp_code = ? 
            AND purpose = ? 
            AND is_used = FALSE 
            AND expires_at > NOW()
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$user_id, $otp_code, $purpose]);
        $otp_record = $stmt->fetch();
        
        if (!$otp_record) {
            // Check if OTP exists but is expired
            $expiredStmt = $pdo->prepare("
                SELECT id FROM otp_verifications 
                WHERE user_id = ? 
                AND otp_code = ? 
                AND purpose = ? 
                AND expires_at <= NOW()
                LIMIT 1
            ");
            $expiredStmt->execute([$user_id, $otp_code, $purpose]);
            
            if ($expiredStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new one.'
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Invalid OTP code.'
            ];
        }
        
        // Mark OTP as used and verified
        $updateStmt = $pdo->prepare("
            UPDATE otp_verifications 
            SET is_used = TRUE, verified_at = NOW() 
            WHERE id = ?
        ");
        
        $updateStmt->execute([$otp_record['id']]);
        
        return [
            'success' => true,
            'message' => 'OTP verified successfully.'
        ];
        
    } catch (PDOException $e) {
        error_log("OTP Verification Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error verifying OTP. Please try again.'
        ];
    }
}

/**
 * Check OTP attempt limits and lockout
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $max_attempts Maximum attempts allowed
 * @return array Status with attempt info
 */
function checkOTPAttempts($pdo, $user_id, $max_attempts = 5) {
    try {
        $stmt = $pdo->prepare("SELECT otp_attempts, otp_locked_until FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Check if locked out using database time comparison
        if ($user['otp_locked_until']) {
            $lockCheckStmt = $pdo->prepare("SELECT IF(NOW() < ?, 1, 0) as is_locked");
            $lockCheckStmt->execute([$user['otp_locked_until']]);
            $lockCheck = $lockCheckStmt->fetch();
            
            if ($lockCheck['is_locked']) {
                // Calculate remaining time
                $remainingStmt = $pdo->prepare("SELECT CEIL(TIMESTAMPDIFF(MINUTE, NOW(), ?)) as remaining_minutes");
                $remainingStmt->execute([$user['otp_locked_until']]);
                $remainingResult = $remainingStmt->fetch();
                $remaining = max(1, $remainingResult['remaining_minutes']);
                
                return [
                    'success' => false,
                    'locked' => true,
                    'message' => "Too many attempts. Try again in {$remaining} minutes."
                ];
            }
        }
        
        // Check if exceeded max attempts
        if ($user['otp_attempts'] >= $max_attempts) {
            // Set lockout time using database time
            $updateStmt = $pdo->prepare("UPDATE users SET otp_locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE id = ?");
            $updateStmt->execute([$user_id]);
            
            return [
                'success' => false,
                'locked' => true,
                'message' => 'Too many failed attempts. Account locked for 15 minutes.'
            ];
        }
        
        return [
            'success' => true,
            'attempts_remaining' => $max_attempts - $user['otp_attempts']
        ];
        
    } catch (PDOException $e) {
        error_log("OTP Attempts Check Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error checking attempts'];
    }
}

/**
 * Increment OTP attempt counter
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 */
function incrementOTPAttempts($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("OTP Attempts Increment Error: " . $e->getMessage());
    }
}

/**
 * Reset OTP attempt counter
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 */
function resetOTPAttempts($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET otp_attempts = 0, otp_locked_until = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("OTP Attempts Reset Error: " . $e->getMessage());
    }
}

/**
 * Get pending OTP for user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $purpose Purpose of OTP
 * @return array|false OTP record or false
 */
function getPendingOTP($pdo, $user_id, $purpose = 'email_verification') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM otp_verifications 
            WHERE user_id = ? 
            AND purpose = ? 
            AND is_used = FALSE 
            AND expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        
        $stmt->execute([$user_id, $purpose]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Pending OTP Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Invalidate all pending OTPs for user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $purpose Purpose of OTP (optional)
 */
function invalidateOTPs($pdo, $user_id, $purpose = null) {
    try {
        if ($purpose) {
            $stmt = $pdo->prepare("UPDATE otp_verifications SET is_used = TRUE WHERE user_id = ? AND purpose = ? AND is_used = FALSE");
            $stmt->execute([$user_id, $purpose]);
        } else {
            $stmt = $pdo->prepare("UPDATE otp_verifications SET is_used = TRUE WHERE user_id = ? AND is_used = FALSE");
            $stmt->execute([$user_id]);
        }
    } catch (PDOException $e) {
        error_log("Invalidate OTPs Error: " . $e->getMessage());
    }
}
?>
