<?php
/**
 * Debug Script: Diagnose PDF Serving Issues
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Debug Console</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .debug-log { background: #000; color: #00ff00; font-family: monospace; padding: 15px; border-radius: 8px; max-height: 600px; overflow-y: auto; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; }
        .debug-error { color: #ff4444; }
        .debug-success { color: #44ff44; }
        .debug-info { color: #44aaff; }
        .debug-warn { color: #ffaa44; }
        .button-group { margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">📋 PDF Serving Debug Console</h1>
    
    <div class="row">
        <div class="col-md-8">
            <h3>Debug Output</h3>
            <div class="debug-log" id="debugLog"></div>
            
            <div class="button-group">
                <button class="btn btn-primary" onclick="runTests()">Run Diagnostics</button>
                <button class="btn btn-secondary" onclick="clearLog()">Clear Log</button>
            </div>
        </div>
        
        <div class="col-md-4">
            <h3>Quick Links</h3>
            <div class="list-group">
                <a href="http://localhost/EXAMs/uploads/notes/1781798787_voter_pintu.pdf" class="list-group-item list-group-item-action" target="_blank">
                    Direct PDF Access (voter_pintu.pdf)
                </a>
                <a href="http://localhost/EXAMs/api/serve-pdf.php?file=uploads/notes/1781798787_voter_pintu.pdf" class="list-group-item list-group-item-action" target="_blank">
                    Via serve-pdf.php
                </a>
                <a href="http://localhost/EXAMs/api/check-pdf.php?file=uploads/notes/1781798787_voter_pintu.pdf" class="list-group-item list-group-item-action" target="_blank">
                    Via check-pdf.php (JSON)
                </a>
            </div>
            
            <h3 class="mt-4">System Info</h3>
            <small class="text-muted">
                <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                <p><strong>Base URL:</strong> <?php echo BASE_URL; ?></p>
                <p><strong>Uploads Dir:</strong> <?php echo realpath(__DIR__ . '/uploads'); ?></p>
            </small>
        </div>
    </div>
</div>

<script>
let logLines = [];

function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const line = `[${timestamp}] ${message}`;
    logLines.push({ message: line, type });
    updateLog();
}

function updateLog() {
    const debugLog = document.getElementById('debugLog');
    debugLog.innerHTML = logLines.map(line => {
        const className = line.type === 'error' ? 'debug-error' : line.type === 'success' ? 'debug-success' : line.type === 'warn' ? 'debug-warn' : 'debug-info';
        return `<div class="${className}">${escapeHtml(line.message)}</div>`;
    }).join('');
    debugLog.scrollTop = debugLog.scrollHeight;
}

function clearLog() {
    logLines = [];
    updateLog();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

async function runTests() {
    logLines = [];
    log('Starting PDF serving diagnostics...', 'info');
    
    const testPdfs = [
        { name: 'voter_pintu.pdf', path: 'uploads/notes/1781798787_voter_pintu.pdf' },
        { name: 'Declaration_Form.pdf', path: 'uploads/notes/1781798076_Declaration_Form_English.pdf' },
        { name: 'Arrays_Linked_Lists.pdf', path: 'uploads/notes/1781797959_Sample_Notes_-_Arrays_and_Linked_Lists.pdf' }
    ];
    
    for (const pdf of testPdfs) {
        log(`\n=== Testing: ${pdf.name} ===`, 'info');
        await testPDF(pdf);
    }
    
    log('\nDiagnostics complete.', 'success');
}

async function testPDF(pdf) {
    const checkUrl = `/EXAMs/api/check-pdf.php?file=${encodeURIComponent(pdf.path)}`;
    const serveUrl = `/EXAMs/api/serve-pdf.php?file=${encodeURIComponent(pdf.path)}`;
    
    // Test check-pdf
    log(`Checking file existence...`, 'info');
    try {
        const response = await fetch(checkUrl);
        const data = await response.json();
        
        if (data.success) {
            log(`✓ File found: ${Math.round(data.size / 1024)} KB, MIME: ${data.mime_type}`, 'success');
        } else {
            log(`✗ File check failed: ${data.error}`, 'error');
            return;
        }
    } catch (e) {
        log(`✗ Fetch error: ${e.message}`, 'error');
        return;
    }
    
    // Test serve-pdf with HEAD
    log(`Testing serve-pdf.php (HEAD)...`, 'info');
    try {
        const headResponse = await fetch(serveUrl, { method: 'HEAD', mode: 'no-cors' });
        log(`✓ HEAD response: ${headResponse.status} ${headResponse.statusText}`, 'success');
    } catch (e) {
        log(`⚠ HEAD request failed: ${e.message}`, 'warn');
    }
    
    // Test serve-pdf with GET (limited)
    log(`Testing serve-pdf.php (GET - headers only)...`, 'info');
    try {
        const response = await fetch(serveUrl, { 
            method: 'GET',
            headers: {
                'Range': 'bytes=0-0' // Request just the first byte
            }
        });
        
        if (response.ok) {
            const contentType = response.headers.get('content-type');
            const contentLength = response.headers.get('content-length');
            const contentDisposition = response.headers.get('content-disposition');
            
            log(`✓ GET headers received:`, 'success');
            log(`  - Content-Type: ${contentType}`, 'success');
            log(`  - Content-Length: ${contentLength}`, 'success');
            log(`  - Content-Disposition: ${contentDisposition}`, 'success');
        } else {
            log(`✗ GET failed: ${response.status} ${response.statusText}`, 'error');
        }
    } catch (e) {
        log(`✗ GET request error: ${e.message}`, 'error');
    }
    
    // Test iframe loading
    log(`Testing iframe load simulation...`, 'info');
    const testFrame = document.createElement('iframe');
    testFrame.style.display = 'none';
    
    testFrame.onerror = () => {
        log(`✗ Iframe load error`, 'error');
    };
    
    testFrame.onload = () => {
        log(`✓ Iframe loaded successfully`, 'success');
    };
    
    testFrame.src = serveUrl + '#toolbar=1';
    // Don't actually append to test, just log
    log(`iframe.src would be: ${serveUrl}#toolbar=1`, 'info');
}
</script>
</body>
</html>
