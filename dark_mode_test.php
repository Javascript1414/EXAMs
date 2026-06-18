<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Mode Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <link rel="stylesheet" href="assets/css/youtube-video-cards.css">
    <style>
        html, body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body {
            padding: 20px;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .controls {
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        html[data-theme="dark"] .controls,
        body[data-theme="dark"] .controls {
            background: #1a1a1a !important;
            color: #e0e0e0 !important;
        }
        
        .controls h2 {
            margin-top: 0;
        }
        
        .toggle-btn {
            padding: 10px 20px;
            font-size: 16px;
            border: 2px solid #007bff;
            background: #007bff;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .toggle-btn:hover {
            background: #0056d2;
            border-color: #0056d2;
        }
        
        .status {
            margin-top: 20px;
            padding: 10px 15px;
            background: #e8f4f8;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        
        html[data-theme="dark"] .status,
        body[data-theme="dark"] .status {
            background: #1a3a3a !important;
            border-left-color: #45b7ff;
            color: #e0e0e0 !important;
        }
        
        .sample-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .sample-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }
        
        html[data-theme="dark"] .sample-card,
        body[data-theme="dark"] .sample-card {
            background: #1a1a1a !important;
            color: #e0e0e0 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        }
        
        .sample-card h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        
        .sample-card p {
            margin: 0;
            font-size: 12px;
            opacity: 0.8;
        }
    </style>
</head>
<body data-theme="light">
    <div class="test-container">
        <div class="controls">
            <h2>🌓 Dark Mode Test</h2>
            <button class="toggle-btn" onclick="toggleDarkMode()">Toggle Dark Mode</button>
            <div class="status">
                <strong>Current Theme:</strong> <span id="themeStatus">Light Mode</span>
                <br>
                <strong>HTML data-theme:</strong> <code id="htmlAttr">Not set</code>
                <br>
                <strong>Body data-theme:</strong> <code id="bodyAttr">Not set</code>
                <br>
                <strong>Dark Mode Manager:</strong> <span id="managerStatus">Loading...</span>
            </div>
        </div>
        
        <h3>Sample Cards (Dark Mode Styling Test)</h3>
        <div class="sample-cards">
            <div class="sample-card">
                <h4>Introduction to Web Dev</h4>
                <p>by John Developer</p>
                <p>⭐ 4.5 | 15K views</p>
            </div>
            <div class="sample-card">
                <h4>Advanced JavaScript</h4>
                <p>by Sarah Code</p>
                <p>⭐ 4.8 | 22K views</p>
            </div>
            <div class="sample-card">
                <h4>CSS Grid Master</h4>
                <p>by Design Master</p>
                <p>⭐ 4.2 | 8.5K views</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/dark-mode.js"></script>
    <script>
        function toggleDarkMode() {
            if (window.darkModeManager) {
                const newTheme = window.darkModeManager.toggleTheme();
                updateStatus(newTheme);
            } else {
                console.error('Dark mode manager not initialized');
            }
        }
        
        function updateStatus(theme) {
            const statusEl = document.getElementById('themeStatus');
            const htmlAttrEl = document.getElementById('htmlAttr');
            const bodyAttrEl = document.getElementById('bodyAttr');
            const managerEl = document.getElementById('managerStatus');
            
            if (theme === 'dark') {
                statusEl.textContent = 'Dark Mode ✓';
            } else {
                statusEl.textContent = 'Light Mode ✓';
            }
            
            const htmlAttr = document.documentElement.getAttribute('data-theme') || 'not set';
            const bodyAttr = document.body.getAttribute('data-theme') || 'not set';
            
            htmlAttrEl.textContent = htmlAttr;
            bodyAttrEl.textContent = bodyAttr;
            managerEl.textContent = 'Ready ✓';
        }
        
        // Initialize status on page load
        window.addEventListener('load', () => {
            if (window.darkModeManager) {
                updateStatus(window.darkModeManager.getTheme());
            }
        });
        
        // Listen for theme changes
        window.addEventListener('themechange', (e) => {
            console.log('Theme changed to:', e.detail.theme);
            updateStatus(e.detail.theme);
        });
    </script>
</body>
</html>

