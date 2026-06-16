<?php
/**
 * SMS Helper Functions
 * Handles SMS sending via various gateways
 * Currently configured for MSG91 (placeholder - add your API key)
 */

// SMS Gateway Configuration
define('SMS_GATEWAY', 'msg91'); // Options: msg91, twilio, custom
define('SMS_API_KEY', 'YOUR_MSG91_API_KEY_HERE'); // Replace with actual API key
define('SMS_SENDER_ID', 'EDUCARE'); // Replace with your sender ID
define('SMS_ENABLED', false); // Set to true when API key is configured

/**
 * Send OTP via SMS (MSG91)
 * 
 * @param string $phone Phone number with country code (e.g., +919876543210)
 * @param string $otp_code OTP code to send
 * @return bool Success status
 */
function sendOTPSMS($phone, $otp_code) {
    if (!SMS_ENABLED) {
        error_log("SMS is not enabled. Configure SMS_API_KEY in sms_helper.php");
        return false;
    }
    
    if (SMS_GATEWAY === 'msg91') {
        return sendSMSMsg91($phone, $otp_code);
    } elseif (SMS_GATEWAY === 'twilio') {
        return sendSMSTwilio($phone, $otp_code);
    } else {
        return false;
    }
}

/**
 * Send SMS via MSG91 Gateway
 * 
 * @param string $phone Phone number
 * @param string $otp_code OTP code
 * @return bool Success status
 */
function sendSMSMsg91($phone, $otp_code) {
    try {
        // Format phone number - ensure it has country code
        $phone = formatPhoneNumber($phone);
        
        $message = "Your " . APP_NAME . " OTP is: {$otp_code}. Valid for 10 minutes. Do not share this with anyone.";
        
        // MSG91 API endpoint
        $url = "https://api.msg91.com/apiv2/route";
        
        $postData = [
            'mobiles' => $phone,
            'message' => $message,
            'route' => '4', // Route 4 = Transactional SMS
            'sender' => SMS_SENDER_ID,
            'authkey' => SMS_API_KEY
        ];
        
        // Use cURL to send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            logSMS($phone, $message, 'sent', $response);
            return true;
        } else {
            logSMS($phone, $message, 'failed', $response);
            error_log("MSG91 SMS Error - HTTP Code: {$httpCode}, Response: {$response}");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("MSG91 SMS Send Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send SMS via Twilio Gateway (placeholder)
 * 
 * @param string $phone Phone number
 * @param string $otp_code OTP code
 * @return bool Success status
 */
function sendSMSTwilio($phone, $otp_code) {
    try {
        // Placeholder for Twilio implementation
        // Requires: composer require twilio/sdk
        
        error_log("Twilio SMS not configured. Please implement Twilio integration.");
        return false;
        
    } catch (Exception $e) {
        error_log("Twilio SMS Send Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Format phone number for SMS gateway
 * 
 * @param string $phone Raw phone number
 * @return string Formatted phone number with country code
 */
function formatPhoneNumber($phone) {
    // Remove spaces, dashes, and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // If no country code, add India country code
    if (strpos($phone, '+') === false) {
        if (strlen($phone) == 10) {
            $phone = '+91' . $phone;
        } else if (strlen($phone) == 12 && substr($phone, 0, 2) == '91') {
            $phone = '+' . $phone;
        }
    }
    
    return $phone;
}

/**
 * Log SMS for auditing
 * 
 * @param string $phone Phone number
 * @param string $message SMS message
 * @param string $status SMS status (pending, sent, failed)
 * @param string $response Gateway response
 */
function logSMS($phone, $message, $status = 'sent', $response = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sms_logs (user_id, phone_number, message, status, response, sent_at) 
            VALUES (NULL, ?, ?, ?, ?, ?)
        ");
        
        $sent_at = ($status === 'sent') ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$phone, $message, $status, $response, $sent_at]);
        
    } catch (PDOException $e) {
        error_log("SMS Log Error: " . $e->getMessage());
    }
}

/**
 * Test SMS Gateway Connection
 * 
 * @param string $test_phone Test phone number
 * @return array Status array
 */
function testSMSGateway($test_phone) {
    if (!SMS_ENABLED) {
        return [
            'success' => false,
            'message' => 'SMS Gateway is not enabled. Please configure SMS_API_KEY.',
            'gateway' => SMS_GATEWAY
        ];
    }
    
    $test_otp = '123456';
    
    if (sendOTPSMS($test_phone, $test_otp)) {
        return [
            'success' => true,
            'message' => 'Test SMS sent successfully!',
            'gateway' => SMS_GATEWAY
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send test SMS. Check API configuration.',
            'gateway' => SMS_GATEWAY
        ];
    }
}
?>
