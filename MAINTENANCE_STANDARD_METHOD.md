# 🔧 Maintenance Code - Standard Error Finding Method

**کسی بھی فائل میں ERROR تلاش کرنے کا STANDARD طریقہ**

---

## 📋 5-Point Standard Checklist (ہر فائل کے لیے)

جب کوئی error ہو تو یہ 5 چیزیں CHECK کرو:

```
1. SYNTAX CHECK (2 منٹ)
2. FILE PATH CHECK (1 منٹ)
3. VARIABLE CHECK (2 منٹ)
4. FUNCTION CHECK (2 منٹ)
5. PERMISSION CHECK (1 منٹ)
```

**Total Time: 8 منٹ میں کوئی بھی error fix ہو سکتا ہے!**

---

## ✅ STANDARD CHECKLIST #1: SYNTAX CHECK

**جب کوئی فائل error show کرے تو یہ check کرو:**

### Check 1.1: Closing Braces `}`

```php
WRONG ❌                          RIGHT ✓
function test() {               function test() {
    if ($x) {                       if ($x) {
        echo "hi";                      echo "hi";
    // MISSING }                    }
                                }

// Error: "Unexpected end of file"
```

**Standard Method:**
```
1. فائل کھول
2. آخر میں جاؤ (Ctrl+End)
3. سے count کرو:
   - } کی تعداد گنو
   - { کی تعداد گنو
   - Dono برابر ہونے چاہیں
4. اگر { زیادہ ہے → } کم ہے
```

### Check 1.2: Quotes Match `" "` یا `' '`

```php
WRONG ❌                          RIGHT ✓
'text is not closed              'text is closed'
"mixed quotes'                   "mixed quotes"
$var = "text;                    $var = "text";
```

**Standard Method:**
```
1. Ctrl+F سے search کرو: "
2. ہر quote کو چیک کرو
3. Opening quote اور closing quote match ہونے چاہیں
4. Same کام کرو: '
```

### Check 1.3: Semicolons `;`

```php
WRONG ❌                          RIGHT ✓
$var = "test"                    $var = "test";
echo "hello"                     echo "hello";
if ($x) {                        if ($x) {
    test()                           test();
}                                }
```

**Standard Method:**
```
1. ہر LINE کے آخر میں ;
2. Exception: } کے بعد ; نہیں چاہیے
3. Exception: if ($condition) { - ; نہیں
```

### Check 1.4: Commas in Arrays/Functions

```php
WRONG ❌                          RIGHT ✓
['a' => 'b'                      ['a' => 'b',
 'c' => 'd']                      'c' => 'd']

function test($x, $y $z)         function test($x, $y, $z)
```

**Standard Method:**
```
Array میں:
1. ہر item کے بعد comma (except آخری)
2. Multi-line arrays میں ہر line پر comma

Function میں:
1. Parameters کے بیچ میں comma ضروری ہے
```

---

## ✅ STANDARD CHECKLIST #2: FILE PATH CHECK

**جب یہ error ہو: "File not found" یا "require failed"**

### Check 2.1: Path Syntax صحیح ہے؟

```php
WRONG ❌                          RIGHT ✓
__DIR__ . '/file.php             __DIR__ . '/file.php'
__DIR__ . "../config.php         __DIR__ . '/../config.php'
__DIR__ "/config.php             __DIR__ . '/config.php'

// Missing: . (concatenation operator)
```

**Standard Method:**
```
1. __DIR__ ہمیشہ . سے connect ہو
2. Path ہمیشہ quotes میں ہو
3. / یا \ سے path join ہو
```

### Check 2.2: Relative Path صحیح ہے؟

```
فائل کی location معلوم کرو:

اگر فائل ہے: /includes/maintenance_middleware.php

اور آپ کو چاہیے: /config/maintenance.php

تو path ہونی چاہیے:
__DIR__ . '/../config/maintenance.php'
  └─ /includes/
     ├─ Up one level: /../
     └─ Then into config: /config/maintenance.php

RIGHT ✓: __DIR__ . '/../config/maintenance.php'
WRONG ❌: __DIR__ . './config/maintenance.php'
```

**Standard Method:**
```
1. require file کی location معلوم کرو
2. اس file سے target file تک کا path سوچو
3. ../ سے ऊपर جاؤ
4. / سے آگے جاؤ
```

### Check 2.3: File actually exists?

```bash
# Terminal میں check کرو:
ls -la /config/maintenance.php

# اگر نہیں ہے تو create کرو:
touch /config/maintenance.php

# یا:
mkdir -p /config
touch /config/maintenance.php
```

---

## ✅ STANDARD CHECKLIST #3: VARIABLE CHECK

**جب یہ error ہو: "Undefined variable" یا "Notice: Undefined"**

### Check 3.1: Variable declare ہوا ہے؟

```php
WRONG ❌                          RIGHT ✓
echo $name;                      $name = "Ali";
                                 echo $name;

// $name declare نہیں ہوا
```

**Standard Method:**
```
1. جہاں variable use ہو رہا ہے وہاں جاؤ
2. اوپر جاؤ اور دیکھو: کہاں $variable = assign ہوا؟
3. اگر نہیں ہے تو assign کرو
```

### Check 3.2: Variable scope صحیح ہے؟

```php
WRONG ❌                          RIGHT ✓
function test() {               function test() {
    $x = 5;                         return $x;
}                               }
echo $x; // undefined           $result = test();
                                echo $result;

// $x function کے اندر ہے
// باہر سے access نہیں ہو سکتا
```

**Standard Method:**
```
1. Variable کہاں declare ہوا؟
2. کہاں use ہو رہا ہے؟
3. Scope match ہے؟
   - Function کے اندر declare → صرف function میں use
   - Function سے باہر → سب جگہ use
```

### Check 3.3: Session variables exist ہیں؟

```php
WRONG ❌                          RIGHT ✓
echo $_SESSION['role'];         if (isset($_SESSION['role'])) {
// Notice if not set              echo $_SESSION['role'];
                                } else {
                                    echo "Not set";
                                }

// یا pehle se check:
session_start();
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user';
}
echo $_SESSION['role'];
```

**Standard Method:**
```
1. isset() یا empty() سے پہلے check کرو
2. یا session_start() کو top پر ڈالو
```

---

## ✅ STANDARD CHECKLIST #4: FUNCTION CHECK

**جب یہ error ہو: "Call to undefined function" یا "Function not found"**

### Check 4.1: Function declare ہوا ہے؟

```php
WRONG ❌                          RIGHT ✓
test();                          function test() {
// function test is not defined      echo "Hi";
                                 }
                                 test();
```

**Standard Method:**
```
1. Function call کو ڈھونڈو
2. ऊपر جاؤ اور function declaration ڈھونڈو
3. اگر نہیں ہے تو:
   - Function define کرو
   - یا دوسری فائل سے require کرو
```

### Check 4.2: Function file require ہوا ہے?

```php
WRONG ❌                          RIGHT ✓
test();                          require_once 'functions.php';
// test() is in functions.php    test();
// But file not included
```

**Standard Method:**
```
1. Function کہاں ہے؟ کس فائل میں؟
2. اس فائل کو require کیا؟
3. اگر نہیں:
   require_once __DIR__ . '/path/to/functions.php';
   test();
```

### Check 4.3: Function parameters match ہیں؟

```php
WRONG ❌                          RIGHT ✓
function save($name, $email) {  function save($name, $email) {
    ...                             ...
}                               }
save("Ali");                    save("Ali", "ali@test.com");
// Missing parameter!          // Both parameters given
```

**Standard Method:**
```
1. Function definition دیکھو
2. Parameters کی تعداد گنو
3. Function call میں وہی تعداد دیں
```

### Check 4.4: Function name spelling ✓

```php
WRONG ❌                          RIGHT ✓
getMaintenanceConfig();         getMaintenanceConfig();
// vs
get_maintenance_config();       get_maintenance_config();

// Naming must match exactly!
```

**Standard Method:**
```
1. Definition میں function name کاپی کرو
2. Call میں paste کرو
3. Exact match ensure کرو
```

---

## ✅ STANDARD CHECKLIST #5: PERMISSION CHECK

**جب یہ error ہو: "Permission denied" یا "Can't write to file"**

### Check 5.1: File Permissions صحیح ہیں؟

```bash
# Check permissions:
ls -l /config/maintenance.php

# Output مثال:
# -rw-r--r-- 1 user user 1234 Jun 20 15:30 maintenance.php
#  ^^^
#  644 = read+write for owner, read for others ✓

# اگر غلط ہے تو:
chmod 644 /config/maintenance.php

# Directory permissions:
chmod 755 /config/
chmod 755 /backups/
chmod 755 /backups/file_backups/
```

### Check 5.2: Directory writable ہے؟

```bash
# Backups directory writable ہونی چاہیے:
chmod 755 /backups/file_backups/
chmod 755 /backups/config_backups/

# Log file writable:
chmod 666 /backups/deployment.log
```

### Check 5.3: Ownership صحیح ہے?

```bash
# کون owner ہے:
ls -l /config/maintenance.php

# Apache/PHP یہ files use کرتا ہے:
# اس کے لیے permissions صحیح ہونے چاہیں

# اگر problem ہے:
sudo chown www-data:www-data /config/maintenance.php
# OR
sudo chown -R www-data:www-data /backups/
```

---

## 🎯 STANDARD WORKFLOW: کوئی بھی Error Fix کرنے کا

### Step 1: Error Message پڑھو (1 منٹ)

```
Error message کہاں ہے؟
- Browser میں: F12 → Console
- PHP logs میں: /xampp/apache/logs/error.log
- Custom logs میں: /backups/deployment.log

Error message کیا کہہ رہا ہے؟
- "Syntax error" → SYNTAX CHECK
- "File not found" → FILE PATH CHECK
- "Undefined variable" → VARIABLE CHECK
- "Call to undefined function" → FUNCTION CHECK
- "Permission denied" → PERMISSION CHECK
```

### Step 2: صحیح Checklist استعمال کرو (2-5 منٹ)

```
1. Error type معلوم کرو
2. اسی section میں جاؤ (Checklist #1-5)
3. تمام چیزیں one-by-one check کرو
4. جب ملے: FIX کرو
```

### Step 3: Fix کریں (1-3 منٹ)

```
1. Change کریں
2. Save کریں
3. Browser refresh کریں (Ctrl+F5)
4. Test کریں
```

### Step 4: Verify (1 منٹ)

```
1. Error gone ہے؟ ✓ YES → Done!
2. Error برقرار ہے؟ → دوبارہ Step 2 کریں
3. نیا error آیا؟ → نئے error کے لیے Step 1 کریں
```

---

## 📊 STANDARD ERROR TYPES & SOLUTIONS

| Error Type | Where Check | Solution |
|-----------|-----------|----------|
| Syntax Error | Checklist #1 | Braces, quotes, semicolons |
| File not found | Checklist #2 | Paths, file exists |
| Undefined variable | Checklist #3 | Variable declare, scope |
| Undefined function | Checklist #4 | Function define, require |
| Permission denied | Checklist #5 | chmod, ownership |

---

## 🔍 STANDARD FILES CHECKING ORDER

جب maintenance system میں error ہو تو یہ ترتیب سے check کرو:

```
1. /config/maintenance.php ← FIRST (90% errors یہاں ہیں)
   ├─ Line 3: maintenance_mode
   ├─ Line 4-6: Messages
   ├─ Line 9: show_admin_panel
   └─ Syntax: Braces, quotes, commas

2. /includes/header.php ← SECOND
   ├─ require_once line موجود ہے؟
   ├─ checkMaintenanceMode() call ہے؟
   └─ Path صحیح ہے؟

3. /includes/maintenance_middleware.php ← THIRD
   ├─ All functions defined ہیں؟
   ├─ All braces بند ہیں؟
   └─ No syntax errors؟

4. /admin/maintenance_control.php ← FOURTH
   ├─ Form handling OK؟
   ├─ CSRF token موجود؟
   └─ file_put_contents working?

5. /includes/safe_deployment.php ← LAST
   ├─ Class definition complete?
   ├─ mkdir() commands ok?
   └─ File paths correct?
```

---

## ⚡ QUICK REFERENCE TABLE

### Common Errors اور Quick Fixes:

```php
╔════════════════════════════════════════════════════════════════╗
║ ERROR                  │ CAUSE              │ FIX              ║
╠════════════════════════════════════════════════════════════════╣
║ "Unexpected {" or "}"  │ Mismatched braces  │ Count { and }   ║
║ "Syntax error"         │ Missing semicolon  │ Check line ends ║
║ "File not found"       │ Wrong path         │ Use __DIR__ . / ║
║ "Undefined variable"   │ Not declared       │ Declare with =  ║
║ "Undefined function"   │ Not defined        │ Define function ║
║ "Permission denied"    │ Wrong permissions  │ chmod 644       ║
║ "Parse error"          │ Quotes مismatch    │ Match quotes    ║
║ "White page"           │ Fatal error        │ Check error log ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 🎯 MOST IMPORTANT: STANDARD PLACES TO CHECK FIRST

### 1️⃣ **99% Errors یہاں ہیں:**

```
FILE: /config/maintenance.php
LINE: 3

Just change:
'maintenance_mode' => false,
to:
'maintenance_mode' => true,

OR reverse it!
```

### 2️⃣ **اگر وہ fix نہ ہو:**

```
FILE: /includes/header.php

Check:
- require_once './maintenance_middleware.php'; موجود ہے؟
- checkMaintenanceMode(); call ہے؟
```

### 3️⃣ **اگر اب بھی نہ ہو:**

```
FILE: /includes/maintenance_middleware.php

Check:
- function checkMaintenanceMode() { موجود ہے؟
- آخر میں } بند ہے؟
- No syntax errors؟
```

---

## 📝 MAINTENANCE CODE - STANDARD RULES

**ہمیشہ یہ rules follow کرو:**

### Rule 1: Syntax Rules
```
✓ Do:
  - function test() { ... }
  - $var = "text";
  - ['a' => 'b', 'c' => 'd']
  
✗ Don't:
  - function test() { ... (missing })
  - $var = "text"  (missing ;)
  - ['a' => 'b' 'c' => 'd']  (missing ,)
```

### Rule 2: Path Rules
```
✓ Do:
  require_once __DIR__ . '/file.php';
  require_once __DIR__ . '/../config/file.php';
  
✗ Don't:
  require_once '/file.php';  (absolute path)
  require_once 'file.php';   (relative, no __DIR__)
```

### Rule 3: Variable Rules
```
✓ Do:
  if (isset($var)) { echo $var; }
  $var = $_SESSION['key'] ?? 'default';
  
✗ Don't:
  echo $var;  (no check)
  echo $_SESSION['missing_key'];  (no isset)
```

### Rule 4: Function Rules
```
✓ Do:
  function test() { return true; }
  if (function_exists('test')) { test(); }
  
✗ Don't:
  test();  (not defined)
  function test() { ... (missing })
```

### Rule 5: Permission Rules
```
✓ Do:
  chmod 644 config/maintenance.php
  chmod 755 backups/
  
✗ Don't:
  chmod 777 config/maintenance.php  (too open)
  Leave permissions as default
```

---

## 🚀 STANDARD MAINTENANCE ROUTINE

**یہ کریں ہفتہ میں ایک بار:**

```
Step 1: Check all files syntax (5 min)
Step 2: Check all paths exist (3 min)
Step 3: Check all permissions (2 min)
Step 4: Test maintenance toggle (2 min)
Step 5: Review logs (1 min)

Total: 13 منٹ میں سب کچھ OK ہے!
```

---

## 📋 SUMMARY: STANDARD CHECKLIST

جب کوئی error ہو تو:

```
□ Step 1: Error message پڑھو
□ Step 2: صحیح چیک پوائنٹ select کرو (1-5)
□ Step 3: تمام items check کرو
□ Step 4: Problem ڈھونڈو
□ Step 5: Fix کرو
□ Step 6: Test کرو
□ Step 7: OK ہے؟ Done! نہیں؟ Repeat!
```

---

**یہ STANDARD method ہے! اسی سے کوئی بھی error 8 منٹ میں fix ہو جائے گا!** ✨

كورڈينيَٹ: Checklist #1-5 + 5-Step Workflow = **100% کامیاب** 🎯
