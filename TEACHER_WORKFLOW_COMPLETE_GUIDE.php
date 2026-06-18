<?php
/**
 * COMPLETE WORKFLOW: How to Add Teachers and Use the System
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Teacher & Subject Workflow</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 { margin-bottom: 10px; font-size: 2.5em; }
        .header p { opacity: 0.9; font-size: 1.1em; }
        
        .content { padding: 40px; }
        
        .section {
            margin: 30px 0;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #667eea;
        }
        
        .section h2 { 
            color: #333; 
            margin-bottom: 20px; 
            font-size: 1.4em;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .step {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .step-num { 
            display: inline-block;
            background: #667eea;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .step-title { 
            font-weight: bold; 
            color: #333; 
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .step-desc { color: #666; line-height: 1.6; }
        
        .url-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
            font-family: monospace;
            color: #1565c0;
            font-size: 1.05em;
        }
        
        .workflow-diagram {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px solid #667eea;
            text-align: center;
        }
        
        .workflow-diagram p { 
            color: #667eea; 
            font-weight: bold; 
            font-size: 1.3em;
            line-height: 1.8;
        }
        
        .arrow { color: #667eea; font-size: 1.5em; }
        
        .role-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .role-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .role-card h3 {
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        
        .role-card ul {
            margin-left: 20px;
            line-height: 1.8;
        }
        
        .role-card li {
            margin: 8px 0;
            font-size: 0.95em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover { background: #f9f9f9; }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px 10px 0;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .highlight {
            background: #fffde7;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        
        .success-box {
            background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
            margin: 20px 0;
        }
        
        .success-box h3 { color: #2e7d32; margin-bottom: 10px; }
        .success-box p { color: #1b5e20; line-height: 1.6; }
        
        ul { line-height: 1.8; }
        li { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Complete System Workflow</h1>
            <p>From Adding Teachers to Students Viewing Results</p>
        </div>
        
        <div class="content">
            <!-- Quick Links -->
            <div class="section">
                <h2>⚡ Quick Navigation Links</h2>
                <p style="margin-bottom: 15px;">Direct access to all important pages:</p>
                <a href="http://localhost/EXAMs/admin/add_teacher.php" class="btn" target="_blank">👨‍🏫 Add New Teacher</a>
                <a href="http://localhost/EXAMs/admin/manage_subject_teachers.php" class="btn" target="_blank">📚 Assign Teachers to Subjects</a>
                <a href="http://localhost/EXAMs/teacher/my_subjects.php" class="btn" target="_blank">👨‍🏫 Teacher Dashboard</a>
                <a href="http://localhost/EXAMs/student/my_results.php" class="btn" target="_blank">📊 Student Results</a>
            </div>
            
            <!-- Overall Workflow -->
            <div class="section">
                <h2>🔄 Complete Workflow Overview</h2>
                
                <div class="workflow-diagram">
                    <p>
                        Step 1: ADMIN ADDS TEACHER<br>
                        <span class="arrow">↓</span><br>
                        Step 2: ADMIN ASSIGNS TEACHER TO SUBJECT<br>
                        <span class="arrow">↓</span><br>
                        Step 3: TEACHER CREATES EXAM FOR SUBJECT<br>
                        <span class="arrow">↓</span><br>
                        Step 4: STUDENT TAKES EXAM<br>
                        <span class="arrow">↓</span><br>
                        Step 5: STUDENT VIEWS RESULTS WITH TEACHER INFO
                    </p>
                </div>
            </div>
            
            <!-- Step 1: Admin Adds Teacher -->
            <div class="section">
                <h2>📝 STEP 1: Admin Adds New Teacher</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Open Add Teacher Page
                    </div>
                    <div class="step-desc">
                        Go to the Add New Teacher page in the admin panel
                    </div>
                    <div class="url-box">📍 /admin/add_teacher.php</div>
                    <p style="margin-top: 10px; color: #667eea; font-weight: bold;">
                        👉 <a href="http://localhost/EXAMs/admin/add_teacher.php" target="_blank" style="color: #667eea; text-decoration: underline;">Click here to add teacher</a>
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Fill Teacher Information
                    </div>
                    <div class="step-desc">
                        Enter the following information:
                    </div>
                    <ul>
                        <li><strong>Full Name:</strong> e.g., "Muhammad Ali"</li>
                        <li><strong>Email:</strong> e.g., "ali@school.com"</li>
                        <li><strong>Phone:</strong> e.g., "03001234567"</li>
                        <li><strong>Password:</strong> Min 6 characters (they can login with this)</li>
                        <li><strong>Trade/Department:</strong> Select the trade they teach</li>
                        <li><strong>Batch:</strong> (Optional) e.g., "2024-2025"</li>
                        <li><strong>Institute Name:</strong> (Optional) e.g., "ABC Institute"</li>
                    </ul>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Click "Add Teacher" Button
                    </div>
                    <div class="step-desc">
                        ✅ Teacher account is created automatically!<br>
                        The account is: verified, active, and approved
                    </div>
                </div>
                
                <div class="success-box">
                    <h3>✅ Teacher Account Created!</h3>
                    <p>
                        The teacher can now login using their email and password.<br>
                        Their account is automatically verified and approved.
                    </p>
                </div>
            </div>
            
            <!-- Step 2: Admin Assigns Teacher to Subject -->
            <div class="section">
                <h2>📚 STEP 2: Admin Assigns Teacher to Subject</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Open Manage Subject Teachers Page
                    </div>
                    <div class="step-desc">
                        Go to Manage Subject Teachers in the admin panel
                    </div>
                    <div class="url-box">📍 /admin/manage_subject_teachers.php</div>
                    <p style="margin-top: 10px; color: #667eea; font-weight: bold;">
                        👉 <a href="http://localhost/EXAMs/admin/manage_subject_teachers.php" target="_blank" style="color: #667eea; text-decoration: underline;">Click here to assign teachers</a>
                    </p>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Select Subject and Teacher
                    </div>
                    <div class="step-desc">
                        • Choose a Subject (e.g., "Mathematics")<br>
                        • Choose the Teacher you just created<br>
                        • Click "Assign Teacher"
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Confirm Assignment
                    </div>
                    <div class="step-desc">
                        ✅ Teacher is now assigned to the subject!<br>
                        The assignment appears in the "Current Assignments" section
                    </div>
                </div>
                
                <div class="success-box">
                    <h3>✅ Teacher Assigned to Subject!</h3>
                    <p>
                        The teacher can now see this subject in their dashboard<br>
                        and can create exams for this subject.
                    </p>
                </div>
            </div>
            
            <!-- Step 3: Teacher Creates Exam -->
            <div class="section">
                <h2>📝 STEP 3: Teacher Creates Exam for Their Subject</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Teacher Logs In
                    </div>
                    <div class="step-desc">
                        Teacher logs in using their email and password created by admin
                    </div>
                    <div class="url-box">📍 /staff_login.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Teacher Views My Subjects
                    </div>
                    <div class="step-desc">
                        Teacher navigates to "My Subjects" dashboard
                    </div>
                    <div class="url-box">📍 /teacher/my_subjects.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Teacher Sees Assigned Subjects
                    </div>
                    <div class="step-desc">
                        The dashboard shows all subjects assigned by admin<br>
                        For each subject, there's a "Create Exam" button
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">4</span>
                        Teacher Creates Exam
                    </div>
                    <div class="step-desc">
                        • Click "Create Exam" button<br>
                        • Fill exam details (name, total marks, etc.)<br>
                        • Add questions to the exam<br>
                        • Publish the exam
                    </div>
                </div>
                
                <div class="success-box">
                    <h3>✅ Exam Created!</h3>
                    <p>
                        The exam is now available for students to take.<br>
                        The teacher's ID is automatically linked to this exam.
                    </p>
                </div>
            </div>
            
            <!-- Step 4: Student Takes Exam -->
            <div class="section">
                <h2>✏️ STEP 4: Student Takes the Exam</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Student Logs In
                    </div>
                    <div class="step-desc">
                        Student logs in to their account
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Student Takes Exam
                    </div>
                    <div class="step-desc">
                        Student finds the exam and takes it<br>
                        Answers questions and submits
                    </div>
                </div>
                
                <div class="success-box">
                    <h3>✅ Exam Submitted!</h3>
                    <p>
                        The exam results are automatically calculated and saved.<br>
                        The student's score and the teacher's information are recorded.
                    </p>
                </div>
            </div>
            
            <!-- Step 5: Student Views Results -->
            <div class="section">
                <h2>📊 STEP 5: Student Views Results with Teacher Info</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Student Navigates to Results Page
                    </div>
                    <div class="step-desc">
                        Student goes to "My Results" dashboard
                    </div>
                    <div class="url-box">📍 /student/my_results.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Student Sees Exam Result
                    </div>
                    <div class="step-desc">
                        Result shows:
                    </div>
                    <ul>
                        <li>✅ Exam name</li>
                        <li>✅ Subject and Trade</li>
                        <li>✅ Score obtained (e.g., 85/100)</li>
                        <li>✅ Percentage (e.g., 85%)</li>
                        <li>✅ Pass/Fail status</li>
                        <li><strong style="color: #667eea;">✅ TEACHER NAME and EMAIL</strong></li>
                        <li>✅ Teacher feedback (if provided)</li>
                        <li>✅ Attempt date and time</li>
                    </ul>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Student Sees Teacher Details
                    </div>
                    <div class="step-desc">
                        <span class="highlight">Teacher Name:</span> Muhammad Ali<br>
                        <span class="highlight">Email:</span> ali@school.com<br>
                        Student can contact teacher or see their feedback
                    </div>
                </div>
                
                <div class="success-box">
                    <h3>✅ Complete Workflow Finished!</h3>
                    <p>
                        Students can now see which teacher created the exam<br>
                        and have all the information they need about their results.
                    </p>
                </div>
            </div>
            
            <!-- Summary Table -->
            <div class="section">
                <h2>📋 Quick Reference: What Each Role Does</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Task</th>
                            <th>Page URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>👨‍💼 Admin</strong></td>
                            <td>Add new teacher accounts</td>
                            <td><code>/admin/add_teacher.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>👨‍💼 Admin</strong></td>
                            <td>Assign teachers to subjects</td>
                            <td><code>/admin/manage_subject_teachers.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>👨‍🏫 Teacher</strong></td>
                            <td>View assigned subjects</td>
                            <td><code>/teacher/my_subjects.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>👨‍🏫 Teacher</strong></td>
                            <td>Create exams for subjects</td>
                            <td><code>/teacher/exam_create.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>👨‍🎓 Student</strong></td>
                            <td>Take exams</td>
                            <td><code>/student/attempt_exam.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>👨‍🎓 Student</strong></td>
                            <td>View results with teacher info</td>
                            <td><code>/student/my_results.php</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Key Relationships -->
            <div class="section">
                <h2>🔗 Database Relationships</h2>
                
                <ul>
                    <li><strong>Users:</strong> Teachers, students, admins</li>
                    <li><strong>Roles:</strong> admin, teacher, student, moderator</li>
                    <li><strong>Subjects:</strong> Mathematics, English, etc.</li>
                    <li><strong>subject_teacher:</strong> Links teachers to subjects (Many-to-Many)</li>
                    <li><strong>Exams:</strong> Created by teachers, linked to subjects</li>
                    <li><strong>Exam Results:</strong> Student attempts, linked to teacher who created exam</li>
                </ul>
            </div>
            
            <!-- Troubleshooting -->
            <div class="section" style="background: #fff3e0; border-left-color: #ff9800;">
                <h2 style="color: #e65100;">❓ Troubleshooting</h2>
                
                <h3 style="color: #e65100; margin: 15px 0;">Problem: Teacher can't see assigned subjects</h3>
                <p style="color: #333; margin-bottom: 10px;">
                    <strong>Solution:</strong> Make sure you:<br>
                    • Added the teacher account using /admin/add_teacher.php<br>
                    • Assigned the teacher to a subject using /admin/manage_subject_teachers.php<br>
                    • Teacher has logged out and back in to refresh their dashboard
                </p>
                
                <h3 style="color: #e65100; margin: 15px 0;">Problem: Student can't see teacher info in results</h3>
                <p style="color: #333;">
                    <strong>Solution:</strong> Make sure the exam was created by the teacher<br>
                    (not created by admin). Teacher must create the exam through their dashboard.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
