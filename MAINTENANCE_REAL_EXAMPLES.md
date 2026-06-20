# 💻 Maintenance Code - Real Examples & Practical Solutions

**Real code examples کے ساتھ STANDARD طریقہ**

---

## 🎯 REAL EXAMPLE 1: Syntax Error

### Scenario: "Parse error: syntax error"

**مسئلہ:** فائل میں syntax error ہے

```php
// ❌ WRONG - /config/maintenance.php میں یہ ہے:
<?php
return [
    'maintenance_mode' => false
    'maintenance_message' => 'System Down',  ← MISSING COMMA!
    'show_admin_panel' => true,
];
```

**Error ہوگی:**
```
Parse error: syntax error, unexpected 'maintenance_message' (T_STRING)
```

### Standard Method سے Fix:

**Step 1: Error message پڑھو**
```
"syntax error" → Checklist #1 (SYNTAX CHECK)
```

**Step 2: Checklist #1 استعمال کرو**
```
Line 4 اور 5 کے بیچ comma ہے؟ ❌ نہیں!
```

**Step 3: Fix کریں**

```php
// ✓ CORRECT:
<?php
return [
    'maintenance_mode' => false,  ← COMMA ADDED!
    'maintenance_message' => 'System Down',
    'show_admin_panel' => true,
];
```

**Step 4: Verify**
```
Refresh page → No error! ✓
```

---

## 🎯 REAL EXAMPLE 2: File Not Found

### Scenario: "Failed to open stream: No such file or directory"

**مسئلہ:** File path غلط ہے

```php
// ❌ WRONG - /includes/header.php میں یہ ہے:
<?php
require_once __DIR__ . '/config/maintenance.php';
// This looks for: /includes/config/maintenance.php
// But file is at: /config/maintenance.php
// WRONG DIRECTION!
```

**Error ہوگی:**
```
Warning: require_once(/xampp/htdocs/EXAMs/includes/config/maintenance.php):
Failed to open stream: No such file or directory
```

### Standard Method سے Fix:

**Step 1: Error message پڑھو**
```
"No such file" → Checklist #2 (FILE PATH CHECK)
```

**Step 2: Checklist #2 استعمال کرو**

```
فائل کہاں ہے؟
- /includes/header.php میں یہ line ہے

Target file کہاں ہے؟
- /config/maintenance.php میں

Path دیکھو:
/includes/ ← یہاں سے
../config/ ← اوپر جاؤ پھر config میں
```

**Step 3: Fix کریں**

```php
// ✓ CORRECT:
<?php
require_once __DIR__ . '/../config/maintenance.php';
// ../  = ऊपر ایک level
// /config/ = پھر config folder میں
// maintenance.php = یہ file
```

**Step 4: Verify**
```
No error! File loaded! ✓
```

---

## 🎯 REAL EXAMPLE 3: Undefined Variable

### Scenario: "Notice: Undefined variable"

**مسئلہ:** Variable استعمال ہو رہا ہے پر declare نہیں ہوا

```php
// ❌ WRONG - /admin/maintenance_control.php میں:
<?php
// Line 5:
echo $config['maintenance_mode'];  ← $config کہاں سے آیا؟

// Line 10 میں ہے:
if ($_POST['toggle']) {
    $config = require __DIR__ . '/../config/maintenance.php';
}
// پر اگر POST نہیں ہے تو $config declare نہیں ہوا!
```

**Error ہوگی:**
```
Notice: Undefined variable: config on line 5
```

### Standard Method سے Fix:

**Step 1: Error message پڑھو**
```
"Undefined variable" → Checklist #3 (VARIABLE CHECK)
```

**Step 2: Checklist #3 استعمال کرو**

```
Line 5 میں: echo $config['maintenance_mode'];
کہاں assign ہوا؟

Search کریں: $config =
- Line 10 میں ہے پر conditional ہے (if statement میں)
```

**Step 3: Fix کریں**

```php
// ✓ CORRECT:
<?php
// Line 5 سے پہلے ہمیشہ define کریں:
$config = require __DIR__ . '/../config/maintenance.php';

// پھر use کریں:
echo $config['maintenance_mode'];

// یا safely:
if (isset($config) && isset($config['maintenance_mode'])) {
    echo $config['maintenance_mode'];
} else {
    echo "Not available";
}
```

**Step 4: Verify**
```
No notice! Variable accessible! ✓
```

---

## 🎯 REAL EXAMPLE 4: Undefined Function

### Scenario: "Call to undefined function"

**مسئلہ:** Function استعمال ہو رہا ہے پر define نہیں ہوا

```php
// ❌ WRONG - /admin/deployment_dashboard.php میں:
<?php
$safe = getSafeDeployment();  ← یہ function کہاں ہے؟
```

**Error ہوگی:**
```
Fatal error: Call to undefined function getSafeDeployment()
```

### Standard Method سے Fix:

**Step 1: Error message پڑھو**
```
"undefined function" → Checklist #4 (FUNCTION CHECK)
```

**Step 2: Checklist #4 استعمال کرو**

```
getSafeDeployment() function کہاں ہے؟

Option 1: Same file میں define ہے؟
- Search: function getSafeDeployment() { 
- نہیں ملا

Option 2: دوسری file میں ہے؟
- safe_deployment.php میں ہے!
```

**Step 3: Fix کریں**

```php
// ✓ CORRECT:
<?php
// سب سے پہلے file include کریں:
require_once __DIR__ . '/../includes/safe_deployment.php';

// اب function available ہے:
$safe = getSafeDeployment();
```

**Step 4: Verify**
```
Function found and works! ✓
```

---

## 🎯 REAL EXAMPLE 5: Permission Denied

### Scenario: "Permission denied" when trying to write

**مسئلہ:** File permissions غلط ہیں

```
When toggle maintenance:
- System tries to write to /config/maintenance.php
- But file permissions don't allow writing
- Error: "Permission denied"
```

### Standard Method سے Fix:

**Step 1: Error message پڑھو**
```
"Permission denied" → Checklist #5 (PERMISSION CHECK)
```

**Step 2: Checklist #5 استعمال کرو**

```bash
# Terminal میں check کریں:
ls -l /config/maintenance.php

# Output مثال:
# -r--r--r-- 1 user user 1234 maintenance.php
#  ^^^
#  r-x = Read only! Can't write!
```

**Step 3: Fix کریں**

```bash
# Terminal میں:
chmod 644 /config/maintenance.php

# Verify:
ls -l /config/maintenance.php
# -rw-r--r-- ✓ Now writable!
```

**Step 4: Verify**
```
Toggle works now! ✓
```

---

## 📊 QUICK REFERENCE: Common Errors & Fixes

### Error Type 1: Syntax Errors

```php
// ❌ Missing semicolon:
$var = "test"
echo $var;

// ✓ Fix:
$var = "test";
echo $var;

// ❌ Mismatched quotes:
$var = 'test";

// ✓ Fix:
$var = 'test';

// ❌ Missing comma in array:
['a' => 'b'
 'c' => 'd']

// ✓ Fix:
['a' => 'b',
 'c' => 'd']

// ❌ Missing brace:
function test() {
    if ($x) {
        echo "hi";
    }

// ✓ Fix:
function test() {
    if ($x) {
        echo "hi";
    }
}
```

### Error Type 2: Path Errors

```php
// ❌ Absolute path (works on one server, breaks on another):
require_once '/xampp/htdocs/EXAMs/config/maintenance.php';

// ✓ Relative path (works everywhere):
require_once __DIR__ . '/../config/maintenance.php';

// ❌ No concatenation:
require_once __DIR__ '/config/maintenance.php';

// ✓ With concatenation:
require_once __DIR__ . '/config/maintenance.php';

// ❌ Wrong slashes:
require_once __DIR__ . "\config\maintenance.php";

// ✓ Forward slashes:
require_once __DIR__ . '/config/maintenance.php';
```

### Error Type 3: Variable Errors

```php
// ❌ No initialization:
echo $_POST['name'];

// ✓ With check:
echo $_POST['name'] ?? 'Not provided';

// ✓ With isset:
if (isset($_POST['name'])) {
    echo $_POST['name'];
}

// ❌ Wrong scope:
function test() {
    $x = 5;
}
echo $x;  // Won't work

// ✓ Return from function:
function test() {
    $x = 5;
    return $x;
}
echo test();  // Works!
```

### Error Type 4: Function Errors

```php
// ❌ Not defined:
test();
// No function test() defined

// ✓ Define first:
function test() {
    echo "Hi";
}
test();

// ❌ Wrong file:
getConfig();
// getConfig() is in another file

// ✓ Include file:
require_once 'functions.php';
getConfig();

// ❌ Wrong parameters:
function save($name, $email) { ... }
save("Ali");  // Missing parameter

// ✓ All parameters:
function save($name, $email) { ... }
save("Ali", "ali@test.com");
```

### Error Type 5: Permission Errors

```bash
# ❌ Read-only:
-r--r--r-- 1 user user maintenance.php
chmod 644  # Fix it

# ✓ Readable & Writable:
-rw-r--r-- 1 user user maintenance.php

# ❌ Log file not writable:
-rw-r--r-- 1 user user deployment.log
chmod 666 deployment.log  # Fix it

# ✓ Directory writable:
drwxr-xr-x 1 user user backups/
chmod 755 backups/

# For web server:
sudo chown www-data:www-data /config/maintenance.php
chmod 644 /config/maintenance.php
```

---

## 🚀 REAL WORKFLOW EXAMPLE

### Scenario: Someone reports "Maintenance not showing"

**Step 1: Identify (1 min)**
```
What's happening?
- User says maintenance page not showing
- User toggled maintenance ON
- But regular users see normal system
```

**Step 2: Check config first (2 min)**
```
Open: /config/maintenance.php
Line 3: maintenance_mode => ?

Check:
if (maintenance_mode => false) {
    ← PROBLEM FOUND!
    Should be: true
}
```

**Step 3: Fix (1 min)**
```php
// Change:
'maintenance_mode' => false,

// To:
'maintenance_mode' => true,

// Save file
```

**Step 4: Test (1 min)**
```
Refresh browser
Do you see maintenance page now?
✓ YES! Problem fixed!
```

**Total time: 5 minutes**

---

## 📋 STEP-BY-STEP TEMPLATE

کسی بھی error کے لیے یہ template استعمال کریں:

```
ERROR: ___________________

STEP 1: Error message پڑھو
Type: [ ] Syntax [ ] Path [ ] Variable [ ] Function [ ] Permission

STEP 2: File ڈھونڈو
File: ___________________
Line: ___________________

STEP 3: Problem ڈھونڈو
Problem: ___________________

STEP 4: Fix کریں
FROM: ___________________
TO:   ___________________

STEP 5: Test کریں
Result: [ ] Fixed [ ] Still broken

If still broken → Repeat from Step 2
```

---

## ✨ PRO TIPS

### Tip 1: Error Logging شامل کریں
```php
// Top of file:
error_reporting(E_ALL);
ini_set('display_errors', 1);

// اب تمام errors دیکھنے میں آئیں گے
// Testing کے بعد remove کر دیں
```

### Tip 2: Browser DevTools استعمال کریں
```
Press: F12
Tab: Console
Check: Red error messages
```

### Tip 3: PHP Error Log Check کریں
```bash
# Windows XAMPP:
tail -100 C:\xampp\apache\logs\error.log

# Linux:
tail -100 /var/log/apache2/error.log
```

### Tip 4: Backup رکھیں
```bash
# پہلے backup لیں:
cp config/maintenance.php config/maintenance.php.bak

# اگر خراب ہو تو:
cp config/maintenance.php.bak config/maintenance.php
```

---

## 🎯 SUMMARY TABLE

| Situation | Check | Fix |
|-----------|-------|-----|
| "Parse error" | Syntax | Braces, quotes, commas |
| "File not found" | Paths | Use __DIR__ . '/../' |
| "Undefined variable" | Declare | $var = value; |
| "Undefined function" | Define/Include | function {} or require |
| "Permission denied" | chmod | chmod 644 file.php |
| "Notice: Undefined" | isset() | if (isset($var)) |
| "White page" | Logs | Check error.log |
| "Not working" | Debug | Add echo/var_dump |

---

**اب اگر کوئی error ہو تو اس guide سے fix کر سکتے ہو!** ✨

**Remember:**
1. Error message پڑھو
2. صحیح Checklist استعمال کرو
3. اس guide سے example دیکھو
4. اپنے code میں apply کرو
5. Test کرو
6. Done! ✓
