console.log("dashboard_scripts.js loaded");

// Fetch and render dashboard based on faculty role
document.addEventListener('DOMContentLoaded', async function() {
    await loadDashboard();
    initializeRoleBasedSidebarVisibility();
});

async function loadDashboard() {
    const loadingContainer = document.getElementById('dashboardLoadingContainer');
    const chartsContainer = document.getElementById('chartsContainer');
    
    try {
        const response = await fetch('fetch_dashboard_stats.php');
        const responseText = await response.text();
        
        console.log('Response status:', response.status);
        console.log('Response text:', responseText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Raw response:', responseText.substring(0, 500));
            throw new Error(`Failed to parse response: ${parseError.message}`);
        }
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Render the appropriate dashboard
        renderDashboard(data);
        
        // Hide loading and show charts
        loadingContainer.style.display = 'none';
        chartsContainer.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading dashboard:', error);
        loadingContainer.innerHTML = `<p class="text-red-500">Error loading dashboard: ${error.message}</p>`;
    }
}

function renderDashboard(payload) {
    const chartsContainer = document.getElementById('chartsContainer');
    chartsContainer.innerHTML = '';

    const dashboardData = payload && payload.dashboard ? payload.dashboard : null;
    chartsContainer.dataset.dashboardRole = dashboardData && dashboardData.type ? dashboardData.type : '';

    if (!dashboardData || !dashboardData.data) {
        chartsContainer.innerHTML = '<p>No data available</p>';
        return;
    }

    const title = dashboardData.title || 'Dashboard';
    const subtitle = dashboardData.subtitle || '';
    const professorDashboard = payload && payload.professor_dashboard ? payload.professor_dashboard : null;

    if ((dashboardData.chart_type === 'vertical_bar' || dashboardData.chart_type === 'horizontal_bar') && professorDashboard) {
        renderDashboardCarousel(chartsContainer, dashboardData, professorDashboard, title, subtitle);
        return;
    }

    if (dashboardData.chart_type === 'vertical_bar') {
        renderVerticalBarChart(chartsContainer, dashboardData, title, subtitle);
    } else if (dashboardData.chart_type === 'horizontal_bar') {
        renderHorizontalBarChart(chartsContainer, dashboardData, title, subtitle);
    } else if (dashboardData.chart_type === 'professor_summary') {
        renderProfessorDashboard(chartsContainer, dashboardData, title, subtitle);
    }
}

function renderVerticalBarChart(container, dashboardData, title, subtitle) {
    container.innerHTML = getVerticalBarChartHtml(dashboardData, title, subtitle);
}

function renderHorizontalBarChart(container, dashboardData, title, subtitle) {
    container.innerHTML = getHorizontalBarChartHtml(dashboardData, title, subtitle);
}

function getMaxValue(data) {
    return Math.max(...data.map(item => item.value || 0), 1);
}

function getVerticalBarChartHtml(dashboardData, title, subtitle) {
    return `
        <div class="dashboard-chart vertical-bar-chart">
            <h3 class="chart-title">${title}</h3>
            <p class="chart-subtitle">${subtitle}</p>
            <div class="chart-wrapper">
                <svg class="bar-chart-svg" viewBox="0 0 800 400" preserveAspectRatio="xMidYMid meet">
                    <!-- Y-axis -->
                    <line x1="60" y1="20" x2="60" y2="350" stroke="#333" stroke-width="2"/>
                    <!-- X-axis -->
                    <line x1="60" y1="350" x2="750" y2="350" stroke="#333" stroke-width="2"/>
                </svg>
                <div class="chart-bars-container">
                    ${dashboardData.data.map((item, index) => `
                        <div class="bar-group" style="--bar-index: ${index}">
                            <div class="bar-wrapper">
                                <div class="bar" style="height: ${(item.value / getMaxValue(dashboardData.data)) * 280}px" title="${item.label}: ${item.value}">
                                    <span class="bar-value">${item.value}</span>
                                </div>
                            </div>
                            <div class="bar-label">${item.label}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="chart-stats">
                <p><strong>Total Submissions:</strong> ${dashboardData.data.reduce((sum, item) => sum + item.value, 0)}</p>
                <p><strong>Programs:</strong> ${dashboardData.data.length}</p>
            </div>
        </div>
    `;
}

function getHorizontalBarChartHtml(dashboardData, title, subtitle) {
    const maxValue = getMaxValue(dashboardData.data);
    return `
        <div class="dashboard-chart horizontal-bar-chart">
            <h3 class="chart-title">${title}</h3>
            <p class="chart-subtitle">${subtitle}</p>
            <div class="chart-wrapper horizontal">
                <div class="chart-bars-container horizontal">
                    ${dashboardData.data.map((item, index) => `
                        <div class="bar-item horizontal">
                            <div class="bar-label-horizontal">${item.label}</div>
                            <div class="bar-wrapper horizontal">
                                <div class="bar horizontal" style="width: ${(item.value / maxValue) * 600}px" title="${item.label}: ${item.value} submissions">
                                    <span class="bar-value horizontal">${item.value}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="chart-stats">
                <p><strong>Total Submissions:</strong> ${dashboardData.data.reduce((sum, item) => sum + item.value, 0)}</p>
                ${dashboardData.metadata && dashboardData.metadata.inactive_count ? 
                    `<p><strong>Inactive Students:</strong> ${dashboardData.metadata.inactive_count}</p>` 
                    : ''
                }
            </div>
        </div>
    `;
}

function renderProfessorDashboard(container, dashboardData, title, subtitle) {
    container.innerHTML = getProfessorDashboardHtml(dashboardData, title, subtitle);
}

function getProfessorDashboardHtml(dashboardData, title, subtitle) {
    const metrics = dashboardData.data && dashboardData.data.metrics ? dashboardData.data.metrics : {};
    const classes = dashboardData.data && Array.isArray(dashboardData.data.classes) ? dashboardData.data.classes : [];
    const pending = Number(metrics.pending_reviews || 0);
    const graded = Number(metrics.total_graded || 0);

    const rows = classes.length === 0
        ? '<tr><td colspan="3" class="prof-empty-row">No active classes assigned yet.</td></tr>'
        : classes.map((entry) => {
            const className = escapeDashboardHtml(entry.class_name || 'Untitled class');
            const submitted = Number(entry.submitted_count || 0);
            const missing = Number(entry.missing_count || 0);
            return `
                <tr>
                    <td>${className}</td>
                    <td>${submitted}</td>
                    <td>${missing}</td>
                </tr>
            `;
        }).join('');

    return `
        <div class="dashboard-chart professor-dashboard">
            <h3 class="chart-title">${title}</h3>
            <p class="chart-subtitle">${subtitle}</p>
            <div class="prof-metric-cards">
                <div class="prof-card prof-card--danger">
                    <div class="prof-card-label">Outputs Pending Review</div>
                    <div class="prof-card-value">${pending}</div>
                </div>
                <div class="prof-card prof-card--success">
                    <div class="prof-card-label">Total Graded This Term</div>
                    <div class="prof-card-value">${graded}</div>
                </div>
            </div>
            <div class="prof-class-table-wrap">
                <table class="prof-class-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Submitted</th>
                            <th>Missing</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function renderDashboardCarousel(container, primaryDashboard, professorDashboard, primaryTitle, primarySubtitle) {
    const primaryHtml = primaryDashboard.chart_type === 'vertical_bar'
        ? getVerticalBarChartHtml(primaryDashboard, primaryTitle, primarySubtitle)
        : getHorizontalBarChartHtml(primaryDashboard, primaryTitle, primarySubtitle);

    const professorTitle = professorDashboard.title || 'Professor Overview';
    const professorSubtitle = professorDashboard.subtitle || 'Your grading workload and active classes';
    const professorHtml = getProfessorDashboardHtml(professorDashboard, professorTitle, professorSubtitle);

    container.innerHTML = `
        <div class="dashboard-carousel" data-slide="0">
            <button type="button" class="dashboard-arrow dashboard-arrow-left" aria-label="Previous dashboard">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="dashboard-carousel-track">
                <div class="dashboard-carousel-slide is-active">
                    ${primaryHtml}
                </div>
                <div class="dashboard-carousel-slide">
                    ${professorHtml}
                </div>
            </div>
            <button type="button" class="dashboard-arrow dashboard-arrow-right" aria-label="Next dashboard">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    `;

    bindDashboardCarousel(container);
}

function bindDashboardCarousel(container) {
    const carousel = container.querySelector('.dashboard-carousel');
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll('.dashboard-carousel-slide'));
    const prevBtn = carousel.querySelector('.dashboard-arrow-left');
    const nextBtn = carousel.querySelector('.dashboard-arrow-right');

    if (slides.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        return;
    }

    const setActiveSlide = (index) => {
        slides.forEach((slide, idx) => {
            slide.classList.toggle('is-active', idx === index);
        });
        if (prevBtn) prevBtn.disabled = index === 0;
        if (nextBtn) nextBtn.disabled = index === slides.length - 1;
        carousel.dataset.slide = String(index);
    };

    setActiveSlide(0);

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const current = Number(carousel.dataset.slide || 0);
            if (current > 0) setActiveSlide(current - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const current = Number(carousel.dataset.slide || 0);
            if (current < slides.length - 1) setActiveSlide(current + 1);
        });
    }
}

function escapeDashboardHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Hide "Lists of Students" link for professors
function initializeRoleBasedSidebarVisibility() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    const chartsContainer = document.getElementById('chartsContainer');
    const dashboardRole = chartsContainer ? chartsContainer.dataset.dashboardRole : '';
    const dashboardTitle = chartsContainer ? chartsContainer.querySelector('.chart-title') : null;
    const isProfessor = dashboardRole === 'professor' || (dashboardTitle && dashboardTitle.textContent.includes('My Classes'));
    if (!chartsContainer) return;
    
    // Find the "Lists of Students" link
    const listsLink = Array.from(sidebar.querySelectorAll('a')).find(link => 
        link.textContent.includes('Lists of Students')
    );
    
    if (listsLink && isProfessor) {
        listsLink.style.display = 'none';
    }
}

// Call the role-based sidebar visibility on page load and orientation change
window.addEventListener('load', function() {
    initializeRoleBasedSidebarVisibility();
});
