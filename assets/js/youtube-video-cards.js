/**
 * YouTube-Style Video Cards
 * Complete functionality for enhanced cards
 */

class YouTubeVideoCard {
    constructor(video, baseUrl) {
        this.video = video;
        this.baseUrl = baseUrl || 'http://localhost/EXAMs';
        this.liked = false;
        this.menuOpen = false;
    }
    
    /**
     * Create enhanced YouTube-style card element
     */
    create() {
        const container = document.createElement('div');
        container.className = 'video-item';
        container.dataset.videoId = this.video.video_id;
        
        container.innerHTML = `
            <div class="video-card">
                <!-- THUMBNAIL SECTION -->
                <div class="video-thumbnail" style="background-image: url('${this.getThumbnailUrl()}')">
                    <!-- Verified Badge (if instructor is verified) -->
                    ${this.video.instructor_verified ? `
                        <div class="verified-badge">
                            ✓ Verified
                        </div>
                    ` : ''}
                    
                    <!-- Quality Badge -->
                    ${this.getQualityBadge()}
                    
                    <!-- Duration Badge -->
                    <span class="duration-badge">${this.formatDuration(this.video.duration)}</span>
                    
                    <!-- Play Icon -->
                    <div class="play-icon">▶</div>
                    
                    <!-- Watch Progress Bar -->
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: ${this.video.progress || 0}%"></div>
                    </div>
                    
                    <!-- Action Buttons on Hover -->
                    <div class="card-overlay-buttons">
                        <button class="action-btn like" title="Like video" data-action="like">♥</button>
                        <button class="action-btn add-playlist" title="Add to playlist" data-action="playlist">+</button>
                        <button class="action-btn more" title="More options" data-action="more">⋯</button>
                    </div>
                    
                    <!-- Hover Info Tooltip -->
                    <div class="card-hover-info">
                        ${this.video.views} views • ${this.getRelativeDate(this.video.created_at)}
                    </div>
                </div>
                
                <!-- CARD CONTENT -->
                <div class="video-card-content">
                    <!-- Title -->
                    <h3 class="video-card-title">${this.escapeHtml(this.video.title)}</h3>
                    
                    <!-- Instructor with Verified Icon -->
                    <div class="video-card-instructor-row">
                        <span class="video-card-instructor">${this.escapeHtml(this.video.instructor || 'Unknown')}</span>
                        ${this.video.instructor_verified ? '<span class="instructor-verified-icon">✓</span>' : ''}
                    </div>
                    
                    <!-- Metadata (Views, Rating, Upload Date, Progress) -->
                    <div class="video-card-meta">
                        <span class="meta-item">
                            <span>👁</span>
                            <span>${this.formatViews(this.video.views)}</span>
                        </span>
                        <span class="meta-separator">•</span>
                        <span class="meta-item">
                            <span class="rating-stars">${this.getRatingStars(this.video.rating)}</span>
                            <span>${(this.video.rating || 0).toFixed(1)}</span>
                        </span>
                        <span class="meta-separator">•</span>
                        <span class="meta-item">
                            <span>📅</span>
                            <span>${this.getRelativeDate(this.video.created_at)}</span>
                        </span>
                        ${this.video.progress > 0 ? `
                            <span class="watch-percentage">${Math.round(this.video.progress)}% watched</span>
                        ` : ''}
                    </div>
                    
                    <!-- Three Dots Menu -->
                    <button class="more-menu" data-action="more-dots">⋮</button>
                    <div class="dropdown-menu">
                        <button class="dropdown-item" data-action="add-to-playlist">
                            <span>+</span> Add to Playlist
                        </button>
                        <button class="dropdown-item" data-action="save-for-later">
                            <span>⏱</span> Save for Later
                        </button>
                        <button class="dropdown-item" data-action="share">
                            <span>↗</span> Share
                        </button>
                        <button class="dropdown-item" data-action="download">
                            <span>⬇</span> Download
                        </button>
                        <hr style="margin: 4px 0; opacity: 0.2;">
                        <button class="dropdown-item danger" data-action="report">
                            <span>⚠</span> Report
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        this.element = container;
        this.attachEventListeners();
        return container;
    }
    
    /**
     * Get thumbnail URL (uses placeholder if not available)
     */
    getThumbnailUrl() {
        if (this.video.thumbnail) {
            return `${this.baseUrl}/uploads/thumbnails/${this.video.thumbnail}`;
        }
        
        // Generate placeholder with gradient based on video ID
        const colors = ['667eea', '764ba2', '1a73e8', 'ea4335', 'fbbc04', '34a853', '4285f4'];
        const colorIndex = Math.abs(this.video.video_id) % colors.length;
        return `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='320' height='180'%3E%3Crect fill='%23${colors[colorIndex]}' width='320' height='180'/%3E%3Ctext x='50%25' y='50%25' font-size='14' fill='white' text-anchor='middle' dy='.3em'%3E${this.video.title.substring(0, 30)}%3C/text%3E%3C/svg%3E`;
    }
    
    /**
     * Get quality badge HTML
     */
    getQualityBadge() {
        if (!this.video.video_quality) return '';
        
        const qualityMap = {
            '4k': 'uhd',
            '1080': 'hd',
            '720': 'hd'
        };
        
        const badgeClass = qualityMap[this.video.video_quality] || '';
        return `<span class="quality-badge ${badgeClass}">${this.video.video_quality}p</span>`;
    }
    
    /**
     * Format duration to MM:SS
     */
    formatDuration(seconds) {
        if (!seconds || seconds <= 0) return '0:00';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }
        return `${minutes}:${String(secs).padStart(2, '0')}`;
    }
    
    /**
     * Format view count (1.2M, 450K, etc.)
     */
    formatViews(views) {
        if (!views) return '0';
        if (views >= 1000000) return (views / 1000000).toFixed(1) + 'M';
        if (views >= 1000) return (views / 1000).toFixed(1) + 'K';
        return views.toString();
    }
    
    /**
     * Get relative date (2 days ago, 3 weeks ago, etc.)
     */
    getRelativeDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        const weeks = Math.floor(days / 7);
        const months = Math.floor(days / 30);
        const years = Math.floor(days / 365);
        
        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        if (weeks < 4) return `${weeks}w ago`;
        if (months < 12) return `${months}mo ago`;
        return `${years}y ago`;
    }
    
    /**
     * Get star rating display (★★★☆☆)
     */
    getRatingStars(rating) {
        const rate = Math.round(rating || 0);
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= rate ? '★' : '☆';
        }
        return stars;
    }
    
    /**
     * Escape HTML special characters
     */
    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    /**
     * Attach event listeners to card
     */
    attachEventListeners() {
        // Click on card opens video
        this.element.addEventListener('click', (e) => {
            if (e.target.closest('.action-btn') || 
                e.target.closest('.more-menu') || 
                e.target.closest('.dropdown-item')) {
                return; // Let action handlers take over
            }
            this.playVideo();
        });
        
        // Action buttons
        this.element.querySelectorAll('[data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = e.currentTarget.dataset.action;
                this.handleAction(action);
            });
        });
        
        // More menu button
        this.element.querySelector('.more-menu').addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdownMenu();
        });
        
        // Dropdown menu items
        this.element.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const action = e.currentTarget.dataset.action;
                this.handleMenuAction(action);
            });
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target)) {
                this.closeDropdownMenu();
            }
        });
    }
    
    /**
     * Handle overlay action buttons
     */
    handleAction(action) {
        switch (action) {
            case 'like':
                this.toggleLike();
                break;
            case 'playlist':
                this.showAddToPlaylistDialog();
                break;
            case 'more':
                this.toggleDropdownMenu();
                break;
        }
    }
    
    /**
     * Handle dropdown menu actions
     */
    handleMenuAction(action) {
        switch (action) {
            case 'add-to-playlist':
                this.showAddToPlaylistDialog();
                break;
            case 'save-for-later':
                this.saveForLater();
                break;
            case 'share':
                this.shareVideo();
                break;
            case 'download':
                this.downloadVideo();
                break;
            case 'report':
                this.reportVideo();
                break;
        }
        this.closeDropdownMenu();
    }
    
    /**
     * Play video
     */
    playVideo() {
        const url = `${this.baseUrl}/student/play_video.php?id=${this.video.video_id}`;
        window.open(url, '_blank', 'width=1200,height=800,resizable=yes,scrollbars=yes');
    }
    
    /**
     * Like/Unlike video
     */
    toggleLike() {
        this.liked = !this.liked;
        const btn = this.element.querySelector('.action-btn.like');
        btn.classList.toggle('liked');
        
        // Save to backend
        fetch(`${this.baseUrl}/api/videos/toggle_like.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video_id: this.video.video_id,
                liked: this.liked
            })
        }).catch(e => console.warn('Like action failed:', e));
    }
    
    /**
     * Show add to playlist dialog
     */
    showAddToPlaylistDialog() {
        const playlistName = prompt('Enter playlist name:');
        if (!playlistName) return;
        
        fetch(`${this.baseUrl}/api/videos/add_to_playlist.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video_id: this.video.video_id,
                playlist_name: playlistName
            })
        })
        .then(r => r.json())
        .then(data => {
            alert(data.success ? 'Added to playlist!' : 'Failed: ' + data.message);
        })
        .catch(e => alert('Error: ' + e.message));
    }
    
    /**
     * Save for later
     */
    saveForLater() {
        fetch(`${this.baseUrl}/api/videos/save_for_later.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video_id: this.video.video_id
            })
        })
        .then(r => r.json())
        .then(data => {
            alert(data.success ? 'Saved for later!' : 'Failed');
        })
        .catch(e => alert('Error'));
    }
    
    /**
     * Share video
     */
    shareVideo() {
        const url = `${window.location.origin}/student/play_video.php?id=${this.video.video_id}`;
        
        if (navigator.share) {
            navigator.share({
                title: this.video.title,
                text: this.video.description || 'Check out this video',
                url: url
            });
        } else {
            navigator.clipboard.writeText(url);
            alert('Link copied to clipboard!');
        }
    }
    
    /**
     * Download video
     */
    downloadVideo() {
        alert('Download will start shortly...');
        const link = document.createElement('a');
        link.href = `${this.baseUrl}/api/videos/download.php?id=${this.video.video_id}`;
        link.download = `${this.video.title}.mp4`;
        link.click();
    }
    
    /**
     * Report video
     */
    reportVideo() {
        const reason = prompt('Why are you reporting this video?\n\n1. Inappropriate content\n2. Copyright violation\n3. Spam\n4. Other');
        if (!reason) return;
        
        fetch(`${this.baseUrl}/api/videos/report_video.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                video_id: this.video.video_id,
                reason: reason
            })
        })
        .then(r => r.json())
        .then(data => {
            alert('Thank you for your report.');
        });
    }
    
    /**
     * Toggle dropdown menu
     */
    toggleDropdownMenu() {
        const menu = this.element.querySelector('.dropdown-menu');
        menu.classList.toggle('active');
    }
    
    /**
     * Close dropdown menu
     */
    closeDropdownMenu() {
        const menu = this.element.querySelector('.dropdown-menu');
        menu.classList.remove('active');
    }
}

/**
 * Update existing createVideoListItem to use YouTube cards
 */
function createVideoListItem(video) {
    const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
    const card = new YouTubeVideoCard(video, baseUrl);
    return card.create();
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YouTubeVideoCard;
}
