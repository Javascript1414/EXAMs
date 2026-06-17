<?php
/**
 * Course Timeline & Progress Visualization
 * Visual representation of learning journey
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'Course Timeline';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">
                <i data-lucide="trending-up" class="me-2"></i>
                Course Timeline & Progress
            </h1>
            <p class="text-muted">Your complete learning journey at a glance</p>
        </div>
    </div>

    <!-- Learning Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="mb-1" id="totalCourses">0</h3>
                    <p class="text-muted small mb-0">Total Courses</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="mb-1" id="completedCourses">0</h3>
                    <p class="text-muted small mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="mb-1" id="inProgressCourses">0</h3>
                    <p class="text-muted small mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="mb-1" id="learningStreak">0</h3>
                    <p class="text-muted small mb-0">Day Streak 🔥</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-lucide="calendar" class="me-2"></i>
                        Learning Timeline
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="filterTimeline('all')">All</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="filterTimeline('month')">Month</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="filterTimeline('week')">Week</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="timelineContainer" class="position-relative" style="padding-left: 30px;">
                        <!-- Timeline items will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Progress Bars -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="book-open" class="me-2"></i>
                        Course Progress
                    </h5>
                </div>
                <div class="card-body">
                    <div id="courseProgressContainer">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Skill Chart -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="star" class="me-2"></i>
                        Skill Levels
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="skillChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i data-lucide="award" class="me-2"></i>
                        Achievements
                    </h5>
                </div>
                <div class="card-body" id="achievementsContainer">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-item {
        position: relative;
        padding: 20px 0;
        padding-left: 40px;
        border-left: 2px solid #e9ecef;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 20px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid #5865f2;
    }

    .timeline-item.completed::before {
        background: #43b581;
        border-color: #43b581;
    }

    body[data-theme="dark"] .timeline-item {
        border-left-color: #3a3f47;
    }

    .skill-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        background: #f0f0f0;
        font-size: 0.85rem;
        margin-right: 8px;
        margin-bottom: 8px;
    }

    body[data-theme="dark"] .skill-badge {
        background: #3a3f47;
    }

    .achievement {
        text-align: center;
        padding: 15px;
        border-radius: 8px;
        background: #f8f9fa;
        margin-bottom: 10px;
    }

    body[data-theme="dark"] .achievement {
        background: #2a2f37;
    }

    .achievement-icon {
        font-size: 32px;
        margin-bottom: 8px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="/assets/js/course-timeline.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
