# InfinityFree Deployment Guide - CITS LMS

## 📋 Table of Contents
1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [InfinityFree Account Setup](#infinityfree-account-setup)
3. [File Upload & Configuration](#file-upload--configuration)
4. [Database Setup](#database-setup)
5. [Final Configuration](#final-configuration)
6. [Post-Deployment Testing](#post-deployment-testing)
7. [Troubleshooting](#troubleshooting)

---

## ✅ Pre-Deployment Checklist

### Files to Include (Production Build)
```
✅ Production Files:
- index_infinityfree.php → Upload as index.php
- config_infinityfree.php → Upload as config.php
- .htaccess (new version)
- login.php
- register.php
- student_login.php
- staff_login.php
- forgot_password.php
- reset_password.php
- verify.php
- logout.php
- profile.php

✅ Core Directories:
- admin/ (exclude test files)
- student/ (exclude debug files)
- teacher/ (exclude debug files)
- moderator/
- community/
- api/
- assets/ (CSS, JS, Images)
- includes/ (all .php files)
- uploads/ (empty, or with subdirectories)
- vendor/ (composer dependencies)
- emails/
```

### Files to EXCLUDE (Development/Testing)
```
❌ Remove These:
- config.php (old, will use config_infinityfree.php)
- index.php (old version, use index_infinityfree.php)
- .htaccess (old version, will use new one)

❌ Remove All Debug/Test Files:
- check_*.php
- debug_*.php
- test_*.php
- verify_*.php
- create_test_*.php
- *.md (documentation files)
- *.sql (migration files)
- *.txt (config templates)
- .git/ (repository files)
```

---

## 🚀 InfinityFree Account Setup

### Step 1: Create InfinityFree Account
1. Go to [InfinityFree.net](https://www.infinityfree.net)
2. Sign up for a free or premium account
3. Note your control panel credentials

### Step 2: Create MySQL Database
1. Log in to InfinityFree Control Panel
2. Go to **Databases** → **MySQL**
3. Create new database:
   - **Database Name**: `exams_lms` (or preferred name)
   - InfinityFree will prefix it: `if0_USERID_exams_lms`
4. Create database user:
   - **Username**: `if0_USERID` (auto-generated)
   - **Password**: Create strong password
5. Save credentials:
   ```
   Host: sql123.infinityfree.com (check your panel)
   Database: if0_USERID_exams_lms
   Username: if0_USERID
   Password: [Your Password]
   ```

### Step 3: Create/Configure Domain
1. Go to **Domains** section
2. Add your domain or use InfinityFree subdomain:
   - Subdomain: `yourdomain.infinityfree.com`
   - Or point external domain to InfinityFree nameservers

---

## 📤 File Upload & Configuration

### Step 1: Prepare Local Environment
```bash
# On your local machine:

# 1. Create production build folder
mkdir CITS_LMS_Production
cd CITS_LMS_Production

# 2. Copy all necessary files (see checklist above)
# 3. Delete all debug/test files

# 4. Create .env file with InfinityFree credentials
# (If using .env instead of config)
```

### Step 2: Update Configuration Files

**File: `config_infinityfree.php`**
```php
// Update these with YOUR InfinityFree credentials:

define('DB_HOST', 'sql123.infinityfree.com');      // Get from InfinityFree panel
define('DB_NAME', 'if0_37654321_exams_lms');       // Your database name
define('DB_USER', 'if0_37654321');                 // Your username
define('DB_PASS', 'YOUR_STRONG_PASSWORD');         // Your password

// Update email configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
```

**File: `.htaccess`**
- Already configured for root domain access
- No changes needed unless in subdirectory

### Step 3: Upload Files via FTP

**Using FileZilla (Recommended):**
1. Download & install [FileZilla](https://filezilla-project.org)
2. Connect using InfinityFree FTP credentials:
   - **Host**: ftp.yourdomain.infinityfree.com
   - **Username**: Your InfinityFree username
   - **Password**: Your InfinityFree password
   - **Port**: 21

3. Upload folder structure:
   ```
   /htdocs/
   ├── index.php (from index_infinityfree.php)
   ├── config.php (from config_infinityfree.php)
   ├── .htaccess
   ├── login.php
   ├── register.php
   ├── admin/
   ├── student/
   ├── teacher/
   ├── includes/
   ├── assets/
   ├── uploads/
   ├── vendor/
   └── [other directories]
   ```

4. Set correct permissions:
   - Files: 644
   - Directories: 755
   - uploads/: 777

---

## 🗄️ Database Setup

### Step 1: Import Database Schema

**Option A: Using phpMyAdmin (Recommended)**
1. Open InfinityFree Control Panel
2. Go to **Databases** → **phpMyAdmin**
3. Select your database
4. Go to **Import** tab
5. Upload `database.sql` or `complete_database_setup.sql`
6. Click **Go/Import**

**Option B: Using SSH (If available)**
```bash
mysql -h sql123.infinityfree.com -u if0_37654321 -p if0_37654321_exams_lms < database.sql
# Enter password when prompted
```

### Step 2: Verify Tables Created
In phpMyAdmin:
- Check tables appear: users, exams, questions, trades, subjects, etc.
- Verify all tables have data if included in SQL

### Step 3: Create Admin User
1. In phpMyAdmin, select table: `users`
2. Insert new record:
   ```
   full_name: Administrator
   email: admin@yourdomain.com
   password: [use password_hash('AdminPassword123', PASSWORD_BCRYPT)]
   role_id: 1 (superadmin)
   status: active
   verified: 1
   approved: 1
   ```

---

## ⚙️ Final Configuration

### Step 1: Update Include Paths

All include paths already use `APP_ROOT` constant from config, so they should work automatically.

Verify in key files:
- `includes/header.php` - Check BASE_URL usage
- `includes/sidebar.php` - Check link paths
- `includes/footer.php` - Check asset paths

### Step 2: Create Upload Directories

Via FTP, create these directories with 777 permissions:
```
uploads/
├── certificates/
├── submissions/
├── profiles/
├── materials/
├── notes/
└── [other needed]
```

### Step 3: Set Environment Variables

In `config_infinityfree.php`, ensure:
- `ENVIRONMENT` = 'production'
- `COOKIE_SECURE` = true (once HTTPS enabled)
- Error logging enabled

### Step 4: Enable HTTPS (SSL Certificate)

InfinityFree provides free AutoSSL:
1. Go to Control Panel → **SSL Certificates**
2. Click **Auto SSL** or **Issue SSL Certificate**
3. Wait 5-15 minutes for certificate
4. Access site via `https://yourdomain.com`

---

## 🧪 Post-Deployment Testing

### Test Checklist

✅ **Homepage Access**
```
Visit: https://yourdomain.com
Expected: Home page loads without errors
```

✅ **Database Connection**
```
Check logs or test by logging in
If connection fails: Verify DB credentials in config_infinityfree.php
```

✅ **User Authentication**
```
Try Login:
- Email: admin@yourdomain.com
- Password: AdminPassword123
Expected: Redirect to admin dashboard
```

✅ **Asset Loading**
```
View page source (Right-click → View Page Source)
Check that CSS/JS URLs are correct:
- Should show: https://yourdomain.com/assets/css/...
- NOT: http://localhost/EXAMs/assets/...
```

✅ **File Upload**
```
Test file upload feature
Expected: Files save to uploads/ directory
Check file paths in database
```

✅ **Email Sending**
```
Trigger email (password reset, notification)
Check email delivery
```

### Debug Mode

If issues occur, temporarily enable debug in `config_infinityfree.php`:
```php
define('ENVIRONMENT', 'development');
```
Then check errors displayed on page or in logs.

---

## 🔧 Troubleshooting

### Issue 1: "Access Denied - Admin Only"
**Cause**: Session not set properly or role_name missing
**Solution**:
1. Clear cookies/cache
2. Logout and login again
3. Check `staff_login.php` sets `$_SESSION['role_name']`
4. Verify user role in database

### Issue 2: Blank Page
**Cause**: PHP error, missing includes, or config error
**Solution**:
1. Check error log: Control Panel → Error Log
2. Enable `ENVIRONMENT = 'development'` temporarily
3. Verify config_infinityfree.php paths are correct
4. Check database connection

### Issue 3: Assets Not Loading (CSS/JS Broken)
**Cause**: Incorrect BASE_URL in config
**Solution**:
1. Check config_infinityfree.php: BASE_URL should be `https://yourdomain.com`
2. View page source to verify asset URLs
3. Manually test asset URL in browser
4. Clear browser cache

### Issue 4: Database Connection Failed
**Cause**: Wrong credentials or connection issues
**Solution**:
1. Verify DB credentials in Control Panel
2. Ensure database user has all privileges
3. Test connection in phpMyAdmin
4. Check host name (InfinityFree may use different host per region)
5. Verify firewall allows connection (unlikely with InfinityFree)

### Issue 5: File Upload Fails
**Cause**: Directory permissions or size limit
**Solution**:
1. Verify uploads/ directory is 777
2. Check InfinityFree file size limits
3. Check available disk space in Control Panel
4. Verify upload_tmp_dir in php.ini (Contact InfinityFree if needed)

### Issue 6: Emails Not Sending
**Cause**: Wrong SMTP credentials or email configuration
**Solution**:
1. Verify SMTP_HOST, SMTP_USER, SMTP_PASS correct
2. Try with Gmail: Enable "App Passwords"
3. Try with InfinityFree default email
4. Check email logs in Control Panel
5. Test with simple mail() function first

---

## 📞 Support Resources

- **InfinityFree Help**: https://infinityfree.net/support/
- **phpMyAdmin**: Accessible from Control Panel
- **File Manager**: Alternative to FTP in Control Panel
- **Domain Management**: Manage DNS records and SSL

---

## 🎯 Post-Deployment Tasks

1. ✅ Set strong password for admin account
2. ✅ Enable HTTPS/SSL certificate
3. ✅ Configure email system for notifications
4. ✅ Test all user roles (admin, teacher, student, moderator)
5. ✅ Backup database regularly
6. ✅ Monitor error logs for issues
7. ✅ Update admin contact email
8. ✅ Create terms of service/privacy policy pages
9. ✅ Set up regular backups
10. ✅ Document any customizations made

---

## 📝 File Structure After Deployment

```
/htdocs/ (Public Root - yourdomain.com)
│
├── index.php                 # Main entry point (from index_infinityfree.php)
├── config.php               # Configuration (from config_infinityfree.php)
├── .htaccess                # URL routing & security
│
├── login.php                # Public pages
├── register.php
├── staff_login.php
├── forgot_password.php
├── reset_password.php
├── profile.php
├── logout.php
│
├── admin/                   # Admin panel
│   ├── index.php
│   ├── practical_exams.php
│   └── [other admin pages]
│
├── student/                 # Student area
│   ├── index.php
│   ├── practical_exams.php
│   └── [other student pages]
│
├── teacher/                 # Teacher area
│   ├── index.php
│   ├── practical_submissions.php
│   └── [other teacher pages]
│
├── moderator/               # Moderator area
│   └── ...
│
├── community/               # Community section
│   └── ...
│
├── api/                     # API endpoints
│   ├── fetch_exams.php
│   ├── get_subjects.php
│   └── ...
│
├── assets/                  # Static files
│   ├── css/
│   ├── js/
│   └── images/
│
├── uploads/                 # User uploads (777 permissions)
│   ├── certificates/
│   ├── submissions/
│   ├── profiles/
│   └── ...
│
├── includes/                # Backend includes (not directly accessible)
│   ├── db.php
│   ├── functions.php
│   ├── header.php
│   ├── sidebar.php
│   ├── footer.php
│   └── [other includes]
│
├── vendor/                  # Composer dependencies
│   └── autoload.php
│
├── emails/                  # Email templates
│   └── ...
│
└── logs/                    # Error logs (optional)
    └── errors.log
```

---

**Last Updated**: 2026-06-20
**Version**: 1.0 - Production Ready
**Created For**: InfinityFree Hosting
