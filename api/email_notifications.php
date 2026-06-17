<?php
/**
 * Email Notification Management API
 * Allows administrators to resend emails, view logs, and manage notifications
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/notification_emails.php';

// Require admin login
requireLogin();
if (!hasRole('superadmin') && !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

header('Content-Type: application/json');

$action = sanitizeInput($_GET['action'] ?? $_POST['action'] ?? '');

/**
 * Action: Get Email Notification Logs
 */
if ($action === 'get_logs') {
    try {
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Build query
        $query = "SELECT * FROM email_notifications WHERE 1=1";
        $params = [];

        if ($user_id) {
            $query .= " AND user_id = ?";
            $params[] = $user_id;
        }

        if ($type && in_array($type, ['registration', 'approval', 'rejection', 'reset_password', 'otp'])) {
            $query .= " AND notification_type = ?";
            $params[] = $type;
        }

        // Total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM email_notifications WHERE 1=1" . (count($params) > 0 ? str_replace('SELECT *', '', str_repeat(' AND 1=1', count($params))) : ''));
        
        // Fetch logs
        $query .= " ORDER BY sent_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $logs,
            'count' => count($logs)
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * Action: Resend Approval Email
 */
if ($action === 'resend_approval') {
    try {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            throw new Exception('Invalid user ID');
        }

        $result = resendApprovalEmail($user_id);

        echo json_encode($result);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

/**
 * Action: Resend Registration Email
 */
if ($action === 'resend_registration') {
    try {
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            throw new Exception('Invalid user ID');
        }

        $user = getUserDetails($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }

        // Note: We cannot resend the actual password as it's hashed
        // Send a password reset email instead
        $email_sent = sendApprovalNotificationEmail(
            $user['email'],
            $user['full_name'],
            $user['id']
        );

        if ($email_sent) {
            echo json_encode(['success' => true, 'message' => 'Email resent successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email']);
        }
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

/**
 * Action: Get Email Statistics
 */
if ($action === 'get_stats') {
    try {
        // Total emails sent
        $totalSent = $pdo->query("SELECT COUNT(*) as count FROM email_notifications WHERE status = 'sent'")->fetch()['count'];
        
        // Total emails failed
        $totalFailed = $pdo->query("SELECT COUNT(*) as count FROM email_notifications WHERE status = 'failed'")->fetch()['count'];
        
        // By type
        $byType = $pdo->query("
            SELECT notification_type, COUNT(*) as count, 
                   SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM email_notifications 
            GROUP BY notification_type
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Today's emails
        $todayEmails = $pdo->query("
            SELECT COUNT(*) as count,
                   SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM email_notifications 
            WHERE DATE(sent_at) = CURDATE()
        ")->fetch();

        echo json_encode([
            'success' => true,
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'by_type' => $byType,
            'today' => $todayEmails
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * Action: Clear Email Logs (older than X days)
 */
if ($action === 'clear_logs' && hasRole('superadmin')) {
    try {
        $days = (int)($_POST['days'] ?? 30);

        if ($days < 7) {
            throw new Exception('Cannot delete logs less than 7 days old');
        }

        $result = $pdo->prepare("DELETE FROM email_notifications WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $result->execute([$days]);

        echo json_encode([
            'success' => true,
            'message' => 'Logs cleared successfully',
            'rows_deleted' => $result->rowCount()
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Default response
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;

?>
