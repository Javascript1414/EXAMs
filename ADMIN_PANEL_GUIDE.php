<?php
/**
 * Admin Panel Guide - How to Access and Use
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel Access Guide</title>
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
            width: 35px;
            height: 35px;
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.1em;
        }
        
        .step-title { font-weight: bold; color: #333; margin-bottom: 8px; }
        .step-desc { color: #666; }
        
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
        
        .warning { 
            background: #fff3e0;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #ff9800;
            color: #e65100;
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
        
        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .menu-item {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .menu-item h3 { margin-bottom: 10px; }
        .menu-item p { opacity: 0.9; font-size: 0.9em; }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px 10px 0;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 0.9em;
            margin: 5px 5px 5px 0;
        }
        
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        
        ul { margin-left: 20px; line-height: 1.8; }
        li { margin: 10px 0; }
        
        .highlight {
            background: #fffde7;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Admin Panel Access Guide</h1>
            <p>Complete guide to login and access admin features</p>
        </div>
        
        <div class="content">
            <!-- Quick Access -->
            <div class="section">
                <h2>⚡ Quick Links</h2>
                <p style="margin-bottom: 15px;">Direct links to important pages:</p>
                <a href="<?= 'http://localhost/EXAMs' ?>" class="btn">🏠 Home Page</a>
                <a href="<?= 'http://localhost/EXAMs/login.php' ?>" class="btn">🔑 Login Page</a>
                <a href="<?= 'http://localhost/EXAMs/admin/index.php' ?>" class="btn">📊 Admin Dashboard</a>
                <a href="<?= 'http://localhost/EXAMs/admin/manage_subject_teachers.php' ?>" class="btn">👨‍🏫 Manage Teachers</a>
            </div>
            
            <!-- Login Instructions -->
            <div class="section">
                <h2>🔑 Step 1: Login to Admin Account</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Go to the Login Page
                    </div>
                    <div class="url-box">📍 http://localhost/EXAMs/login.php</div>
                    <div class="step-desc" style="margin-top: 10px;">
                        Or click <a href="http://localhost/EXAMs/login.php" target="_blank" style="color: #667eea; font-weight: bold;">here</a> to login
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Enter Your Admin Credentials
                    </div>
                    <div class="step-desc">
                        <strong>Email:</strong> Your admin email address<br>
                        <strong>Password:</strong> Your admin password<br>
                        <br>
                        <span class="warning">⚠️ Make sure you're using the account with role "admin" or "superadmin"</span>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        Click "Login" Button
                    </div>
                    <div class="step-desc">
                        After successful login, you'll be redirected to the admin dashboard
                    </div>
                </div>
            </div>
            
            <!-- Admin Dashboard -->
            <div class="section">
                <h2>📊 Step 2: Access Admin Dashboard</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        You're now in the Admin Dashboard
                    </div>
                    <div class="url-box">📍 http://localhost/EXAMs/admin/index.php</div>
                    <div class="step-desc" style="margin-top: 10px;">
                        This is your main admin control center with all management options
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Look for the Left Sidebar Menu
                    </div>
                    <div class="step-desc">
                        The sidebar shows all available admin functions:<br>
                        • Users Management<br>
                        • Exams Management<br>
                        • Questions Management<br>
                        • Study Materials<br>
                        • Results & Analytics<br>
                        • And more...
                    </div>
                </div>
            </div>
            
            <!-- Manage Teachers -->
            <div class="section">
                <h2>👨‍🏫 Step 3: Manage Subject Teachers</h2>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">1</span>
                        Click "Manage Teachers" in Admin Dashboard
                    </div>
                    <div class="step-desc">
                        Look for "👨‍🏫 Manage Teachers" or similar option in the sidebar<br>
                        Or go directly to:
                    </div>
                    <div class="url-box">📍 http://localhost/EXAMs/admin/manage_subject_teachers.php</div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">2</span>
                        Assign Teacher to Subject
                    </div>
                    <div class="step-desc">
                        • Select a Subject from dropdown<br>
                        • Select a Teacher from dropdown<br>
                        • Click "Assign Teacher" button<br>
                        • ✅ Assignment saved successfully
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-title">
                        <span class="step-num">3</span>
                        View All Assignments
                    </div>
                    <div class="step-desc">
                        Scroll down to see all current teacher-subject assignments<br>
                        You can remove assignments by clicking the "Remove" button
                    </div>
                </div>
            </div>
            
            <!-- All Admin Pages -->
            <div class="section">
                <h2>🗂️ All Admin Pages Available</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>URL</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>📊 Dashboard</td>
                            <td><code>/admin/index.php</code></td>
                            <td>Main admin dashboard with statistics</td>
                        </tr>
                        <tr>
                            <td>👥 Users</td>
                            <td><code>/admin/users.php</code></td>
                            <td>Manage all system users</td>
                        </tr>
                        <tr>
                            <td>📝 Exams</td>
                            <td><code>/admin/exam_management.php</code></td>
                            <td>Manage exams and questions</td>
                        </tr>
                        <tr>
                            <td>❓ Questions</td>
                            <td><code>/admin/questions.php</code></td>
                            <td>Add/edit exam questions</td>
                        </tr>
                        <tr>
                            <td>📚 Study Materials</td>
                            <td><code>/admin/materials.php</code></td>
                            <td>Manage study materials</td>
                        </tr>
                        <tr>
                            <td>📊 Results</td>
                            <td><code>/admin/results.php</code></td>
                            <td>View exam results and analytics</td>
                        </tr>
                        <tr>
                            <td>🎓 Certificates</td>
                            <td><code>/admin/release_certificates.php</code></td>
                            <td>Release certificates to students</td>
                        </tr>
                        <tr>
                            <td>👨‍🏫 Manage Teachers</td>
                            <td><code>/admin/manage_subject_teachers.php</code></td>
                            <td>Assign teachers to subjects</td>
                        </tr>
                        <tr>
                            <td>📢 Notifications</td>
                            <td><code>/admin/notifications.php</code></td>
                            <td>Send notifications to users</td>
                        </tr>
                        <tr>
                            <td>⚙️ Settings</td>
                            <td><code>/admin/subjects.php</code></td>
                            <td>Manage subjects and trades</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Troubleshooting -->
            <div class="section">
                <h2>❌ Troubleshooting</h2>
                
                <h3 style="color: #f44336; margin: 15px 0;">Problem: "Access Denied"</h3>
                <div class="step">
                    <div class="step-title">Solution:</div>
                    <div class="step-desc">
                        • Make sure you're logged in<br>
                        • Make sure your user role is "admin" or "superadmin"<br>
                        • Try logging out and logging back in<br>
                        • Check that your account status is "active"
                    </div>
                </div>
                
                <h3 style="color: #f44336; margin: 15px 0;">Problem: "Page not found"</h3>
                <div class="step">
                    <div class="step-title">Solution:</div>
                    <div class="step-desc">
                        • Go to <code>/admin/index.php</code> first<br>
                        • Use the sidebar menu to navigate<br>
                        • Check that you're using the correct URL<br>
                        • Make sure your Apache/XAMPP server is running
                    </div>
                </div>
                
                <h3 style="color: #f44336; margin: 15px 0;">Problem: "Database connection error"</h3>
                <div class="step">
                    <div class="step-title">Solution:</div>
                    <div class="step-desc">
                        • Start XAMPP Control Panel<br>
                        • Start Apache<br>
                        • Start MySQL<br>
                        • Try refreshing the page
                    </div>
                </div>
            </div>
            
            <!-- Quick Reference -->
            <div class="section" style="background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%); border-left-color: #4caf50;">
                <h2 style="color: #2e7d32;">✅ Admin Account Requirements</h2>
                <ul>
                    <li>✅ User role must be <span class="highlight">"admin"</span> or <span class="highlight">"superadmin"</span></li>
                    <li>✅ Account status must be <span class="highlight">"active"</span></li>
                    <li>✅ Email must be verified</li>
                    <li>✅ Password must be correctly entered</li>
                </ul>
            </div>
            
            <!-- Still Having Issues? -->
            <div class="section" style="background: #fff3e0; border-left-color: #ff9800;">
                <h2 style="color: #e65100;">❓ Still Having Issues?</h2>
                <p style="margin: 15px 0;">
                    <strong>Check the following:</strong>
                </p>
                <ul>
                    <li>Is XAMPP running? (Apache and MySQL should be started)</li>
                    <li>Can you access the homepage? Go to <code>http://localhost/EXAMs</code></li>
                    <li>Can you login? Go to <code>http://localhost/EXAMs/login.php</code></li>
                    <li>Are you using correct credentials?</li>
                    <li>Check browser console for any error messages (Press F12)</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
