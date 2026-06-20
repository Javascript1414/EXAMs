# Exam Management Workflow Redesign - Complete Guide

## 🎯 Overview

The exam management system has been completely redesigned to provide a **simple, linear workflow**:

```
Theory Exam Created → Auto-generated ID → Link Practical Exam → Publish Both → Students Take Both
```

## 🔄 New Workflow

### Step 1: Create Theory Exam (Admin/Teacher)
- Go to **Create Exam** menu
- Fill in theory exam details (questions, marks, etc.)
- System auto-generates **Theory Exam ID** (e.g., ID: 1234)
- Theory exam is ready for practical exam linking

### Step 2: Create Practical Exam (Admin/Moderator/Teacher)
- Go to **Practical Exams** menu  
- Click **Create New Practical Exam**
- Fill in:
  - Exam Title
  - Subject
  - Practical Marks (how many marks for practical)
  - Pass Marks
  - Submission Deadline
  - Evaluation Instructions
- Exam is created in **DRAFT** status

### Step 3: Link Practical to Theory Exam
- In **Practical Exams** dashboard
- Find the draft practical exam
- Click **Link** button
- Select the **Theory Exam** from dropdown
- System enforces **one-to-one relationship** (one practical per theory exam)

### Step 4: Publish Both Exams
- After linking, click **Publish** button
- System automatically publishes both exams
- Students can now see and take the exams
- Status changes to **Published** ✓

### Step 5: Review Submissions & Mark
- Students submit practical work
- Teacher marks submissions
- Theory marks come from exam portal
- System combines both marks
- Certificate generates when both marks available

## 📊 Key Features

### Database Changes
✅ Added `theory_exam_id` column to `practical_exams` table  
✅ Added `published` status tracking  
✅ Unique constraint: One practical per theory exam  
✅ Foreign key relationship between practical and theory exams

### Simplified UI
✅ **Removed** complicated menu items:
  - ❌ "Find Exam ID"
  - ❌ "Link Theory & Practical"
  - ❌ "Exam IDs Reference"

✅ **Kept** single menu:
  - ✅ "Practical Exams" (admin and teachers)

### Smart Validation
✅ Cannot publish without theory exam link  
✅ Cannot link if another practical already linked  
✅ Cannot delete if students submitted  
✅ Cannot delete linked theory exam if practical exists

### Role-Based Access
| Role | Can Access |
|------|-----------|
| Admin | Create, Link, Publish, Edit, Delete |
| Superadmin | Create, Link, Publish, Edit, Delete |
| Moderator | Create, Link, Publish, Edit, Delete |
| Teacher | Create, Link, Publish, Edit, Delete (own exams only) |
| Student | Take exams, Submit practical, View results |

## 🔧 Migration Steps

### Run Database Migration
```bash
# Option 1: Visit the migration page
Navigate to: /apply_phase_24_migration.php

# Option 2: Run SQL directly
ALTER TABLE practical_exams ADD COLUMN theory_exam_id BIGINT UNSIGNED NULL;
ALTER TABLE practical_exams ADD UNIQUE KEY unique_theory_exam_id (theory_exam_id);
ALTER TABLE practical_exams ADD FOREIGN KEY (theory_exam_id) REFERENCES exams(id) ON DELETE CASCADE;
ALTER TABLE exams ADD COLUMN published BOOLEAN DEFAULT FALSE;
```

### Verify Database Structure
```sql
DESC practical_exams;
-- Should show: theory_exam_id, published, published_at columns
```

## 📱 Pages & URLs

| Page | Path | Description |
|------|------|-------------|
| Practical Exams Dashboard | `/admin/practical_exams.php` | Main management page (admins) |
| Teacher Practical Exams | `/teacher/practical_exams.php` | Teacher view (redirects to admin page) |
| Exam Creation | `/admin/exams.php` | Create theory exams |
| Mark Submissions | `/teacher/practical_mark_submissions.php` | Grade practical work |
| Student Results | `/student/results.php` | View marks & certificates |

## ✨ Features by Page

### Practical Exams Dashboard
- ✅ Create new practical exam
- ✅ Link to theory exam (with validation)
- ✅ Publish/Unpublish exams
- ✅ Edit exam details
- ✅ Delete exam (with submission check)
- ✅ View submissions count
- ✅ Status badges (Draft/Published)
- ✅ Deadline tracking

### Submission Workflow
1. Exam published → Students see it
2. Students submit practical work
3. Teacher marks submissions
4. Marks saved to database
5. Theory marks pulled from exam portal
6. Combined result calculated
7. Certificate generated automatically

## 🚨 Important Notes

### Status Values
- **Draft**: Not yet published, can be edited/deleted
- **Active**: Published and available to students
- **Closed**: Submissions no longer accepted

### Publishing Rules
- ⚠️ Must link to theory exam FIRST
- ⚠️ Cannot publish without valid link
- ⚠️ Both exams publish together

### Certificate Generation
- Requires BOTH theory AND practical marks
- Only generates if student PASSED both
- Prevents incomplete certificates
- Grade calculated as: (Theory + Practical) / Total Marks

## 🔍 Troubleshooting

### "Cannot Publish: Exam Must Be Linked"
→ Use the **Link** button to select a theory exam

### "Another Practical Already Linked"
→ Each theory exam can only have ONE practical
→ Create new practical exam with different theory exam

### "Cannot Delete: Students Submitted"
→ Use **Unpublish** instead
→ Or delete submissions first (if allowed)

### Theory Exam Marks Not Showing
→ Ensure theory exam has marks in exam portal
→ Both marks must be complete for certificate

## 📋 Backup Files

Old pages backed up to: `/backup_removed_pages/`
- `find_exam_id.php`
- `link_theory_practical.php`
- `view_exam_ids.php`
- `practical_create_exam.php`

Can be restored if needed.

## 🎓 Student Experience

### Before Publishing
- Exam not visible in student dashboard
- Status: Hidden

### After Publishing
- Both theory and practical visible
- Can take theory exam first
- Can submit practical work after deadline
- Sees marks when teacher marks practical
- Certificate downloads when both marks ready

## 📊 Data Flow Diagram

```
┌─────────────────────────────────────────┐
│ Teacher Creates Theory Exam             │
│ System generates: Exam ID (e.g., 1234)  │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Teacher Creates Practical Exam          │
│ Status: DRAFT (not visible to students) │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Teacher Links Practical to Theory       │
│ One-to-one relationship established     │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Teacher Publishes Both Exams            │
│ Students can now take both              │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Student Takes Theory Exam               │
│ Marks recorded in exam_results          │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Student Submits Practical Work          │
│ File uploaded to submissions             │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Teacher Marks Practical                 │
│ Marks recorded in practical_marks       │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ System Combines Marks                   │
│ Final Result = Theory + Practical       │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│ Certificate Generated                   │
│ If student PASSED both sections         │
└─────────────────────────────────────────┘
```

## 🔐 Security & Validation

✅ CSRF token protection on all forms  
✅ Role-based access control  
✅ Ownership verification (can only edit own exams)  
✅ SQL injection prevention (prepared statements)  
✅ Input sanitization on all fields  
✅ Unique constraint prevents duplicate links  
✅ Foreign key cascade ensures data integrity

## 📞 Support

For issues or questions:
1. Check `/backup_removed_pages/` for old files
2. Review database migration log
3. Check error logs in PHP
4. Verify database constraints are applied

---

**Version**: 2.0 (Phase 24)  
**Date**: June 2026  
**Status**: ✅ Production Ready
