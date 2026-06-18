<?php
/**
 * Direct Create Notes Table
 */

require_once __DIR__ . '/includes/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS `notes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trade_id` INT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`trade_id`) REFERENCES `trades`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_trade_id` (`trade_id`),
    INDEX `idx_subject_id` (`subject_id`),
    INDEX `idx_uploaded_by` (`uploaded_by`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    $status = "✓ <strong>Success!</strong> Notes table created successfully.";
    $color = "green";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $status = "✓ <strong>Info:</strong> Notes table already exists.";
        $color = "blue";
    } else {
        $status = "✗ <strong>Error:</strong> " . htmlspecialchars($e->getMessage());
        $color = "red";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Notes Table</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .status-box.green {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .status-box.blue {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .status-box.red {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin-top: 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }
        .details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #666;
        }
        .details strong { color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 Create Notes Table</h1>
        
        <div class="status-box <?php echo $color; ?>">
            <?php echo $status; ?>
        </div>
        
        <div class="details">
            <strong>✓ Table Details:</strong><br>
            • Primary Key: id (BIGINT AUTO_INCREMENT)<br>
            • Foreign Keys: trades, subjects, users<br>
            • Status Field: active/inactive<br>
            • Indexes: trade, subject, uploader, status
        </div>
        
        <a href="/admin/notes.php" class="btn btn-primary">
            → Go to Admin Notes
        </a>
    </div>
</body>
</html>
