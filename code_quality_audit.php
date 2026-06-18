#!/usr/bin/env php
<?php
/**
 * CODE QUALITY AUDIT & ISSUE FINDER
 * Comprehensive scan of all PHP files for errors, warnings, and security issues
 */

$root_dir = 'c:\xampp\htdocs\EXAMs';
$issues = [];

// Scan all PHP files
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root_dir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$php_files = [];
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $php_files[] = $file->getRealPath();
    }
}

echo "📁 SCANNING " . count($php_files) . " PHP FILES...\n\n";

// Issues to check
$critical_issues = [];
$high_issues = [];
$medium_issues = [];
$low_issues = [];

// Common patterns to check
$patterns = [
    'undefined_conn' => '/\$conn\s*[=\->]/',
    'direct_sql' => '/mysql_query|mysqli_query|\$_GET\s*\[/i',
    'xss_vulnerabilities' => '/echo\s+\$_[A-Z]+|echo\s+\$_SERVER/',
    'session_mismatch' => '/\$_SESSION\s*\[\s*[\'"]role[\'"]\s*\]/',
    'undefined_pdo' => '/\$pdo\s*->\s*query/',
    'hardcoded_credentials' => '/password|api_key|secret/',
];

foreach ($php_files as $file) {
    if (strpos($file, 'vendor') !== false || strpos($file, '.git') !== false) {
        continue;
    }
    
    $content = file_get_contents($file);
    $lines = file($file);
    $relative_path = str_replace($root_dir, '', $file);
    
    // Check for syntax errors
    $output = [];
    $return_var = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
    
    if ($return_var !== 0) {
        $critical_issues[] = [
            'file' => $relative_path,
            'type' => 'SYNTAX ERROR',
            'severity' => 'CRITICAL',
            'message' => implode("\n", $output),
            'line' => 0
        ];
    }
    
    // Check for undefined variables and functions
    if (preg_match_all('/\$conn\s*[=\->]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        $line_num = substr_count($content, "\n", 0, $matches[0][0][1]) + 1;
        if (!preg_match('/\$pdo/', $content)) {
            $critical_issues[] = [
                'file' => $relative_path,
                'type' => 'UNDEFINED VARIABLE',
                'severity' => 'CRITICAL',
                'message' => 'Using $conn instead of $pdo for database connection',
                'line' => $line_num,
                'code' => trim($lines[$line_num - 1] ?? '')
            ];
        }
    }
    
    // Check for direct SQL injection vulnerabilities
    if (preg_match_all('/\$\w+\s*\.\s*"SELECT|INSERT|UPDATE|DELETE|".*\$_GET|".*\$_POST/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $line_num = substr_count($content, "\n", 0, $match[1]) + 1;
            $high_issues[] = [
                'file' => $relative_path,
                'type' => 'SQL INJECTION RISK',
                'severity' => 'HIGH',
                'message' => 'Possible SQL injection vulnerability - use prepared statements',
                'line' => $line_num,
                'code' => trim($lines[$line_num - 1] ?? '')
            ];
        }
    }
    
    // Check for XSS vulnerabilities
    if (preg_match_all('/echo\s+\$_(?:GET|POST|REQUEST|SERVER|COOKIE)\s*\[/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $line_num = substr_count($content, "\n", 0, $match[1]) + 1;
            $high_issues[] = [
                'file' => $relative_path,
                'type' => 'XSS VULNERABILITY',
                'severity' => 'HIGH',
                'message' => 'Direct output of user input without escaping - use htmlspecialchars()',
                'line' => $line_num,
                'code' => trim($lines[$line_num - 1] ?? '')
            ];
        }
    }
    
    // Check for session variable mismatches
    if (preg_match_all('/\$_SESSION\s*\[\s*[\'"]role[\'"]\s*\]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $line_num = substr_count($content, "\n", 0, $match[1]) + 1;
            $medium_issues[] = [
                'file' => $relative_path,
                'type' => 'SESSION VARIABLE MISMATCH',
                'severity' => 'MEDIUM',
                'message' => 'Should use $_SESSION[\'role_name\'] not $_SESSION[\'role\']',
                'line' => $line_num,
                'code' => trim($lines[$line_num - 1] ?? '')
            ];
        }
    }
    
    // Check for hardcoded credentials
    if (preg_match_all('/(password|api_key|secret|token)\s*=\s*[\'"][^\'\"]{8,}[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $line_num = substr_count($content, "\n", 0, $match[1]) + 1;
            if (strpos($relative_path, 'config') === false) { // config.php is expected to have credentials
                $high_issues[] = [
                    'file' => $relative_path,
                    'type' => 'HARDCODED CREDENTIALS',
                    'severity' => 'HIGH',
                    'message' => 'Hardcoded credentials found - should use environment variables',
                    'line' => $line_num
                ];
            }
        }
    }
}

// Output report
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "CRITICAL ISSUES: " . count($critical_issues) . "\n";
echo "HIGH ISSUES: " . count($high_issues) . "\n";
echo "MEDIUM ISSUES: " . count($medium_issues) . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (count($critical_issues) > 0) {
    echo "🔴 CRITICAL ISSUES (MUST FIX):\n";
    foreach (array_slice($critical_issues, 0, 5) as $issue) {
        echo "\n  File: " . $issue['file'] . "\n";
        echo "  Type: " . $issue['type'] . "\n";
        echo "  Line: " . $issue['line'] . "\n";
        echo "  Message: " . $issue['message'] . "\n";
        if (isset($issue['code'])) {
            echo "  Code: " . $issue['code'] . "\n";
        }
    }
}

echo "\n\n✅ SCANNING COMPLETE\n";
echo "Total files scanned: " . count($php_files) . "\n";
echo "Issues found: " . (count($critical_issues) + count($high_issues) + count($medium_issues)) . "\n";

?>
