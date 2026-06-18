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
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/youtube-video-cards.css">

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


<script src="<?= BASE_URL ?>/assets/js/youtube-video-cards.js"></script>
<script src="<?= BASE_URL ?>/assets/js/video-streaming.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

