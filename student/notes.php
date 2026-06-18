<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Only students can view notes
if (!hasRole('student')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$user_id = $_SESSION['user_id'];

// Get user's assigned trades
$stmt = $pdo->prepare("
    SELECT DISTINCT st.trade_id, t.trade_name
    FROM student_trades st
    JOIN trades t ON st.trade_id = t.id
    WHERE st.student_id = ?
    ORDER BY t.trade_name
");
$stmt->execute([$user_id]);
$assigned_trades = $stmt->fetchAll();

if (empty($assigned_trades)) {
    $_SESSION['error_message'] = "No trades assigned. Please contact administrator.";
    redirect('/student/dashboard.php');
}

// Get trade IDs array for queries
$trade_ids = array_column($assigned_trades, 'trade_id');
$trade_ids_placeholders = implode(',', array_fill(0, count($trade_ids), '?'));

// DEBUG: Show student info
$student_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$student_stmt->execute([$user_id]);
$student = $student_stmt->fetch();

// Get available subjects from all assigned trades - with duplicate removal
$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, s.trade_id
    FROM subjects s
    WHERE s.trade_id IN ($trade_ids_placeholders)
    ORDER BY s.subject_name ASC, s.id ASC
");
$stmt->execute($trade_ids);
$subjects_raw = $stmt->fetchAll();

// Remove duplicate subject names, keep first occurrence
$subjects = [];
$subject_names_seen = [];
foreach ($subjects_raw as $subject) {
    if (!isset($subject_names_seen[$subject['subject_name']])) {
        $subjects[] = $subject;
        $subject_names_seen[$subject['subject_name']] = true;
    }
}

// Get selected subject and search term - handle empty parameters properly
$subject_filter = !empty($_GET['subject']) ? (int)$_GET['subject'] : 0;
$search_term = !empty($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Clean up URL if both filters are empty (redirect to clean URL)
if (empty($search_term) && $subject_filter === 0 && (isset($_GET['subject']) || isset($_GET['search']))) {
    header("Location: notes.php");
    exit;
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // 6 per page
$offset = ($page - 1) * $limit;

// Build query - Query notes from ALL assigned trades
$query = "
    SELECT n.*, s.subject_name, t.trade_name, u.full_name as uploaded_by_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.uploaded_by = u.id
    WHERE n.trade_id IN ($trade_ids_placeholders) AND n.status = 'active'
";

$params = $trade_ids;

if ($subject_filter > 0) {
    $query .= " AND n.subject_id = ?";
    $params[] = $subject_filter;
}

if (!empty($search_term)) {
    $query .= " AND (n.title LIKE ? OR n.description LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

$query .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notes = $stmt->fetchAll();

// Get total count
$countQuery = "
    SELECT COUNT(*) FROM notes n
    WHERE n.trade_id IN ($trade_ids_placeholders) AND n.status = 'active'
";
$countParams = $trade_ids;

if ($subject_filter > 0) {
    $countQuery .= " AND n.subject_id = ?";
    $countParams[] = $subject_filter;
}

if (!empty($search_term)) {
    $countQuery .= " AND (n.title LIKE ? OR n.description LIKE ?)";
    $countParams[] = "%$search_term%";
    $countParams[] = "%$search_term%";
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalNotes = $countStmt->fetchColumn();
$totalPages = ceil($totalNotes / $limit);

// DEBUG INFO
$debug_trades_count = count($trade_ids);
$debug_subjects_count = count($subjects);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Debug Info -->
    <div class="alert alert-info" role="alert" style="font-size: 12px;">
        <strong>📊 Debug Info:</strong> 
        Student: <code><?php echo htmlspecialchars($student['full_name']); ?></code> | 
        Assigned Trades: <code><?php echo $debug_trades_count; ?></code> | 
        Total Subjects: <code><?php echo $debug_subjects_count; ?></code> | 
        Total Notes Found: <code><?php echo $totalNotes; ?></code>
        <br>
        <strong>Trades:</strong> <?php echo implode(', ', array_column($assigned_trades, 'trade_name')); ?>
    </div>
    
    <!-- Header -->
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">📚 Study Notes</h3>
        <p class="text-muted">Download and study materials for your courses</p>
    </div>
    
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i data-lucide="filter" style="width: 16px; height: 16px; display: inline;"></i> 
                        Filter by Subject
                    </label>
                    <select name="subject" class="form-select" id="subjectFilter">
                        <option value="">📚 All Subjects</option>
                        <?php if (empty($subjects)): ?>
                            <option value="" disabled>No subjects available</option>
                        <?php else: ?>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['id']); ?>" 
                                        <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>
                                        data-trade="<?php echo htmlspecialchars($subject['trade_id']); ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i data-lucide="search" style="width: 16px; height: 16px; display: inline;"></i> 
                        Search Notes
                    </label>
                    <div class="input-group">
                        <input type="text" name="search" id="searchInput" class="form-control" 
                               placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-secondary" type="submit" id="searchBtn">
                            <i data-lucide="search" style="width: 16px; height: 16px;"></i> Search
                        </button>
                        <?php if (!empty($search_term) || $subject_filter > 0): ?>
                            <a href="notes.php" class="btn btn-outline-secondary" id="clearBtn">
                                <i data-lucide="x" style="width: 16px; height: 16px;"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-submit form when subject changes
        document.getElementById('subjectFilter').addEventListener('change', function() {
            // Clear search when subject filter changes
            document.getElementById('searchInput').value = '';
            // Reset to page 1 when filtering
            document.getElementById('filterForm').submit();
        });

        // Handle search input with Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Clear subject filter when searching
                document.getElementById('subjectFilter').value = '';
                document.getElementById('filterForm').submit();
            }
        });

        // Debug info
        console.log('Total subjects in dropdown: <?php echo count($subjects); ?>');
        console.log('Selected subject ID: <?php echo $subject_filter; ?>');
        console.log('Search term: "<?php echo addslashes($search_term); ?>"');
    </script>
    
    <!-- Notes Display -->
    <?php if (empty($notes)): ?>
        <div class="text-center py-5">
            <i data-lucide="inbox" style="width: 64px; height: 64px; opacity: 0.3;"></i>
            <p class="mt-3 text-muted">No notes available for this filter</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <?php foreach ($notes as $note): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 hover-shadow transition">
                        <div class="card-body d-flex flex-column">
                            <!-- Subject Badge -->
                            <div class="mb-2">
                                <span class="badge bg-info"><?php echo htmlspecialchars($note['subject_name']); ?></span>
                            </div>
                            
                            <!-- Title -->
                            <h5 class="card-title fw-bold mb-2" title="<?php echo htmlspecialchars($note['title']); ?>">
                                <?php echo htmlspecialchars(strlen($note['title']) > 50 ? substr($note['title'], 0, 50) . '...' : $note['title']); ?>
                            </h5>
                            
                            <!-- Description -->
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo htmlspecialchars(strlen($note['description'] ?? '') > 80 ? substr($note['description'], 0, 80) . '...' : $note['description']); ?>
                            </p>
                            
                            <!-- Meta Information -->
                            <div class="border-top pt-2 mt-auto">
                                <small class="text-muted">
                                    <i data-lucide="user" style="width: 12px; height: 12px; display: inline;"></i>
                                    <?php echo htmlspecialchars($note['uploaded_by_name']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i data-lucide="calendar" style="width: 12px; height: 12px; display: inline;"></i>
                                    <?php echo date('M d, Y', strtotime($note['created_at'])); ?>
                                </small>
                            </div>
                            
                            <!-- Preview & Download Buttons -->
                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="btn btn-outline-info btn-sm flex-grow-1 btn-preview-pdf" 
                                        data-note-id="<?php echo $note['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($note['title']); ?>"
                                        data-file-path="<?php echo htmlspecialchars($note['file_path']); ?>"
                                        data-subject="<?php echo htmlspecialchars($note['subject_name']); ?>"
                                        data-trade="<?php echo htmlspecialchars($note['trade_name']); ?>"
                                        title="Preview PDF">
                                    <i data-lucide="eye" style="width: 16px; height: 16px; display: inline;"></i> Preview
                                </button>
                                <a href="<?php echo htmlspecialchars(BASE_URL . '/api/serve-pdf.php?file=' . urlencode($note['file_path'])); ?>" 
                                   class="btn btn-primary btn-sm flex-grow-1" 
                                   download="<?php echo htmlspecialchars($note['title']); ?>.pdf"
                                   title="Download PDF">
                                    <i data-lucide="download" style="width: 16px; height: 16px; display: inline;"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="d-flex justify-content-center py-4" aria-label="Page navigation">
            <ul class="pagination mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo $subject_filter ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">First</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $subject_filter ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $subject_filter ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $subject_filter ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Next</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo $subject_filter ? '&subject=' . $subject_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">Last</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}

.hover-shadow:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    transform: translateY(-4px);
}

.transition {
    transition: all 0.3s ease;
}
</style>

<!-- PDF Preview Modal - Enhanced -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl" style="max-width: 95vw;">
        <div class="modal-content h-100" style="max-height: 95vh;">
            <!-- Modal Header -->
            <div class="modal-header border-bottom-0 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <i data-lucide="file-pdf" style="width: 24px; height: 24px; color: #dc3545;"></i>
                    <div>
                        <h5 class="modal-title mb-0" id="pdfTitle" style="font-weight: 600;">PDF Preview</h5>
                        <small class="text-muted" id="pdfSubtitle" style="font-size: 0.85rem;"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Modal Body with Flexible Height -->
            <div class="modal-body p-0 overflow-hidden d-flex flex-column" style="min-height: 500px; max-height: calc(95vh - 180px); background: #f5f5f5; position: relative;">
                
                <!-- Loading Indicator -->
                <div id="pdfLoading" class="d-flex align-items-center justify-content-center w-100 flex-fill bg-light" style="min-height: 400px; z-index: 1;">
                    <div class="text-center">
                        <div class="spinner-border text-danger mb-3" role="status" style="width: 56px; height: 56px;">
                            <span class="visually-hidden">Loading PDF...</span>
                        </div>
                        <p class="text-muted mb-0" style="font-size: 0.95rem;">Loading PDF file...</p>
                        <small class="text-muted d-block mt-1" id="pdfLoadingTime">0s</small>
                    </div>
                </div>
                
                <!-- PDF Viewer Container (Primary - Browser PDF Viewer) -->
                <div id="pdfIframeContainer" class="flex-fill w-100" style="display: none; position: relative; background: white; min-height: 400px; overflow: hidden; z-index: 10;">
                    <iframe id="pdfFrame" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; display: block; background: #fff;"
                            sandbox="allow-same-origin allow-popups allow-presentation"
                            title="PDF Viewer"></iframe>
                </div>
                
                <!-- PDF.js Viewer Container (Fallback) -->
                <div id="pdfJsContainer" style="display: none; width: 100%; flex-fill; background: #343a40; overflow: hidden; position: relative;">
                    <div style="display: flex; flex-direction: column; height: 100%; width: 100%;">
                        <!-- PDF.js Toolbar -->
                        <div class="d-flex align-items-center justify-content-between bg-dark text-white p-2" style="gap: 10px; flex-wrap: wrap; flex-shrink: 0;">
                            <div class="d-flex align-items-center gap-1">
                                <button id="pdfPrevPage" class="btn btn-sm btn-outline-light" title="Previous Page">«</button>
                                <input type="number" id="pdfPageNum" class="form-control form-control-sm" style="width: 80px; text-align: center;">
                                <span id="pdfPageCount" class="text-white" style="font-size: 0.9rem;"></span>
                                <button id="pdfNextPage" class="btn btn-sm btn-outline-light" title="Next Page">»</button>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <button id="pdfZoomOut" class="btn btn-sm btn-outline-light" title="Zoom Out">−</button>
                                <input type="range" id="pdfZoom" class="form-range" min="50" max="300" value="100" style="width: 100px;">
                                <span id="pdfZoomLevel" class="text-white" style="font-size: 0.9rem; min-width: 45px;">100%</span>
                                <button id="pdfZoomIn" class="btn btn-sm btn-outline-light" title="Zoom In">+</button>
                            </div>
                        </div>
                        <!-- Canvas for PDF rendering -->
                        <div id="pdfCanvasContainer" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; align-items: center; padding: 10px; background: #222;">
                            <canvas id="pdfCanvas" style="border: 1px solid #555; max-width: 100%; background: white;"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Error Message -->
                <div id="pdfError" class="flex-fill w-100 d-flex align-items-center" style="display: none; padding: 20px;">
                    <div class="alert alert-danger mb-0 w-100">
                        <div class="d-flex gap-3">
                            <i data-lucide="alert-triangle" style="width: 24px; height: 24px; flex-shrink: 0; margin-top: 3px;"></i>
                            <div>
                                <h5 class="alert-heading mb-2">PDF Preview Error</h5>
                                <p id="pdfErrorMessage" class="mb-0" style="font-size: 0.95rem;"></p>
                                <small id="pdfErrorDetails" class="text-muted d-block mt-2"></small>
                                <a id="pdfErrorDownloadLink" href="#" class="btn btn-sm btn-primary mt-3" download>
                                    <i data-lucide="download" style="width: 14px; height: 14px; display: inline;"></i> Download PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer border-top bg-light">
                <small id="pdfFileInfo" class="text-muted me-auto" style="font-size: 0.85rem;"></small>
                <a id="pdfDownloadBtn" href="#" class="btn btn-primary btn-sm" download title="Download PDF">
                    <i data-lucide="download" style="width: 16px; height: 16px; display: inline;"></i> Download
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    // Set up PDF.js worker
    if (typeof pdfjsLib !== 'undefined') {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    }
</script>

<script>
const BASE_URL = <?php echo json_encode(BASE_URL); ?>;
let currentPdfDoc = null;
let currentPage = 1;
let currentZoom = 100;
const notesData = {};

// ===== MAIN PDF PREVIEW FUNCTION =====
function previewPDF(noteData) {
    currentPdfDoc = null;
    currentPage = 1;
    currentZoom = 100;
    
    const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
    modal.show();
    
    document.getElementById('pdfTitle').textContent = noteData.title || 'Untitled';
    document.getElementById('pdfSubtitle').textContent = (noteData.subject_name || 'Subject') + ' • ' + (noteData.trade_name || 'Trade');
    
    document.getElementById('pdfLoading').style.display = 'flex';
    document.getElementById('pdfIframeContainer').style.display = 'none';
    document.getElementById('pdfJsContainer').style.display = 'none';
    document.getElementById('pdfError').style.display = 'none';
    
    const filePath = noteData.file_path;
    const encodedPath = encodeURIComponent(filePath);
    const serveUrl = BASE_URL + '/api/serve-pdf.php?file=' + encodedPath;
    const checkUrl = BASE_URL + '/api/check-pdf.php?file=' + encodedPath;
    
    console.log('📄 PDF Preview Started');
    console.log('Title:', noteData.title);
    console.log('File Path:', filePath);
    console.log('Check URL:', checkUrl);
    console.log('Serve URL:', serveUrl);
    
    document.getElementById('pdfDownloadBtn').href = serveUrl;
    document.getElementById('pdfDownloadBtn').download = (noteData.title || 'document') + '.pdf';
    document.getElementById('pdfErrorDownloadLink').href = serveUrl;
    document.getElementById('pdfErrorDownloadLink').download = (noteData.title || 'document') + '.pdf';
    
    let loadingSeconds = 0;
    const loadingTimer = setInterval(() => {
        loadingSeconds++;
        document.getElementById('pdfLoadingTime').textContent = loadingSeconds + 's';
        if (loadingSeconds > 30) {
            clearInterval(loadingTimer);
            if (document.getElementById('pdfLoading').style.display !== 'none') {
                showPDFError('⏱️ PDF is taking too long to load. <br><small>The file might be too large or the server is slow.</small>');
            }
        }
    }, 1000);
    
    loadPDFWithIframe(serveUrl, checkUrl, 
        () => clearInterval(loadingTimer),
        (error) => {
            console.error('❌ Iframe loading failed:', error);
            clearInterval(loadingTimer);
            loadPDFWithPdfJs(serveUrl, checkUrl);
        }
    );
}

// ===== IFRAME LOADER WITH STATE TRACKING =====
function loadPDFWithIframe(serveUrl, checkUrl, onSuccess, onError) {
    fetch(checkUrl, { credentials: 'include' })
        .then(response => {
            // Check if response status is ok
            if (!response.ok) {
                throw new Error('HTTP ' + response.status + ': ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('✓ PDF Check Result:', data);
            
            if (!data.success) {
                console.error('❌ PDF check failed:', data.error);
                onError(data.error || 'File not found');
                showPDFError(
                    '❌ PDF file not found.' +
                    '<br><small>Error: ' + (data.error || 'Unknown') + '</small>' +
                    '<br><small class="text-muted">Path: ' + (data.file_path || 'N/A') + '</small>'
                );
                return;
            }
            
            console.log('✓ PDF file verified, loading in iframe...');
            const loadingDiv = document.getElementById('pdfLoading');
            const iframeContainer = document.getElementById('pdfIframeContainer');
            
            // Hide loading and show iframe
            loadingDiv.style.display = 'none';
            loadingDiv.style.visibility = 'hidden';
            iframeContainer.style.display = 'block';
            iframeContainer.style.visibility = 'visible';
            
            const iframe = document.getElementById('pdfFrame');
            let iframeLoaded = false;
            let iframeErrorOccurred = false;
            
            const loadTimeout = setTimeout(() => {
                if (!iframeLoaded && !iframeErrorOccurred) {
                    console.warn('⏱️ Iframe timeout (10s) - fallback to PDF.js');
                    iframeErrorOccurred = true;
                    onError('Iframe timeout');
                    iframeContainer.style.display = 'none';
                    loadingDiv.style.display = 'none';
                    iframe.src = '';
                    loadPDFWithPdfJs(serveUrl, checkUrl);
                }
            }, 10000);
            
            iframe.onload = function() {
                if (!iframeErrorOccurred) {
                    iframeLoaded = true;
                    clearTimeout(loadTimeout);
                    console.log('✓ PDF loaded successfully in iframe');
                    // Ensure loading spinner is hidden and iframe is visible
                    loadingDiv.style.display = 'none';
                    loadingDiv.style.visibility = 'hidden';
                    iframeContainer.style.display = 'block';
                    iframeContainer.style.visibility = 'visible';
                    onSuccess();
                    updateFileInfo(data);
                }
            };
            
            iframe.onerror = function() {
                if (!iframeErrorOccurred) {
                    iframeErrorOccurred = true;
                    clearTimeout(loadTimeout);
                    console.error('❌ Iframe error - fallback to PDF.js');
                    onError('Iframe error');
                    document.getElementById('pdfIframeContainer').style.display = 'none';
                    iframe.src = '';
                    loadPDFWithPdfJs(serveUrl, checkUrl);
                }
            };
            
            iframe.src = serveUrl + '#toolbar=1&navpanes=0&scrollbar=1';
            
            // PDFs don't trigger onload event - hide loading spinner immediately
            loadingDiv.style.display = 'none';
            loadingDiv.style.visibility = 'hidden';
            console.log('✓ PDF viewer should be displaying now');
            
            setTimeout(() => {
                if (!iframeErrorOccurred && !iframeLoaded) {
                    try {
                        if (iframe.contentDocument === null) {
                            console.warn('⚠️ Iframe rendering check failed - fallback to PDF.js');
                            iframeErrorOccurred = true;
                            clearTimeout(loadTimeout);
                            iframeContainer.style.display = 'none';
                            iframe.src = '';
                            loadPDFWithPdfJs(serveUrl, checkUrl);
                        } else {
                            console.log('✓ Iframe document is accessible');
                        }
                    } catch (e) {
                        console.log('ℹ️ Iframe cross-origin (expected):', e.message);
                    }
                }
            }, 1500);
        })
        .catch(error => {
            console.error('❌ Check-PDF API failed:', error);
            onError(error.message);
            loadPDFWithIframeDirectly(serveUrl);
        });
}

// ===== DIRECT IFRAME LOADER (FALLBACK) =====
function loadPDFWithIframeDirectly(serveUrl) {
    console.log('Attempting direct iframe load...');
    const loadingDiv = document.getElementById('pdfLoading');
    const iframeContainer = document.getElementById('pdfIframeContainer');
    
    loadingDiv.style.display = 'none';
    loadingDiv.style.visibility = 'hidden';
    iframeContainer.style.display = 'block';
    iframeContainer.style.visibility = 'visible';
    
    const iframe = document.getElementById('pdfFrame');
    iframe.src = serveUrl + '#toolbar=1&navpanes=0&scrollbar=1';
}

// ===== PDF.JS FALLBACK LOADER =====
function loadPDFWithPdfJs(serveUrl, checkUrl) {
    console.log('📚 Loading PDF with PDF.js fallback...');
    
    if (typeof pdfjsLib === 'undefined') {
        showPDFError('PDF.js library not available. <a href="' + serveUrl + '" class="btn btn-sm btn-primary mt-2" download>Download PDF</a>');
        return;
    }
    
    const loadingDiv = document.getElementById('pdfLoading');
    loadingDiv.style.display = 'none';
    loadingDiv.style.visibility = 'hidden';
    document.getElementById('pdfJsContainer').style.display = 'flex';
    document.getElementById('pdfJsContainer').style.visibility = 'visible';
    
    const pdfjsLib = window.pdfjsLib;
    
    fetch(serveUrl, { credentials: 'include' })
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.arrayBuffer();
        })
        .then(arrayBuffer => {
            console.log('✓ PDF data fetched, rendering...');
            return pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        })
        .then(pdf => {
            currentPdfDoc = pdf;
            console.log('✓ PDF document loaded. Pages:', pdf.numPages);
            
            document.getElementById('pdfPageCount').textContent = ' of ' + pdf.numPages;
            document.getElementById('pdfPageNum').max = pdf.numPages;
            document.getElementById('pdfPageNum').value = 1;
            
            renderPdfPage(1);
        })
        .catch(error => {
            console.error('❌ PDF.js error:', error);
            document.getElementById('pdfJsContainer').style.display = 'none';
            showPDFError('❌ Could not render PDF: ' + error.message + '<br><a href="' + serveUrl + '" class="btn btn-sm btn-primary mt-2" download>Download PDF</a>');
        });
}

// ===== PAGE RENDERING =====
function renderPdfPage(pageNum) {
    if (!currentPdfDoc) return;
    
    currentPage = Math.min(Math.max(pageNum, 1), currentPdfDoc.numPages);
    document.getElementById('pdfPageNum').value = currentPage;
    
    currentPdfDoc.getPage(currentPage).then(page => {
        const canvas = document.getElementById('pdfCanvas');
        const ctx = canvas.getContext('2d');
        
        const viewport = page.getViewport({ scale: currentZoom / 100 });
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        const renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };
        
        page.render(renderContext).promise.then(() => {
            console.log('✓ Page ' + currentPage + ' rendered');
        });
    });
}

// ===== ERROR DISPLAY =====
function showPDFError(message) {
    const loadingDiv = document.getElementById('pdfLoading');
    const iframeContainer = document.getElementById('pdfIframeContainer');
    const jsContainer = document.getElementById('pdfJsContainer');
    const errorDiv = document.getElementById('pdfError');
    
    loadingDiv.style.display = 'none';
    loadingDiv.style.visibility = 'hidden';
    iframeContainer.style.display = 'none';
    iframeContainer.style.visibility = 'hidden';
    jsContainer.style.display = 'none';
    jsContainer.style.visibility = 'hidden';
    errorDiv.style.display = 'flex';
    errorDiv.style.visibility = 'visible';
    
    document.getElementById('pdfErrorMessage').innerHTML = message;
}

// ===== FILE INFO DISPLAY =====
function updateFileInfo(fileData) {
    if (fileData && fileData.size) {
        const sizeKB = Math.round(fileData.size / 1024);
        const sizeMB = (sizeKB / 1024).toFixed(2);
        const fileSize = sizeKB > 1024 ? sizeMB + ' MB' : sizeKB + ' KB';
        document.getElementById('pdfFileInfo').textContent = 'Size: ' + fileSize;
    }
}

// ===== INITIALIZATION ON PAGE LOAD =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('📚 Initializing PDF preview system...');
    
    // Setup note preview buttons
    const noteButtons = document.querySelectorAll('.btn-preview-pdf');
    console.log('Found ' + noteButtons.length + ' preview buttons');
    
    noteButtons.forEach(btn => {
        const noteId = btn.getAttribute('data-note-id');
        const noteData = {
            id: noteId,
            title: btn.getAttribute('data-title') || 'Untitled',
            file_path: btn.getAttribute('data-file-path'),
            subject_name: btn.getAttribute('data-subject') || 'Subject',
            trade_name: btn.getAttribute('data-trade') || 'Trade'
        };
        
        notesData[noteId] = noteData;
        console.log('✓ Registered note #' + noteId + ': ' + noteData.title);
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (noteData && noteData.file_path) {
                console.log('👁️ Previewing note:', noteData.title);
                previewPDF(noteData);
            } else {
                console.error('Invalid note data:', noteData);
                alert('Error: Could not load note data. Please try downloading instead.');
            }
        });
    });
    
    // Setup PDF.js controls
    const pageNum = document.getElementById('pdfPageNum');
    if (pageNum) {
        pageNum.addEventListener('change', function() {
            renderPdfPage(parseInt(this.value) || 1);
        });
    }
    
    document.getElementById('pdfPrevPage')?.addEventListener('click', function() {
        renderPdfPage(currentPage - 1);
    });
    
    document.getElementById('pdfNextPage')?.addEventListener('click', function() {
        renderPdfPage(currentPage + 1);
    });
    
    document.getElementById('pdfZoomOut')?.addEventListener('click', function() {
        currentZoom = Math.max(50, currentZoom - 10);
        document.getElementById('pdfZoom').value = currentZoom;
        document.getElementById('pdfZoomLevel').textContent = currentZoom + '%';
        renderPdfPage(currentPage);
    });
    
    document.getElementById('pdfZoomIn')?.addEventListener('click', function() {
        currentZoom = Math.min(300, currentZoom + 10);
        document.getElementById('pdfZoom').value = currentZoom;
        document.getElementById('pdfZoomLevel').textContent = currentZoom + '%';
        renderPdfPage(currentPage);
    });
    
    document.getElementById('pdfZoom')?.addEventListener('input', function() {
        currentZoom = parseInt(this.value) || 100;
        document.getElementById('pdfZoomLevel').textContent = currentZoom + '%';
        renderPdfPage(currentPage);
    });
    
    console.log('✓ PDF preview system initialized');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
