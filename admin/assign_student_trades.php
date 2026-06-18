<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Get current page early (for redirect purposes)
$current_page = max(1, (int)($_GET['page'] ?? 1));

// Handle assign/unassign trades
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        $student_id = (int)($_POST['student_id'] ?? 0);
        $trade_id = (int)($_POST['trade_id'] ?? 0);
        
        if ($action === 'assign' && $student_id && $trade_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO student_trades (student_id, trade_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $trade_id]);
            $_SESSION['success_message'] = "✓ Trade assigned to student!";
        } elseif ($action === 'unassign' && $student_id && $trade_id) {
            $stmt = $pdo->prepare("DELETE FROM student_trades WHERE student_id = ? AND trade_id = ?");
            $stmt->execute([$student_id, $trade_id]);
            $_SESSION['success_message'] = "✓ Trade removed from student!";
        }
    }
    redirect('/admin/assign_student_trades.php?page=' . $current_page);
}

// Pagination setup
$items_per_page = 5;
$page = $current_page;
$offset = ($page - 1) * $items_per_page;

// Get total students count
$count_result = $pdo->query("
    SELECT COUNT(*) as total
    FROM users u
    WHERE u.role_id = 4
")->fetch();
$total_students = $count_result['total'];
$total_pages = ceil($total_students / $items_per_page);

// Ensure page is within valid range
$page = min($page, max(1, $total_pages));
$offset = ($page - 1) * $items_per_page;

// Get paginated students
$students = $pdo->query("
    SELECT u.id, u.full_name, u.email
    FROM users u
    WHERE u.role_id = 4
    ORDER BY u.full_name
    LIMIT $items_per_page OFFSET $offset
")->fetchAll();

// Get all trades
$trades = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name")->fetchAll();

// Debug: Show total trades available
$total_trades = count($trades);

require_once __DIR__ . '/../includes/header.php';
// Navbar removed for full-width display
?>

<style>
/* Hide all sidebar/navbar elements */
.sidebar, nav, .navbar, .sidenav, aside, [class*="sidebar"], [class*="nav"], [class*="menu"], .offcanvas {
    display: none !important;
    visibility: hidden !important;
}

/* Hide elements with sidebar-related IDs */
#sidebar, #nav, #navbar, #sidenav, #menu, #offcanvas {
    display: none !important;
    visibility: hidden !important;
}

/* Fix body layout - disable flex/grid from header.php */
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    display: block !important;
}

/* Override all flexbox layouts */
*, *::before, *::after {
    flex-direction: initial !important;
}

body > *, .container, .container-fluid, main, .main-content {
    display: block !important;
    flex-direction: initial !important;
    flex-wrap: initial !important;
}

/* Force single column layout */
body > .d-flex, 
body > div[class*="d-flex"],
body > div[class*="flex"],
[class*="row"]:not(.table-row):not(.row-group):not([role="row"]) {
    display: block !important;
    flex: initial !important;
}

/* Fix container width and layout */
.container-fluid, .container {
    display: block !important;
    margin: 0 !important;
    padding: 2rem !important;
    width: 100% !important;
    max-width: 100% !important;
    flex: initial !important;
}

/* Ensure content flows properly */
main, .main-content, [role="main"] {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Fix stats box to stack vertically */
.page-header, .stats-container, .page-stats {
    display: block !important;
    width: 100% !important;
    margin-bottom: 2rem !important;
}

/* ===== PAGE BACKGROUND ===== */

/* ===== HEADER STYLING ===== */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.page-header h3 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.stats-box {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 1rem 1.5rem;
    border-radius: 10px;
    color: white;
    font-size: 0.9rem;
    font-weight: 500;
}

.stats-box strong {
    color: #ffd700;
}

/* ===== CARD STYLING ===== */
.main-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    margin-bottom: 2rem;
    animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== TABLE STYLING ===== */
.table-responsive {
    border-radius: 8px;
}

.table {
    margin-bottom: 0;
}

.table thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table thead th {
    border: none;
    font-weight: 600;
    padding: 1rem;
    letter-spacing: 0.5px;
}

.table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

.table tbody tr:hover {
    background-color: #f8f9ff;
    transform: translateX(5px);
    box-shadow: inset 5px 0 0 #667eea;
}

.table tbody td {
    padding: 1.2rem;
    vertical-align: middle;
}

.table tbody tr:last-child {
    border-bottom: none;
}

/* ===== STUDENT NAME STYLING ===== */
.student-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.05rem;
}

.student-email {
    color: #7f8c8d;
    font-size: 0.9rem;
}

/* ===== BADGE STYLING ===== */
.badge {
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    animation: badgePop 0.3s ease-out;
}

@keyframes badgePop {
    0% {
        transform: scale(0.8);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.badge-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    color: white;
    font-weight: 600;
}

.badge-success {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
}

.badge-success:hover {
    box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
}

/* ===== REMOVE BUTTON (×) ===== */
.badge button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-weight: bold;
    padding: 0 4px;
    margin-left: 4px;
    font-size: 1.2rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
}

.badge button:hover {
    transform: scale(1.3) rotate(90deg);
    text-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

.badge button:active {
    transform: scale(1.1) rotate(90deg);
}

/* ===== INTERACTIVE SELECT STYLING ===== */
.trade-select-form {
    display: inline-block;
}

.trade-select-form select {
    border: 2px solid #e9ecef !important;
    border-radius: 8px !important;
    padding: 10px 14px !important;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    background-color: #fff !important;
    background-image: linear-gradient(45deg, transparent 50%, #667eea 50%), linear-gradient(135deg, #667eea 50%, transparent 50%) !important;
    background-position: right 12px center, right 8px center !important;
    background-size: 5px 5px, 5px 5px !important;
    background-repeat: no-repeat !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    padding-right: 32px !important;
    color: #2c3e50;
    min-width: 160px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.trade-select-form select:hover {
    border-color: #667eea !important;
    background-color: #f8f9ff !important;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.25) !important;
    transform: translateY(-2px);
}

.trade-select-form select:focus {
    border-color: #667eea !important;
    outline: none !important;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15), 0 4px 15px rgba(102, 126, 234, 0.25) !important;
    background-color: #fff !important;
}

.trade-select-form select:active {
    transform: translateY(0);
}

.trade-select-form select option {
    padding: 10px 12px;
    background-color: #fff;
    color: #2c3e50;
    font-weight: 500;
}

.trade-select-form select option:hover {
    background: linear-gradient(#667eea, #667eea);
    background-color: #667eea;
    color: white;
}

.trade-select-form select option:checked {
    background: linear-gradient(#667eea, #667eea);
    background-color: #667eea;
    color: white;
    font-weight: bold;
}

.trade-select-form select:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ===== PAGINATION STYLING ===== */
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.pagination-container a,
.pagination-container button,
.pagination-container span {
    padding: 8px 12px;
    border-radius: 6px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    text-decoration: none;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination-container a:hover,
.pagination-container button:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.pagination-container a:active,
.pagination-container button:active {
    transform: translateY(0);
}

.pagination-container button:disabled,
.pagination-container a:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    border-color: #ccc;
    color: #999;
}

.pagination-container .page-info {
    font-weight: 600;
    color: #667eea;
    background: rgba(102, 126, 234, 0.1);
    padding: 8px 12px;
    border-radius: 6px;
}

/* ===== FLASH MESSAGES ===== */
.alert {
    border-radius: 10px;
    border: none;
    animation: slideInDown 0.5s ease-out;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    margin-bottom: 1.5rem;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    border-left: 5px solid #2e7d32;
}

.alert-danger {
    background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
    color: white;
    border-left: 5px solid #c62828;
}

/* ===== "NO TRADES" STYLING ===== */
.no-trades {
    color: #e74c3c;
    font-weight: 600;
    font-size: 0.95rem;
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 768px) {
    .page-header h3 {
        font-size: 1.5rem;
    }

    .stats-box {
        font-size: 0.8rem;
        padding: 0.8rem 1rem;
        margin-top: 0.5rem;
    }

    .table {
        font-size: 0.9rem;
    }

    .table tbody td {
        padding: 0.8rem;
    }

    .trade-select-form select {
        min-width: 140px;
        font-size: 0.85rem;
    }
}

/* ===== SCROLL ANIMATION ===== */
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.table tbody tr {
    animation: fadeIn 0.5s ease-out;
}

.table tbody tr:nth-child(1) { animation-delay: 0.05s; }
.table tbody tr:nth-child(2) { animation-delay: 0.1s; }
.table tbody tr:nth-child(3) { animation-delay: 0.15s; }
.table tbody tr:nth-child(4) { animation-delay: 0.2s; }
.table tbody tr:nth-child(5) { animation-delay: 0.25s; }
</style>

<script>
// Handle form confirmations for remove trade
document.querySelectorAll('form[onsubmit]').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!confirm('Remove this trade?')) {
            e.preventDefault();
        }
    });
});

// Add smooth transition effect on select
document.querySelectorAll('.trade-select-form select').forEach(select => {
    select.addEventListener('change', function() {
        this.style.opacity = '0.7';
        setTimeout(() => {
            this.style.opacity = '1';
        }, 300);
    });
});
</script>

<div class="page-header">
    <div class="container-fluid px-4">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3>👥 Assign Multiple Trades to Students</h3>
                <p style="color: rgba(255,255,255,0.8); margin: 0; font-size: 0.95rem;">Manage and assign trades to your students efficiently</p>
            </div>
            <div class="stats-box">
                <div><strong>Total Students:</strong> <?php echo $total_students; ?></div>
                <div style="margin-top: 0.5rem;"><strong>Total Trades:</strong> <?php echo $total_trades; ?></div>
                <div style="margin-top: 0.5rem;"><strong>Page <?php echo $page; ?>/<?php echo max(1, $total_pages); ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4 py-4">
    <?php displayFlashMessages(); ?>
    
    <div class="card main-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom-0">
                        <tr>
                            <th>👤 Student Name & Email</th>
                            <th>🎓 Assigned Trades</th>
                            <th class="text-center">⚙️ Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php
                            // Get assigned trades for this student
                            $stmt = $pdo->prepare("
                                SELECT t.id, t.trade_name
                                FROM student_trades st
                                JOIN trades t ON st.trade_id = t.id
                                WHERE st.student_id = ?
                                ORDER BY t.trade_name
                            ");
                            $stmt->execute([$student['id']]);
                            $assigned = $stmt->fetchAll();
                            ?>
                            <tr>
                                <td><strong class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></strong><br><span class="student-email"><?php echo htmlspecialchars($student['email']); ?></span></td>
                                <td>
                                    <?php if (empty($assigned)): ?>
                                        <span class="badge badge-danger">No Trades</span>
                                    <?php else: ?>
                                        <?php foreach ($assigned as $trade): ?>
                                            <span class="badge badge-success me-2 mb-2">
                                                <?php echo htmlspecialchars($trade['trade_name']); ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this trade?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                                    <input type="hidden" name="action" value="unassign">
                                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                    <input type="hidden" name="trade_id" value="<?php echo $trade['id']; ?>">
                                                    <button type="submit" style="background: none; border: none; color: white; cursor: pointer; font-weight: bold; padding: 0 4px; margin-left: 4px;">×</button>
                                                </form>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center" style="width: 200px;">
                                    <form method="POST" class="trade-select-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="assign">
                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                        
                                        <select name="trade_id" onchange="
                                            if (this.value) {
                                                this.closest('form').submit();
                                            }
                                        ">
                                            <option value="">+ Assign Trade</option>
                                            <?php foreach ($trades as $trade): ?>
                                                <?php
                                                $is_assigned = false;
                                                foreach ($assigned as $a) {
                                                    if ($a['id'] == $trade['id']) {
                                                        $is_assigned = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <?php if (!$is_assigned): ?>
                                                    <option value="<?php echo $trade['id']; ?>">
                                                        ✓ <?php echo htmlspecialchars($trade['trade_name']); ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
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

<!-- Pagination Controls at Bottom -->
<div class="container-fluid px-4 py-4">
    <div class="pagination-container">
        <?php if ($page > 1): ?>
            <a href="?page=1">« First</a>
            <a href="?page=<?php echo $page - 1; ?>">‹ Previous</a>
        <?php else: ?>
            <button disabled>« First</button>
            <button disabled>‹ Previous</button>
        <?php endif; ?>
        
        <!-- Page Numbers -->
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <span>...</span>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="page-info"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
                <span>...</span>
            <?php endif; ?>
        </div>
        
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
            <a href="?page=<?php echo $total_pages; ?>">Last »</a>
        <?php else: ?>
            <button disabled>Next ›</button>
            <button disabled>Last »</button>
        <?php endif; ?>
    </div>
</div>

<style>
.badge form {
    display: inline;
}

.badge button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-weight: bold;
    padding: 0 2px;
}

.badge button:hover {
    opacity: 0.8;
}

.dropdown-item[type="submit"] {
    padding: 0.5rem 1rem;
    display: block;
    width: 100%;
    text-align: left;
    background-color: transparent;
    border: none;
    cursor: pointer;
    color: inherit;
}

.dropdown-item[type="submit"]:hover {
    background-color: #e9ecef;
}
</style>
