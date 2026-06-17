<?php
/**
 * Video Streaming Platform
 * Adaptive bitrate streaming for study materials
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'Video Streaming';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">
                <i data-lucide="play-circle" class="me-2"></i>
                Video Learning Platform
            </h1>
            <p class="text-muted">Stream high-quality educational videos with adaptive bitrate</p>
        </div>
    </div>

    <!-- Video Player -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <video id="videoPlayer" class="w-100" style="background: #000; border-radius: 12px 12px 0 0;" controls>
                        <source src="" type="video/mp4">
                        Your browser doesn't support HTML5 video.
                    </video>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Video Quality:</small>
                            <select id="qualitySelector" class="form-select form-select-sm d-inline-block w-auto" onchange="changeQuality(this.value)">
                                <option value="auto">Auto (Adaptive)</option>
                                <option value="1080">1080p</option>
                                <option value="720">720p</option>
                                <option value="480">480p</option>
                                <option value="360">360p</option>
                            </select>
                        </div>
                        <div>
                            <small class="text-muted" id="bitrate">Loading...</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Info -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h5 id="videoTitle" class="mb-2">Select a video to watch</h5>
                    <p id="videoDescription" class="text-muted mb-3">Description will appear here</p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="addToPlaylist()">
                            <i data-lucide="plus" style="width: 16px;"></i> Add to Playlist
                        </button>
                        <button class="btn btn-outline-secondary" onclick="shareVideo()">
                            <i data-lucide="share-2" style="width: 16px;"></i> Share
                        </button>
                        <button class="btn btn-outline-info" onclick="downloadVideo()">
                            <i data-lucide="download" style="width: 16px;"></i> Download
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Video List -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="list-video" class="me-2"></i>
                        Videos
                    </h5>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                    <div id="videoList" class="list-group list-group-flush">
                        <!-- Videos will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Playlists -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-lucide="list" class="me-2"></i>
                        My Playlists
                    </h5>
                    <button class="btn btn-sm btn-primary" onclick="createPlaylist()">
                        <i data-lucide="plus" style="width: 16px;"></i> New Playlist
                    </button>
                </div>
                <div class="card-body">
                    <div id="playlistsContainer" class="row">
                        <!-- Playlists will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Watch History -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="history" class="me-2"></i>
                        Watch History
                    </h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Video Title</th>
                                <th>Watched</th>
                                <th>Progress</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody id="watchHistory">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No watch history yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #videoPlayer {
        border-radius: 12px;
        width: 100%;
        height: auto;
        max-height: 500px;
    }

    .list-group-item {
        border: none;
        border-bottom: 1px solid #e9ecef;
        cursor: pointer;
        padding: 12px;
        transition: all 0.3s ease;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    .list-group-item.active {
        background-color: #5865f2;
        color: white;
        border-color: #5865f2;
    }

    body[data-theme="dark"] .list-group-item:hover {
        background-color: #2a2f37;
    }

    body[data-theme="dark"] .list-group-item {
        border-bottom-color: #3a3f47;
    }
</style>

<script src="/assets/js/video-streaming.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
