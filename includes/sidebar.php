<?php
require_once __DIR__ . '/db.php';

// Fetch latest notifications for the top navigation bell
if (isLoggedIn()) {
    global $pdo;
    $notifStmt = $pdo->prepare("SELECT nr.id as recipient_id, nr.is_read, n.title, n.message, n.action_url, n.created_at FROM notification_recipients nr JOIN notifications n ON nr.notification_id = n.id WHERE nr.user_id = ? ORDER BY n.created_at DESC LIMIT 5");
    $notifStmt->execute([$_SESSION['user_id']]);
    $latestNotifications = $notifStmt->fetchAll();
    
    // Add default icon to each notification
    foreach ($latestNotifications as &$notif) {
        $notif['icon'] = 'bell';
    }

    $unreadCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notification_recipients WHERE user_id = ? AND is_read = 0");
    $unreadCountStmt->execute([$_SESSION['user_id']]);
    $unreadCount = $unreadCountStmt->fetchColumn();
}
?>
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h4 class="mb-0 fw-bold d-flex align-items-center">
                    <i data-lucide="graduation-cap" class="me-2"></i> <?= APP_NAME ?>
                </h4>
            </div>

            <ul class="list-unstyled components">
                <?php if (hasRole('superadmin') || hasRole('admin')): ?>
                    <li><a href="<?= BASE_URL ?>/admin/index.php"><i data-lucide="layout-dashboard"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/users.php"><i data-lucide="users"></i> Manage Users</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/add_teacher.php"><i data-lucide="user-plus"></i> Add Teacher</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/manage_subject_teachers.php"><i data-lucide="book-open"></i> Manage Teachers</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/assign_student_trades.php"><i data-lucide="link-2"></i> Assign Trades</a></li>
                    <?php if (hasRole('superadmin')): ?>
                        <li><a href="<?= BASE_URL ?>/admin/deleted_users_archive.php"><i data-lucide="archive"></i> Deleted Users Archive</a></li>
                    <?php endif; ?>
                    <li><a href="<?= BASE_URL ?>/admin/trades.php"><i data-lucide="briefcase"></i> Trades</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/subjects.php"><i data-lucide="library"></i> Subjects</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/analytics_dashboard.php"><i data-lucide="bar-chart-3"></i> Advanced Analytics</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/videos/list.php"><i data-lucide="video"></i> Manage Videos</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/materials.php"><i data-lucide="book-open"></i> Study Materials</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/notes.php"><i data-lucide="file-text"></i> Study Notes</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/questions.php"><i data-lucide="help-circle"></i> Question Bank</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/exams.php"><i data-lucide="file-text"></i> Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/practical_exams.php"><i data-lucide="clipboard-list"></i> Practical Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/google_form_exams.php"><i data-lucide="globe"></i> Google Form Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/material_analytics.php"><i data-lucide="bar-chart-3"></i> Material Analytics</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/results.php"><i data-lucide="bar-chart"></i> Exam Results</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/certificate_config.php"><i data-lucide="settings"></i> Certificate Config</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/maintenance_control.php"><i data-lucide="wrench"></i> Maintenance Mode</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/deployment_dashboard.php"><i data-lucide="rocket"></i> Deployment</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/release_certificates.php"><i data-lucide="file-check"></i> Release Certificates</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/certificates.php"><i data-lucide="award"></i> Certificates</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/notifications.php"><i data-lucide="bell-ring"></i> Notifications</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/manage_carousel_photos.php"><i data-lucide="image"></i> Carousel Photos</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/community.php"><i data-lucide="shield-alert"></i> Moderation</a></li>
                    <li><a href="<?= BASE_URL ?>/community/index.php"><i data-lucide="globe"></i> Global Community</a></li>
                <?php elseif (hasRole('moderator')): ?>
                    <li><a href="<?= BASE_URL ?>/moderator/index.php"><i data-lucide="layout-dashboard"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/materials.php"><i data-lucide="book-open"></i> Study Materials</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/notes.php"><i data-lucide="file-text"></i> Study Notes</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/questions.php"><i data-lucide="help-circle"></i> Question Bank</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/exams.php"><i data-lucide="file-text"></i> Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/practical_exams.php"><i data-lucide="clipboard-list"></i> Practical Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/results.php"><i data-lucide="bar-chart"></i> Exam Results</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/material_analytics.php"><i data-lucide="bar-chart-3"></i> Material Analytics</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/release_certificates.php"><i data-lucide="file-check"></i> Release Certificates</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/certificates.php"><i data-lucide="award"></i> Certificates</a></li>
                    <li><a href="#"><i data-lucide="pen-tool"></i> Grade Answers</a></li>
                    <li><a href="<?= BASE_URL ?>/moderator/community.php"><i data-lucide="shield-alert"></i> Moderation</a></li>
                    <li><a href="<?= BASE_URL ?>/community/index.php"><i data-lucide="globe"></i> Global Community</a></li>
                <?php elseif (hasRole('teacher')): ?>
                    <li><a href="<?= BASE_URL ?>/teacher/index.php"><i data-lucide="layout-dashboard"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/exams.php"><i data-lucide="edit-3"></i> Create Exam</a></li>
                    <li><a href="<?= BASE_URL ?>/teacher/practical_exams.php"><i data-lucide="clipboard-list"></i> Practical Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/teacher/practical_mark_submissions.php"><i data-lucide="check-circle"></i> Mark Practical</a></li>
                    <li><a href="<?= BASE_URL ?>/teacher/submissions.php"><i data-lucide="file-text"></i> Submissions</a></li>
                    <li><a href="<?= BASE_URL ?>/community/index.php"><i data-lucide="globe"></i> Global Community</a></li>
                <?php elseif (hasRole('student')): ?>
                    <li><a href="<?= BASE_URL ?>/student/index.php"><i data-lucide="layout-dashboard"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/student/progress.php"><i data-lucide="trending-up"></i> My Progress</a></li>
                    <li><a href="<?= BASE_URL ?>/student/materials.php"><i data-lucide="book-open"></i> Study Materials</a></li>
                    <li><a href="<?= BASE_URL ?>/student/notes.php"><i data-lucide="file-text"></i> Study Notes</a></li>
                    <li><a href="<?= BASE_URL ?>/student/google_form_exams.php"><i data-lucide="globe"></i> Google Form Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/student/recommendations.php"><i data-lucide="sparkles"></i> Recommendations</a></li>
                    <li><a href="<?= BASE_URL ?>/student/ai_recommendations.php"><i data-lucide="brain"></i> AI Recommendations</a></li>
                    <li><a href="<?= BASE_URL ?>/student/video_streaming.php"><i data-lucide="video"></i> Video Learning</a></li>
                    <li><a href="<?= BASE_URL ?>/student/course_timeline.php"><i data-lucide="calendar"></i> Learning Timeline</a></li>
                    <li><a href="<?= BASE_URL ?>/student/realtime_chat.php"><i data-lucide="message-circle"></i> Chat & Support</a></li>
                    <li><a href="<?= BASE_URL ?>/student/subscription.php"><i data-lucide="credit-card"></i> Premium</a></li>
                    <li><a href="<?= BASE_URL ?>/student/bookmarks.php"><i data-lucide="bookmark"></i> Bookmarks</a></li>
                    <li><a href="<?= BASE_URL ?>/student/exams.php"><i data-lucide="edit-3"></i> My Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/student/practical_exams.php"><i data-lucide="clipboard-list"></i> Practical Exams</a></li>
                    <li><a href="<?= BASE_URL ?>/student/results.php"><i data-lucide="bar-chart"></i> My Results</a></li>
                    <li><a href="<?= BASE_URL ?>/student/certificates.php"><i data-lucide="award"></i> Certificates</a></li>
                    <li><a href="<?= BASE_URL ?>/community/index.php"><i data-lucide="globe"></i> Global Community</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Main Content -->
        <div id="content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg top-navbar">
                <div class="container-fluid px-0">
                    <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none border">
                        <i data-lucide="menu" style="width: 20px; height: 20px; color: #4B5563;"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        
                        <!-- Notifications Dropdown -->
                        <div class="dropdown me-4">
                            <a href="#" class="text-secondary position-relative" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i data-lucide="bell" style="width: 22px; height: 22px;"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;" id="notifBadge">
                                        <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 pt-0 pb-0" aria-labelledby="notifDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                                <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                                    <h6 class="mb-0 fw-bold">Notifications</h6>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0 text-primary fw-semibold" onclick="markAllRead(event)">Mark all read</button>
                                </div>
                                <div id="notifContainer">
                                    <?php foreach ($latestNotifications as $notif): ?>
                                        <a href="<?= $notif['action_url'] ? htmlspecialchars($notif['action_url']) : '#' ?>" class="dropdown-item p-3 border-bottom text-wrap <?= !$notif['is_read'] ? 'bg-primary bg-opacity-10' : '' ?>" onclick="markRead(event, <?= $notif['recipient_id'] ?>, '<?= htmlspecialchars($notif['action_url'] ?? '') ?>')">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-white rounded-circle p-2 shadow-sm border me-3"><i data-lucide="<?= htmlspecialchars($notif['icon']) ?>" class="text-primary" style="width:16px; height:16px;"></i></div>
                                                <div>
                                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($notif['title']) ?></div>
                                                    <div class="text-muted small mb-1" style="line-height: 1.4;"><?= htmlspecialchars($notif['message']) ?></div>
                                                    <div class="text-secondary" style="font-size: 0.75rem;"><?= timeElapsedString($notif['created_at']) ?></div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if(empty($latestNotifications)): ?><div class="p-4 text-center text-muted small">You're all caught up!</div><?php endif; ?>
                                </div>
                                <a href="#" class="dropdown-item text-center text-primary fw-bold py-2 bg-light border-top">View All History</a>
                            </div>
                        </div>

                        <!-- Theme Toggle Button -->
                        <button type="button" class="btn theme-toggle-btn me-3" id="themeToggleBtn" title="Toggle Dark Mode" style="background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%); border: 1.5px solid rgba(255,255,255,0.3); padding: 8px 12px; border-radius: 8px; color: #ffffff; font-weight: 600; font-size: 0.85rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 6px;">
                            <i data-lucide="moon" style="width: 16px; height: 16px;"></i>
                            <span class="d-none d-sm-inline">Dark</span>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown" style="position: relative;">
                            <a href="#" class="d-flex align-items-center text-decoration-none user-dropdown-trigger" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: rgba(255,255,255,0.9); text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.25); box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                                <div class="avatar-circle" style="width: 38px; height: 38px; margin: 0; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                                    <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="fw-bold d-none d-sm-inline" style="color: #ffffff; font-size: 0.96rem; letter-spacing: -0.4px;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                                <i data-lucide="chevron-down" style="width: 16px; height: 16px; color: #ffffff; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); margin-left: auto;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li class="px-0 py-0">
                                    <div style="padding: 16px 14px; margin: 0;">
                                        <div class="fw-bold" style="color: #78350f; font-size: 0.98rem; letter-spacing: -0.3px;"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
                                        <div style="color: #92400e; font-size: 0.82rem; margin-top: 4px; word-break: break-all;"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                                    </div>
                                </li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/profile.php">
                                    <i data-lucide="user" style="flex-shrink: 0;"></i>
                                    <span>My Profile</span>
                                </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/student/settings.php">
                                    <i data-lucide="settings" style="flex-shrink: 0;"></i>
                                    <span>Settings</span>
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout_new.php">
                                    <i data-lucide="log-out" style="flex-shrink: 0;"></i>
                                    <span>Logout</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>