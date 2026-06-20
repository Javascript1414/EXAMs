<?php
require 'config.php';
require 'includes/db.php';

echo "Setting up exam invitations...\n";

// Create table
$sql = "CREATE TABLE IF NOT EXISTS `practical_exam_invitations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `practical_exam_id` INT NOT NULL,
    `invitation_code` VARCHAR(64) UNIQUE NOT NULL,
    `invitation_url` VARCHAR(255),
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME,
    `max_uses` INT DEFAULT NULL,
    `used_count` INT DEFAULT 0,
    `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    echo "✓ Exam invitations table created!\n";
} catch (Exception $e) {
    echo "Note: Table may already exist. Error: " . $e->getMessage() . "\n";
}

// Function to generate invitation link
function generateExamInvitation($practical_exam_id, $created_by, $expires_days = 30) {
    global $pdo;
    
    // Generate unique code
    $code = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));
    $invitation_url = BASE_URL . "/student/practical_exams.php?invite=" . $code;
    
    $stmt = $pdo->prepare("
        INSERT INTO practical_exam_invitations 
        (practical_exam_id, invitation_code, invitation_url, created_by, expires_at, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([$practical_exam_id, $code, $invitation_url, $created_by]);
    
    return [
        'success' => true,
        'code' => $code,
        'url' => $invitation_url,
        'id' => $pdo->lastInsertId()
    ];
}

// Add to functions.php or practical_exam_functions.php
echo "✓ Setup complete! You can now generate invitation links for practical exams.\n";
?>
