<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            $trade_name = sanitizeInput($_POST['trade_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if (!empty($trade_name)) {
                $stmt = $pdo->prepare("INSERT INTO trades (trade_name, description) VALUES (?, ?)");
                $stmt->execute([$trade_name, $description]);
                $_SESSION['success_message'] = "Trade added successfully.";
            } else {
                $_SESSION['error_message'] = "Trade name is required.";
            }
        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $trade_name = sanitizeInput($_POST['trade_name'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            
            if ($id > 0 && !empty($trade_name)) {
                $stmt = $pdo->prepare("UPDATE trades SET trade_name = ?, description = ? WHERE id = ?");
                $stmt->execute([$trade_name, $description, $id]);
                $_SESSION['success_message'] = "Trade updated successfully.";
            } else {
                $_SESSION['error_message'] = "Invalid input for edit.";
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                // Check if trade is used before deleting (foreign key handles restriction, but good to catch)
                try {
                    $stmt = $pdo->prepare("DELETE FROM trades WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success_message'] = "Trade deleted successfully.";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Cannot delete trade. It is currently in use.";
                }
            }
        }
    }
    redirect('/admin/trades.php');
}

// Pagination
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$trades_per_page = 10;

// Get total count for pagination
$count_stmt = $pdo->query("SELECT COUNT(*) as total FROM trades");
$total_trades = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_trades / $trades_per_page);

if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}

$offset = ($page - 1) * $trades_per_page;

// Fetch Trades with pagination
$stmt = $pdo->prepare("SELECT * FROM trades ORDER BY trade_name ASC LIMIT ? OFFSET ?");
$stmt->execute([$trades_per_page, $offset]);
$trades = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Manage Trades</h3>
        <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addTradeModal">
            <i data-lucide="plus" class="me-2" style="width: 16px; height: 16px;"></i> Add New Trade
        </button>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Trade Name</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trades as $trade): ?>
                    <tr>
                        <td><?= $trade['id'] ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($trade['trade_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($trade['description']) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="openEditModal(<?= $trade['id'] ?>, '<?= htmlspecialchars(addslashes($trade['trade_name'])) ?>', '<?= htmlspecialchars(addslashes($trade['description'])) ?>')">
                                Edit
                            </button>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this trade?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $trade['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($trades)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No trades found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small">
                    Showing <?= (($page - 1) * $trades_per_page) + 1 ?> to <?= min($page * $trades_per_page, $total_trades) ?> of <?= $total_trades ?> trades
                </div>
            </div>
            <ul class="pagination justify-content-center mb-0">
                <?php 
                // Previous button
                if ($page > 1): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="trades.php?page=<?= $page - 1 ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                // Page numbers with smart range
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): 
                ?>
                <li class="page-item"><a class="page-link" href="trades.php?page=1">1</a></li>
                <?php if ($start_page > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="trades.php?page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php 
                if ($end_page < $total_pages): 
                    if ($end_page < $total_pages - 1): 
                ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="trades.php?page=<?= $total_pages ?>"><?= $total_pages ?></a></li>
                <?php endif; ?>
                
                <?php 
                // Next button
                if ($page < $total_pages): 
                ?>
                <li class="page-item">
                    <a class="page-link" href="trades.php?page=<?= $page + 1 ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Add Trade Modal -->
<div class="modal fade" id="addTradeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Trade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="add">
                <div class="mb-3"><label class="form-label">Trade Name</label><input type="text" name="trade_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Trade</button></div>
        </form>
    </div>
</div>

<!-- Edit Trade Modal -->
<div class="modal fade" id="editTradeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Trade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_trade_id">
                <div class="mb-3"><label class="form-label">Trade Name</label><input type="text" name="trade_name" id="edit_trade_name" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="edit_trade_desc" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Update Trade</button></div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, desc) {
    document.getElementById('edit_trade_id').value = id;
    document.getElementById('edit_trade_name').value = name;
    document.getElementById('edit_trade_desc').value = desc;
    new bootstrap.Modal(document.getElementById('editTradeModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>