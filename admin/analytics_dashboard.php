<?php
/**
 * Advanced Analytics Dashboard
 * Comprehensive metrics and visualizations for admin panel
 * 
 * Displays:
 * - Student performance metrics
 * - Exam completion rates
 * - Course enrollment trends
 * - Question difficulty analysis
 * - Material engagement metrics
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
if (!hasRole('superadmin') && !hasRole('admin')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

$page_title = 'Analytics Dashboard';
require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i data-lucide="bar-chart-3" class="me-2"></i>
                    Advanced Analytics Dashboard
                </h1>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshCharts()">
                        <i data-lucide="refresh-cw" class="me-1"></i> Refresh
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportAnalytics()">
                        <i data-lucide="download" class="me-1"></i> Export
                    </button>
                </div>
            </div>
            <p class="text-muted mt-2">Real-time system metrics and performance insights</p>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2 small">Total Students</p>
                            <h3 class="mb-0" id="stat-students">0</h3>
                        </div>
                        <div class="stats-icon bg-primary">
                            <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                    <small class="text-success" id="stat-students-change">+0% from last month</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2 small">Active Exams</p>
                            <h3 class="mb-0" id="stat-exams">0</h3>
                        </div>
                        <div class="stats-icon bg-success">
                            <i data-lucide="clipboard-list" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                    <small class="text-info" id="stat-exams-ongoing">0 currently running</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2 small">Avg Performance</p>
                            <h3 class="mb-0" id="stat-avg-score">0%</h3>
                        </div>
                        <div class="stats-icon bg-info">
                            <i data-lucide="trending-up" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                    <small class="text-warning" id="stat-performance-trend">Trend data loading...</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-2 small">System Health</p>
                            <h3 class="mb-0" id="stat-health">98%</h3>
                        </div>
                        <div class="stats-icon bg-warning">
                            <i data-lucide="heart-pulse" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                    <small class="text-success" id="stat-health-status">All systems operational</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Exam Completion Rate -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="check-circle-2" class="me-2"></i>
                        Exam Completion Rate
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="completionChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Student Performance Distribution -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="distribution" class="me-2"></i>
                        Performance Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Course Enrollment Trends -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="line-chart" class="me-2"></i>
                        Monthly Activity Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Question Difficulty -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="brain" class="me-2"></i>
                        Question Difficulty Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="difficultyChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Material Ratings -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="star" class="me-2"></i>
                        Material Ratings Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ratingsChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Performing Courses -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="award" class="me-2"></i>
                        Top Performing Courses
                    </h5>
                </div>
                <div class="card-body">
                    <div id="topCoursesContainer">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Metrics Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-lucide="table-2" class="me-2"></i>
                        Detailed Exam Metrics
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleTableView()">
                            <i data-lucide="eye" class="me-1"></i> Toggle Details
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover mb-0" id="metricsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Exam Name</th>
                                <th>Total Students</th>
                                <th>Completed</th>
                                <th>Completion %</th>
                                <th>Avg Score</th>
                                <th>Pass Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="metricsTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter (Optional) -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom py-3">
                    <h5 class="mb-0">
                        <i data-lucide="calendar" class="me-2"></i>
                        Custom Date Range
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" class="form-control" id="dateFrom" onchange="updateDateRange()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" class="form-control" id="dateTo" onchange="updateDateRange()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Quick Range</label>
                            <select class="form-select" onchange="setQuickRange(this.value)">
                                <option value="">Select range...</option>
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="refreshCharts()">
                                <i data-lucide="filter" class="me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stats-card {
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .stats-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .card {
        transition: all 0.3s ease;
    }

    body[data-theme="dark"] .card {
        background-color: #252a33;
        border-color: #3a3f47 !important;
    }

    body[data-theme="dark"] .table-light {
        background-color: #1a1d23;
    }

    body[data-theme="dark"] .table-hover tbody tr:hover {
        background-color: #2a2f37;
    }

    canvas {
        max-height: 350px;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="/assets/js/analytics.js"></script>

<?php require_once '../includes/footer.php'; ?>
