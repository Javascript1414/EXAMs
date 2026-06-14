<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Fetch Analytics Data
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$totalTrades = $pdo->query("SELECT COUNT(*) FROM trades")->fetchColumn();
$totalExamsAttempted = $pdo->query("SELECT COUNT(*) FROM exam_attempts")->fetchColumn();
$totalCertificates = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$avgScore = $pdo->query("SELECT AVG(percentage) FROM results")->fetchColumn() ?? 0;

// Chart Data: Growth Trend (Last 7 Days)
$trendStmt = $pdo->query("SELECT DATE(created_at) as d, COUNT(*) as c FROM users GROUP BY DATE(created_at) ORDER BY d DESC LIMIT 7");
$growthTrend = array_reverse($trendStmt->fetchAll(PDO::FETCH_KEY_PAIR));

// Chart Data: Pass/Fail Ratio
$passFailStmt = $pdo->query("SELECT is_passed, COUNT(*) as c FROM results GROUP BY is_passed");
$passFailRaw = $passFailStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$passCount = $passFailRaw[1] ?? 0;
$failCount = $passFailRaw[0] ?? 0;

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Analytics Dashboard</h3>
    </div>
    
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-primary border-4">
                <div class="bg-primary bg-opacity-10 text-primary rounded p-3 me-3">
                    <i data-lucide="users" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Total Registered Users</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($totalUsers) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-success border-4">
                <div class="bg-success bg-opacity-10 text-success rounded p-3 me-3">
                    <i data-lucide="activity" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Active Users</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($activeUsers) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-warning border-4">
                <div class="bg-warning bg-opacity-10 text-warning rounded p-3 me-3">
                    <i data-lucide="library" style="width: 26px; height: 26px;"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1" style="font-size: 0.9rem;">Total Trades</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($totalTrades) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-info border-4">
                <div class="bg-info bg-opacity-10 text-info rounded p-3 me-3"><i data-lucide="edit-3" style="width: 26px; height: 26px;"></i></div>
                <div><h6 class="text-muted mb-1" style="font-size: 0.9rem;">Exams Attempted</h6><h3 class="mb-0 fw-bold"><?= number_format($totalExamsAttempted) ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-danger border-4">
                <div class="bg-danger bg-opacity-10 text-danger rounded p-3 me-3"><i data-lucide="award" style="width: 26px; height: 26px;"></i></div>
                <div><h6 class="text-muted mb-1" style="font-size: 0.9rem;">Certificates Issued</h6><h3 class="mb-0 fw-bold"><?= number_format($totalCertificates) ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 d-flex flex-row align-items-center border-start border-secondary border-4">
                <div class="bg-secondary bg-opacity-10 text-secondary rounded p-3 me-3"><i data-lucide="target" style="width: 26px; height: 26px;"></i></div>
                <div><h6 class="text-muted mb-1" style="font-size: 0.9rem;">Average Platform Score</h6><h3 class="mb-0 fw-bold"><?= number_format($avgScore, 2) ?>%</h3></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card p-4 h-100 shadow-sm border-0">
                <h5 class="fw-bold mb-4">User Growth Trend (Last 7 Days)</h5>
                <canvas id="growthChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100 shadow-sm border-0">
                <h5 class="fw-bold mb-4">Exam Pass/Fail Ratio</h5>
                <canvas id="passFailChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
const gCtx = document.getElementById('growthChart').getContext('2d');
new Chart(gCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($growthTrend)) ?>,
        datasets: [{
            label: 'New Registrations',
            data: <?= json_encode(array_values($growthTrend)) ?>,
            borderColor: '#0056D2', backgroundColor: 'rgba(0, 86, 210, 0.1)', borderWidth: 3, fill: true, tension: 0.3
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

const pfCtx = document.getElementById('passFailChart').getContext('2d');
new Chart(pfCtx, {
    type: 'doughnut',
    data: {
        labels: ['Passed', 'Failed'],
        datasets: [{ data: [<?= $passCount ?>, <?= $failCount ?>], backgroundColor: ['#198754', '#dc3545'], borderWidth: 0 }]
    },
    options: { cutout: '65%', plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>