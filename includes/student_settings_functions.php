<?php
/**
 * Student Settings Functions
 * Handles all student settings, preferences, and activity logging
 * 
 * Functions included:
 * - Notification Settings (get, update)
 * - Student Preferences (get, update)
 * - Login History (get, paginate, export)
 * - Activity Logs (log activity, get history)
 * - Data Export (request, generate, download)
 * - Account Deletion (request, manage, complete)
 */

require_once __DIR__ . '/db.php';

// =====================================================================
// NOTIFICATION SETTINGS FUNCTIONS
// =====================================================================

/**
 * Get notification settings for a student
 * @param int $student_id
 * @return array|false
 */
function getNotificationSettings($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM student_notification_settings 
            WHERE student_id = ?
        ");
        $stmt->execute([$student_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found, create default settings
        if (!$result) {
            createDefaultNotificationSettings($student_id);
            return getNotificationSettings($student_id);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error getting notification settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Create default notification settings for a new student
 * @param int $student_id
 * @return bool
 */
function createDefaultNotificationSettings($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_notification_settings 
            (student_id, exam_reminder, result_notification, system_notification, email_notifications, sms_notifications)
            VALUES (?, TRUE, TRUE, TRUE, TRUE, FALSE)
        ");
        return $stmt->execute([$student_id]);
    } catch (Exception $e) {
        error_log("Error creating notification settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Update notification settings
 * @param int $student_id
 * @param array $settings
 * @return array ['success' => bool, 'message' => string]
 */
function updateNotificationSettings($student_id, $settings) {
    global $pdo;
    try {
        $update_fields = [];
        $values = [];
        
        // Whitelist allowed settings
        $allowed = ['exam_reminder', 'result_notification', 'system_notification', 'email_notifications', 'sms_notifications'];
        
        foreach ($allowed as $field) {
            if (isset($settings[$field])) {
                $update_fields[] = "$field = ?";
                $values[] = (bool)$settings[$field] ? 1 : 0;
            }
        }
        
        if (empty($update_fields)) {
            return ['success' => false, 'message' => 'No settings to update'];
        }
        
        $values[] = $student_id;
        
        $stmt = $pdo->prepare("
            UPDATE student_notification_settings 
            SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ?
        ");
        
        if ($stmt->execute($values)) {
            return ['success' => true, 'message' => 'Notification settings updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update settings'];
    } catch (Exception $e) {
        error_log("Error updating notification settings: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

// =====================================================================
// PREFERENCES FUNCTIONS
// =====================================================================

/**
 * Get student preferences
 * @param int $student_id
 * @return array|false
 */
function getStudentPreferences($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM student_preferences 
            WHERE student_id = ?
        ");
        $stmt->execute([$student_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found, create default preferences
        if (!$result) {
            createDefaultPreferences($student_id);
            return getStudentPreferences($student_id);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error getting preferences: " . $e->getMessage());
        return false;
    }
}

/**
 * Create default preferences for a new student
 * @param int $student_id
 * @return bool
 */
function createDefaultPreferences($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO student_preferences 
            (student_id, theme, dashboard_view, language, timezone, items_per_page)
            VALUES (?, 'light', 'grid', 'en', 'Asia/Kolkata', 10)
        ");
        return $stmt->execute([$student_id]);
    } catch (Exception $e) {
        error_log("Error creating preferences: " . $e->getMessage());
        return false;
    }
}

/**
 * Update student preferences
 * @param int $student_id
 * @param array $preferences
 * @return array ['success' => bool, 'message' => string]
 */
function updateStudentPreferences($student_id, $preferences) {
    global $pdo;
    try {
        $update_fields = [];
        $values = [];
        
        // Whitelist allowed preferences
        $allowed = ['theme', 'dashboard_view', 'language', 'timezone', 'items_per_page'];
        
        foreach ($allowed as $field) {
            if (isset($preferences[$field])) {
                $update_fields[] = "$field = ?";
                $values[] = $preferences[$field];
            }
        }
        
        if (empty($update_fields)) {
            return ['success' => false, 'message' => 'No preferences to update'];
        }
        
        $values[] = $student_id;
        
        $stmt = $pdo->prepare("
            UPDATE student_preferences 
            SET " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ?
        ");
        
        if ($stmt->execute($values)) {
            return ['success' => true, 'message' => 'Preferences updated successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to update preferences'];
    } catch (Exception $e) {
        error_log("Error updating preferences: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

// =====================================================================
// LOGIN HISTORY FUNCTIONS
// =====================================================================

/**
 * Get paginated login history for a student
 * @param int $student_id
 * @param int $page
 * @param int $per_page
 * @param array $filters
 * @return array ['data' => array, 'total' => int, 'pages' => int]
 */
function getLoginHistoryPaginated($student_id, $page = 1, $per_page = 10, $filters = []) {
    global $pdo;
    try {
        $offset = ($page - 1) * $per_page;
        $where_clauses = ['user_id = ?'];
        $params = [$student_id];
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $where_clauses[] = 'login_time >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where_clauses[] = 'login_time <= ?';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        if (!empty($filters['browser'])) {
            $where_clauses[] = 'browser LIKE ?';
            $params[] = '%' . $filters['browser'] . '%';
        }
        if (!empty($filters['device'])) {
            $where_clauses[] = 'device LIKE ?';
            $params[] = '%' . $filters['device'] . '%';
        }
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = ?';
            $params[] = $filters['status'];
        }
        
        $where = implode(' AND ', $where_clauses);
        
        // Get total count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs WHERE $where");
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Get paginated data
        $stmt = $pdo->prepare("
            SELECT id, user_id, ip_address, browser, device, status, login_time, logout_time
            FROM login_logs 
            WHERE $where
            ORDER BY login_time DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $per_page;
        $params[] = $offset;
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pages = ceil($total / $per_page);
        
        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    } catch (Exception $e) {
        error_log("Error getting login history: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
    }
}

/**
 * Get current session/last active login
 * @param int $student_id
 * @return array|false
 */
function getCurrentSession($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM login_logs 
            WHERE user_id = ? AND logout_time IS NULL
            ORDER BY login_time DESC
            LIMIT 1
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current session: " . $e->getMessage());
        return false;
    }
}

// =====================================================================
// ACTIVITY LOGGING FUNCTIONS
// =====================================================================

/**
 * Log student activity
 * @param int $student_id
 * @param string $activity_type
 * @param string|null $description
 * @param string|null $entity_type
 * @param int|null $entity_id
 * @param array|null $metadata
 * @return bool
 */
function logStudentActivity($student_id, $activity_type, $description = null, $entity_type = null, $entity_id = null, $metadata = null) {
    global $pdo;
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $metadata_json = $metadata ? json_encode($metadata) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO student_activity_logs 
            (student_id, activity_type, description, related_entity_type, related_entity_id, ip_address, user_agent, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $student_id,
            $activity_type,
            $description,
            $entity_type,
            $entity_id,
            $ip_address,
            $user_agent,
            $metadata_json
        ]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get activity history with pagination
 * @param int $student_id
 * @param int $page
 * @param int $per_page
 * @param array $filters
 * @return array ['data' => array, 'total' => int, 'pages' => int]
 */
function getActivityHistoryPaginated($student_id, $page = 1, $per_page = 10, $filters = []) {
    global $pdo;
    try {
        $offset = ($page - 1) * $per_page;
        $where_clauses = ['student_id = ?'];
        $params = [$student_id];
        
        if (!empty($filters['activity_type'])) {
            $where_clauses[] = 'activity_type = ?';
            $params[] = $filters['activity_type'];
        }
        if (!empty($filters['start_date'])) {
            $where_clauses[] = 'created_at >= ?';
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where_clauses[] = 'created_at <= ?';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        $where = implode(' AND ', $where_clauses);
        
        // Get total count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM student_activity_logs WHERE $where");
        $count_stmt->execute($params);
        $total = $count_stmt->fetchColumn();
        
        // Get paginated data
        $stmt = $pdo->prepare("
            SELECT id, activity_type, description, related_entity_type, related_entity_id, created_at
            FROM student_activity_logs 
            WHERE $where
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $per_page;
        $params[] = $offset;
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $pages = ceil($total / $per_page);
        
        return [
            'data' => $data,
            'total' => $total,
            'pages' => $pages,
            'current_page' => $page
        ];
    } catch (Exception $e) {
        error_log("Error getting activity history: " . $e->getMessage());
        return ['data' => [], 'total' => 0, 'pages' => 0, 'current_page' => 1];
    }
}

// =====================================================================
// ACCOUNT DELETION FUNCTIONS
// =====================================================================

/**
 * Request account deletion
 * @param int $student_id
 * @param string $reason
 * @param string|null $feedback
 * @return array ['success' => bool, 'message' => string, 'request_id' => int]
 */
function requestAccountDeletion($student_id, $reason, $feedback = null) {
    global $pdo;
    try {
        // Check if there's already a pending request
        $check_stmt = $pdo->prepare("
            SELECT id FROM account_deletion_requests 
            WHERE student_id = ? AND status IN ('pending', 'approved')
        ");
        $check_stmt->execute([$student_id]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'You already have a pending deletion request'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO account_deletion_requests 
            (student_id, reason, feedback, status)
            VALUES (?, ?, ?, 'pending')
        ");
        
        if ($stmt->execute([$student_id, $reason, $feedback])) {
            $request_id = $pdo->lastInsertId();
            
            // Log activity
            logStudentActivity($student_id, 'profile_updated', 'Account deletion requested', 'deletion_request', $request_id);
            
            return [
                'success' => true,
                'message' => 'Account deletion request submitted successfully',
                'request_id' => $request_id
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to submit deletion request'];
    } catch (Exception $e) {
        error_log("Error requesting account deletion: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Get deletion request status
 * @param int $student_id
 * @return array|false
 */
function getDeletionRequestStatus($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM account_deletion_requests 
            WHERE student_id = ?
            ORDER BY requested_at DESC
            LIMIT 1
        ");
        $stmt->execute([$student_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting deletion request: " . $e->getMessage());
        return false;
    }
}

/**
 * Cancel deletion request
 * @param int $student_id
 * @return array ['success' => bool, 'message' => string]
 */
function cancelDeletionRequest($student_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            UPDATE account_deletion_requests 
            SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP
            WHERE student_id = ? AND status = 'pending'
        ");
        
        if ($stmt->execute([$student_id])) {
            logStudentActivity($student_id, 'profile_updated', 'Account deletion request cancelled');
            return ['success' => true, 'message' => 'Deletion request cancelled'];
        }
        
        return ['success' => false, 'message' => 'No pending request to cancel'];
    } catch (Exception $e) {
        error_log("Error cancelling deletion request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

// =====================================================================
// DATA EXPORT FUNCTIONS
// =====================================================================

/**
 * Request data export
 * @param int $student_id
 * @param string $export_type
 * @return array ['success' => bool, 'message' => string, 'request_id' => int]
 */
function requestDataExport($student_id, $export_type = 'full') {
    global $pdo;
    try {
        // Check for duplicate pending requests
        $check_stmt = $pdo->prepare("
            SELECT id FROM data_export_requests 
            WHERE student_id = ? AND status IN ('pending', 'processing')
        ");
        $check_stmt->execute([$student_id]);
        if ($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'You already have a pending export request'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO data_export_requests 
            (student_id, export_type, status, expires_at)
            VALUES (?, ?, 'pending', DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 7 DAY))
        ");
        
        if ($stmt->execute([$student_id, $export_type])) {
            $request_id = $pdo->lastInsertId();
            logStudentActivity($student_id, 'material_downloaded', 'Data export requested', 'export_request', $request_id);
            
            return [
                'success' => true,
                'message' => 'Data export request submitted. You will receive an email when ready.',
                'request_id' => $request_id
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to submit export request'];
    } catch (Exception $e) {
        error_log("Error requesting data export: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error'];
    }
}

/**
 * Get data export requests
 * @param int $student_id
 * @param int $limit
 * @return array
 */
function getDataExportRequests($student_id, $limit = 5) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id, export_type, status, file_size, expires_at, requested_at, completed_at
            FROM data_export_requests 
            WHERE student_id = ?
            ORDER BY requested_at DESC
            LIMIT ?
        ");
        $stmt->execute([$student_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting export requests: " . $e->getMessage());
        return [];
    }
}

// =====================================================================
// SECURITY HELPER FUNCTIONS
// =====================================================================

/**
 * Check if user has access to student settings
 * @param int $user_id
 * @param int $target_student_id
 * @return bool
 */
function canAccessStudentSettings($user_id, $target_student_id) {
    global $pdo;
    try {
        // Can access own settings
        if ($user_id == $target_student_id) {
            return true;
        }
        
        // Admin/superadmin can access any student's settings
        $stmt = $pdo->prepare("
            SELECT role_id FROM users WHERE id = ? AND role_id IN (1, 2)
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch() ? true : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Export login history to CSV
 * @param int $student_id
 * @param array $filters
 * @return string CSV content
 */
function exportLoginHistoryToCSV($student_id, $filters = []) {
    global $pdo;
    try {
        $history = getLoginHistoryPaginated($student_id, 1, 1000, $filters);
        
        $csv = "Login Time,Logout Time,IP Address,Browser,Device,Status\n";
        
        foreach ($history['data'] as $entry) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $entry['login_time'],
                $entry['logout_time'] ?? 'Still Active',
                $entry['ip_address'],
                $entry['browser'] ?? 'Unknown',
                $entry['device'] ?? 'Unknown',
                $entry['status']
            );
        }
        
        return $csv;
    } catch (Exception $e) {
        error_log("Error exporting login history: " . $e->getMessage());
        return false;
    }
}

/**
 * Export activity logs to CSV
 * @param int $student_id
 * @param array $filters
 * @return string CSV content
 */
function exportActivityToCSV($student_id, $filters = []) {
    global $pdo;
    try {
        $activity = getActivityHistoryPaginated($student_id, 1, 1000, $filters);
        
        $csv = "Activity Type,Description,Date/Time\n";
        
        foreach ($activity['data'] as $entry) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\"\n",
                $entry['activity_type'],
                $entry['description'] ?? '',
                $entry['created_at']
            );
        }
        
        return $csv;
    } catch (Exception $e) {
        error_log("Error exporting activity: " . $e->getMessage());
        return false;
    }
}
