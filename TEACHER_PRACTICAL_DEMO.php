<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Practical Submissions - Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f7fafc; font-family: 'Segoe UI'; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; min-height: 100vh; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 1rem 1.5rem; transition: 0.3s; }
        .sidebar a:hover { background: rgba(255,255,255,0.1); padding-left: 2rem; }
        .main-content { padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: 700; color: #667eea; }
        .stat-label { color: #718096; font-size: 0.9rem; margin-top: 0.5rem; }
        .section-title { color: #667eea; font-weight: 700; border-bottom: 3px solid #667eea; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .practical-group { background: white; padding: 1.5rem; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .practical-title { color: #667eea; font-weight: 700; margin-bottom: 1rem; border-left: 4px solid #667eea; padding-left: 1rem; }
        .submission-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 1rem; 
            border-bottom: 1px solid #e2e8f0;
            gap: 1rem;
        }
        .submission-row:last-child { border-bottom: none; }
        .submission-row:hover { background: #f7fafc; }
        .submission-info { flex-grow: 1; }
        .student-name { font-weight: 700; color: #2d3748; }
        .submission-meta { font-size: 0.9rem; color: #718096; margin-top: 0.3rem; }
        .file-link { color: #667eea; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .file-link:hover { text-decoration: underline; }
        .badge-pending { background: #fef5e7; color: #f39c12; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .badge-marked { background: #d5f4e6; color: #27ae60; padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .btn-mark { background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-mark:hover { background: #5568d3; }
        .modal-overlay { 
            display: none; 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0,0,0,0.7); 
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box { 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; 
            max-width: 600px; 
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 1rem;
        }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { font-weight: 600; color: #2d3748; margin-bottom: 0.5rem; display: block; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; 
            padding: 0.75rem; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-family: inherit;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .file-preview { 
            background: #f0f7ff; 
            padding: 1rem; 
            border-radius: 6px; 
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }
        .btn-save { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600; 
            width: 100%;
            margin-top: 1rem;
        }
        .btn-save:hover { opacity: 0.9; }
        .marks-display { 
            background: #e7f3ff; 
            padding: 0.5rem 1rem; 
            border-radius: 6px; 
            display: inline-block; 
            color: #0066cc; 
            font-weight: 600;
        }
        .success-message { 
            background: #d5f4e6; 
            color: #27ae60; 
            padding: 1rem; 
            border-radius: 6px; 
            margin-bottom: 1rem;
            display: none;
        }
        .success-message.active { display: block; }
    </style>
</head>
<body>
    <div style="display: flex;">
        <!-- Sidebar -->
        <div class="sidebar" style="width: 250px;">
            <h4 style="padding: 1.5rem;">👨‍🏫 Teacher Panel</h4>
            <a href="#"><i class="fas fa-home me-2"></i>Dashboard</a>
            <a href="#" style="background: rgba(255,255,255,0.2); border-left: 4px solid white;"><i class="fas fa-file-upload me-2"></i>Practical Submissions</a>
            <a href="#"><i class="fas fa-chart-bar me-2"></i>Results</a>
            <a href="#"><i class="fas fa-users me-2"></i>My Students</a>
            <a href="#" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.2);"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" style="flex-grow: 1;">
            <h2 style="color: #667eea; margin-bottom: 2rem;"><i class="fas fa-folder-open me-2"></i>Practical Submissions</h2>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Total Practicals</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Total Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">2</div>
                    <div class="stat-label">Pending Marks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">Marked</div>
                </div>
            </div>
            
            <!-- Practical Groups -->
            
            <!-- Practical 1 -->
            <div class="practical-group">
                <div class="practical-title">
                    <i class="fas fa-flask me-2"></i>Networking Fundamentals - Practical Lab
                </div>
                
                <!-- Submission 1 -->
                <div class="submission-row">
                    <div class="submission-info">
                        <div class="student-name">Ahmed Khan</div>
                        <div class="submission-meta">
                            📤 Submitted: 2 days ago | 
                            <a href="#" class="file-link" onclick="downloadFile(); return false;">
                                <i class="fas fa-download"></i> cisco_project.pkt (250 KB)
                            </a>
                        </div>
                    </div>
                    <div><span class="badge-pending">Pending</span></div>
                    <button class="btn-mark" onclick="openMarkModal('Ahmed Khan', 'cisco_project.pkt', 'Practical 1')">Mark</button>
                </div>
                
                <!-- Submission 2 -->
                <div class="submission-row">
                    <div class="submission-info">
                        <div class="student-name">Fatima Ali</div>
                        <div class="submission-meta">
                            📤 Submitted: 3 days ago | 
                            <a href="#" class="file-link" onclick="downloadFile(); return false;">
                                <i class="fas fa-download"></i> network_config.pkt (180 KB)
                            </a>
                        </div>
                    </div>
                    <div><span class="badge-marked">✓ 42/50 marks</span></div>
                    <button class="btn-mark" onclick="openMarkModal('Fatima Ali', 'network_config.pkt', 'Practical 1', true)">Edit Marks</button>
                </div>
            </div>
            
            <!-- Practical 2 -->
            <div class="practical-group">
                <div class="practical-title">
                    <i class="fas fa-code me-2"></i>Database Design - SQL Project
                </div>
                
                <!-- Submission 3 -->
                <div class="submission-row">
                    <div class="submission-info">
                        <div class="student-name">Hassan Ahmed</div>
                        <div class="submission-meta">
                            📤 Submitted: 1 day ago | 
                            <a href="#" class="file-link" onclick="downloadFile(); return false;">
                                <i class="fas fa-download"></i> database_project.sql (95 KB)
                            </a>
                        </div>
                    </div>
                    <div><span class="badge-pending">Pending</span></div>
                    <button class="btn-mark" onclick="openMarkModal('Hassan Ahmed', 'database_project.sql', 'Practical 2')">Mark</button>
                </div>
                
                <!-- Submission 4 -->
                <div class="submission-row">
                    <div class="submission-info">
                        <div class="student-name">Sara Khan</div>
                        <div class="submission-meta">
                            📤 Submitted: 2 days ago | 
                            <a href="#" class="file-link" onclick="downloadFile(); return false;">
                                <i class="fas fa-download"></i> sql_queries.sql (120 KB)
                            </a>
                        </div>
                    </div>
                    <div><span class="badge-marked">✓ 48/50 marks</span></div>
                    <button class="btn-mark" onclick="openMarkModal('Sara Khan', 'sql_queries.sql', 'Practical 2', true)">Edit Marks</button>
                </div>
            </div>
            
            <!-- Practical 3 -->
            <div class="practical-group">
                <div class="practical-title">
                    <i class="fas fa-laptop-code me-2"></i>Web Development - Portfolio Project
                </div>
                
                <!-- Submission 5 -->
                <div class="submission-row">
                    <div class="submission-info">
                        <div class="student-name">Muhammad Ali</div>
                        <div class="submission-meta">
                            📤 Submitted: 4 hours ago | 
                            <a href="#" class="file-link" onclick="downloadFile(); return false;">
                                <i class="fas fa-download"></i> portfolio.zip (5.2 MB)
                            </a>
                        </div>
                    </div>
                    <div><span class="badge-pending">Pending</span></div>
                    <button class="btn-mark" onclick="openMarkModal('Muhammad Ali', 'portfolio.zip', 'Practical 3')">Mark</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mark Modal -->
    <div class="modal-overlay" id="markModal">
        <div class="modal-box">
            <div class="modal-header">
                <h4 style="color: #667eea; margin: 0;">Assign Marks</h4>
                <button class="modal-close" onclick="closeMarkModal()">✕</button>
            </div>
            
            <div class="success-message" id="successMessage">
                ✅ Marks saved successfully! Certificate will be generated automatically.
            </div>
            
            <!-- File Preview -->
            <div class="file-preview">
                <strong style="color: #667eea;">📁 File:</strong> <span id="fileName"></span><br>
                <strong style="color: #667eea;">👤 Student:</strong> <span id="studentName"></span><br>
                <strong style="color: #667eea;">📋 Practical:</strong> <span id="practicalName"></span>
            </div>
            
            <!-- Form -->
            <form id="markForm">
                <div class="form-group">
                    <label>Student Name (Display Only)</label>
                    <input type="text" id="studentNameDisplay" readonly>
                </div>
                
                <div class="form-group">
                    <label>Marks Obtained (0-50)</label>
                    <input type="number" id="marksInput" min="0" max="50" step="0.5" placeholder="Enter marks (e.g., 42)" required>
                </div>
                
                <div class="form-group">
                    <label>Result Status</label>
                    <select id="resultStatus" required>
                        <option value="">Select status...</option>
                        <option value="pass">✓ Pass</option>
                        <option value="fail">✗ Fail</option>
                        <option value="pending_review">⏳ Pending Review</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Feedback (Optional)</label>
                    <textarea id="feedback" placeholder="Add your feedback for the student..."></textarea>
                </div>
                
                <button type="submit" class="btn-save">💾 Save Marks</button>
            </form>
        </div>
    </div>
    
    <script>
        function openMarkModal(studentName, fileName, practicalName, isEdit = false) {
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('fileName').textContent = fileName;
            document.getElementById('practicalName').textContent = practicalName;
            document.getElementById('studentNameDisplay').value = studentName;
            
            if (isEdit) {
                document.getElementById('marksInput').value = '42';
                document.getElementById('resultStatus').value = 'pass';
                document.getElementById('feedback').value = 'Great work on the project!';
            } else {
                document.getElementById('marksInput').value = '';
                document.getElementById('resultStatus').value = '';
                document.getElementById('feedback').value = '';
            }
            
            document.getElementById('markModal').classList.add('active');
        }
        
        function closeMarkModal() {
            document.getElementById('markModal').classList.remove('active');
            document.getElementById('successMessage').classList.remove('active');
        }
        
        function downloadFile() {
            alert('File downloaded! In the actual system, this would download the student\'s submitted file.');
            return false;
        }
        
        document.getElementById('markForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const marks = document.getElementById('marksInput').value;
            const status = document.getElementById('resultStatus').value;
            const feedback = document.getElementById('feedback').value;
            
            console.log('Saving marks:', { marks, status, feedback });
            
            // Show success message
            document.getElementById('successMessage').classList.add('active');
            
            // Auto close after 2 seconds
            setTimeout(() => {
                closeMarkModal();
            }, 2000);
        });
        
        // Close modal when clicking outside
        document.getElementById('markModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMarkModal();
            }
        });
    </script>
</body>
</html>
