# Production Deployment System Guide

## Overview

This is a **safe, zero-downtime deployment system** that allows you to update files, add features, and test changes without disrupting users.

---

## ✨ Key Features

✅ **Maintenance Mode** - Show users a professional message during updates  
✅ **Automatic Backups** - Every file is backed up before changes  
✅ **Safe Rollback** - Restore any file to its previous state  
✅ **Admin Access** - Admins can test during maintenance  
✅ **Deployment Log** - Track all changes  
✅ **Zero Downtime** - Users don't lose data  
✅ **Easy to Use** - One-click toggle  

---

## 📋 Deployment Workflow

### Step 1: Enable Maintenance Mode
1. Login as Admin
2. Go to Menu → **Maintenance Mode** (or `/admin/maintenance_control.php`)
3. Toggle **Enable Maintenance Mode** to ON
4. Update the message: e.g., "Adding new exam features"
5. Set estimated time: e.g., "5-10 minutes"
6. Click **Update Settings**

**Result:** Users see maintenance page, admins can still access

### Step 2: Users See Maintenance Page
- All non-admin users get professional maintenance page
- Shows:
  - Maintenance message
  - Estimated time
  - Last updated
  - Contact support link

### Step 3: Deploy Changes
- Update files
- Add new features
- Modify database
- Deploy new pages
- **Files are automatically backed up**

### Step 4: Test as Admin
- Login as admin
- Admins bypass maintenance page
- Test all new features
- Verify everything works
- Fix any issues

### Step 5: Disable Maintenance Mode
1. Go to **Maintenance Mode** page
2. Toggle **Enable Maintenance Mode** to OFF
3. Click **Update Settings**

**Result:** System goes live, users can access again ✓

---

## 📁 Directory Structure

```
c:\xampp\htdocs\EXAMs\
├── config/
│   └── maintenance.php          ← Configuration (toggle here)
├── includes/
│   ├── maintenance_middleware.php    ← Checks maintenance mode
│   ├── safe_deployment.php           ← Backup system
│   └── header.php               ← Now includes maintenance check
├── admin/
│   └── maintenance_control.php   ← Admin control panel
├── backups/
│   ├── config_backups/          ← Config file backups
│   ├── file_backups/            ← File backups
│   └── deployment.log           ← Deployment log
└── public/
    └── maintenance.html         ← (Generated) Maintenance page
```

---

## 🔧 Configuration Files

### `/config/maintenance.php`
Controls maintenance mode settings:

```php
return [
    'maintenance_mode' => false,              // Toggle: true = maintenance, false = live
    'maintenance_message' => 'System Maintenance',
    'maintenance_details' => 'Updating with new features...',
    'maintenance_estimated_time' => '5-10 minutes',
    'show_admin_panel' => true,              // Admins can still access
    'allowed_ips' => ['127.0.0.1', 'localhost'],
    'last_maintenance' => null,
    'next_scheduled_maintenance' => null,
];
```

### Quick Toggle via Command Line

```bash
# Enable maintenance (edit config/maintenance.php)
# Change: 'maintenance_mode' => false,
# To: 'maintenance_mode' => true,

# Then disable when done:
# Change: 'maintenance_mode' => true,
# To: 'maintenance_mode' => false,
```

---

## 💾 Backup System

### Automatic Backups
- Every file update is automatically backed up
- Backups stored in `/backups/file_backups/`
- Naming: `filename_YYYY-MM-DD_HH-MM-SS_hash.bak`
- Configuration backups in `/backups/config_backups/`

### Manual Backup
```php
<?php
require_once 'includes/safe_deployment.php';

// Backup a single file
$result = backupFileBeforeUpdate('/path/to/file.php', 'Before adding new feature');

// List all backups
$backups = getBackupsList(50);

// Clean old backups (older than 48 hours)
getSafeDeployment()->cleanOldBackups(48);
?>
```

### Restore from Backup
```php
<?php
require_once 'includes/safe_deployment.php';

// Restore specific file
$result = restoreFromBackup(
    '/backups/file_backups/filename_2024-06-20_15-30-45.bak',
    '/path/to/file.php'
);

if ($result['success']) {
    echo "File restored!";
}
?>
```

---

## 👨‍💼 Admin Control Panel

Access: `/admin/maintenance_control.php` (Menu → Maintenance Mode)

### Features:
1. **Current Status** - Shows if system is live or in maintenance
2. **Toggle Control** - Switch maintenance on/off
3. **Message Editor** - Customize message to users
4. **Deployment Workflow** - Step-by-step guide
5. **Backup Management** - View and manage backups
6. **Configuration History** - See previous settings

---

## 🚀 Common Deployment Scenarios

### Scenario 1: Add New Feature

```
1. Enable Maintenance Mode
   ↓
2. Add new feature files
   (Backups created automatically)
   ↓
3. Test as admin
   ↓
4. If works: Disable maintenance → LIVE ✓
5. If broken: Restore from backup → Try again
```

### Scenario 2: Fix Bug in Production

```
1. Enable Maintenance Mode
   ↓
2. Fix bug in file (backup created)
   ↓
3. Test fix as admin
   ↓
4. If fixed: Disable maintenance → LIVE ✓
5. If not fixed: Restore old version, debug more
```

### Scenario 3: Database Migration

```
1. Enable Maintenance Mode
   ↓
2. Run migration script
   ↓
3. Test with sample data
   ↓
4. Verify no errors
   ↓
5. Disable maintenance → LIVE ✓
```

### Scenario 4: Scheduled Maintenance

```
1. Set next_scheduled_maintenance in config
   ↓
2. Pre-announce to users
   ↓
3. Enable maintenance at scheduled time
   ↓
4. Do maintenance work
   ↓
5. Disable maintenance when done
```

---

## 🔒 Security Features

✅ **CSRF Protection** - All forms have tokens  
✅ **Role-Based Access** - Admins only  
✅ **IP Allowlist** - Restrict during maintenance  
✅ **Automatic Backups** - Can't lose data  
✅ **Audit Log** - Track who changed what  
✅ **File Integrity** - Verify backups match originals  

---

## 📊 Deployment Log

All deployments logged to `/backups/deployment.log`:

```
[2024-06-20 15:30:45] BACKUP: Created backup: maintenance_2024-06-20_15-30-45.php
[2024-06-20 15:30:50] INFO: Admin enabled maintenance mode
[2024-06-20 15:35:20] UPDATE: New feature added: exam_linking.php
[2024-06-20 15:35:25] BACKUP: File backed up automatically
[2024-06-20 15:40:00] TEST: Admin testing new features
[2024-06-20 15:40:15] INFO: Admin disabled maintenance mode
[2024-06-20 15:40:15] INFO: System now LIVE - Users can access
```

View log in admin panel or SSH:
```bash
tail -100 backups/deployment.log
```

---

## ⚠️ Important Rules

### DO:
✅ Enable maintenance before major updates  
✅ Test thoroughly as admin before going live  
✅ Keep backups for at least 48 hours  
✅ Check deployment log before/after  
✅ Update message to be user-friendly  
✅ Disable maintenance when done  

### DON'T:
❌ Update files without maintenance mode  
❌ Skip testing just to go live faster  
❌ Delete backup files  
❌ Ignore error logs  
❌ Leave maintenance mode on too long  
❌ Update while users are accessing  

---

## 🆘 Troubleshooting

### Maintenance Page Shows But I'm Admin

**Problem:** You're admin but seeing maintenance page

**Solution:**
1. Check `show_admin_panel` in `config/maintenance.php` is `true`
2. Verify you're logged in as admin
3. Clear browser cache
4. Try incognito/private browsing

### Can't Disable Maintenance Mode

**Problem:** Toggle not saving

**Solution:**
1. Check file permissions: `config/maintenance.php` should be writable
2. Try via SSH:
   ```bash
   chmod 644 config/maintenance.php
   ```
3. Or manually edit file in editor

### Backup Not Created

**Problem:** Files updated but no backup

**Solution:**
1. Check `/backups/file_backups/` directory exists
2. Verify permissions: `chmod 755 backups/`
3. Check deployment log for errors
4. Create directory manually:
   ```bash
   mkdir -p backups/file_backups
   chmod 755 backups/file_backups
   ```

### Want to Restore File

**Problem:** Need to undo changes

**Solution:**
1. Go to Admin → Maintenance Mode
2. Scroll to "Configuration Backups"
3. Find backup from before the change
4. Use safe_deployment.php to restore:
   ```php
   restoreFromBackup('/path/to/backup.bak', '/path/to/original/file.php');
   ```

---

## 📞 Support

For issues:
1. Check deployment log: `/backups/deployment.log`
2. Review error logs in PHP
3. Verify file permissions
4. Check maintenance.php syntax
5. Restore from backup if needed

---

## ✅ Checklist Before Going Live

- [ ] Enable maintenance mode
- [ ] All files backed up
- [ ] Tested all new features
- [ ] Database migrations run
- [ ] No errors in logs
- [ ] User message is clear
- [ ] Admins can still access
- [ ] Ready to go live
- [ ] Disable maintenance mode
- [ ] Test user access
- [ ] Monitor for issues
- [ ] Document changes
- [ ] Clean up old backups

---

## 🎯 Best Practices

1. **Small Changes** - Deploy frequently, not all at once
2. **Test First** - Always test as admin before opening
3. **Monitor After** - Watch for errors after going live
4. **Keep Backups** - Don't delete recent backups
5. **Log Everything** - Use the deployment log
6. **User Communication** - Let users know what's happening
7. **Estimate Time** - Be realistic about maintenance duration
8. **Have Plan B** - Know how to rollback if needed

---

**Version:** 1.0  
**Last Updated:** June 2024  
**Status:** Production Ready ✓
