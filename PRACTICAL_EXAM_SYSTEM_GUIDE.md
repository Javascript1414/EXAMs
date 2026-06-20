# 🎓 Practical Exam System - Complete Implementation Guide

## Overview

This is a complete end-to-end system for managing practical exams, student submissions, teacher marking, and automatic certificate generation.

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PRACTICAL EXAM SYSTEM                     │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  STUDENT PHASE          TEACHER PHASE        AUTO PHASE      │
│  ─────────────────      ──────────────       ─────────────   │
│                                                               │
│  1. Upload Work  ──→    2. Review Files ──→  3. Calculate    │
│     (Any file)          Assign Marks        Theory+Practical │
│                         Add Feedback        = Total           │
│  4. View Marks   ←─     5. Update Status    6. Gen Certificate
│     (Total)       ─     Notify Student      Issue Email      │
│                                                               │
│  7. Download Cert                                             │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 Files & Their Purposes

### Student Interface Files

| File | URL | Purpose |
|------|-----|---------|
| `practical_exams.php` | `/student/practical_exams.php` | Upload practical work - any file type accepted |
| `view_marks.php` | `/student/view_marks.php` | View theory + practical marks combined with totals |
| `download_certificate.php` | `/student/download_certificate.php?id=CERT-XXXXX` | Download certificate as HTML/PDF |

### Teacher Interface Files

| File | URL | Purpose |
|------|-----|---------|
| `practical_submissions.php` | `/teacher/practical_submissions.php` | View student submissions, download files, assign marks |

### Backend/Core Files

| File | Purpose |
|------|---------|
| `includes/practical_exam_functions.php` | Core functions for practical exam operations |
| `includes/certificate_functions.php` | Certificate generation & email sending |
| `setup_practical_workflow.php` | System setup & documentation page |
| `setup_triggers.php` | Database trigger creation for auto-status updates |

---

## 🔄 Complete Workflow Steps

### Step 1: Student Uploads Practical Work ✏️

**Location:** `/student/practical_exams.php`

```php
// Student actions:
1. Click "Submit Work" button on practical exam card
2. Modal opens with file input
3. Select ANY file type (Cisco .pkt, .exe, .zip, .pdf, etc.)
4. Add optional notes
5. Click "Upload"

// Backend processes:
- File moved to /uploads/practical_submissions/
- Entry created in practical_submissions table
- Status = "submitted"
- Student sees button change to "Awaiting Marks"
```

### Step 2: Teacher Reviews & Marks 👨‍🏫

**Location:** `/teacher/practical_submissions.php`

```php
// Teacher actions:
1. Login → Navigate to "Practical Submissions"
2. See dashboard stats (submissions, pending marks, etc.)
3. Submissions grouped by practical exam
4. Click "Mark" button on student submission
5. Modal opens showing:
   - Student name (display only)
   - Submitted file (with download link)
   - Input: Marks Obtained (0 to practical_marks max)
   - Input: Result Status (pass/fail/pending_review)
   - Input: Feedback (optional)
6. Click "Save Marks"

// Backend processes:
- Marks saved to practical_marks table
- submission_status updated to "marked" (via trigger)
- Total calculated: Theory + Practical obtained = Total
- Certificate generated automatically
- Trigger creates entry in certificates table
```

### Step 3: Automatic Calculations 🧮

**Triggered when marks are saved:**

```php
// Database Trigger: update_submission_status_on_mark
- Updates practical_submissions.submission_status = "marked"
- Visible to student in practical_exams.php

// Certificate Generation (certificate_functions.php):
1. Fetch practical exam theory marks
2. Get teacher-assigned practical marks
3. Calculate: total_marks = theory_marks + practical_marks
4. Calculate: percentage = (total_marks / (theory_max + practical_max)) * 100
5. Determine pass/fail based on passing_marks
6. Insert into certificates table with unique ID
7. Schedule email notification

// Email Notifications:
- Email 1: Marks assigned notification
  - Student name
  - Subject
  - Marks obtained
  - Feedback from teacher
  
- Email 2: Certificate issued
  - Certificate PDF attachment (if available)
  - Download link from dashboard
  - Verification code
```

### Step 4: Student Views Complete Marks 📊

**Location:** `/student/view_marks.php`

**Displays:**

| Column | Value | Example |
|--------|-------|---------|
| Subject | From subjects table | "Networking" |
| Theory | From practical_exams | 50 |
| Practical Max | From practical_exams | 50 |
| Practical Obtained | From practical_marks | 45 |
| Total | Theory + Obtained | 95/100 |
| Percentage | (95/100) * 100 | 95% |
| Status | Pass/Fail badge | PASSED ✓ |

**Statistics Shown:**
- Total practicals: X
- Submitted: Y
- Marked: Z
- Average percentage: A%
- Total marks possible: TM

### Step 5: Student Downloads Certificate 🎓

**Location:** `/student/download_certificate.php?id=CERT-XXXXX`

**Certificate Contains:**
- Student full name
- Subject name
- Trade name
- Theory marks
- Practical marks
- Total marks obtained
- Percentage
- Pass/Fail status
- Issue date
- Certificate ID
- Verification link

**Format:** HTML (printable) or PDF (if TCPDF installed)

---

## 🗄️ Database Schema

### practical_exams Table
```sql
- id: Primary key
- subject_id: Foreign key to subjects
- title: Practical title
- description: Practical description
- theory_marks: Theory component marks (e.g., 50)
- practical_marks: Practical component marks (e.g., 50)
- submission_deadline: Date/time when submission closes
- status: 'active' or 'inactive'
```

### practical_submissions Table
```sql
- id: Primary key
- practical_exam_id: Foreign key to practical_exams
- student_id: Foreign key to users
- file_name: Name of uploaded file
- submitted_at: Submission timestamp
- submission_status: 'submitted', 'marked', or 'pending'
- is_late: Boolean (true if submitted after deadline)
```

### practical_marks Table
```sql
- id: Primary key
- submission_id: Foreign key to practical_submissions (UNIQUE)
- marks_obtained: Decimal marks given by teacher
- result_status: 'pass', 'fail', or 'pending_review'
- feedback: Teacher feedback comments
- marked_by: Teacher ID (foreign key to users)
- marked_at: Timestamp when marked
```

### certificates Table
```sql
- id: Primary key
- certificate_id: Unique certificate identifier (CERT-YYYYMM#####)
- student_id: Foreign key to users
- practical_exam_id: Foreign key to practical_exams
- subject_id: Foreign key to subjects
- trade_id: Foreign key to trades
- theory_marks: Theory marks from practical_exams
- practical_marks: Practical marks from practical_exams
- total_marks: Theory + practical obtained
- percentage: Calculated percentage
- is_passed: Boolean (1 or 0)
- issued_at: Certificate issue timestamp
- downloaded_at: First download timestamp
```

---

## ⚙️ Configuration & Setup

### Step 1: Create Required Tables

Run `setup_practical_workflow.php` once:
```
Navigate to: http://localhost/EXAMs/setup_practical_workflow.php
```

Creates:
- `practical_marks` table
- `certificates` table
- Verifies `practical_submissions` table

### Step 2: Create Database Triggers

Run `setup_triggers.php` once:
```
Navigate to: http://localhost/EXAMs/setup_triggers.php
```

Creates triggers:
- `update_submission_status_on_mark` - Updates submission status when marks inserted
- `update_submission_status_on_mark_update` - Updates when marks modified

### Step 3: Configure Email (Optional)

Edit `includes/certificate_functions.php`:

```php
// Line 85-89: SMTP Configuration
$mail->Host = 'smtp.gmail.com';
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password'; // Gmail App Password, not main password
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
```

For Gmail:
1. Enable 2-Factor Authentication
2. Generate App Password
3. Use App Password in config (not main password)

### Step 4: Create Upload Directory

Ensure directory exists with write permissions:
```
/uploads/practical_submissions/
```

Permissions: `755` or `777`

---

## 🔑 Key Features

✅ **Any File Type Accepted**
- Cisco .pkt files
- Program files (.exe, .cpp, .java, etc.)
- Documents (.pdf, .doc, .docx)
- Archives (.zip, .rar)
- Media files
- 50MB max size limit

✅ **Automatic Total Calculation**
- Theory marks set by institution
- Practical marks assigned by teacher
- Total = Theory + Practical
- Percentage calculated automatically

✅ **Automatic Certificate Generation**
- Triggered when marks assigned
- Unique certificate ID generated
- Includes all marks breakdown
- Pass/fail status determined

✅ **Email Notifications**
- Marks notification to student
- Certificate attachment/link
- Teacher feedback included

✅ **Student Dashboard**
- All practicals with marks
- Theory + Practical breakdown
- Total marks visible
- Percentage calculated
- Download certificates

✅ **Teacher Dashboard**
- View all submissions
- Group by practical
- Download submitted files
- Mark with feedback
- Instant certificate generation

---

## 🐛 Troubleshooting

### Issue: "Marks not showing in student view"
**Solution:**
1. Verify practical_marks table has data: `SELECT * FROM practical_marks`
2. Check submission_id is correct in practical_marks
3. Run trigger setup: `setup_triggers.php`

### Issue: "Certificate not generating"
**Solution:**
1. Verify certificates table exists: `setup_practical_workflow.php`
2. Check practical_exams has theory_marks and practical_marks values
3. Check teacher assigned marks correctly

### Issue: "File upload failing"
**Solution:**
1. Verify `/uploads/practical_submissions/` directory exists
2. Check directory permissions (755 or 777)
3. Check file size < 50MB
4. Check `practical_exams.php` logs

### Issue: "Email not sending"
**Solution:**
1. Verify SMTP config in `certificate_functions.php`
2. For Gmail: Use App Password, not main password
3. Check firewall allows port 587 (TLS) outbound
4. Test with `setup_practical_workflow.php` manual trigger

---

## 📝 User Instructions

### For Students

1. **Upload Practical Work:**
   - Go to "Practical Exams" from sidebar
   - Click "Submit Work" on exam card
   - Upload ANY file type
   - Wait for "Awaiting Marks"

2. **Check Marks:**
   - Go to "My Marks"
   - See theory + practical + total
   - View status (Pending/Pass/Fail)
   - Check your percentage

3. **Download Certificate:**
   - In "My Marks" table
   - Click "Download" button in Certificate column
   - Print or save PDF
   - Share on LinkedIn/job applications

### For Teachers

1. **View Submissions:**
   - Go to "Practical Submissions" from admin panel
   - See all student submissions
   - Click file link to download

2. **Assign Marks:**
   - Click "Mark" button
   - Enter marks obtained
   - Select result status
   - Add feedback
   - Click "Save Marks"

3. **Verify Completion:**
   - Check certificate generation confirmation
   - System auto-sends student email
   - Certificate appears in student's dashboard

---

## 🎯 Summary

**Complete workflow from upload to certificate:**

```
Student uploads file
        ↓
File stored in /uploads/practical_submissions/
        ↓
Database entry in practical_submissions
        ↓
Status shows "Awaiting Marks"
        ↓
Teacher reviews file & assigns marks
        ↓
Entry created in practical_marks
        ↓
Trigger: Status updated to "marked"
        ↓
Auto-calculate: Theory + Practical = Total
        ↓
Generate certificate with unique ID
        ↓
Send email to student with certificate
        ↓
Student views marks in "My Marks" page
        ↓
Student downloads certificate
        ↓
✅ COMPLETE!
```

---

## 📞 Support

For issues or questions:
1. Check this documentation
2. Run setup files again
3. Check database tables and data
4. Review error logs in browser console
5. Check server logs: `/xampp/apache/logs/`

