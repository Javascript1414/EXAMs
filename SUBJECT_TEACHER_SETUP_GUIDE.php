<?php
/**
 * SUBJECT-TEACHER SYSTEM SETUP & USAGE GUIDE
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Subject-Teacher System Guide</title>
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
        .header p { opacity: 0.9; }
        
        .content { padding: 40px; }
        
        .section {
            margin: 30px 0;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #667eea;
        }
        
        .section h2 { color: #333; margin-bottom: 15px; font-size: 1.4em; }
        
        .flow-diagram {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 15px 0;
            border: 2px solid #667eea;
        }
        
        .step {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        .step-num { 
            display: inline-block;
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .step-title { font-weight: bold; color: #333; margin-bottom: 8px; }
        .step-desc { color: #666; }
        
        .code {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 10px 0;
            border-left: 3px solid #667eea;
        }
        
        .url-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
            font-family: monospace;
            color: #1565c0;
        }
        
        .success { color: #4caf50; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        
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
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-role { background: #c8e6c9; color: #2e7d32; }
        .badge-page { background: #bbdefb; color: #1565c0; }
        
        ul { margin-left: 20px; }
        li { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Subject-Teacher System</h1>
            <p>Complete workflow for assigning teachers to subjects</p>
        </div>
        
        <div class="content">
            <!-- Database Table -->
            <div class="section">
                <h2>🗄️ Database Table Created</h2>
                <p><span class="success">✅ subject_teacher table</span> has been created with the following structure:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>id</strong></td>
                            <td>BIGINT</td>
                            <td>Primary key (auto-increment)</td>
                        </tr>
                        <tr>
                            <td><strong>subject_id</strong></td>
                            <td>INT</td>
                            <td>Reference to subjects table</td>
                        </tr>
                        <tr>
                            <td><strong>teacher_id</strong></td>
                            <td>BIGINT</td>
                            <td>Reference to users table (teacher)</td>
                        </tr>
                        <tr>
                            <td><strong>created_by</strong></td>
                            <td>BIGINT</td>
                            <td>Admin who made the assignment</td>
                        </tr>
                        <tr>
                            <td><strong>created_at</strong></td>
                            <td>TIMESTAMP</td>
                            <td>Assignment date</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Complete Workflow -->
            <div class="section">
                <h2>🔄 Complete System Workflow</h2>
                
                <div class="flow-diagram">
                    <p style="color: #667eea; font-weight: bold; margin-bottom: 15px;">Admin → Teachers → Subjects → Exams → Students → Results</p>
                </div>
                
                <h3 style="margin-top: 25px; color: #333;">Step-by-Step Process:</h3>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Admin logs in and goes to manage teachers page
                    </div>
                    <div class="step-desc">
                        The admin user with role 'admin' navigates to the manage subject teachers page
                    </div>
                    <div class="url-box">📍 /admin/manage_subject_teachers.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Admin selects a Subject and a Teacher
                    </div>
                    <div class="step-desc">
                        Admin chooses which subject (e.g., "Mathematics") and which teacher to assign
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Admin clicks "Assign Teacher" button
                    </div>
                    <div class="step-desc">
                        The assignment is saved to the database and a record appears in "Current Assignments"
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">4</span>
                        Teacher logs in and views assigned subjects
                    </div>
                    <div class="step-desc">
                        Teacher navigates to "My Subjects" dashboard
                    </div>
                    <div class="url-box">📍 /teacher/my_subjects.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">5</span>
                        Teacher creates exams for their subjects
                    </div>
                    <div class="step-desc">
                        Teacher clicks on a subject and creates an exam with questions
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">6</span>
                        Students take the exam
                    </div>
                    <div class="step-desc">
                        Students attempt the exam created by the teacher
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">7</span>
                        Students view results with teacher information
                    </div>
                    <div class="step-desc">
                        Results page shows: exam name, score, percentage, <strong>teacher name and email</strong>, and feedback
                    </div>
                    <div class="url-box">📍 /student/my_results.php</div>
                </div>
            </div>
            
            <!-- User Interface Pages -->
            <div class="section">
                <h2>🖥️ User Interface Pages</h2>
                
                <h3 style="color: #333; margin: 15px 0;">ADMIN PAGES:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>URL</th>
                            <th>Features</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-role">Admin</span> Manage Teachers</td>
                            <td><code>/admin/manage_subject_teachers.php</code></td>
                            <td>
                                • Assign teachers to subjects<br>
                                • View all assignments<br>
                                • Remove assignments<br>
                                • View all teachers
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3 style="color: #333; margin: 20px 0 15px;">TEACHER PAGES:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>URL</th>
                            <th>Features</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-role">Teacher</span> My Subjects</td>
                            <td><code>/teacher/my_subjects.php</code></td>
                            <td>
                                • View assigned subjects<br>
                                • Create exams for each subject<br>
                                • View recent exams created
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3 style="color: #333; margin: 20px 0 15px;">STUDENT PAGES:</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>URL</th>
                            <th>Features</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-role">Student</span> My Results</td>
                            <td><code>/student/my_results.php</code></td>
                            <td>
                                • View all exam results<br>
                                • See teacher information<br>
                                • View scores and percentages<br>
                                • Read teacher feedback
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Key Features -->
            <div class="section">
                <h2>✨ Key Features</h2>
                <ul>
                    <li><strong>✅ One-to-Many:</strong> One teacher can teach multiple subjects</li>
                    <li><strong>✅ Many-to-Many:</strong> One subject can have multiple teachers</li>
                    <li><strong>✅ Unique Constraint:</strong> Same teacher cannot be assigned to same subject twice</li>
                    <li><strong>✅ Foreign Keys:</strong> All relationships properly enforced with CASCADE and RESTRICT</li>
                    <li><strong>✅ Audit Trail:</strong> Records who created each assignment</li>
                    <li><strong>✅ Teacher Tracking:</strong> Students can see which teacher created their exam</li>
                </ul>
            </div>
            
            <!-- Testing Instructions -->
            <div class="section">
                <h2>🧪 How to Test</h2>
                
                <h3 style="color: #333; margin: 15px 0;">1. Create a Teacher User:</h3>
                <div class="code">
User Role: teacher<br>
Full Name: "John Smith"<br>
Email: "john@school.com"
                </div>
                
                <h3 style="color: #333; margin: 15px 0;">2. Go to Admin Panel:</h3>
                <div class="url-box">📍 http://localhost/EXAMs/admin/manage_subject_teachers.php</div>
                
                <h3 style="color: #333; margin: 15px 0;">3. Assign Teacher to Subject:</h3>
                <ul>
                    <li>Select Subject: "Mathematics"</li>
                    <li>Select Teacher: "John Smith"</li>
                    <li>Click "Assign Teacher"</li>
                    <li>You'll see: <span class="success">✅ Teacher assigned successfully</span></li>
                </ul>
                
                <h3 style="color: #333; margin: 15px 0;">4. Login as Teacher:</h3>
                <ul>
                    <li>Go to <code>/teacher/my_subjects.php</code></li>
                    <li>You'll see "Mathematics" as an assigned subject</li>
                    <li>Click "Create Exam" button</li>
                </ul>
                
                <h3 style="color: #333; margin: 15px 0;">5. Create Exam:</h3>
                <ul>
                    <li>Fill in exam details</li>
                    <li>Add questions</li>
                    <li>Publish exam</li>
                </ul>
                
                <h3 style="color: #333; margin: 15px 0;">6. Login as Student:</h3>
                <ul>
                    <li>Take the exam</li>
                    <li>Go to <code>/student/my_results.php</code></li>
                    <li>You'll see: Teacher Name: "John Smith", Email: "john@school.com"</li>
                </ul>
            </div>
            
            <!-- Database Query Examples -->
            <div class="section">
                <h2>📊 Useful Database Queries</h2>
                
                <h3 style="color: #333; margin: 15px 0;">Get all subjects taught by a teacher:</h3>
                <div class="code">
SELECT s.subject_name, t.trade_name<br>
FROM subject_teacher st<br>
JOIN subjects s ON st.subject_id = s.id<br>
JOIN trades t ON s.trade_id = t.id<br>
WHERE st.teacher_id = 5;
                </div>
                
                <h3 style="color: #333; margin: 15px 0;">Get all teachers for a subject:</h3>
                <div class="code">
SELECT u.full_name, u.email<br>
FROM subject_teacher st<br>
JOIN users u ON st.teacher_id = u.id<br>
WHERE st.subject_id = 3;
                </div>
                
                <h3 style="color: #333; margin: 15px 0;">Get exam with teacher info:</h3>
                <div class="code">
SELECT e.exam_name, u.full_name as teacher_name,<br>
       s.subject_name, COUNT(eq.id) as question_count<br>
FROM exams e<br>
LEFT JOIN users u ON e.created_by = u.id<br>
LEFT JOIN subjects s ON e.subject_id = s.id<br>
LEFT JOIN exam_questions eq ON e.id = eq.exam_id<br>
GROUP BY e.id;
                </div>
            </div>
            
            <!-- Summary -->
            <div class="section" style="background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%); border-left-color: #4caf50;">
                <h2 style="color: #2e7d32;">✅ System Ready!</h2>
                <p>The complete Subject-Teacher system is now set up and ready to use:</p>
                <ul>
                    <li>✅ Database table <code>subject_teacher</code> created</li>
                    <li>✅ Admin panel for assigning teachers: <code>/admin/manage_subject_teachers.php</code></li>
                    <li>✅ Teacher dashboard to view subjects: <code>/teacher/my_subjects.php</code></li>
                    <li>✅ Student results page with teacher info: <code>/student/my_results.php</code></li>
                    <li>✅ Foreign key constraints properly configured</li>
                    <li>✅ All relationships properly enforced</li>
                </ul>
                <p style="margin-top: 20px;">You can now login as admin and start assigning teachers to subjects!</p>
            </div>
        </div>
    </div>
</body>
</html>
