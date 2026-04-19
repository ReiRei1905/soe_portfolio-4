document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const classId = Number(params.get('class_id') || 0);

    const classNameEl = document.getElementById('reportsClassName');
    const portfolioCrumbEl = document.getElementById('reportsPortfolioCrumb');
    const tabPosts = document.getElementById('tabPosts');
    const tabMembers = document.getElementById('tabMembers');
    const tabReports = document.getElementById('tabReports');

    const reportsListView = document.getElementById('reportsListView');
    const reportsPortfolioView = document.getElementById('reportsPortfolioView');
    const reportsStudentsList = document.getElementById('reportsStudentsList');
    const reportsChartsCard = document.getElementById('reportsChartsCard');
    const highestPercentageChart = document.getElementById('highestPercentageChart');
    const highestGradeChart = document.getElementById('highestGradeChart');
    const difficultyRatingChart = document.getElementById('difficultyRatingChart');
    const highestPercentageChartTitle = document.getElementById('highestPercentageChartTitle');
    const highestGradeChartTitle = document.getElementById('highestGradeChartTitle');
    const difficultyRatingChartTitle = document.getElementById('difficultyRatingChartTitle');

    const reportsPortfolioHeading = document.getElementById('reportsPortfolioHeading');
    const portfolioStudentName = document.getElementById('portfolioStudentName');
    const portfolioStudentCourse = document.getElementById('portfolioStudentCourse');
    const portfolioStudentIdNumber = document.getElementById('portfolioStudentIdNumber');
    const portfolioStudentJoinedAt = document.getElementById('portfolioStudentJoinedAt');
    const portfolioOutputsList = document.getElementById('portfolioOutputsList');
    const portfolioFinalGradeInput = document.getElementById('portfolioFinalGradeInput');
    const portfolioFinalPercentInput = document.getElementById('portfolioFinalPercentInput');
    const portfolioRejectReasonRow = document.getElementById('portfolioRejectReasonRow');
    const portfolioRejectReasonInput = document.getElementById('portfolioRejectReasonInput');
    const approvePortfolioBtn = document.getElementById('approvePortfolioBtn');
    const rejectPortfolioBtn = document.getElementById('rejectPortfolioBtn');
    const portfolioReviewSummary = document.getElementById('portfolioReviewSummary');
    const reviewResultModal = document.getElementById('portfolioReviewResultModal');
    const reviewResultTitle = document.getElementById('portfolioReviewResultTitle');
    const reviewResultMessage = document.getElementById('portfolioReviewResultMessage');
    const reviewResultOkBtn = document.getElementById('portfolioReviewResultOkBtn');

    let classLabel = '';
    let reportsStudentsCache = [];
    let selectedPortfolioStudent = null;

    if (!classId) {
        alert('Missing class reference.');
        window.location.href = './classes.html';
        return;
    }

    if (tabPosts) tabPosts.href = `./class-handling.html?class_id=${encodeURIComponent(classId)}`;
    if (tabMembers) tabMembers.href = `./student_members.html?class_id=${encodeURIComponent(classId)}`;
    if (tabReports) tabReports.href = `./class_reports.html?class_id=${encodeURIComponent(classId)}`;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildClassDisplay(item) {
        const className = String(item.class_name || '').trim();
        const term = String(item.term_number || '').trim();
        const startYear = String(item.start_year || '').trim();
        const endYear = String(item.end_year || '').trim();
        if (/term\s*\d+/i.test(className) || (startYear && endYear && className.includes(`${startYear}-${endYear}`))) {
            return className;
        }
        return `${className} Term ${term} ${startYear}-${endYear}`.trim();
    }

    function formatStudentPortfolioCrumb(studentName) {
        const name = String(studentName || '').trim() || 'Student';
        return `/ ${name}'s Portfolio`;
    }

    function formatDateTime(value) {
        const raw = String(value || '').trim();
        if (!raw || raw === '0000-00-00 00:00:00') {
            return 'N/A';
        }

        const date = new Date(raw.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return escapeHtml(raw);
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

    function toggleReportsView(showPortfolioView) {
        if (reportsListView) {
            reportsListView.classList.toggle('hidden', showPortfolioView);
        }
        if (reportsPortfolioView) {
            reportsPortfolioView.classList.toggle('hidden', !showPortfolioView);
        }
        if (reportsChartsCard) {
            reportsChartsCard.classList.toggle('hidden', showPortfolioView);
        }

        const primaryCard = reportsListView ? reportsListView.closest('.reports-primary-card') : null;
        if (primaryCard) {
            primaryCard.classList.toggle('is-portfolio-view', showPortfolioView);
        }
    }

    function updateChartTitles() {
        const classTitle = classLabel || 'This Class';
        if (highestPercentageChartTitle) {
            highestPercentageChartTitle.textContent = `Academic Percentage per Student in ${classTitle}`;
        }
        if (highestGradeChartTitle) {
            highestGradeChartTitle.textContent = `Academic Grade per Student in ${classTitle}`;
        }
        if (difficultyRatingChartTitle) {
            difficultyRatingChartTitle.textContent = `Class Difficulty Ratings in ${classTitle}`;
        }
    }

    function renderDifficultyRatingChart(items) {
        if (!difficultyRatingChart) return;

        const rows = Array.isArray(items) ? items : [];
        const counts = { easy: 0, normal: 0, hard: 0 };

        rows.forEach((entry) => {
            const rating = String(entry.difficultyRating || '').toLowerCase().trim();
            if (rating === 'easy' || rating === 'normal' || rating === 'hard') {
                counts[rating] += 1;
            }
        });

        const totalResponses = counts.easy + counts.normal + counts.hard;
        if (totalResponses <= 0) {
            difficultyRatingChart.innerHTML = '<p class="reports-chart-empty">No student difficulty ratings yet.</p>';
            return;
        }

        const easyPercent = (counts.easy / totalResponses) * 100;
        const normalPercent = (counts.normal / totalResponses) * 100;
        const hardPercent = (counts.hard / totalResponses) * 100;

        difficultyRatingChart.innerHTML = `
            <div class="reports-difficulty-stack" role="img" aria-label="Difficulty ratings: ${counts.easy} easy, ${counts.normal} normal, ${counts.hard} hard">
                <span class="reports-difficulty-segment easy" style="width: ${easyPercent.toFixed(2)}%"></span>
                <span class="reports-difficulty-segment normal" style="width: ${normalPercent.toFixed(2)}%"></span>
                <span class="reports-difficulty-segment hard" style="width: ${hardPercent.toFixed(2)}%"></span>
            </div>
            <div class="reports-difficulty-breakdown">
                <div class="reports-difficulty-item">
                    <span class="reports-difficulty-dot easy"></span>
                    <span class="reports-difficulty-label">Easy</span>
                    <span class="reports-difficulty-value">${counts.easy} (${easyPercent.toFixed(1)}%)</span>
                </div>
                <div class="reports-difficulty-item">
                    <span class="reports-difficulty-dot normal"></span>
                    <span class="reports-difficulty-label">Normal</span>
                    <span class="reports-difficulty-value">${counts.normal} (${normalPercent.toFixed(1)}%)</span>
                </div>
                <div class="reports-difficulty-item">
                    <span class="reports-difficulty-dot hard"></span>
                    <span class="reports-difficulty-label">Hard</span>
                    <span class="reports-difficulty-value">${counts.hard} (${hardPercent.toFixed(1)}%)</span>
                </div>
            </div>
            <p class="reports-difficulty-total">${totalResponses} student${totalResponses === 1 ? '' : 's'} responded</p>
        `;
    }

    function parsePercentageValue(rawValue) {
        const normalized = String(rawValue === null || rawValue === undefined ? '' : rawValue).replace('%', '').trim();
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : Number.NaN;
    }

    function getGradeScore(rawGrade) {
        const grade = String(rawGrade || '').toUpperCase().trim();
        if (!grade) return Number.NaN;
        if (grade === 'R') return 0;
        const numeric = Number(grade);
        if (!Number.isFinite(numeric)) return Number.NaN;
        // Lower academic grade number is stronger: 1.0 best, 4.0 weakest.
        return Math.max(0, 5 - numeric);
    }

    function buildHorizontalChartHtml(rows, maxValue, formatValue) {
        if (!Array.isArray(rows) || rows.length === 0 || !Number.isFinite(maxValue) || maxValue <= 0) {
            return '<p class="reports-chart-empty">No approved review data yet.</p>';
        }

        return rows.map((entry) => {
            const widthPercent = Math.max(0, Math.min(100, (entry.metricValue / maxValue) * 100));
            return `
                <div class="reports-chart-row">
                    <div class="reports-chart-label">${escapeHtml(entry.studentName)}</div>
                    <div class="reports-chart-track">
                        <div class="reports-chart-fill" style="width: ${widthPercent.toFixed(2)}%"></div>
                        <span class="reports-chart-value">${escapeHtml(formatValue(entry))}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderAcademicCharts(items) {
        if (!highestPercentageChart || !highestGradeChart) return;

        const rows = Array.isArray(items) ? items : [];

        const percentageRows = rows
            .filter((entry) => String(entry.reviewDecision || '').toLowerCase() === 'approved')
            .map((entry) => {
                const studentName = `${entry.firstName || ''} ${entry.lastName || ''}`.trim() || 'Student';
                const metricValue = parsePercentageValue(entry.reviewFinalPercentage);
                return {
                    studentName,
                    metricValue,
                    rawPercentage: entry.reviewFinalPercentage
                };
            })
            .filter((entry) => Number.isFinite(entry.metricValue))
            .sort((a, b) => b.metricValue - a.metricValue);

        const gradeRows = rows
            .filter((entry) => String(entry.reviewDecision || '').toLowerCase() === 'approved')
            .map((entry) => {
                const studentName = `${entry.firstName || ''} ${entry.lastName || ''}`.trim() || 'Student';
                const gradeText = String(entry.reviewFinalGrade || '').toUpperCase().trim();
                const metricValue = getGradeScore(gradeText);
                return {
                    studentName,
                    metricValue,
                    gradeText
                };
            })
            .filter((entry) => Number.isFinite(entry.metricValue))
            .sort((a, b) => b.metricValue - a.metricValue);

        const maxPercentage = 100;
        const maxGradeScore = 4;

        highestPercentageChart.innerHTML = buildHorizontalChartHtml(
            percentageRows,
            maxPercentage,
            (entry) => `${Number(entry.metricValue).toFixed(1)}%`
        );

        highestGradeChart.innerHTML = buildHorizontalChartHtml(
            gradeRows,
            maxGradeScore,
            (entry) => entry.gradeText || 'N/A'
        );
    }

    function clearPortfolioView() {
        selectedPortfolioStudent = null;
        if (portfolioCrumbEl) {
            portfolioCrumbEl.textContent = '';
            portfolioCrumbEl.classList.add('hidden');
        }
        if (portfolioOutputsList) {
            portfolioOutputsList.innerHTML = '';
        }
        if (portfolioReviewSummary) {
            portfolioReviewSummary.textContent = '';
            portfolioReviewSummary.className = 'portfolio-review-summary hidden';
        }
        if (portfolioRejectReasonRow) {
            portfolioRejectReasonRow.classList.add('hidden');
        }
        if (portfolioRejectReasonInput) {
            portfolioRejectReasonInput.value = '';
        }
        [approvePortfolioBtn, rejectPortfolioBtn].forEach((btn) => {
            if (!btn) return;
            btn.classList.remove('hidden');
        });
    }

    function setRejectReasonVisibility(visible) {
        if (!portfolioRejectReasonRow) return;
        portfolioRejectReasonRow.classList.toggle('hidden', !visible);
    }

    function renderPortfolioReviewSummary(review) {
        if (!portfolioReviewSummary) return;

        if (!review || !review.decision) {
            portfolioReviewSummary.textContent = '';
            portfolioReviewSummary.className = 'portfolio-review-summary hidden';
            return;
        }

        const decision = String(review.decision || '').toLowerCase();
        const finalGrade = String(review.finalGrade || review.final_grade || '').trim();
        const finalPercentage = String(review.finalPercentage || review.final_percentage || '').trim();
        const rejectionReason = String(review.rejectionReason || review.rejection_reason || '').trim();
        const reviewedAt = formatDateTime(review.reviewedAt || review.reviewed_at);

        if (decision === 'rejected') {
            portfolioReviewSummary.textContent = rejectionReason
                ? `Portfolio was rejected. Reason: ${rejectionReason} | Date Accomplished: ${reviewedAt}`
                : `Portfolio was rejected. Date Accomplished: ${reviewedAt}`;
        } else {
            portfolioReviewSummary.textContent = `Final Grade: ${finalGrade || 'N/A'} | Percentage: ${finalPercentage ? `${finalPercentage}%` : 'N/A'} | Date Accomplished: ${reviewedAt}`;
        }
        portfolioReviewSummary.className = `portfolio-review-summary ${decision === 'approved' ? 'is-approved' : 'is-rejected'}`;
    }

    function renderPortfolioOutputs(outputs) {
        if (!portfolioOutputsList) return;

        if (!Array.isArray(outputs) || outputs.length === 0) {
            portfolioOutputsList.innerHTML = '<p class="portfolio-outputs-empty">No required outputs were found.</p>';
            return;
        }

        const rows = outputs.map((output, index) => {
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
        });

        portfolioOutputsList.innerHTML = rows.join('');
    }

    function setReviewButtonsState(isBusy) {
        [approvePortfolioBtn, rejectPortfolioBtn].forEach((btn) => {
            if (!btn) return;
            btn.disabled = isBusy;
        });
    }

    function syncReviewActionVisibility(review, submittedAtRaw) {
        const decision = String(review && review.decision || '').toLowerCase();
        const isApproved = decision === 'approved';

        const submittedAt = submittedAtRaw ? Date.parse(String(submittedAtRaw).replace(' ', 'T')) : Number.NaN;
        const reviewedAtRaw = review && (review.reviewedAt || review.reviewed_at);
        const reviewedAt = reviewedAtRaw ? Date.parse(String(reviewedAtRaw).replace(' ', 'T')) : Number.NaN;

        // Hide actions only if this exact submitted portfolio is already approved.
        // If the student has a newer submission than the approval timestamp,
        // actions should be available again for the next review cycle.
        const approvedForCurrentSubmission =
            isApproved
            && !Number.isNaN(submittedAt)
            && !Number.isNaN(reviewedAt)
            && submittedAt <= reviewedAt;

        [approvePortfolioBtn, rejectPortfolioBtn].forEach((btn) => {
            if (!btn) return;
            btn.classList.toggle('hidden', approvedForCurrentSubmission);
        });
    }

    const allowedFinalGrades = new Set(['1.0', '1.5', '2.0', '2.5', '3.0', '3.5', '4.0', 'R']);

    function showReviewResultModal(title, message) {
        if (!reviewResultModal || !reviewResultTitle || !reviewResultMessage || !reviewResultOkBtn) {
            return Promise.resolve();
        }

        reviewResultTitle.textContent = title || 'Portfolio Review Saved';
        reviewResultMessage.textContent = message || 'Portfolio review has been saved.';
        reviewResultModal.classList.remove('hidden');

        return new Promise((resolve) => {
            let done = false;
            const finish = () => {
                if (done) return;
                done = true;
                reviewResultModal.classList.add('hidden');
                reviewResultOkBtn.removeEventListener('click', finish);
                resolve();
            };

            reviewResultOkBtn.addEventListener('click', finish);
            setTimeout(finish, 1400);
        });
    }

    async function submitPortfolioReview(decision) {
        if (!selectedPortfolioStudent) {
            alert('Select a submitted student portfolio first.');
            return;
        }

        const finalGrade = (portfolioFinalGradeInput && portfolioFinalGradeInput.value || '').trim();
        const normalizedFinalGrade = finalGrade.toUpperCase();
        const finalPercentage = (portfolioFinalPercentInput && portfolioFinalPercentInput.value || '').trim();
        const rejectionReason = (portfolioRejectReasonInput && portfolioRejectReasonInput.value || '').trim();

        if (decision === 'approved' && (!finalGrade || !finalPercentage)) {
            alert('Please provide both Final Grade and Final Percentage.');
            return;
        }

        if (decision === 'approved' && !allowedFinalGrades.has(normalizedFinalGrade)) {
            alert('Final Grade must be one of: 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, or R.');
            return;
        }

        if (decision === 'approved' && portfolioFinalGradeInput) {
            portfolioFinalGradeInput.value = normalizedFinalGrade;
        }

        if (decision === 'rejected' && !rejectionReason) {
            alert('Please provide a rejection reason.');
            return;
        }

        const body = new URLSearchParams({
            class_id: String(classId),
            student_id: String(selectedPortfolioStudent.studentId),
            decision: String(decision),
            final_grade: normalizedFinalGrade,
            final_percentage: finalPercentage,
            rejection_reason: rejectionReason
        });

        setReviewButtonsState(true);
        try {
            const response = await fetch('review_portfolio_submission.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to review portfolio submission.');
            }

            renderPortfolioReviewSummary(data.review || null);
            if (decision !== 'approved') {
                await showReviewResultModal(
                    'Portfolio Rejected',
                    data.message || 'Portfolio review has been saved.'
                );
            }
            clearPortfolioView();
            toggleReportsView(false);
            await loadReports();
        } catch (error) {
            alert(error.message || 'Failed to review portfolio submission.');
        } finally {
            setReviewButtonsState(false);
        }
    }

    async function openPortfolioDetail(entry) {
        selectedPortfolioStudent = entry;
        if (reportsPortfolioHeading) {
            reportsPortfolioHeading.textContent = `${entry.studentName || 'Student'} Portfolio`;
        }
        if (portfolioCrumbEl) {
            portfolioCrumbEl.textContent = formatStudentPortfolioCrumb(entry.studentName || 'Student');
            portfolioCrumbEl.classList.remove('hidden');
        }

        toggleReportsView(true);

        try {
            const response = await fetch(
                `fetch_student_portfolio_report.php?class_id=${encodeURIComponent(classId)}&student_id=${encodeURIComponent(entry.studentId)}`,
                { credentials: 'same-origin' }
            );
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Failed to load student portfolio report.');
            }

            const student = data.student || {};
            const studentName = String(student.studentName || entry.studentName || 'Student');

            if (portfolioStudentName) portfolioStudentName.textContent = studentName;
            if (portfolioStudentCourse) portfolioStudentCourse.textContent = classLabel || 'Class Portfolio';
            if (portfolioStudentIdNumber) portfolioStudentIdNumber.textContent = student.idNumber || entry.idNumber || 'No ID';
            if (portfolioStudentJoinedAt) {
                portfolioStudentJoinedAt.textContent = `Joined at: ${formatDateTime(student.joinedAt)}`;
            }

            renderPortfolioOutputs(Array.isArray(data.outputs) ? data.outputs : []);

            if (portfolioFinalGradeInput) {
                portfolioFinalGradeInput.value = String(data.review && data.review.finalGrade || '');
            }
            if (portfolioFinalPercentInput) {
                const reviewPercent = String(data.review && data.review.finalPercentage || '').trim();
                portfolioFinalPercentInput.value = reviewPercent ? `${reviewPercent}%` : '';
            }
            if (portfolioRejectReasonInput) {
                portfolioRejectReasonInput.value = String(data.review && data.review.rejectionReason || '').trim();
            }

            setRejectReasonVisibility(false);

            renderPortfolioReviewSummary(data.review || null);
            syncReviewActionVisibility(data.review || null, student.portfolioSubmittedAt || null);
        } catch (error) {
            toggleReportsView(false);
            if (portfolioCrumbEl) {
                portfolioCrumbEl.textContent = '';
                portfolioCrumbEl.classList.add('hidden');
            }
            alert(error.message || 'Failed to load student portfolio report.');
        }
    }

    function renderReportsList(items) {
        if (!reportsStudentsList) return;
        reportsStudentsList.innerHTML = '';

        if (!Array.isArray(items) || items.length === 0) {
            reportsStudentsList.innerHTML = '<p class="reports-empty">No approved student members yet.</p>';
            return;
        }

        reportsStudentsCache = Array.isArray(items) ? items.slice() : [];

        reportsStudentsCache.forEach((entry) => {
            const fullName = `${entry.firstName || ''} ${entry.lastName || ''}`.trim() || 'Student';
            entry.studentName = fullName;
            const idNumber = String(entry.idNumber || 'No ID');
            const joinedAt = formatDateTime(entry.joinedAt);
            const submittedAt = formatDateTime(entry.portfolioSubmittedAt);
            const displayStatus = String(entry.portfolioDisplayStatus || '').toLowerCase();
            const hasPortfolio = displayStatus === 'submitted' || displayStatus === 'approved';

            let statusLabel = 'No Submission Yet';
            let statusClass = 'is-pending';
            if (displayStatus === 'submitted') {
                statusLabel = 'Submitted';
                statusClass = 'is-submitted';
            } else if (displayStatus === 'approved') {
                statusLabel = 'Approved';
                statusClass = 'is-approved';
            } else if (displayStatus === 'revised') {
                statusLabel = 'Revise Portfolio Status';
                statusClass = 'is-revised';
            }

            const card = document.createElement('article');
            card.className = 'report-student-card';

            card.innerHTML = `
                <div class="report-student-avatar"><i class="fas fa-user"></i></div>
                <div class="report-student-info">
                    <p class="report-student-name">${escapeHtml(fullName)}</p>
                    <p class="report-student-meta">${escapeHtml(idNumber)}</p>
                    <p class="report-student-meta">Date Join: ${escapeHtml(joinedAt)}</p>
                    ${hasPortfolio ? `<p class="report-student-meta">Submitted At: ${escapeHtml(submittedAt)}</p>` : ''}
                </div>
                <div class="report-student-actions">
                    <span class="report-status-pill ${statusClass}">
                        ${statusLabel}
                    </span>
                    ${hasPortfolio ? '<button type="button" class="report-portfolio-btn">Portfolio</button>' : ''}
                </div>
            `;

            const portfolioBtn = card.querySelector('.report-portfolio-btn');
            if (portfolioBtn) {
                portfolioBtn.addEventListener('click', () => {
                    openPortfolioDetail(entry).catch((error) => {
                        alert(error.message || 'Failed to open student portfolio.');
                    });
                });
            }

            reportsStudentsList.appendChild(card);
        });
    }

    async function loadClassHeader() {
        try {
            const response = await fetch('fetch_classes.php', { credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok || !data.success || !Array.isArray(data.classes)) return;
            const target = data.classes.find((entry) => Number(entry.class_id) === classId);
            if (target && classNameEl) {
                classLabel = buildClassDisplay(target);
                classNameEl.textContent = classLabel;
                updateChartTitles();
            }
        } catch (error) {
            console.warn('Unable to load class header:', error);
        }
    }

    async function loadReports() {
        const response = await fetch(`fetch_class_reports.php?class_id=${encodeURIComponent(classId)}`, {
            credentials: 'same-origin'
        });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load class reports.');
        }

        const students = Array.isArray(data.students) ? data.students : [];
        renderReportsList(students);
        renderAcademicCharts(students);
        renderDifficultyRatingChart(students);
        updateChartTitles();
    }

    if (approvePortfolioBtn) {
        approvePortfolioBtn.addEventListener('click', () => {
            setRejectReasonVisibility(false);
            submitPortfolioReview('approved').catch((error) => {
                alert(error.message || 'Failed to approve portfolio submission.');
            });
        });
    }

    if (rejectPortfolioBtn) {
        rejectPortfolioBtn.addEventListener('click', () => {
            const hiddenNow = portfolioRejectReasonRow && portfolioRejectReasonRow.classList.contains('hidden');
            if (hiddenNow) {
                setRejectReasonVisibility(true);
                if (portfolioRejectReasonInput) {
                    portfolioRejectReasonInput.focus();
                }
                return;
            }
            submitPortfolioReview('rejected').catch((error) => {
                alert(error.message || 'Failed to reject portfolio submission.');
            });
        });
    }

    Promise.all([loadClassHeader(), loadReports()]).catch((error) => {
        alert(error.message || 'Failed to load class reports.');
    });
});
