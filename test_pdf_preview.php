<?php
/**
 * Test Script: Verify PDF Preview System
 * Tests the check-pdf and serve-pdf endpoints
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Preview System Test</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-item { margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px; }
        .test-item h5 { margin-bottom: 10px; }
        .pdf-status { padding: 8px 12px; border-radius: 4px; font-size: 0.9rem; }
        .status-success { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-loading { background: #cfe2ff; color: #084298; }
        iframe { border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4"><i class="fas fa-file-pdf"></i> PDF Preview System Test</h1>
    
    <div class="row">
        <div class="col-md-6">
            <h3>Test Results</h3>
            <div id="testResults"></div>
        </div>
        <div class="col-md-6">
            <h3>PDF Preview</h3>
            <div id="previewContainer"></div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const testPDFs = [
    'uploads/notes/1781797959_Sample_Notes_-_Arrays_and_Linked_Lists.pdf',
    'uploads/notes/1781798787_voter_pintu.pdf',
    'uploads/notes/1781798076_Declaration_Form_English.pdf'
];

async function testPDFSystem() {
    const resultsContainer = document.getElementById('testResults');
    resultsContainer.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Testing...</p>';
    
    for (const pdfPath of testPDFs) {
        const checkUrl = BASE_URL + '/api/check-pdf.php?file=' + encodeURIComponent(pdfPath);
        const serveUrl = BASE_URL + '/api/serve-pdf.php?file=' + encodeURIComponent(pdfPath);
        
        const fileName = pdfPath.split('/').pop();
        const testItem = document.createElement('div');
        testItem.className = 'test-item';
        testItem.innerHTML = `
            <h5>${fileName}</h5>
            <p class="mb-2"><small class="text-muted">${pdfPath}</small></p>
            <div class="mb-2">
                <strong>Check Endpoint:</strong>
                <div class="pdf-status status-loading"><i class="fas fa-spinner fa-spin"></i> Testing...</div>
            </div>
            <div class="mb-2">
                <strong>Preview Test:</strong>
                <div class="pdf-status-preview status-loading"><i class="fas fa-spinner fa-spin"></i> Testing...</div>
            </div>
        `;
        resultsContainer.appendChild(testItem);
        
        try {
            // Test check-pdf endpoint
            const checkResponse = await fetch(checkUrl);
            const checkData = await checkResponse.json();
            
            const checkStatus = testItem.querySelector('.pdf-status');
            if (checkData.success) {
                checkStatus.className = 'pdf-status status-success';
                checkStatus.innerHTML = `<i class="fas fa-check-circle"></i> ✓ File found (${Math.round(checkData.size / 1024)} KB)`;
            } else {
                checkStatus.className = 'pdf-status status-error';
                checkStatus.innerHTML = `<i class="fas fa-times-circle"></i> ✗ ${checkData.error}`;
            }
            
            // Test iframe preview
            const previewStatus = testItem.querySelector('.pdf-status-preview');
            const previewUrl = serveUrl + '#toolbar=1&navpanes=0&scrollbar=1';
            
            try {
                const previewResponse = await fetch(serveUrl, { method: 'HEAD' });
                if (previewResponse.ok) {
                    previewStatus.className = 'pdf-status status-success';
                    previewStatus.innerHTML = `<i class="fas fa-check-circle"></i> ✓ Can be served`;
                    
                    // Add preview button
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm btn-primary mt-2';
                    btn.textContent = 'Preview in Modal';
                    btn.onclick = () => previewPDF(pdfPath, fileName);
                    testItem.appendChild(btn);
                } else {
                    previewStatus.className = 'pdf-status status-error';
                    previewStatus.innerHTML = `<i class="fas fa-times-circle"></i> ✗ HTTP ${previewResponse.status}`;
                }
            } catch (e) {
                previewStatus.className = 'pdf-status status-error';
                previewStatus.innerHTML = `<i class="fas fa-times-circle"></i> ✗ ${e.message}`;
            }
            
        } catch (error) {
            const checkStatus = testItem.querySelector('.pdf-status');
            checkStatus.className = 'pdf-status status-error';
            checkStatus.innerHTML = `<i class="fas fa-times-circle"></i> ✗ Fetch error: ${error.message}`;
            
            const previewStatus = testItem.querySelector('.pdf-status-preview');
            previewStatus.className = 'pdf-status status-error';
            previewStatus.innerHTML = `<i class="fas fa-times-circle"></i> ✗ Fetch error: ${error.message}`;
        }
    }
}

function previewPDF(filePath, fileName) {
    const container = document.getElementById('previewContainer');
    const serveUrl = BASE_URL + '/api/serve-pdf.php?file=' + encodeURIComponent(filePath);
    
    container.innerHTML = `
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">${fileName}</h6>
            </div>
            <div class="card-body" style="padding: 0; overflow: hidden;">
                <div style="height: 600px; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                    <p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading PDF...</p>
                </div>
                <iframe id="pdfFrame" 
                        style="width: 100%; height: 600px; display: none; border: none;"
                        onload="onIframeLoad()"
                        onerror="onIframeError()">
                </iframe>
            </div>
        </div>
    `;
    
    const iframe = container.querySelector('iframe');
    iframe.src = serveUrl + '#toolbar=1&navpanes=0&scrollbar=1';
    
    window.onIframeLoad = function() {
        console.log('PDF loaded successfully');
        container.querySelector('div[style*="height: 600px"]').style.display = 'none';
        iframe.style.display = 'block';
    };
    
    window.onIframeError = function() {
        console.error('PDF iframe error');
        const loadingDiv = container.querySelector('div[style*="height: 600px"]');
        loadingDiv.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Failed to load PDF</p>';
    };
}

// Run tests on page load
document.addEventListener('DOMContentLoaded', testPDFSystem);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
