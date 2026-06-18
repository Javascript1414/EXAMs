# 🎓 Custom Certificate ID Format - Complete Implementation

## Certificate ID Format

```
CITS/24-25/Y/1414/A1
```

### Format Breakdown

| Part | Example | Description | Source |
|------|---------|-------------|--------|
| **Course Code** | CITS | Trade/Course shortname | trades.trade_code |
| **Academic Year** | 24-25 | YY-YY format (Aug-July) | Calculated from exam date |
| **Year Marker** | Y | Static year indicator | Constant |
| **Student Registration** | 1414 | Student enrollment number | users.enrollment_no |
| **Exam Sequence** | A1 | Exam sequence (A1, A2, A3...) | Auto-incremented per student |

## Implementation Steps

### Step 1: Run Database Migration

Navigate to one of these pages to apply database changes:

**Option A (Recommended):**
```
http://localhost/EXAMs/certificate_id_migration.php
```

**Option B (Manual):**
Execute: `phase_21_custom_certificate_id.sql`

### Step 2: Configure Trade Codes

Go to Admin Dashboard → Certificate Config

Set trade codes for all courses:
- CITS = Computer-Integrated Tool & Services
- COPA = Computer Operator & Programming Assistant
- DDVT = Dial Data Video Tech
- ACIT = Advanced Computer & Information Tech
- Etc.

### Step 3: Set Student Enrollment Numbers

Go to Admin Dashboard → Certificate Config

Enter enrollment/registration numbers for each student. Examples:
- 1414
- 2020
- 3535
- Etc.

### Step 4: Release Certificates

Go to Admin Dashboard → Release Certificates

Click "Release Certificate" for each passed exam result.

Certificate will auto-generate with format: **CITS/24-25/Y/1414/A1**

---

## File Structure

### Database Files
- `phase_21_custom_certificate_id.sql` - Migration script

### PHP Files
- `includes/certificate_generator.php` - Core certificate ID generation functions
- `admin/certificate_config.php` - Admin configuration interface
- `admin/certificate_id_migration.php` - Migration runner
- `admin/certificate_actions.php` - Certificate operations (updated)
- `admin/release_certificates.php` - Release interface
- `admin/certificates.php` - Certificate management
- `student/certificate_view.php` - Student certificate view (updated)
- `student/certificate_download.php` - PDF download (updated)

---

## Key Functions

### `getAcademicYear($date)`
Calculates academic year from a date using Aug-July system.

```php
getAcademicYear('2024-09-15')  // Returns: "24-25"
getAcademicYear('2025-01-15')  // Returns: "24-25"
getAcademicYear('2025-08-15')  // Returns: "25-26"
```

### `getExamSequenceLetter($sequence)`
Converts exam sequence number to letter format.

```php
getExamSequenceLetter(1)   // Returns: "A1"
getExamSequenceLetter(2)   // Returns: "B2"
getExamSequenceLetter(10)  // Returns: "J10"
```

### `generateCertificateID($pdo, $student_id, $exam_id, $result_id, $result_date)`
Main function to generate certificate ID with all metadata.

Returns array with:
- `certificate_id` - Full certificate ID (e.g., "CITS/24-25/Y/1414/A1")
- `course_code` - Course code
- `academic_year` - Academic year
- `student_registration` - Student enrollment number
- `exam_sequence` - Sequence number

### `insertCertificate($pdo, $student_id, $exam_id, $result_id, $result_data, $generated_by)`
Inserts certificate into database with generated ID.

---

## Database Schema Changes

### New Columns in `certificates` Table

```sql
course_code VARCHAR(20)           -- CITS, COPA, etc.
academic_year VARCHAR(10)         -- 24-25
student_registration VARCHAR(50)  -- 1414
exam_sequence INT UNSIGNED        -- 1, 2, 3...
```

### New Column in `trades` Table

```sql
trade_code VARCHAR(20) UNIQUE  -- CITS, COPA, DDVT, ACIT
```

---

## Examples

### Scenario 1: First Exam
- Student: 1414
- Course: CITS
- Academic Year: 2024-25 (Aug-July)
- First exam taken
- **Certificate ID:** CITS/24-25/Y/1414/A1

### Scenario 2: Second Exam Same Year
- Same student (1414), same course
- Second exam in same academic year
- **Certificate ID:** CITS/24-25/Y/1414/A2

### Scenario 3: Different Course
- Student: 2020
- Course: COPA
- Academic Year: 2024-25
- First exam
- **Certificate ID:** COPA/24-25/Y/2020/A1

### Scenario 4: Next Academic Year
- Student: 1414
- Course: CITS
- Academic Year: 2025-26 (Aug 2025 - July 2026)
- First exam in new year (sequence resets per student per trade)
- **Certificate ID:** CITS/25-26/Y/1414/A1

---

## Academic Year Calculation

The system uses **August-July Academic Year** system:

| Date Range | Code |
|-----------|------|
| Aug 1, 2023 - July 31, 2024 | 23-24 |
| Aug 1, 2024 - July 31, 2025 | 24-25 |
| Aug 1, 2025 - July 31, 2026 | 25-26 |

**Automatic:** No manual configuration needed. Calculated from exam date.

---

## Workflow

### For Automatic Generation (Student-Initiated)

1. Student takes exam and passes
2. Student views results
3. Certificate auto-generates with proper ID format
4. Format: `CITS/24-25/Y/1414/A1`

### For Manual Release (Admin-Initiated)

1. Admin goes to: Admin Dashboard → Release Certificates
2. Finds passed exam result
3. Clicks "Release Certificate"
4. Certificate generated with proper ID format
5. Admin can email student or student accesses from dashboard

---

## Testing Checklist

- [ ] Database migration applied successfully
- [ ] Trade codes set for all courses
- [ ] Enrollment numbers set for all students
- [ ] Certificate auto-generates with correct format
- [ ] Admin can manually release certificates
- [ ] Certificate ID shows: CITS/24-25/Y/1414/A1
- [ ] Exam sequence increments correctly (A1, A2, A3...)
- [ ] Academic year calculates correctly
- [ ] Student can view certificate
- [ ] Student can download PDF
- [ ] Admin can email certificate
- [ ] Verification page shows correct ID

---

## Troubleshooting

### Problem: Certificate ID shows "null"
**Solution:** 
- Check if enrollment_no is set for student (Admin → Certificate Config)
- Check if trade_code is set for course (Admin → Certificate Config)

### Problem: Academic year wrong
**Solution:**
- Academic year is calculated automatically from exam date
- System uses Aug-July academic year
- Check exam date in database

### Problem: Sequence numbers wrong
**Solution:**
- Sequence resets for each student
- Check: SELECT COUNT(*) FROM certificates WHERE student_id = X
- If old format mixed with new, this could cause issues

### Problem: Trade code appears twice
**Solution:**
- Each trade should have unique code
- Update from admin panel or run:
```sql
UPDATE trades SET trade_code = 'CITS' WHERE trade_name LIKE '%CITS%' LIMIT 1;
```

---

## Admin Panel Links

| Page | URL | Purpose |
|------|-----|---------|
| Certificate Config | `/admin/certificate_config.php` | Set trade codes & enrollment numbers |
| Release Certificates | `/admin/release_certificates.php` | Release/generate certificates |
| Manage Certificates | `/admin/certificates.php` | View, revoke, email certificates |

---

## Student Panel Links

| Page | URL | Purpose |
|------|-----|---------|
| My Certificates | `/student/certificates.php` | View all certificates |
| Certificate View | `/student/certificate_view.php?id=X` | View single certificate |
| Download PDF | `/student/certificate_download.php?id=X` | Download as PDF |

---

## Public Verification

Anyone can verify a certificate at:
```
http://localhost/EXAMs/verify.php?code=VERIFICATION_CODE
```

Enter Certificate ID or Verification Code to verify authenticity.

---

## Security Features

✅ CSRF Token Validation
✅ Role-Based Access Control  
✅ Ownership Verification
✅ Unique Verification Codes
✅ Status Tracking (active/revoked/pending)
✅ Audit Trail (generated_by user tracking)

---

## Important Notes

1. **Academic Year:** Automatically calculated from exam date - no configuration needed
2. **Enrollment Numbers:** Must be set per student for ID generation to work
3. **Trade Codes:** Must be set per course/trade
4. **Exam Sequence:** Auto-incremented per student starting from 1
5. **Old Certificates:** Existing certificates keep old format, new ones use CITS/24-25/Y/1414/A1

---

**Status:** ✅ Ready for Production
**Last Updated:** June 2026
