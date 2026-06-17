<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('moderator'); // Blocks access and redirects if not a moderator

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Moderator Dashboard</h3>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                    <i data-lucide="file-check-2" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Active Exams</h6>
                    <h4 class="mb-0 fw-bold">12</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i data-lucide="edit-3" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Pending Grades</h6>
                    <h4 class="mb-0 fw-bold">34</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                    <i data-lucide="message-square" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Forum Flags</h6>
                    <h4 class="mb-0 fw-bold">2</h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card p-4">
        <h5 class="fw-bold mb-3 text-dark">Tasks Needing Attention</h5>
        <div class="alert alert-light border text-muted d-flex align-items-center">
            <i data-lucide="info" class="me-2 text-primary"></i> Course content and exam grading UI will be implemented in Phase 6.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>