<!DOCTYPE html>
<html>
<head>
    <title>Display Preferences - Live Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        h1, h2 {
            color: #667eea;
        }
        
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .test-button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        
        .test-button:hover {
            background: #764ba2;
        }
        
        .output {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        
        .success {
            color: #28a745;
        }
        
        .error {
            color: #dc3545;
        }
        
        .info {
            color: #0066cc;
        }
    </style>
</head>
<body>
    <h1>🔧 Display Preferences - Live Debug Console</h1>
    
    <div class="test-section">
        <h2>1. Environment Check</h2>
        <button class="test-button" onclick="checkEnvironment()">Check Environment</button>
        <div id="env-output" class="output" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>2. AJAX Connectivity Test</h2>
        <button class="test-button" onclick="testAjaxConnection()">Test AJAX</button>
        <div id="ajax-output" class="output" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>3. Simulate Preference Update</h2>
        <button class="test-button" onclick="testUpdateTheme()">Update Theme to Dark</button>
        <button class="test-button" onclick="testUpdateDashboard()">Update Dashboard to List</button>
        <div id="update-output" class="output" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>4. Check Database State</h2>
        <button class="test-button" onclick="checkDatabaseState()">Check Database</button>
        <div id="db-output" class="output" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>5. Go to Settings</h2>
        <p>Once you verify everything is working, go to actual settings page:</p>
        <button class="test-button" onclick="window.location.href='/EXAMs/student/settings.php?tab=preferences'">
            Open Settings (Preferences Tab)
        </button>
    </div>

    <script>
        const BASE_URL = 'http://localhost/EXAMs';
        
        function log(element, message, type = 'info') {
            const output = document.getElementById(element);
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#0066cc';
            
            output.style.display = 'block';
            output.innerHTML += `<span style="color: ${color};">[${timestamp}] ${message}</span>\n`;
            output.scrollTop = output.scrollHeight;
        }
        
        function checkEnvironment() {
            const output = 'env-output';
            document.getElementById(output).innerHTML = '';
            
            log(output, '🔍 Environment Information', 'info');
            log(output, `Base URL: ${BASE_URL}`, 'info');
            log(output, `Current URL: ${window.location.href}`, 'info');
            log(output, `Document Ready State: ${document.readyState}`, 'info');
            log(output, `Session Storage: ${typeof(Storage) !== "undefined" ? 'Available' : 'Not Available'}`, 'info');
            
            if (typeof XMLHttpRequest !== 'undefined') {
                log(output, '✅ XMLHttpRequest: Available', 'success');
            } else {
                log(output, '❌ XMLHttpRequest: Not Available', 'error');
            }
        }
        
        function testAjaxConnection() {
            const output = 'ajax-output';
            document.getElementById(output).innerHTML = '';
            
            log(output, '🌐 Testing AJAX Connection...', 'info');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${BASE_URL}/student/settings_ajax.php`, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    log(output, `✅ Server Response (Status 200): OK`, 'success');
                    try {
                        const response = JSON.parse(xhr.responseText);
                        log(output, `Response Type: ${typeof response}`, 'info');
                        log(output, `Response Content: ${JSON.stringify(response, null, 2)}`, 'info');
                    } catch(e) {
                        log(output, `⚠️ Invalid JSON Response: ${xhr.responseText}`, 'error');
                    }
                } else {
                    log(output, `❌ Server Response (Status ${xhr.status}): ${xhr.statusText}`, 'error');
                    log(output, `Response: ${xhr.responseText}`, 'error');
                }
            };
            
            xhr.onerror = function() {
                log(output, '❌ Network Error - Could not reach server', 'error');
            };
            
            xhr.onprogress = function() {
                log(output, '📡 Request in progress...', 'info');
            };
            
            // Send without action (to test basic connectivity)
            log(output, '📤 Sending test request...', 'info');
            xhr.send('action=invalid_action');
        }
        
        function testUpdateTheme() {
            const output = 'update-output';
            document.getElementById(output).innerHTML = '';
            
            log(output, '🎨 Testing Theme Update...', 'info');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${BASE_URL}/student/settings_ajax.php`, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                log(output, `📥 Server Response (Status ${xhr.status}):`, 'info');
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            log(output, `✅ Update Successful!`, 'success');
                            log(output, `Message: ${response.message}`, 'success');
                        } else {
                            log(output, `⚠️ Update Failed`, 'error');
                            log(output, `Error: ${response.message}`, 'error');
                        }
                        log(output, `Full Response: ${JSON.stringify(response, null, 2)}`, 'info');
                    } catch(e) {
                        log(output, `❌ JSON Parse Error: ${e.message}`, 'error');
                        log(output, `Raw Response: ${xhr.responseText}`, 'error');
                    }
                } else {
                    log(output, `❌ HTTP Error ${xhr.status}`, 'error');
                    log(output, `Response: ${xhr.responseText}`, 'error');
                }
            };
            
            xhr.onerror = function() {
                log(output, '❌ Network Error', 'error');
            };
            
            log(output, '📤 Sending update request for theme=dark...', 'info');
            xhr.send('action=update_preference&theme=dark');
        }
        
        function testUpdateDashboard() {
            const output = 'update-output';
            document.getElementById(output).innerHTML = '';
            
            log(output, '📊 Testing Dashboard View Update...', 'info');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${BASE_URL}/student/settings_ajax.php`, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                log(output, `📥 Server Response (Status ${xhr.status}):`, 'info');
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            log(output, `✅ Update Successful!`, 'success');
                            log(output, `Message: ${response.message}`, 'success');
                        } else {
                            log(output, `⚠️ Update Failed`, 'error');
                            log(output, `Error: ${response.message}`, 'error');
                        }
                        log(output, `Full Response: ${JSON.stringify(response, null, 2)}`, 'info');
                    } catch(e) {
                        log(output, `❌ JSON Parse Error: ${e.message}`, 'error');
                        log(output, `Raw Response: ${xhr.responseText}`, 'error');
                    }
                } else {
                    log(output, `❌ HTTP Error ${xhr.status}`, 'error');
                    log(output, `Response: ${xhr.responseText}`, 'error');
                }
            };
            
            xhr.onerror = function() {
                log(output, '❌ Network Error', 'error');
            };
            
            log(output, '📤 Sending update request for dashboard_view=list...', 'info');
            xhr.send('action=update_preference&dashboard_view=list');
        }
        
        function checkDatabaseState() {
            const output = 'db-output';
            
            // Fetch via PHP
            document.getElementById(output).innerHTML = '';
            log(output, '🗄️ Checking Database State...', 'info');
            
            fetch('test_preferences.php')
                .then(response => response.text())
                .then(html => {
                    // Extract preferences from HTML
                    const match = html.match(/"theme":\s*"([^"]+)"/);
                    if (match) {
                        log(output, `✅ Current Theme: ${match[1]}`, 'success');
                    }
                    
                    const dashMatch = html.match(/"dashboard_view":\s*"([^"]+)"/);
                    if (dashMatch) {
                        log(output, `✅ Current Dashboard View: ${dashMatch[1]}`, 'success');
                    }
                    
                    const langMatch = html.match(/"language":\s*"([^"]+)"/);
                    if (langMatch) {
                        log(output, `✅ Current Language: ${langMatch[1]}`, 'success');
                    }
                    
                    log(output, 'Database state retrieved successfully!', 'success');
                })
                .catch(error => {
                    log(output, `❌ Error checking database: ${error}`, 'error');
                });
        }
        
        // Auto-check on load
        window.addEventListener('load', function() {
            checkEnvironment();
        });
    </script>
</body>
</html>
