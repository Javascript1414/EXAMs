<?php
/**
 * Comprehensive PDF Preview System Diagnostic
 * Tests all components of the PDF preview flow
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Start output buffer to capture any errors
ob_start();

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => ENVIRONMENT,
    'base_url' => BASE_URL,
    'php_version' => PHP_VERSION,
];

// === 1. DATABASE CHECK ===
$db_check = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notes WHERE status = 'active'");
    $active_notes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notes");
    $total_notes = $stmt->fetch()['total'];
    
    $db_check = [
        'connected' => true,
        'total_notes' => $total_notes,
        'active_notes' => $active_notes,
        'error' => null
    ];
    
    // Get sample note data
    if ($active_notes > 0) {
        $stmt = $pdo->query("SELECT id, title, file_path, subject_id, trade_id FROM notes WHERE status = 'active' LIMIT 1");
        $sample = $stmt->fetch();
        $db_check['sample_note'] = $sample;
    }
} catch (Exception $e) {
    $db_check = [
        'connected' => false,
        'error' => $e->getMessage()
    ];
}
$diagnostics['database'] = $db_check;

// === 2. FILE SYSTEM CHECK ===
$fs_check = [];

// Check directories
$dirs_to_check = [
    'uploads' => __DIR__ . '/uploads',
    'uploads/notes' => __DIR__ . '/uploads/notes',
    'api' => __DIR__ . '/api',
];

foreach ($dirs_to_check as $name => $path) {
    $fs_check[$name] = [
        'exists' => is_dir($path),
        'readable' => is_readable($path),
        'writable' => is_writable($path),
        'path' => $path
    ];
}

// Check API files
$api_files = [
    'check-pdf.php' => __DIR__ . '/api/check-pdf.php',
    'serve-pdf.php' => __DIR__ . '/api/serve-pdf.php',
];

foreach ($api_files as $name => $path) {
    $fs_check[$name] = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'size' => file_exists($path) ? filesize($path) : 0,
        'path' => $path
    ];
}

// Check PDF files
$pdf_files = [];
$notes_dir = __DIR__ . '/uploads/notes';
if (is_dir($notes_dir)) {
    $files = scandir($notes_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
            $full_path = $notes_dir . '/' . $file;
            $pdf_files[$file] = [
                'size' => filesize($full_path),
                'readable' => is_readable($full_path),
                'permissions' => substr(sprintf('%o', fileperms($full_path)), -4),
                'full_path' => $full_path
            ];
        }
    }
}
$fs_check['pdf_files_found'] = count($pdf_files);
$fs_check['pdf_files'] = $pdf_files;

$diagnostics['filesystem'] = $fs_check;

// === 3. PATH VALIDATION TEST ===
$path_test = [];

if (isset($db_check['sample_note'])) {
    $file_path = $db_check['sample_note']['file_path'];
    
    // Test serve-pdf.php path validation
    $sanitized = str_replace('\\', '/', $file_path);
    $sanitized = preg_replace('/\.\.\//', '', $sanitized);
    $sanitized = preg_replace('/\.\.\\\\/', '', $sanitized);
    
    $full_path = __DIR__ . '/' . $sanitized;
    $real_path = @realpath($full_path);
    
    $uploads_base = realpath(__DIR__ . '/uploads');
    
    // Normalize
    $real_path_norm = $real_path ? str_replace('\\', '/', $real_path) : null;
    $uploads_base_norm = str_replace('\\', '/', $uploads_base);
    
    if (substr($uploads_base_norm, -1) !== '/') {
        $uploads_base_norm .= '/';
    }
    
    $validation_pass = $real_path && strpos($real_path_norm, $uploads_base_norm) === 0;
    
    $path_test = [
        'file_path_from_db' => $file_path,
        'sanitized_path' => $sanitized,
        'full_path_constructed' => $full_path,
        'real_path' => $real_path,
        'uploads_base' => $uploads_base,
        'real_path_normalized' => $real_path_norm,
        'uploads_base_normalized' => $uploads_base_norm,
        'path_validation_pass' => $validation_pass,
        'file_exists' => file_exists($full_path),
        'file_readable' => is_readable($full_path),
    ];
}

$diagnostics['path_validation'] = $path_test;

// === 4. FUNCTION CHECK ===
$functions_check = [
    'requireLogin' => function_exists('requireLogin'),
    'sanitizeInput' => function_exists('sanitizeInput'),
    'isLoggedIn' => function_exists('isLoggedIn'),
    'hasRole' => function_exists('hasRole'),
    'BASE_URL_constant' => defined('BASE_URL'),
];

$diagnostics['functions'] = $functions_check;

// === 5. SESSION CHECK ===
$session_check = [
    'session_status' => session_status(),
    'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
    'role_name' => $_SESSION['role_name'] ?? 'NOT SET',
    'is_logged_in' => isset($_SESSION['user_id']),
];

$diagnostics['session'] = $session_check;

// === 6. CODE INSPECTION ===
$code_check = [];

// Check if serve-pdf.php calls requireLogin
$serve_pdf_code = file_get_contents(__DIR__ . '/api/serve-pdf.php');
$code_check['serve_pdf_calls_requireLogin'] = strpos($serve_pdf_code, 'requireLogin()') !== false;

// Check if check-pdf.php calls requireLogin
$check_pdf_code = file_get_contents(__DIR__ . '/api/check-pdf.php');
$code_check['check_pdf_calls_requireLogin'] = strpos($check_pdf_code, 'requireLogin()') !== false;

// Check if previewPDF function exists in student/notes.php
$notes_code = file_get_contents(__DIR__ . '/student/notes.php');
$code_check['student_notes_has_previewPDF'] = strpos($notes_code, 'function previewPDF') !== false;
$code_check['student_notes_has_previewPDF_v2'] = strpos($notes_code, 'const previewPDF') !== false;
$code_check['student_notes_has_loadPDFWithIframe'] = strpos($notes_code, 'function loadPDFWithIframe') !== false;
$code_check['student_notes_has_checkUrl'] = strpos($notes_code, '/api/check-pdf.php') !== false;
$code_check['student_notes_has_serveUrl'] = strpos($notes_code, '/api/serve-pdf.php') !== false;

$diagnostics['code'] = $code_check;

// === OUTPUT RESULTS ===
echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
