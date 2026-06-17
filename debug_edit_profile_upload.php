<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please <a href='/EXAMs/login.php'>log in</a> first");
}

$user_id = $_SESSION['user_id'];

echo "<h1>🔍 Edit Profile - Upload Debug</h1>\n";

// Show upload form
echo "<h2>Test Upload Form</h2>\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_profile_photo'])) {
    echo "<h3>📤 Upload Result</h3>\n";
    
    $file = $_FILES['test_profile_photo'];
    
    echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px; margin-bottom:15px;'>\n";
    echo "<strong>File Details:</strong><br>\n";
    echo "Name: " . htmlspecialchars($file['name']) . "<br>\n";
    echo "Type: " . htmlspecialchars($file['type']) . "<br>\n";
    echo "Size: " . $file['size'] . " bytes<br>\n";
    echo "Error: " . $file['error'] . " (0 = OK)<br>\n";
    echo "Tmp: " . htmlspecialchars($file['tmp_name']) . "\n";
    echo "</div>\n";
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Partial upload',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp dir',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file',
            UPLOAD_ERR_EXTENSION => 'Extension blocked'
        ];
        echo "<p style='color:red; font-weight:bold;'>❌ Upload Error: " . ($errors[$file['error']] ?? 'Unknown') . "</p>\n";
    } else {
        // Test validation
        echo "<h4>🧪 Validation Test:</h4>\n";
        $validation = validateImageFile($file, 'profile');
        
        if ($validation['valid']) {
            echo "<p style='color:green;'>✓ Validation passed</p>\n";
            
            // Test upload
            echo "<h4>📝 Upload Test:</h4>\n";
            $result = uploadImageFile($file, $user_id, 'profile');
            
            if ($result['success']) {
                echo "<p style='color:green; font-weight:bold;'>✓ Upload Successful!</p>\n";
                echo "<p>URL: " . htmlspecialchars($result['path']) . "</p>\n";
                echo "<p><img src='" . htmlspecialchars($result['path']) . "' style='max-width:200px; border:1px solid #ccc;'></p>\n";
            } else {
                echo "<p style='color:red; font-weight:bold;'>✗ Upload Failed</p>\n";
                echo "<p>Error: " . htmlspecialchars($result['error']) . "</p>\n";
            }
        } else {
            echo "<p style='color:red; font-weight:bold;'>✗ Validation Failed</p>\n";
            echo "<p>Error: " . htmlspecialchars($validation['error']) . "</p>\n";
        }
    }
    
    echo "<hr>\n";
}

// Form
echo "<form method='post' enctype='multipart/form-data' style='border:2px solid #667eea; padding:20px; border-radius:5px;'>\n";
echo "<p>\n";
echo "<label>Select Profile Photo (min 100×100px, max 5MB):</label><br><br>\n";
echo "<input type='file' name='test_profile_photo' accept='image/*' required style='padding:10px;'>\n";
echo "</p>\n";
echo "<p><button type='submit' style='padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; font-size:14px;'>Test Upload</button></p>\n";
echo "</form>\n";

// Check what's happening in edit_profile
echo "<h2>✅ Edit Profile Form Status</h2>\n";

$form_ok = true;
$checks = [];

// Check 1: Form has enctype
$edit_profile_content = file_get_contents(__DIR__ . '/student/edit_profile.php');
$has_enctype = strpos($edit_profile_content, 'enctype="multipart/form-data"') !== false;
$checks[] = ['Form has enctype="multipart/form-data"', $has_enctype ? '✓' : '✗'];
if (!$has_enctype) $form_ok = false;

// Check 2: File input exists
$has_file_input = strpos($edit_profile_content, 'name="profile_photo"') !== false;
$checks[] = ['File input exists (profile_photo)', $has_file_input ? '✓' : '✗'];
if (!$has_file_input) $form_ok = false;

// Check 3: Submit button exists
$has_submit = strpos($edit_profile_content, 'type="submit"') !== false;
$checks[] = ['Submit button exists', $has_submit ? '✓' : '✗'];
if (!$has_submit) $form_ok = false;

// Check 4: Upload functions exist
$has_upload_func = strpos($edit_profile_content, 'uploadImageFile') !== false;
$checks[] = ['uploadImageFile() called', $has_upload_func ? '✓' : '✗'];
if (!$has_upload_func) $form_ok = false;

// Check 5: CSRF token
$has_csrf = strpos($edit_profile_content, 'csrf_token') !== false;
$checks[] = ['CSRF token present', $has_csrf ? '✓' : '✗'];
if (!$has_csrf) $form_ok = false;

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>\n";
foreach ($checks as $check) {
    $color = $check[1] === '✓' ? 'lightgreen' : 'salmon';
    echo "<tr style='background:$color;'><td>" . $check[0] . "</td><td><strong>" . $check[1] . "</strong></td></tr>\n";
}
echo "</table>\n";

if ($form_ok) {
    echo "<p style='color:green; padding:10px; background:#e8f5e9; border-radius:5px; margin-top:15px;'>\n";
    echo "<strong>✓ Edit Profile Form looks correct!</strong> Try uploading at:<br>\n";
    echo "<a href='student/edit_profile.php' style='color:green; font-size:16px; font-weight:bold;'>student/edit_profile.php</a>\n";
    echo "</p>\n";
} else {
    echo "<p style='color:red; padding:10px; background:#ffebee; border-radius:5px; margin-top:15px;'>\n";
    echo "<strong>❌ Edit Profile Form has issues - see above</strong>\n";
    echo "</p>\n";
}

// Show how edit_profile upload works
echo "<h2>📋 How Edit Profile Upload Works</h2>\n";
echo "<ol>\n";
echo "<li>User fills form and selects photo</li>\n";
echo "<li>Clicks 'Save Profile' button</li>\n";
echo "<li>Form submits with enctype='multipart/form-data'</li>\n";
echo "<li>PHP receives \$_FILES['profile_photo']</li>\n";
echo "<li>uploadImageFile() validates and saves photo</li>\n";
echo "<li>Photo URL saved to database</li>\n";
echo "<li>Success message shown</li>\n";
echo "</ol>\n";

// Show current settings
echo "<h2>⚙️ Current Settings</h2>\n";
echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>\n";
echo "<p>Upload Dir: " . getUploadDirectory('profile') . "</p>\n";
echo "<p>Writable: " . (is_writable(getUploadDirectory('profile')) ? '✓ YES' : '✗ NO') . "</p>\n";

?>
