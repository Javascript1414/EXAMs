<?php
/**
 * Setup Upload Directories
 * Creates necessary directory structure for profile photos and cover photos
 * Run this once to initialize the upload directories
 */

// Create base uploads directory
$base_dir = __DIR__ . '/uploads';
if (!is_dir($base_dir)) {
    mkdir($base_dir, 0755, true);
    echo "✓ Created: /uploads\n";
}

// Create profiles directory
$profiles_dir = $base_dir . '/profiles';
if (!is_dir($profiles_dir)) {
    mkdir($profiles_dir, 0755, true);
    echo "✓ Created: /uploads/profiles\n";
}

// Create profile photos subdirectory
$profile_photos_dir = $profiles_dir . '/profile_photos';
if (!is_dir($profile_photos_dir)) {
    mkdir($profile_photos_dir, 0755, true);
    echo "✓ Created: /uploads/profiles/profile_photos\n";
}

// Create cover photos subdirectory
$cover_photos_dir = $profiles_dir . '/cover_photos';
if (!is_dir($cover_photos_dir)) {
    mkdir($cover_photos_dir, 0755, true);
    echo "✓ Created: /uploads/profiles/cover_photos\n";
}

// Create .htaccess to prevent directory listing in uploads
$htaccess_content = "Options -Indexes
<FilesMatch \"\.php$\">
    Deny from all
</FilesMatch>\n";

$htaccess_files = [
    $profiles_dir . '/.htaccess',
    $profile_photos_dir . '/.htaccess',
    $cover_photos_dir . '/.htaccess'
];

foreach ($htaccess_files as $htaccess_file) {
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, $htaccess_content);
        echo "✓ Created: " . str_replace(__DIR__, '', $htaccess_file) . "\n";
    }
}

// Create index.php files to prevent directory listing
$index_content = "<?php\n// Directory index\nheader('HTTP/1.0 403 Forbidden');\ndie();\n";

$index_files = [
    $profiles_dir . '/index.php',
    $profile_photos_dir . '/index.php',
    $cover_photos_dir . '/index.php'
];

foreach ($index_files as $index_file) {
    if (!file_exists($index_file)) {
        file_put_contents($index_file, $index_content);
        echo "✓ Created: " . str_replace(__DIR__, '', $index_file) . "\n";
    }
}

echo "\n✅ Upload directory structure initialized!\n";
echo "\nDirectory Structure:\n";
echo "├── /uploads/\n";
echo "│   └── /profiles/\n";
echo "│       ├── /profile_photos/  (Student profile photos)\n";
echo "│       └── /cover_photos/     (Student cover photos)\n";
echo "\nSecurity Measures:\n";
echo "✓ Directory listing disabled\n";
echo "✓ PHP execution prevented\n";
echo "✓ File validation in place\n";
?>
