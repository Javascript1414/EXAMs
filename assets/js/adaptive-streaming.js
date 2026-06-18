/**
 * Adaptive Video Streaming Client
 * Detects bandwidth and switches quality automatically
 * Implements YouTube-style adaptive streaming
 */

class AdaptiveVideoStreamer {
    constructor(videoId) {
        this.videoId = videoId;
        this.videoPlayer = null;
        this.baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
        this.bandwidthTest = new BandwidthDetector();
        this.currentQuality = 'auto';
        this.qualityProfiles = {};
        this.isBuffering = false;
        this.bufferWarnThreshold = 2000; // milliseconds
        this.maxConsecutiveBuffers = 3;
        this.consecutiveBufferCount = 0;
        this.lastBufferTime = 0;
    }
    
    /**
     * Initialize adaptive streaming
     */
    async init(videoPlayer) {
        this.videoPlayer = videoPlayer;
        
        // Detect initial bandwidth
        await this.bandwidthTest.detect();
        const bandwidth = this.bandwidthTest.getEstimatedBandwidth();
        
        // Load quality profiles
        await this.loadQualityProfiles(bandwidth);
        
        // Setup event listeners
        this.setupBufferingDetection();
        this.setupQualityMonitoring();
        
        // Set initial quality
        this.applyRecommendedQuality();
        
        console.log('Adaptive streaming initialized. Bandwidth:', (bandwidth / 1000000).toFixed(1), 'Mbps');
    }
    
    /**
     * Detect network bandwidth using a test chunk
     */
    async loadQualityProfiles(bandwidth) {
        try {
            const response = await fetch(
                `${this.baseUrl}/api/videos/adaptive_streaming.php?id=${this.videoId}&bandwidth=${bandwidth}`
            );
            const data = await response.json();
            
            if (data.success) {
                this.qualityProfiles = data.qualities;
                this.currentQuality = data.recommended || 'auto';
                return data.recommended;
            }
        } catch (error) {
            console.warn('Failed to load quality profiles:', error);
        }
        
        return 'auto';
    }
    
    /**
     * Apply recommended quality based on bandwidth
     */
    applyRecommendedQuality() {
        if (this.currentQuality && this.qualityProfiles[this.currentQuality]) {
            const qualityLabel = this.qualityProfiles[this.currentQuality].label;
            console.log('Applying quality:', qualityLabel);
            this.updateVideoQuality(this.currentQuality);
        }
    }
    
    /**
     * Setup buffering detection and recovery
     */
    setupBufferingDetection() {
        if (!this.videoPlayer) return;
        
        // Monitor buffer health
        let lastTime = 0;
        const checkInterval = setInterval(() => {
            if (!this.videoPlayer) {
                clearInterval(checkInterval);
                return;
            }
            
            const currentTime = this.videoPlayer.currentTime;
            
            // Check if playback is stalling
            if (currentTime === lastTime && !this.videoPlayer.paused) {
                if (!this.isBuffering) {
                    this.handleBuffering();
                }
            } else {
                this.isBuffering = false;
            }
            
            lastTime = currentTime;
        }, 1000);
        
        // Also listen to native buffering events
        this.videoPlayer.addEventListener('waiting', () => {
            this.handleBuffering();
        });
        
        this.videoPlayer.addEventListener('playing', () => {
            this.isBuffering = false;
            this.consecutiveBufferCount = 0;
        });
    }
    
    /**
     * Handle buffering by reducing quality
     */
    handleBuffering() {
        if (this.isBuffering) return;
        
        this.isBuffering = true;
        this.consecutiveBufferCount++;
        
        const now = Date.now();
        const timeSinceLastBuffer = now - this.lastBufferTime;
        this.lastBufferTime = now;
        
        console.warn(`Buffering detected (${this.consecutiveBufferCount}x). Time since last: ${timeSinceLastBuffer}ms`);
        
        // If buffering happens too frequently, reduce quality
        if (this.consecutiveBufferCount >= this.maxConsecutiveBuffers && timeSinceLastBuffer < 30000) {
            this.reduceQuality();
        }
    }
    
    /**
     * Reduce quality to improve streaming smoothness
     */
    reduceQuality() {
        const qualityOrder = ['1080', '720', '480', '360', '240'];
        const currentIndex = qualityOrder.indexOf(this.currentQuality);
        
        if (currentIndex >= 0 && currentIndex < qualityOrder.length - 1) {
            const newQuality = qualityOrder[currentIndex + 1];
            console.log(`Reducing quality from ${this.currentQuality} to ${newQuality}`);
            
            const currentTime = this.videoPlayer.currentTime;
            this.currentQuality = newQuality;
            this.updateVideoQuality(newQuality);
            
            // Resume from where we left off
            this.videoPlayer.currentTime = currentTime;
            this.videoPlayer.play();
            
            this.consecutiveBufferCount = 0;
        }
    }
    
    /**
     * Increase quality when streaming is smooth
     */
    async improveQuality() {
        const qualityOrder = ['240', '360', '480', '720', '1080'];
        const currentIndex = qualityOrder.indexOf(this.currentQuality);
        
        if (currentIndex > 0) {
            const newQuality = qualityOrder[currentIndex - 1];
            
            // Check if bandwidth supports it
            const bandwidth = this.bandwidthTest.getEstimatedBandwidth();
            const requiredBitrate = this.qualityProfiles[newQuality]?.bitrate || 0;
            
            if ((bandwidth * 8) / 1000 >= requiredBitrate * 1.2) {
                console.log(`Improving quality to ${newQuality}`);
                
                const currentTime = this.videoPlayer.currentTime;
                this.currentQuality = newQuality;
                this.updateVideoQuality(newQuality);
                
                this.videoPlayer.currentTime = currentTime;
                this.videoPlayer.play();
            }
        }
    }
    
    /**
     * Update video quality by changing source
     */
    updateVideoQuality(quality) {
        if (!this.videoPlayer || !this.videoPlayer.src) return;
        
        // Add quality parameter to stream URL
        const url = new URL(this.videoPlayer.src, window.location.origin);
        url.searchParams.set('quality', quality);
        
        this.videoPlayer.src = url.toString();
    }
    
    /**
     * Monitor streaming quality and adjust as needed
     */
    setupQualityMonitoring() {
        if (!this.videoPlayer) return;
        
        // Check every 30 seconds if quality should be improved
        setInterval(() => {
            if (!this.isBuffering && this.videoPlayer && !this.videoPlayer.paused) {
                this.improveQuality();
            }
        }, 30000);
    }
    
    /**
     * Get current quality info
     */
    getQualityInfo() {
        return {
            current: this.currentQuality,
            profiles: this.qualityProfiles,
            isBuffering: this.isBuffering,
            bandwidth: this.bandwidthTest.getEstimatedBandwidth()
        };
    }
    
    /**
     * Manually set quality
     */
    setQuality(quality) {
        if (this.qualityProfiles[quality]) {
            const currentTime = this.videoPlayer.currentTime;
            this.currentQuality = quality;
            this.updateVideoQuality(quality);
            
            setTimeout(() => {
                this.videoPlayer.currentTime = currentTime;
                this.videoPlayer.play();
            }, 100);
        }
    }
}

/**
 * Bandwidth Detection Class
 * Tests actual network speed using small chunks
 */
class BandwidthDetector {
    constructor() {
        this.estimatedBandwidth = null;
        this.testInProgress = false;
    }
    
    /**
     * Detect bandwidth by downloading a test file
     */
    async detect() {
        if (this.testInProgress) return this.estimatedBandwidth;
        if (this.estimatedBandwidth) return this.estimatedBandwidth;
        
        this.testInProgress = true;
        
        try {
            // Create a 1MB test file URL (or use existing resource)
            const testUrl = typeof window.BASE_URL !== 'undefined' 
                ? `${window.BASE_URL}/api/videos/bandwidth_test.bin` 
                : '/bandwidth_test.bin';
            
            // Attempt to detect using image or existing resource
            const testSize = 1048576; // 1MB
            const startTime = performance.now();
            
            const response = await fetch(testUrl, {
                method: 'GET',
                cache: 'no-cache'
            });
            
            if (!response.ok) {
                // Fallback: estimate based on video load time
                this.estimatedBandwidth = this.estimateFallback();
            } else {
                const endTime = performance.now();
                const timeTaken = (endTime - startTime) / 1000; // seconds
                const size = response.headers.get('content-length') || testSize;
                
                this.estimatedBandwidth = (size * 8) / timeTaken; // bits/sec
            }
        } catch (error) {
            console.warn('Bandwidth detection failed:', error);
            this.estimatedBandwidth = this.estimateFallback();
        }
        
        this.testInProgress = false;
        return this.estimatedBandwidth;
    }
    
    /**
     * Fallback bandwidth estimation using navigator API
     */
    estimateFallback() {
        // Use Navigation Timing or fallback to average
        if (navigator.connection) {
            const connection = navigator.connection;
            
            if (connection.downlink) {
                return connection.downlink * 1000000; // Mbps to bytes/sec
            }
            
            if (connection.effectiveType) {
                const speeds = {
                    '4g': 10000000,   // 10 Mbps
                    '3g': 2000000,    // 2 Mbps
                    '2g': 500000,     // 0.5 Mbps
                    'slow-2g': 250000 // 0.25 Mbps
                };
                return speeds[connection.effectiveType] || 5000000;
            }
        }
        
        // Default estimate
        return 5000000; // 5 Mbps
    }
    
    /**
     * Get estimated bandwidth in bytes/sec
     */
    getEstimatedBandwidth() {
        return this.estimatedBandwidth || this.estimateFallback();
    }
    
    /**
     * Get bandwidth in Mbps (formatted)
     */
    getBandwidthMbps() {
        return (this.getEstimatedBandwidth() * 8) / 1000000;
    }
}

/**
 * Global instance management
 */
window.AdaptiveVideoStreamer = AdaptiveVideoStreamer;
window.BandwidthDetector = BandwidthDetector;
