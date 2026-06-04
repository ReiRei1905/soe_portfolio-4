console.log("dashboard_scripts.js loaded");

// Global state for difficulty filtering
window.__difficultyState = {
    fullData: [],
    programFilter: 'all',
    searchQuery: '',
    role: ''
};

// Fetch and render dashboard based on faculty role
document.addEventListener('DOMContentLoaded', async function() {
    await loadDashboard();
    initializeRoleBasedSidebarVisibility();
});

async function loadDashboard(year = null) {
    const loadingContainer = document.getElementById('dashboardLoadingContainer');
    const chartsContainer = document.getElementById('chartsContainer');
    const yearFilter = document.getElementById('dashboardYearFilter');
    
    try {
        let url = 'fetch_dashboard_stats.php';
        if (year) {
            url += `?year=${encodeURIComponent(year)}`;
        }
        
        const response = await fetch(url);
        const responseText = await response.text();
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            throw new Error(`Failed to parse response: ${parseError.message}`);
        }
        
        if (data.error) {
            throw new Error(data.error);
        }

        // Reset difficulty state for new data load
        window.__difficultyState.fullData = data.dashboard?.difficulty_data || [];
        window.__difficultyState.role = data.dashboard?.type || '';
        window.__difficultyState.programFilter = 'all';
        window.__difficultyState.searchQuery = '';

        // Handle Year Filter Population
        if (yearFilter && yearFilter.options.length === 0 && Array.isArray(data.available_years)) {
            data.available_years.forEach(y => {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = `AY ${y}-${y + 1}`;
                if (Number(y) === Number(data.selected_year)) opt.selected = true;
                yearFilter.appendChild(opt);
            });
            
            yearFilter.onchange = () => {
                loadingContainer.style.display = 'block';
                chartsContainer.style.display = 'none';
                loadDashboard(yearFilter.value);
            };
        }
        
        // Render the appropriate dashboard
        renderDashboard(data);
        
        // Hide loading and show charts
        loadingContainer.style.display = 'none';
        chartsContainer.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading dashboard:', error);
        loadingContainer.innerHTML = `<p class="text-red-500" style="padding: 2rem; font-weight: bold;">Error: ${error.message}</p>`;
    }
}

function renderDashboard(payload) {
    const chartsContainer = document.getElementById('chartsContainer');
    chartsContainer.innerHTML = '';

    const dashboardData = payload && payload.dashboard ? payload.dashboard : null;
    const professorDashboard = payload && payload.professor_dashboard ? payload.professor_dashboard : null;
    
    chartsContainer.dataset.dashboardRole = dashboardData && dashboardData.type ? dashboardData.type : '';

    if (!dashboardData) {
        chartsContainer.innerHTML = '<div class="dashboard-chart"><p class="text-center text-gray-500 py-8">No data available for the selected academic year.</p></div>';
        return;
    }

    const title = dashboardData.title || 'Dashboard';
    const subtitle = dashboardData.subtitle || '';

    if (dashboardData.chart_type === 'split_summary') {
        renderSplitDashboard(chartsContainer, dashboardData, professorDashboard, title, subtitle);
    } else if (dashboardData.chart_type === 'professor_summary' || dashboardData.type === 'professor') {
        renderProfessorDashboard(chartsContainer, dashboardData, title, subtitle);
    }
}

function renderSplitDashboard(container, dashboardData, professorDashboard, title, subtitle) {
    let leftColumnHtml = '';
    const difficultyHtml = getDifficultyInsightsHtml(dashboardData.difficulty_data, dashboardData.available_programs);

    if (dashboardData.type === 'executive_director') {
        leftColumnHtml = getVerticalBarChartHtml({
            data: dashboardData.participation_data,
            title: 'Submissions per Program',
            subtitle: 'Active programs participation'
        });
    } else {
        // Program Director
        leftColumnHtml = getHorizontalBarChartHtml({
            data: dashboardData.top_students,
            title: 'Top 15 Active Students',
            subtitle: 'Students with highest submission count'
        });
    }

    const splitHtml = `
        <div class="dashboard-split-layout">
            <div class="dashboard-column dashboard-column--primary">
                ${leftColumnHtml}
            </div>
            <div id="difficultyColumn" class="dashboard-column dashboard-column--secondary">
                ${difficultyHtml}
            </div>
        </div>
    `;

    if (professorDashboard) {
        renderDashboardCarousel(container, splitHtml, professorDashboard, title, subtitle);
    } else {
        container.innerHTML = `
            <div class="dashboard-header-block">
                <h3 class="chart-title">${title}</h3>
                <p class="chart-subtitle">${subtitle}</p>
            </div>
            ${splitHtml}
        `;
    }

    // Bind difficulty filters after rendering
    bindDifficultyFilters();
}

function getDifficultyInsightsHtml(difficultyData, availablePrograms = []) {
    const isED = window.__difficultyState.role === 'executive_director';
    
    // Header with search and program filter (if ED)
    let filtersHtml = `
        <div class="widget-controls">
            <div class="widget-search">
                <i class="fas fa-search"></i>
                <input type="text" id="diffSearchInput" placeholder="Search course..." value="${window.__difficultyState.searchQuery}">
            </div>
    `;

    if (isED && availablePrograms.length > 0) {
        filtersHtml += `
            <div class="widget-filter">
                <select id="diffProgramFilter">
                    <option value="all">All Programs</option>
                    ${availablePrograms.map(p => `
                        <option value="${p.id}" ${Number(window.__difficultyState.programFilter) === p.id ? 'selected' : ''}>
                            ${escapeDashboardHtml(p.name)}
                        </option>
                    `).join('')}
                </select>
            </div>
        `;
    }

    filtersHtml += `</div>`;

    return `
        <div class="dashboard-chart difficulty-widget">
            <div class="widget-header-row">
                <h4 class="widget-title">Course Difficulty Insights</h4>
                ${filtersHtml}
            </div>
            <p class="chart-subtitle-small">Student perceived difficulty distribution</p>
            <div id="difficultyListContainer" class="difficulty-list">
                ${renderDifficultyList(difficultyData)}
            </div>
            <div class="difficulty-full-legend">
                <span class="leg-item"><span class="leg-dot diff-seg--easy"></span> Easy</span>
                <span class="leg-item"><span class="leg-dot diff-seg--normal"></span> Normal</span>
                <span class="leg-item"><span class="leg-dot diff-seg--hard"></span> Hard</span>
            </div>
        </div>
    `;
}

function renderDifficultyList(data) {
    if (!data || data.length === 0) {
        return `<p class="text-center text-gray-400 py-8">No matching courses found.</p>`;
    }

    return data.map(course => {
        const total = course.total;
        const easyP = Math.round((course.easy / total) * 100);
        const normalP = Math.round((course.normal / total) * 100);
        const hardP = Math.round((course.hard / total) * 100);
        const courseCodeLabel = course.course_code ? ` [${course.course_code}]` : '';

        return `
            <div class="difficulty-course-row">
                <div class="difficulty-course-info">
                    <div class="difficulty-course-texts">
                        <span class="difficulty-course-name">${escapeDashboardHtml(course.course_name)}${escapeDashboardHtml(courseCodeLabel)}</span>
                        ${window.__difficultyState.role === 'executive_director' ? 
                            `<span class="difficulty-course-program">${escapeDashboardHtml(course.program_name)}</span>` : ''}
                    </div>
                    <span class="difficulty-course-total">${total} ratings</span>
                </div>
                <div class="difficulty-stack-bar">
                    <div class="diff-seg diff-seg--easy" style="width: ${easyP}%" title="Easy: ${course.easy}"></div>
                    <div class="diff-seg diff-seg--normal" style="width: ${normalP}%" title="Normal: ${course.normal}"></div>
                    <div class="diff-seg diff-seg--hard" style="width: ${hardP}%" title="Hard: ${course.hard}"></div>
                </div>
                <div class="difficulty-legend-mini">
                    <span class="text-green-600">${easyP}%</span>
                    <span class="text-yellow-600">${normalP}%</span>
                    <span class="text-red-600">${hardP}%</span>
                </div>
            </div>
        `;
    }).join('');
}

function bindDifficultyFilters() {
    const searchInput = document.getElementById('diffSearchInput');
    const programFilter = document.getElementById('diffProgramFilter');
    const listContainer = document.getElementById('difficultyListContainer');

    if (!searchInput || !listContainer) return;

    const updateList = () => {
        const query = searchInput.value.toLowerCase().trim();
        const programId = programFilter ? programFilter.value : 'all';

        window.__difficultyState.searchQuery = query;
        window.__difficultyState.programFilter = programId;

        const filtered = window.__difficultyState.fullData.filter(item => {
            const matchesName = item.course_name.toLowerCase().includes(query);
            const matchesCode = item.course_code && item.course_code.toLowerCase().includes(query);
            const matchesProgram = programId === 'all' || Number(item.program_id) === Number(programId);
            return (matchesName || matchesCode) && matchesProgram;
        });

        listContainer.innerHTML = renderDifficultyList(filtered);
    };

    searchInput.addEventListener('input', updateList);
    if (programFilter) {
        programFilter.addEventListener('change', updateList);
    }
}

function getVerticalBarChartHtml(config) {
    const data = config.data || [];
    const maxVal = Math.max(...data.map(item => item.value || 0), 1);

    return `
        <div class="dashboard-chart vertical-bar-chart">
            <h4 class="widget-title">${config.title}</h4>
            <p class="chart-subtitle-small">${config.subtitle}</p>
            <div class="chart-wrapper">
                <div class="chart-bars-container ${data.length < 4 ? 'chart-bars-container--centered' : ''}">
                    ${data.map((item, index) => `
                        <div class="bar-group" style="--bar-index: ${index}">
                            <div class="bar-wrapper">
                                <div class="bar" style="height: ${(item.value / maxVal) * 200}px" title="${item.label}: ${item.value}">
                                    <span class="bar-value">${item.value}</span>
                                </div>
                            </div>
                            <div class="bar-label">${item.label}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

function getHorizontalBarChartHtml(config) {
    const data = config.data || [];
    const maxValue = Math.max(...data.map(item => item.value || 0), 1);

    return `
        <div class="dashboard-chart horizontal-bar-chart">
            <h4 class="widget-title">${config.title}</h4>
            <p class="chart-subtitle-small">${config.subtitle}</p>
            <div class="chart-wrapper horizontal">
                <div class="chart-bars-container horizontal">
                    ${data.map(item => `
                        <div class="bar-item horizontal">
                            <div class="bar-label-horizontal">${item.label}</div>
                            <div class="bar-wrapper horizontal">
                                <div class="bar horizontal" style="width: ${(item.value / maxValue) * 100}%" title="${item.label}: ${item.value} submissions">
                                    <span class="bar-value horizontal">${item.value}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
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
        ? '<tr><td colspan="3" class="prof-empty-row">No active classes assigned for this period.</td></tr>'
        : classes.map((entry) => {
            const className = escapeDashboardHtml(entry.class_name || 'Untitled class');
            const submitted = Number(entry.submitted_count || 0);
            const total = Number(entry.total_students || 0);
            const missing = Number(entry.missing_count || 0);
            const percent = total > 0 ? Math.round((submitted / total) * 100) : 0;
            
            return `
                <tr>
                    <td>${className}</td>
                    <td>
                        <div class="table-progress-wrap">
                            <span class="table-progress-text">${submitted} / ${total}</span>
                            <div class="table-progress-bar"><div class="table-progress-fill" style="width: ${percent}%"></div></div>
                        </div>
                    </td>
                    <td><span class="${missing > 0 ? 'text-red-500 font-bold' : 'text-gray-400'}">${missing}</span></td>
                </tr>
            `;
        }).join('');

    return `
        <div class="dashboard-chart professor-dashboard">
            <h3 class="chart-title">${title}</h3>
            <p class="chart-subtitle">${subtitle}</p>
            <div class="prof-metric-cards">
                <div class="prof-card prof-card--danger">
                    <div class="prof-card-label">Portfolios Pending Review</div>
                    <div class="prof-card-value">${pending}</div>
                </div>
                <div class="prof-card prof-card--success">
                    <div class="prof-card-label">Successfully Graded</div>
                    <div class="prof-card-value">${graded}</div>
                </div>
            </div>
            <div class="prof-class-table-wrap">
                <table class="prof-class-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Submission Rate</th>
                            <th>Pending</th>
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

function renderDashboardCarousel(container, primaryHtml, professorDashboard, primaryTitle, primarySubtitle) {
    const professorTitle = professorDashboard.title || 'Professor Overview';
    const professorSubtitle = professorDashboard.subtitle || 'Your grading workload and active classes';
    const professorHtml = getProfessorDashboardHtml(professorDashboard, professorTitle, professorSubtitle);

    container.innerHTML = `
        <div class="dashboard-header-block">
            <h3 class="chart-title">${primaryTitle}</h3>
            <p class="chart-subtitle">${primarySubtitle}</p>
        </div>
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
