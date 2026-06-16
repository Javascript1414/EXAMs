<?php
/**
 * Debug OTP Generation
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/otp_helper.php';

echo "<h2>🔍 OTP Generation Debug</h2>";
echo "<hr>";

// Step 1: Check if otp_verifications table exists
echo "<h3>1️⃣ Checking otp_verifications table:</h3>";
try {
    $result = $pdo->query("DESCRIBE otp_verifications");
    $columns = $result->fetchAll();
    echo "<p style='color: green;'>✅ Table exists with " . count($columns) . " columns</p>";
    
    echo "<p><strong>Columns:</strong></p>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")" . (($col['Null'] === 'NO') ? " NOT NULL" : " NULLABLE") . "</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Table error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 2: Test generateOTP function
echo "<h3>2️⃣ Testing generateOTP() function:</h3>";
try {
    $test_otp = generateOTP();
    echo "<p style='color: green;'>✅ Generated OTP: <strong>$test_otp</strong></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Step 3: Test creating a test user and OTP
echo "<h3>3️⃣ Testing OTP creation with test user:</h3>";

try {
    // Create test user if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['otp_test_' . time() . '@test.com']);
    
    if ($stmt->rowCount() === 0) {
        // Create test user
        $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
        $roleStmt->execute();
        $role = $roleStmt->fetch();
        
        if ($role) {
            $insertStmt = $pdo->prepare("
                INSERT INTO users (role_id, full_name, email, phone, password, trade_id, status, email_verified)
                VALUES (?, ?, ?, ?, ?, ?, 'inactive', FALSE)
            ");
            
            $insertStmt->execute([
                $role['id'],
                'OTP Test User',
                'otp_test_' . time() . '@test.com',
                '9999999999',
                password_hash('test123', PASSWORD_DEFAULT),
                1
            ]);
            
            $user_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Test user created with ID: $user_id</p>";
        } else {
            echo "<p style='color: red;'>❌ Student role not found</p>";
            exit;
        }
    } else {
        $user = $stmt->fetch();
        $user_id = $user['id'];
    }
    
    // Test OTP creation
    $test_otp = createOTP($pdo, $user_id, 'email_verification', 'both', 10);
    
    if ($test_otp) {
        echo "<p style='color: green;'>✅ OTP created successfully: <strong>$test_otp</strong></p>";
        
        // Check if it's in database
        $checkStmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE user_id = ? AND otp_code = ?");
        $checkStmt->execute([$user_id, $test_otp]);
        
        if ($checkStmt->rowCount() > 0) {
            $otp_record = $checkStmt->fetch();
            echo "<p style='color: green;'>✅ OTP verified in database</p>";
            echo "<ul>";
            echo "<li>ID: " . $otp_record['id'] . "</li>";
            echo "<li>User ID: " . $otp_record['user_id'] . "</li>";
            echo "<li>OTP Code: " . $otp_record['otp_code'] . "</li>";
            echo "<li>Purpose: " . $otp_record['purpose'] . "</li>";
            echo "<li>Channel: " . $otp_record['channel'] . "</li>";
            echo "<li>Expires At: " . $otp_record['expires_at'] . "</li>";
            echo "<li>Is Used: " . ($otp_record['is_used'] ? 'Yes' : 'No') . "</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>❌ OTP NOT found in database!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ OTP creation failed (returned false)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2 style='color: blue;'>✅ Debug Complete</h2>";
?>
