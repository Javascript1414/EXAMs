<?php
/**
 * Test PDF Preview API Endpoints
 * Quick test script to verify check-pdf and serve-pdf endpoints
 */

require_once __DIR__ . '/includes/db.php';

// Test 1: Check if there are any notes in database
echo "=== TEST 1: Database Notes ===\n";
$query = $pdo->query("SELECT id, title, file_path FROM notes LIMIT 3");
$notes = $query->fetchAll(PDO::FETCH_ASSOC);
if ($notes) {
    foreach ($notes as $note) {
        echo "Note ID: {$note['id']}, Title: {$note['title']}\n";
        echo "File Path: {$note['file_path']}\n";
        
        // Test 2: Check file existence
        echo "  Checking if file exists...\n";
        $full_path = __DIR__ . '/' . $note['file_path'];
        $real_path = realpath($full_path);
        echo "  Full Path: $full_path\n";
        echo "  Real Path: $real_path\n";
        echo "  File Exists: " . (file_exists($full_path) ? "YES" : "NO") . "\n";
        echo "  Is Readable: " . (is_readable($full_path) ? "YES" : "NO") . "\n";
        
        // Test 3: Test path normalization (like check-pdf.php does)
        echo "  Testing path normalization...\n";
        $full_path_normalized = str_replace('\\', '/', $real_path);
        $uploads_base = realpath(__DIR__ . '/uploads');
        $uploads_base_normalized = str_replace('\\', '/', $uploads_base);
        if (substr($uploads_base_normalized, -1) !== '/') {
            $uploads_base_normalized .= '/';
        }
        
        echo "  Full path (normalized): $full_path_normalized\n";
        echo "  Uploads base (normalized): $uploads_base_normalized\n";
        echo "  Prefix check (strpos): " . (strpos($full_path_normalized, $uploads_base_normalized) !== false ? "PASS" : "FAIL") . "\n";
        echo "\n";
    }
} else {
    echo "No notes found in database\n";
}

// Test 4: List files in uploads/notes
echo "\n=== TEST 2: Files in uploads/notes ===\n";
$upload_dir = __DIR__ . '/uploads/notes';
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    $pdfs = array_filter($files, function($f) { return pathinfo($f, PATHINFO_EXTENSION) === 'pdf'; });
    echo "Found " . count($pdfs) . " PDF files:\n";
    foreach (array_slice($pdfs, 0, 3) as $pdf) {
        $path = $upload_dir . '/' . $pdf;
        echo "  $pdf (" . filesize($path) . " bytes)\n";
    }
} else {
    echo "uploads/notes directory not found\n";
}

echo "\n=== TESTS COMPLETE ===\n";
