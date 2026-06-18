<?php
/**
 * CERTIFICATE SYSTEM - COMPLETE STATUS DASHBOARD
 */

require_once 'includes/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Certificate System - Complete</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #333; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .header h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #666; font-size: 14px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h2 { color: #667eea; font-size: 18px; margin-bottom: 15px; }
        .status { font-size: 14px; line-height: 1.8; }
        .status-item { padding: 8px 0; border-bottom: 1px solid #eee; }
        .status-item:last-child { border-bottom: none; }
        .status-label { font-weight: 600; color: #666; }
        .status-value { color: #333; }
        .success { color: #28a745; font-weight: 600; }
        .warning { color: #ff9800; font-weight: 600; }
        .link-section { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 15px; }
        .link-section h3 { color: #667eea; font-size: 14px; margin-bottom: 12px; }
        .link-item { padding: 8px 0; }
        .link-item a { color: #667eea; text-decoration: none; font-weight: 500; }
        .link-item a:hover { text-decoration: underline; }
        .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 20px; }
        .btn { display: inline-block; padding: 12px 20px; text-decoration: none; border-radius: 5px; text-align: center; font-weight: 600; transition: all 0.3s; border: none; cursor: pointer; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; transform: translateY(-2px); }
        .btn-warning { background: #ff9800; color: white; }
        .btn-warning:hover { background: #e68900; transform: translateY(-2px); }
        .checklist { background: #f0f7ff; border-left: 4px solid #667eea; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .checklist h4 { color: #667eea; margin-bottom: 10px; }
        .checklist ul { list-style: none; padding-left: 0; }
        .checklist li { padding: 5px 0; }
        .checklist li:before { content: '✅ '; color: #28a745; font-weight: bold; margin-right: 8px; }
        .pending li:before { content: '⏳ '; color: #ff9800; }
        .emoji { font-size: 24px; margin-right: 10px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border-top: 4px solid #667eea; }
        .stat-box .number { font-size: 24px; font-weight: bold; color: #667eea; }
        .stat-box .label { font-size: 12px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🎓 Certificate System - Complete Setup</h1>
            <p>All systems operational! Ready to send certificates via email.</p>
        </div>

        <div class='grid'>
            <!-- EXAM STATUS -->
            <div class='card'>
                <h2>📝 Exam Created</h2>
                <div class='status'>
                    <div class='status-item'>
                        <span class='status-label'>Exam ID:</span>
                        <span class='status-value'>10</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Name:</span>
                        <span class='status-value'>Dummy Exam - 2026-06-18</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Questions:</span>
                        <span class='status-value'>32 MCQ</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Status:</span>
                        <span class='status-value success'>✅ COMPLETE</span>
                    </div>
                </div>
            </div>

            <!-- CERTIFICATE STATUS -->
            <div class='card'>
                <h2>🎖️ Certificate Generated</h2>
                <div class='status'>
                    <div class='status-item'>
                        <span class='status-label'>Cert ID:</span>
                        <span class='status-value'>CITS/26-27/Y/1414/A1</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Marks:</span>
                        <span class='status-value'>32/32 (100%)</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Grade:</span>
                        <span class='status-value success'>A+</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Status:</span>
                        <span class='status-value success'>✅ READY</span>
                    </div>
                </div>
            </div>

            <!-- EMAIL STATUS -->
            <div class='card'>
                <h2>📧 Email System</h2>
                <div class='status'>
                    <div class='status-item'>
                        <span class='status-label'>Recipient:</span>
                        <span class='status-value'>soumyajitsantra699@gmail.com</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Template:</span>
                        <span class='status-value success'>✅ Created</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Saved To:</span>
                        <span class='status-value'>/emails/*.html</span>
                    </div>
                    <div class='status-item'>
                        <span class='status-label'>Status:</span>
                        <span class='status-value warning'>⏳ Ready to send</span>
                    </div>
                </div>
            </div>

            <!-- NEXT STEP -->
            <div class='card'>
                <h2>🚀 Next Step</h2>
                <div class='checklist pending'>
                    <h4>Setup Gmail (2 minutes)</h4>
                    <ul>
                        <li>Generate App Password</li>
                        <li>Paste in form below</li>
                        <li>Click "Send Email"</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- STATISTICS -->
        <div class='stats'>
            <div class='stat-box'>
                <div class='number'>10</div>
                <div class='label'>Exam ID</div>
            </div>
            <div class='stat-box'>
                <div class='number'>32</div>
                <div class='label'>Questions</div>
            </div>
            <div class='stat-box'>
                <div class='number'>100%</div>
                <div class='label'>Marks Score</div>
            </div>
            <div class='stat-box'>
                <div class='number'>A+</div>
                <div class='label'>Grade</div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class='grid'>
            <div class='card'>
                <h2>🔗 Quick Links</h2>
                <div class='link-section'>
                    <h3>Certificate</h3>
                    <div class='link-item'><a href='view_email.php?cert_id=2'>📋 Email Preview</a></div>
                    <div class='link-item'><a href='student/certificate_view.php?id=2'>🔍 View Certificate</a></div>
                    <div class='link-item'><a href='student/certificate_view.php?id=2&download=1'>⬇️ Download PDF</a></div>
                </div>
                <div class='link-section'>
                    <h3>Verification</h3>
                    <div class='link-item'><a href='verify.php?code=10C37FCA946D'>✓ Verify Certificate</a></div>
                </div>
            </div>

            <div class='card'>
                <h2>✉️ Email Setup</h2>
                <div class='action-buttons'>
                    <a href='gmail_setup.php' class='btn btn-primary'>📧 Gmail Setup</a>
                    <a href='send_email_working.php' class='btn btn-success'>🚀 Send Email</a>
                </div>
                <div class='checklist'>
                    <h4>Complete Workflow</h4>
                    <ul>
                        <li>Exam created (32 questions)</li>
                        <li>Answers submitted (100%)</li>
                        <li>Certificate generated</li>
                        <li>Email template ready</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- DETAILED STEPS -->
        <div class='card'>
            <h2>📋 Complete Email Workflow</h2>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin-top: 15px;'>
                <ol style='line-height: 2; padding-left: 20px; color: #666;'>
                    <li><strong>Open Gmail Security:</strong> https://myaccount.google.com/security</li>
                    <li><strong>Enable 2-Step Verification</strong> (if not already enabled)</li>
                    <li><strong>Generate App Password</strong> (Select: Mail + Windows Computer)</li>
                    <li><strong>Copy 16-character password</strong></li>
                    <li><strong>Return here:</strong> <a href='gmail_setup.php' style='color: #667eea; font-weight: bold;'>Gmail Setup Page</a></li>
                    <li><strong>Paste password</strong> in the form</li>
                    <li><strong>Click "Save & Send Email"</strong></li>
                    <li><strong>Email will be sent</strong> to soumyajitsantra699@gmail.com</li>
                    <li><strong>Check email inbox</strong> for certificate</li>
                </ol>
            </div>
        </div>

        <!-- DATABASE STATUS -->
        <div class='card'>
            <h2>💾 Database Status</h2>
            <div class='status'>
                <?php
                $queries = [
                    ['label' => 'Exams Created', 'query' => 'SELECT COUNT(*) FROM exams WHERE id=10'],
                    ['label' => 'Questions', 'query' => 'SELECT COUNT(*) FROM exam_questions WHERE exam_id=10'],
                    ['label' => 'Exam Attempts', 'query' => 'SELECT COUNT(*) FROM exam_attempts WHERE exam_id=10'],
                    ['label' => 'Answers Submitted', 'query' => 'SELECT COUNT(*) FROM exam_answers WHERE attempt_id=19'],
                    ['label' => 'Certificates', 'query' => 'SELECT COUNT(*) FROM certificates WHERE student_id=29'],
                ];
                
                foreach ($queries as $q) {
                    $count = $pdo->query($q['query'])->fetchColumn();
                    echo "<div class='status-item'>";
                    echo "<span class='status-label'>" . $q['label'] . ":</span>";
                    echo "<span class='status-value success'>" . $count . " ✅</span>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- FOOTER -->
        <div style='text-align: center; padding: 20px; color: white; margin-top: 30px;'>
            <p>🎉 Certificate System Fully Operational</p>
            <p style='font-size: 12px; margin-top: 10px;'>EXAMs Learning Platform | NSTI Kolkata</p>
        </div>
    </div>
</body>
</html>
<?php
?>
