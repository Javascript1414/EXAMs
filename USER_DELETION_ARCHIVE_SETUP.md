# User Deletion Archive System - Implementation Guide

## ✅ System Overview

A complete user deletion archive system has been implemented with the following features:

### Core Features
1. **Permanent Deletion with Archive**: Users can only be deleted by superadmin with automatic archiving
2. **Complete Data Preservation**: All user data stored as JSON for recovery
3. **Restoration**: Superadmin can restore archived users back to active database
4. **Pagination**: 10 users per page with navigation controls
5. **Search & Filter**: Search by name/email/phone, filter by role and date range
6. **Audit Trail**: Track who deleted the account and when
7. **Security**: Transaction-based operations, self-delete prevention

---

## 🚀 Installation Steps

### Step 1: Run the Migration
Execute the migration to create the `deleted_users_archive` table:

```bash
http://localhost/EXAMs/phase_15_user_deletion_archive.php
```

**Expected Output:**
```
✅ Phase 15 Migration Completed Successfully!
✓ Created deleted_users_archive table for user deletion archiving
✓ Archives store complete user records before permanent deletion
✓ Supports restoration by superadmin
```

### Step 2: Verify Installation
The system is ready when:
- ✅ Migration completes successfully
- ✅ `deleted_users_archive` table exists in database
- ✅ "Deleted Users Archive" link appears in sidebar (superadmin only)
- ✅ Users page shows "Delete" button for superadmin

---

## 📋 Database Schema

### deleted_users_archive Table
```sql
CREATE TABLE `deleted_users_archive` (
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
)
```

---

## 👥 User Roles & Permissions

| Action | Admin | Superadmin | Notes |
|--------|-------|-----------|-------|
| View Users | ✅ | ✅ | |
| Block/Unblock | ✅ | ✅ | |
| Edit Role | ✅ | ✅ | |
| Approve/Reject | ✅ | ✅ | |
| Delete User | ❌ | ✅ | Creates archive entry |
| View Archive | ❌ | ✅ | |
| Restore User | ❌ | ✅ | From archive only |
| Delete Self | ❌ | ❌ | Prevented for all roles |

---

## 🎯 Usage Guide

### Admin User Management (admin/users.php)

**Features:**
- ✅ Search by name, email, or phone
- ✅ Pagination with 10 users per page
- ✅ Approve/Reject pending users
- ✅ Block/Unblock users
- ✅ Edit user role
- ✅ Delete user (superadmin only)

**How to Delete a User:**
1. Navigate to "Manage Users" → admin/users.php
2. Find the user (use search if needed)
3. Click "Delete" button (only visible for superadmin)
4. Enter deletion reason
5. Confirm the permanent deletion dialog
6. User is archived and removed from active database

**Pagination:**
- First, Previous, [1] [2] [3], Next, Last buttons
- Maintains search term across pages
- Shows record count: "Showing X to Y of Z users"

---

### Deleted Users Archive (admin/deleted_users_archive.php)

**Access:** Superadmin only - Sidebar → "Deleted Users Archive"

**Features:**
- ✅ View all deleted users
- ✅ Search by name, email, or phone
- ✅ Filter by role dropdown
- ✅ Filter by date range (from_date, to_date)
- ✅ View complete user details
- ✅ Restore deleted users

**How to Restore a User:**
1. Navigate to "Deleted Users Archive"
2. Find the user using search/filters
3. Click "View" to see complete details (optional)
4. Click "Restore" button
5. Confirm restoration dialog
6. User is recreated in active database

**Filter Options:**
- **Search**: By name, email, or phone number
- **Role**: Filter by user role (admin, student, moderator, etc.)
- **Date Range**: From date and To date for deletion period
- **Status**: Shows deleted account status (active, suspended, inactive)

**Pagination:**
- Same as Users page: First, Previous, [1] [2] [3], Next, Last
- Maintains all filter parameters across pages

---

## 🔒 Security Features

### Data Protection
- **Transactions**: All delete/restore operations use database transactions
- **CSRF Protection**: All forms include CSRF token verification
- **SQL Injection Prevention**: Prepared statements used everywhere
- **Self-Delete Prevention**: Superadmin cannot delete their own account

### Audit Trail
Every deletion logs:
- User ID deleted
- Admin ID who performed deletion
- Deletion reason
- Timestamp
- Complete user snapshot as JSON

### Role-Based Access
- Regular admins: Cannot delete or access archive
- Superadmin: Full delete and restore capabilities
- Others: No access to user management

---

## 🧪 Testing Checklist

### Test 1: Migration & Database
- [ ] Run phase_15_user_deletion_archive.php
- [ ] Verify table created: `SELECT * FROM deleted_users_archive LIMIT 1;`
- [ ] Check all columns exist

### Test 2: Pagination
- [ ] Add 15+ test users if needed
- [ ] Navigate to admin/users.php
- [ ] Verify first page shows 10 users
- [ ] Click "Next" - see users 11-20
- [ ] Click page numbers - navigate directly
- [ ] Click "First" and "Last" - work correctly

### Test 3: Search with Pagination
- [ ] Search for a user name
- [ ] Results appear on page 1
- [ ] Pagination updates based on search results
- [ ] Clear search - all users appear again

### Test 4: User Deletion (Superadmin)
- [ ] As superadmin, go to Manage Users
- [ ] Click "Delete" on any non-superadmin user
- [ ] Enter deletion reason
- [ ] Confirm permanent deletion warning
- [ ] User disappears from list
- [ ] Check in database: User removed from users table

### Test 5: Archive Verification
- [ ] Go to "Deleted Users Archive"
- [ ] Verify deleted user appears in archive
- [ ] Check all details stored (name, email, role, etc.)
- [ ] Verify deletion_by_admin_id and deleted_at
- [ ] Verify original_user_data contains JSON

### Test 6: Restore User
- [ ] In Deleted Users Archive, click "View"
- [ ] Check modal shows complete user details
- [ ] Click "Restore" in modal
- [ ] Confirm restoration
- [ ] Verify user restored to active database
- [ ] Check in Manage Users - user appears again
- [ ] Verify restored_at and restored_by_admin_id in archive

### Test 7: Filter & Search in Archive
- [ ] Filter by role - shows only users of that role
- [ ] Search by name - finds archived users
- [ ] Date range filter - shows deletions in date range
- [ ] Combine filters - all work together
- [ ] Clear filters - all users appear

### Test 8: Permissions
- [ ] As admin: Delete button NOT visible
- [ ] As admin: Cannot access deleted_users_archive.php directly
- [ ] As superadmin: Can access both pages
- [ ] Try to delete superadmin - error message
- [ ] Try to delete yourself - error message

### Test 9: Edge Cases
- [ ] Delete user with special characters in name - works
- [ ] Long deletion reason - stored correctly
- [ ] Restore twice - prevents duplicate creation
- [ ] Restore with existing email - shows error
- [ ] Rapid delete/restore - transaction safety

### Test 10: Data Integrity
- [ ] Verify JSON data matches original user record
- [ ] Check timestamps (created_at vs deleted_at)
- [ ] Verify foreign keys work correctly
- [ ] Restored user has identical data as before

---

## 📚 Code Files Reference

### 1. phase_15_user_deletion_archive.php
**Purpose**: Migration runner to create database table
**Functions**:
- Creates `deleted_users_archive` table
- Adds foreign keys and indexes

### 2. includes/user_deletion_functions.php
**Purpose**: Core deletion/restoration logic
**Functions**:
- `archiveUserBeforeDeletion()` - Archive and delete user
- `restoreUserFromArchive()` - Restore user from archive
- `getDeletedUsersPaginated()` - Fetch paginated archive data
- `getUsersPaginated()` - Fetch paginated active users

### 3. admin/users.php (Modified)
**Changes**:
- Added `require_once user_deletion_functions.php`
- Added delete action handler
- Replaced query with `getUsersPaginated()`
- Added pagination controls (First, Previous, [1][2][3], Next, Last)
- Added results counter
- Added Delete button for superadmin
- Added `deleteUser()` JavaScript function

### 4. admin/deleted_users_archive.php (New)
**Purpose**: Archive management interface
**Features**:
- View deleted users with pagination
- Search and multi-filter
- View details modal
- Restore functionality
- Audit information display

### 5. includes/sidebar.php (Modified)
**Changes**:
- Added "Deleted Users Archive" link (superadmin only)
- Positioned after "Manage Users"

---

## 🐛 Troubleshooting

### Issue: "Table already exists" during migration
**Solution**: This is normal. The table was already created. The migration is idempotent.

### Issue: Delete button not showing
**Solution**: 
- Verify you're logged in as superadmin
- Check user role in database
- Clear browser cache

### Issue: Restored user still in archive
**Solution**: 
- Restored users show with `restored_at` timestamp
- They're not deleted again - they're restored
- Check `restored_by_admin_id` to confirm restoration

### Issue: Cannot restore - "User already exists"
**Solution**: 
- User was restored previously
- Current user has same email in active database
- Manually check database for duplicates

### Issue: Pagination not working
**Solution**:
- Check page number in URL: `?page=1`
- Verify search is preserved: `?page=2&search=...`
- Clear browser cache

---

## 📊 Monitoring & Logs

### Error Logs
All errors are logged to:
```
error_log("User Deletion: User ID $user_id deleted by Admin ID $admin_id. Reason: $deletion_reason");
error_log("User Restoration: User ID $original_user_id restored from archive by Admin ID $admin_id");
```

Check PHP error log for:
- Deletion attempts
- Restoration operations
- Permission violations

### Database Audit
Query archive activity:
```sql
-- See all deletions
SELECT * FROM deleted_users_archive WHERE restored_at IS NULL;

-- See all restorations
SELECT * FROM deleted_users_archive WHERE restored_at IS NOT NULL;

-- Who deleted the most users
SELECT u.full_name, COUNT(*) as deletions 
FROM deleted_users_archive d 
JOIN users u ON d.deleted_by_admin_id = u.id 
GROUP BY d.deleted_by_admin_id 
ORDER BY deletions DESC;

-- Deletions per day
SELECT DATE(deleted_at) as deletion_date, COUNT(*) as count 
FROM deleted_users_archive 
GROUP BY DATE(deleted_at) 
ORDER BY deleted_at DESC;
```

---

## 🎓 Best Practices

1. **Always Review Before Deleting**
   - Check user details before confirmation
   - Ensure correct user selected

2. **Document Deletion Reason**
   - Provide meaningful reason for audit trail
   - Helps with compliance and investigations

3. **Regular Backups**
   - Backup database regularly
   - Archive data is backup but restore is better

4. **Monitor Deletions**
   - Review deletion logs periodically
   - Check for unusual patterns

5. **Test Restoration**
   - Verify restored users work correctly
   - Check all user data is intact

---

## 📞 Support

For issues or questions:
1. Check troubleshooting section above
2. Review test checklist
3. Check database logs
4. Verify user permissions
5. Check browser console for JavaScript errors

---

**Last Updated**: 2026-06-17
**Version**: 1.0
**Status**: Production Ready ✅
