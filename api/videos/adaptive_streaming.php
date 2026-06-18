<?php
/**
 * Adaptive Video Streaming Quality Manager
 * Detects network speed and recommends optimal quality
 * Supports bandwidth-based quality switching
 */

class AdaptiveStreamingManager {
    private $pdo;
    private $videoId;
    private $userId;
    
    // Quality profiles (bitrate in kbps)
    private $qualityProfiles = [
        'auto'   => ['label' => 'Auto (Adaptive)', 'bitrate' => 0],
        '1080'   => ['label' => '1080p (5-8 Mbps)', 'bitrate' => 6000],
        '720'    => ['label' => '720p (2.5-5 Mbps)', 'bitrate' => 3500],
        '480'    => ['label' => '480p (1-2.5 Mbps)', 'bitrate' => 1500],
        '360'    => ['label' => '360p (0.5-1 Mbps)', 'bitrate' => 750],
        '240'    => ['label' => '240p (0.25-0.5 Mbps)', 'bitrate' => 375]
    ];
    
    public function __construct($pdo, $videoId, $userId) {
        $this->pdo = $pdo;
        $this->videoId = $videoId;
        $this->userId = $userId;
    }
    
    /**
     * Get quality profiles with current bandwidth estimate
     */
    public function getQualityProfiles($estimatedBandwidth = null) {
        $profiles = [];
        
        foreach ($this->qualityProfiles as $key => $profile) {
            $profiles[$key] = $profile;
            
            // Mark recommended quality based on bandwidth
            if ($estimatedBandwidth && $key !== 'auto') {
                $required_mbps = $profile['bitrate'] / 1000;
                $estimated_mbps = $estimatedBandwidth / 1000;
                $profiles[$key]['recommended'] = ($estimated_mbps >= $required_mbps * 1.2);
            }
        }
        
        return $profiles;
    }
    
    /**
     * Recommend quality based on network conditions
     */
    public function recommendQuality($bandwidth, $videoDuration) {
        // bandwidth is in bytes/sec
        $bandwidthMbps = ($bandwidth * 8) / 1000000;
        
        // Add 20% headroom for stability
        $safetyMargin = $bandwidthMbps * 0.8;
        
        // Recommend highest quality that fits bandwidth
        if ($safetyMargin >= 6) return '1080';
        if ($safetyMargin >= 3.5) return '720';
        if ($safetyMargin >= 1.5) return '480';
        if ($safetyMargin >= 0.75) return '360';
        
        return '240';
    }
    
    /**
     * Store user's bandwidth estimate for future sessions
     */
    public function saveBandwidthEstimate($bandwidth) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE video_watch_history 
                SET estimated_bandwidth = ?, 
                    last_bandwidth_test = NOW()
                WHERE student_id = ? AND video_id = ?
                LIMIT 1
            ");
            $stmt->execute([$bandwidth, $this->userId, $this->videoId]);
        } catch (Exception $e) {
            // Silently fail - not critical
        }
    }
    
    /**
     * Get user's average bandwidth from previous sessions
     */
    public function getUserAverageBandwidth() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(estimated_bandwidth) as avg_bandwidth
                FROM video_watch_history
                WHERE student_id = ? 
                AND estimated_bandwidth > 0
                AND last_bandwidth_test > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch();
            
            return $result['avg_bandwidth'] ? floatval($result['avg_bandwidth']) : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Get quality information via API
 */
if (php_sapi_name() !== 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../includes/db.php';
    require_once __DIR__ . '/../../includes/functions.php';
    
    header('Content-Type: application/json');
    
    requireLogin();
    
    $videoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $bandwidth = isset($_GET['bandwidth']) ? floatval($_GET['bandwidth']) : null;
    
    if (!$videoId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing video ID']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, duration FROM videos WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        
        if (!$video) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Video not found']);
            exit;
        }
        
        $manager = new AdaptiveStreamingManager($pdo, $videoId, $_SESSION['user_id']);
        
        // Get user's average bandwidth
        $avgBandwidth = $manager->getUserAverageBandwidth();
        $estimatedBandwidth = $bandwidth ?? $avgBandwidth;
        
        $qualityProfiles = $manager->getQualityProfiles($estimatedBandwidth);
        
        $response = [
            'success' => true,
            'qualities' => $qualityProfiles,
            'recommended' => $manager->recommendQuality($estimatedBandwidth ?? 5000000, $video['duration']),
            'estimated_bandwidth' => $estimatedBandwidth
        ];
        
        // Save bandwidth estimate if provided
        if ($bandwidth) {
            $manager->saveBandwidthEstimate($bandwidth);
        }
        
        echo json_encode($response);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
