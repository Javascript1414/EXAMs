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
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$questions_per_page = 10;

$base_query = "SELECT q.*, t.trade_name, s.subject_name, s.id as subject_id_val
               FROM questions q 
               JOIN trades t ON q.trade_id = t.id 
               JOIN subjects s ON q.subject_id = s.id 
               WHERE 1=1";
$params = [];

if (!empty($search)) {
    $base_query .= " AND q.question_text LIKE ?";
    $params[] = "%$search%";
}
if ($trade_id > 0) {
    $base_query .= " AND q.trade_id = ?";
    $params[] = $trade_id;
}
if ($subject_id > 0) {
    $base_query .= " AND q.subject_id = ?";
    $params[] = $subject_id;
}
if (!empty($difficulty)) {
    $base_query .= " AND q.difficulty = ?";
    $params[] = $difficulty;
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM questions q 
                JOIN trades t ON q.trade_id = t.id 
                JOIN subjects s ON q.subject_id = s.id WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND q.question_text LIKE ?";
}
if ($trade_id > 0) {
    $count_query .= " AND q.trade_id = ?";
}
if ($subject_id > 0) {
    $count_query .= " AND q.subject_id = ?";
}
if (!empty($difficulty)) {
    $count_query .= " AND q.difficulty = ?";
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_questions = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_questions / $questions_per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $questions_per_page;

$query = $base_query . " ORDER BY s.subject_name, q.created_at DESC LIMIT ? OFFSET ?";
$params[] = $questions_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$all_questions = $stmt->fetchAll();

// Group questions by subject
$questions_by_subject = [];
foreach ($all_questions as $q) {
    $subject_name = $q['subject_name'];
    if (!isset($questions_by_subject[$subject_name])) {
        $questions_by_subject[$subject_name] = [];
    }
    $questions_by_subject[$subject_name][] = $q;
}

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
        <?php if (empty($questions_by_subject)): ?>
            <div class="text-center py-5 text-muted">
                <p>No questions found matching your criteria.</p>
            </div>
        <?php else: ?>
            <!-- Subject-wise Accordion -->
            <div class="accordion" id="subjectAccordion">
                <?php $accordion_index = 0; foreach ($questions_by_subject as $subject_name => $questions): $accordion_index++; ?>
                
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $accordion_index !== 1 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $accordion_index ?>" aria-expanded="<?= $accordion_index === 1 ? 'true' : 'false' ?>" aria-controls="collapse<?= $accordion_index ?>">
                            <span class="fw-semibold"><?= htmlspecialchars($subject_name) ?></span>
                            <span class="badge bg-primary ms-2"><?= count($questions) ?></span>
                        </button>
                    </h2>
                    <div id="collapse<?= $accordion_index ?>" class="accordion-collapse collapse <?= $accordion_index === 1 ? 'show' : '' ?>" data-bs-parent="#subjectAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="max-width: 300px;">Question</th>
                                            <th>Trade</th>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <div class="card p-4 mt-4">
        <nav aria-label="Page navigation">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small">
                    Showing <?= (($page - 1) * $questions_per_page) + 1 ?> to <?= min($page * $questions_per_page, $total_questions) ?> of <?= $total_questions ?> questions
                </div>
            </div>
            <ul class="pagination justify-content-center mb-0">
                <?php 
                // Previous button
                if ($page > 1): 
                    $prev_params = ['page' => $page - 1];
                    if (!empty($search)) $prev_params['search'] = $search;
                    if ($trade_id > 0) $prev_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $prev_params['subject_id'] = $subject_id;
                    if (!empty($difficulty)) $prev_params['difficulty'] = $difficulty;
                ?>
                <li class="page-item">
                    <a class="page-link" href="questions.php?<?= http_build_query($prev_params) ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="questions.php">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): 
                    $page_params = ['page' => $i];
                    if (!empty($search)) $page_params['search'] = $search;
                    if ($trade_id > 0) $page_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $page_params['subject_id'] = $subject_id;
                    if (!empty($difficulty)) $page_params['difficulty'] = $difficulty;
                ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="questions.php?<?= http_build_query($page_params) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="questions.php?page=<?= $total_pages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $trade_id > 0 ? '&trade_id=' . $trade_id : '' ?><?= $subject_id > 0 ? '&subject_id=' . $subject_id : '' ?><?= !empty($difficulty) ? '&difficulty=' . urlencode($difficulty) : '' ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                    $next_params = ['page' => $page + 1];
                    if (!empty($search)) $next_params['search'] = $search;
                    if ($trade_id > 0) $next_params['trade_id'] = $trade_id;
                    if ($subject_id > 0) $next_params['subject_id'] = $subject_id;
                    if (!empty($difficulty)) $next_params['difficulty'] = $difficulty;
                ?>
                <li class="page-item">
                    <a class="page-link" href="questions.php?<?= http_build_query($next_params) ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>