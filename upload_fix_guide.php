<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>📸 Photo Upload - Complete Fix Guide</h1>\n";

echo "<div style='background:#fff3cd; padding:20px; border-radius:5px; margin-bottom:20px; border-left:4px solid #ffc107;'>\n";
echo "<h3>⚠️ Issue Found</h3>\n";
echo "<p><strong>Photo upload is not working because:</strong> Upload directories don't have Write permissions</p>\n";
echo "</div>\n";

echo "<h2>🔧 Step-by-Step Fix</h2>\n";

echo "<h3>Method 1: Automatic Fix (PHP)</h3>\n";
echo "<p><strong><a href='fix_upload_permissions.php' style='color:#667eea; font-size:16px;'>Click Here to Auto-Fix Permissions</a></strong></p>\n";
echo "<p>This will attempt to automatically fix directory permissions</p>\n";

echo "<h3>Method 2: Manual Fix (Windows GUI)</h3>\n";
echo "<ol style='line-height:2;'>\n";
echo "<li>Open File Explorer</li>\n";
echo "<li>Navigate to: <code>C:\\xampp\\htdocs\\EXAMs\\uploads\\profiles</code></li>\n";
echo "<li>Right-click the <strong>profiles</strong> folder</li>\n";
echo "<li>Click <strong>Properties</strong></li>\n";
echo "<li>Go to the <strong>Security</strong> tab</li>\n";
echo "<li>Click <strong>Edit</strong></li>\n";
echo "<li>Select <strong>Users</strong> from the list</li>\n";
echo "<li>Check the <strong>Modify</strong> checkbox</li>\n";
echo "<li>Click <strong>Apply</strong></li>\n";
echo "<li>When asked \"Do you want to change permissions on subfolders?\", click <strong>Yes</strong></li>\n";
echo "<li>Click <strong>OK</strong></li>\n";
echo "</ol>\n";

echo "<h3>Method 3: Command Line (Admin CMD)</h3>\n";
echo "<p>Open Command Prompt as Administrator and run:</p>\n";
echo "<pre style='background:#f5f5f5; padding:10px; border-radius:5px;'>\n";
echo "icacls \"C:\\xampp\\htdocs\\EXAMs\\uploads\\profiles\" /grant Users:(OI)(CI)M /T\n";
echo "</pre>\n";

echo "<h2>✅ Verification</h2>\n";

echo "<div style='border:1px solid #667eea; padding:20px; border-radius:5px; margin:20px 0;'>\n";
echo "<h3>Check If Fix Works</h3>\n";

// Check directories
$dirs = [
    'Profile Photos' => __DIR__ . '/uploads/profiles/profile_photos',
    'Cover Photos' => __DIR__ . '/uploads/profiles/cover_photos'
];

$all_ok = true;
foreach ($dirs as $name => $dir) {
    $writable = is_writable($dir) ? '✓' : '✗';
    $color = is_writable($dir) ? 'green' : 'red';
    
    if (!is_writable($dir)) $all_ok = false;
    
    echo "<p style='color:$color; font-size:16px;'><strong>$writable $name</strong>: " . (is_writable($dir) ? 'WRITABLE' : 'NOT WRITABLE') . "</p>\n";
}

if ($all_ok) {
    echo "<p style='color:green; font-size:16px; font-weight:bold;'>✅ All directories are writable!</p>\n";
} else {
    echo "<p style='color:red; font-size:16px; font-weight:bold;'>❌ Still have permission issues</p>\n";
}

echo "</div>\n";

echo "<h2>🧪 Test Upload</h2>\n";

if ($all_ok) {
    echo "<p style='color:green; padding:10px; background:#e8f5e9; border-radius:5px;'>\n";
    echo "Permissions are fixed! You can now test:<br><br>\n";
    echo "<strong><a href='full_upload_debug.php' style='color:green; font-size:16px;'>→ Go to Upload Test Page</a></strong>\n";
    echo "</p>\n";
} else {
    echo "<p style='color:red; padding:10px; background:#ffebee; border-radius:5px;'>\n";
    echo "Please fix the permissions first, then try again\n";
    echo "</p>\n";
}

echo "<h2>📋 What's Happening</h2>\n";
echo "<p>Windows limits who can write to folders. The web server (Apache) needs permission to save uploaded photos. The steps above give Apache permission to write to the upload folders.</p>\n";

echo "<h2>🚀 After Permissions Are Fixed</h2>\n";
echo "<p>Go to: <a href='student/edit_profile.php'><strong>Edit Your Profile</strong></a></p>\n";
echo "<ol>\n";
echo "<li>Upload your profile photo (JPG, PNG, GIF)</li>\n";
echo "<li>Photo must be at least 100×100 pixels</li>\n";
echo "<li>Photo will be saved to: <code>/uploads/profiles/profile_photos/</code></li>\n";
echo "<li>View on profile page: <a href='student/profile.php'>Your Profile</a></li>\n";
echo "</ol>\n";

echo "<h2>❓ FAQ</h2>\n";
echo "<dl style='line-height:2;'>\n";
echo "<dt><strong>Q: Why do I need to fix permissions?</strong></dt>\n";
echo "<dd>A: Windows security requires explicit permission for any program (Apache web server) to write files. This is by design.</dd>\n";
echo "<br>\n";
echo "<dt><strong>Q: Is it safe to give 'Modify' permission?</strong></dt>\n";
echo "<dd>A: Yes! The upload directory has security files that prevent PHP execution and directory listing.</dd>\n";
echo "<br>\n";
echo "<dt><strong>Q: Photo still not uploading?</strong></dt>\n";
echo "<dd>A: <a href='full_upload_debug.php'>Use the debug page</a> to see the exact error message</dd>\n";
echo "<br>\n";
echo "<dt><strong>Q: Can I move to a different location?</strong></dt>\n";
echo "<dd>A: No, uploading is configured for <code>/uploads/profiles/</code> directory</dd>\n";
echo "</dl>\n";

?>
