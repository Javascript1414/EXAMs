# 🎓 Certificate System - Complete Setup Guide

## Overview
The certificate system has been upgraded with:
- ✅ Professional certificate format (matching NSTI Kolkata design)
- ✅ Automatic certificate generation (when student views result)
- ✅ Manual certificate release by admin
- ✅ Email delivery to students
- ✅ PDF download capability
- ✅ Works for all exam results (pass/fail display on certificates)

## Step 1: Database Migration

Run the migration to add new certificate fields:

```bash
# Option A: Using PHP Setup Script
Navigate to: http://localhost/EXAMs/certificate_setup.php

# Option B: Manual SQL
mysql -u root exams_lms < phase_20_certificate_enhancement.sql
```

### New Columns Added:
- `grade` - Letter grade (A+, A, B, C, D)
- `obtained_marks` - Student's marks
- `total_marks` - Total marks
- `status` - Now includes 'pending' state

## Step 2: Certificate Format

The certificate now displays:

```
┌─────────────────────────────────────┐
│ National Skill Training Institute   │
│              Kolkata                │
│         CERTIFICATE                 │
│      OF ACHIEVEMENT                 │
│                    Date: XX May XXXX │
├─────────────────────────────────────┤
│        This is to certify that       │
│       [STUDENT NAME]                 │
│  has successfully appeared and       │
│  passed the examination...           │
├─────────────────────────────────────┤
│ EXAM TYPE: [Exam Name]              │
│ MARKS OBTAINED: 87/100              │
│ PERCENTAGE: 87%                     │
│ GRADE: A                            │
│ CERTIFICATE ID: CERT-XXXXXXXXXX    │
├─────────────────────────────────────┤
│ [Signature]    [Logo]   [Signature] │
│ Admin          NSTI      Officer     │
└─────────────────────────────────────┘
        [QR Code - Scan to Verify]
```

## Step 3: Two Ways to Generate Certificates

### Method 1: Automatic (Student-Initiated)
1. Student passes an exam
2. Student views results
3. Certificate auto-generates when viewing details
4. Student can view online or download PDF

**File:** `/student/certificate_view.php?id=<result_id>`

### Method 2: Manual (Admin-Initiated)
1. Admin goes to: `/admin/release_certificates.php`
2. Admin finds passed exam result
3. Admin clicks "📜 Release Certificate"
4. Certificate is generated with all data stored
5. Admin can email to student or student can access anytime

**File:** `/admin/release_certificates.php`

## Step 4: Using the System

### For Students:
1. **View Certificate:**
   - Navigate to: Student Dashboard → Certificates
   - Click on exam to view certificate
   - Certificate auto-generates on first view

2. **Download as PDF:**
   - Click "Download PDF" button
   - Receive professionally formatted PDF

3. **Print:**
   - Click "Print" button
   - Print to paper or PDF printer

### For Admins:
1. **Release Certificates:**
   - Go to: Admin Dashboard → Release Certificates
   - Search for student or exam
   - Filter: "Pending" certificates
   - Click "Release Certificate" button

2. **Email Certificates:**
   - Find certificate in "Already Certified" section
   - Click "📧 Email" button
   - Student receives email with links

3. **Manage:**
   - Admin Dashboard → Certificates
   - View all certificates
   - Revoke if needed
   - Reissue if revoked

## Step 5: Email Configuration

For email functionality to work:

### Gmail Setup:
1. Enable 2-Factor Authentication on your Gmail
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Set environment variables:

```bash
# Windows Command Prompt:
set SMTP_USER=your-email@gmail.com
set SMTP_PASS=your-app-password

# Windows PowerShell:
$env:SMTP_USER = "your-email@gmail.com"
$env:SMTP_PASS = "your-app-password"
```

## File Structure

```
/admin/
├── release_certificates.php       ← Release certificates to students
├── certificates.php               ← Manage all certificates
└── certificate_actions.php        ← Backend for email/release

/student/
├── certificates.php               ← List of student's certificates
├── certificate_view.php           ← View certificate (auto-generates)
└── certificate_download.php       ← Download as PDF

/
├── verify.php                     ← Public verification
├── certificate_setup.php          ← Run migrations
└── phase_20_certificate_enhancement.sql ← Database migration
```

## Database Schema

### certificates table

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| certificate_id | VARCHAR(100) | Unique cert ID (CERT-XXXXXX) |
| student_id | BIGINT | Student reference |
| exam_id | BIGINT | Exam reference |
| result_id | BIGINT | Result reference |
| score | DECIMAL(8,2) | Obtained marks |
| percentage | DECIMAL(5,2) | Percentage (0-100) |
| grade | VARCHAR(2) | Letter grade (A+, A, B, C, D) |
| obtained_marks | DECIMAL(8,2) | Student's marks |
| total_marks | DECIMAL(8,2) | Total exam marks |
| issued_at | TIMESTAMP | Issue date |
| verification_code | VARCHAR(100) | Unique code for verification |
| generated_by | BIGINT | Admin who generated |
| status | ENUM | active/revoked/pending |

## Features

### ✅ Automatic Generation
- Triggered when student views result
- Stores grade, marks, percentage
- Creates unique verification code
- Flags result as `certificate_generated`

### ✅ Manual Release
- Admin can pre-generate certificates
- Better control and tracking
- Can email directly to student
- Professional workflow

### ✅ Email Delivery
- Sends to student's registered email
- Includes view link
- Includes download link
- Includes verification link

### ✅ PDF Download
- Professional format
- High-quality print ready
- Unique certificate ID
- All exam details included

### ✅ Public Verification
- Anyone can verify certificate
- Using Certificate ID or Verification Code
- Shows certificate details
- Checks revocation status

## Testing Checklist

- [ ] Run database migration successfully
- [ ] Automatic certificate generates when viewing result
- [ ] Certificate displays correct format
- [ ] Grade calculated correctly (A+, A, B, C, D)
- [ ] Marks display correctly
- [ ] PDF downloads successfully
- [ ] Admin can release certificate manually
- [ ] Email sends successfully to student
- [ ] Email links work correctly
- [ ] Verification page works
- [ ] Revoke/Reissue works

## Troubleshooting

### Certificate not generating
- Check database migration ran: `SELECT * FROM certificates LIMIT 1;`
- Check error logs: `php error_log`
- Verify result status: `is_passed = 1`

### Email not sending
- Check SMTP credentials
- Verify Gmail app password (not regular password)
- Check error logs
- Test with `certificate_setup.php`

### PDF generation failed
- Ensure Dompdf installed: `composer install`
- Check file permissions
- Verify PHP temp directory writable

### Grade not showing
- Run migration again: `certificate_setup.php`
- Update existing records
- Clear browser cache

## Grade Scale

| Percentage | Grade |
|-----------|-------|
| 90-100% | A+ |
| 80-89% | A |
| 70-79% | B |
| 60-69% | C |
| Below 60% | D |

## Security Features

✅ CSRF Token Validation
✅ Role-Based Access Control
✅ Ownership Verification
✅ Unique Verification Codes
✅ Status Tracking
✅ Audit Trail (generated_by)

---

**Setup Time:** ~5 minutes
**Status:** ✅ Ready for Production
