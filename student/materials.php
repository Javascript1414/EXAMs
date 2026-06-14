<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

// Fetch filters
$search = sanitizeInput($_GET['search'] ?? '');
$trade_id = (int)($_GET['trade_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);

// Build Query
$query = "SELECT sm.*, t.trade_name, s.subject_name 
          FROM study_materials sm 
          JOIN trades t ON sm.trade_id = t.id 
          JOIN subjects s ON sm.subject_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (sm.title LIKE ? OR sm.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($trade_id > 0) {
    $query .= " AND sm.trade_id = ?";
    $params[] = $trade_id;
}
if ($subject_id > 0) {
    $query .= " AND sm.subject_id = ?";
    $params[] = $subject_id;
}
$query .= " ORDER BY sm.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">Study Materials</h3>
    
    <!-- Filters -->
    <div class="card p-3 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search title or description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Filter by Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>