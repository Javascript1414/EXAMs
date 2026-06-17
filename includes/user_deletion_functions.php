<?php
/**
 * User Deletion Archive Helper Functions
 */

/**
 * Ensure deleted_users_archive table exists
 */
function ensureDeletedUsersArchiveTableExists() {
    global $pdo;
    
    try {
        $pdo->query("SELECT 1 FROM deleted_users_archive LIMIT 1");
    } catch (Exception $e) {
        // Table doesn't exist, create it
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `deleted_users_archive` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `original_user_id` BIGINT UNSIGNED NOT NULL,
                `full_name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `role_name` VARCHAR(100) NOT NULL,
                `trade_name` VARCHAR(255) NULL,
                `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL,
                `account_status` ENUM('active', 'inactive', 'suspended') NOT NULL,
                `registration_date` TIMESTAMP NULL,
                `last_login` TIMESTAMP NULL,
                `deleted_by_admin_id` BIGINT UNSIGNED NOT NULL,
                `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `deletion_reason` TEXT NULL,
                `original_user_data` JSON NOT NULL,
                `restored_at` TIMESTAMP NULL,
                `restored_by_admin_id` BIGINT UNSIGNED NULL,
                FOREIGN KEY (`deleted_by_admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`restored_by_admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                INDEX `idx_original_user_id` (`original_user_id`),
                INDEX `idx_email` (`email`),
                INDEX `idx_deleted_at` (`deleted_at`),
                INDEX `idx_restored_at` (`restored_at`),
                INDEX `idx_role_name` (`role_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}

/**
 * Archive a user before permanent deletion
 * Only for superadmin use
 * Uses transaction for data safety
 */
function archiveUserBeforeDeletion($user_id, $deletion_reason = '', $admin_id = null) {
    global $pdo;
    
    // Ensure table exists
    ensureDeletedUsersArchiveTableExists();
    
    if (!$admin_id) {
        $admin_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$admin_id) {
        throw new Exception("Admin ID is required");
    }
    
    // Prevent self-deletion
    if ($user_id === $admin_id) {
        throw new Exception("You cannot delete your own account");
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get complete user record
        $stmt = $pdo->prepare("
            SELECT u.*, r.name as role_name, t.trade_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN trades t ON u.trade_id = t.id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Check if user is superadmin trying to be deleted
        if ($user['role_name'] === 'superadmin') {
            throw new Exception("Cannot delete superadmin accounts");
        }
        
        // Archive the user
        $stmt = $pdo->prepare("
            INSERT INTO deleted_users_archive 
            (original_user_id, full_name, email, phone, role_name, trade_name, 
             approval_status, account_status, registration_date, last_login, 
             deleted_by_admin_id, deletion_reason, original_user_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user['id'],
            $user['full_name'],
            $user['email'],
            $user['phone'],
            $user['role_name'],
            $user['trade_name'],
            $user['approval_status'] ?? 'pending',
            $user['status'],
            $user['created_at'],
            $user['last_login'],
            $admin_id,
            $deletion_reason,
            json_encode($user) // Store complete user record
        ]);
        
        // Delete the user from active table
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        
        // Log the deletion action
        error_log("User Deletion: User ID $user_id deleted by Admin ID $admin_id. Reason: $deletion_reason");
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Restore a deleted user from archive
 * Only for superadmin use
 * Uses transaction for data safety
 */
function restoreUserFromArchive($archive_id, $admin_id = null) {
    global $pdo;
    
    // Ensure table exists
    ensureDeletedUsersArchiveTableExists();
    
    if (!$admin_id) {
        $admin_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$admin_id) {
        throw new Exception("Admin ID is required");
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get archived user record
        $stmt = $pdo->prepare("
            SELECT * FROM deleted_users_archive WHERE id = ? AND restored_at IS NULL
        ");
        $stmt->execute([$archive_id]);
        $archive = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$archive) {
            throw new Exception("Archived user record not found or already restored");
        }
        
        // Decode original user data
        $original_data = json_decode($archive['original_user_data'], true);
        
        if (!$original_data) {
            throw new Exception("Cannot restore: Invalid user data");
        }
        
        // Check if user already exists with same email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR id = ?");
        $check->execute([$archive['email'], $archive['original_user_id']]);
        if ($check->rowCount() > 0) {
            throw new Exception("User already exists in active database");
        }
        
        // Restore user to active table
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (id, full_name, email, phone, password, trade_id, profile_photo, 
             gender, date_of_birth, address, batch, institute_name, enrollment_no,
             role_id, status, email_verified, last_login, created_at, updated_at,
             approval_status, approved_by, approved_at, rejection_reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $original_data['id'],
            $original_data['full_name'],
            $original_data['email'],
            $original_data['phone'],
            $original_data['password'],
            $original_data['trade_id'],
            $original_data['profile_photo'] ?? null,
            $original_data['gender'] ?? null,
            $original_data['date_of_birth'] ?? null,
            $original_data['address'] ?? null,
            $original_data['batch'] ?? null,
            $original_data['institute_name'] ?? null,
            $original_data['enrollment_no'] ?? null,
            $original_data['role_id'],
            $original_data['status'],
            $original_data['email_verified'] ?? false,
            $original_data['last_login'] ?? null,
            $original_data['created_at'],
            $original_data['updated_at'],
            $original_data['approval_status'] ?? 'pending',
            $original_data['approved_by'] ?? null,
            $original_data['approved_at'] ?? null,
            $original_data['rejection_reason'] ?? null
        ]);
        
        // Update archive record with restoration info
        $pdo->prepare("UPDATE deleted_users_archive SET restored_at = NOW(), restored_by_admin_id = ? WHERE id = ?")
            ->execute([$admin_id, $archive_id]);
        
        // Log the restoration
        error_log("User Restoration: User ID {$archive['original_user_id']} restored from archive by Admin ID $admin_id");
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Ensure deleted_users_archive table exists
 */

/**
 * Get paginated deleted users
 */
function getDeletedUsersPaginated($page = 1, $per_page = 10, $filters = []) {
    global $pdo;
    
    // Ensure table exists
    ensureDeletedUsersArchiveTableExists();
    
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $per_page;
    
    $query = "SELECT d.*, a.full_name as deleted_by_admin_name FROM deleted_users_archive d 
              LEFT JOIN users a ON d.deleted_by_admin_id = a.id WHERE restored_at IS NULL";
    
    $params = [];
    
    // Filter by role
    if (!empty($filters['role_name'])) {
        $query .= " AND d.role_name = ?";
        $params[] = $filters['role_name'];
    }
    
    // Filter by date range
    if (!empty($filters['from_date'])) {
        $query .= " AND DATE(d.deleted_at) >= ?";
        $params[] = $filters['from_date'];
    }
    
    if (!empty($filters['to_date'])) {
        $query .= " AND DATE(d.deleted_at) <= ?";
        $params[] = $filters['to_date'];
    }
    
    // Search by name or email
    if (!empty($filters['search'])) {
        $query .= " AND (d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Count total records
    $countQuery = "SELECT COUNT(*) FROM deleted_users_archive d WHERE restored_at IS NULL";
    $countParams = [];
    
    if (!empty($filters['role_name'])) {
        $countQuery .= " AND d.role_name = ?";
        $countParams[] = $filters['role_name'];
    }
    if (!empty($filters['from_date'])) {
        $countQuery .= " AND DATE(d.deleted_at) >= ?";
        $countParams[] = $filters['from_date'];
    }
    if (!empty($filters['to_date'])) {
        $countQuery .= " AND DATE(d.deleted_at) <= ?";
        $countParams[] = $filters['to_date'];
    }
    if (!empty($filters['search'])) {
        $countQuery .= " AND (d.full_name LIKE ? OR d.email LIKE ? OR d.phone LIKE ?)";
        $search_term = "%{$filters['search']}%";
        $countParams[] = $search_term;
        $countParams[] = $search_term;
        $countParams[] = $search_term;
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Get paginated data
    $query .= " ORDER BY d.deleted_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($query);
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'records' => $records,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page
    ];
}

/**
 * Get paginated users for admin list
 */
function getUsersPaginated($page = 1, $per_page = 10, $search = '') {
    global $pdo;
    
    $page = max(1, (int)$page);
    $offset = ($page - 1) * $per_page;
    
    $base_query = "SELECT u.id, u.full_name, u.email, u.phone, u.status, u.approval_status, 
                          u.created_at, r.name as role_name, t.trade_name 
                   FROM users u 
                   JOIN roles r ON u.role_id = r.id 
                   LEFT JOIN trades t ON u.trade_id = t.id";
    
    $params = [];
    $where_clause = "";
    
    if (!empty($search)) {
        $where_clause = " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
        $search_term = "%$search%";
        $params = [$search_term, $search_term, $search_term];
    }
    
    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u" . $where_clause);
    $countStmt->execute($params);
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    
    // Get paginated data
    $query = $base_query . $where_clause . " ORDER BY u.approval_status = 'pending' DESC, u.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($query);
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'records' => $records,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page
    ];
}
?>
