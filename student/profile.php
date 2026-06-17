<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Fetch user basic info
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
           u.approval_status, u.approved_at,
           up.bio, up.profile_photo_path, up.cover_photo_path,
           up.phone_verified, up.aadhaar_number, up.father_name,
           up.mother_name, up.emergency_contact, up.emergency_contact_name,
           up.social_media_links, up.skills, up.certifications,
           up.about_education, up.about_experience, up.website, up.location
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/student/index.php');
}

// Parse JSON fields
$social_media = !empty($user['social_media_links']) ? json_decode($user['social_media_links'], true) : [];
$skills = !empty($user['skills']) ? json_decode($user['skills'], true) : [];
$certifications = !empty($user['certifications']) ? json_decode($user['certifications'], true) : [];

// Fetch exam statistics
$exam_stats = ['total_exams' => 0, 'completed_exams' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_exams,
               SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as completed_exams
        FROM exam_attempts
        WHERE student_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $exam_stats = $result;
    }
} catch (Exception $e) {
    // Table might not exist, continue with default stats
}

// Fetch materials bookmarked
$bookmark_stats = ['total_bookmarks' => 0];
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_bookmarks
        FROM material_bookmarks
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result) {
        $bookmark_stats = $result;
    }
} catch (Exception $e) {
    // Table might not exist, continue with default stats
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<style>
    * {
        box-sizing: border-box;
    }

    .profile-container {
        max-width: 1000px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
    }

    .profile-cover {
        height: 250px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        position: relative;
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 30px;
    }

    .profile-cover::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, rgba(0,0,0,0.15), rgba(0,0,0,0.4));
        z-index: 1;
    }

    .profile-cover-overlay {
        position: relative;
        z-index: 2;
        align-self: flex-start;
        margin-top: 10px;
    }

    .profile-cover-overlay a {
        background: white;
        color: #667eea;
        padding: 10px 22px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        font-size: 15px;
    }

    .profile-cover-overlay a:hover {
        background: #f0f0f0;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.25);
    }

    .profile-header {
        position: relative;
        padding: 30px 40px;
        display: flex;
        align-items: center;
        gap: 40px;
        background: white;
        border-bottom: 1px solid #e2e8f0;
    }

    .profile-photo {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 5px solid #667eea;
        object-fit: cover;
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
        flex-shrink: 0;
    }

    .profile-photo-placeholder {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 5px solid #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 75px;
        box-shadow: 0 12px 32px rgba(102, 126, 234, 0.3);
        flex-shrink: 0;
    }

    .profile-info {
        flex: 1;
    }

    .profile-info h2 {
        color: #1a202c;
        margin: 0 0 12px;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: -0.8px;
    }

    .profile-info p {
        color: #4a5568;
        margin: 8px 0;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .approval-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 700;
        margin-top: 14px;
        letter-spacing: 0.3px;
    }

    .approval-badge.approved {
        background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        color: #0f5b32;
        box-shadow: 0 4px 12px rgba(132, 250, 176, 0.3);
    }

    .approval-badge.pending {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: #6b3410;
        box-shadow: 0 4px 12px rgba(250, 112, 154, 0.3);
    }

    .approval-badge.rejected {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    }

    .profile-content {
        display: grid;
        grid-template-columns: 1fr 310px;
        gap: 30px;
        padding: 30px;
        background: #f8fafc;
        min-height: calc(100vh - 600px);
    }

    .profile-section {
        margin-bottom: 28px;
        background: white;
        padding: 28px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-left: 4px solid #667eea;
        transition: all 0.3s ease;
    }

    .profile-section:hover {
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    .profile-section h3 {
        color: #1a202c;
        margin-top: 0;
        margin-bottom: 18px;
        padding-bottom: 0;
        border-bottom: none;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: -0.3px;
    }

    .profile-section p {
        color: #4a5568;
        line-height: 1.8;
        margin: 14px 0;
        font-size: 15px;
    }

    .section-label {
        font-weight: 700;
        color: #1a202c;
        display: inline-block;
        min-width: 140px;
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .section-value {
        color: #4a5568;
        font-weight: 600;
    }

    .skills-list, .certifications-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
    }

    .skill-tag, .cert-tag {
        background: #f0f0f0;
        color: #333;
        padding: 8px 14px;
        border-radius: 18px;
        font-size: 13px;
        border: none;
        transition: all 0.3s ease;
    }

    .skill-tag {
        background: linear-gradient(135deg, #e8f4f8 0%, #f0e8ff 100%);
        border: 1px solid #667eea;
        color: #667eea;
        font-weight: 600;
        padding: 10px 16px;
        font-size: 14px;
    }

    .skill-tag:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(102, 126, 234, 0.3);
    }

    .cert-tag {
        background: linear-gradient(135deg, #fff4e6 0%, #ffe8e8 100%);
        border: 1px solid #f5576c;
        color: #f5576c;
        font-weight: 600;
        padding: 10px 16px;
        font-size: 14px;
    }

    .cert-tag:hover {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(245, 87, 108, 0.3);
    }

    .social-links {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        flex-wrap: wrap;
    }

    .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
        color: #667eea;
        text-decoration: none;
        font-size: 20px;
        transition: all 0.3s ease;
        border: none;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    }

    .social-links a:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.35);
    }

    .stats-card {
        background: white;
        border: none;
        border-radius: 12px;
        padding: 25px 20px;
        text-align: center;
        margin-bottom: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .stats-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 25px rgba(102, 126, 234, 0.25);
    }

    .stats-card h4 {
        margin: 0;
        font-size: 40px;
        font-weight: 800;
        color: #667eea;
    }

    .stats-card p {
        margin: 10px 0 0;
        color: #718096;
        font-size: 14px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .stats-card.gradient1 {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
    }

    .stats-card.gradient1 h4,
    .stats-card.gradient1 p {
        color: white;
    }

    .stats-card.gradient1:hover {
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.5);
    }

    .stats-card.gradient2 {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        box-shadow: 0 8px 20px rgba(245, 87, 108, 0.35);
    }

    .stats-card.gradient2 h4,
    .stats-card.gradient2 p {
        color: white;
    }

    .stats-card.gradient2:hover {
        box-shadow: 0 15px 35px rgba(245, 87, 108, 0.5);
    }

    .stats-card.gradient3 {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        border: none;
        box-shadow: 0 8px 20px rgba(79, 172, 254, 0.35);
    }

    .stats-card.gradient3 h4,
    .stats-card.gradient3 p {
        color: white;
    }

    .stats-card.gradient3:hover {
        box-shadow: 0 15px 35px rgba(79, 172, 254, 0.5);
    }

    .empty-state {
        color: #a0aec0;
        text-align: center;
        padding: 30px 20px;
        background: #f9fafb;
        border-radius: 8px;
        border: 2px dashed #cbd5e0;
    }

    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .sidebar .profile-section {
        margin-bottom: 0;
    }

    .sidebar .profile-section:not(:last-child) {
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .profile-content {
            grid-template-columns: 1fr;
        }

        .profile-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 20px;
            padding: 25px 20px;
        }

        .profile-info h2 {
            font-size: 28px;
        }

        .profile-photo,
        .profile-photo-placeholder {
            width: 140px;
            height: 140px;
            font-size: 60px;
            border-width: 4px;
        }

        .profile-cover {
            height: 200px;
            padding: 15px;
        }

        .profile-section {
            padding: 20px;
        }

        .approval-badge {
            justify-content: center;
        }
    }
</style>

<div class="profile-container">
    <!-- Cover Photo -->
    <div class="profile-cover" style="<?php echo !empty($user['cover_photo_path']) ? 'background-image: url(' . htmlspecialchars($user['cover_photo_path']) . ');' : ''; ?>">
        <div></div>
        <div class="profile-cover-overlay">
            <a href="<?php echo BASE_URL; ?>/student/edit_profile.php">✏️ Edit Profile</a>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <?php if (!empty($user['profile_photo_path'])): ?>
            <img src="<?php echo htmlspecialchars($user['profile_photo_path']); ?>" alt="Profile" class="profile-photo">
        <?php else: ?>
            <div class="profile-photo-placeholder">👤</div>
        <?php endif; ?>

        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p>📧 <?php echo htmlspecialchars($user['email']); ?></p>
            <?php if (!empty($user['phone'])): ?>
                <p>📱 <?php echo htmlspecialchars($user['phone']); ?></p>
            <?php endif; ?>
            <?php if (!empty($user['location'])): ?>
                <p>📍 <?php echo htmlspecialchars($user['location']); ?></p>
            <?php endif; ?>

            <?php 
            $approval_status = $user['approval_status'] ?? 'pending';
            $badge_class = $approval_status === 'approved' ? 'approved' : ($approval_status === 'pending' ? 'pending' : 'rejected');
            $badge_text = ucfirst($approval_status);
            $badge_icon = $approval_status === 'approved' ? '✓' : ($approval_status === 'pending' ? '⏳' : '✗');
            ?>
            <span class="approval-badge <?php echo $badge_class; ?>"><?php echo $badge_icon; ?> <?php echo $badge_text; ?></span>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
        <div class="main-content">
            <!-- Bio -->
            <?php if (!empty($user['bio'])): ?>
                <div class="profile-section">
                    <h3>📝 About Me</h3>
                    <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- About Education -->
            <?php if (!empty($user['about_education'])): ?>
                <div class="profile-section">
                    <h3>🎓 Education</h3>
                    <p><?php echo nl2br(htmlspecialchars($user['about_education'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- About Experience -->
            <?php if (!empty($user['about_experience'])): ?>
                <div class="profile-section">
                    <h3>💼 Experience</h3>
                    <p><?php echo nl2br(htmlspecialchars($user['about_experience'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Skills -->
            <?php if (!empty($skills) && count($skills) > 0): ?>
                <div class="profile-section">
                    <h3>🔧 Skills</h3>
                    <div class="skills-list">
                        <?php foreach ($skills as $skill): ?>
                            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Certifications -->
            <?php if (!empty($certifications) && count($certifications) > 0): ?>
                <div class="profile-section">
                    <h3>🏆 Certifications</h3>
                    <div class="certifications-list">
                        <?php foreach ($certifications as $cert): ?>
                            <span class="cert-tag"><?php echo htmlspecialchars($cert); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="profile-section">
                <h3>📞 Contact Information</h3>
                
                <p>
                    <span class="section-label">📧 Email:</span>
                    <span class="section-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </p>

                <?php if (!empty($user['phone'])): ?>
                    <p>
                        <span class="section-label">📱 Phone:</span>
                        <span class="section-value">
                            <?php echo htmlspecialchars($user['phone']); ?>
                            <?php if (!empty($user['phone_verified'])): ?>
                                <span style="color: #10b981; font-weight: 700;">✅</span>
                            <?php endif; ?>
                        </span>
                    </p>
                <?php endif; ?>

                <?php if (!empty($user['father_name']) || !empty($user['mother_name'])): ?>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">
                    <?php if (!empty($user['father_name'])): ?>
                        <p>
                            <span class="section-label">👨 Father:</span>
                            <span class="section-value"><?php echo htmlspecialchars($user['father_name']); ?></span>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($user['mother_name'])): ?>
                        <p>
                            <span class="section-label">👩 Mother:</span>
                            <span class="section-value"><?php echo htmlspecialchars($user['mother_name']); ?></span>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($user['emergency_contact'])): ?>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">
                    <p>
                        <span class="section-label">🚨 Emergency:</span>
                        <span class="section-value"><?php echo htmlspecialchars($user['emergency_contact_name']); ?></span>
                    </p>
                    <p style="margin-left: 140px;">
                        <span class="section-value">📞 <?php echo htmlspecialchars($user['emergency_contact']); ?></span>
                    </p>
                <?php endif; ?>

                <?php if (!empty($user['aadhaar_number'])): ?>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">
                    <p>
                        <span class="section-label">🆔 Aadhaar:</span>
                        <span class="section-value">***<?php echo substr($user['aadhaar_number'], -4); ?></span>
                    </p>
                <?php endif; ?>

                <?php if (!empty($user['website']) || (!empty($social_media) && count($social_media) > 0)): ?>
                    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">
                    
                    <?php if (!empty($user['website'])): ?>
                        <p>
                            <span class="section-label">🌐 Website:</span>
                            <span class="section-value">
                                <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600; cursor: pointer;">
                                    <?php echo htmlspecialchars($user['website']); ?>
                                </a>
                            </span>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($social_media) && count($social_media) > 0): ?>
                        <p>
                            <span class="section-label">🔗 Social:</span>
                        </p>
                        <div class="social-links" style="margin-left: 140px;">
                            <?php foreach ($social_media as $social): ?>
                                <a href="<?php echo htmlspecialchars($social['url'] ?? '#'); ?>" target="_blank" title="<?php echo htmlspecialchars($social['platform'] ?? ''); ?>" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: #667eea; color: white; text-decoration: none; font-size: 18px; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(102, 126, 234, 0.25);">
                                    <?php echo htmlspecialchars(strtoupper(substr($social['platform'], 0, 1))); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Links moved to Contact Information section above -->
        </div>

        <!-- Sidebar Stats -->
        <div class="sidebar">
            <div class="stats-card gradient1">
                <h4><?php echo $exam_stats['total_exams'] ?? 0; ?></h4>
                <p>Exams Attempted</p>
            </div>

            <div class="stats-card gradient2">
                <h4><?php echo $exam_stats['completed_exams'] ?? 0; ?></h4>
                <p>Exams Completed</p>
            </div>

            <div class="stats-card gradient3">
                <h4><?php echo $bookmark_stats['total_bookmarks'] ?? 0; ?></h4>
                <p>Bookmarked</p>
            </div>

            <div class="profile-section">
                <h3>ℹ️ Account</h3>
                <p>
                    <span class="section-label">Member Since:</span><br>
                    <span class="section-value" style="font-weight: 600; color: #667eea;"><?php echo date('d M Y', strtotime($user['created_at'])); ?></span>
                </p>
                <p style="margin-top: 14px;">
                    <span class="section-label">Status:</span><br>
                    <span class="section-value" style="font-weight: 600;">
                        <?php 
                        if ($user['status'] === 'active') {
                            echo '✅ Active';
                        } elseif ($user['status'] === 'inactive') {
                            echo '⭕ Inactive';
                        } else {
                            echo '⏸️ ' . ucfirst($user['status']);
                        }
                        ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
