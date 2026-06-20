<?php
/**
 * Maintenance Mode Middleware
 * Checks if system is in maintenance and shows appropriate page
 * Include this in header.php to activate
 */

function checkMaintenanceMode() {
    $config = require __DIR__ . '/../config/maintenance.php';
    
    // If not in maintenance mode, allow access
    if (!$config['maintenance_mode']) {
        return true;
    }
    
    // Check if user's IP is allowed during maintenance
    $user_ip = $_SERVER['REMOTE_ADDR'];
    if (in_array($user_ip, $config['allowed_ips'])) {
        return true;
    }
    
    // Check if user is admin and admin panel access is allowed
    if (isset($_SESSION['role_name']) && in_array($_SESSION['role_name'], ['admin', 'superadmin'])) {
        if ($config['show_admin_panel']) {
            return true;
        }
    }
    
    // Show maintenance page
    showMaintenancePage($config);
    exit;
}

/**
 * Display maintenance page to users
 */
function showMaintenancePage($config) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Maintenance</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .maintenance-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
                padding: 60px 40px;
                text-align: center;
                animation: slideUp 0.6s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .icon {
                font-size: 80px;
                margin-bottom: 20px;
                animation: spin 2s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            h1 {
                color: #333;
                margin-bottom: 15px;
                font-size: 32px;
            }
            
            .message {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            
            .details {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            
            .details p {
                color: #555;
                margin: 10px 0;
                font-size: 14px;
            }
            
            .details strong {
                color: #333;
            }
            
            .progress-bar {
                height: 6px;
                background: #e0e0e0;
                border-radius: 10px;
                margin: 20px 0;
                overflow: hidden;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
                animation: progress 3s ease-in-out infinite;
            }
            
            @keyframes progress {
                0%, 100% { width: 0; }
                50% { width: 100%; }
            }
            
            .support {
                margin-top: 30px;
                padding-top: 30px;
                border-top: 1px solid #e0e0e0;
            }
            
            .support p {
                color: #999;
                font-size: 13px;
                margin: 8px 0;
            }
            
            .support-link {
                color: #667eea;
                text-decoration: none;
            }
            
            .support-link:hover {
                text-decoration: underline;
            }
            
            @media (max-width: 600px) {
                .maintenance-container {
                    padding: 40px 20px;
                }
                
                h1 {
                    font-size: 24px;
                }
                
                .icon {
                    font-size: 60px;
                }
            }
        </style>
    </head>
    <body>
        <div class="maintenance-container">
            <div class="icon">⚙️</div>
            
            <h1><?= htmlspecialchars($config['maintenance_message']) ?></h1>
            
            <p class="message">
                <?= htmlspecialchars($config['maintenance_details']) ?>
            </p>
            
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            
            <div class="details">
                <p><strong>Estimated Time:</strong> <?= htmlspecialchars($config['maintenance_estimated_time']) ?></p>
                <p><strong>Last Updated:</strong> <?= $config['last_maintenance'] ? date('M d, Y H:i', strtotime($config['last_maintenance'])) : 'N/A' ?></p>
                <?php if ($config['next_scheduled_maintenance']): ?>
                    <p><strong>Next Maintenance:</strong> <?= date('M d, Y H:i', strtotime($config['next_scheduled_maintenance'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="support">
                <p>❤️ Thank you for your patience!</p>
                <p>We are working hard to bring you new features and improvements.</p>
                <p>If you have questions: <a href="mailto:support@example.com" class="support-link">Contact Support</a></p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
