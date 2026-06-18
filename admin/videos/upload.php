<?php
/**
 * Video Upload Management
 * Admin panel for uploading and managing educational videos
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle Video Upload - BEFORE including header.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid CSRF token.";
    } else {
        $video_name = sanitizeInput($_POST['video_name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);
        $upload_type = sanitizeInput($_POST['upload_type'] ?? 'file'); // 'file' or 'youtube'
        $video_file = $_FILES['video_file'] ?? null;

        // Validate inputs
        if (empty($video_name)) {
            $error = "Video name is required.";
        } elseif ($course_id <= 0) {
            $error = "Please select a course.";
        } else if ($upload_type === 'file') {
            // File upload validation
            if (!$video_file || $video_file['error'] !== UPLOAD_ERR_OK) {
                $error = "Video upload error: " . ($video_file['error'] ?? 'No file provided');
            } else {
                // Validate file type
                $allowed_types = ['video/mp4', 'video/webm', 'video/ogg'];
                $file_type = mime_content_type($video_file['tmp_name']);

                if (!in_array($file_type, $allowed_types)) {
                    $error = "Only MP4, WebM, and OGG videos are allowed.";
                } else {
                    // Create upload directory if not exists
                    $upload_dir = __DIR__ . '/../../uploads/videos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    // Generate unique filename
                    $file_ext = pathinfo($video_file['name'], PATHINFO_EXTENSION);
                    $unique_name = 'video_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                    $upload_path = $upload_dir . $unique_name;

                    // Move uploaded file
                    if (move_uploaded_file($video_file['tmp_name'], $upload_path)) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO videos (video_name, description, course_id, instructor_id, video_file, status)
                                VALUES (?, ?, ?, ?, ?, 'active')
                            ");
                            $stmt->execute([$video_name, $description, $course_id, $_SESSION['user_id'], '/uploads/videos/' . $unique_name]);
                            
                            $_SESSION['success_message'] = "Video uploaded successfully!";
                            header('Location: ' . BASE_URL . '/admin/videos/list.php');
                            exit;
                        } catch (PDOException $e) {
                            @unlink($upload_path); // Delete uploaded file on DB error
                            $error = "Database error: " . $e->getMessage();
                        }
                    } else {
                        $error = "Failed to move uploaded file.";
                    }
                }
            }
        } else if ($upload_type === 'youtube') {
            // YouTube URL upload
            $youtube_url = sanitizeInput($_POST['youtube_url'] ?? '');
            
            if (empty($youtube_url)) {
                $error = "YouTube URL is required.";
            } else {
                // Extract YouTube video ID and validate URL
                $youtube_id = '';
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtube_url, $matches)) {
                    $youtube_id = $matches[1];
                } else {
                    $error = "Invalid YouTube URL. Please use format: https://youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID";
                }
                
                if (!empty($youtube_id)) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO videos (video_name, description, course_id, instructor_id, video_file, status)
                            VALUES (?, ?, ?, ?, ?, 'active')
                        ");
                        $stmt->execute([$video_name, $description, $course_id, $_SESSION['user_id'], 'youtube:' . $youtube_id]);
                        
                        $_SESSION['success_message'] = "YouTube video added successfully!";
                        header('Location: ' . BASE_URL . '/admin/videos/list.php');
                        exit;
                    } catch (PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Get courses for dropdown - BEFORE including header.php
try {
    $courses = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC")->fetchAll();
} catch (PDOException $e) {
    $courses = [];
    $error = "Failed to load courses.";
}

$page_title = 'Upload Video';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i data-lucide="upload-cloud" class="me-2"></i>
                    Upload Educational Video
                </h1>
                <a href="<?= BASE_URL ?>/admin/videos/list.php" class="btn btn-secondary">
                    <i data-lucide="list" class="me-1"></i> All Videos
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i data-lucide="alert-circle" class="me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" enctype="multipart/form-data" id="videoForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                        <!-- Video Name -->
                        <div class="mb-3">
                            <label for="video_name" class="form-label fw-500">
                                <i data-lucide="video" class="me-1"></i> Video Name
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="video_name" 
                                name="video_name" 
                                placeholder="e.g., Mathematics Fundamentals - Chapter 1"
                                required
                            >
                            <small class="text-muted">Give your video a clear, descriptive name</small>
                        </div>

                        <!-- Course Selection -->
                        <div class="mb-3">
                            <label for="course_id" class="form-label fw-500">
                                <i data-lucide="book-open" class="me-1"></i> Course
                            </label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['course_id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-500">
                                <i data-lucide="file-text" class="me-1"></i> Description
                            </label>
                            <textarea 
                                class="form-control" 
                                id="description" 
                                name="description" 
                                rows="4" 
                                placeholder="Describe what students will learn from this video..."
                            ></textarea>
                            <small class="text-muted">Optional: Helps students understand video content</small>
                        </div>

                        <!-- Video Source Selection Tabs -->
                        <div class="mb-4">
                            <label class="form-label fw-500 mb-3">
                                <i data-lucide="film" class="me-1"></i> Video Source
                            </label>
                            <div class="btn-group w-100" role="group" style="display: flex;">
                                <input type="radio" class="btn-check" name="upload_type" id="uploadFile" value="file" checked onchange="toggleUploadType()">
                                <label class="btn btn-outline-primary flex-grow-1" for="uploadFile">
                                    <i data-lucide="upload-cloud" class="me-2"></i> Upload File
                                </label>

                                <input type="radio" class="btn-check" name="upload_type" id="uploadYoutube" value="youtube" onchange="toggleUploadType()">
                                <label class="btn btn-outline-primary flex-grow-1" for="uploadYoutube">
                                    <i data-lucide="youtube" class="me-2"></i> YouTube Link
                                </label>
                            </div>
                        </div>

                        <!-- File Upload Section -->
                        <div id="fileUploadSection" class="mb-4">
                            <div class="upload-zone border-2 border-dashed rounded p-4 text-center" id="uploadZone">
                                <input 
                                    type="file" 
                                    class="form-control d-none" 
                                    id="video_file" 
                                    name="video_file" 
                                    accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.ogv"
                                >
                                <i data-lucide="cloud-upload" style="width: 48px; height: 48px;" class="text-muted mb-3 d-block"></i>
                                <p class="mb-2">
                                    <strong>Drag and drop your video here</strong><br>
                                    or click to browse
                                </p>
                                <small class="text-muted">
                                    Supported formats: MP4, WebM, OGG (Max 500MB)
                                </small>
                                <div id="fileName" class="mt-3" style="display: none;">
                                    <p class="mb-0"><strong>Selected:</strong> <span id="selectedName"></span></p>
                                </div>
                            </div>
                        </div>

                        <!-- YouTube URL Section -->
                        <div id="youtubeSection" class="mb-4" style="display: none;">
                            <label for="youtube_url" class="form-label fw-500">
                                <i data-lucide="link-2" class="me-1"></i> YouTube URL
                            </label>
                            <input 
                                type="url" 
                                class="form-control" 
                                id="youtube_url" 
                                name="youtube_url" 
                                placeholder="https://www.youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID"
                            >
                            <small class="text-muted">
                                Paste the YouTube video link. Examples:<br>
                                • https://www.youtube.com/watch?v=dQw4w9WgXcQ<br>
                                • https://youtu.be/dQw4w9WgXcQ
                            </small>
                            <div id="youtubePreview" class="mt-3" style="display: none;">
                                <p class="mb-2"><strong>Preview:</strong></p>
                                <div id="youtubeEmbed" style="max-width: 300px; margin: 0 auto;"></div>
                            </div>
                        </div>

                        <!-- Progress Bar (Hidden by default) -->
                        <div id="uploadProgress" style="display: none;" class="mb-3">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: 0%" 
                                     id="progressBar">
                                    0%
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i data-lucide="upload" class="me-2"></i> Upload Video
                            </button>
                            <a href="<?= BASE_URL ?>/admin/videos/list.php" class="btn btn-outline-secondary">
                                <i data-lucide="x" class="me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i data-lucide="info" class="me-2"></i> Upload Guidelines
                    </h6>
                    <ul class="small mb-0">
                        <li>✓ Use clear, descriptive video titles</li>
                        <li>✓ Include detailed descriptions</li>
                        <li>✓ Maximum file size: 500MB</li>
                        <li>✓ Supported: MP4, WebM, OGG</li>
                        <li>✓ Recommended resolution: 1080p</li>
                        <li>✓ Good audio quality is essential</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i data-lucide="trending-up" class="me-2"></i> Video Tips
                    </h6>
                    <ul class="small mb-0">
                        <li>📹 Keep videos under 15 minutes</li>
                        <li>🎙️ Speak clearly and slowly</li>
                        <li>✨ Add subtitles for accessibility</li>
                        <li>⏱️ Include chapter markers</li>
                        <li>📊 Add engaging thumbnails</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-zone {
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: var(--bg-secondary, #f8f9fa);
    }

    .upload-zone:hover {
        background-color: var(--bg-tertiary, #e9ecef);
        border-color: var(--primary, #007bff) !important;
    }

    .upload-zone.dragover {
        background-color: var(--primary, #007bff);
        opacity: 0.1;
        border-color: var(--primary, #007bff) !important;
    }

    /* Dark mode adjustments */
    [data-theme="dark"] .upload-zone {
        background-color: #2a2e35;
        border-color: #444 !important;
    }

    [data-theme="dark"] .upload-zone:hover {
        background-color: #353b44;
    }
</style>

<script>
    // Toggle between file upload and YouTube link
    function toggleUploadType() {
        const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
        const fileSection = document.getElementById('fileUploadSection');
        const youtubeSection = document.getElementById('youtubeSection');
        const videoFileInput = document.getElementById('video_file');
        const youtubeInput = document.getElementById('youtube_url');

        if (uploadType === 'file') {
            fileSection.style.display = 'block';
            youtubeSection.style.display = 'none';
            videoFileInput.required = true;
            youtubeInput.required = false;
        } else {
            fileSection.style.display = 'none';
            youtubeSection.style.display = 'block';
            videoFileInput.required = false;
            youtubeInput.required = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const uploadZone = document.getElementById('uploadZone');
        const videoFileInput = document.getElementById('video_file');
        const fileNameDisplay = document.getElementById('fileName');
        const selectedNameSpan = document.getElementById('selectedName');
        const youtubeInput = document.getElementById('youtube_url');
        const youtubePreview = document.getElementById('youtubePreview');
        const youtubeEmbed = document.getElementById('youtubeEmbed');

        // Click to browse
        uploadZone.addEventListener('click', () => videoFileInput.click());

        // Drag and drop
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                videoFileInput.files = files;
                updateFileName();
            }
        });

        // File input change
        videoFileInput.addEventListener('change', updateFileName);

        function updateFileName() {
            if (videoFileInput.files.length > 0) {
                const fileName = videoFileInput.files[0].name;
                const fileSize = (videoFileInput.files[0].size / 1024 / 1024).toFixed(2);
                selectedNameSpan.textContent = `${fileName} (${fileSize}MB)`;
                fileNameDisplay.style.display = 'block';
            }
        }

        // YouTube URL preview
        youtubeInput.addEventListener('input', function() {
            const url = this.value;
            let videoId = '';

            // Extract video ID from various YouTube URL formats
            if (url.includes('youtube.com')) {
                const match = url.match(/[?&]v=([^&]*)/);
                if (match) videoId = match[1];
            } else if (url.includes('youtu.be')) {
                const match = url.match(/youtu\.be\/([^?]*)/);
                if (match) videoId = match[1];
            }

            if (videoId) {
                const embedUrl = `https://www.youtube.com/embed/${videoId}?rel=0&modestbranding=1`;
                youtubeEmbed.innerHTML = `
                    <iframe width="100%" height="250" src="${embedUrl}" 
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; 
                        encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
                    </iframe>
                `;
                youtubePreview.style.display = 'block';
            } else {
                youtubePreview.style.display = 'none';
            }
        });

        // Form submission validation
        document.getElementById('videoForm').addEventListener('submit', function(e) {
            const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
            
            if (uploadType === 'file') {
                const fileSize = videoFileInput.files[0]?.size || 0;
                if (fileSize > 500 * 1024 * 1024) {
                    e.preventDefault();
                    alert('File size exceeds 500MB limit');
                }
            }
        });

        lucide.createIcons();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
