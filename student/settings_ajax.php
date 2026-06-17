<?php
/**
 * Student Settings AJAX Handler
 * Processes all asynchronous requests from settings pages
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_settings_functions.php';

// Check if logged in
if (!isLoggedIn() || !hasRole('student')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        // =====================================================================
        // NOTIFICATION SETTINGS
        // =====================================================================
        case 'update_notification':
            $settings = [];
            
            // Check which notification settings are being updated
            if (isset($_POST['exam_reminder'])) {
                $settings['exam_reminder'] = $_POST['exam_reminder'];
            }
            if (isset($_POST['result_notification'])) {
                $settings['result_notification'] = $_POST['result_notification'];
            }
            if (isset($_POST['system_notification'])) {
                $settings['system_notification'] = $_POST['system_notification'];
            }
            if (isset($_POST['email_notifications'])) {
                $settings['email_notifications'] = $_POST['email_notifications'];
            }
            if (isset($_POST['sms_notifications'])) {
                $settings['sms_notifications'] = $_POST['sms_notifications'];
            }
            
            $response = updateNotificationSettings($user_id, $settings);
            break;

        // =====================================================================
        // PREFERENCES
        // =====================================================================
        case 'update_preference':
            $preferences = [];
            
            if (isset($_POST['theme'])) {
                $preferences['theme'] = $_POST['theme'];
            }
            if (isset($_POST['dashboard_view'])) {
                $preferences['dashboard_view'] = $_POST['dashboard_view'];
            }
            if (isset($_POST['language'])) {
                $preferences['language'] = $_POST['language'];
            }
            if (isset($_POST['timezone'])) {
                $preferences['timezone'] = $_POST['timezone'];
            }
            
            $response = updateStudentPreferences($user_id, $preferences);
            break;

        // =====================================================================
        // DATA EXPORT
        // =====================================================================
        case 'request_export':
            $export_type = $_POST['type'] ?? 'full';
            
            // Validate export type
            $allowed_types = ['full', 'profile', 'activity', 'results', 'certificates', 'materials'];
            if (!in_array($export_type, $allowed_types)) {
                $response = ['success' => false, 'message' => 'Invalid export type'];
                break;
            }
            
            $response = requestDataExport($user_id, $export_type);
            break;

        // =====================================================================
        // ACCOUNT DELETION
        // =====================================================================
        case 'request_deletion':
            $reason = $_POST['reason'] ?? '';
            $feedback = $_POST['feedback'] ?? null;
            
            if (empty($reason)) {
                $response = ['success' => false, 'message' => 'Please provide a reason'];
                break;
            }
            
            // Validate reason length
            if (strlen($reason) < 10) {
                $response = ['success' => false, 'message' => 'Please provide a detailed reason (at least 10 characters)'];
                break;
            }
            
            if (strlen($reason) > 500) {
                $response = ['success' => false, 'message' => 'Reason is too long (max 500 characters)'];
                break;
            }
            
            $response = requestAccountDeletion($user_id, $reason, $feedback);
            break;

        case 'cancel_deletion':
            $response = cancelDeletionRequest($user_id);
            break;

        // =====================================================================
        // FILE DOWNLOADS
        // =====================================================================
        case 'download_export':
            // This is a file download, not JSON
            $export_id = $_GET['id'] ?? null;
            
            if (!$export_id) {
                http_response_code(400);
                echo 'Invalid request';
                exit;
            }
            
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT file_path, file_size, export_type 
                FROM data_export_requests 
                WHERE id = ? AND student_id = ? AND status = 'completed'
            ");
            $stmt->execute([$export_id, $user_id]);
            $export = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$export || !file_exists($export['file_path'])) {
                http_response_code(404);
                echo 'File not found';
                exit;
            }
            
            // Update download count
            $update_stmt = $pdo->prepare("
                UPDATE data_export_requests 
                SET download_count = download_count + 1 
                WHERE id = ?
            ");
            $update_stmt->execute([$export_id]);
            
            // Send file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="student_data_' . date('Y-m-d') . '.zip"');
            header('Content-Length: ' . $export['file_size']);
            readfile($export['file_path']);
            exit;

        // =====================================================================
        // LOGIN HISTORY EXPORT
        // =====================================================================
        case 'export_login_history':
            $csv = exportLoginHistoryToCSV($user_id);
            
            if (!$csv) {
                $response = ['success' => false, 'message' => 'Failed to export'];
                break;
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="login_history_' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;

        // =====================================================================
        // ACTIVITY LOG EXPORT
        // =====================================================================
        case 'export_activity':
            $csv = exportActivityToCSV($user_id);
            
            if (!$csv) {
                $response = ['success' => false, 'message' => 'Failed to export'];
                break;
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;

        // =====================================================================
        // CHANGE PASSWORD
        // =====================================================================
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate inputs
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $response = ['success' => false, 'message' => 'All fields are required'];
                break;
            }
            
            if ($new_password !== $confirm_password) {
                $response = ['success' => false, 'message' => 'New passwords do not match'];
                break;
            }
            
            if (strlen($new_password) < 8) {
                $response = ['success' => false, 'message' => 'Password must be at least 8 characters'];
                break;
            }
            
            if ($current_password === $new_password) {
                $response = ['success' => false, 'message' => 'New password must be different from current password'];
                break;
            }
            
            // Get current user password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                $response = ['success' => false, 'message' => 'Current password is incorrect'];
                break;
            }
            
            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?, password_last_changed = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([$new_password_hash, $user_id])) {
                logStudentActivity($user_id, 'password_changed', 'Password changed successfully');
                $response = ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update password'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    error_log("Settings AJAX Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
