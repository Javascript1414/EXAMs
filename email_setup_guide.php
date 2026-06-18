<?php
/**
 * Email Configuration Setup
 * अपने अनुसार configure करें
 */

require_once 'includes/db.php';
require_once 'vendor/autoload.php';

echo "📧 EMAIL CONFIGURATION SETUP\n";
echo "=" . str_repeat("=", 70) . "\n\n";

echo "आपके पास 3 विकल्प हैं (3 OPTIONS):\n\n";

echo "OPTION 1: Gmail के साथ (With Gmail App Password)\n";
echo "-" . str_repeat("-", 68) . "\n";
echo "Steps:\n";
echo "1. Gmail account खोलें: https://myaccount.google.com/\n";
echo "2. Security → App Passwords में जाएं\n";
echo "3. App Password generate करें\n";
echo "4. नीचे दिया हुआ code में डालें\n\n";

echo "OPTION 2: Mailtrap के साथ (Free - Testing के लिए)\n";
echo "-" . str_repeat("-", 68) . "\n";
echo "Steps:\n";
echo "1. Mailtrap.io पर free account बनाएं\n";
echo "2. Inbox settings से SMTP credentials copy करें\n";
echo "3. नीचे दिया हुआ code में डालें\n\n";

echo "OPTION 3: SendGrid के साथ (Free - Production के लिए)\n";
echo "-" . str_repeat("-", 68) . "\n";
echo "Steps:\n";
echo "1. SendGrid.com पर free account बनाएं\n";
echo "2. API Key generate करें\n";
echo "3. नीचे दिया हुआ code में डालें\n\n";

?>
