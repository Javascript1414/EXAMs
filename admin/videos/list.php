<?php
/**
 * Video Management - List & Manage Videos
 * Admin panel for viewing, editing, and deleting videos
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle video deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $video_id = (int)($_POST['video_id'] ?? 0);
        
        try {
            // Get video file path
            $stmt = $pdo->prepare("SELECT video_file FROM videos WHERE id = ?");
            $stmt->execute([$video_id]);
            $video = $stmt->fetch();
            
            if ($video) {
                // Delete file from server
                $file_path = __DIR__ . '/../../' . $video['video_file'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->execute([$video_id]);
                
                $_SESSION['success_message'] = "Video deleted successfully!";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error deleting video: " . $e->getMessage();
        }
        
        header('Location: ' . BASE_URL . '/admin/videos/list.php');
        exit;
    }
}

$page_title = 'Manage Videos';
require_once __DIR__ . '/../../includes/header.php';

// Get all videos
try {
    $videos = $pdo->query("
        SELECT 
            v.id, 
            v.video_name, 
            v.course_id,
            c.course_name,
            v.instructor_id,
            u.full_name as instructor_name,
            v.views,
            v.rating,
            v.total_ratings,
            v.status,
            v.created_at
        FROM videos v
        LEFT JOIN courses c ON v.course_id = c.course_id
        LEFT JOIN users u ON v.instructor_id = u.id
        ORDER BY v.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $videos = [];
    $error = "Failed to load videos: " . $e->getMessage();
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i data-lucide="video" class="me-2"></i>
                    Manage Videos
                </h1>
                <a href="<?= BASE_URL ?>/admin/videos/upload.php" class="btn btn-primary">
                    <i data-lucide="upload-cloud" class="me-1"></i> Upload Video
                </a>
            </div>
            <p class="text-muted mt-2">Total Videos: <?php echo count($videos); ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-lucide="alert-circle" class="me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($videos)): ?>
        <div class="alert alert-info">
            <i data-lucide="info" class="me-2"></i>
            No videos uploaded yet. <a href="<?= BASE_URL ?>/admin/videos/upload.php">Upload your first video</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Video Name</th>
                        <th>Course</th>
                        <th>Instructor</th>
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
                                <strong><?php echo htmlspecialchars($video['video_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($video['course_name'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($video['instructor_name'] ?? 'Unknown'); ?>
                            </td>
                            <td>
                                <i data-lucide="eye" style="width: 16px; height: 16px;" class="me-1"></i>
                                <?php echo number_format($video['views']); ?>
                            </td>
                            <td>
                                <?php if ($video['total_ratings'] > 0): ?>
                                    <span class="text-warning">
                                        ★ <?php echo number_format($video['rating'], 1); ?>
                                        <small class="text-muted">(<?php echo $video['total_ratings']; ?>)</small>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No ratings</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $video['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($video['status']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($video['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" title="View Details">
                                        <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" title="Edit">
                                        <i data-lucide="edit" style="width: 16px; height: 16px;"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal"
                                            onclick="setDeleteId(<?php echo $video['id']; ?>)"
                                            title="Delete">
                                        <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this video? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="deleteVideoId" name="video_id" value="">
                    <button type="submit" class="btn btn-danger">
                        <i data-lucide="trash-2" class="me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setDeleteId(videoId) {
        document.getElementById('deleteVideoId').value = videoId;
    }

    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
