<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$query = "SELECT sm.id, sm.title, sm.material_type, sm.view_count, sm.download_count, sm.average_rating, sm.is_featured, t.trade_name, s.subject_name,
          (SELECT COUNT(*) FROM material_ratings mr WHERE mr.material_id = sm.id) as review_count
          FROM study_materials sm 
          JOIN trades t ON sm.trade_id = t.id 
          JOIN subjects s ON sm.subject_id = s.id 
          ORDER BY sm.view_count DESC, sm.average_rating DESC";
$materials = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Material Analytics</h3>
    </div>

    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material Title</th>
                        <th>Location</th>
                        <th class="text-center">Views</th>
                        <th class="text-center">Downloads</th>
                        <th class="text-center">Rating</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($m['title']) ?></div>
                            <div class="small text-muted text-uppercase"><?= htmlspecialchars($m['material_type']) ?></div>
                        </td>
                        <td><div class="small"><?= htmlspecialchars($m['subject_name']) ?></div><div class="small text-muted"><?= htmlspecialchars($m['trade_name']) ?></div></td>
                        <td class="text-center"><span class="badge bg-light text-primary border"><i data-lucide="eye" style="width:12px;"></i> <?= $m['view_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-light text-success border"><i data-lucide="download" style="width:12px;"></i> <?= $m['download_count'] ?></span></td>
                        <td class="text-center">
                            <div class="fw-bold text-warning"><i data-lucide="star" style="width:14px; fill: currentColor;"></i> <?= $m['average_rating'] > 0 ? number_format($m['average_rating'], 1) : 'N/A' ?></div>
                            <div class="small text-muted">(<?= $m['review_count'] ?> reviews)</div>
                        </td>
                        <td class="text-center"><?= $m['is_featured'] ? '<span class="badge bg-warning text-dark"><i data-lucide="star" style="width:12px;"></i> Featured</span>' : '<span class="badge bg-secondary">Standard</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>