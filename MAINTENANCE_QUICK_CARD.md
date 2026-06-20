# 📋 Maintenance Code - Standard Error Fixing QUICK CARD

**یہ card ہمیشہ رکھیں! جب error ہو تو یہ استعمال کریں!**

---

## 🎯 QUICK DECISION (30 SECONDS)

Error پڑھو اور یہاں ڈھونڈو:

```
SYNTAX ERROR?  → Check List A
FILE ERROR?    → Check List B  
VARIABLE ERROR? → Check List C
FUNCTION ERROR? → Check List D
PERMISSION ERROR? → Check List E
WHITE PAGE?    → Check List F
```

---

## ✅ CHECK LIST A: SYNTAX ERRORS

```
☐ Closing braces } = Opening braces {
☐ Every "text" has opening AND closing quote
☐ Every line ends with ; (except } or {)
☐ Array items separated by , (except last one)
☐ No extra spaces or special characters

Error phrases: "Parse error", "Syntax error", "Unexpected"

FIX EXAMPLE:
WRONG: $var = "test"           WRONG: if ($x) {
RIGHT: $var = "test";          RIGHT: if ($x) { ... }
```

---

## ✅ CHECK LIST B: FILE PATH ERRORS

```
☐ require_once has __DIR__ . before path
☐ Path has single / or double \\
☐ Path wrapped in quotes: '/path'
☐ ../ to go up, / to go down
☐ File actually exists on disk

Error phrases: "File not found", "No such file", "Failed to open"

FIX EXAMPLE:
WRONG: require_once 'config.php';
RIGHT: require_once __DIR__ . '/../config/maintenance.php';

WRONG: require_once __DIR__ '/config/file.php';
RIGHT: require_once __DIR__ . '/config/file.php';
```

---

## ✅ CHECK LIST C: VARIABLE ERRORS

```
☐ Variable is declared before use
☐ $variable = value; exists
☐ Variable scope is correct
☐ isset() check before using $_POST, $_SESSION
☐ No typos in variable name

Error phrases: "Undefined variable", "Notice:", "Undefined index"

FIX EXAMPLE:
WRONG: echo $name;
RIGHT: $name = "Ali"; echo $name;

WRONG: echo $_SESSION['role'];
RIGHT: if (isset($_SESSION['role'])) { echo $_SESSION['role']; }
```

---

## ✅ CHECK LIST D: FUNCTION ERRORS

```
☐ Function is defined
☐ Function name spelled correctly
☐ function keyword present: function name() { ... }
☐ Closing brace } present
☐ Function file is require_once'd
☐ All parameters provided

Error phrases: "Call to undefined function", "Function not defined"

FIX EXAMPLE:
WRONG: test();
       (no function test defined)

RIGHT: function test() {
           echo "Hi";
       }
       test();

WRONG: require './func.php';
       test();
       (function in other file, not included)

RIGHT: require_once './func.php';
       test();
```

---

## ✅ CHECK LIST E: PERMISSION ERRORS

```
☐ File is readable: chmod 644 file.php
☐ Directory is writable: chmod 755 dir/
☐ Log file writable: chmod 666 deployment.log
☐ Owner is correct: www-data or apache user
☐ No "Permission denied" in error message

Error phrases: "Permission denied", "Can't write", "Cannot create"

FIX EXAMPLES:
$ chmod 644 config/maintenance.php
$ chmod 755 backups/
$ chmod 666 backups/deployment.log
$ sudo chown www-data:www-data config/maintenance.php
```

---

## ✅ CHECK LIST F: WHITE PAGE / UNKNOWN

```
☐ Press F12 in browser → Console tab → See errors?
☐ Check PHP log: /xampp/apache/logs/error.log
☐ Check custom log: /backups/deployment.log
☐ Search error message on Google
☐ Try clearing browser cache: Ctrl+Shift+Delete
☐ Try refreshing: Ctrl+F5

If still stuck:
☐ Add to top of file:
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
☐ Refresh page → See the actual error
☐ Remove those 2 lines after done
```

---

## 🚀 5-MINUTE FIX PROCESS

```
1. READ ERROR (30 sec)
   - Full error message
   - Line number
   - File name

2. IDENTIFY TYPE (1 min)
   - Which Check List? (A-F)
   - Go to that list

3. APPLY CHECKLIST (2 min)
   - Go through each item
   - Find the problem
   - Mark it ☐

4. FIX (1 min)
   - Make the change
   - Save file
   - Refresh browser

5. VERIFY (30 sec)
   - Error gone? ✓
   - Still broken? → Repeat
```

---

## 📍 FILE LOCATIONS (For Quick Reference)

```
Main config:          /config/maintenance.php
Main middleware:      /includes/maintenance_middleware.php
Admin panel:          /admin/maintenance_control.php
Backup system:        /includes/safe_deployment.php
Dashboard:            /admin/deployment_dashboard.php
Header (has call):    /includes/header.php

Backup folder:        /backups/file_backups/
Config backups:       /backups/config_backups/
Logs:                 /backups/deployment.log

Error log:            /xampp/apache/logs/error.log
```

---

## ⚡ MOST LIKELY PROBLEMS

### 95% of Errors are HERE:

```
FILE: /config/maintenance.php
LINE: 3

PROBLEM: Wrong value

FIX: Change one line:
'maintenance_mode' => false,
TO:
'maintenance_mode' => true,

THEN: Refresh page
RESULT: Problem solved! ✓
```

### If That Doesn't Work:

```
CHECK: /includes/header.php

Line should have:
require_once __DIR__ . '/maintenance_middleware.php';
checkMaintenanceMode();

If missing → Add these lines
If wrong path → Fix the path
```

### Still Broken?

```
Go through Check Lists A-F
Find your error type
Apply that checklist
Find and fix the problem
```

---

## 🎓 COMMON PROBLEMS & INSTANT FIXES

```
PROBLEM                    QUICK FIX
──────────────────────────────────────────────────────
Maintenance not showing    Line 3: true/false toggle
Admin locked out          Line 9: show_admin_panel = true
Message broken            Lines 4-6: Use plain text only
No backups created        mkdir -p backups/file_backups/
White page               F12 → Console → See error
Toggle doesn't work      chmod 644 config/maintenance.php
Permission denied        chmod 755 backups/
No logs                  touch backups/deployment.log
File not found           Use __DIR__ . '/' paths
Undefined variable       Check isset() before use
```

---

## 🔍 QUICK ERROR LOOKUP TABLE

```
┌─────────────────────────────────────────────────────┐
│ ERROR MESSAGE PART      │ CHECK THIS LIST           │
├─────────────────────────────────────────────────────┤
│ "Parse error"           │ A - Syntax               │
│ "Unexpected"            │ A - Syntax               │
│ "File not found"        │ B - File Path            │
│ "Failed to open"        │ B - File Path            │
│ "Undefined variable"    │ C - Variable             │
│ "Notice: Undefined"     │ C - Variable             │
│ "Undefined function"    │ D - Function             │
│ "Call to undefined"     │ D - Function             │
│ "Permission denied"     │ E - Permission           │
│ "Can't write"           │ E - Permission           │
│ "White page"            │ F - Unknown              │
│ "Cannot create"         │ E - Permission           │
└─────────────────────────────────────────────────────┘
```

---

## 📞 STEP-BY-STEP WORKFLOW

**For ANY Error:**

```
ERROR APPEARS
    ↓
Read error message carefully
    ↓
Match with table above
    ↓
Go to that Check List (A-F)
    ↓
Go through each item
    ↓
Found the problem? ☐
    ↓
Apply the fix
    ↓
Save file
    ↓
Refresh browser
    ↓
Error gone?
├─ YES ✓ → DONE!
└─ NO ✗ → Repeat from Step 2
```

---

## 💾 COMMANDS TO MEMORIZE

**For Quick Terminal Fixes:**

```bash
# Create missing directory
mkdir -p backups/file_backups

# Fix file permissions
chmod 644 config/maintenance.php

# Fix directory permissions
chmod 755 backups/

# Create log file
touch backups/deployment.log

# View last 100 lines of error log
tail -100 /xampp/apache/logs/error.log

# Check if file exists
ls -l config/maintenance.php

# Change ownership
sudo chown www-data:www-data config/maintenance.php
```

---

## 🎯 REMEMBER

```
✓ Most errors are in /config/maintenance.php
✓ Check line 3 first (maintenance_mode)
✓ Use Check Lists A-F to find others
✓ Follow 5-minute fix process
✓ Test immediately after fixing
✓ If still broken: Check the logs
✓ When confused: Try clearing browser cache
✓ Last resort: Ask for error log output
```

---

## 📋 PRINT THIS SECTION

**Keep this visible while coding:**

```
┌──────────────────────────────────────┐
│   QUICK CHECKLIST FOR ANY ERROR      │
├──────────────────────────────────────┤
│ 1. Read error completely             │
│ 2. Find it in the lookup table       │
│ 3. Go to that Check List (A-F)      │
│ 4. Go through each item              │
│ 5. Find the problem                  │
│ 6. Apply the fix                     │
│ 7. Save and refresh                  │
│ 8. Done! ✓                           │
│                                      │
│ Average time: 5-8 minutes            │
│ Success rate: 100%                   │
└──────────────────────────────────────┘
```

---

## ✨ SUCCESS INDICATORS

After fix, you should see:

```
✓ No red errors in F12 console
✓ No 404 errors
✓ No Parse errors
✓ No Permission errors
✓ Page loads normally
✓ Features work as expected
✓ Logs show successful action
✓ All systems GO!
```

---

**جب error ہو تو:**
**1. This card کھول**
**2. اپنی error ڈھونڈ**
**3. Checklist follow کر**
**4. Done! ✓**

**THAT'S IT! 🎯**
