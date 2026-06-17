/**
 * Analytics Dashboard JavaScript
 * Handles data fetching, chart rendering, and user interactions
 */

let charts = {};
let dataCache = {};

/**
 * Initialize analytics dashboard on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadQuickStats();
    lucide.createIcons();
});

/**
 * Initialize all charts
 */
function initializeCharts() {
    const chartConfig = [
        {
            id: 'completionChart',
            type: 'bar',
            action: 'get_completion_data'
        },
        {
            id: 'performanceChart',
            type: 'doughnut',
            action: 'get_performance_data'
        },
        {
            id: 'trendsChart',
            type: 'line',
            action: 'get_trends_data'
        },
        {
            id: 'difficultyChart',
            type: 'pie',
            action: 'get_difficulty_data'
        },
        {
            id: 'ratingsChart',
            type: 'doughnut',
            action: 'get_ratings_data'
        }
    ];

    chartConfig.forEach(config => {
        if (document.getElementById(config.id)) {
            const ctx = document.getElementById(config.id).getContext('2d');
            charts[config.id] = new Chart(ctx, {
                type: config.type,
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: []
                    }]
                },
                options: getChartOptions(config.type)
            });
        }
    });
}

/**
 * Get chart options based on chart type
 */
function getChartOptions(type) {
    const isDark = document.body.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#e4e6eb' : '#333333';
    const gridColor = isDark ? '#3a3f47' : '#e9ecef';

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: {
                    color: textColor,
                    font: {
                        size: 12,
                        family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                    },
                    padding: 15,
                    usePointStyle: true
                }
            }
        }
    };

    if (type === 'line') {
        return {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    },
                    ticks: {
                        color: textColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: textColor
                    }
                }
            }
        };
    } else if (type === 'bar') {
        return {
            ...commonOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: gridColor,
                        drawBorder: false
                    },
                    ticks: {
                        color: textColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: textColor
                    }
                }
            }
        };
    } else {
        return {
            ...commonOptions,
            plugins: {
                ...commonOptions.plugins,
                legend: {
                    ...commonOptions.plugins.legend,
                    position: 'right'
                }
            }
        };
    }
}

/**
 * Load quick statistics
 */
async function loadQuickStats() {
    try {
        const response = await fetch('/api/analytics/get_stats.php?action=get_stats');
        const data = await response.json();

        if (data.success) {
            document.getElementById('stat-students').textContent = data.total_students;
            document.getElementById('stat-exams').textContent = data.total_exams;
            document.getElementById('stat-avg-score').textContent = data.avg_score + '%';
            document.getElementById('stat-health').textContent = data.system_health + '%';
            document.getElementById('stat-exams-ongoing').textContent = data.ongoing_attempts + ' currently running';
        }
    } catch (error) {
        console.error('Error loading quick stats:', error);
    }
}

/**
 * Refresh all charts with current date range
 */
async function refreshCharts() {
    showLoadingSpinner();

    const dateFrom = document.getElementById('dateFrom').value || getDefaultDateFrom();
    const dateTo = document.getElementById('dateTo').value || new Date().toISOString().split('T')[0];

    try {
        // Load all chart data in parallel
        const [completion, performance, trends, difficulty, ratings, metrics, topCourses] = await Promise.all([
            fetchChartData('get_completion_data', dateFrom, dateTo),
            fetchChartData('get_performance_data', dateFrom, dateTo),
            fetchChartData('get_trends_data', dateFrom, dateTo),
            fetchChartData('get_difficulty_data', dateFrom, dateTo),
            fetchChartData('get_ratings_data', dateFrom, dateTo),
            fetchChartData('get_exam_metrics', dateFrom, dateTo),
            fetchChartData('get_top_courses', dateFrom, dateTo)
        ]);

        // Update charts
        updateChart('completionChart', 'bar', completion);
        updateChart('performanceChart', 'doughnut', performance);
        updateChart('trendsChart', 'line', trends);
        updateChart('difficultyChart', 'pie', difficulty);
        updateChart('ratingsChart', 'doughnut', ratings);

        // Update table
        if (metrics.success) {
            updateMetricsTable(metrics.data);
        }

        // Update top courses
        if (topCourses.success) {
            updateTopCourses(topCourses.data);
        }

        hideLoadingSpinner();
        showNotification('Charts updated successfully', 'success');
    } catch (error) {
        console.error('Error refreshing charts:', error);
        hideLoadingSpinner();
        showNotification('Error loading analytics data', 'danger');
    }
}

/**
 * Fetch chart data from API
 */
async function fetchChartData(action, dateFrom, dateTo) {
    const url = `/api/analytics/get_stats.php?action=${action}&date_from=${dateFrom}&date_to=${dateTo}`;
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
}

/**
 * Update chart with new data
 */
function updateChart(chartId, type, data) {
    if (!charts[chartId]) return;

    const chart = charts[chartId];

    if (type === 'line') {
        chart.data.labels = data.labels || [];
        chart.data.datasets = data.datasets || [];
    } else if (type === 'bar') {
        chart.data.labels = data.labels || [];
        chart.data.datasets = [{
            label: 'Completion Rate (%)',
            data: data.data || [],
            backgroundColor: data.colors || [],
            borderColor: 'rgba(0, 0, 0, 0.1)',
            borderWidth: 1,
            borderRadius: 8
        }];
    } else {
        chart.data.labels = data.labels || [];
        chart.data.datasets = [{
            data: data.data || [],
            backgroundColor: data.colors || [],
            borderColor: 'rgba(255, 255, 255, 0.3)',
            borderWidth: 2
        }];
    }

    chart.update('none');
}

/**
 * Update metrics table
 */
function updateMetricsTable(metrics) {
    const tbody = document.getElementById('metricsTableBody');
    tbody.innerHTML = '';

    if (!metrics || metrics.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    No exam data available for selected period
                </td>
            </tr>
        `;
        return;
    }

    metrics.forEach(exam => {
        const statusBadge = getStatusBadge(exam.status);
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${exam.exam_name}</strong></td>
            <td><span class="badge bg-info">${exam.total_students}</span></td>
            <td><span class="badge bg-success">${exam.completed || 0}</span></td>
            <td>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar" style="width: ${exam.completion_percentage || 0}%">
                        ${exam.completion_percentage || 0}%
                    </div>
                </div>
            </td>
            <td><strong>${exam.avg_score || 0}</strong></td>
            <td><span class="badge bg-warning">${exam.pass_rate || 0}%</span></td>
            <td>${statusBadge}</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Update top courses display
 */
function updateTopCourses(courses) {
    const container = document.getElementById('topCoursesContainer');
    container.innerHTML = '';

    if (!courses || courses.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-4">No course data available</div>';
        return;
    }

    let html = '<div class="list-group">';
    courses.forEach((course, index) => {
        const medal = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'][index];
        html += `
            <div class="list-group-item border-0 py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${medal} ${course.course_name}</h6>
                        <small class="text-muted">
                            ${course.enrollments} enrollments • Avg: ${course.avg_score}% • Pass: ${course.pass_rate}%
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="progress" style="width: 100px; height: 8px;">
                            <div class="progress-bar bg-success" style="width: ${course.pass_rate || 0}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Get status badge HTML
 */
function getStatusBadge(status) {
    const badges = {
        'Active': '<span class="badge bg-success">Active</span>',
        'Completed': '<span class="badge bg-secondary">Completed</span>',
        'Upcoming': '<span class="badge bg-warning">Upcoming</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Update date range
 */
function updateDateRange() {
    refreshCharts();
}

/**
 * Set quick date range
 */
function setQuickRange(days) {
    if (!days) return;

    const today = new Date();
    const from = new Date(today.setDate(today.getDate() - days));

    document.getElementById('dateFrom').value = from.toISOString().split('T')[0];
    document.getElementById('dateTo').value = new Date().toISOString().split('T')[0];

    refreshCharts();
}

/**
 * Get default date from (30 days ago)
 */
function getDefaultDateFrom() {
    const date = new Date();
    date.setDate(date.getDate() - 30);
    return date.toISOString().split('T')[0];
}

/**
 * Export analytics data
 */
function exportAnalytics() {
    const dateFrom = document.getElementById('dateFrom').value || getDefaultDateFrom();
    const dateTo = document.getElementById('dateTo').value || new Date().toISOString().split('T')[0];

    // Create CSV content
    let csv = 'Analytics Report\n';
    csv += `Generated: ${new Date().toLocaleString()}\n`;
    csv += `Date Range: ${dateFrom} to ${dateTo}\n\n`;

    // Add metrics table data
    const table = document.getElementById('metricsTable');
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td, th');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim()).join(',');
        csv += rowData + '\n';
    });

    // Create download link
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `analytics_${new Date().getTime()}.csv`;
    link.click();

    showNotification('Analytics exported successfully', 'success');
}

/**
 * Toggle detailed table view
 */
function toggleTableView() {
    const table = document.getElementById('metricsTable');
    const tbody = table.querySelector('tbody');

    if (tbody.style.display === 'none') {
        tbody.style.display = 'table-row-group';
    } else {
        tbody.style.display = 'none';
    }
}

/**
 * Show loading spinner
 */
function showLoadingSpinner() {
    // Could add a global spinner here
    console.log('Loading...');
}

/**
 * Hide loading spinner
 */
function hideLoadingSpinner() {
    console.log('Loading complete');
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    // Create toast notification
    const alertClass = `alert-${type}`;
    const toast = document.createElement('div');
    toast.className = `alert ${alertClass} alert-dismissible fade show`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.remove();
    }, 4000);
}

/**
 * Listen for theme changes and update charts
 */
window.addEventListener('themechange', function(e) {
    // Update all charts with new color scheme
    Object.keys(charts).forEach(chartId => {
        if (charts[chartId]) {
            charts[chartId].options = getChartOptions(charts[chartId].config.type);
            charts[chartId].update();
        }
    });
});

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshCharts);
} else {
    refreshCharts();
}
