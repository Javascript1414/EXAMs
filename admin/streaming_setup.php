<?php
/**
 * Video Streaming Optimization - Setup & Activation Script
 * Run this once to activate all streaming optimizations
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin', 'moderator'])) {
    http_response_code(403);
    die('Unauthorized access. Admin only.');
}

$success = true;
$messages = [];

try {
    // 1. Check if stream.php exists
    if (file_exists(__DIR__ . '/api/videos/stream.php')) {
        $messages[] = '✅ stream.php exists';
    } else {
        $messages[] = '❌ stream.php not found';
        $success = false;
    }
    
    // 2. Check if adaptive_streaming.php exists
    if (file_exists(__DIR__ . '/api/videos/adaptive_streaming.php')) {
        $messages[] = '✅ adaptive_streaming.php exists';
    } else {
        $messages[] = '❌ adaptive_streaming.php not found';
        $success = false;
    }
    
    // 3. Check if adaptive-streaming.js exists
    if (file_exists(__DIR__ . '/assets/js/adaptive-streaming.js')) {
        $messages[] = '✅ adaptive-streaming.js exists';
    } else {
        $messages[] = '❌ adaptive-streaming.js not found';
        $success = false;
    }
    
    // 4. Check video_watch_history table columns
    $stmt = $pdo->query("SHOW COLUMNS FROM video_watch_history");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    $requiredColumns = ['estimated_bandwidth', 'last_bandwidth_test', 'quality_used', 'buffering_events'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            $messages[] = "✅ Column '$col' exists in video_watch_history";
        } else {
            $messages[] = "⚠️  Column '$col' missing. Run migration.";
            $missingColumns[] = $col;
        }
    }
    
    // 5. Check video_streaming_metrics table
    $stmt = $pdo->query("SHOW TABLES LIKE 'video_streaming_metrics'");
    if ($stmt->fetch()) {
        $messages[] = '✅ video_streaming_metrics table exists';
    } else {
        $messages[] = '⚠️  video_streaming_metrics table missing. Run migration.';
    }
    
    // 6. Check uploads/videos directory
    $uploadDir = __DIR__ . '/uploads/videos';
    if (is_dir($uploadDir) && is_writable($uploadDir)) {
        $messages[] = '✅ uploads/videos directory exists and is writable';
    } else {
        $messages[] = '❌ uploads/videos directory issue';
        $success = false;
    }
    
} catch (Exception $e) {
    $messages[] = '❌ Error: ' . $e->getMessage();
    $success = false;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Streaming Optimization - Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #28a745;
            background: #f0f9f4;
            border-radius: 4px;
        }
        .status.error {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        .status.warning {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .message {
            font-family: monospace;
            margin: 8px 0;
            font-size: 14px;
        }
        .next-steps {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .code {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .success-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: bold;
        }
        .error-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 Video Streaming Optimization Setup</h1>
        
        <div class="status <?= $success ? '' : 'error' ?>">
            <h3><?= $success ? '✅ System Ready' : '⚠️ Setup Required' ?></h3>
            <div>
                <?php foreach ($messages as $message): ?>
                    <div class="message"><?= htmlspecialchars($message) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!$success): ?>
        <div class="next-steps">
            <h3>Next Steps:</h3>
            <ol>
                <li><strong>Run Database Migration</strong>
                    <div class="code">
                        mysql -u root EXAMs < migrations/phase_18_video_streaming_optimization.sql
                    </div>
                </li>
                <li><strong>Verify Files Exist</strong>
                    <ul>
                        <li>/api/videos/stream.php</li>
                        <li>/api/videos/adaptive_streaming.php</li>
                        <li>/assets/js/adaptive-streaming.js</li>
                    </ul>
                </li>
                <li><strong>Check File Permissions</strong>
                    <div class="code">
                        chmod 755 /api/videos/stream.php<br>
                        chmod 755 /api/videos/adaptive_streaming.php<br>
                        chmod 755 /uploads/videos
                    </div>
                </li>
                <li><strong>Test a Video</strong>
                    <div class="code">
                        Open a video from /student/video_streaming.php<br>
                        Check browser console for initialization messages
                    </div>
                </li>
            </ol>
        </div>
        <?php else: ?>
        <div class="next-steps">
            <h3>✨ System is Ready to Use!</h3>
            <p>The video streaming optimization is fully activated. All users will now benefit from:</p>
            <ul>
                <li>✅ Smooth video playback without buffering</li>
                <li>✅ Automatic quality adjustment based on network speed</li>
                <li>✅ Instant seeking with HTTP range requests</li>
                <li>✅ Smart buffering recovery</li>
                <li>✅ 24-hour browser caching</li>
            </ul>
            <p><strong>Test It:</strong> Open a video and look for the quality indicator in the top-right corner of the player.</p>
        </div>
        <?php endif; ?>
        
        <div class="next-steps" style="margin-top: 20px;">
            <h3>📚 Documentation</h3>
            <p>For detailed information, see: <code>VIDEO_STREAMING_OPTIMIZATION.md</code></p>
        </div>
        
    </div>
</body>
</html>
