<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle Delete / Archive Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            if ($action === 'delete') {
                try {
                    $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
                    $_SESSION['success_message'] = "Question deleted successfully.";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Cannot delete question. It may be linked to an exam.";
                }
            } elseif ($action === 'archive') {
                $pdo->prepare("UPDATE questions SET status = 'archived' WHERE id = ?")->execute([$id]);
                $_SESSION['success_message'] = "Question archived successfully.";
            }
        }
    }
    redirect('/admin/questions.php');
}

// Filters
$search = sanitizeInput($_GET['search'] ?? '');
$trade_id = (int)($_GET['trade_id'] ?? 0);
$subject_id = (int)($_GET['subject_id'] ?? 0);
$difficulty = sanitizeInput($_GET['difficulty'] ?? '');

$query = "SELECT q.*, t.trade_name, s.subject_name 
          FROM questions q 
          JOIN trades t ON q.trade_id = t.id 
          JOIN subjects s ON q.subject_id = s.id 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND q.question_text LIKE ?";
    $params[] = "%$search%";
}
if ($trade_id > 0) {
    $query .= " AND q.trade_id = ?";
    $params[] = $trade_id;
}
if ($subject_id > 0) {
    $query .= " AND q.subject_id = ?";
    $params[] = $subject_id;
}
if (!empty($difficulty)) {
    $query .= " AND q.difficulty = ?";
    $params[] = $difficulty;
}

$query .= " ORDER BY q.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$questions = $stmt->fetchAll();

$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Question Bank</h3>
        <div>
            <a href="question_add.php" class="btn btn-primary btn-sm d-inline-flex align-items-center me-2">
                <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Add Single Question
            </a>
            <a href="question_import.php" class="btn btn-success btn-sm d-inline-flex align-items-center me-2">
                <i data-lucide="upload" class="me-2" style="width: 16px; height: 16px;"></i> Bulk Import
            </a>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4 mb-4">
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-muted small mb-1">Search Questions</label>
                <input type="text" name="search" class="form-control" placeholder="Search text..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Trade</label>
                <select name="trade_id" class="form-select">
                    <option value="">All Trades</option>
                    <?php foreach($trades as $t): ?><option value="<?= $t['id'] ?>" <?= $trade_id === $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['trade_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Subject</label>
                <select name="subject_id" class="form-select">
                    <option value="">All Subjects</option>
                    <?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>" <?= $subject_id === $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['subject_name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label text-muted small mb-1">Difficulty</label>
                <select name="difficulty" class="form-select">
                    <option value="">Any</option>
                    <option value="Easy" <?= $difficulty === 'Easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="Medium" <?= $difficulty === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Hard" <?= $difficulty === 'Hard' ? 'selected' : '' ?>>Hard</option>
                </select>
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-primary w-100 me-2"><i data-lucide="filter" style="width:16px;"></i> Filter</button>
                <a href="questions.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="max-width: 300px;">Question</th>
                        <th>Subject & Trade</th>
                        <th>Details</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q): ?>
                    <tr>
                        <td class="text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($q['question_text']) ?>"><?= htmlspecialchars($q['question_text']) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($q['subject_name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($q['trade_name']) ?></div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($q['difficulty']) ?></span>
                            <span class="badge bg-light text-dark border"><?= (float)$q['marks'] ?>M / <?= (float)$q['negative_marks'] ?>M</span>
                            <span class="badge bg-light text-dark border text-uppercase"><?= str_replace('_', ' ', $q['question_type']) ?></span>
                        </td>
                        <td>
                            <?php 
                            $badge = match($q['status']) { 'active' => 'bg-success', 'archived' => 'bg-secondary', default => 'bg-warning text-dark' };
                            echo "<span class='badge {$badge}'>" . ucfirst($q['status']) . "</span>";
                            ?>
                        </td>
                        <td class="text-end">
                            <a href="question_edit.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <form method="POST" action="" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                <?php if($q['status'] !== 'archived'): ?>
                                    <button type="submit" name="action" value="archive" class="btn btn-sm btn-outline-secondary me-1" onclick="return confirm('Archive this question?');">Archive</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Permanently delete this question?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No questions found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>