<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('student'); // Blocks access and redirects if not a student

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get time-based greeting
$hour = date('H');
if ($hour < 12) {
    $greeting = "Good Morning";
    $greeting_icon = "☀️";
} elseif ($hour < 18) {
    $greeting = "Good Afternoon";
    $greeting_icon = "🌤️";
} else {
    $greeting = "Good Evening";
    $greeting_icon = "🌙";
}
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/student/dashboard.css">

<div class="container-fluid px-0 dashboard-container">
    <!-- Enhanced Welcome Section -->
    <div class="welcome-section-enhanced">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <div class="greeting-header">
                    <span class="greeting-emoji"><?= $greeting_icon ?></span>
                    <div>
                        <h1 class="greeting-title mb-1"><?= $greeting ?>, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Student') ?>!</h1>
                        <p class="greeting-subtitle text-muted mb-0">Ready to continue your learning journey?</p>
                    </div>
                </div>
            </div>
            <div class="col-auto d-none d-md-block">
                <div class="date-time-widget">
                    <div class="date-display"><?= date('D, M d') ?></div>
                    <div class="time-display" id="time-display"></div>
                </div>
            </div>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Quick Actions -->
    <div class="quick-actions-section mb-4">
        <div class="quick-actions-grid">
            <a href="./courses.php" class="quick-action-btn quick-action-primary">
                <i data-lucide="book-open"></i>
                <span>My Courses</span>
            </a>
            <a href="./exams.php" class="quick-action-btn quick-action-warning">
                <i data-lucide="clipboard-list"></i>
                <span>Take Exam</span>
            </a>
            <a href="./materials.php" class="quick-action-btn quick-action-info">
                <i data-lucide="file-text"></i>
                <span>Study Materials</span>
            </a>
            <a href="./certificates.php" class="quick-action-btn quick-action-success">
                <i data-lucide="award"></i>
                <span>Certificates</span>
            </a>
        </div>
    </div>
    
    <!-- Quick Links Section -->
    <div class="quick-links-section mb-4">
        <h6 class="quick-links-title">Quick Links</h6>
        <div class="quick-links-grid">
            <a href="./assignments.php" class="quick-link-item">
                <i data-lucide="list-check"></i>
                <span>My Assignments</span>
            </a>
            <a href="./grades.php" class="quick-link-item">
                <i data-lucide="bar-chart-3"></i>
                <span>My Grades</span>
            </a>
            <a href="./downloads.php" class="quick-link-item">
                <i data-lucide="download"></i>
                <span>Downloads</span>
            </a>
            <a href="./schedule.php" class="quick-link-item">
                <i data-lucide="calendar"></i>
                <span>Schedule</span>
            </a>
            <a href="./messages.php" class="quick-link-item">
                <i data-lucide="mail"></i>
                <span>Messages</span>
            </a>
            <a href="./support.php" class="quick-link-item">
                <i data-lucide="help-circle"></i>
                <span>Support</span>
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card stat-card-primary shadow-sm border-0">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="book-open"></i>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Enrolled Courses</p>
                        <h2 class="stat-number">4</h2>
                        <p class="stat-change"><i data-lucide="trending-up" class="icon-sm"></i> 2 in progress</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card stat-card-warning shadow-sm border-0">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="clock"></i>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Upcoming Exams</p>
                        <h2 class="stat-number">1</h2>
                        <p class="stat-change"><i data-lucide="alert-circle" class="icon-sm"></i> In 5 days</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-6">
            <div class="card stat-card stat-card-success shadow-sm border-0">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrapper">
                        <i data-lucide="award"></i>
                    </div>
                    <div class="stat-details">
                        <p class="stat-label">Certificates Earned</p>
                        <h2 class="stat-number">2</h2>
                        <p class="stat-change"><i data-lucide="check-circle" class="icon-sm"></i> All verified</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Courses Section -->
    <div class="section-card mb-4">
        <div class="section-header">
            <h5 class="section-title">My Courses</h5>
            <a href="?page=courses" class="link-primary">View All →</a>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="course-card">
                    <div class="course-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="course-badge">In Progress</div>
                        <div class="course-progress-bar">
                            <div class="progress-fill" style="width: 65%;"></div>
                        </div>
                    </div>
                    <div class="course-body">
                        <h6 class="course-title">Web Development Fundamentals</h6>
                        <p class="course-meta">8 modules • 24 lessons</p>
                        <div class="course-progress-text">65% Complete</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="course-card">
                    <div class="course-header" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <div class="course-badge">In Progress</div>
                        <div class="course-progress-bar">
                            <div class="progress-fill" style="width: 42%;"></div>
                        </div>
                    </div>
                    <div class="course-body">
                        <h6 class="course-title">Digital Marketing Essentials</h6>
                        <p class="course-meta">10 modules • 32 lessons</p>
                        <div class="course-progress-text">42% Complete</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="section-card mb-4">
        <div class="section-header">
            <h5 class="section-title">Recent Activity</h5>
        </div>
        <div class="activity-list">
            <div class="activity-item">
                <div class="activity-icon primary">
                    <i data-lucide="check-circle"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-title">Completed Module: "JavaScript Basics"</p>
                    <p class="activity-time">2 hours ago</p>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon warning">
                    <i data-lucide="alert-circle"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-title">Exam scheduled: "PHP Advanced Concepts"</p>
                    <p class="activity-time">1 day ago</p>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon success">
                    <i data-lucide="award"></i>
                </div>
                <div class="activity-content">
                    <p class="activity-title">Certificate earned: "Frontend Developer Pro"</p>
                    <p class="activity-time">3 days ago</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Progress Card -->
    <div class="card progress-card p-4 shadow-sm border-0">
        <div class="d-flex align-items-center mb-3">
            <h5 class="fw-bold mb-0 text-dark">Learning Progress</h5>
            <span class="ms-auto badge bg-primary">On Track</span>
        </div>
        <div class="progress-overview mb-3">
            <div class="progress-item">
                <div class="progress-label">Overall Progress</div>
                <div class="progress-bar-wrapper">
                    <div class="progress-bar-custom">
                        <div class="progress-bar-fill" style="width: 54%;"></div>
                    </div>
                    <span class="progress-percentage">54%</span>
                </div>
            </div>
        </div>
        <div class="alert alert-light progress-info-alert text-muted d-flex align-items-center">
            <i data-lucide="info" class="me-2 text-primary"></i>
            <span>Keep up the great work! Complete 2 more modules to unlock the next course tier.</span>
        </div>
    </div>
</div>

<script>
// Update time display
function updateTime() {
    const now = new Date();
    const timeDisplay = document.getElementById('time-display');
    if (timeDisplay) {
        timeDisplay.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }
}
updateTime();
setInterval(updateTime, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>