<?php
/**
 * AI Recommendations Engine
 * Intelligent learning recommendations based on student performance
 * Uses collaborative filtering + content-based recommendations
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$page_title = 'AI Recommendations';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">
                <i data-lucide="sparkles" class="me-2"></i>
                AI-Powered Learning Recommendations
            </h1>
            <p class="text-muted">Personalized recommendations based on your learning patterns</p>
        </div>
    </div>

    <!-- Recommendation Categories -->
    <div class="row mb-4">
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i data-lucide="zap" style="width: 40px; height: 40px; color: #faa61a; margin-bottom: 10px;"></i>
                    <h5 class="card-title">Weak Topics</h5>
                    <p class="text-muted small mb-0">Based on your exam performance</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i data-lucide="trending-up" style="width: 40px; height: 40px; color: #43b581; margin-bottom: 10px;"></i>
                    <h5 class="card-title">Next Steps</h5>
                    <p class="text-muted small mb-0">Recommended learning path</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i data-lucide="book-open" style="width: 40px; height: 40px; color: #5865f2; margin-bottom: 10px;"></i>
                    <h5 class="card-title">Similar Materials</h5>
                    <p class="text-muted small mb-0">From other students</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 mb-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <i data-lucide="users" style="width: 40px; height: 40px; color: #00b0f4; margin-bottom: 10px;"></i>
                    <h5 class="card-title">Study Groups</h5>
                    <p class="text-muted small mb-0">Connect with similar learners</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="weak-topics-tab" data-bs-toggle="tab" data-bs-target="#weak-topics">
                <i data-lucide="alert-circle" class="me-2"></i> Weak Topics
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="next-steps-tab" data-bs-toggle="tab" data-bs-target="#next-steps">
                <i data-lucide="arrow-right" class="me-2"></i> Recommended Path
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials">
                <i data-lucide="book" class="me-2"></i> Materials
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="peers-tab" data-bs-toggle="tab" data-bs-target="#peers">
                <i data-lucide="users" class="me-2"></i> Study Groups
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Weak Topics -->
        <div class="tab-pane fade show active" id="weak-topics" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="weakTopicsContainer" class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommended Path -->
        <div class="tab-pane fade" id="next-steps" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="nextStepsContainer" class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommended Materials -->
        <div class="tab-pane fade" id="materials" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="materialsContainer" class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Study Groups -->
        <div class="tab-pane fade" id="peers" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div id="peersContainer" class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Statistics -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Your Learning Score</h5>
                </div>
                <div class="card-body text-center">
                    <div class="learning-score-chart">
                        <svg width="150" height="150" viewBox="0 0 150 150">
                            <circle cx="75" cy="75" r="70" fill="none" stroke="#e9ecef" stroke-width="4"/>
                            <circle cx="75" cy="75" r="70" fill="none" stroke="#5865f2" stroke-width="4" 
                                    stroke-dasharray="220 220" stroke-dashoffset="0" opacity="0.7"/>
                            <text x="75" y="75" text-anchor="middle" dy="0.3em" font-size="32" font-weight="bold" fill="#5865f2">
                                <span id="learningScore">0</span>%
                            </text>
                        </svg>
                    </div>
                    <p class="text-muted mt-3 mb-0">Based on completed exams & materials</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Recommended Next Actions</h5>
                </div>
                <div class="card-body">
                    <div id="nextActionsContainer">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link {
        color: #666;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
    }
    
    .nav-tabs .nav-link.active {
        color: #5865f2;
        border-bottom-color: #5865f2;
    }
    
    .nav-tabs .nav-link:hover {
        color: #5865f2;
    }
    
    body[data-theme="dark"] .nav-tabs .nav-link {
        color: #aaa;
    }
    
    body[data-theme="dark"] .nav-tabs .nav-link.active {
        color: #88b2ff;
    }
    
    .learning-score-chart {
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>

<script src="/assets/js/ai-recommendations.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
