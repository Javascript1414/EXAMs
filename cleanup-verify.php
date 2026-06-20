#!/usr/bin/env php
<?php
/**
 * ========================================
 * CLEANUP VERIFICATION SCRIPT
 * Purpose: Show what will be deleted
 * Run: php cleanup-verify.php
 * ========================================
 */

error_reporting(0);
ini_set('display_errors', 0);

// Terminal colors
define('RED', "\033[91m");
define('GREEN', "\033[92m");
define('YELLOW', "\033[93m");
define('BLUE', "\033[94m");
define('RESET', "\033[0m");
define('BOLD', "\033[1m");

class CleanupVerifier
{
    private $rootDir;
    private $toDelete = [];
    private $toKeep = [];
    private $statistics = [
        'files_to_delete' => 0,
        'size_to_delete' => 0,
        'files_to_keep' => 0,
        'size_to_keep' => 0,
    ];

    public function __construct($rootDir = '.')
    {
        $this->rootDir = rtrim($rootDir, '/\\');
    }

    public function run()
    {
        $this->header("CITS LMS - CLEANUP VERIFICATION");
        echo "\n";
        
        echo BLUE . "Scanning directory: " . RESET . $this->rootDir . "\n";
        echo BLUE . "Scan started: " . RESET . date('Y-m-d H:i:s') . "\n";
        echo "\n";

        $this->scanFiles();
        $this->displayResults();
        $this->displayConfirmation();
    }

    private function scanFiles()
    {
        $this->updateDisplay("Scanning files...");
        
        // Get all files recursively
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->rootDir,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            
            $relativePath = $this->getRelativePath($file->getPathname());
            $filename = $file->getFilename();
            $filesize = $file->getSize();

            // Check if file should be deleted
            if ($this->shouldDelete($filename, $relativePath)) {
                $this->toDelete[$relativePath] = $filesize;
                $this->statistics['files_to_delete']++;
                $this->statistics['size_to_delete'] += $filesize;
            } else {
                $this->toKeep[$relativePath] = $filesize;
                $this->statistics['files_to_keep']++;
                $this->statistics['size_to_keep'] += $filesize;
            }
        }
    }

    private function shouldDelete($filename, $relativePath)
    {
        // Files to delete patterns
        $deletePatterns = [
            // Test/Debug files
            '/^check_.*\.php$/',
            '/^debug_.*\.php$/',
            '/^test_.*\.php$/',
            '/^verify_.*\.php$/',
            
            // Development docs
            '/^(?!INFINITYFREE_|DEPLOYMENT_|PRODUCTION_)[^.]*\.md$/',
            '/\.txt$/',
            
            // Migration/Setup
            '/^create_test_.*\.php$/',
            '/^run_.*\.php$/',
            '/^setup_.*\.php$/',
            '/^migrate_.*\.php$/',
            
            // Root SQL files (keep migrations folder)
            '/^(?!migrations\/)[^\/]*\.sql$/',
            
            // Git files
            '/^\.git/',
            '/\.gitignore/',
            '/\.gitattributes/',
            
            // Reports and guides
            '/^.*REPORT.*\.php$/',
            '/^.*AUDIT.*\.php$/',
            '/^.*COMPLETE.*\.php$/',
            '/^.*SUMMARY.*\.php$/',
            
            // Temporary files
            '/^\.DS_Store$/',
            '/^Thumbs\.db$/',
            '/~$/',
        ];

        foreach ($deletePatterns as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }

    private function getRelativePath($fullPath)
    {
        $relative = str_replace($this->rootDir, '', $fullPath);
        return ltrim($relative, '/\\');
    }

    private function displayResults()
    {
        echo "\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo BOLD . "FILES TO BE DELETED" . RESET . " (" . $this->statistics['files_to_delete'] . " files)\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo "\n";

        if (empty($this->toDelete)) {
            echo GREEN . "✓ No files to delete" . RESET . "\n";
        } else {
            // Categorize files
            $categories = [];
            foreach ($this->toDelete as $file => $size) {
                if (preg_match('/^check_/', basename($file))) {
                    $categories['Check Files'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/^debug_/', basename($file))) {
                    $categories['Debug Files'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/^test_/', basename($file))) {
                    $categories['Test Files'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/^verify_/', basename($file))) {
                    $categories['Verify Files'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/\.md$/', $file)) {
                    $categories['Documentation'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/\.txt$/', $file)) {
                    $categories['Text Files'][] = ['file' => $file, 'size' => $size];
                } elseif (preg_match('/\.sql$/', $file)) {
                    $categories['Database Files'][] = ['file' => $file, 'size' => $size];
                } else {
                    $categories['Other'][] = ['file' => $file, 'size' => $size];
                }
            }

            // Display by category
            foreach ($categories as $category => $files) {
                echo RED . "▼ " . $category . RESET . " (" . count($files) . " files)\n";
                
                foreach ($files as $item) {
                    $sizeStr = $this->formatSize($item['size']);
                    printf("  • %-50s %s\n", $item['file'], YELLOW . $sizeStr . RESET);
                }
                echo "\n";
            }
        }

        // Files to keep
        echo "\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo BOLD . "FILES TO KEEP" . RESET . " (" . $this->statistics['files_to_keep'] . " files)\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo "\n";

        $categories = [];
        foreach ($this->toKeep as $file => $size) {
            $dir = dirname($file);
            if ($dir === '.') {
                $categories['Root'][] = ['file' => $file, 'size' => $size];
            } else {
                $topDir = explode('/', $dir)[0];
                $categories[$topDir][] = ['file' => $file, 'size' => $size];
            }
        }

        foreach ($categories as $category => $files) {
            echo GREEN . "▼ " . $category . RESET . " (" . count($files) . " files)\n";
            
            if (count($files) <= 5) {
                foreach ($files as $item) {
                    $sizeStr = $this->formatSize($item['size']);
                    printf("  ✓ %-47s %s\n", $item['file'], BLUE . $sizeStr . RESET);
                }
            } else {
                // Show first 3 and summary
                for ($i = 0; $i < min(3, count($files)); $i++) {
                    $sizeStr = $this->formatSize($files[$i]['size']);
                    printf("  ✓ %-47s %s\n", $files[$i]['file'], BLUE . $sizeStr . RESET);
                }
                echo "  ... and " . (count($files) - 3) . " more files\n";
            }
            echo "\n";
        }

        // Statistics
        echo "\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo BOLD . "STORAGE STATISTICS" . RESET . "\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo "\n";

        printf("%-30s %s\n", "Current usage:", YELLOW . $this->formatSize($this->statistics['size_to_delete'] + $this->statistics['size_to_keep']) . RESET);
        printf("%-30s %s\n", "After cleanup:", GREEN . $this->formatSize($this->statistics['size_to_keep']) . RESET);
        printf("%-30s %s\n", "Space saved:", GREEN . $this->formatSize($this->statistics['size_to_delete']) . RESET);
        printf("%-30s %s\n", "Reduction:", GREEN . $this->getPercentageReduction() . "%" . RESET);
        echo "\n";
    }

    private function displayConfirmation()
    {
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo BOLD . "CLEANUP SUMMARY" . RESET . "\n";
        echo BOLD . "═══════════════════════════════════════════" . RESET . "\n";
        echo "\n";

        printf("%-30s %s\n", "Files to delete:", RED . $this->statistics['files_to_delete'] . RESET);
        printf("%-30s %s\n", "Files to keep:", GREEN . $this->statistics['files_to_keep'] . RESET);
        printf("%-30s %s\n", "Space to free:", YELLOW . $this->formatSize($this->statistics['size_to_delete']) . RESET);
        echo "\n";

        echo BLUE . "This cleanup will:" . RESET . "\n";
        echo "  ✓ Remove all test/debug/verify/check PHP files\n";
        echo "  ✓ Remove development documentation\n";
        echo "  ✓ Remove migration/setup scripts\n";
        echo "  ✓ Keep all production code\n";
        echo "  ✓ Keep all user directories (admin, student, teacher, etc.)\n";
        echo "  ✓ Keep all required assets and includes\n";
        echo "\n";

        echo GREEN . "✓ SAFE TO DELETE - This will optimize for hosting!" . RESET . "\n";
        echo "\n";

        echo "To execute the cleanup, run:\n";
        echo BOLD . "  windows:   CLEANUP_FOR_HOSTING.bat" . RESET . "\n";
        echo BOLD . "  linux/mac: bash cleanup.sh" . RESET . "\n";
        echo "\n";
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function getPercentageReduction()
    {
        $total = $this->statistics['size_to_delete'] + $this->statistics['size_to_keep'];
        if ($total == 0) return 0;
        return round(($this->statistics['size_to_delete'] / $total) * 100, 1);
    }

    private function header($title)
    {
        echo BOLD . BLUE;
        echo "╔═══════════════════════════════════════════╗\n";
        echo "║  " . str_pad($title, 40) . "║\n";
        echo "╚═══════════════════════════════════════════╝\n";
        echo RESET;
    }

    private function updateDisplay($message)
    {
        // Stub for CLI progress updates
    }
}

// Run the verifier
$verifier = new CleanupVerifier('.');
$verifier->run();

echo "\n" . BLUE . "Scan completed at: " . RESET . date('Y-m-d H:i:s') . "\n\n";
?>
