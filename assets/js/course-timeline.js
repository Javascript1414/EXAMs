/**
 * Course Timeline JavaScript
 * Displays learning journey with charts and timeline
 */

let allTimelineData = [];
let skillChart = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCourseStats();
    loadTimeline();
    loadCourseProgress();
    loadAchievements();
    initSkillChart();
});

async function loadCourseStats() {
    try {
        const response = await fetch('/api/timeline/get_stats.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalCourses').textContent = data.total_courses;
            document.getElementById('completedCourses').textContent = data.completed_courses;
            document.getElementById('inProgressCourses').textContent = data.in_progress_courses;
            document.getElementById('learningStreak').textContent = data.learning_streak;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadTimeline() {
    try {
        const response = await fetch('/api/timeline/get_timeline.php');
        const data = await response.json();
        
        if (data.success) {
            allTimelineData = data.timeline;
            displayTimeline(allTimelineData);
        }
    } catch (error) {
        console.error('Error loading timeline:', error);
    }
}

function displayTimeline(timeline) {
    const container = document.getElementById('timelineContainer');
    container.innerHTML = '';
    
    if (timeline.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No timeline data yet. Start learning!</p>';
        return;
    }
    
    timeline.forEach((item, idx) => {
        const div = document.createElement('div');
        div.className = `timeline-item ${item.status === 'completed' ? 'completed' : ''}`;
        
        const date = new Date(item.date);
        const dateStr = date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        
        let icon = 'book';
        if (item.event_type === 'exam') icon = 'file-text';
        if (item.event_type === 'material') icon = 'book-open';
        if (item.event_type === 'achievement') icon = 'award';
        
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${item.title}</h6>
                    <small class="text-muted d-block">${item.description}</small>
                    ${item.score ? `<small class="text-muted d-block">Score: ${item.score}%</small>` : ''}
                </div>
                <small class="text-muted text-nowrap">${dateStr}</small>
            </div>
        `;
        
        container.appendChild(div);
    });
}

function filterTimeline(period) {
    let filtered = allTimelineData;
    const now = new Date();
    
    if (period === 'month') {
        const monthAgo = new Date(now.setMonth(now.getMonth() - 1));
        filtered = allTimelineData.filter(item => new Date(item.date) >= monthAgo);
    } else if (period === 'week') {
        const weekAgo = new Date(now.setDate(now.getDate() - 7));
        filtered = allTimelineData.filter(item => new Date(item.date) >= weekAgo);
    }
    
    displayTimeline(filtered);
}

async function loadCourseProgress() {
    try {
        const response = await fetch('/api/timeline/get_course_progress.php');
        const data = await response.json();
        
        if (data.success && data.courses) {
            displayCourseProgress(data.courses);
        }
    } catch (error) {
        console.error('Error loading course progress:', error);
    }
}

function displayCourseProgress(courses) {
    const container = document.getElementById('courseProgressContainer');
    container.innerHTML = '';
    
    if (courses.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No courses yet</p>';
        return;
    }
    
    courses.forEach(course => {
        const progress = course.progress || 0;
        const statusColor = progress >= 80 ? 'success' : progress >= 50 ? 'info' : 'warning';
        
        const div = document.createElement('div');
        div.className = 'mb-4';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h6 class="mb-0">${course.name}</h6>
                    <small class="text-muted">${course.completed_lessons}/${course.total_lessons} lessons</small>
                </div>
                <strong>${progress}%</strong>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-${statusColor}" style="width: ${progress}%"></div>
            </div>
        `;
        
        container.appendChild(div);
    });
}

async function loadAchievements() {
    try {
        const response = await fetch('/api/timeline/get_achievements.php');
        const data = await response.json();
        
        if (data.success && data.achievements) {
            displayAchievements(data.achievements);
        }
    } catch (error) {
        console.error('Error loading achievements:', error);
    }
}

function displayAchievements(achievements) {
    const container = document.getElementById('achievementsContainer');
    container.innerHTML = '';
    
    if (achievements.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No achievements yet. Keep learning!</p>';
        return;
    }
    
    achievements.forEach(achievement => {
        const div = document.createElement('div');
        div.className = 'achievement';
        div.innerHTML = `
            <div class="achievement-icon">${achievement.icon}</div>
            <small class="font-weight-bold">${achievement.title}</small>
            <p class="text-muted small mb-0">${achievement.description}</p>
        `;
        container.appendChild(div);
    });
}

function initSkillChart() {
    // Load skill data
    fetch('/api/timeline/get_skills.php')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.skills) {
                createSkillChart(data.skills);
            }
        });
}

function createSkillChart(skills) {
    const ctx = document.getElementById('skillChart').getContext('2d');
    
    const labels = skills.map(s => s.name);
    const values = skills.map(s => s.level);
    
    skillChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Your Skills',
                data: values,
                borderColor: 'rgba(88, 101, 242, 1)',
                backgroundColor: 'rgba(88, 101, 242, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(88, 101, 242, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                r: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Initialize icons
lucide.createIcons();
