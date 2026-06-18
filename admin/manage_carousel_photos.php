<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Explicit role check for Admin
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$imageDir = __DIR__ . '/../assets/images/';
$allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Handle Photo Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $photoName = basename($_POST['photo'] ?? '');
    $photoPath = $imageDir . $photoName;
    
    // Validate file exists and is in the correct directory
    if (file_exists($photoPath) && strpos(realpath($photoPath), realpath($imageDir)) === 0) {
        if (unlink($photoPath)) {
            setFlashMessage("✅ Photo deleted successfully!", 'success');
        } else {
            setFlashMessage("❌ Error deleting photo", 'error');
        }
    }
    header("Location: manage_carousel_photos.php");
    exit();
}

// Handle Photo Upload
$uploadMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    // Validation
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadMessage = "❌ Upload error: " . $file['error'];
    } elseif ($file['size'] > $maxFileSize) {
        $uploadMessage = "❌ File too large (max 5MB)";
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $uploadMessage = "❌ Invalid file type. Allowed: " . implode(', ', $allowedExtensions);
        } else {
            // Generate unique filename
            $newFilename = 'college_' . time() . '_' . uniqid() . '.' . $extension;
            $destinationPath = $imageDir . $newFilename;
            
            // Create directory if it doesn't exist
            if (!is_dir($imageDir)) {
                mkdir($imageDir, 0755, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
                setFlashMessage("✅ Photo uploaded successfully!", 'success');
                header("Location: manage_carousel_photos.php");
                exit();
            } else {
                $uploadMessage = "❌ Failed to upload file";
            }
        }
    }
}

// Get all carousel photos
$allCarouselPhotos = [];
if (is_dir($imageDir)) {
    $files = scandir($imageDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $allowedExtensions)) {
                $allCarouselPhotos[] = $file;
            }
        }
    }
    sort($allCarouselPhotos);
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$photos_per_page = 9;   // 3 rows x 3 columns
$total_photos = count($allCarouselPhotos);
$total_pages = ceil($total_photos / $photos_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $photos_per_page;
$carouselPhotos = array_slice($allCarouselPhotos, $offset, $photos_per_page);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_index.css">
<style>
    .carousel-management {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
    }
    
    .carousel-management h2 {
        margin-bottom: 10px;
        font-size: 28px;
        font-weight: bold;
    }
    
    .carousel-management p {
        opacity: 0.95;
        margin-bottom: 0;
    }
    
    .upload-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }
    
    .upload-area {
        border: 2px dashed #667eea;
        border-radius: 10px;
        padding: 40px;
        text-align: center;
        background: #f8f9ff;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .upload-area:hover {
        background: #f0f2ff;
        border-color: #764ba2;
    }
    
    .upload-area.dragover {
        background: #e8ebff;
        border-color: #764ba2;
        transform: scale(1.02);
    }
    
    .upload-area input[type="file"] {
        display: none;
    }
    
    .upload-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    
    .upload-text {
        color: #667eea;
        font-weight: 600;
        font-size: 18px;
    }
    
    .upload-hint {
        color: #999;
        font-size: 14px;
        margin-top: 10px;
    }
    
    .btn-upload {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
    }
    
    .btn-upload:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .photos-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    .photo-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .photo-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .photo-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
    }
    
    .photo-info {
        padding: 15px;
    }
    
    .photo-name {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        margin-bottom: 10px;
        word-break: break-word;
    }
    
    .photo-delete-btn {
        width: 100%;
        background: #ff4757;
        color: white;
        border: none;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .photo-delete-btn:hover {
        background: #ff3838;
        transform: scale(1.02);
    }
    
    .alert-message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 12px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
</style>

<div class="carousel-management">
    <h2>🎠 Carousel Photos Management</h2>
    <p>Add or remove photos from the homepage carousel. Only visible to logged-in administrators.</p>
</div>

<!-- Flash Messages -->
<?php displayFlashMessages(); ?>

<?php if ($uploadMessage): ?>
    <div class="alert-message alert-<?= strpos($uploadMessage, '❌') ? 'error' : 'success' ?>">
        <?= htmlspecialchars($uploadMessage) ?>
    </div>
<?php endif; ?>

<!-- Upload Card -->
<div class="upload-card">
    <h4 style="margin-bottom: 25px; color: #333;">📸 Upload New Photo</h4>
    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="form-group">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📤</div>
                <div class="upload-text">Click or drag photos here</div>
                <div class="upload-hint">Supported: JPG, PNG, GIF, WebP (Max 5MB)</div>
                <input type="file" name="photo" id="photoInput" accept=".jpg,.jpeg,.png,.gif,.webp">
            </div>
        </div>
        <button type="submit" class="btn-upload">Upload Photo</button>
    </form>
</div>

<!-- Photos Gallery -->
<div class="upload-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 style="color: #333; margin-bottom: 0;">📷 Current Carousel Photos (<?= $total_photos ?>)</h4>
        <?php if ($total_photos > 0): ?>
            <div class="text-muted small">
                Showing <?= $total_photos > 0 ? (($page - 1) * $photos_per_page) + 1 : 0 ?> to <?= min($page * $photos_per_page, $total_photos) ?> of <?= $total_photos ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (count($carouselPhotos) > 0): ?>
        <div class="photos-grid">
            <?php foreach ($carouselPhotos as $index => $photo): ?>
                <div class="photo-card">
                    <img src="<?= BASE_URL ?>/assets/images/<?= htmlspecialchars($photo) ?>" alt="Carousel Photo" class="photo-image">
                    <div class="photo-info">
                        <div class="photo-name"><?= htmlspecialchars($photo) ?></div>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this photo?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="photo" value="<?= htmlspecialchars($photo) ?>">
                            <button type="submit" class="photo-delete-btn">🗑️ Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p>No photos uploaded yet. Upload your first photo to get started!</p>
        </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php 
            // Previous button
            if ($page > 1): 
            ?>
            <li class="page-item">
                <a class="page-link" href="manage_carousel_photos.php?page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php endif; ?>
            
            <?php 
            // Page numbers with smart range
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): 
            ?>
            <li class="page-item"><a class="page-link" href="manage_carousel_photos.php?page=1">1</a></li>
            <?php if ($start_page > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="manage_carousel_photos.php?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php 
            if ($end_page < $total_pages): 
                if ($end_page < $total_pages - 1): 
            ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="manage_carousel_photos.php?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
            <?php endif; ?>
            
            <?php 
            // Next button
            if ($page < $total_pages): 
            ?>
            <li class="page-item">
                <a class="page-link" href="manage_carousel_photos.php?page=<?= $page + 1 ?>">Next</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script>
    // Drag and drop functionality
    const uploadArea = document.getElementById('uploadArea');
    const photoInput = document.getElementById('photoInput');
    const uploadForm = document.getElementById('uploadForm');

    uploadArea.addEventListener('click', () => photoInput.click());
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        photoInput.files = e.dataTransfer.files;
        uploadForm.submit();
    });

    photoInput.addEventListener('change', () => {
        if (photoInput.files.length > 0) {
            uploadForm.submit();
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
