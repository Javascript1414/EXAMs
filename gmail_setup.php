<?php
/**
 * ⚡ QUICK GMAIL SETUP GUIDE
 * यह file execute करके dekhne ke liye
 */

echo "=" . str_repeat("=", 90) . "\n";
echo "🚀 QUICK GMAIL SETUP (सिर्फ 2 मिनट में!)\n";
echo "=" . str_repeat("=", 90) . "\n\n";

echo "✋ IMPORTANT: आपका Gmail Account है: soumyajitsantra699@gmail.com\n\n";

echo "📋 EXACT STEPS (बिल्कुल सही order में करो):\n";
echo str_repeat("-", 90) . "\n\n";

echo "STEP 1️⃣ : Gmail Security Page खोलो\n";
echo "   👉 Link: https://myaccount.google.com/security\n";
echo "   ⏱️ Wait करो लोड होने के लिए\n\n";

echo "STEP 2️⃣ : 2-Step Verification Enable करो\n";
echo "   👉 Left sidebar में \"2-Step Verification\" देखो\n";
echo "   👉 अगर पहले से ON है तो next step go करो\n";
echo "   👉 अगर OFF है तो click करके ON करो\n";
echo "   👉 अपना phone number verify करो (OTP से)\n\n";

echo "STEP 3️⃣ : App Passwords Generate करो\n";
echo "   👉 अब फिर से https://myaccount.google.com/security खोलो\n";
echo "   👉 \"App passwords\" search करो (left में)\n";
echo "   👉 Click करो \"App passwords\" पर\n";
echo "   👉 अगर नहीं दिख रहा तो scroll करो नीचे\n\n";

echo "STEP 4️⃣ : App Password Generate करो\n";
echo "   👉 Dropdown में से select करो:\n";
echo "      • First dropdown: \"Mail\"\n";
echo "      • Second dropdown: \"Windows Computer\"\n";
echo "   👉 \"Generate\" button click करो\n";
echo "   👉 Google तुम्हें 16-character password देगा\n";
echo "   👉 यह password देखाई दे रहे yellow box में है\n\n";

echo "STEP 5️⃣ : Password Copy करो\n";
echo "   👉 पूरा 16-character password select करो\n";
echo "   👉 Right-click → Copy करो\n";
echo "   👉 (या Ctrl+C से copy करो)\n\n";

echo "STEP 6️⃣ : अब यहां वापस आओ\n";
echo "   👉 यह page reload करो\n";
echo "   👉 नीचे paste करो password\n\n";

echo "=" . str_repeat("=", 90) . "\n";
echo "🔒 PASTE YOUR 16-CHARACTER APP PASSWORD HERE:\n";
echo "=" . str_repeat("=", 90) . "\n\n";

echo "<form method='POST' style='max-width: 600px;'>\n";
echo "    <label style='display: block; margin-bottom: 10px; font-weight: bold;'>📝 App Password:</label>\n";
echo "    <input type='password' name='app_password' placeholder='Paste your 16-character password here' style='width: 100%; padding: 10px; font-size: 14px; border: 2px solid #667eea; border-radius: 5px;'>\n";
echo "    <br><br>\n";
echo "    <button type='submit' style='padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; font-weight: bold;'>✅ SAVE & SEND EMAIL</button>\n";
echo "</form>\n\n";

if ($_POST && isset($_POST['app_password'])) {
    $password = trim($_POST['app_password']);
    
    if (strlen($password) < 10) {
        echo "<div style='color: red; font-weight: bold;'>\n";
        echo "❌ Password too short! Must be 16 characters\n";
        echo "</div>\n";
    } else {
        // Update config.php
        $config_file = __DIR__ . '/config.php';
        $config_content = file_get_contents($config_file);
        
        // Replace SMTP_PASS
        $config_content = preg_replace(
            "/define\('SMTP_PASS',\s*'[^']*'\);/",
            "define('SMTP_PASS', '" . addslashes($password) . "');",
            $config_content
        );
        
        // Also enable Gmail config
        $config_content = preg_replace(
            "/\/\/ define\('SMTP_HOST',\s*'smtp\.gmail\.com'\);/",
            "define('SMTP_HOST', 'smtp.gmail.com');",
            $config_content
        );
        $config_content = preg_replace(
            "/\/\/ define\('SMTP_PORT',\s*587\);/",
            "define('SMTP_PORT', 587);",
            $config_content
        );
        $config_content = preg_replace(
            "/\/\/ define\('SMTP_USER',\s*'[^']*'\);/",
            "define('SMTP_USER', 'soumyajitsantra699@gmail.com');",
            $config_content
        );
        
        file_put_contents($config_file, $config_content);
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h3>✅ PASSWORD SAVED!</h3>\n";
        echo "<p>Gmail SMTP credentials updated successfully in config.php</p>\n";
        echo "<p>Password: " . str_repeat('*', strlen($password)-4) . substr($password, -4) . "</p>\n";
        echo "</div>\n";
        
        echo "<div style='background: #e7f3ff; border: 1px solid #b3d9ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h3>🚀 अब Email भेजो!</h3>\n";
        echo "<p><a href='send_email_working.php' style='color: #004085; font-weight: bold; text-decoration: underline;'>Click यहां email भेजने के लिए →</a></p>\n";
        echo "</div>\n";
    }
} else {
    echo "\n" . str_repeat("-", 90) . "\n";
    echo "💡 PASSWORD कहां से मिलेगा?\n";
    echo str_repeat("-", 90) . "\n\n";
    
    echo "Gmail ने जो 16-character password दिया है:\n";
    echo "अगर ऐसा दिखता है: \"abcd efgh ijkl mnop\"\n\n";
    
    echo "तो आप यह paste करो (spaces के साथ या बिना):\n";
    echo "   abcdefghijklmnop\n";
    echo "या\n";
    echo "   abcd efgh ijkl mnop\n\n";
    
    echo "दोनों ठीक हैं! 🟢\n\n";
}

echo "\n" . str_repeat("=", 90) . "\n";

?>
