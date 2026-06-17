/**
 * Video Streaming JavaScript
 * Handles adaptive bitrate streaming and video controls
 */

let currentVideoId = null;
let videosList = [];
let playlistsList = [];

document.addEventListener('DOMContentLoaded', function() {
    loadVideos();
    loadPlaylists();
    loadWatchHistory();
});

async function loadVideos() {
    try {
        const response = await fetch('/api/videos/get_videos.php');
        const data = await response.json();
        
        if (data.success && data.videos) {
            videosList = data.videos;
            displayVideoList();
            
            // Load first video by default
            if (videosList.length > 0) {
                playVideo(videosList[0]);
            }
        }
    } catch (error) {
        console.error('Error loading videos:', error);
    }
}

function displayVideoList() {
    const container = document.getElementById('videoList');
    container.innerHTML = '';
    
    videosList.forEach((video, idx) => {
        const item = document.createElement('button');
        item.className = `list-group-item text-start ${currentVideoId === video.video_id ? 'active' : ''}`;
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${video.title}</h6>
                    <small class="text-muted">${formatDuration(video.duration)}</small>
                </div>
                <small class="badge bg-secondary">${video.views} views</small>
            </div>
        `;
        item.onclick = () => playVideo(video);
        container.appendChild(item);
    });
}

function playVideo(video) {
    currentVideoId = video.video_id;
    
    // Update video source based on quality
    const quality = document.getElementById('qualitySelector').value;
    const sourceUrl = `/api/videos/stream.php?id=${video.video_id}&quality=${quality}`;
    
    document.getElementById('videoPlayer').src = sourceUrl;
    document.getElementById('videoTitle').textContent = video.title;
    document.getElementById('videoDescription').textContent = video.description;
    
    // Update active state
    displayVideoList();
    
    // Log watch event
    logWatchEvent(video.video_id);
    
    // Update bitrate display
    updateBitrateDisplay(quality);
}

function changeQuality(quality) {
    if (currentVideoId) {
        const video = videosList.find(v => v.video_id === currentVideoId);
        if (video) {
            playVideo(video);
        }
    }
}

function updateBitrateDisplay(quality) {
    const bitrates = {
        'auto': 'Adaptive',
        '1080': '5-8 Mbps (1080p)',
        '720': '2.5-5 Mbps (720p)',
        '480': '1-2.5 Mbps (480p)',
        '360': '0.5-1 Mbps (360p)'
    };
    
    document.getElementById('bitrate').textContent = bitrates[quality] || 'Auto';
}

async function loadPlaylists() {
    try {
        const response = await fetch('/api/videos/get_playlists.php');
        const data = await response.json();
        
        if (data.success && data.playlists) {
            playlistsList = data.playlists;
            displayPlaylists();
        }
    } catch (error) {
        console.error('Error loading playlists:', error);
    }
}

function displayPlaylists() {
    const container = document.getElementById('playlistsContainer');
    container.innerHTML = '';
    
    if (playlistsList.length === 0) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted">No playlists yet. Create one to get started!</div>';
        return;
    }
    
    playlistsList.forEach(playlist => {
        const card = document.createElement('div');
        card.className = 'col-md-6 col-lg-4 mb-3';
        card.innerHTML = `
            <div class="card border-0 h-100">
                <div class="card-body">
                    <h6 class="card-title">${playlist.name}</h6>
                    <p class="card-text small text-muted">${playlist.description}</p>
                    <small class="badge bg-success">${playlist.video_count} videos</small>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-outline-primary w-100" onclick="openPlaylist(${playlist.playlist_id})">
                        View Playlist
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

async function loadWatchHistory() {
    try {
        const response = await fetch('/api/videos/get_watch_history.php');
        const data = await response.json();
        
        if (data.success && data.history) {
            displayWatchHistory(data.history);
        }
    } catch (error) {
        console.error('Error loading watch history:', error);
    }
}

function displayWatchHistory(history) {
    const tbody = document.getElementById('watchHistory');
    tbody.innerHTML = '';
    
    if (history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No watch history yet</td></tr>';
        return;
    }
    
    history.forEach(item => {
        const row = document.createElement('tr');
        const progress = Math.round((item.watch_time / item.duration) * 100);
        
        row.innerHTML = `
            <td>${item.title}</td>
            <td><small class="text-muted">${formatDate(item.watched_at)}</small></td>
            <td>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: ${progress}%"></div>
                </div>
                <small class="text-muted">${progress}%</small>
            </td>
            <td>${formatDuration(item.duration)}</td>
        `;
        
        tbody.appendChild(row);
    });
}

function createPlaylist() {
    const name = prompt('Enter playlist name:');
    if (!name) return;
    
    fetch('/api/videos/create_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadPlaylists();
            alert('Playlist created!');
        }
    });
}

function addToPlaylist() {
    if (!currentVideoId) {
        alert('Please select a video first');
        return;
    }
    
    fetch('/api/videos/add_to_playlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            video_id: currentVideoId,
            playlist_id: 1
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Added to playlist!');
        }
    });
}

function openPlaylist(playlistId) {
    window.location.href = `/student/playlist.php?id=${playlistId}`;
}

function shareVideo() {
    const video = videosList.find(v => v.video_id === currentVideoId);
    if (!video) return;
    
    const url = `${window.location.origin}/student/video_streaming.php?video=${currentVideoId}`;
    
    if (navigator.share) {
        navigator.share({
            title: video.title,
            text: video.description,
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(url);
        alert('Link copied to clipboard!');
    }
}

function downloadVideo() {
    if (!currentVideoId) {
        alert('Please select a video first');
        return;
    }
    
    // Check subscription
    fetch('/api/videos/can_download.php')
        .then(r => r.json())
        .then(data => {
            if (data.can_download) {
                window.location.href = `/api/videos/download.php?id=${currentVideoId}`;
            } else {
                alert('Upgrade to premium to download videos');
            }
        });
}

function logWatchEvent(videoId) {
    fetch('/api/videos/log_watch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ video_id: videoId })
    }).catch(e => console.log('Watch event logged'));
}

function formatDuration(seconds) {
    if (!seconds) return '0:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);
    
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }
    
    return date.toLocaleDateString();
}

// Initialize icons
lucide.createIcons();
