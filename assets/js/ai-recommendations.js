/**
 * AI Recommendations Engine - JavaScript
 * Fetches and displays personalized learning recommendations
 */

document.addEventListener('DOMContentLoaded', function() {
    loadAllRecommendations();
});

async function loadAllRecommendations() {
    try {
        const [weakTopics, nextSteps, materials, peers, stats] = await Promise.all([
            fetch('/api/ai/weak_topics.php').then(r => r.json()),
            fetch('/api/ai/next_steps.php').then(r => r.json()),
            fetch('/api/ai/recommended_materials.php').then(r => r.json()),
            fetch('/api/ai/study_groups.php').then(r => r.json()),
            fetch('/api/ai/learning_stats.php').then(r => r.json())
        ]);

        displayWeakTopics(weakTopics);
        displayNextSteps(nextSteps);
        displayMaterials(materials);
        displayPeers(peers);
        displayStats(stats);
    } catch (error) {
        console.error('Error loading recommendations:', error);
    }
}

function displayWeakTopics(data) {
    const container = document.getElementById('weakTopicsContainer');
    
    if (!data.success || !data.topics || data.topics.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Keep learning to get topic recommendations!</p>';
        return;
    }

    let html = '<div class="list-group">';
    data.topics.forEach((topic, idx) => {
        const severity = topic.accuracy < 40 ? 'danger' : topic.accuracy < 60 ? 'warning' : 'info';
        html += `
            <div class="list-group-item py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${idx + 1}. <strong>${topic.topic_name}</strong></h6>
                        <small class="text-muted">Your accuracy: ${topic.accuracy}% | Attempts: ${topic.attempts}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="startPractice('${topic.question_id}')">
                        Practice Now
                    </button>
                </div>
                <div class="progress mt-2" style="height: 8px;">
                    <div class="progress-bar bg-${severity}" style="width: ${topic.accuracy}%"></div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function displayNextSteps(data) {
    const container = document.getElementById('nextStepsContainer');
    
    if (!data.success || !data.steps || data.steps.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">Continue with your current course!</p>';
        return;
    }

    let html = '<div class="list-group">';
    data.steps.forEach((step, idx) => {
        html += `
            <div class="list-group-item py-3">
                <div class="d-flex">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 40px; height: 40px; font-weight: bold;">
                            ${idx + 1}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${step.course_name} - ${step.subject_name}</h6>
                        <small class="text-muted">${step.description}</small>
                        <div class="mt-2">
                            <small class="badge bg-info">${step.difficulty}</small>
                            <small class="badge bg-secondary">${step.questions_count} Questions</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function displayMaterials(data) {
    const container = document.getElementById('materialsContainer');
    
    if (!data.success || !data.materials || data.materials.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No materials recommended yet!</p>';
        return;
    }

    let html = '<div class="row">';
    data.materials.forEach(mat => {
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card border-0 h-100">
                    <div class="card-body">
                        <h6 class="card-title">${mat.title}</h6>
                        <p class="card-text small text-muted">${mat.description}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="badge bg-warning text-dark">${mat.type}</small>
                            <small class="text-muted">${mat.rating} ⭐</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top">
                        <a href="/student/materials/${mat.material_id}" class="btn btn-sm btn-outline-primary w-100">
                            View Material
                        </a>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function displayPeers(data) {
    const container = document.getElementById('peersContainer');
    
    if (!data.success || !data.groups || data.groups.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-4">No study groups available!</p>';
        return;
    }

    let html = '<div class="row">';
    data.groups.forEach(group => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card border-0">
                    <div class="card-body">
                        <h6 class="card-title">${group.group_name}</h6>
                        <p class="card-text small text-muted">${group.description}</p>
                        <div class="mb-2">
                            <small class="badge bg-success">${group.member_count} Members</small>
                            <small class="badge bg-info">${group.similarity}% Match</small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button class="btn btn-sm btn-outline-primary w-100" onclick="joinGroup(${group.group_id})">
                            Join Study Group
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function displayStats(data) {
    const container = document.getElementById('nextActionsContainer');
    const scoreElement = document.getElementById('learningScore');
    
    if (!data.success) {
        container.innerHTML = '<p class="text-muted">No data available</p>';
        return;
    }

    scoreElement.textContent = Math.round(data.learning_score);

    let html = '<div class="list-unstyled">';
    if (data.actions && data.actions.length > 0) {
        data.actions.forEach(action => {
            html += `
                <div class="mb-3">
                    <div class="d-flex align-items-start">
                        <i data-lucide="${action.icon}" style="width: 20px; height: 20px; color: #5865f2; margin-right: 10px; flex-shrink: 0; margin-top: 2px;"></i>
                        <div>
                            <strong>${action.title}</strong>
                            <p class="text-muted small mb-0">${action.description}</p>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    html += '</div>';
    container.innerHTML = html;
    
    lucide.createIcons();
}

function startPractice(questionId) {
    window.location.href = `/student/practice.php?question_id=${questionId}`;
}

function joinGroup(groupId) {
    fetch('/api/ai/join_study_group.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ group_id: groupId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Successfully joined study group!');
            loadAllRecommendations();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Reinitialize icons when tabs change
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function() {
        setTimeout(() => lucide.createIcons(), 100);
    });
});
