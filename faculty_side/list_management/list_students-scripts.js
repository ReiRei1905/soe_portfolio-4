document.addEventListener('DOMContentLoaded', () => {
    const createListBtn = document.getElementById('createListBtn');
    const listInputContainer = document.getElementById('listInputContainer');
    
    const listBatchInput = document.getElementById('listBatchInput');
    const listYearInput = document.getElementById('listYearInput');
    const confirmListBtn = document.getElementById('confirmListBtn');

    const listsEmptyState = document.getElementById('listsEmptyState');
    const listsGrid = document.getElementById('listsGrid');
    const listStudentsView = document.getElementById('listStudentsView');
    const listStudentsTitle = document.getElementById('listStudentsTitle');
    const listStudentsMembers = document.getElementById('listStudentsMembers');
    const listStudentsFilterBtn = document.getElementById('listStudentsFilterBtn');
    const listStudentsFilterMenu = document.getElementById('listStudentsFilterMenu');
    const listStudentsSearchInput = document.getElementById('listStudentsSearchInput');
    const refreshListStudentsBtn = document.getElementById('refreshListStudentsBtn');
    const listStudentReportsView = document.getElementById('listStudentReportsView');
    const academicPortfoliosTabBtn = document.getElementById('academicPortfoliosTabBtn');
    const extracurricularPortfoliosTabBtn = document.getElementById('extracurricularPortfoliosTabBtn');
    const reportsPortfolioHeading = document.getElementById('reportsPortfolioHeading');
    const academicPortfoliosContainer = document.getElementById('academicPortfoliosContainer');
    const extracurricularPortfoliosContainer = document.getElementById('extracurricularPortfoliosContainer');
    const listReportsBackBtn = document.getElementById('listReportsBackBtn');
    const listExtraFilesModal = document.getElementById('listExtraFilesModal');
    const listExtraFilesModalTitle = document.getElementById('listExtraFilesModalTitle');
    const listExtraFilesModalBody = document.getElementById('listExtraFilesModalBody');
    const listExtraFilesModalClose = document.getElementById('listExtraFilesModalClose');
    const breadcrumbItem = document.querySelector('.lists_students-title .breadcrumb-item');

    const state = {
        programs: [],
        selectedListId: 0,
        listsById: new Map(),
        canCreate: false,
        hasAnyLists: false,
        selectedStudentForReports: null,
        currentList: null,
        currentReportMode: 'academic',
        cachedExtracurricularByStudent: new Map(),
        currentStudents: [],
        listStudentsSortMode: 'alphabetical',
        studentSearchQuery: ''
    };

    function redirectToLogin() {
        window.location.href = '../../user_info_V3/index.php';
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function updateBreadcrumb(listLabel = '', reportLabel = '') {
        if (!breadcrumbItem) return;

        breadcrumbItem.innerHTML = '';

        const link = document.createElement('a');
        link.href = './list_students.html';
        link.textContent = 'Lists of Students';
        link.addEventListener('click', (event) => {
            event.preventDefault();
            state.selectedListId = 0;
            state.selectedStudentForReports = null;
            showOverviewMode();
            updateBreadcrumb('');
        });
        breadcrumbItem.appendChild(link);

        if (listLabel) {
            breadcrumbItem.appendChild(document.createTextNode(` > ${listLabel}`));
        }

        if (reportLabel) {
            breadcrumbItem.appendChild(document.createTextNode(` > ${reportLabel}`));
        }
    }

    function showOverviewMode() {
        if (state.canCreate) {
            createListBtn.style.display = '';
        }

        state.studentSearchQuery = '';
        if (listStudentsSearchInput) {
            listStudentsSearchInput.value = '';
        }

        listStudentsView.classList.add('hidden');
        listStudentReportsView.classList.add('hidden');

        if (state.hasAnyLists) {
            listsGrid.classList.remove('hidden');
            listsEmptyState.classList.add('hidden');
        } else {
            listsGrid.classList.add('hidden');
            listsEmptyState.classList.remove('hidden');
        }
    }

    function showDetailMode() {
        createListBtn.style.display = 'none';
        listInputContainer.classList.add('hidden');
        listsEmptyState.classList.add('hidden');
        listsGrid.classList.add('hidden');
        listStudentsView.classList.remove('hidden');
        listStudentReportsView.classList.add('hidden');
    }

    function showReportsMode() {
        createListBtn.style.display = 'none';
        listInputContainer.classList.add('hidden');
        listsEmptyState.classList.add('hidden');
        listsGrid.classList.add('hidden');
        listStudentsView.classList.add('hidden');
        listStudentReportsView.classList.remove('hidden');
    }

    function listLabel(item) {
        const batchName = String(item.batchName || '').trim();
        const year = Number(item.yearOfEnrollment || 0);
        // Changed from hyphen to space and brackets
        return year > 0 ? `${batchName} [${year}]` : batchName; 
    }

    function closeAllListDropdowns() {
        document.querySelectorAll('.list-item-dropdown').forEach((menu) => {
            menu.classList.add('hidden');
        });
    }

    async function parseJsonResponse(response) {
        const raw = await response.text();
        try {
            return JSON.parse(raw);
        } catch (_error) {
            const cleaned = String(raw || '').replace(/\s+/g, ' ').trim();
            const shortMessage = cleaned.slice(0, 220) || 'Invalid JSON response.';
            return { success: false, message: shortMessage };
        }
    }

    async function postForm(url, payload) {
        const body = new URLSearchParams(payload);
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });

        const data = await parseJsonResponse(response);
        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            throw new Error(data.message || 'Unauthorized');
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Request failed.');
        }

        return data;
    }

    function renderProgramOptions(programs) {
        listProgramSelect.innerHTML = '<option value="" disabled selected>Select Program</option>';

        programs.forEach((program) => {
            const option = document.createElement('option');
            option.value = String(program.id);
            option.textContent = String(program.name || '').trim();
            listProgramSelect.appendChild(option);
        });
    }

    function formatDateTime(value) {
        const raw = String(value || '').trim();
        if (!raw || raw === '0000-00-00 00:00:00') {
            return 'N/A';
        }

        const date = new Date(raw.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return raw;
        }

        const month = date.toLocaleString('en-US', { month: 'short' });
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();

        let hours = date.getHours();
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const period = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;

        return `${month}. ${day}, ${year} ${hours}:${minutes} ${period}`;
    }

    function parseDateTimeForSort(value) {
        const raw = String(value || '').trim();
        if (!raw || raw === '0000-00-00 00:00:00') {
            return 0;
        }

        const parsed = Date.parse(raw.replace(' ', 'T'));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function getComparableStudentName(student) {
        const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim();
        if (fullName !== '') {
            return fullName.toLowerCase();
        }
        return String(student.email || '').trim().toLowerCase();
    }

    function sortStudents(students) {
        const list = Array.isArray(students) ? [...students] : [];

        if (state.listStudentsSortMode === 'datetime') {
            list.sort((a, b) => {
                const bTime = parseDateTimeForSort(b.joinedAt || '');
                const aTime = parseDateTimeForSort(a.joinedAt || '');
                if (bTime !== aTime) {
                    return bTime - aTime;
                }
                return getComparableStudentName(a).localeCompare(getComparableStudentName(b));
            });
            return list;
        }

        list.sort((a, b) => getComparableStudentName(a).localeCompare(getComparableStudentName(b)));
        return list;
    }

    function closeListStudentsFilterMenu() {
        if (!listStudentsFilterMenu) {
            return;
        }

        listStudentsFilterMenu.classList.add('hidden');
        if (listStudentsFilterBtn) {
            listStudentsFilterBtn.setAttribute('aria-expanded', 'false');
        }
    }

    function openListStudentsFilterMenu() {
        if (!listStudentsFilterMenu) {
            return;
        }

        listStudentsFilterMenu.classList.remove('hidden');
        if (listStudentsFilterBtn) {
            listStudentsFilterBtn.setAttribute('aria-expanded', 'true');
        }
    }

    function renderCurrentListStudents() {
        if (!listStudentsMembers) {
            return;
        }

        listStudentsMembers.innerHTML = '';
        
        // Filter students based on search query
        const query = (state.studentSearchQuery || '').toLowerCase().trim();
        const filtered = state.currentStudents.filter(student => {
            if (!query) return true;
            const fullName = `${student.firstName || ''} ${student.lastName || ''}`.toLowerCase();
            const idNumber = String(student.idNumber || '').toLowerCase();
            const email = String(student.email || '').toLowerCase();
            return fullName.includes(query) || idNumber.includes(query) || email.includes(query);
        });

        const sortedStudents = sortStudents(filtered);
        if (sortedStudents.length === 0) {
            const msg = query ? 'No students match your search.' : 'No students found for this list.';
            listStudentsMembers.innerHTML = `<p class="members-empty">${msg}</p>`;
            return;
        }

        sortedStudents.forEach((student) => {
            listStudentsMembers.appendChild(createStudentRow(student));
        });
    }

    function setReportsMode(mode) {
        const isAcademic = mode === 'academic';
        state.currentReportMode = isAcademic ? 'academic' : 'extracurricular';

        if (academicPortfoliosTabBtn) {
            academicPortfoliosTabBtn.classList.toggle('is-active', isAcademic);
            academicPortfoliosTabBtn.setAttribute('aria-pressed', isAcademic ? 'true' : 'false');
        }

        if (extracurricularPortfoliosTabBtn) {
            extracurricularPortfoliosTabBtn.classList.toggle('is-active', !isAcademic);
            extracurricularPortfoliosTabBtn.setAttribute('aria-pressed', !isAcademic ? 'true' : 'false');
        }

        if (academicPortfoliosContainer) {
            academicPortfoliosContainer.classList.toggle('hidden', !isAcademic);
        }
        if (extracurricularPortfoliosContainer) {
            extracurricularPortfoliosContainer.classList.toggle('hidden', isAcademic);
        }
    }

    function renderPortfolioReviewSummary(review) {
        if (!review || !review.decision) {
            return '<p class="portfolio-review-summary">No review summary yet.</p>';
        }

        const decision = String(review.decision || '').toLowerCase();
        const finalGrade = String(review.finalGrade || '').trim();
        const finalPercentage = String(review.finalPercentage || '').trim();
        const rejectionReason = String(review.rejectionReason || '').trim();
        const reviewedAt = formatDateTime(review.reviewedAt || null);

        if (decision === 'rejected') {
            const reasonPart = rejectionReason ? ` | Reason: ${escapeHtml(rejectionReason)}` : '';
            return `<p class="portfolio-review-summary is-rejected">Decision: Rejected${reasonPart} | Date Accomplished: ${escapeHtml(reviewedAt)}</p>`;
        }

        return `<p class="portfolio-review-summary is-approved">Final Grade: ${escapeHtml(finalGrade || 'N/A')} | Percentage: ${escapeHtml(finalPercentage ? `${finalPercentage}%` : 'N/A')} | Date Accomplished: ${escapeHtml(reviewedAt)}</p>`;
    }

    function renderPortfolioOutputs(outputs) {
        if (!Array.isArray(outputs) || outputs.length === 0) {
            return '<p class="portfolio-outputs-empty">No required outputs were found.</p>';
        }

        return outputs.map((output, index) => {
            const outputName = escapeHtml(output.outputName || `Output ${index + 1}`);
            const scoreValue = output.studentScore !== null && output.studentScore !== undefined
                ? Number(output.studentScore)
                : 0;
            const totalValue = Number(output.totalScore || 0);
            const scoreText = `${scoreValue}/${totalValue}`;
            const fileLink = output.hasFile && output.fileViewUrl
                ? `<a class="portfolio-file-link" href="${escapeHtml(output.fileViewUrl)}" target="_blank" rel="noopener">${escapeHtml(output.submittedFileName || 'View File')}</a>`
                : '<span class="portfolio-file-muted">No file attached</span>';

            return `
                <div class="portfolio-output-row">
                    <span class="portfolio-output-index">${index + 1}.</span>
                    <span class="portfolio-output-name">${outputName}</span>
                    <span class="portfolio-output-score">${escapeHtml(scoreText)}</span>
                    <span class="portfolio-output-file">${fileLink}</span>
                </div>
            `;
        }).join('');
    }

    function renderStudentSummaryCard(studentName, emailLine, idNumber, joinedAt) {
        return `
            <section class="portfolio-student-summary">
                <div class="portfolio-avatar"><i class="fas fa-user"></i></div>
                <p class="portfolio-student-name">${escapeHtml(studentName || 'Student')}</p>
                <p class="portfolio-student-course">${escapeHtml(emailLine || 'No email')}</p>
                <p class="portfolio-student-id">${escapeHtml(idNumber || 'No ID Number')}</p>
                <p class="portfolio-student-meta">Joined at: ${escapeHtml(joinedAt || 'N/A')}</p>
            </section>
        `;
    }

    function renderAcademicPortfolios(data) {
        if (!academicPortfoliosContainer) {
            return;
        }

        const student = data.student || {};
        const portfolios = Array.isArray(data.academicPortfolios) ? data.academicPortfolios : [];

        const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Student';
        const studentEmail = String(student.email || '').trim();
        const joinedAt = formatDateTime(student.joinedAt || null);

        if (portfolios.length === 0) {
            academicPortfoliosContainer.innerHTML = '<p class="members-empty">No academic portfolios found for this student yet.</p>';
            return;
        }

        const courseButtons = portfolios.map((portfolio, index) => {
            const courseName = String(portfolio.courseName || '').trim() || 'Course';
            const courseCode = String(portfolio.courseCode || '').trim();
            const label = courseCode ? `[${courseCode}] ${courseName}` : courseName;
            const isActive = index === 0 ? 'is-active' : '';
            return `
                <button type="button" class="list-academic-course-btn ${isActive}" data-academic-class-id="${Number(portfolio.classId || 0)}">
                    ${escapeHtml(label)}
                </button>
            `;
        }).join('');

        academicPortfoliosContainer.innerHTML = `
            <div class="list-academic-layout">
                <div class="list-academic-course-list">
                    ${courseButtons}
                </div>
                <div id="academicPortfolioDetail" class="list-academic-detail"></div>
            </div>
        `;

        const detailContainer = document.getElementById('academicPortfolioDetail');
        if (!detailContainer) return;

        const portfolioByClassId = new Map();
        portfolios.forEach((portfolio) => {
            portfolioByClassId.set(Number(portfolio.classId || 0), portfolio);
        });

        function renderAcademicDetail(portfolio) {
            if (!portfolio) return;
            const courseName = String(portfolio.courseName || '').trim() || 'Course';
            const courseCode = String(portfolio.courseCode || '').trim();
            const label = courseCode ? `[${courseCode}] ${courseName}` : courseName;
            const outputsMarkup = renderPortfolioOutputs(Array.isArray(portfolio.outputs) ? portfolio.outputs : []);
            const reviewMarkup = renderPortfolioReviewSummary(portfolio.review || null);

            detailContainer.innerHTML = `
                <section class="reports-panel-card reports-primary-card is-portfolio-view list-academic-portfolio-card">
                    <div class="reports-portfolio-view">
                        <div class="portfolio-review-layout">
                            ${renderStudentSummaryCard(fullName, studentEmail, student.idNumber || 'No ID Number', joinedAt)}

                            <section class="portfolio-review-panel">
                                <h4 class="portfolio-review-title">${escapeHtml(label)} Portfolio Table of Contents</h4>
                                <div class="portfolio-outputs-list">${outputsMarkup}</div>
                                <div class="portfolio-review-controls">${reviewMarkup}</div>
                            </section>
                        </div>
                    </div>
                </section>
            `;
        }

        const firstPortfolio = portfolios[0];
        renderAcademicDetail(firstPortfolio);

        academicPortfoliosContainer.querySelectorAll('[data-academic-class-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const classId = Number(button.getAttribute('data-academic-class-id') || 0);
                const selected = portfolioByClassId.get(classId) || null;
                if (!selected) return;

                academicPortfoliosContainer.querySelectorAll('.list-academic-course-btn').forEach((btn) => {
                    btn.classList.toggle('is-active', btn === button);
                });
                renderAcademicDetail(selected);
            });
        });
    }

    function renderExtracurricularPortfolios(data) {
        if (!extracurricularPortfoliosContainer) {
            return;
        }

        const student = data.student || {};
        const portfolios = Array.isArray(data.extracurricularPortfolios) ? data.extracurricularPortfolios : [];

        const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Student';
        const studentEmail = String(student.email || '').trim();
        const joinedAt = formatDateTime(student.joinedAt || null);

        let latestTimestamp = 0;
        let latestRaw = '';
        portfolios.forEach((portfolio) => {
            const raw = portfolio.updatedAt || portfolio.createdAt || '';
            const candidate = parseDateTimeForSort(raw);
            if (candidate > latestTimestamp) {
                latestTimestamp = candidate;
                latestRaw = raw;
            }
        });
        const latestLabel = latestRaw ? formatDateTime(latestRaw) : 'N/A';

        let html = `
            <section class="reports-panel-card reports-primary-card is-portfolio-view list-academic-portfolio-card">
                <div class="reports-portfolio-view">
                    <div class="portfolio-review-layout">
                        ${renderStudentSummaryCard(fullName, studentEmail, student.idNumber || 'No ID Number', joinedAt)}
                        <section class="portfolio-review-panel list-extracurricular-panel">
                            <p class="list-extra-updated-meta">Recent update: ${escapeHtml(latestLabel)}</p>
                            <div class="list-extra-quick-grid">
        `;

        if (portfolios.length === 0) {
            html += '<p class="members-empty">No extracurricular portfolios found for this student yet.</p>';
        } else {
            portfolios.forEach((portfolio) => {
                const title = String(portfolio.title || '').trim() || 'Untitled portfolio';
                const portfolioId = Number(portfolio.portfolioId || 0);

                html += `
                    <div class="list-extra-quick-card-shell">
                        <button type="button" class="quick-card list-extra-quick-card" data-extra-portfolio-id="${portfolioId}">
                            <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                            <h3 class="quick-title">${escapeHtml(title)}</h3>
                        </button>
                    </div>
                `;
            });
        }

        html += `
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        `;

        extracurricularPortfoliosContainer.innerHTML = html;

        const portfolioById = new Map();
        portfolios.forEach((portfolio) => {
            portfolioById.set(Number(portfolio.portfolioId || 0), portfolio);
        });

        extracurricularPortfoliosContainer.querySelectorAll('[data-extra-portfolio-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const id = Number(button.getAttribute('data-extra-portfolio-id') || 0);
                const portfolio = portfolioById.get(id) || null;
                if (!portfolio) {
                    return;
                }

                const title = String(portfolio.title || 'Portfolio Files');
                const files = Array.isArray(portfolio.files) ? portfolio.files : [];
                openExtracurricularFilesModal(title, files);
            });
        });
    }

    function openExtracurricularFilesModal(title, files) {
        if (!listExtraFilesModal || !listExtraFilesModalTitle || !listExtraFilesModalBody) {
            return;
        }

        listExtraFilesModalTitle.textContent = title || 'Portfolio Files';

        if (!Array.isArray(files) || files.length === 0) {
            listExtraFilesModalBody.innerHTML = '<p class="members-empty">No files attached yet.</p>';
        } else {
            const links = files.map((file) => {
                const fileUrl = String(file.viewUrl || '').trim();
                if (!fileUrl) {
                    return '';
                }
                return `<a class="portfolio-file-link list-extra-file-link" href="${escapeHtml(fileUrl)}" target="_blank" rel="noopener">${escapeHtml(file.fileName || 'View file')}</a>`;
            }).filter(Boolean).join('');

            listExtraFilesModalBody.innerHTML = links || '<p class="members-empty">No files attached yet.</p>';
        }

        listExtraFilesModal.classList.remove('hidden');
    }

    function closeExtracurricularFilesModal() {
        if (!listExtraFilesModal || !listExtraFilesModalBody) {
            return;
        }
        listExtraFilesModal.classList.add('hidden');
        listExtraFilesModalBody.innerHTML = '';
    }

    async function loadExtracurricularPortfolios(studentId) {
        const cacheKey = Number(studentId || 0);
        if (cacheKey > 0 && state.cachedExtracurricularByStudent.has(cacheKey)) {
            return state.cachedExtracurricularByStudent.get(cacheKey);
        }

        const response = await fetch(
            `fetch_student_extracurricular_portfolios.php?listId=${encodeURIComponent(state.selectedListId)}&studentId=${encodeURIComponent(studentId)}`,
            { credentials: 'same-origin' }
        );
        const data = await parseJsonResponse(response);

        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            return null;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load extracurricular portfolios.');
        }

        if (cacheKey > 0) {
            state.cachedExtracurricularByStudent.set(cacheKey, data);
        }

        return data;
    }

    async function openStudentReports(student) {
        if (state.selectedListId <= 0) {
            return;
        }

        state.selectedStudentForReports = student;

        const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Student';
        if (reportsPortfolioHeading) {
            reportsPortfolioHeading.textContent = `${fullName} Portfolio`;
        }

        const listItem = state.currentList || state.listsById.get(state.selectedListId) || null;
        const listTrail = listItem ? listLabel(listItem) : '';
        updateBreadcrumb(listTrail, `${fullName} Reports`);

        showReportsMode();
        setReportsMode('academic');

        if (academicPortfoliosContainer) {
            academicPortfoliosContainer.innerHTML = '<p class="members-empty">Loading academic portfolios...</p>';
        }

        const response = await fetch(
            `fetch_student_academic_portfolios.php?listId=${encodeURIComponent(state.selectedListId)}&studentId=${encodeURIComponent(student.studentId)}`,
            { credentials: 'same-origin' }
        );
        const data = await parseJsonResponse(response);

        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load academic portfolios.');
        }

        renderAcademicPortfolios(data);
    }

    function createStudentRow(student) {
        const fullName = `${student.firstName || ''} ${student.lastName || ''}`.trim() || 'Student';

        const article = document.createElement('article');
        article.className = 'member-card list-student-card';
        article.innerHTML = `
            <div class="member-avatar"><i class="fas fa-user"></i></div>
            <div class="member-info">
                <p class="member-name">${escapeHtml(fullName)}</p>
                <p class="member-meta">${escapeHtml(student.idNumber || 'No ID Number')}</p>
                <p class="member-meta">${escapeHtml(student.email || '')}</p>
            </div>
            <div class="member-actions-right">
                <button type="button" class="member-action-btn list-student-reports-btn">Reports</button>
            </div>
        `;

        const reportsBtn = article.querySelector('.list-student-reports-btn');
        if (reportsBtn) {
            reportsBtn.addEventListener('click', async (event) => {
                event.stopPropagation();
                reportsBtn.disabled = true;
                reportsBtn.classList.add('is-loading');
                try {
                    await openStudentReports(student);
                } catch (error) {
                    alert(error.message || 'Failed to load student reports.');
                } finally {
                    reportsBtn.disabled = false;
                    reportsBtn.classList.remove('is-loading');
                }
            });
        }

        return article;
    }

    async function loadListStudents(listId) {
        const response = await fetch(`fetch_list_students.php?listId=${encodeURIComponent(listId)}`, {
            credentials: 'same-origin'
        });
        const data = await parseJsonResponse(response);

        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load students.');
        }

        const item = data.list || null;
        if (!item) {
            throw new Error('List details are missing.');
        }

        state.selectedListId = Number(item.listId || 0);
        state.currentList = item;
        state.selectedStudentForReports = null;
        state.currentStudents = Array.isArray(data.students) ? data.students : [];
        updateBreadcrumb(listLabel(item));

        listStudentsTitle.textContent = `${item.batchName || 'Batch'} Students`;
        renderCurrentListStudents();

        showDetailMode();

        document.querySelectorAll('.student-list-card').forEach((card) => {
            card.classList.toggle('is-selected', Number(card.dataset.listId) === state.selectedListId);
        });
    }

    function createListCard(item) {
        const card = document.createElement('article');
        card.className = 'student-list-card';
        card.dataset.listId = String(item.listId);

        if (Number(item.listId) === state.selectedListId) {
            card.classList.add('is-selected');
        }

        const title = listLabel(item);

        card.innerHTML = `
            <div class="student-list-main">
                <div class="student-list-icon"><i class="fas fa-users"></i></div>
                <div class="student-list-texts">
                    <p class="student-list-name">${escapeHtml(title)}</p>
                    <p class="student-list-meta">${Number(item.studentCount || 0)} student(s)</p>
                </div>
            </div>
            <div class="student-list-actions"></div>
            <div class="list-inline-edit hidden">
                <input type="text" class="list-inline-input" aria-label="Edit list name" />
                <button type="button" class="list-inline-confirm confirm-btn">Confirm</button>
                <button type="button" class="list-inline-cancel">Cancel</button>
            </div>
        `;

        const actionsWrap = card.querySelector('.student-list-actions');
        const inlineEdit = card.querySelector('.list-inline-edit');
        const inlineInput = card.querySelector('.list-inline-input');
        const inlineConfirmBtn = card.querySelector('.list-inline-confirm');
        const inlineCancelBtn = card.querySelector('.list-inline-cancel');

        function openInlineEditor() {
            closeAllListDropdowns();
            inlineInput.value = item.batchName || '';
            card.classList.add('is-editing');
            inlineEdit.classList.remove('hidden');
            inlineInput.focus();
            inlineInput.select();
        }

        function closeInlineEditor() {
            card.classList.remove('is-editing');
            inlineEdit.classList.add('hidden');
        }

        inlineEdit.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        inlineCancelBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            closeInlineEditor();
        });

        inlineConfirmBtn.addEventListener('click', async (event) => {
            event.stopPropagation();

            const trimmed = inlineInput.value.trim();
            if (!trimmed) {
                alert('Batch name is required.');
                return;
            }

            try {
                inlineConfirmBtn.disabled = true;
                await postForm('update_student_list.php', {
                    listId: String(item.listId),
                    batchName: trimmed
                });

                state.selectedListId = 0;
                updateBreadcrumb('');
                await loadLists();
                showOverviewMode();
            } catch (error) {
                alert(error.message || 'Failed to rename list.');
            } finally {
                inlineConfirmBtn.disabled = false;
            }
        });

        inlineInput.addEventListener('keydown', async (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                inlineConfirmBtn.click();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                closeInlineEditor();
            }
        });

        if (item.canManage) {
            const optionsBtn = document.createElement('button');
            optionsBtn.type = 'button';
            optionsBtn.className = 'student-list-options';
            optionsBtn.innerHTML = '<i class="fas fa-ellipsis-h"></i>';
            optionsBtn.setAttribute('aria-label', 'List options');

            const dropdown = document.createElement('div');
            dropdown.className = 'list-item-dropdown hidden';
            dropdown.innerHTML = `
                <button type="button" class="edit-btn">Edit</button>
                <button type="button" class="remove-btn">Delete</button>
            `;

            optionsBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = dropdown.classList.contains('hidden');
                closeAllListDropdowns();
                if (willOpen) {
                    dropdown.classList.remove('hidden');
                }
            });

            dropdown.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            const editBtn = dropdown.querySelector('.edit-btn');
            const removeBtn = dropdown.querySelector('.remove-btn');

            editBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                openInlineEditor();
            });

            removeBtn.addEventListener('click', async () => {
                const confirmed = confirm(`Delete ${title}?`);
                if (!confirmed) return;

                try {
                    await postForm('delete_student_list.php', {
                        listId: String(item.listId)
                    });

                    closeAllListDropdowns();
                    const wasSelected = state.selectedListId === Number(item.listId);
                    if (wasSelected) {
                        state.selectedListId = 0;
                        state.currentList = null;
                        state.selectedStudentForReports = null;
                        state.currentStudents = [];
                        listStudentsView.classList.add('hidden');
                        listStudentReportsView.classList.add('hidden');
                        listStudentsMembers.innerHTML = '';
                        updateBreadcrumb('');
                    }
                    await loadLists();
                } catch (error) {
                    alert(error.message || 'Failed to delete list.');
                }
            });

            actionsWrap.appendChild(optionsBtn);
            actionsWrap.appendChild(dropdown);
        }

        card.addEventListener('click', async () => {
            try {
                await loadListStudents(item.listId);
            } catch (error) {
                alert(error.message || 'Failed to load list students.');
            }
        });

        return card;
    }

    function renderLists(groups) {
        state.listsById.clear();
        listsGrid.innerHTML = '';

        groups.forEach((group) => {
            const column = document.createElement('section');
            column.className = 'list-program-column';
            column.innerHTML = `
                <h3 class="list-program-title">${escapeHtml(group.programName || 'Program')}</h3>
                <div class="list-program-items"></div>
            `;

            const itemsWrap = column.querySelector('.list-program-items');
            const lists = Array.isArray(group.lists) ? group.lists : [];

            if (lists.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'members-empty';
                empty.textContent = 'No lists yet for this program.';
                itemsWrap.appendChild(empty);
            } else {
                lists.forEach((item) => {
                    const normalized = {
                        listId: Number(item.listId || 0),
                        programId: Number(item.programId || 0),
                        programName: String(item.programName || ''),
                        batchName: String(item.batchName || ''),
                        yearOfEnrollment: Number(item.yearOfEnrollment || 0),
                        studentCount: Number(item.studentCount || 0),
                        canManage: Boolean(item.canManage)
                    };

                    state.listsById.set(normalized.listId, normalized);
                    itemsWrap.appendChild(createListCard(normalized));
                });
            }

            listsGrid.appendChild(column);
        });
    }

    async function loadLists(preferredListId = 0) {
        const response = await fetch('fetch_student_lists.php', { credentials: 'same-origin' });
        const data = await parseJsonResponse(response);

        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            return;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load student lists.');
        }

        const groups = Array.isArray(data.groups) ? data.groups : [];
        const hasAnyLists = groups.some((group) => Array.isArray(group.lists) && group.lists.length > 0);
        state.hasAnyLists = hasAnyLists;

        listsEmptyState.classList.toggle('hidden', hasAnyLists);
        listsGrid.classList.toggle('hidden', !hasAnyLists);

        if (!hasAnyLists) {
            listStudentsView.classList.add('hidden');
            listStudentReportsView.classList.add('hidden');
            listStudentsMembers.innerHTML = '';
            state.selectedListId = 0;
            state.currentList = null;
            state.selectedStudentForReports = null;
            state.currentStudents = [];
            updateBreadcrumb('');
            showOverviewMode();
            return;
        }

        renderLists(groups);

        let nextSelectedId = Number(preferredListId || 0);
        if (nextSelectedId <= 0 && state.selectedListId > 0 && state.listsById.has(state.selectedListId)) {
            nextSelectedId = state.selectedListId;
        }

        if (nextSelectedId > 0 && state.listsById.has(nextSelectedId)) {
            await loadListStudents(nextSelectedId);
        } else {
            showOverviewMode();
        }
    }

    async function loadAccessContext() {
        const response = await fetch('fetch_list_access.php', { credentials: 'same-origin' });
        const data = await parseJsonResponse(response);

        if (response.status === 401 || response.status === 403) {
            redirectToLogin();
            return false;
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load list access context.');
        }

        state.programs = Array.isArray(data.programs) ? data.programs : [];
        state.canCreate = Boolean(data.canCreate);

        

        if (!state.canCreate) {
            createListBtn.style.display = 'none';
            listInputContainer.classList.add('hidden');
        }

        return true;
    }

    createListBtn.addEventListener('click', () => {
        if (!state.canCreate) {
            alert('You are not allowed to create lists of students.');
            return;
        }
        listInputContainer.classList.toggle('hidden');
        if (!listInputContainer.classList.contains('hidden')) {
            listBatchInput.focus();
        }
    });

    confirmListBtn.addEventListener('click', async () => {
        if (!state.canCreate) {
            alert('You are not allowed to create lists of students.');
            return;
        }

        // Automatically use the first program they are assigned to (since the UI is hidden)
        const programId = state.programs.length > 0 ? Number(state.programs[0].id) : 0;
        const batchName = String(listBatchInput.value || '').trim();
        const yearOfEnrollment = Number(listYearInput.value || 0);

        if (programId <= 0) {
            alert('You are not assigned to any program to create a list.');
            return;
        }

        if (batchName === '' || yearOfEnrollment <= 0) {
            alert('Please complete Batch Name, and Year of Admission.');
            return;
        }

        try {
            await postForm('create_student_list.php', {
                programId: String(programId),
                batchName,
                yearOfEnrollment: String(yearOfEnrollment)
            });

            listBatchInput.value = '';
            listYearInput.value = '';
            listInputContainer.classList.add('hidden');

            state.selectedListId = 0;
            updateBreadcrumb('');
            await loadLists();
        } catch (error) {
            alert(error.message || 'Failed to create list.');
        }
    });

    document.addEventListener('click', (event) => {
        const isOptionButton = event.target.closest('.student-list-options');
        const isDropdown = event.target.closest('.list-item-dropdown');
        const isFilterButton = event.target.closest('#listStudentsFilterBtn');
        const isFilterMenu = event.target.closest('#listStudentsFilterMenu');

        if (!isOptionButton && !isDropdown) {
            closeAllListDropdowns();
        }

        if (!isFilterButton && !isFilterMenu) {
            closeListStudentsFilterMenu();
        }
    });

    if (listStudentsSearchInput) {
        listStudentsSearchInput.addEventListener('input', () => {
            state.studentSearchQuery = listStudentsSearchInput.value;
            renderCurrentListStudents();
        });
    }

    if (listStudentsFilterBtn && listStudentsFilterMenu) {
        listStudentsFilterBtn.addEventListener('click', (event) => {
            event.preventDefault();
            const isHidden = listStudentsFilterMenu.classList.contains('hidden');
            if (isHidden) {
                openListStudentsFilterMenu();
            } else {
                closeListStudentsFilterMenu();
            }
        });

        listStudentsFilterMenu.addEventListener('click', (event) => {
            const option = event.target.closest('[data-student-sort]');
            if (!option) {
                return;
            }

            const nextSort = String(option.getAttribute('data-student-sort') || 'alphabetical');
            if (nextSort !== 'alphabetical' && nextSort !== 'datetime') {
                return;
            }

            state.listStudentsSortMode = nextSort;
            listStudentsFilterMenu.querySelectorAll('[data-student-sort]').forEach((button) => {
                const isActive = button === option;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            renderCurrentListStudents();
            closeListStudentsFilterMenu();
        });
    }

    if (refreshListStudentsBtn) {
        refreshListStudentsBtn.addEventListener('click', async () => {
            if (state.selectedListId <= 0) {
                return;
            }

            try {
                refreshListStudentsBtn.disabled = true;
                refreshListStudentsBtn.classList.add('is-refreshing');
                await loadListStudents(state.selectedListId);
            } catch (error) {
                alert(error.message || 'Failed to refresh students.');
            } finally {
                refreshListStudentsBtn.disabled = false;
                refreshListStudentsBtn.classList.remove('is-refreshing');
            }
        });
    }

    if (listReportsBackBtn) {
        listReportsBackBtn.addEventListener('click', () => {
            const listItem = state.currentList || state.listsById.get(state.selectedListId) || null;
            state.selectedStudentForReports = null;
            showDetailMode();
            updateBreadcrumb(listItem ? listLabel(listItem) : '');
        });
    }

    if (academicPortfoliosTabBtn) {
        academicPortfoliosTabBtn.addEventListener('click', () => {
            setReportsMode('academic');
        });
    }

    if (extracurricularPortfoliosTabBtn) {
        extracurricularPortfoliosTabBtn.addEventListener('click', async () => {
            setReportsMode('extracurricular');

            const selected = state.selectedStudentForReports;
            if (!selected || Number(selected.studentId || 0) <= 0) {
                if (extracurricularPortfoliosContainer) {
                    extracurricularPortfoliosContainer.innerHTML = '<p class="members-empty">Select a student report first.</p>';
                }
                return;
            }

            if (extracurricularPortfoliosContainer) {
                extracurricularPortfoliosContainer.innerHTML = '<p class="members-empty">Loading extracurricular portfolios...</p>';
            }

            try {
                const data = await loadExtracurricularPortfolios(selected.studentId);
                if (data) {
                    renderExtracurricularPortfolios(data);
                }
            } catch (error) {
                if (extracurricularPortfoliosContainer) {
                    extracurricularPortfoliosContainer.innerHTML = '<p class="members-empty">Failed to load extracurricular portfolios.</p>';
                }
                alert(error.message || 'Failed to load extracurricular portfolios.');
            }
        });
    }

    if (listExtraFilesModalClose) {
        listExtraFilesModalClose.addEventListener('click', () => {
            closeExtracurricularFilesModal();
        });
    }

    if (listExtraFilesModal) {
        listExtraFilesModal.addEventListener('click', (event) => {
            if (event.target === listExtraFilesModal) {
                closeExtracurricularFilesModal();
            }
        });
    }

    (async () => {
        try {
            updateBreadcrumb('');
            const accessOk = await loadAccessContext();
            if (!accessOk) {
                return;
            }
            await loadLists();
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to load list management page.');
        }
    })();
});
