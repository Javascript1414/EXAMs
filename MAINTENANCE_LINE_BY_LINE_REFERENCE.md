# 🎯 Maintenance Files - Line-by-Line Quick Reference

**تیز سے فائل میں error تلاش کرو! (Find errors in files quickly!)**

---

## 📄 File 1: config/maintenance.php ⭐ MOST IMPORTANT

**This ONE file controls EVERYTHING! Check here FIRST!**

```php
<?php
return [
    'maintenance_mode' => false,  ← LINE 3: TOGGLE HERE! (true = ON, false = OFF)
    
    'maintenance_message' => 'System Maintenance',     ← LINE 4: Message for users
    'maintenance_details' => 'We are updating...',      ← LINE 5: Details
    'maintenance_estimated_time' => 'A few moments',    ← LINE 6: Time estimate
    
    'show_admin_panel' => true,   ← LINE 9: Can admins access? (true = yes)
    
    'allowed_ips' => [
        '127.0.0.1',              ← LINE 12: IPs that bypass
        'localhost'               ← LINE 13: maintenance
    ],
    
    'last_maintenance' => '2024-06-20 15:30:00',  ← LINE 16: Last update time
    'next_scheduled_maintenance' => null,          ← LINE 17: Next scheduled
];
```

### ✅ Quick Checks for This File:

| Line | Check | Should Be | Status |
|------|-------|-----------|--------|
| 3 | `maintenance_mode` | true or false | ✓ |
| 4-6 | Messages | Plain text only | ✓ |
| 9 | `show_admin_panel` | true | ✓ |
| All | Quotes | Matched ('text' or "text") | ✓ |
| All | Commas | One after each line | ✓ |

### 🔧 Most Common Fixes:

```php
// PROBLEM: Maintenance not showing
// Line 3: Change from
'maintenance_mode' => false,
// To:
'maintenance_mode' => true,

// PROBLEM: Admin locked out
// Line 9: Change from
'show_admin_panel' => false,
// To:
'show_admin_panel' => true,

// PROBLEM: Bad message showing
// Lines 4-6: Make sure NO special characters:
'maintenance_message' => 'System Down',  ✓ Good
'maintenance_message' => 'System Down ❌',  ✗ Bad (emoji)
'maintenance_message' => 'System Down ♥',  ✗ Bad (symbol)
```

---

## 📄 File 2: includes/maintenance_middleware.php

**Shows the maintenance page to users**

```php
<?php
function checkMaintenanceMode() {  ← LINE 3: Main function name
    
    // Line 7-8: Load config file
    $config = require_once __DIR__ . '/../config/maintenance.php';
    
    if (!$config['maintenance_mode']) {  ← LINE 10: If maintenance OFF, let through
        return; // Allow access
    }
    
    // Line 14: Check if admin
    $is_admin = isset($_SESSION['role']) && 
                in_array($_SESSION['role'], ['admin', 'superadmin']);
    
    // Line 17: If admin and allowed, let through
    if ($is_admin && $config['show_admin_panel']) {
        return; // Admin can access
    }
    
    // Line 21: Show maintenance page
    showMaintenancePage($config);
    exit;
}

function showMaintenancePage($config) {  ← LINE 25: Display function
    // Lines 26-100: HTML for maintenance page
    // Check: Is HTML closed properly?
}

// Line 100+: Check that all functions have closing braces }
```

### ✅ Quick Checks for This File:

| Line | Check | Should Be |
|------|-------|-----------|
| 3 | Function name | `checkMaintenanceMode` |
| 7-8 | Config path | `/../config/maintenance.php` |
| 10 | Logic check | `if (!$config['maintenance_mode'])` |
| 25 | Function exists | `showMaintenancePage()` |
| End | Closing brace | `}` |

### 🔧 Most Common Issues:

```php
// PROBLEM: Syntax error (white page)
// Check: Missing closing brace
function checkMaintenanceMode() {
    // code
}  ← LINE END: Must have closing brace!

// PROBLEM: Not showing maintenance page
// Check: Line 21 - showMaintenancePage() called?
showMaintenancePage($config);  ← Must be there!

// PROBLEM: Admin locked out
// Check: Line 17 - Is condition correct?
if ($is_admin && $config['show_admin_panel']) {
    return; // Must allow admin access
}
```

---

## 📄 File 3: includes/safe_deployment.php

**Handles automatic backups**

```php
<?php
class SafeDeployment {  ← LINE 3: Class definition
    
    private $backup_dir;  ← LINE 5: Backup directory path
    private $log_file;    ← LINE 6: Log file path
    
    public function __construct() {  ← LINE 8: Constructor
        $this->backup_dir = __DIR__ . '/../backups/';
        $this->log_file = $this->backup_dir . 'deployment.log';
        
        // Create directories if missing
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);  ← LINE 14: Creates backup folder
        }
    }
    
    public function backupFile($file_path) {  ← LINE 18: Backup function
        // Creates backup with timestamp
        $backup_path = $this->backup_dir . 'file_backups/';
        if (!is_dir($backup_path)) {
            mkdir($backup_path, 0755, true);  ← LINE 22: Creates file_backups folder
        }
        // Rest of backup code...
    }
    
    public function log($type, $message) {  ← LINE 50+: Logging function
        // Writes to deployment.log
    }
}

function getSafeDeployment() {  ← LINE 100+: Helper function
    return new SafeDeployment();
}
```

### ✅ Quick Checks for This File:

| Line | Check | Should Be |
|------|-------|-----------|
| 3 | Class name | `SafeDeployment` |
| 5-6 | Properties | Defined |
| 8 | Constructor | `__construct()` |
| 14 | mkdir | Creates `/backups/` |
| 18 | Backup function | `backupFile()` exists |
| 22 | Sub-mkdir | Creates `file_backups/` |

### 🔧 Most Common Issues:

```php
// PROBLEM: Backups not created
// Check: Lines 14, 22 - mkdir() creating directories?
mkdir($this->backup_dir, 0755, true);  ← Must create backups/
mkdir($backup_path, 0755, true);       ← Must create backups/file_backups/

// PROBLEM: Logs not writing
// Check: Log file permissions
chmod($this->log_file, 0666);  ← Must be writable!

// PROBLEM: Restore not working
// Check: Function exists and takes right parameters
public function restoreFile($backup_path, $restore_path) {
    // Must have both parameters
}
```

---

## 📄 File 4: admin/maintenance_control.php

**Admin control panel to toggle maintenance**

```php
<?php
// Check permissions early
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {  ← LINE 5: Admin check
    die('Access denied');
}

if ($_POST['action'] === 'toggle_maintenance') {  ← LINE 9: Form submission check
    
    // Line 11: CSRF token verification
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token error');
    }
    
    // Line 15: Get config file
    $config = require_once __DIR__ . '/../config/maintenance.php';
    
    // Line 18: Toggle mode
    $new_mode = $_POST['maintenance_mode'] === 'on';
    
    // Line 20: Update config file
    $config['maintenance_mode'] = $new_mode;
    file_put_contents(__DIR__ . '/../config/maintenance.php', 
        '<?php return ' . var_export($config, true) . ';'
    );
    
    // Line 25: Backup old config
    backupFileBeforeUpdate(...);
}

// Line 30-100: HTML form
// Check: Form fields match POST checks above
```

### ✅ Quick Checks for This File:

| Line | Check | Should Be |
|------|-------|-----------|
| 5 | Role check | Only admin/superadmin |
| 9 | POST action | `toggle_maintenance` |
| 11 | CSRF token | Validated |
| 15 | Config path | `/../config/maintenance.php` |
| 18 | Toggle logic | Correct (on/off) |
| 25 | Backup call | Function exists |

### 🔧 Most Common Issues:

```php
// PROBLEM: Toggle not working
// Check: Line 9 - Form POST action correct?
if ($_POST['action'] === 'toggle_maintenance') {  ← Must match form

// PROBLEM: Can't update config
// Check: Line 20-22 - file_put_contents() works?
file_put_contents(  ← File must be writable (chmod 644)

// PROBLEM: CSRF error
// Check: Line 11 - Token generated and passed?
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    ← Must match session token
}
```

---

## 📄 File 5: admin/deployment_dashboard.php

**Shows status and logs**

```php
<?php
// Check permissions
if (!in_array($_SESSION['role'], ['admin', 'superadmin'])) {  ← LINE 5: Admin check
    header('Location: /');
    die;
}

// Line 9: Get status
$config = require_once __DIR__ . '/../config/maintenance.php';
$maintenance_on = $config['maintenance_mode'];  ← LINE 10: Current status

// Line 12: Get backups list
$safe = getSafeDeployment();
$backups = $safe->listBackups(50);  ← LINE 13: Get last 50 backups

// Line 15: Get logs
$logs = $safe->getLog(100);  ← LINE 15: Get last 100 log lines

// Line 17: Display status
if ($maintenance_on) {
    echo '<span class="badge badge-danger">🔴 MAINTENANCE</span>';  ← LINE 19
} else {
    echo '<span class="badge badge-success">🟢 LIVE</span>';  ← LINE 21
}

// Line 23-100: Display backups and logs in tables
// Check: All functions called actually exist
```

### ✅ Quick Checks for This File:

| Line | Check | Should Be |
|------|-------|-----------|
| 5 | Role check | Only admin/superadmin |
| 10 | Status read | From config file |
| 13 | Backups list | Function `listBackups()` |
| 15 | Logs read | Function `getLog()` |
| 19 | Status display | Shows correctly |
| 21 | Status display | Shows correctly |

### 🔧 Most Common Issues:

```php
// PROBLEM: No status showing
// Check: Line 10 - Config loaded correctly?
$config = require_once __DIR__ . '/../config/maintenance.php';

// PROBLEM: No backups list
// Check: Line 13 - Function exists?
$backups = $safe->listBackups(50);  ← In safe_deployment.php?

// PROBLEM: No logs
// Check: Line 15 - Function works?
$logs = $safe->getLog(100);  ← In safe_deployment.php?

// PROBLEM: Wrong status showing
// Check: Line 10 - Value from config is boolean?
$maintenance_on = $config['maintenance_mode'];  ← Should be true/false
```

---

## 📄 File 6: includes/header.php (UPDATED)

**Called on every page load**

```php
<?php
// ... other code ...

// LINE NEAR END OF FILE:
require_once __DIR__ . '/maintenance_middleware.php';  ← LINE X: Include middleware
checkMaintenanceMode();  ← LINE X+1: Call the function

// ... rest of page renders below ...
?>
```

### ✅ Quick Checks:

| Check | Should Be |
|-------|-----------|
| Include exists | Yes |
| Function called | Yes |
| Path correct | `./maintenance_middleware.php` |
| Placed before content | Yes (near top) |

### 🔧 Most Common Issues:

```php
// PROBLEM: Maintenance not checking on every page
// Check: Are these lines in header.php?
require_once __DIR__ . '/maintenance_middleware.php';
checkMaintenanceMode();

// PROBLEM: File not found error
// Check: Path is correct relative to header.php location
// header.php is in: /includes/
// middleware.php is in: /includes/
// So path is: './maintenance_middleware.php'
```

---

## 🎯 DEBUGGING QUICK CHECKLIST

**Go through these in order:**

```
□ File: /config/maintenance.php
  Line 3: maintenance_mode = true/false ✓
  Line 4-6: Messages are plain text ✓
  Line 9: show_admin_panel = true ✓
  
□ File: /includes/maintenance_middleware.php
  Line 3: Function checkMaintenanceMode() exists ✓
  Line 10: Config loaded correctly ✓
  Line 21: showMaintenancePage() called ✓
  
□ File: /includes/safe_deployment.php
  Line 3: Class SafeDeployment exists ✓
  Line 14: mkdir creates backup directory ✓
  Line 22: mkdir creates file_backups folder ✓
  
□ File: /admin/maintenance_control.php
  Line 5: Admin check present ✓
  Line 9: POST form handled ✓
  Line 20: Config file updated ✓
  
□ File: /admin/deployment_dashboard.php
  Line 5: Admin check present ✓
  Line 10: Status read from config ✓
  Line 13: Backups list shown ✓
  
□ File: /includes/header.php
  Line ?: maintenance_middleware included ✓
  Line ?: checkMaintenanceMode() called ✓
```

---

## 📊 Most Likely Error Locations

| Problem | Check File | Check Lines |
|---------|-----------|------------|
| Not showing | maintenance.php | 3 |
| Admin locked | maintenance.php | 9 |
| Bad message | maintenance.php | 4-6 |
| White page | middleware.php | All |
| No backups | safe_deployment.php | 14, 22 |
| Toggle fails | maintenance_control.php | 9, 20 |
| No logs | deployment_dashboard.php | 15 |
| Path error | All files | Check `__DIR__` paths |

---

## ⚡ SUPER QUICK FIX (Most Likely Solution)

**90% of problems are THIS:**

```
File: /config/maintenance.php
Line: 3

CHANGE THIS:
'maintenance_mode' => false,

TO THIS:
'maintenance_mode' => true,

SAVE & REFRESH PAGE!
✓ FIXED!
```

**If that doesn't work, check other lines in same file!**

---

## 🔐 Remember: SAFE ZONES

These are SAFE to change:
- ✅ maintenance_mode value (true/false)
- ✅ Message text (plain text only)
- ✅ Time estimates
- ✅ show_admin_panel value

These are NOT safe to change (unless you know what you're doing):
- ❌ Function names
- ❌ File paths (__DIR__)
- ❌ Class definitions
- ❌ Array brackets { [ } ]
- ❌ Quotes around values

---

**تمام errors /config/maintenance.php میں ہیں! (Most errors are in /config/maintenance.php!)**

**Check Line 3 FIRST! 🎯**
