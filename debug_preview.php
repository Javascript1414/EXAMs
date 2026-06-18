<?php
/**
 * Debug Student Notes Page
 * Check if the API endpoints are working
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

// Get first note from database
$query = $pdo->query("SELECT id, title, file_path FROM notes LIMIT 1");
$note = $query->fetch();

if (!$note) {
    echo json_encode(['error' => 'No notes found']);
    exit;
}

echo "Testing PDF preview API endpoints...\n\n";
echo "Note: " . $note['title'] . "\n";
echo "File Path: " . $note['file_path'] . "\n\n";

// Test 1: File exists
echo "1. FILE EXISTENCE CHECK\n";
$full_path = __DIR__ . '/' . $note['file_path'];
echo "Full path: $full_path\n";
echo "File exists: " . (file_exists($full_path) ? "YES" : "NO") . "\n";
echo "Is readable: " . (is_readable($full_path) ? "YES" : "NO") . "\n";
echo "File size: " . filesize($full_path) . " bytes\n\n";

// Test 2: Test check-pdf.php API
echo "2. CHECK-PDF.PHP API TEST\n";
$encoded_file = urlencode($note['file_path']);
$check_url = "http://localhost/EXAMs/api/check-pdf.php?file=$encoded_file";
echo "Check URL: $check_url\n";

// Simulate the check-pdf API call
$full_path = __DIR__ . '/../' . $note['file_path'];
$full_path = realpath($full_path);
$uploads_base = realpath(__DIR__ . '/../uploads');

echo "Real path: $full_path\n";
echo "Uploads base: $uploads_base\n";

// Test path normalization
$full_path_normalized = str_replace('\\', '/', $full_path);
$uploads_base_normalized = str_replace('\\', '/', $uploads_base);
if (substr($uploads_base_normalized, -1) !== '/') {
    $uploads_base_normalized .= '/';
}

echo "Normalized full path: $full_path_normalized\n";
echo "Normalized uploads base: $uploads_base_normalized\n";
echo "strpos result: " . (strpos($full_path_normalized, $uploads_base_normalized) !== false ? "PASS" : "FAIL") . "\n";

// Test 3: MIME type
echo "\n3. MIME TYPE CHECK\n";
$mime = mime_content_type($full_path);
echo "MIME type: $mime\n";
echo "Extension: " . pathinfo($full_path, PATHINFO_EXTENSION) . "\n";

// Test 4: Permissions
echo "\n4. FILE PERMISSIONS\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($full_path)), -4) . "\n";

echo "\n✓ All checks complete";
