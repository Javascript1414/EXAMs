<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

// Native XLSX Parser utilizing ZipArchive
function parseXLSX($filePath) {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($filePath) === true) {
        $sharedStrings = [];
        if (($indexXML = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($indexXML);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $val) {
                    $text = "";
                    if (isset($val->t)) { $text = (string)$val->t; }
                    else { foreach($val->r as $r) { $text .= (string)$r->t; } }
                    $sharedStrings[] = $text;
                }
            }
        }

        if (($sheetXML = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
            $xml = simplexml_load_string($sheetXML);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $rowData = [];
                    $cellIndex = 0;
                    foreach ($row->c as $c) {
                        $r = (string)$c['r']; 
                        $colLetter = preg_replace('/[0-9]/', '', $r);
                        $colNum = 0;
                        for($i=0; $i<strlen($colLetter); $i++) {
                            $colNum = $colNum * 26 + (ord($colLetter[$i]) - 64);
                        }
                        $colNum--; 

                        while ($cellIndex < $colNum) {
                            $rowData[] = "";
                            $cellIndex++;
                        }

                        $v = (string)$c->v;
                        if (isset($c['t']) && $c['t'] == 's') {
                            $v = $sharedStrings[(int)$v] ?? $v;
                        }
                        $rowData[] = $v;
                        $cellIndex++;
                    }
                    $rows[] = $rowData;
                }
            }
        }
        $zip->close();
    }
    return $rows;
}

function extractRows($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $rows = [];
    if ($ext === 'csv') {
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    } elseif ($ext === 'xlsx') {
        $rows = parseXLSX($filePath);
    }
    return $rows;
}

$step = 1;
$previewData = [];
$tmpFile = '';
$fileName = '';
$stats = ['total' => 0, 'valid' => 0, 'invalid' => 0, 'duplicate' => 0];

// Cache Trades and Subjects for rapid in-memory validation
$tradesCache = [];
foreach($pdo->query("SELECT id, trade_name FROM trades")->fetchAll() as $t) {
    $tradesCache[strtolower(trim($t['trade_name']))] = $t['id'];
}
$subjectsCache = [];
foreach($pdo->query("SELECT id, trade_id, subject_name FROM subjects")->fetchAll() as $s) {
    $subjectsCache[$s['trade_id'] . '_' . strtolower(trim($s['subject_name']))] = $s['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'preview') {
            if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['csv', 'xlsx'])) {
                    $_SESSION['error_message'] = "Invalid file type. Only CSV and XLSX are supported.";
                } else {
                    $uploadDir = __DIR__ . '/../uploads/temp_imports/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    
                    // Clean up old temp files (older than 1 hour)
                    foreach (glob($uploadDir . '*') as $oldFile) {
                        if (is_file($oldFile) && time() - filemtime($oldFile) > 3600) {
                            @unlink($oldFile);
                        }
                    }
                    
                    $tmpFile = $uploadDir . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['import_file']['tmp_name'], $tmpFile)) {
                        $step = 2;
                        $fileName = $_FILES['import_file']['name'];
                    } else {
                        $_SESSION['error_message'] = "Failed to save uploaded file.";
                    }
                }
            } else {
                $_SESSION['error_message'] = "Please select a valid file.";
            }
        } elseif ($action === 'import') {
            $tmpFile = $_POST['tmp_file'] ?? '';
            $fileName = $_POST['file_name'] ?? 'unknown_file';
            if (!empty($tmpFile) && file_exists($tmpFile)) {
                $step = 3;
            } else {
                $_SESSION['error_message'] = "Temporary file expired or invalid. Please upload again.";
                redirect('/admin/question_import.php');
            }
        } elseif ($action === 'cancel') {
            // Clean up temp file if canceling
            $tmpFile = $_POST['tmp_file'] ?? '';
            if (!empty($tmpFile) && file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
            redirect('/admin/question_import.php');
        }

        // Execute Parsing logic for both Preview and Import steps
        if ($step === 2 || $step === 3) {
            $rows = extractRows($tmpFile);
            $checkDup = $pdo->prepare("SELECT id FROM questions WHERE trade_id=? AND subject_id=? AND LOWER(question_text)=?");
            $insertStmt = $pdo->prepare("INSERT INTO questions (trade_id, subject_id, question_type, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, marks, negative_marks, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
            
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                if (empty(array_filter($row))) continue; // Skip empty rows
                
                $stats['total']++;
                
                $tName = strtolower(trim($row[0] ?? ''));
                $sName = strtolower(trim($row[1] ?? ''));
                $qType = strtolower(trim($row[2] ?? 'mcq'));
                $qText = trim($row[3] ?? '');
                $optA = trim($row[4] ?? '');
                $optB = trim($row[5] ?? '');
                $optC = trim($row[6] ?? '');
                $optD = trim($row[7] ?? '');
                $correct = strtoupper(trim($row[8] ?? ''));
                $diff = ucfirst(strtolower(trim($row[9] ?? 'Medium')));
                $marks = (float)($row[10] ?? 1.00);
                $negative = (float)($row[11] ?? 0.00);
                $expl = trim($row[12] ?? '');
                
                $isValid = true;
                $errorMsg = [];
                
                $trade_id = $tradesCache[$tName] ?? 0;
                if (!$trade_id) { $isValid = false; $errorMsg[] = "Trade not found"; }
                
                $subject_id = $subjectsCache[$trade_id . '_' . $sName] ?? 0;
                if (!$subject_id) { $isValid = false; $errorMsg[] = "Subject not found in Trade"; }
                
                if (empty($qText) || empty($optA) || empty($optB) || empty($correct)) {
                    $isValid = false; $errorMsg[] = "Missing required fields";
                }
                
                if (!in_array($correct, ['A', 'B', 'C', 'D'])) {
                    $isValid = false; $errorMsg[] = "Invalid Correct Answer";
                }

                if ($isValid) {
                    $checkDup->execute([$trade_id, $subject_id, strtolower($qText)]);
                    if ($checkDup->fetch()) {
                        $isValid = false; $errorMsg[] = "Duplicate question";
                        $stats['duplicate']++;
                    }
                }

                if ($isValid) {
                    $stats['valid']++;
                    if ($step === 3) {
                        $insertStmt->execute([$trade_id, $subject_id, $qType, $qText, $optA, $optB, $optC, $optD, $correct, $expl, $diff, $marks, $negative, $_SESSION['user_id']]);
                    }
                } else {
                    $stats['invalid']++;
                }

                // Build preview array for the first 100 rows
                if ($step === 2 && count($previewData) < 100) {
                    $previewData[] = [
                        'row' => $index + 1,
                        'trade' => $tName,
                        'subject' => $sName,
                        'question' => $qText,
                        'status' => $isValid ? 'Valid' : implode(', ', $errorMsg),
                        'is_valid' => $isValid
                    ];
                }
            }

            if ($step === 3) {
                // Import Complete - Write Log & Cleanup
                $logStmt = $pdo->prepare("INSERT INTO question_import_logs (uploaded_by, file_name, total_rows, imported_rows, failed_rows, duplicate_rows) VALUES (?, ?, ?, ?, ?, ?)");
                $logStmt->execute([$_SESSION['user_id'], $fileName, $stats['total'], $stats['valid'], $stats['invalid'], $stats['duplicate']]);
                
                if (file_exists($tmpFile)) unlink($tmpFile);
                $_SESSION['success_message'] = "Import completed! Successfully imported " . $stats['valid'] . " questions.";
                redirect('/admin/questions.php');
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0">Bulk Question Import</h3>
        <div>
            <a href="download_template.php" class="btn btn-outline-primary btn-sm me-2"><i data-lucide="download" style="width: 16px;"></i> Download Template</a>
            <a href="questions.php" class="btn btn-outline-secondary btn-sm">Back to Question Bank</a>
        </div>
    </div>
    
    <?php displayFlashMessages(); ?>
    
    <?php if ($step === 1): ?>
    <div class="card p-4 mx-auto" style="max-width: 600px;">
        <div class="text-center mb-4">
            <i data-lucide="upload-cloud" class="text-primary mb-3" style="width: 48px; height: 48px;"></i>
            <h5>Upload Excel or CSV File</h5>
            <p class="text-muted small">Upload your spreadsheet to instantly add hundreds of questions to the bank. Please ensure you are using the exact format provided in the template.</p>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="preview">
            <div class="mb-4">
                <input type="file" name="import_file" class="form-control" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Preview File</button>
        </form>
    </div>
    <?php elseif ($step === 2): ?>
    
    <!-- Preview Step -->
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card p-3 text-center border-primary"><h3 class="fw-bold text-primary mb-0"><?= $stats['total'] ?></h3><small class="text-muted">Total Rows</small></div></div>
        <div class="col-md-3"><div class="card p-3 text-center border-success"><h3 class="fw-bold text-success mb-0"><?= $stats['valid'] ?></h3><small class="text-muted">Valid / Ready to Import</small></div></div>
        <div class="col-md-3"><div class="card p-3 text-center border-danger"><h3 class="fw-bold text-danger mb-0"><?= $stats['invalid'] ?></h3><small class="text-muted">Failed Validation</small></div></div>
        <div class="col-md-3"><div class="card p-3 text-center border-warning"><h3 class="fw-bold text-warning mb-0"><?= $stats['duplicate'] ?></h3><small class="text-muted">Duplicates Detected</small></div></div>
    </div>

    <div class="card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Preview (First 100 Rows)</h5>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="tmp_file" value="<?= htmlspecialchars($tmpFile) ?>">
                <input type="hidden" name="file_name" value="<?= htmlspecialchars($fileName) ?>">
                <?php if ($stats['valid'] > 0): ?>
                    <button type="submit" class="btn btn-success fw-bold"><i data-lucide="check-circle" class="me-2" style="width: 16px;"></i> Confirm & Import <?= $stats['valid'] ?> Questions</button>
                <?php else: ?>
                    <a href="question_import.php" class="btn btn-secondary">Upload Different File</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Row</th><th>Trade</th><th>Subject</th><th style="max-width:300px;">Question</th><th>Validation Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewData as $row): ?>
                    <tr class="<?= $row['is_valid'] ? '' : 'table-danger' ?>">
                        <td><?= $row['row'] ?></td>
                        <td><?= htmlspecialchars($row['trade']) ?></td>
                        <td><?= htmlspecialchars($row['subject']) ?></td>
                        <td class="text-truncate" style="max-width:300px;"><?= htmlspecialchars($row['question']) ?></td>
                        <td>
                            <?php if ($row['is_valid']): ?><span class="badge bg-success">Valid</span>
                            <?php else: ?><span class="text-danger fw-semibold"><?= htmlspecialchars($row['status']) ?></span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>