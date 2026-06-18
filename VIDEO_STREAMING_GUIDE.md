# Video Streaming Platform - Complete Implementation

## Overview
A professional video streaming platform with pagination, modal player, and sample videos.

## Features Implemented

### 1. **Video Grid Layout (3x3 with Pagination)**
- 9 videos per page (3 columns × 3 rows)
- Previous/Next pagination buttons
- Page counter display
- Button auto-disable at boundaries

### 2. **Modal Video Player**
- Opens when clicking a video card
- Shows video in black player frame
- Quality selector (Auto, 1080p, 720p, 480p, 360p)
- Displays video info: Title, Channel, Views, Duration
- Full video description
- YouTube and MP4 support
- Close with ✕ button or Escape key
- Overlay click to close

### 3. **Video Card Design**
- Gradient thumbnail backgrounds
- Play icon overlay
- Duration badge
- Title (2-line clamp)
- Channel/Instructor name
- View count
- Hover effects and animations

### 4. **Sample Videos**
- 9 sample videos with YouTube links
- Realistic titles and descriptions
- Different durations
- Ready to play

---

## File Structure

### HTML (PHP)
**File:** `student/video_streaming.php`

```php
<?php
/**
 * Video Streaming Platform - ULTRA COMPACT
 * Videos play ONLY in the main black/dark player area
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'Video Learning Platform';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/video-streaming.css">

<!-- VIDEO PLAYER MODAL -->
<div id="videoModal" class="video-modal">
    <div class="video-modal-overlay" onclick="closeVideoModal()"></div>
    <div class="video-modal-content">
        <div class="video-modal-header">
            <h2 id="modalVideoTitle" class="modal-video-title">Video Title</h2>
            <button class="modal-close-btn" onclick="closeVideoModal()">✕</button>
        </div>
        
        <div class="video-modal-player">
            <video id="modalVideoPlayer" class="modal-video-player" controls controlsList="nodownload">
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>

        <div class="video-modal-controls">
            <div class="quality-selector-wrapper">
                <label class="quality-label" for="modalQualitySelector">Quality:</label>
                <select id="modalQualitySelector" class="quality-select" onchange="changeQuality(this.value)">
                    <option value="auto">Auto</option>
                    <option value="1080">1080p</option>
                    <option value="720">720p</option>
                    <option value="480">480p</option>
                    <option value="360">360p</option>
                </select>
            </div>
            <div class="bitrate-display">
                <span id="modalBitrate">Adaptive</span>
            </div>
        </div>

        <div class="video-modal-info">
            <div class="modal-info-item">
                <span class="modal-info-label">Channel:</span>
                <span id="modalInstructor" class="modal-info-value">Unknown</span>
            </div>
            <div class="modal-info-item">
                <span class="modal-info-label">Views:</span>
                <span id="modalViews" class="modal-info-value">0</span>
            </div>
            <div class="modal-info-item">
                <span class="modal-info-label">Duration:</span>
                <span id="modalDuration" class="modal-info-value">0:00</span>
            </div>
        </div>

        <div class="video-modal-description">
            <p id="modalDescription" class="modal-description-text">No description available</p>
        </div>
    </div>
</div>

<div class="video-streaming-container">
    <!-- SIDEBAR: VIDEO LIST GRID -->
    <aside class="video-sidebar">
        <!-- Videos Grid -->
        <div class="videos-list-container">
            <div id="videoList" class="videos-list">
                <!-- Videos loaded from database -->
            </div>
        </div>
        
        <!-- Pagination Controls -->
        <div class="pagination-controls">
            <button id="prevBtn" class="pagination-btn" onclick="previousPage()" disabled>← Previous</button>
            <div class="pagination-info">
                <span>Page <span id="currentPage">1</span> of <span id="totalPages">1</span></span>
            </div>
            <button id="nextBtn" class="pagination-btn" onclick="nextPage()" disabled>Next →</button>
        </div>
    </aside>
</div>

<script src="<?= BASE_URL ?>/assets/js/video-streaming.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
```

---

### CSS (Key Styles)
**File:** `assets/css/video-streaming.css`

```css
/* VIDEO MODAL - PLAYER FRAME */
.video-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.video-modal.active {
    display: flex;
}

.video-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    cursor: pointer;
}

.video-modal-content {
    position: relative;
    z-index: 10001;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.video-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e0e0e0;
    background: #f9f9f9;
}

.modal-video-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: #222;
    flex: 1;
}

.modal-close-btn {
    background: none;
    border: none;
    font-size: 28px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 40px;
    height: 40px;
}

.modal-close-btn:hover {
    background: #f0f0f0;
    border-radius: 6px;
}

.video-modal-player {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 */
    background: #000;
    overflow: hidden;
    min-height: 300px;
}

.modal-video-player {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #000;
}

.video-modal-info {
    padding: 16px;
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
    border-bottom: 1px solid #e0e0e0;
    background: #f9f9f9;
}

.modal-info-item {
    display: flex;
    gap: 8px;
    align-items: center;
}

.modal-info-label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.modal-info-value {
    font-size: 14px;
    font-weight: 500;
    color: #222;
}

.video-modal-description {
    padding: 16px;
    flex: 1;
    overflow-y: auto;
}

.modal-description-text {
    font-size: 13px;
    line-height: 1.6;
    color: #333;
    margin: 0;
}

/* Pagination Controls */
.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.pagination-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: #f5f6f7;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    color: #333;
    font-weight: 600;
    transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
    background: #e8eaed;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Dark theme support */
body[data-theme="dark"] .video-modal-content {
    background: #1a1a1a;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.7);
}

body[data-theme="dark"] .modal-video-title {
    color: #fff;
}

body[data-theme="dark"] .modal-info-label {
    color: #aaa;
}

body[data-theme="dark"] .modal-info-value {
    color: #fff;
}

body[data-theme="dark"] .modal-description-text {
    color: #ccc;
}

body[data-theme="dark"] .pagination-controls {
    background: #1a1a1a;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

body[data-theme="dark"] .pagination-btn {
    background: #2a2a2a;
    border-color: #444;
    color: #fff;
}
```

---

### JavaScript (Key Functions)
**File:** `assets/js/video-streaming.js`

```javascript
// Pagination state
let currentPage = 1;
const itemsPerPage = 9; // 3x3 grid
let totalPages = 1;

/**
 * Display videos in paginated grid (3x3 = 9 per page)
 */
function displayVideoList() {
    const container = document.getElementById('videoList');
    
    if (!container) {
        console.error('Video list container not found');
        return;
    }

    container.innerHTML = '';

    if (filteredVideosList.length === 0) {
        container.innerHTML = '<div class="empty-state">No videos found</div>';
        updatePagination();
        return;
    }

    // Calculate pagination
    totalPages = Math.ceil(filteredVideosList.length / itemsPerPage);
    
    // Ensure currentPage is valid
    if (currentPage > totalPages) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }

    // Calculate start and end indices for current page
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;

    // Use for loop to display only videos on current page
    for (let i = startIndex; i < endIndex && i < filteredVideosList.length; i++) {
        const video = filteredVideosList[i];
        const videoItem = createVideoListItem(video);
        container.appendChild(videoItem);
    }

    // Update pagination controls
    updatePagination();
}

/**
 * Create a single video list item element - CARD STYLE
 */
function createVideoListItem(video) {
    const item = document.createElement('div');
    item.className = `video-item ${currentVideoId === video.video_id ? 'active' : ''}`;
    item.onclick = () => openVideoModal(video);
    
    const title = video.title || 'Untitled Video';
    const duration = formatDuration(video.duration || 0);
    const instructor = video.instructor || 'Unknown';
    const views = video.views || 0;
    
    // Generate thumbnail with gradient background
    const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    const colorIndex = Math.floor(Math.abs(video.video_id) % colors.length);
    const bgColor = colors[colorIndex];
    
    item.innerHTML = `
        <div class="video-card">
            <div class="video-thumbnail" style="background: linear-gradient(135deg, ${bgColor} 0%, ${adjustBrightness(bgColor, -20)} 100%);">
                <div class="play-icon">▶</div>
                <div class="duration-badge">${duration}</div>
            </div>
            <div class="video-card-content">
                <h3 class="video-card-title">${escapeHtml(title)}</h3>
                <p class="video-card-instructor">${escapeHtml(instructor)}</p>
                <div class="video-card-meta">
                    <span class="views">${views} views</span>
                </div>
            </div>
        </div>
    `;
    
    return item;
}

/**
 * Open video player modal with video details
 */
function openVideoModal(video) {
    if (!video || !video.video_id) {
        console.error('Invalid video object');
        return;
    }

    // Update current video state
    currentVideoId = video.video_id;
    currentVideoData = video;

    // Get modal elements
    const modal = document.getElementById('videoModal');
    const player = document.getElementById('modalVideoPlayer');
    const title = document.getElementById('modalVideoTitle');
    const instructor = document.getElementById('modalInstructor');
    const views = document.getElementById('modalViews');
    const duration = document.getElementById('modalDuration');
    const description = document.getElementById('modalDescription');

    if (!modal || !player) {
        console.error('Modal elements not found');
        return;
    }

    // Update modal content
    title.textContent = escapeHtml(video.title || 'Untitled Video');
    instructor.textContent = escapeHtml(video.instructor || 'Unknown');
    views.textContent = (video.views || 0).toLocaleString() + ' views';
    duration.textContent = formatDuration(video.duration || 0);
    description.textContent = escapeHtml(video.description || 'No description available');

    // Handle different video formats
    if (video.video_file && video.video_file.startsWith('youtube:')) {
        // YouTube video
        const youtubeId = video.video_file.substring(8);
        player.parentElement.innerHTML = `
            <iframe class="youtube-iframe" 
                src="https://www.youtube.com/embed/${youtubeId}?autoplay=1&rel=0&modestbranding=1&fs=1" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
            </iframe>
        `;
    } else {
        // Regular video file
        const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
        const videoPath = baseUrl + '/uploads/videos/' + video.video_file;
        player.src = videoPath;
    }

    // Show modal
    modal.classList.add('active');

    // Disable body scroll
    document.body.style.overflow = 'hidden';

    // Log watch event
    logWatchEvent(video.video_id);
}

/**
 * Close video player modal
 */
function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    if (modal) {
        modal.classList.remove('active');
        
        // Stop video playback
        const player = document.getElementById('modalVideoPlayer');
        if (player && player.tagName === 'VIDEO') {
            player.pause();
            player.currentTime = 0;
        }
    }

    // Enable body scroll
    document.body.style.overflow = 'auto';
}

/**
 * Close modal when pressing Escape key
 */
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeVideoModal();
    }
});

/**
 * Go to previous page
 */
function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        displayVideoList();
    }
}

/**
 * Go to next page
 */
function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        displayVideoList();
    }
}

/**
 * Update pagination controls (buttons and info)
 */
function updatePagination() {
    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');
    
    if (currentPageSpan) {
        currentPageSpan.textContent = currentPage;
    }
    if (totalPagesSpan) {
        totalPagesSpan.textContent = totalPages;
    }

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
    }
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    }
}

/**
 * Format duration from seconds to MM:SS format
 */
function formatDuration(seconds) {
    if (!seconds || seconds <= 0) return '0:00';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}
```

---

## Setup Instructions

### 1. Add Sample Videos
Run this in your browser to add 9 sample videos:
```
http://localhost/EXAMs/add_sample_videos.php
```

### 2. Access Video Platform
```
http://localhost/EXAMs/student/video_streaming.php
```

### 3. Usage
- Click any video card to open the modal player
- Video plays in the black frame with full controls
- See title, channel, views, duration, and description
- Use Previous/Next buttons to navigate between pages
- Close with ✕ button or press Escape
- Select quality from dropdown

---

## Features Summary

✅ **3x3 Grid Layout** - 9 videos per page  
✅ **Pagination** - Previous/Next with auto-disable  
✅ **Modal Player** - Click card to play in modal frame  
✅ **Video Info** - Title, channel, views, duration, description  
✅ **Quality Selector** - Multiple quality options  
✅ **YouTube Support** - Play YouTube videos  
✅ **Dark Theme** - Full dark mode support  
✅ **Responsive** - Mobile, tablet, and desktop layouts  
✅ **Sample Videos** - 9 ready-to-play videos  

---

## Browser Support
- Chrome/Edge 60+
- Firefox 55+
- Safari 11+
