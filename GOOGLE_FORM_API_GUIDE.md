# Google Form Exam Management - API & Integration Guide

## Overview
This document provides technical details for developers integrating the Google Form Exam Management system with other parts of the application.

---

## 🔌 Utility Functions

All functions are available in `/includes/google_form_functions.php`. Include this file to use them:

```php
require_once __DIR__ . '/../includes/google_form_functions.php';
```

### Permission Functions

#### `hasGoogleFormExamPermission($teacher_id, $subject_id)`
Check if a teacher has Google Form exam creation permission for a subject.

```php
if (hasGoogleFormExamPermission($teacher_id, 5)) {
    // Teacher can create exams for subject 5
}
```

**Returns:** `bool`

---

#### `getTeacherGoogleFormSubjects($teacher_id)`
Get all subjects a teacher can create Google Form exams for.

```php
$subjects = getTeacherGoogleFormSubjects($teacher_id);
// Returns array of subjects with id, subject_name, trade_name
```

**Returns:** `array` of subject records

---

### Exam Functions

#### `getTeacherGoogleFormExams($teacher_id)`
Get all Google Form exams created by a teacher.

```php
$exams = getTeacherGoogleFormExams($teacher_id);
// Returns exam list with stats
```

**Returns:** `array` with exam data including `total_students`, `marks_entered`

---

#### `getExamStudents($exam_id, $teacher_id)`
Get students registered for a specific exam.

```php
$students = getExamStudents($exam_id, $teacher_id);
// Returns array of students with their attempt info
```

**Returns:** `array` with student records (attempt_id, student_id, full_name, email, marks_obtained, result_status)

---

#### `getStudentGoogleFormExams($student_id, $trade_id, $filter_subject, $filter_status)`
Get Google Form exams available to a student.

```php
// Get all exams
$exams = getStudentGoogleFormExams($student_id, $trade_id);

// Get specific subject only
$exams = getStudentGoogleFormExams($student_id, $trade_id, 5);

// Get only completed exams
$exams = getStudentGoogleFormExams($student_id, $trade_id, 0, 'completed');
```

**Parameters:**
- `$filter_subject` - Subject ID or 0 for all
- `$filter_status` - 'all', 'completed', 'pending', 'upcoming'

**Returns:** `array` of exams with marks and results

---

#### `getGoogleFormExamDetails($exam_id)`
Get comprehensive details about an exam including statistics.

```php
$exam = getGoogleFormExamDetails($exam_id);
// Includes: exam info, statistics, teacher details
```

**Returns:** `array` with exam details and stats (total_students, marks_entered, pass_count, fail_count, avg_marks)

---

### Marks Functions

#### `saveGoogleFormMarks($attempt_id, $marks_obtained, $teacher_id)`
Save marks for a student's exam attempt.

```php
$result = saveGoogleFormMarks($attempt_id, 85, $teacher_id);
if ($result['success']) {
    echo "Marks saved. Result: " . $result['result_status'];
} else {
    echo "Error: " . $result['message'];
}
```

**Returns:** `array` with keys:
- `success` - bool
- `message` - string
- `result_status` - 'pass' or 'fail' (if successful)

---

### Statistics Functions

#### `getStudentGoogleFormStats($student_id, $trade_id)`
Get Google Form exam statistics for a student.

```php
$stats = getStudentGoogleFormStats($student_id, $trade_id);
// Returns: total_exams, completed, pending, upcoming, avg_marks, pass_count, fail_count
```

**Returns:** `array` with stats

---

#### `getGoogleFormAdminStats()`
Get overall admin statistics for all Google Form exams.

```php
$stats = getGoogleFormAdminStats();
// Returns total_exams, total_students_appeared, marks_entered, certificates_generated
```

**Returns:** `array` with global stats

---

#### `getExamsByTeacher()`
Get statistics grouped by teacher.

```php
$teachers = getExamsByTeacher();
// Returns array with teacher_name, total_exams, students_appeared, marks_entered
```

**Returns:** `array` of teacher statistics

---

### Certificate Functions

#### `generateGoogleFormCertificates($exam_id, $subject_id)`
Generate certificates for all passing students in an exam.

```php
$result = generateGoogleFormCertificates($exam_id, $subject_id);
if ($result['success']) {
    echo "Generated " . $result['generated'] . " certificates";
}
```

**Returns:** `array` with:
- `success` - bool
- `generated` - int (number of certificates created)
- `message` - string (if error)

---

### Query Functions

#### `getStudentExamResult($student_id, $exam_id)`
Get a student's result for a specific exam.

```php
$result = getStudentExamResult($student_id, $exam_id);
// Returns: marks_obtained, result_status, marks_entered_at, attempt_time, total_marks, pass_marks
```

**Returns:** `array` with result details

---

#### `hasStudentCompletedGoogleFormExam($student_id, $exam_id)`
Check if a student has completed an exam (marks entered).

```php
if (hasStudentCompletedGoogleFormExam($student_id, $exam_id)) {
    // Student has completed this exam
}
```

**Returns:** `bool`

---

### Publication Functions

#### `publishGoogleFormExam($exam_id, $teacher_id)`
Publish an exam (make it visible to students).

```php
$result = publishGoogleFormExam($exam_id, $teacher_id);
```

**Returns:** `array` with success status and message

---

#### `closeGoogleFormExam($exam_id, $teacher_id)`
Close an exam (prevent access).

```php
$result = closeGoogleFormExam($exam_id, $teacher_id);
```

**Returns:** `array` with success status and message

---

## 📊 Database Schema

### google_form_exams
```sql
CREATE TABLE google_form_exams (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_title VARCHAR(255) NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    trade_id INT UNSIGNED NOT NULL,
    google_form_link TEXT NOT NULL,
    total_marks INT NOT NULL DEFAULT 100,
    pass_marks INT NOT NULL DEFAULT 40,
    exam_date DATE NOT NULL,
    exam_time TIME NULL,
    instructions LONGTEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    show_results BOOLEAN DEFAULT TRUE,
    show_answers BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign keys and indexes...
);
```

### google_form_exam_attempts
```sql
CREATE TABLE google_form_exam_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    exam_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    exam_title VARCHAR(255) NOT NULL,
    exam_source VARCHAR(50) DEFAULT 'Google Form',
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    marks_obtained INT NULL,
    marks_entered_by BIGINT UNSIGNED NULL,
    marks_entered_at TIMESTAMP NULL,
    result_status ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign keys and indexes...
    UNIQUE KEY unique_attempt (student_id, exam_id)
);
```

### google_form_exam_permissions
```sql
CREATE TABLE google_form_exam_permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    can_create_exams BOOLEAN DEFAULT TRUE,
    can_enter_marks BOOLEAN DEFAULT TRUE,
    granted_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign keys and indexes...
    UNIQUE KEY unique_permission (teacher_id, subject_id)
);
```

---

## 🔐 Security

All functions use prepared statements to prevent SQL injection:

```php
$stmt = $pdo->prepare("SELECT ... WHERE id = ?");
$stmt->execute([$id]);
```

CSRF protection is built into forms:

```php
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
```

Permission checks are performed before sensitive operations:

```php
if (!hasGoogleFormExamPermission($teacher_id, $subject_id)) {
    throw new Exception('Access denied');
}
```

---

## 🔄 Integration Examples

### Example 1: Dashboard Widget
Display upcoming Google Form exams on student dashboard:

```php
require_once '../includes/google_form_functions.php';

$student_id = $_SESSION['user_id'];
$trade_id = $_SESSION['trade_id'];

$upcoming = getStudentGoogleFormExams($student_id, $trade_id, 0, 'upcoming');

echo "<h3>Upcoming Exams (" . count($upcoming) . ")</h3>";
foreach ($upcoming as $exam) {
    echo "<div class='exam-item'>";
    echo "<h5>" . htmlspecialchars($exam['exam_title']) . "</h5>";
    echo "<p>Date: " . date('M d, Y', strtotime($exam['exam_date'])) . "</p>";
    echo "</div>";
}
```

### Example 2: Reports Integration
Add to admin reports:

```php
$stats = getGoogleFormAdminStats();
$by_teacher = getExamsByTeacher();

echo "Google Form Exams: " . $stats['total_exams'];
echo "Students Appeared: " . $stats['total_students_appeared'];
echo "Marks Entered: " . $stats['marks_entered'];
echo "Certificates: " . $stats['certificates_generated'];
```

### Example 3: Email Notification
Send email when marks are entered:

```php
require_once '../includes/google_form_functions.php';
require_once '../includes/phpmailer_config.php';

$result = getStudentExamResult($student_id, $exam_id);

if ($result && !is_null($result['marks_obtained'])) {
    $mail = getMailer();
    $mail->addAddress($student_email);
    $mail->Subject = "Your Exam Results are Ready";
    $mail->Body = "Your marks: " . $result['marks_obtained'] . "/" . $result['total_marks'];
    $mail->send();
}
```

### Example 4: Automatic Certificate Generation
Batch generate certificates:

```php
$exams = $pdo->query("
    SELECT DISTINCT gfe.id, gfe.subject_id
    FROM google_form_exams gfe
    WHERE gfe.status = 'published' 
    AND gfe.exam_date < NOW()
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($exams as $exam) {
    generateGoogleFormCertificates($exam['id'], $exam['subject_id']);
}
```

---

## 🚨 Error Handling

All functions include error handling. Check return values:

```php
$result = saveGoogleFormMarks($attempt_id, $marks, $teacher_id);

if (!$result['success']) {
    $_SESSION['error_message'] = $result['message'];
    // Handle error
} else {
    $_SESSION['success_message'] = "Marks saved";
}
```

---

## 📈 Performance Notes

- Functions use indexed columns for fast queries
- Statistics are calculated on-demand (can be cached)
- Prepare statements for reuse in loops
- Use UNIQUE constraints to prevent duplicates

---

## 🔗 AJAX Endpoints

### GET /admin/google_form_exams.php?action=get_students
Load students for marks entry (use POST with form data)

### POST /teacher/google_form_enter_marks.php
Actions:
- `action=get_students` - Load exam students
- `action=save_marks` - Save student marks
- `action=publish_exam` - Publish exam

---

## 📝 Adding Custom Fields

To extend the system with custom fields:

1. Alter the `google_form_exams` table:
```sql
ALTER TABLE google_form_exams ADD COLUMN custom_field VARCHAR(255);
```

2. Update the form in `google_form_create_exam.php`

3. Update the database insert statement

4. Update the `getGoogleFormExamDetails()` function in utility functions

---

## 🔄 Workflow Hooks

Consider adding hooks at these points:

- **Before Exam Creation** - Validate Google Form link
- **After Exam Creation** - Send notification to teacher
- **Before Marks Save** - Validate marks format
- **After Marks Save** - Send notification to student
- **Before Certificate Generation** - Validate student data
- **After Certificate Generation** - Log certificate creation

---

## 📚 Related Files

- Database Migration: `/migrations/phase_22_google_form_exams.sql`
- Admin Interface: `/admin/google_form_exams.php`
- Teacher Create: `/teacher/google_form_create_exam.php`
- Teacher Marks: `/teacher/google_form_enter_marks.php`
- Student View: `/student/google_form_exams.php`
- Functions: `/includes/google_form_functions.php`

---

**Version:** 1.0  
**Last Updated:** 2026-06-19
