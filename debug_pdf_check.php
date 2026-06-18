<?php
require_once 'config.php';
require_once 'includes/db.php';

echo "=== PDF PREVIEW DEBUG ===\n";

// 1. Check database for notes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM notes");
$count = $stmt->fetch()['total'];
echo "\n1. Database Notes Count: $count\n";

if ($count > 0) {
    $stmt = $pdo->query("SELECT id, title, file_path, status FROM notes LIMIT 10");
    $notes = $stmt->fetchAll();
    echo "\n2. Sample Notes from Database:\n";
    foreach ($notes as $note) {
        $file_path = $note['file_path'];
        $full_path = __DIR__ . '/' . $file_path;
        $exists = file_exists($full_path);
        $readable = $exists && is_readable($full_path);
        $size = $exists ? filesize($full_path) : 'N/A';
        
        echo "\n   ID: {$note['id']}\n";
        echo "   Title: {$note['title']}\n";
        echo "   Path: {$file_path}\n";
        echo "   Status: {$note['status']}\n";
        echo "   Full Path: $full_path\n";
        echo "   File Exists: " . ($exists ? 'YES' : 'NO') . "\n";
        echo "   Readable: " . ($readable ? 'YES' : 'NO') . "\n";
        echo "   Size: $size bytes\n";
    }
}

// 3. Check file permissions
echo "\n3. Uploads/Notes Directory Permissions:\n";
$notes_dir = __DIR__ . '/uploads/notes';
$perms = fileperms($notes_dir);
echo "   Path: $notes_dir\n";
echo "   Exists: " . (is_dir($notes_dir) ? 'YES' : 'NO') . "\n";
echo "   Readable: " . (is_readable($notes_dir) ? 'YES' : 'NO') . "\n";
echo "   Writable: " . (is_writable($notes_dir) ? 'YES' : 'NO') . "\n";
echo "   Permissions: " . substr(sprintf('%o', $perms), -4) . "\n";

// 4. Check physical files
echo "\n4. Physical PDF Files in uploads/notes:\n";
$files = scandir($notes_dir);
$pdf_files = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'pdf'; });
foreach ($pdf_files as $file) {
    $full_path = $notes_dir . '/' . $file;
    $size = filesize($full_path);
    echo "   - $file (" . round($size / 1024, 2) . " KB)\n";
}

// 5. Check API endpoints exist
echo "\n5. API Endpoints Check:\n";
$check_pdf = __DIR__ . '/api/check-pdf.php';
$serve_pdf = __DIR__ . '/api/serve-pdf.php';
echo "   check-pdf.php exists: " . (file_exists($check_pdf) ? 'YES' : 'NO') . "\n";
echo "   serve-pdf.php exists: " . (file_exists($serve_pdf) ? 'YES' : 'NO') . "\n";

// 6. Test path validation logic
echo "\n6. Path Validation Test:\n";
if (count($notes) > 0) {
    $test_note = $notes[0];
    $file_path = $test_note['file_path'];
    echo "   Testing file: {$test_note['title']}\n";
    echo "   File path from DB: $file_path\n";
    
    // Simulate check-pdf.php logic
    $file_path_test = preg_replace('/\.\.\//', '', $file_path);
    $file_path_test = preg_replace('/\.\.\\\\/', '', $file_path_test);
    $full_path_test = __DIR__ . '/../' . $file_path_test;
    $full_path_test = realpath($full_path_test);
    $uploads_base_test = realpath(__DIR__ . '/../uploads');
    
    echo "   Normalized file path: $full_path_test\n";
    echo "   Uploads base: $uploads_base_test\n";
    
    // Check validation
    $full_path_normalized = str_replace('\\', '/', $full_path_test);
    $uploads_base_normalized = str_replace('\\', '/', $uploads_base_test);
    
    if (substr($uploads_base_normalized, -1) !== '/') {
        $uploads_base_normalized .= '/';
    }
    
    $path_check = strpos($full_path_normalized, $uploads_base_normalized) === 0;
    echo "   Path validation: " . ($path_check ? 'PASS' : 'FAIL') . "\n";
    
    if (!$path_check) {
        echo "   ERROR: Path is not within uploads directory!\n";
        echo "   Normalized path: $full_path_normalized\n";
        echo "   Uploads base: $uploads_base_normalized\n";
    }
}

// 7. Check error logs
echo "\n7. Recent PHP Errors:\n";
$error_log = 'c:\\xampp\\php\\logs\\php_error_log';
if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent = array_slice($lines, -10);
    foreach ($recent as $line) {
        if (strpos($line, 'PDF') !== false || strpos($line, 'serve') !== false) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   Error log not found at $error_log\n";
}

echo "\n=== END DEBUG ===\n";
