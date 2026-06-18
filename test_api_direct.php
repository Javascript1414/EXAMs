<?php
/**
 * Direct API Test - Check PDF endpoint
 */
header('Content-Type: application/json');

// Test with a known PDF file
$test_file = 'uploads/notes/1781797959_Sample_Notes_-_Arrays_and_Linked_Lists.pdf';

echo "Testing PDF API endpoint...\n";
echo "Test file: $test_file\n\n";

// Simulate what check-pdf.php does
$full_path = __DIR__ . '/' . $test_file;
$full_path_real = realpath($full_path);

echo "Full Path (relative): $full_path\n";
echo "Full Path (realpath): $full_path_real\n";
echo "File exists: " . (file_exists($full_path) ? "YES" : "NO") . "\n";
echo "Is readable: " . (is_readable($full_path) ? "YES" : "NO") . "\n";

// Test path normalization
echo "\nTesting path normalization...\n";
$full_path_normalized = str_replace('\\', '/', $full_path_real);
$uploads_base = realpath(__DIR__ . '/uploads');
$uploads_base_normalized = str_replace('\\', '/', $uploads_base);

if (substr($uploads_base_normalized, -1) !== '/') {
    $uploads_base_normalized .= '/';
}

echo "Full path (normalized): $full_path_normalized\n";
echo "Uploads base (normalized): $uploads_base_normalized\n";

$strpos_result = strpos($full_path_normalized, $uploads_base_normalized);
echo "strpos result: " . ($strpos_result === 0 ? "0 (PASS)" : ($strpos_result === false ? "false (FAIL)" : "$strpos_result")) . "\n";

// Show MIME type
$mime = mime_content_type($full_path_real);
echo "\nMIME Type: $mime\n";
echo "Extension: " . pathinfo($full_path_real, PATHINFO_EXTENSION) . "\n";

// Show file size
echo "File size: " . filesize($full_path_real) . " bytes\n";

// Test file permissions
echo "File permissions: " . substr(sprintf('%o', fileperms($full_path_real)), -4) . "\n";
