/**
 * Video Streaming Platform JavaScript
 * Videos play ONLY in the main black/dark player area
 * No other elements can play videos
 */

let currentVideoId = null;
let currentVideoData = null;
let videosList = [];
let filteredVideosList = [];

// Pagination state
let currentPage = 1;
const itemsPerPage = 9; // 3x3 grid
let totalPages = 1;

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Video Streaming Platform initialized');
    loadVideos();
    setupEventListeners();
});

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Prevent right-click on video player
    const videoPlayer = document.getElementById('videoPlayer');
    if (videoPlayer) {
        videoPlayer.addEventListener('contextmenu', (e) => e.preventDefault());
    }
}

/**
 * Load all videos from API
 */
async function loadVideos() {
    try {
        const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
        const response = await fetch(baseUrl + '/api/videos/get_videos.php');
        const data = await response.json();
        
        if (data.success && data.videos && Array.isArray(data.videos)) {
            videosList = data.videos;
            filteredVideosList = [...videosList];
            displayVideoList();
            
            // Auto-load first video if available
            if (videosList.length > 0) {
                playVideo(videosList[0]);
            }
        } else {
            console.error('Failed to load videos:', data.message || 'Unknown error');
            showEmptyState();
        }
    } catch (error) {
        console.error('Error loading videos:', error);
        showEmptyState();
    }
}

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
    item.style.cursor = 'pointer';
    item.onclick = () => {
        // Open video player in new page
        const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
        window.open(baseUrl + '/student/play_video.php?id=' + video.video_id, '_blank', 'width=1200,height=800,resizable=yes,scrollbars=yes');
    };
    
    const title = video.title || 'Untitled Video';
    const duration = formatDuration(video.duration || 0);
    const instructor = video.instructor || 'Unknown';
    const course = video.course || 'General';
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
 * Adjust brightness of hex color
 */
function adjustBrightness(color, percent) {
    const num = parseInt(color.replace("#",""), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) + amt;
    const G = (num >> 8 & 0x00FF) + amt;
    const B = (num & 0x0000FF) + amt;
    return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
        (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
        (B < 255 ? B < 1 ? 0 : B : 255))
        .toString(16).slice(1);
}

/**
 * Play video - ONLY in the main black player area
 */
function playVideo(video) {
    if (!video || !video.video_id) {
        console.error('Invalid video object');
        return;
    }

    // Update current video state
    currentVideoId = video.video_id;
    currentVideoData = video;

    // Update UI
    updateVideoTitle(video.title || 'Untitled Video');
    updateVideoDescription(video.description || 'No description available');
    
    // Update sidebar active state
    updateSidebarActiveState();

    // Log watch event
    logWatchEvent(video.video_id);

    // Play the video in the main player ONLY
    if (video.video_file && video.video_file.startsWith('youtube:')) {
        // Handle YouTube video
        playYouTubeVideo(video);
    } else {
        // Handle regular video file
        playRegularVideo(video);
    }
}

/**
 * Play regular video file in the main player
 */
function playRegularVideo(video) {
    const qualitySelector = document.getElementById('qualitySelector');
    const quality = qualitySelector ? qualitySelector.value : 'auto';
    
    const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
    const sourceUrl = `${baseUrl}/api/videos/stream.php?id=${video.video_id}&quality=${quality}`;
    
    const videoPlayer = document.getElementById('videoPlayer');
    
    if (!videoPlayer) {
        console.error('Video player element not found');
        return;
    }

    // Clear and set new source
    videoPlayer.innerHTML = '';
    const source = document.createElement('source');
    source.src = sourceUrl;
    source.type = 'video/mp4';
    videoPlayer.appendChild(source);
    
    // Load the new source
    videoPlayer.load();
    
    // Re-enable quality selector for regular videos
    if (qualitySelector) {
        qualitySelector.disabled = false;
        qualitySelector.title = 'Adjust video quality';
    }
    
    updateBitrateDisplay(quality);
}

/**
 * Play YouTube video in the main player
 */
function playYouTubeVideo(video) {
    // Extract YouTube ID from video_file (format: youtube:VIDEO_ID)
    const youtubeId = video.video_file.substring(8);
    
    const videoPlayerContainer = document.getElementById('videoPlayerContainer');
    
    if (!videoPlayerContainer) {
        console.error('Video player container not found');
        return;
    }

    // Create iframe for YouTube
    videoPlayerContainer.innerHTML = `
        <iframe 
            class="youtube-iframe"
            width="100%" 
            height="100%"
            src="https://www.youtube.com/embed/${youtubeId}?autoplay=1&rel=0&modestbranding=1&fs=1" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
        </iframe>
    `;

    // Add CSS for iframe
    const style = document.createElement('style');
    style.textContent = `
        .youtube-iframe {
            display: block !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: #000 !important;
        }
    `;
    document.head.appendChild(style);

    // Disable quality selector for YouTube videos
    const qualitySelector = document.getElementById('qualitySelector');
    if (qualitySelector) {
        qualitySelector.disabled = true;
        qualitySelector.title = 'Quality control not available for YouTube videos';
    }

    document.getElementById('bitrate').textContent = 'YouTube Stream';
}

/**
 * Change video quality
 */
function changeQuality(quality) {
    if (!currentVideoData || !quality) return;

    // Don't allow quality change for YouTube videos
    if (currentVideoData.video_file && currentVideoData.video_file.startsWith('youtube:')) {
        alert('Quality control is not available for YouTube videos');
        return;
    }

    // Replay video with new quality
    playRegularVideo(currentVideoData);
}

/**
 * Update bitrate display based on quality
 */
function updateBitrateDisplay(quality) {
    const bitrates = {
        'auto': 'Adaptive',
        '1080': '5-8 Mbps (1080p)',
        '720': '2.5-5 Mbps (720p)',
        '480': '1-2.5 Mbps (480p)',
        '360': '0.5-1 Mbps (360p)'
    };
    
    const bitrateElement = document.getElementById('bitrate');
    if (bitrateElement) {
        bitrateElement.textContent = bitrates[quality] || 'Auto';
    }
}

/**
 * Update video title in the info section
 */
function updateVideoTitle(title) {
    const titleElement = document.getElementById('videoTitle');
    if (titleElement) {
        titleElement.textContent = escapeHtml(title);
    }
}

/**
 * Update video description in the info section
 */
function updateVideoDescription(description) {
    const descElement = document.getElementById('videoDescription');
    if (descElement) {
        descElement.textContent = escapeHtml(description);
    }
}

/**
 * Update sidebar active state
 */
function updateSidebarActiveState() {
    const videoItems = document.querySelectorAll('.video-item');
    videoItems.forEach(item => {
        item.classList.remove('active');
    });

    const activeItem = document.querySelector(`.video-item[data-video-id="${currentVideoId}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
}

/**
 * Filter videos by search term
 */
function filterVideos(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    
    if (term === '') {
        filteredVideosList = [...videosList];
    } else {
        filteredVideosList = videosList.filter(video => {
            const title = (video.title || '').toLowerCase();
            const description = (video.description || '').toLowerCase();
            return title.includes(term) || description.includes(term);
        });
    }
    
    displayVideoList();
}

/**
 * Toggle search box visibility
 */
function toggleSearchBox() {
    const searchBox = document.getElementById('searchBox');
    if (!searchBox) return;
    
    const isHidden = searchBox.style.display === 'none';
    searchBox.style.display = isHidden ? 'block' : 'none';
    
    if (isHidden) {
        const searchInput = document.getElementById('videoSearch');
        if (searchInput) {
            searchInput.focus();
        }
    }
}

/**
 * Add current video to playlist
 */
function addToPlaylist() {
    if (!currentVideoId || !currentVideoData) {
        alert('Please select a video first');
        return;
    }

    const playlistName = prompt('Enter playlist name:');
    if (!playlistName) return;

    const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
    
    fetch(baseUrl + '/api/videos/add_to_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            video_id: currentVideoId,
            playlist_name: playlistName
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Video added to playlist!');
        } else {
            alert('Failed to add video to playlist: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error adding to playlist:', error);
        alert('Error adding video to playlist');
    });
}

/**
 * Share current video
 */
function shareVideo() {
    if (!currentVideoId || !currentVideoData) {
        alert('Please select a video first');
        return;
    }

    const title = currentVideoData.title || 'Check out this video';
    const description = currentVideoData.description || 'Watch this educational video';
    const url = `${window.location.origin}/student/video_streaming.php?video=${currentVideoId}`;

    if (navigator.share) {
        navigator.share({
            title: title,
            text: description,
            url: url
        }).catch(err => console.log('Share cancelled'));
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url);
        alert('Link copied to clipboard!');
    }
}

/**
 * Log watch event
 */
function logWatchEvent(videoId) {
    const baseUrl = typeof window.BASE_URL !== 'undefined' ? window.BASE_URL : 'http://localhost/EXAMs';
    
    fetch(baseUrl + '/api/videos/log_watch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId })
    }).catch(e => {
        console.log('Watch event logged silently');
    });
}

/**
 * Show empty state when no videos available
 */
function showEmptyState() {
    const container = document.getElementById('videoList');
    if (container) {
        container.innerHTML = '<div class="empty-state">No videos available</div>';
    }

    const titleElement = document.getElementById('videoTitle');
    if (titleElement) {
        titleElement.textContent = 'No videos available';
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

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
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
        player.parentElement.innerHTML = `<video id="modalVideoPlayer" class="modal-video-player" controls controlsList="nodownload">
            <source src="${videoPath}" type="video/mp4">
            Your browser does not support the video tag.
        </video>`;
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
    // Update page info
    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');
    
    if (currentPageSpan) {
        currentPageSpan.textContent = currentPage;
    }
    if (totalPagesSpan) {
        totalPagesSpan.textContent = totalPages;
    }

    // Update button states
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    if (prevBtn) {
        prevBtn.disabled = currentPage === 1;
    }
    if (nextBtn) {
        nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    }
}

// Initialize Lucide icons if available
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
