# ❌ Quiz mein Questions Visible Nahi Hai - FIX Guide

## 🎯 Problem
- Exam ID = 2
- 3 Questions add kiye
- Lekin exam attempt mein blank hai

## 🔍 Root Cause
```
Database:
✅ questions table = 3 questions saved
❌ exam_questions table = EMPTY (no linking!)

Solution: exam_questions mein questions ko link karna hai
```

---

## ✅ Fix Methods

### **METHOD 1: Automatic Fix (Fastest - 30 seconds)**

1. **Browser mein yeh file open karo:**
   ```
   http://localhost/EXAMs/fix_exam_questions.php
   ```

2. **Run karo aur dekho green ✅ messages**

3. **Refresh karo browser**

4. **Ab exam mein 3 questions dikhengi!**

---

### **METHOD 2: Admin Panel (Manual - 1 minute)**

1. **Login karo Admin ke saath**
   ```
   http://localhost/EXAMs/staff_login.php
   ```

2. **Go to Exams section:**
   ```
   Left Sidebar → Exams
   ```

3. **Find "Exam 2" aur click karo:**
   ```
   Edit button
   ```

4. **Click "Assign Questions" tab**

5. **Select your 3 questions:**
   ```
   ☑️ Question 1
   ☑️ Question 2
   ☑️ Question 3
   ```

6. **Click "Assign Questions" button**

7. **Refresh exam aur questions dikhengi!**

---

### **METHOD 3: Direct Database SQL**

Open MySQL aur run karo:

```sql
-- Clear existing (if any)
DELETE FROM exam_questions WHERE exam_id = 2;

-- Get your 3 question IDs
SELECT id FROM questions ORDER BY id DESC LIMIT 3;

-- Link them to exam (replace ID numbers)
INSERT INTO exam_questions (exam_id, question_id, question_order) VALUES
(2, 15, 1),  -- Replace 15 with your question ID
(2, 14, 2),  -- Replace 14 with your question ID
(2, 13, 3);  -- Replace 13 with your question ID
```

---

## 🎓 After Fix - Verification

1. **Student login karo**
   ```
   http://localhost/EXAMs/student_login.php
   ```

2. **Go to Exams**

3. **Click "Exam 2" → Start Exam**

4. **Ab 3 questions dikhengi! ✅**

---

## 📝 SQL Query - Exact Command

Agar apne questions manually check karna ho:

```sql
-- Check if questions exist
SELECT COUNT(*) FROM questions;

-- Check if linked to exam 2
SELECT * FROM exam_questions WHERE exam_id = 2;

-- See full details
SELECT q.id, q.question_text, eq.question_order 
FROM exam_questions eq 
JOIN questions q ON eq.question_id = q.id 
WHERE eq.exam_id = 2;
```

---

## 🔄 Next Time - Proper Flow

Jab naye exam mein questions add karo:

1. **Questions banao** (`/admin/question_add.php`) ✅ (aap yeh kiya)
2. **Exam banao** (`/admin/exam_add.php`) 
3. **Exam mein questions assign karo** (`/admin/exam_assign_questions.php?id=X`) ⬅️ **YEH BHOOL GAYE!**
4. **Exam publish karo**
5. **Students attempt kar sakte hain**

---

## ✨ One-Click Fix

**Fastest way:**
```
Open: http://localhost/EXAMs/fix_exam_questions.php
Done!
```

---

## 🎯 Aapko Kya Karna Chahiye?

**Ab yeh do karo:**

1. ✅ **fix_exam_questions.php run karo** (ek link, done!)
   
   OR
   
   ✅ **Admin panel se Assign Questions** (3 clicks)

2. ✅ **Exam attempt refresh karo**

3. ✅ **Questions ab visible hongi!**

---

## 💡 If Still Not Showing

Agar phir bhi nahi dik rahe:

1. **Browser cache clear karo**: `Ctrl+Shift+Delete`
2. **Database refresh karo**: Run `fix_exam_questions.php` again
3. **Check student role**: Student ko exam attempt ke permissions chahiye

---

**Ready? Kaunsa method use karoge?** 🚀

1. **Auto Fix** (`fix_exam_questions.php`)
2. **Admin Panel** (manual)
3. **SQL Direct** (database)
