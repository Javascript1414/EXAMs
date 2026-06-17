<?php
/**
 * Admin Video Management Dashboard
 * Allows admins/instructors to upload, manage, and organize videos
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('instructor')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$page_title = 'Video Management';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Get admin's user ID
$admin_id = $_SESSION['user_id'];

// Handle Video Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload_video') {
            $video_name = sanitizeInput($_POST['video_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $course_id = (int)($_POST['course_id'] ?? 0);

            // Validate input
            if (empty($video_name) || empty($course_id)) {
                $_SESSION['error_message'] = "Video name and course are required.";
            } elseif (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error_message'] = "Video file upload failed.";
            } else {
                $video_file = $_FILES['video_file'];
                $allowed_types = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'];

                // Validate file type
                if (!in_array($video_file['type'], $allowed_types)) {
                    $_SESSION['error_message'] = "Only MP4, MPEG, MOV, and AVI files are allowed.";
                } else {
                    // Create upload directory if it doesn't exist
                    $upload_dir = __DIR__ . '/../uploads/videos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    // Generate unique filename
                    $file_extension = pathinfo($video_file['name'], PATHINFO_EXTENSION);
                    $unique_filename = uniqid('video_', true) . '.' . $file_extension;
                    $video_path = $upload_dir . $unique_filename;

                    // Move uploaded file
                    if (move_uploaded_file($video_file['tmp_name'], $video_path)) {
                        try {
                            // Get video duration using ffprobe or set default
                            $duration = 0; // Can be set using ffprobe if available

                            // Insert into database
                            $stmt = $pdo->prepare("
                                INSERT INTO videos (video_name, description, course_id, instructor_id, video_file, duration, status)
                                VALUES (?, ?, ?, ?, ?, ?, 'active')
                            ");
                            $stmt->execute([$video_name, $description, $course_id, $admin_id, '/uploads/videos/' . $unique_filename, $duration]);

                            $_SESSION['success_message'] = "Video uploaded successfully!";
                        } catch (PDOException $e) {
                            unlink($video_path); // Delete the file if DB insert fails
                            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
                        }
                    } else {
                        $_SESSION['error_message'] = "Failed to save video file.";
                    }
                }
            }
        } elseif ($action === 'delete_video') {
            $video_id = (int)($_POST['video_id'] ?? 0);

            if ($video_id > 0) {
                try {
                    // Get video path
                    $stmt = $pdo->prepare("SELECT video_file FROM videos WHERE id = ? AND instructor_id = ?");
                    $stmt->execute([$video_id, $admin_id]);
                    $video = $stmt->fetch();

                    if ($video) {
                        // Delete from database
                        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ? AND instructor_id = ?");
                        $stmt->execute([$video_id, $admin_id]);

                        // Delete file from server
                        $file_path = __DIR__ . '/..' . $video['video_file'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }

                        $_SESSION['success_message'] = "Video deleted successfully!";
                    } else {
                        $_SESSION['error_message'] = "Video not found or unauthorized.";
                    }
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'update_status') {
            $video_id = (int)($_POST['video_id'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? 'active');

            if ($video_id > 0 && in_array($status, ['active', 'inactive', 'archived'])) {
                try {
                    $stmt = $pdo->prepare("UPDATE videos SET status = ? WHERE id = ? AND instructor_id = ?");
                    $stmt->execute([$status, $video_id, $admin_id]);
                    $_SESSION['success_message'] = "Video status updated!";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Get all courses for dropdown
try {
    $courses = $pdo->query("SELECT course_id, course_name FROM courses WHERE status = 'active' ORDER BY course_name")->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

// Get admin's videos
try {
    $videos = $pdo->prepare("
        SELECT v.*, c.course_name 
        FROM videos v
        LEFT JOIN courses c ON v.course_id = c.course_id
        WHERE v.instructor_id = ?
        ORDER BY v.created_at DESC
    ");
    $videos->execute([$admin_id]);
    $videos = $videos->fetchAll();
} catch (PDOException $e) {
    $videos = [];
}

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['success_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['error_message']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i data-lucide="video" class="me-2"></i>
                    Video Management
                </h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadVideoModal">
                    <i data-lucide="upload" class="me-2"></i>Upload Video
                </button>
            </div>
            <p class="text-muted mt-2">Manage your course videos and media content</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2 small">Total Videos</p>
                    <h3 class="mb-0"><?php echo count($videos); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2 small">Total Views</p>
                    <h3 class="mb-0"><?php echo array_sum(array_column($videos, 'views')); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2 small">Avg Rating</p>
                    <h3 class="mb-0"><?php echo count($videos) > 0 ? number_format(array_sum(array_column($videos, 'rating')) / count($videos), 1) : '0'; ?> ★</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-2 small">Active Videos</p>
                    <h3 class="mb-0"><?php echo count(array_filter($videos, fn($v) => $v['status'] === 'active')); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Videos Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0">Your Videos</h5>
                </div>
                <div class="card-body">
                    <?php if (count($videos) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Video Name</th>
                                        <th>Course</th>
                                        <th>Views</th>
                                        <th>Rating</th>
                                        <th>Status</th>
                                        <th>Uploaded</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($videos as $video): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i data-lucide="video" class="me-2"></i>
                                                    <?php echo htmlspecialchars($video['video_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($video['course_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge bg-info"><?php echo $video['views']; ?></span></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-1"><?php echo number_format($video['rating'], 1); ?></span>
                                                    <span style="color: #ffc107;">★</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $video['status'] === 'active' ? 'success' : ($video['status'] === 'inactive' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($video['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($video['created_at'])); ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="delete_video">
                                                    <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this video?')">
                                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No videos uploaded yet. Click the button above to upload your first video!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Video Modal -->
<div class="modal fade" id="uploadVideoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload New Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="upload_video">

                    <div class="mb-3">
                        <label for="video_name" class="form-label">Video Name *</label>
                        <input type="text" class="form-control" id="video_name" name="video_name" required placeholder="Enter video title">
                    </div>

                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter video description (optional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="video_file" class="form-label">Video File *</label>
                        <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*" required>
                        <small class="text-muted d-block mt-2">Supported formats: MP4, MPEG, MOV, AVI (Max 500MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="upload" class="me-2"></i>Upload Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
