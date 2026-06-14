<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Explicit role check for Admin routing
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Admin Overview</h3>
        <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm d-flex align-items-center">
            <i data-lucide="log-out" class="me-2" style="width: 16px; height: 16px;"></i> Logout
        </a>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Metrics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                    <i data-lucide="users" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Total Students</h6>
                    <h4 class="mb-0 fw-bold">1,248</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3">
                    <i data-lucide="library" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Active Subjects</h6>
                    <h4 class="mb-0 fw-bold">42</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                    <i data-lucide="award" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Certificates Issued</h6>
                    <h4 class="mb-0 fw-bold">856</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i data-lucide="trending-up" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Avg Exam Score</h6>
                    <h4 class="mb-0 fw-bold">78%</h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card p-4 mb-4">
        <h5 class="fw-bold mb-3 text-dark">Recent System Activity</h5>
        <p class="text-muted mb-0">The data tables for enrollments and module management will be implemented in Phase 6.</p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>