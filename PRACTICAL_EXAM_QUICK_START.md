# Practical Exam Management System - Quick Start Guide

## Overview
The Practical Exam System allows teachers to create practical exams with flexible Theory + Practical marks split. Students submit work, teachers mark it, and certificates are generated from combined marks.

## Flow Diagram
```
1. Teacher Creates Practical Exam
   ↓
2. Admin Approves (if needed)
   ↓
3. Students Submit Work
   ↓
4. Teacher Marks Submissions
   ↓
5. System Combines Theory + Practical Marks
   ↓
6. Certificates Auto-Generate (if passing)
   ↓
7. Admin Releases Certificates
   ↓
8. Students View & Download Certificates
```

## Setup Instructions

### 1. Run Database Migration
Execute the migration file to create all necessary tables:

```bash
# Import in MySQL
SOURCE migrations/phase_23_practical_exams.sql;
```

Tables created:
- `practical_exams` - Practical exam definitions
- `practical_submissions` - Student submissions
- `practical_marks` - Teacher marks and feedback
- `combined_exam_results` - Merged theory + practical scores
- `practical_exam_stats` - Statistics

### 2. Update Sidebar Menu
Add menu items to `/includes/sidebar.php`:

**For Teachers:**
```php
<li>
    <a href="/teacher/practical_create_exam.php" class="nav-link">
        <i class="bi bi-hammer"></i> Create Practical
    </a>
</li>
<li>
    <a href="/teacher/practical_mark_submissions.php" class="nav-link">
        <i class="bi bi-check-circle"></i> Mark Practicals
    </a>
</li>
```

**For Students:**
```php
<li>
    <a href="/student/practical_exams.php" class="nav-link">
        <i class="bi bi-hammer"></i> Practical Exams
    </a>
</li>
```

**For Admin:**
```php
<li>
    <a href="/admin/practical_exams.php" class="nav-link">
        <i class="bi bi-speedometer2"></i> Practical Exams
    </a>
</li>
```

## User Workflows

### Teacher Workflow

#### Creating a Practical Exam
1. Go to **Create Practical** page
2. Enter exam details:
   - **Title**: Name of practical (e.g., "Welding Practical - Lap Joint")
   - **Description**: What students need to do
   - **Subject**: Select subject (auto-fetches teacher's subjects)
   - **Theory Marks**: Set marks for theory part (e.g., 80)
   - **Practical Marks**: Set marks for practical part (e.g., 20)
   - **Practical Pass Marks**: Minimum to pass practical (e.g., 10)
   - **Submission Deadline**: When students must submit by
   - **Evaluation Instructions**: What to look for when marking

3. Click **Create Practical Exam**

**Features:**
- Total marks = Theory + Practical (auto-calculated)
- Marks are fully flexible (80-20, 70-30, 60-40, etc.)
- Each exam is tied to a subject

#### Marking Student Submissions
1. Go to **Mark Practicals** page
2. All your practical exams are listed
3. Click an exam to view student submissions
4. For each student, click **Mark** button
5. In modal window:
   - Review uploaded file
   - See student's notes
   - Enter marks (out of practical marks)
   - Add feedback (optional)
6. Click **Save Marks**

**Features:**
- Download student files to review
- Auto-calculates pass/fail based on pass marks
- Provides feedback system
- Marks are saved instantly

#### Generating Certificates
1. After marking all submissions
2. Click **Generate Certificates** button on exam
3. System automatically:
   - Gets theory marks from exam portal
   - Combines with practical marks
   - Creates certificates for passing students
   - Auto-calculates total percentage

**Passing criteria:**
- Must pass theory exam (pass mark)
- Must pass practical (practical pass mark)
- Only then certificate is generated

### Student Workflow

#### Viewing Practicals
1. Go to **Practical Exams** page
2. See all active practical exams for your trade
3. Each exam shows:
   - Theory marks (from exam portal)
   - Practical marks
   - Total marks
   - Submission deadline
   - Evaluation criteria

#### Submitting Practical Work
1. Click **Submit Practical Work** button
2. Upload file (PDF, DOC, ZIP, Images supported, max 50MB)
3. Add optional notes about submission
4. Click **Submit Practical**

**Status:**
- **Pending**: Not yet submitted
- **Submitted**: Waiting for teacher to mark
- **Marked**: Teacher has marked with score and feedback

#### Viewing Marks & Results
1. Once teacher marks, your score appears:
   - Practical marks obtained
   - Result status (Pass/Fail)
   - Teacher's feedback
2. Marks automatically combined with theory marks
3. If you pass both, certificate is generated

### Admin Workflow

#### Viewing Certificates
1. Go to **Practical Exam Management**
2. See certificates ready for release
3. Each shows:
   - Certificate ID
   - Student info
   - Exam name
   - Combined marks & percentage
   - Current status

#### Releasing Certificates
1. Click **Release** button on certificate
2. Certificate is now visible to student
3. Student can download it

**Certificate Status:**
- **Active**: Generated, not yet released
- **Released**: Visible to student
- **Revoked**: Can be re-released if needed

#### Viewing Statistics
1. Dashboard shows:
   - Total practical exams
   - Students participated
   - Submissions marked
   - Certificates generated

## Database Schema Summary

### practical_exams
```
id | title | subject_id | theory_marks | practical_marks | 
submission_deadline | created_by | status | created_at
```

### practical_submissions
```
id | practical_exam_id | student_id | submission_file | 
submission_notes | submitted_at | is_late | status
```

### practical_marks
```
id | submission_id | marks_obtained | result_status | 
feedback | marked_by | marked_at
```

### combined_exam_results
```
id | student_id | exam_id | practical_exam_id | theory_marks | 
practical_marks | total_marks | total_percentage | result_status | 
certificate_generated
```

## Key Features

### ✅ Flexible Marks Configuration
- Teachers set any split (80-20, 70-30, 60-40, etc.)
- Each practical can have different split
- No hardcoded values

### ✅ File Upload Support
- Students upload practical work files
- Multiple file formats supported
- Organized in `/uploads/practical_submissions/`

### ✅ Comprehensive Marking
- Detailed feedback system
- Auto-pass/fail calculation
- Late submission tracking

### ✅ Automatic Combination
- System combines theory + practical automatically
- Only when both marks available
- Auto-calculates total percentage

### ✅ Certificate Management
- Auto-generate when criteria met
- Admin controls release
- Both teacher and student can view
- Unique certificate IDs

### ✅ Statistics & Reporting
- Admin dashboard with stats
- Per-exam statistics
- Submission tracking
- Pass/fail counts

## Security Features

- CSRF protection on all forms
- Input sanitization
- Role-based access control
- File upload validation
- Prepared SQL statements
- Unique submission records (one per student per exam)

## API Functions Reference

All functions are in `/includes/practical_exam_functions.php`

### Teacher Functions
- `createPracticalExam($data)` - Create new practical
- `getTeacherPracticalExams($teacher_id)` - Get teacher's practicals
- `getPracticalSubmissionsForMarking($practical_id, $teacher_id)` - Get submissions
- `markPracticalSubmission($submission_id, ...)` - Mark a submission

### Student Functions
- `getStudentPracticalExams($student_id, $trade_id)` - Get available practicals
- `submitPractical($practical_id, ...)` - Submit work

### System Functions
- `combineCombinedExamMarks($student_id, $exam_id)` - Combine theory + practical
- `generateCombinedExamCertificate($result_id, $generated_by)` - Generate certificate

### Statistics
- `getPracticalExamStats($practical_id)` - Get exam statistics

## Troubleshooting

### Issue: Database tables not created
**Solution:** Run the migration file `migrations/phase_23_practical_exams.sql`

### Issue: "Access Denied" error
**Solution:** Check that:
- User has correct role (teacher/student/admin)
- Session variables are set properly
- User is logged in

### Issue: File upload not working
**Solution:**
- Check `/uploads/practical_submissions/` folder exists and is writable
- Check file size (max 50MB)
- Check file type is supported (PDF, DOC, ZIP, Images)

### Issue: Certificates not generating
**Solution:**
- Verify both theory and practical marks are entered
- Check that student passed both (theory + practical)
- Ensure marks are not NULL

## Next Steps

1. ✅ Run database migration
2. ✅ Update sidebar menu with links
3. ✅ Teachers can now create practicals
4. ✅ Students can now submit work
5. ✅ Start using the system!

## Support

For issues or questions:
1. Check database migration is complete
2. Verify all files exist in correct directories
3. Check error logs in browser console
4. Review function documentation in `practical_exam_functions.php`
