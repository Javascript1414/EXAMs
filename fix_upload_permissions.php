<?php
require_once __DIR__ . '/config.php';

echo "<h1>🔧 Fix Upload Directory Permissions</h1>\n";

$dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/profiles',
    __DIR__ . '/uploads/profiles/profile_photos',
    __DIR__ . '/uploads/profiles/cover_photos'
];

echo "<h2>Attempting to Fix Permissions</h2>\n";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>\n";
echo "<tr><th>Directory</th><th>Before</th><th>After Chmod</th><th>Writable?</th></tr>\n";

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        echo "<tr><td>$dir</td><td colspan='3' style='color:red;'>Directory does not exist!</td></tr>\n";
        continue;
    }
    
    // Get before permissions
    $before = decoct(fileperms($dir) & 0777);
    
    // Try to chmod
    $chmod_result = chmod($dir, 0777);
    
    // Get after permissions
    $after = decoct(fileperms($dir) & 0777);
    $writable = is_writable($dir) ? '✓ YES' : '✗ NO';
    
    $result_color = $writable === '✓ YES' ? 'lightgreen' : 'salmon';
    
    echo "<tr style='background:$result_color;'>\n";
    echo "<td><small>$dir</small></td>\n";
    echo "<td>$before</td>\n";
    echo "<td>$after " . ($chmod_result ? '✓' : '✗') . "</td>\n";
    echo "<td>$writable</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h2>Summary</h2>\n";

$all_writable = true;
foreach ($dirs as $dir) {
    if (!is_writable($dir)) {
        $all_writable = false;
        break;
    }
}

if ($all_writable) {
    echo "<p style='color:green; font-size:16px; padding:15px; background:#e8f5e9; border-radius:5px;'>\n";
    echo "✅ All directories are writable!<br>\n";
    echo "Photo upload should work now.<br><br>\n";
    echo "<a href='student/edit_profile.php' style='color:green; font-weight:bold; text-decoration:none;'>→ Go to Edit Profile</a>\n";
    echo "</p>\n";
} else {
    echo "<p style='color:red; font-size:16px; padding:15px; background:#ffebee; border-radius:5px;'>\n";
    echo "⚠️ Some directories are still not writable<br>\n";
    echo "This is a Windows permissions issue. Manual fix needed:<br><br>\n";
    echo "<code>Right-click folder → Properties → Security → Edit → Select 'Users' → Check 'Modify' → OK</code>\n";
    echo "</p>\n";
}

echo "<h2>Alternative: Manual Windows Fix</h2>\n";
echo "<p>If PHP chmod didn't work, do this:</p>\n";
echo "<ol>\n";
echo "<li>Navigate to: <code>C:\\xampp\\htdocs\\EXAMs\\uploads\\profiles</code></li>\n";
echo "<li>Right-click folder → <strong>Properties</strong></li>\n";
echo "<li>Go to <strong>Security</strong> tab</li>\n";
echo "<li>Click <strong>Edit</strong></li>\n";
echo "<li>Select '<strong>Users</strong>'</li>\n";
echo "<li>Check '<strong>Modify</strong>'</li>\n";
echo "<li>Click <strong>Apply</strong> and <strong>OK</strong></li>\n";
echo "<li>Select '<strong>Apply to this folder, subfolders and files</strong>'</li>\n";
echo "</ol>\n";

echo "<h2>After Fixing Permissions</h2>\n";
echo "<p>Test upload here: <a href='full_upload_debug.php'>Full Upload Debug</a></p>\n";

?>
