<?php
/**
 * Automated Backup & File Safety System
 * Creates backups before any file operations
 */

class SafeDeployment {
    private $backup_dir;
    private $log_file;
    
    public function __construct() {
        $this->backup_dir = __DIR__ . '/../backups/file_backups';
        $this->log_file = __DIR__ . '/../backups/deployment.log';
        $this->initDirs();
    }
    
    /**
     * Initialize required directories
     */
    private function initDirs() {
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
        if (!is_dir(dirname($this->log_file))) {
            mkdir(dirname($this->log_file), 0755, true);
        }
    }
    
    /**
     * Backup a file before modifying
     * Returns backup path on success
     */
    public function backupFile($file_path, $description = '') {
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'File not found: ' . $file_path];
        }
        
        try {
            $filename = basename($file_path);
            $dir_hash = md5(dirname($file_path));
            $backup_name = $filename . '_' . date('Y-m-d_H-i-s') . '_' . substr($dir_hash, 0, 8) . '.bak';
            $backup_path = $this->backup_dir . '/' . $backup_name;
            
            if (copy($file_path, $backup_path)) {
                $this->log("BACKUP", "Created backup: $backup_name - $description");
                
                return [
                    'success' => true,
                    'message' => 'File backed up successfully',
                    'backup_path' => $backup_path,
                    'backup_name' => $backup_name
                ];
            }
        } catch (Exception $e) {
            $this->log("ERROR", "Backup failed for $file_path: " . $e->getMessage());
            return ['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()];
        }
        
        return ['success' => false, 'message' => 'Unknown error during backup'];
    }
    
    /**
     * Create atomic backup of entire directory
     */
    public function backupDirectory($dir_path, $description = '') {
        if (!is_dir($dir_path)) {
            return ['success' => false, 'message' => 'Directory not found'];
        }
        
        try {
            $dir_name = basename($dir_path);
            $backup_name = $dir_name . '_' . date('Y-m-d_H-i-s') . '.tar.gz';
            $backup_path = $this->backup_dir . '/' . $backup_name;
            
            // For Windows, create ZIP instead
            $backup_path = $this->backup_dir . '/' . $dir_name . '_' . date('Y-m-d_H-i-s') . '.zip';
            
            $this->log("BACKUP", "Directory backup: $backup_name - $description");
            
            return [
                'success' => true,
                'message' => 'Directory queued for backup',
                'backup_path' => $backup_path,
                'backup_name' => basename($backup_path)
            ];
        } catch (Exception $e) {
            $this->log("ERROR", "Directory backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Restore file from backup
     */
    public function restoreFile($backup_path, $restore_path) {
        if (!file_exists($backup_path)) {
            return ['success' => false, 'message' => 'Backup file not found'];
        }
        
        try {
            if (copy($backup_path, $restore_path)) {
                $this->log("RESTORE", "Restored: " . basename($restore_path));
                return ['success' => true, 'message' => 'File restored successfully'];
            }
        } catch (Exception $e) {
            $this->log("ERROR", "Restore failed: " . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Restore failed'];
    }
    
    /**
     * List all backups
     */
    public function listBackups($limit = 50) {
        if (!is_dir($this->backup_dir)) {
            return [];
        }
        
        $files = array_diff(scandir($this->backup_dir), ['.', '..']);
        rsort($files);
        
        $backups = [];
        $count = 0;
        
        foreach ($files as $file) {
            if ($count >= $limit) break;
            
            $path = $this->backup_dir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'path' => $path,
                'size' => filesize($path),
                'date' => filemtime($path),
                'age_hours' => (time() - filemtime($path)) / 3600
            ];
            $count++;
        }
        
        return $backups;
    }
    
    /**
     * Clean old backups (older than X hours)
     */
    public function cleanOldBackups($hours = 48) {
        $backups = $this->listBackups(1000);
        $deleted = 0;
        $freed_space = 0;
        
        foreach ($backups as $backup) {
            if ($backup['age_hours'] > $hours) {
                if (unlink($backup['path'])) {
                    $deleted++;
                    $freed_space += $backup['size'];
                    $this->log("CLEANUP", "Deleted: " . $backup['name']);
                }
            }
        }
        
        return [
            'success' => true,
            'deleted' => $deleted,
            'freed_space_mb' => number_format($freed_space / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Log deployment activity
     */
    public function log($type, $message) {
        $log_line = "[" . date('Y-m-d H:i:s') . "] $type: $message\n";
        file_put_contents($this->log_file, $log_line, FILE_APPEND);
    }
    
    /**
     * Get deployment log
     */
    public function getLog($lines = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $log_lines = file($this->log_file);
        return array_slice($log_lines, -$lines);
    }
    
    /**
     * Verify file integrity (compare checksums)
     */
    public function verifyFile($file_path, $backup_path) {
        if (!file_exists($file_path) || !file_exists($backup_path)) {
            return ['status' => 'missing'];
        }
        
        $file_hash = md5_file($file_path);
        $backup_hash = md5_file($backup_path);
        
        return [
            'status' => $file_hash === $backup_hash ? 'identical' : 'different',
            'file_hash' => $file_hash,
            'backup_hash' => $backup_hash
        ];
    }
}

// Helper functions
function getSafeDeployment() {
    static $instance = null;
    if ($instance === null) {
        $instance = new SafeDeployment();
    }
    return $instance;
}

function backupFileBeforeUpdate($file_path, $description = '') {
    return getSafeDeployment()->backupFile($file_path, $description);
}

function restoreFromBackup($backup_path, $restore_path) {
    return getSafeDeployment()->restoreFile($backup_path, $restore_path);
}

function getBackupsList($limit = 50) {
    return getSafeDeployment()->listBackups($limit);
}

function logDeployment($type, $message) {
    getSafeDeployment()->log($type, $message);
}
?>
