<?php
/**
 * PreviewManager - Centralized Preview System Management
 * Provides unified utilities for all preview systems across the application
 * 
 * FEATURES:
 * - Standardized timeout configuration
 * - Error handling and user-friendly messages
 * - Loading state management
 * - Memory/resource cleanup
 * - Accessibility helpers
 * - Logging and debugging
 */

class PreviewManager {
    
    // Configuration constants
    const TIMEOUT_SHORT = 10000;  // 10s for quick previews (images, small files)
    const TIMEOUT_MEDIUM = 30000; // 30s for PDFs and videos
    const TIMEOUT_LONG = 60000;   // 60s for large files (XLSX, etc.)
    
    const CLEANUP_INTERVAL = 3600; // 1 hour - clean up temp files older than this
    
    // Error messages for different scenarios
    private static $errorMessages = [
        'timeout_short' => 'Preview is taking too long. The file might be too large. Try downloading instead.',
        'timeout_medium' => 'PDF is taking too long to load (>30s). The file might be very large or the server is slow. Try downloading instead.',
        'timeout_long' => 'Import is taking too long to process. The file might be very large. Try splitting it into smaller files.',
        'file_not_found' => 'The file could not be found. It may have been deleted or moved.',
        'file_access_denied' => 'You don\'t have permission to access this file.',
        'file_corrupted' => 'The file appears to be corrupted or in an unsupported format.',
        'network_error' => 'Network error while loading preview. Please check your connection and try again.',
        'browser_error' => 'Your browser couldn\'t display the preview. Try downloading the file instead.',
        'unsupported_format' => 'This file format is not supported for preview.',
    ];
    
    /**
     * Get user-friendly error message
     */
    public static function getErrorMessage($type = 'file_not_found') {
        return self::$errorMessages[$type] ?? self::$errorMessages['file_not_found'];
    }
    
    /**
     * Clean up temporary files older than specified interval
     */
    public static function cleanupTempFiles($directory, $maxAgeSeconds = null) {
        if ($maxAgeSeconds === null) {
            $maxAgeSeconds = self::CLEANUP_INTERVAL;
        }
        
        if (!is_dir($directory)) {
            return ['cleaned' => 0, 'failed' => 0];
        }
        
        $stats = ['cleaned' => 0, 'failed' => 0];
        $cutoffTime = time() - $maxAgeSeconds;
        
        try {
            foreach (glob($directory . '*') as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    if (@unlink($file)) {
                        $stats['cleaned']++;
                    } else {
                        $stats['failed']++;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("PreviewManager: Cleanup error in {$directory}: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Validate file exists and is readable
     */
    public static function validateFile($filePath, $allowedExtensions = null) {
        $errors = [];
        
        if (empty($filePath)) {
            $errors[] = 'file_path_empty';
        } elseif (!file_exists($filePath)) {
            $errors[] = 'file_not_found';
        } elseif (!is_readable($filePath)) {
            $errors[] = 'file_access_denied';
        } else {
            // Validate extension if specified
            if ($allowedExtensions !== null) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                if (!in_array($ext, (array)$allowedExtensions)) {
                    $errors[] = 'unsupported_format';
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_exists' => file_exists($filePath),
            'file_readable' => file_exists($filePath) && is_readable($filePath),
            'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
            'file_type' => file_exists($filePath) ? mime_content_type($filePath) : null,
        ];
    }
    
    /**
     * Generate HTML for loading spinner with timeout display
     */
    public static function getLoadingSpinner($message = 'Loading...', $showTimer = true) {
        $timerHtml = $showTimer ? ' (<span id="loadingTimer">0</span>s)' : '';
        return <<<HTML
        <div class="preview-loading-state" style="display: flex; align-items: center; gap: 12px; padding: 20px;">
            <div class="spinner-border spinner-border-sm" role="status" style="width: 24px; height: 24px; color: #667eea;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div>
                <strong>{$message}{$timerHtml}</strong>
                <div style="font-size: 12px; color: #999; margin-top: 4px;">
                    This may take a few moments...
                </div>
            </div>
        </div>
        HTML;
    }
    
    /**
     * Generate HTML for error alert with suggestions
     */
    public static function getErrorAlert($message, $downloadUrl = null, $detailedError = null) {
        $downloadButton = '';
        if ($downloadUrl) {
            $downloadButton = <<<HTML
            <div style="margin-top: 15px;">
                <a href="{$downloadUrl}" class="btn btn-sm btn-primary" download>
                    <i data-lucide="download" style="width: 14px; height: 14px; display: inline;"></i> 
                    Download File Instead
                </a>
            </div>
            HTML;
        }
        
        $errorDetails = '';
        if ($detailedError) {
            $errorDetails = <<<HTML
            <details style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 12px;">
                <summary style="cursor: pointer; font-weight: 500;">Error Details</summary>
                <pre style="margin: 8px 0 0 0; overflow-x: auto; background: #fff; padding: 6px; border-radius: 3px; font-size: 11px;">
{$detailedError}
                </pre>
            </details>
            HTML;
        }
        
        return <<<HTML
        <div class="alert alert-danger mb-0" role="alert">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong>Preview Unavailable</strong>
                    <p style="margin: 8px 0 0 0; font-size: 14px;">
                        {$message}
                    </p>
                    {$errorDetails}
                    {$downloadButton}
                </div>
            </div>
        </div>
        HTML;
    }
    
    /**
     * Get safe file size formatting
     */
    public static function formatFileSize($bytes) {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = abs($bytes);
        $pow = floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Log preview event for debugging and analytics
     */
    public static function logPreviewEvent($eventType, $fileType, $fileSize = 0, $duration = 0, $status = 'success', $error = null) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'duration_ms' => $duration,
            'status' => $status,
            'error' => $error,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100),
        ];
        
        // Log to error log for debugging
        if ($status === 'error') {
            error_log('PREVIEW_ERROR: ' . json_encode($logEntry));
        }
        
        // Could also store in database for analytics
        // $pdo->prepare("INSERT INTO preview_logs (data) VALUES (?)")->execute([json_encode($logEntry)]);
    }
    
    /**
     * Generate ARIA attributes for accessibility
     */
    public static function getAriaAttributes($type = 'status') {
        $attributes = [
            'status' => 'role="status" aria-live="polite" aria-atomic="true"',
            'alert' => 'role="alert" aria-live="assertive"',
            'dialog' => 'role="dialog" aria-modal="true"',
        ];
        return $attributes[$type] ?? $attributes['status'];
    }
}

// Utility function for easy access
function cleanupPreviewTempFiles($directory) {
    return PreviewManager::cleanupTempFiles($directory);
}
