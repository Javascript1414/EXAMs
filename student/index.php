<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('student'); // Blocks access and redirects if not a student

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Student') ?>!</h3>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center">
            <i data-lucide="log-out" class="me-2" style="width: 16px; height: 16px;"></i> Logout
        </a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                    <i data-lucide="book-open" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Enrolled Courses</h6>
                    <h4 class="mb-0 fw-bold">4</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i data-lucide="clock" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Upcoming Exams</h6>
                    <h4 class="mb-0 fw-bold">1</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                    <i data-lucide="award" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Certificates Earned</h6>
                    <h4 class="mb-0 fw-bold">2</h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card p-4">
        <h5 class="fw-bold mb-3 text-dark">My Progress</h5>
        <div class="alert alert-light border text-muted d-flex align-items-center">
            <i data-lucide="info" class="me-2 text-primary"></i> Course timeline and interactive exam player will be built in the upcoming modules.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>