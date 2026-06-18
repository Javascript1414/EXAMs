<?php
/**
 * Complete Setup & Testing Guide
 * Step-by-step to add teachers
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Setup Guide - Add Teachers</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { margin-bottom: 10px; font-size: 2em; }
        .content { padding: 40px; }
        .step { 
            background: #f9f9f9;
            border-left: 5px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .step h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .step p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 10px;
        }
        .code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px 10px 0;
            transition: all 0.3s;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .success {
            background: #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            border-left: 4px solid #4caf50;
            border-radius: 6px;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover { background: #f9f9f9; }
        .highlight {
            background: #fff9e6;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ffd700;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Complete Setup & Testing Guide</h1>
            <p>Everything needed to add teachers to the system</p>
        </div>
        
        <div class="content">
            <div class="success">
                <strong>🎉 Good News!</strong> The teacher role has been created successfully in the database (ID: 5). Now you just need to login and add teachers!
            </div>
            
            <!-- Step 1: Check Admin Account -->
            <div class="step">
                <h2>Step 1️⃣: Check Admin Account</h2>
                <p>First, verify that an admin account exists in the database:</p>
                <button class="button" onclick="window.open('check_admin_account.php', '_blank')">Check Admin Account</button>
                
                <p style="margin-top: 15px;"><strong>Expected:</strong> You should see at least one admin user listed.</p>
                <p style="color: #f57f17;"><strong>If no admin found:</strong> You'll get instructions to create one.</p>
            </div>
            
            <!-- Step 2: Login as Admin -->
            <div class="step">
                <h2>Step 2️⃣: Login as Admin</h2>
                <p>Login to the staff/admin portal:</p>
                <button class="button" onclick="window.open('staff_login.php', '_blank')">Go to Staff Login</button>
                
                <p style="margin-top: 15px;"><strong>Login credentials:</strong></p>
                <table>
                    <tr>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                    </tr>
                    <tr>
                        <td>admin@example.com</td>
                        <td>password</td>
                        <td>Admin/SuperAdmin</td>
                    </tr>
                </table>
                
                <div class="highlight">
                    <strong>Note:</strong> Use the credentials for your admin account. If you don't know the password, you can reset it or create a new admin account.
                </div>
            </div>
            
            <!-- Step 3: Access Add Teacher Page -->
            <div class="step">
                <h2>Step 3️⃣: Access Add Teacher Page</h2>
                <p>After logging in as admin, click on the "Add Teacher" option in the left sidebar.</p>
                
                <p style="margin-top: 15px;"><strong>Direct URL (after login):</strong></p>
                <div class="code">
                    http://localhost/EXAMs/admin/add_teacher.php
                </div>
                
                <p style="margin-top: 15px;">Or use this button:</p>
                <button class="button" onclick="window.open('admin/add_teacher.php', '_blank')">Go to Add Teacher Page</button>
                
                <p style="margin-top: 15px; color: #f57f17;"><strong>Important:</strong> This button will only work after you're logged in as admin.</p>
            </div>
            
            <!-- Step 4: Add a Teacher -->
            <div class="step">
                <h2>Step 4️⃣: Add a Teacher</h2>
                <p>Once you're on the Add Teacher page, fill in the form:</p>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li><strong>👤 Full Name:</strong> Teacher's full name</li>
                    <li><strong>📧 Email:</strong> Teacher's email (must be unique)</li>
                    <li><strong>📱 Phone:</strong> Teacher's phone number</li>
                    <li><strong>🔑 Password:</strong> Set a password (min 6 characters)</li>
                    <li><strong>💼 Trade:</strong> Select the trade/department</li>
                </ul>
                
                <p style="margin-top: 15px;">Click "Add Teacher" button to create the account.</p>
                
                <div class="success">
                    ✅ <strong>Teacher will be:</strong>
                    <ul style="margin-left: 20px; margin-top: 10px;">
                        <li>Created with role: Teacher</li>
                        <li>Email verified: Yes</li>
                        <li>Status: Approved</li>
                        <li>Ready to create exams immediately</li>
                    </ul>
                </div>
            </div>
            
            <!-- Step 5: Assign Subjects (Optional) -->
            <div class="step">
                <h2>Step 5️⃣: Assign Teachers to Subjects (Optional)</h2>
                <p>After adding a teacher, you can assign subjects to them:</p>
                <button class="button" onclick="window.open('admin/manage_subject_teachers.php', '_blank')">Go to Manage Teachers</button>
                
                <p style="margin-top: 15px;"><strong>On this page, you can:</strong></p>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>Select a subject</li>
                    <li>Select a teacher</li>
                    <li>Click "Assign" to link them</li>
                </ul>
                
                <div class="highlight">
                    <strong>Note:</strong> Teachers can also see and create exams for subjects even without explicit assignment (optional step).
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="step" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); border-left-color: #4caf50;">
                <h2>⚡ Quick Navigation Links</h2>
                <p style="margin-bottom: 15px;">Click to navigate to key pages:</p>
                
                <button class="button" onclick="window.open('staff_login.php', '_blank')">🔐 Staff Login</button>
                <button class="button" onclick="window.open('admin/index.php', '_blank')">📊 Admin Dashboard</button>
                <button class="button" onclick="window.open('admin/add_teacher.php', '_blank')">👨‍🏫 Add Teacher</button>
                <button class="button" onclick="window.open('admin/manage_subject_teachers.php', '_blank')">📚 Manage Teachers</button>
                <button class="button" onclick="window.open('check_admin_account.php', '_blank')">✓ Check Admin Account</button>
            </div>
            
            <!-- Troubleshooting -->
            <div class="step" style="background: linear-gradient(135deg, #ffe0b2 0%, #ffe0b2 100%); border-left-color: #ff9800;">
                <h2>🔧 Troubleshooting</h2>
                
                <p><strong>Q: Access Denied error?</strong></p>
                <p style="margin-left: 20px; color: #666;">A: Make sure you're logged in as admin first. Login at staff_login.php, then try again.</p>
                
                <p style="margin-top: 15px;"><strong>Q: Can't find the Add Teacher option in sidebar?</strong></p>
                <p style="margin-left: 20px; color: #666;">A: Refresh the page (F5). The sidebar should show after login.</p>
                
                <p style="margin-top: 15px;"><strong>Q: Teacher role not found error?</strong></p>
                <p style="margin-left: 20px; color: #666;">A: The role should be created now. If error persists, visit fix_teacher_role.php to create it manually.</p>
                
                <p style="margin-top: 15px;"><strong>Q: Can't login?</strong></p>
                <p style="margin-left: 20px; color: #666;">A: Make sure your admin account exists. Check with admin@example.com / password</p>
            </div>
            
            <!-- Summary -->
            <div class="step" style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border-left-color: #2196f3;">
                <h2>📋 Summary</h2>
                <p><strong>What's been done:</strong></p>
                <ul style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                    <li>✅ Teacher role created in database (ID: 5)</li>
                    <li>✅ Add Teacher page created (/admin/add_teacher.php)</li>
                    <li>✅ Manage Teachers page created (/admin/manage_subject_teachers.php)</li>
                    <li>✅ Sidebar menu updated with new options</li>
                    <li>✅ All authorization checks fixed</li>
                </ul>
                
                <p style="margin-top: 20px;"><strong style="color: #1565c0;">Next: Login as admin and start adding teachers!</strong></p>
            </div>
        </div>
    </div>
</body>
</html>
