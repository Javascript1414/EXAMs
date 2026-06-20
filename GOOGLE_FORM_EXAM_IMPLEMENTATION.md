# Google Form Exam Management - Implementation Guide

## Overview
This feature enables teachers to create and manage Google Form-based exams in the EXAMs LMS. Students can access these exams and teachers can manually enter marks obtained from Google Form responses. Automatic certificate generation is supported.

## Files Created

### 1. Database Migration
**File:** `/migrations/phase_22_google_form_exams.sql`
- Creates Google Form exam tables
- Creates exam attempts tracking
- Creates teacher permissions system
- Updates certificates table

### 2. Admin Pages
**File:** `/admin/google_form_exams.php`
- Admin dashboard for Google Form exam management
- Grant/revoke teacher permissions
- View statistics and reports
- Monitor exam creation and marks entry

### 3. Teacher Pages
**File:** `/teacher/google_form_create_exam.php`
- Teachers can create Google Form exams
- Assign to specific subjects they teach
- Set exam details (title, marks, date, instructions)
- Auto-populate student attempts on creation

**File:** `/teacher/google_form_enter_marks.php`
- Teachers view all their created exams
- View registered students for each exam
- Enter marks from Google Form responses
- Auto-calculate pass/fail status

### 4. Student Pages
**File:** `/student/google_form_exams.php`
- View all available Google Form exams
- Filter by subject and status
- See exam details and instructions
- Click "Start Exam" to open Google Form in new tab
- View results and marks once entered

## Installation Steps

### Step 1: Run Database Migration
Execute the SQL file in your MySQL database:

```bash
# Option 1: Via MySQL Command Line
mysql -u root -p exams_lms < migrations/phase_22_google_form_exams.sql

# Option 2: Via phpMyAdmin
1. Go to phpMyAdmin
2. Select 'exams_lms' database
3. Click 'Import'
4. Upload 'phase_22_google_form_exams.sql'
5. Click 'Go'
```

### Step 2: Update Sidebar Navigation
Add to `/includes/sidebar.php` in the Admin section:

```php
<?php if (in_array($_SESSION['role_name'], ['admin', 'superadmin'])): ?>
    <!-- Inside Admin Menu -->
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/admin/google_form_exams.php">
            <i class="bi bi-google"></i> Google Form Exams
        </a>
    </li>
<?php endif; ?>
```

Add to `/includes/sidebar.php` in the Teacher section:

```php
<?php if ($_SESSION['role_name'] === 'teacher'): ?>
    <!-- Inside Teacher Menu -->
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/teacher/google_form_create_exam.php">
            <i class="bi bi-file-earmark-plus"></i> Create Google Form Exam
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/teacher/google_form_enter_marks.php">
            <i class="bi bi-pencil-square"></i> Enter Exam Marks
        </a>
    </li>
<?php endif; ?>
```

Add to `/includes/sidebar.php` in the Student section:

```php
<?php if ($_SESSION['role_name'] === 'student'): ?>
    <!-- Inside Student Menu -->
    <li class="nav-item">
        <a class="nav-link" href="<?= BASE_URL ?>/student/google_form_exams.php">
            <i class="bi bi-google"></i> Google Form Exams
        </a>
    </li>
<?php endif; ?>
```

### Step 3: Update Certificate Generation
Modify `/admin/certificates.php` and `/admin/release_certificates.php` to include Google Form exams:

```php
// Add this function to includes/functions.php

function generateGoogleFormCertificates($exam_id, $subject_id) {
    global $pdo;
    
    // Get exam and student marks
    $stmt = $pdo->prepare("
        SELECT gfea.student_id, gfea.exam_title, gfea.marks_obtained, 
               gfea.result_status, s.subject_name, u.full_name
        FROM google_form_exam_attempts gfea
        JOIN subjects s ON gfea.subject_id = s.id
        JOIN users u ON gfea.student_id = u.id
        WHERE gfea.exam_id = ? AND gfea.subject_id = ?
        AND gfea.marks_obtained IS NOT NULL
        AND gfea.result_status = 'pass'
    ");
    $stmt->execute([$exam_id, $subject_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate certificate for each student
    foreach ($students as $student) {
        $cert_stmt = $pdo->prepare("
            INSERT INTO certificates (student_id, subject_id, exam_title, marks_obtained, 
                                       result_status, exam_source, created_at)
            VALUES (?, ?, ?, ?, ?, 'Google Form', NOW())
            ON DUPLICATE KEY UPDATE marks_obtained = ?, result_status = ?
        ");
        $cert_stmt->execute([
            $student['student_id'], 
            $subject_id, 
            $student['exam_title'],
            $student['marks_obtained'],
            $student['result_status'],
            $student['marks_obtained'],
            $student['result_status']
        ]);
    }
    
    return count($students);
}
```

## Workflow

### For Admin:
1. Login to Admin Panel
2. Navigate to "Google Form Exams"
3. Go to "Permissions" tab
4. Click "Grant Permission"
5. Select Teacher and Subject
6. Teacher can now create exams for that subject

### For Teacher:
1. Login to Teacher Portal
2. Click "Create Google Form Exam"
3. Fill in exam details:
   - Exam Title
   - Select Subject (must have permission)
   - Google Form Link
   - Total Marks & Pass Marks
   - Exam Date & Time
   - Instructions
4. Click "Create Exam" (creates as DRAFT)
5. Click "Enter Exam Marks"
6. Select exam and view registered students
7. Enter marks from Google Form responses
8. Save marks (auto-calculates pass/fail)

### For Student:
1. Login to Student Dashboard
2. Click "Google Form Exams"
3. View all available exams
4. Filter by Subject or Status
5. Click "Start Exam" to open Google Form
6. After exam completion, teacher enters marks
7. View results once marks are entered

## Database Tables

### google_form_exams
Stores all Google Form exam information

### google_form_exam_attempts
Tracks student exam attempts and marks

### google_form_exam_permissions
Manages teacher permissions by subject

### google_form_exam_stats
Stores cached statistics for reports

## Features

✅ Create Google Form exams with full details
✅ Assign exams to specific subjects
✅ Only teachers with permission can create exams
✅ Student exam attempt tracking
✅ Manual marks entry from Google Form
✅ Auto-pass/fail status calculation
✅ Certificate generation for passing students
✅ Admin reports and statistics
✅ Bootstrap 5 responsive UI
✅ CSRF protection
✅ Role-based access control

## Troubleshooting

### Exams not visible to students:
- Ensure exam status is 'published' (not draft)
- Check student is in the same trade/course

### Teacher can't create exams:
- Admin must grant permission first
- Check "Permissions" tab in Google Form Exams admin page

### Marks not saving:
- Check if marks are within 0 to total_marks range
- Verify teacher has permission for the subject

### Certificates not generating:
- Ensure marks_obtained is not NULL
- Check result_status is 'pass'
- Run certificate generation manually from admin panel

## API Endpoints

### Admin
- `POST /admin/google_form_exams.php` - Grant/Revoke permissions

### Teacher
- `POST /teacher/google_form_create_exam.php` - Create exam
- `POST /teacher/google_form_enter_marks.php` - Save marks, Load students

### Student
- `GET /student/google_form_exams.php` - View exams

## Security

- CSRF tokens on all forms
- Role-based access control
- SQL injection prevention via prepared statements
- Input validation and sanitization
- Permission verification before actions

## Notes

- All times are in configured timezone (Asia/Kolkata by default)
- Exams are automatically created in DRAFT status
- Teachers must publish exams for students to see them
- Student attempt records are auto-created when exam is created
- Marks can only be entered once per student per exam
