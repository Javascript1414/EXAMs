# Google Form Exam Management - Quick Start Guide

## ⚡ 5-Minute Setup

### Step 1: Run Database Migration (2 minutes)
Navigate to your project root and run:

```bash
# Via MySQL CLI
mysql -u root -p exams_lms < migrations/phase_22_google_form_exams.sql

# Or via phpMyAdmin:
# 1. Open phpMyAdmin
# 2. Select exams_lms database
# 3. Go to Import tab
# 4. Upload phase_22_google_form_exams.sql
# 5. Click Go
```

### Step 2: Update Sidebar (2 minutes)

Edit `/includes/sidebar.php` and add these menu items:

**For Admin Users** (find the admin menu section):
```php
<li class="nav-item">
    <a class="nav-link" href="<?= BASE_URL ?>/admin/google_form_exams.php">
        <i class="bi bi-google"></i> Google Form Exams
    </a>
</li>
```

**For Teachers** (find the teacher menu section):
```php
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
```

**For Students** (find the student menu section):
```php
<li class="nav-item">
    <a class="nav-link" href="<?= BASE_URL ?>/student/google_form_exams.php">
        <i class="bi bi-google"></i> Google Form Exams
    </a>
</li>
```

### Step 3: Verify Setup (1 minute)

Open this URL in your browser to verify installation:
```
http://localhost/EXAMs/setup_google_form_exams.php
```

You should see confirmation messages. If there are any errors, fix them before proceeding.

---

## 🎯 Using the Feature

### For Admin:

1. Login as Admin
2. Go to **Google Form Exams**
3. Click **"Grant Permission"** button
4. Select a Teacher and Subject
5. Click **"Grant Permission"**

✅ Teacher can now create exams for that subject!

### For Teacher:

1. Login as Teacher
2. Go to **Create Google Form Exam**
3. Fill in:
   - Exam Title
   - Subject (must have permission)
   - Google Form Link (paste the share link)
   - Total Marks (e.g., 100)
   - Pass Marks (e.g., 40)
   - Exam Date
   - Instructions (optional)
4. Click **"Create Exam"**

✅ Exam created! Now students can see it.

5. Go to **Enter Exam Marks**
6. Click on an exam to expand and see students
7. Enter marks for each student
8. Click **"Save"** for each student

✅ Marks saved! Students can now see results.

### For Student:

1. Login as Student
2. Go to **Google Form Exams**
3. See all available exams
4. Click **"Start Exam"** button
5. Opens Google Form in new tab
6. Fill and submit Google Form
7. Return to system to see results (once teacher enters marks)

---

## 📁 File Structure

```
EXAMs/
├── migrations/
│   └── phase_22_google_form_exams.sql       # Database setup
│
├── admin/
│   └── google_form_exams.php                # Admin dashboard
│
├── teacher/
│   ├── google_form_create_exam.php          # Create exams
│   └── google_form_enter_marks.php          # Enter marks
│
├── student/
│   └── google_form_exams.php                # View exams
│
├── includes/
│   └── google_form_functions.php            # Utility functions
│
├── setup_google_form_exams.php              # Setup verification
└── GOOGLE_FORM_EXAM_IMPLEMENTATION.md       # Full documentation
```

---

## 🚀 Key Features

✅ **Admin Controls**
- Grant/revoke teacher permissions by subject
- View statistics and reports
- Monitor exam creation and marks entry

✅ **Teacher Features**
- Create exams with full details
- Only for subjects they teach
- Enter marks from Google Form responses
- Auto-calculate pass/fail status
- View exam statistics

✅ **Student Features**
- View all assigned exams
- Filter by subject and status
- Click to open Google Form
- See results once teacher enters marks
- Automatic certificate generation

✅ **System Features**
- Bootstrap 5 responsive design
- CSRF protection
- Role-based access control
- Auto-attempt creation for all students
- Email notifications (if configured)

---

## 🔧 Troubleshooting

### Problem: Database tables not created
**Solution:** 
- Check MySQL user has proper privileges
- Run migration manually via phpMyAdmin

### Problem: Teacher can't create exams
**Solution:**
- Admin must grant permission first
- Check the "Permissions" tab in Google Form Exams admin page

### Problem: Exams not visible to students
**Solution:**
- Ensure exam status is "published" (not draft)
- Check student is in the same trade

### Problem: Marks not saving
**Solution:**
- Ensure marks are between 0 and total marks
- Check teacher has permission for that subject

### Problem: Menu items not showing
**Solution:**
- Verify sidebar.php was updated correctly
- Clear browser cache
- Check session role is set correctly

---

## 📊 Database Tables

### google_form_exams
Stores exam information (title, marks, dates, instructions, etc.)

**Key fields:**
- `id` - Exam ID
- `exam_title` - Exam name
- `subject_id` - Associated subject
- `google_form_link` - URL to Google Form
- `total_marks`, `pass_marks` - Scoring
- `exam_date` - When exam is scheduled
- `status` - draft/published/closed
- `created_by` - Teacher who created it

### google_form_exam_attempts
Tracks student exam participation and marks

**Key fields:**
- `id` - Attempt ID
- `student_id` - Student who took exam
- `exam_id` - Associated exam
- `marks_obtained` - Marks entered by teacher
- `result_status` - pass/fail/pending
- `marks_entered_by` - Teacher who entered marks
- `marks_entered_at` - When marks were entered

### google_form_exam_permissions
Controls which teachers can create exams for which subjects

**Key fields:**
- `id` - Permission ID
- `teacher_id` - Teacher
- `subject_id` - Subject they can create exams for
- `can_create_exams` - Permission flag
- `can_enter_marks` - Permission flag

### google_form_exam_stats
Caches statistics for reporting

---

## 💡 Tips & Best Practices

1. **Create Google Form First**
   - Design your form on google.com/forms
   - Share it (get the share link)
   - Then add it to the system

2. **Use Clear Exam Titles**
   - Good: "Java Programming - Mid Term Exam"
   - Bad: "Exam 1"

3. **Set Realistic Pass Marks**
   - Typically 40% of total marks
   - Can be customized per exam

4. **Add Instructions**
   - Help students understand expectations
   - Mention time limits
   - Clarify submission process

5. **Enter Marks Promptly**
   - Students expect feedback quickly
   - Marks show in their dashboard once entered

6. **Generate Certificates**
   - Do this after entering marks
   - Automatic for passing students
   - Shows exam source as "Google Form"

---

## 📞 Support

For issues or questions:
1. Check the full documentation: `GOOGLE_FORM_EXAM_IMPLEMENTATION.md`
2. Review the utility functions: `includes/google_form_functions.php`
3. Check error logs in browser console
4. Verify database tables exist

---

## ✅ Verification Checklist

- [ ] Database migration completed
- [ ] All tables created (check phpMyAdmin)
- [ ] Sidebar updated with menu items
- [ ] Setup script runs without errors
- [ ] Admin can grant permissions
- [ ] Teacher can create exams
- [ ] Student can view exams
- [ ] Teacher can enter marks
- [ ] Marks appear in student dashboard
- [ ] Certificates can be generated

---

**Enjoy using Google Form Exams!** 🎉
