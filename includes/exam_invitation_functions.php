<?php
/**
 * Practical Exam Invitation/Link Generation Functions
 */

/**
 * Create exam invitations table if it doesn't exist
 */
function initExamInvitationsTable() {
    global $pdo;
    
    try {
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
        
        $pdo->exec($sql);
        return ['success' => true, 'message' => 'Table initialized'];
    } catch (Exception $e) {
        // Table may already exist, that's fine
        return ['success' => true];
    }
}

/**
 * Generate a shareable invitation link for a practical exam
 * Returns: ['success' => bool, 'code' => string, 'url' => string, 'message' => string]
 */
function generateExamInvitation($practical_exam_id, $created_by, $expires_days = 30, $max_uses = null) {
    global $pdo;
    
    // Ensure table exists
    initExamInvitationsTable();
    
    try {
        // Check if exam exists
        $exam_check = $pdo->prepare("SELECT id FROM practical_exams WHERE id = ?");
        $exam_check->execute([$practical_exam_id]);
        
        if (!$exam_check->fetch()) {
            return ['success' => false, 'message' => 'Practical exam not found'];
        }
        
        // Generate unique code
        $code = bin2hex(random_bytes(16));
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));
        $invitation_url = (defined('BASE_URL') ? BASE_URL : 'http://localhost/EXAMs') . "/invite/practical_exam.php?code=" . $code;
        
        // Insert invitation
        $stmt = $pdo->prepare("
            INSERT INTO practical_exam_invitations 
            (practical_exam_id, invitation_code, invitation_url, created_by, expires_at, max_uses, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $practical_exam_id,
            $code,
            $invitation_url,
            $created_by,
            $expires_at,
            $max_uses
        ]);
        
        return [
            'success' => true,
            'code' => $code,
            'url' => $invitation_url,
            'id' => $pdo->lastInsertId(),
            'message' => 'Invitation link generated successfully'
        ];
    } catch (Exception $e) {
        error_log("Generate Invitation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get all invitations for a practical exam
 */
function getExamInvitations($practical_exam_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM practical_exam_invitations
            WHERE practical_exam_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$practical_exam_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Revoke/deactivate an invitation
 */
function revokeExamInvitation($invitation_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE practical_exam_invitations 
            SET status = 'inactive'
            WHERE id = ?
        ");
        
        $stmt->execute([$invitation_id]);
        
        return ['success' => true, 'message' => 'Invitation revoked'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get practical exam details from invitation code
 */
function getPracticalExamByInvitation($code) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT pe.id, pe.title, pe.subject_id, pe.trade_id, pe.theory_marks, 
                   pe.practical_marks, pe.submission_deadline, pei.invitation_url, pei.expires_at
            FROM practical_exam_invitations pei
            JOIN practical_exams pe ON pei.practical_exam_id = pe.id
            WHERE pei.invitation_code = ? AND pei.status = 'active'
            AND (pei.expires_at IS NULL OR pei.expires_at > NOW())
            AND (pei.max_uses IS NULL OR pei.used_count < pei.max_uses)
            LIMIT 1
        ");
        
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Increment use count
            $use_stmt = $pdo->prepare("
                UPDATE practical_exam_invitations 
                SET used_count = used_count + 1
                WHERE invitation_code = ?
            ");
            $use_stmt->execute([$code]);
        }
        
        return $result;
    } catch (Exception $e) {
        return null;
    }
}
?>
