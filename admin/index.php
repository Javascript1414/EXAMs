<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Explicit role check for Admin routing
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Get current time for greeting
$hour = (int)date('H');
if ($hour >= 22 || $hour == 0) {
    $greeting = "Good Night";
} elseif ($hour >= 1 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 16) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

$admin_name = $_SESSION['full_name'] ?? 'Administrator';
$current_date = date('l, F j, Y');



require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Link Admin CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_index.css">

<div class="admin-dashboard">
    <div class="container-fluid" style="max-width: 1400px;">
        
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1>
                <i data-lucide="layout-dashboard" style="width: 32px; height: 32px;"></i>
                Dashboard
            </h1>
        </div>

        <!-- Flash Messages -->
        <?php displayFlashMessages(); ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-banner-content">
                <h2><span id="greeting-text"><?php echo $greeting; ?></span>, <?php echo htmlspecialchars($admin_name); ?>! 👋</h2>
                <p>Welcome to your admin dashboard. Here's an overview of your system.</p>
                <div class="welcome-banner-time">📅 <?php echo $current_date; ?></div>
                <div class="welcome-banner-clock">🕐 <span id="current-time">--:--:--</span></div>
            </div>
        </div>

        <!-- Metrics Cards Grid -->
        <div class="metrics-grid">
            <!-- Total Students Card -->
            <div class="metric-card primary">
                <div class="metric-icon">
                    <i data-lucide="users" style="width: 28px; height: 28px;"></i>
                </div>
                <span class="metric-label">Total Students</span>
                <div class="metric-value">1,248</div>
                <div class="metric-change positive">
                    <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                    <span>+12% this month</span>
                </div>
            </div>

            <!-- Active Exams Card -->
            <div class="metric-card info">
                <div class="metric-icon">
                    <i data-lucide="clipboard-list" style="width: 28px; height: 28px;"></i>
                </div>
                <span class="metric-label">Active Exams</span>
                <div class="metric-value">42</div>
                <div class="metric-change positive">
                    <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                    <span>+5 this week</span>
                </div>
            </div>

            <!-- Certificates Issued Card -->
            <div class="metric-card success">
                <div class="metric-icon">
                    <i data-lucide="award" style="width: 28px; height: 28px;"></i>
                </div>
                <span class="metric-label">Certificates Issued</span>
                <div class="metric-value">856</div>
                <div class="metric-change positive">
                    <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                    <span>+28 this week</span>
                </div>
            </div>

            <!-- Average Exam Score Card -->
            <div class="metric-card warning">
                <div class="metric-icon">
                    <i data-lucide="bar-chart-3" style="width: 28px; height: 28px;"></i>
                </div>
                <span class="metric-label">Avg Exam Score</span>
                <div class="metric-value">78%</div>
                <div class="metric-change positive">
                    <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                    <span>+2% this month</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--dark-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                <i data-lucide="zap" style="width: 20px; height: 20px; color: var(--primary-color);"></i>
                Quick Actions
            </h3>
            <div class="quick-actions">
                <a href="<?= BASE_URL ?>/admin/users.php" class="action-btn">
                    <i data-lucide="users-cog"></i>
                    <span>Manage Users</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/exams.php" class="action-btn">
                    <i data-lucide="edit-3"></i>
                    <span>Create Exam</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/questions.php" class="action-btn">
                    <i data-lucide="help-circle"></i>
                    <span>Add Questions</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/materials.php" class="action-btn">
                    <i data-lucide="book-open"></i>
                    <span>Study Materials</span>
                </a>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="statistics-section">
            <!-- Recent Activity -->
            <div class="activity-card">
                <h3>
                    <i data-lucide="clock"></i>
                    Recent Activity
                </h3>
                <ul class="activity-list">
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">New student registration</div>
                            <div class="activity-item-time">2 hours ago</div>
                        </div>
                        <span class="activity-badge primary">New</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Exam completed: Physics 101</div>
                            <div class="activity-item-time">4 hours ago</div>
                        </div>
                        <span class="activity-badge success">Complete</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Certificate issued</div>
                            <div class="activity-item-time">6 hours ago</div>
                        </div>
                        <span class="activity-badge success">Success</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Material uploaded</div>
                            <div class="activity-item-time">1 day ago</div>
                        </div>
                        <span class="activity-badge primary">Upload</span>
                    </li>
                </ul>
            </div>

            <!-- System Status -->
            <div class="activity-card">
                <h3>
                    <i data-lucide="activity"></i>
                    System Status
                </h3>
                <ul class="activity-list">
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Database Connection</div>
                            <div class="activity-item-time">All systems operational</div>
                        </div>
                        <span class="status-badge active">Active</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Server Performance</div>
                            <div class="activity-item-time">95% uptime</div>
                        </div>
                        <span class="status-badge active">Active</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Email Service</div>
                            <div class="activity-item-time">Notifications sent</div>
                        </div>
                        <span class="status-badge active">Active</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Backup Status</div>
                            <div class="activity-item-time">Last backup: Today 2:00 AM</div>
                        </div>
                        <span class="status-badge active">Success</span>
                    </li>
                </ul>
            </div>

            <!-- Statistics Summary -->
            <div class="activity-card">
                <h3>
                    <i data-lucide="pie-chart"></i>
                    Key Metrics
                </h3>
                <ul class="activity-list">
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Student Completion Rate</div>
                            <div class="activity-item-time">92% success</div>
                        </div>
                        <span class="status-badge active">Excellent</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Average Response Time</div>
                            <div class="activity-item-time">45ms</div>
                        </div>
                        <span class="status-badge active">Good</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Active Users Today</div>
                            <div class="activity-item-time">389 users</div>
                        </div>
                        <span class="status-badge active">Normal</span>
                    </li>
                    <li>
                        <div class="activity-item-text">
                            <div class="activity-item-title">Pending Tasks</div>
                            <div class="activity-item-time">12 items</div>
                        </div>
                        <span class="activity-badge primary">Review</span>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>

<!-- Real-time Clock & Greeting Script -->
<script>
    function getGreeting(hour) {
        if (hour >= 22 || hour === 0) {
            return 'Good Night';
        } else if (hour >= 1 && hour < 12) {
            return 'Good Morning';
        } else if (hour >= 12 && hour < 18) {
            return 'Good Afternoon';
        } else {
            return 'Good Evening';
        }
    }
    
    function updateClockAndGreeting() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        // Update time
        let displayHours = hours % 12;
        displayHours = displayHours ? displayHours : 12;
        displayHours = String(displayHours).padStart(2, '0');
        document.getElementById('current-time').textContent = `${displayHours}:${minutes}:${seconds} ${ampm}`;
        
        // Update greeting based on current hour
        document.getElementById('greeting-text').textContent = getGreeting(hours);
    }
    
    updateClockAndGreeting();
    setInterval(updateClockAndGreeting, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>