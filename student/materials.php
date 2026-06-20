<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

// Get student's assigned trade
$student_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_trade_id = $student['trade_id'] ?? 0;

// Fetch filters
$search = sanitizeInput($_GET['search'] ?? '');
$subject_id = (int)($_GET['subject_id'] ?? 0);

// SECURITY: Force student to see ONLY their assigned trade's materials
// Ignore trade_id from GET parameter - always use student's trade
$trade_id = $student_trade_id;

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$items_per_page = 6;

// Build Query - ONLY materials from student's assigned trade (for counting)
$count_query = "SELECT COUNT(*) as total 
                FROM study_materials sm 
                WHERE sm.trade_id = ?";
$count_params = [$trade_id];

if (!empty($search)) {
    $count_query .= " AND (sm.title LIKE ? OR sm.description LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

// If subject_id is specified, validate it belongs to student's trade
if ($subject_id > 0) {
    $subj_check = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND trade_id = ?");
    $subj_check->execute([$subject_id, $trade_id]);
    if ($subj_check->fetch()) {
        $count_query .= " AND sm.subject_id = ?";
        $count_params[] = $subject_id;
    }
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($count_params);
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $items_per_page;

// Build Query - ONLY materials from student's assigned trade (with pagination)
$query = "SELECT sm.*, t.trade_name, s.subject_name 
          FROM study_materials sm 
          JOIN trades t ON sm.trade_id = t.id 
          JOIN subjects s ON sm.subject_id = s.id 
          WHERE sm.trade_id = ?";  // ENFORCE student's trade
$params = [$trade_id];

if (!empty($search)) {
    $query .= " AND (sm.title LIKE ? OR sm.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// If subject_id is specified, validate it belongs to student's trade
if ($subject_id > 0) {
    $subj_check = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND trade_id = ?");
    $subj_check->execute([$subject_id, $trade_id]);
    if ($subj_check->fetch()) {
        $query .= " AND sm.subject_id = ?";
        $params[] = $subject_id;
    }
}
$query .= " ORDER BY sm.created_at DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Get ONLY subjects from student's trade for filter dropdown
$subjects = $pdo->query("SELECT id, subject_name FROM subjects WHERE trade_id = " . (int)$trade_id . " ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .pagination {
        gap: 0.25rem;
    }
    
    .page-link {
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        color: #0d6efd;
        text-decoration: none;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    .page-link:hover:not(.disabled) {
        color: #fff;
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
        transform: translateY(-2px);
    }
    
    .page-item.active .page-link {
        color: #fff;
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
        border-color: #0d6efd;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
    }
    
    .page-item.disabled .page-link {
        color: #999;
        background-color: #f8f9fa;
        border-color: #dee2e6;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">Study Materials</h3>
    
    <!-- Filters -->
    <div class="card p-3 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search title or description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Filter by Subject</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $subject_id === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="filter" style="width:16px;"></i> Filter</button>
                <a href="materials.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <!-- Materials Grid -->
    <div class="row g-4">
        <?php foreach ($materials as $m): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <!-- Top Badge Banner -->
                    <div class="bg-primary text-white p-2 d-flex justify-content-between align-items-center" style="font-size: 0.8rem;">
                        <span class="text-truncate fw-semibold"><?= htmlspecialchars($m['subject_name']) ?></span>
                        <span class="badge bg-light text-primary text-uppercase"><?= htmlspecialchars($m['material_type']) ?></span>
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold text-dark text-truncate mb-2"><?= htmlspecialchars($m['title']) ?></h5>
                        <p class="card-text text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= htmlspecialchars($m['description'] ?? 'No description provided.') ?>
                        </p>
                        
                        <?php if ($m['material_type'] === 'youtube'): 
                            $ytUrl = htmlspecialchars($m['youtube_url']);
                            $embedUrl = preg_replace('/(watch\?v=|youtu\.be\/)/', 'embed/', $ytUrl);
                        ?>
                            <div class="ratio ratio-16x9 rounded overflow-hidden mb-0">
                                <iframe src="<?= str_replace('&', '?', $embedUrl) ?>" title="YouTube video" allowfullscreen></iframe>
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL . '/' . htmlspecialchars($m['file_path']) ?>" target="_blank" class="btn btn-outline-primary w-100 mt-auto d-flex justify-content-center align-items-center">
                                <?php if($m['material_type'] === 'video'): ?> <i data-lucide="play-circle" class="me-2" style="width: 18px;"></i> Watch Video
                                <?php else: ?> <i data-lucide="download" class="me-2" style="width: 18px;"></i> Download File <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($materials)): ?><div class="col-12"><div class="alert alert-light border text-center text-muted p-5">No study materials matched your criteria.</div></div><?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-5 d-flex justify-content-center">
        <ul class="pagination mb-0">
            <!-- Previous Button -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])) ?>" <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>
                    <i data-lucide="chevron-left" style="width: 18px; height: 18px;"></i> Previous
                </a>
            </li>

            <!-- Page Numbers -->
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                </li>
                <?php if ($start_page > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                </li>
            <?php endif; ?>

            <!-- Next Button -->
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])) ?>" <?= $page >= $total_pages ? 'aria-disabled="true"' : '' ?>>
                    Next <i data-lucide="chevron-right" style="width: 18px; height: 18px;"></i>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Pagination Info -->
    <div class="text-center text-muted small mt-3 mb-5">
        Showing <?= $total_items > 0 ? ($offset + 1) : 0 ?> to <?= min($offset + $items_per_page, $total_items) ?> of <?= $total_items ?> materials
        (Page <?= $page ?> of <?= $total_pages ?>)
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>