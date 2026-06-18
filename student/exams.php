<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

// Fetch user's assigned trade
$stmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userTrade = $stmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$exams_per_page = 9;

// Fetch published exams for this trade - COUNT
// CRITICAL: Only show exams from teachers who are assigned to that subject
$count_query = "SELECT COUNT(DISTINCT e.id) as total 
                FROM exams e 
                JOIN subjects s ON e.subject_id = s.id 
                JOIN subject_teacher st ON s.id = st.subject_id AND e.created_by = st.teacher_id
                WHERE e.trade_id = ? AND e.status = 'published'";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute([$userTrade]);
$total_exams = $count_stmt->fetch()['total'];
$total_pages = ceil($total_exams / $exams_per_page);

// Ensure page is within bounds
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $exams_per_page;

// Fetch published exams for this trade with teacher info
$query = "SELECT e.*, s.subject_name, 
            u.full_name as teacher_name, u.email as teacher_email,
            (SELECT COUNT(*) FROM exam_questions eq WHERE eq.exam_id = e.id) as question_count,
            (SELECT status FROM exam_attempts ea WHERE ea.exam_id = e.id AND ea.student_id = ? ORDER BY id DESC LIMIT 1) as attempt_status
          FROM exams e 
          JOIN subjects s ON e.subject_id = s.id 
          JOIN subject_teacher st ON s.id = st.subject_id AND e.created_by = st.teacher_id
          JOIN users u ON e.created_by = u.id
          WHERE e.trade_id = ? AND e.status = 'published'
          ORDER BY e.created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id'], $userTrade, $exams_per_page, $offset]);
$exams = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .exams-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
    
    @media (max-width: 1200px) {
        .exams-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .exams-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<div class="container-fluid px-0">
    <h3 class="fw-bold text-dark mb-4">Available Exams</h3>
    
    <?php displayFlashMessages(); ?>
    
    <!-- Results Info -->
    <?php if ($total_exams > 0): ?>
    <div class="mb-3 text-muted small">
        Showing <?= $total_exams > 0 ? (($page - 1) * $exams_per_page) + 1 : 0 ?> to <?= min($page * $exams_per_page, $total_exams) ?> of <?= $total_exams ?> exams
    </div>
    <?php endif; ?>
    
    <div class="exams-grid">
        <?php foreach ($exams as $e): ?>
            <div>
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="bg-primary text-white p-3">
                        <span class="badge bg-light text-primary mb-2"><?= htmlspecialchars($e['exam_type']) ?></span>
                        <h5 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($e['exam_name']) ?></h5>
                        <small class="opacity-75"><?= htmlspecialchars($e['subject_name']) ?></small>
                    </div>
                    <div class="card-body">
                        <!-- Teacher Info -->
                        <div style="background: #f0f0f0; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85em;">
                            <div style="color: #666;"><strong>👨‍🏫 Teacher:</strong></div>
                            <div style="color: #333;"><strong><?= htmlspecialchars($e['teacher_name']) ?></strong></div>
                            <div style="color: #666; font-size: 0.8em;">📧 <?= htmlspecialchars($e['teacher_email']) ?></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small"><i data-lucide="clock" style="width:14px;"></i> <?= $e['duration_minutes'] ?> Mins</span>
                            <span class="text-muted small"><i data-lucide="check-circle" style="width:14px;"></i> <?= (float)$e['passing_marks'] ?> / <?= (float)$e['total_marks'] ?> Pass</span>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-muted small"><i data-lucide="help-circle" style="width:14px;"></i> <?= $e['question_count'] ?> Questions</span>
                            <span class="text-muted small"><i data-lucide="alert-triangle" style="width:14px;"></i> <?= $e['negative_marking_enabled'] ? 'Negative Marking' : 'No Negative Marks' ?></span>
                        </div>
                        
                        <?php if ($e['attempt_status'] === 'submitted'): ?>
                            <button class="btn btn-secondary w-100 disabled">Already Attempted</button>
                        <?php elseif ($e['attempt_status'] === 'in_progress'): ?>
                            <a href="exam_attempt.php?id=<?= $e['id'] ?>" class="btn btn-warning w-100 fw-bold">Resume Attempt</a>
                        <?php else: ?>
                            <a href="exam_instructions.php?id=<?= $e['id'] ?>" class="btn btn-primary w-100 fw-bold">Start Exam</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($exams)): ?><div style="grid-column: 1 / -1;"><div class="alert alert-light border text-center text-muted p-5">No exams are currently available for your trade.</div></div><?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-5">
        <ul class="pagination justify-content-center mb-4">
            <?php 
            // Previous button
            if ($page > 1): 
            ?>
            <li class="page-item">
                <a class="page-link" href="exams.php?page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php endif; ?>
            
            <?php 
            // Page numbers with smart range
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): 
            ?>
            <li class="page-item"><a class="page-link" href="exams.php?page=1">1</a></li>
            <?php if ($start_page > 2): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="exams.php?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            
            <?php 
            if ($end_page < $total_pages): 
                if ($end_page < $total_pages - 1): 
            ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="exams.php?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
            <?php endif; ?>
            
            <?php 
            // Next button
            if ($page < $total_pages): 
            ?>
            <li class="page-item">
                <a class="page-link" href="exams.php?page=<?= $page + 1 ?>">Next</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>