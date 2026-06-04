function escapeStudentClassHtml(value) {
	return String(value || '')
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#39;');
}

function buildStudentClassDisplay(entry) {
	const baseName = String(entry.className || '').trim();
	const term = String(entry.termNumber || '').trim();
	const years = `${String(entry.startYear || '').trim()}-${String(entry.endYear || '').trim()}`;
	if (/term\s*\d+/i.test(baseName) || baseName.includes(years)) {
		return baseName;
	}
	return `${baseName} Term ${term} ${years}`.trim();
}

function normalizeStudentClassSearch(entry) {
	return [
		entry.className,
		entry.courseName,
		entry.courseCode,
		entry.programName,
		entry.professorName
	].join(' ').toLowerCase();
}

document.addEventListener('DOMContentLoaded', () => {
	const pageType = (document.body && document.body.dataset.studentClassPage) || 'list';
	if (pageType === 'details') {
		initStudentClassDetailsPage();
		return;
	}
	initStudentClassListPage();
});

function initStudentClassListPage() {
	const joinClassToggleBtn = document.getElementById('toggleJoinClassBtn');
	const myPanelToggleAnchor = document.getElementById('myPanelToggleAnchor');
	const availablePanelToggleAnchor = document.getElementById('availablePanelToggleAnchor');
	const joinClassPanel = document.getElementById('joinClassPanel');
	const myClassesPanel = document.querySelector('.student-my-classes-panel');
	const classSearchInput = document.getElementById('classSearchInput');
	const globalClassesSearch = document.getElementById('globalClassesSearch');
	const programFilterSelect = document.getElementById('programFilterSelect');
	const availableClassList = document.getElementById('availableClassList');
	const myClassList = document.getElementById('myClassList');

	if (!availableClassList || !myClassList || !joinClassPanel) return;

	let availableClasses = [];
	let myClasses = [];
	let programOptions = [];
	let searchKeyword = '';
	let selectedProgramId = '';
	let isJoinPanelVisible = false;
	let actionToastTimer = null;

	function ensureStudentActionToast() {
		let toast = document.getElementById('studentClassActionToast');
		if (toast) return toast;

		toast = document.createElement('div');
		toast.id = 'studentClassActionToast';
		toast.className = 'student-class-action-toast hidden';
		document.body.appendChild(toast);
		return toast;
	}

	function showStudentActionToast(message, type = 'success') {
		if (!message) return;
		const toast = ensureStudentActionToast();
		toast.textContent = message;
		toast.classList.remove('hidden', 'success', 'error');
		toast.classList.add(type === 'error' ? 'error' : 'success');

		if (actionToastTimer) {
			clearTimeout(actionToastTimer);
		}

		actionToastTimer = setTimeout(() => {
			toast.classList.add('hidden');
		}, 2200);
	}

	function wait(ms) {
		return new Promise((resolve) => setTimeout(resolve, ms));
	}

	function updateJoinPanelState() {
		joinClassPanel.classList.toggle('hidden', !isJoinPanelVisible);
		if (myClassesPanel) {
			myClassesPanel.classList.toggle('hidden', isJoinPanelVisible);
		}
		if (joinClassToggleBtn) {
			const activeAnchor = isJoinPanelVisible ? availablePanelToggleAnchor : myPanelToggleAnchor;
			if (activeAnchor && joinClassToggleBtn.parentElement !== activeAnchor) {
				activeAnchor.appendChild(joinClassToggleBtn);
			}
			joinClassToggleBtn.textContent = isJoinPanelVisible ? 'Hide Join a Class' : 'Join a Class';
		}
	}

	function createClassCard(entry, mode) {
		const card = document.createElement('article');
		card.className = 'student-class-card';

		const classDisplay = buildStudentClassDisplay(entry);
		const professorName = entry.professorName || 'Not yet assigned';
		const programName = entry.programName || '';
		const status = String(entry.enrollmentStatus || '').toLowerCase();

		card.innerHTML = `
			<div class="student-class-card-icon"><i class="fas fa-user"></i></div>
			<div class="student-class-card-content">
				<h4>${escapeStudentClassHtml(classDisplay)}</h4>
				<p>${escapeStudentClassHtml(programName)}</p>
				<p>Instructor: ${escapeStudentClassHtml(professorName)}</p>
			</div>
			<div class="student-class-card-actions"></div>
		`;

		const actions = card.querySelector('.student-class-card-actions');

		const openClass = () => {
			window.location.href = `./student_class.html?class_id=${encodeURIComponent(entry.classId)}`;
		};

		if (mode === 'my') {
			const openBtn = document.createElement('button');
			openBtn.type = 'button';
			openBtn.className = 'student-class-btn primary';
			openBtn.textContent = 'Open Class';
			openBtn.addEventListener('click', openClass);
			actions.appendChild(openBtn);
			return card;
		}

		if (status === 'approved') {
			const statusTag = document.createElement('span');
			statusTag.className = 'student-class-status approved';
			statusTag.textContent = entry.invitationSource === 'invited' ? 'Invited and confirmed' : 'Enrolled';
			actions.appendChild(statusTag);

			const openBtn = document.createElement('button');
			openBtn.type = 'button';
			openBtn.className = 'student-class-btn primary';
			openBtn.textContent = 'Open Class';
			openBtn.addEventListener('click', openClass);
			actions.appendChild(openBtn);
			return card;
		}

		if (status === 'pending') {
			const pendingBtn = document.createElement('button');
			pendingBtn.type = 'button';
			pendingBtn.className = 'student-class-btn pending';
			pendingBtn.textContent = 'Pending';
			pendingBtn.disabled = true;
			actions.appendChild(pendingBtn);
			return card;
		}

		const enrollBtn = document.createElement('button');
		enrollBtn.type = 'button';
		enrollBtn.className = 'student-class-btn';
		enrollBtn.textContent = 'Enroll Class';
		enrollBtn.addEventListener('click', async () => {
			const originalLabel = enrollBtn.textContent;
			try {
				enrollBtn.disabled = true;
				enrollBtn.classList.add('is-loading');
				enrollBtn.textContent = 'Processing...';

				const body = new URLSearchParams({ class_id: String(entry.classId) });
				const response = await fetch('request_join_class.php', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				});
				const data = await response.json();
				if (!response.ok || !data.success) {
					throw new Error(data.message || 'Failed to submit enrollment request.');
				}

				enrollBtn.classList.remove('is-loading');
				enrollBtn.classList.add('is-done');
				enrollBtn.textContent = 'Done';
				showStudentActionToast('Enrollment request sent.');
				await wait(450);
				await reloadClassData();
			} catch (error) {
				enrollBtn.classList.remove('is-loading', 'is-done');
				enrollBtn.textContent = originalLabel;
				enrollBtn.disabled = false;
				showStudentActionToast(error.message || 'Failed to submit enrollment request.', 'error');
				alert(error.message || 'Failed to submit enrollment request.');
			}
		});

		const statusTag = document.createElement('span');
		statusTag.className = 'student-class-status available';
		statusTag.textContent = 'Available';

		actions.appendChild(statusTag);
		actions.appendChild(enrollBtn);

		return card;
	}

	function renderAvailableClasses() {
		availableClassList.innerHTML = '';

		const keyword = searchKeyword.trim().toLowerCase();
		const filtered = availableClasses.filter((entry) => {
			if (keyword === '') return true;
			return normalizeStudentClassSearch(entry).includes(keyword);
		});

		if (filtered.length === 0) {
			availableClassList.innerHTML = '<p class="student-class-empty">No classes found.</p>';
			return;
		}

		filtered.forEach((entry) => {
			availableClassList.appendChild(createClassCard(entry, 'available'));
		});
	}

	function renderMyClasses() {
		myClassList.innerHTML = '';

		if (!Array.isArray(myClasses) || myClasses.length === 0) {
			myClassList.innerHTML = '<p class="student-class-empty">You are not enrolled in any class yet.</p>';
			return;
		}

		myClasses.forEach((entry) => {
			myClassList.appendChild(createClassCard(entry, 'my'));
		});
	}

	function populateProgramFilter() {
		const current = selectedProgramId;
		programFilterSelect.innerHTML = '<option value="">All Programs</option>';

		const optionsToRender = Array.isArray(programOptions) && programOptions.length > 0
			? programOptions
			: Array.from(new Map(
				availableClasses
					.filter((entry) => Number(entry.programId) > 0)
					.map((entry) => [Number(entry.programId), entry.programName || `Program ${entry.programId}`])
			).entries()).map(([id, label]) => ({ programId: id, programName: label }));

		optionsToRender
			.sort((a, b) => String(a.programName || '').localeCompare(String(b.programName || '')))
			.forEach((optionEntry) => {
				const id = Number(optionEntry.programId || 0);
				const label = String(optionEntry.programName || `Program ${id}`);
				if (id <= 0) return;
				const option = document.createElement('option');
				option.value = String(id);
				option.textContent = label;
				if (String(current) === String(id)) option.selected = true;
				programFilterSelect.appendChild(option);
			});
	}

	async function reloadClassData() {
		const query = new URLSearchParams();
		if (selectedProgramId) {
			query.set('program_id', String(selectedProgramId));
		}

		const availableUrl = query.toString()
			? `fetch_available_classes.php?${query.toString()}`
			: 'fetch_available_classes.php';

		const availableResp = await fetch(availableUrl, { credentials: 'same-origin' });
		const availableData = await availableResp.json();
		if (!availableResp.ok || !availableData.success) {
			throw new Error(availableData.message || 'Failed to load available classes.');
		}

		const myResp = await fetch('fetch_student_classes.php', { credentials: 'same-origin' });
		const myData = await myResp.json();
		if (!myResp.ok || !myData.success) {
			throw new Error(myData.message || 'Failed to load student classes.');
		}

		availableClasses = Array.isArray(availableData.classes) ? availableData.classes : [];
		myClasses = Array.isArray(myData.classes) ? myData.classes : [];
		programOptions = Array.isArray(availableData.programOptions) ? availableData.programOptions : [];

		if (selectedProgramId && !programOptions.some((entry) => String(entry.programId) === String(selectedProgramId))) {
			selectedProgramId = '';
		}

		populateProgramFilter();
		renderAvailableClasses();
		renderMyClasses();
	}

	classSearchInput.addEventListener('input', () => {
		searchKeyword = classSearchInput.value;
		if (isJoinPanelVisible) {
			renderAvailableClasses();
		}
	});

	if (globalClassesSearch) {
		globalClassesSearch.addEventListener('input', () => {
			classSearchInput.value = globalClassesSearch.value;
			searchKeyword = globalClassesSearch.value;
			if (isJoinPanelVisible) {
				renderAvailableClasses();
			}
		});
	}

	programFilterSelect.addEventListener('change', () => {
		selectedProgramId = programFilterSelect.value;
		reloadClassData().catch((error) => {
			availableClassList.innerHTML = `<p class="student-class-empty">${escapeStudentClassHtml(error.message || 'Failed to load classes.')}</p>`;
		});
	});

	if (joinClassToggleBtn) {
		joinClassToggleBtn.addEventListener('click', () => {
			isJoinPanelVisible = !isJoinPanelVisible;
			updateJoinPanelState();
			if (isJoinPanelVisible) {
				renderAvailableClasses();
			}
		});
	}

	updateJoinPanelState();

	reloadClassData().catch((error) => {
		availableClassList.innerHTML = `<p class="student-class-empty">${escapeStudentClassHtml(error.message || 'Failed to load classes.')}</p>`;
		myClassList.innerHTML = `<p class="student-class-empty">${escapeStudentClassHtml(error.message || 'Failed to load classes.')}</p>`;
	});
}

function initStudentClassDetailsPage() {
	const params = new URLSearchParams(window.location.search);
	const classId = Number(params.get('class_id') || 0);

	const classNameEl = document.getElementById('studentClassName');
	const detailInstructor = document.getElementById('studentDetailInstructor');
	const detailProgram = document.getElementById('studentDetailProgram');
	const detailCourse = document.getElementById('studentDetailCourse');
	const detailClass = document.getElementById('studentDetailClass');
	const deadlineDisplay = document.getElementById('studentDeadlineDisplay');
	const requirementsList = document.getElementById('studentRequirementsList');
	const outputsList = document.getElementById('studentOutputsList');
	const outputSortToggleBtn = document.getElementById('studentOutputSortToggleBtn');
	const portfolioToggleBtn = document.getElementById('studentPortfolioToggleBtn');
	const portfolioReviewStatus = document.getElementById('studentPortfolioReviewStatus');
	const portfolioInfoWrap = document.querySelector('.student-portfolio-info-wrap');
	const difficultyRatingWrap = document.getElementById('studentDifficultyRatingWrap');
	const difficultyRatingButtons = Array.from(document.querySelectorAll('.student-difficulty-rating-btn'));
	const difficultyRatingStatus = document.getElementById('studentDifficultyRatingStatus');

	let studentOutputsCache = [];
	let isStudentOutputSortRecentFirst = true;
	let isPortfolioSubmitted = false;
	let portfolioReviewDecision = '';
	let portfolioReviewedAt = '';
	let selectedDifficultyRating = '';

	// Feedback logic for invite redirects
	if (params.get('already_joined') === '1') {
		alert("You are already a member of this class.");
	} else if (params.get('joined') === '1') {
		alert("Successfully joined the class!");
	}

	if (!classId) {
		alert('Missing class reference.');
		window.location.href = './student_classes.html';
		return;
	}

	const formatRules = {
		'.docx': ['docx'],
		'.pdf': ['pdf'],
		'.xlsx': ['xlsx'],
		'.png/.jpg': ['png', 'jpg', 'jpeg']
	};

	function isFormatAllowed(requiredFormat, fileName) {
		const format = String(requiredFormat || '').trim().toLowerCase();
		const extension = String(fileName || '').split('.').pop().trim().toLowerCase();
		const allowed = formatRules[format] || [];
		return allowed.includes(extension);
	}

	async function loadDetails() {
		const response = await fetch(`fetch_student_class_details.php?class_id=${encodeURIComponent(classId)}`, {
			credentials: 'same-origin'
		});
		const data = await response.json();

		if (!response.ok || !data.success) {
			throw new Error(data.message || 'Failed to load class details.');
		}

		const details = data.details || {};
		const displayName = buildStudentClassDisplay({
			className: details.class_name,
			termNumber: details.term_number,
			startYear: details.start_year,
			endYear: details.end_year
		});

		if (classNameEl) classNameEl.textContent = displayName;
		if (detailInstructor) detailInstructor.textContent = details.professor_name || 'Not yet assigned';
		if (detailProgram) detailProgram.textContent = details.program_name || '-';
		if (detailCourse) detailCourse.textContent = details.course_name || '-';
		if (detailClass) detailClass.textContent = details.class_name || '-';

		if (deadlineDisplay) {
			if (details.deadline_at && details.deadline_at !== '0000-00-00 00:00:00') {
				const deadline = new Date(String(details.deadline_at).replace(' ', 'T'));
				const dateStr = deadline.toISOString().slice(0, 10);
				const hours = deadline.getHours();
				const minutes = String(deadline.getMinutes()).padStart(2, '0');
				const period = hours >= 12 ? 'PM' : 'AM';
				const displayHours = hours % 12 || 12;
				deadlineDisplay.innerHTML = `<strong>Deadline:</strong> ${dateStr} at ${displayHours}:${minutes} ${period}`;
			} else {
				deadlineDisplay.innerHTML = '<strong>Deadline:</strong> Not set';
			}
		}
	}

	async function loadRequirements() {
		const response = await fetch(`fetch_student_class_requirements.php?class_id=${encodeURIComponent(classId)}`, {
			credentials: 'same-origin'
		});
		const data = await response.json();

		if (!response.ok || !data.success) {
			throw new Error(data.message || 'Failed to load requirements.');
		}

		requirementsList.innerHTML = '';
		const items = Array.isArray(data.requirements) ? data.requirements : [];

		if (items.length === 0) {
			requirementsList.innerHTML = '<li><span class="requirement-text">No requirements listed yet.</span></li>';
			return;
		}

		items.forEach((item) => {
			const li = document.createElement('li');
			li.innerHTML = `<span class="requirement-text">${escapeStudentClassHtml(item.requirementDesc || '')}</span>`;
			requirementsList.appendChild(li);
		});
	}

	function buildOutputItem(output) {
		const li = document.createElement('li');
		li.dataset.outputId = output.output_id;
		li.dataset.requiredFormat = output.required_file_format || '';

		const requiredFormatLabel = output.required_file_format || 'Not set';
		const isNoOutput = String(output.status || '').toLowerCase() === 'no_output' || output.submitted_file_name === 'No Output Submitted';
		const isSubmitted = String(output.status || '').toLowerCase() === 'submitted' && !isNoOutput;
		const isDone = isSubmitted || isNoOutput; // Determines if fields should be disabled
		const scoreValue = output.student_score !== null && output.student_score !== undefined ? output.student_score : '';
		const createdAtText = formatOutputDateTime(output.created_at);
		const updatedAtText = formatOutputDateTime(output.updated_at);

		li.innerHTML = `
			<div class="output-header">
				<span>
					${escapeStudentClassHtml(output.output_name)} (Total Score: ${escapeStudentClassHtml(output.total_score)})
					<label class="no-output-label" style="margin-left: 15px; font-size: 0.85em; cursor: pointer; color: #d9534f; user-select: none;">
						<input type="checkbox" class="no-output-checkbox" ${isNoOutput ? 'checked' : ''} ${isDone ? 'disabled' : ''}>
						No Output?
					</label>
				</span>
				<div><span class="required-format-chip">Required format: ${escapeStudentClassHtml(requiredFormatLabel)}</span></div>
			</div>
			<div class="output-date-meta">Created: ${escapeStudentClassHtml(createdAtText)} | Modified: ${escapeStudentClassHtml(updatedAtText)}</div>
			<div class="attach-output-row">
				<label class="attach-output-label">Attach required output</label>
				<input type="text" class="attach-output-name" placeholder="Attach required output" value="${escapeStudentClassHtml(output.submitted_file_name || '')}" readonly ${isDone ? 'disabled' : ''} />
				<button type="button" class="attach-output-btn" ${isDone ? 'disabled' : ''}>Browse</button>
				<input type="file" class="required-output-file-input" ${isDone ? 'disabled' : ''} />
			</div>
			<div class="input-and-buttons">
				<div class="score-input-row">
					<label class="score-input-label">Enter your score</label>
					<input type="number" placeholder="Enter your score" class="user-score" value="${escapeStudentClassHtml(scoreValue)}" ${isDone ? 'disabled' : ''} />
				</div>
				<div class="button-group">
					<button class="student-submit-btn" ${isDone ? 'style="display:none"' : ''}>Turn in</button>
					<button class="student-undo-btn" ${isDone ? '' : 'style="display:none"'}>Undo Turn in</button>
				</div>
			</div>
		`;

		return li;
	}

	function formatOutputDateTime(value) {
		if (!value || String(value).trim() === '') return 'N/A';
		const normalized = String(value).replace(' ', 'T');
		const date = new Date(normalized);
		if (Number.isNaN(date.getTime())) return 'N/A';

		const year = date.getFullYear();
		const month = String(date.getMonth() + 1).padStart(2, '0');
		const day = String(date.getDate()).padStart(2, '0');
		const hours = date.getHours();
		const minutes = String(date.getMinutes()).padStart(2, '0');
		const period = hours >= 12 ? 'PM' : 'AM';
		const displayHour = hours % 12 || 12;

		return `${year}-${month}-${day} ${displayHour}:${minutes} ${period}`;
	}

	function getOutputSortTimestamp(output) {
		const timestamp = output && output.created_at ? Date.parse(String(output.created_at).replace(' ', 'T')) : Number.NaN;
		return Number.isNaN(timestamp) ? 0 : timestamp;
	}

	function getSortedStudentOutputs(outputs) {
		return [...outputs].sort((a, b) => {
			const left = getOutputSortTimestamp(a);
			const right = getOutputSortTimestamp(b);
			return isStudentOutputSortRecentFirst ? right - left : left - right;
		});
	}

	function syncOutputSortToggleLabel() {
		if (!outputSortToggleBtn) return;
		outputSortToggleBtn.textContent = isStudentOutputSortRecentFirst ? 'Recent to Oldest' : 'Oldest to Recent';
	}

	function setDifficultyRatingStatus(text, type = '') {
		if (!difficultyRatingStatus) return;
		difficultyRatingStatus.textContent = text || '';
		difficultyRatingStatus.classList.remove('is-saved', 'is-error');
		if (type === 'saved') difficultyRatingStatus.classList.add('is-saved');
		if (type === 'error') difficultyRatingStatus.classList.add('is-error');
	}

	function syncDifficultyRatingUi(isSaving = false) {
		difficultyRatingButtons.forEach((btn) => {
			const btnRating = String(btn.dataset.rating || '').toLowerCase();
			btn.classList.toggle('is-selected', btnRating === selectedDifficultyRating);
			btn.disabled = isSaving;
		});
	}

	async function loadDifficultyRating() {
		if (!difficultyRatingWrap || difficultyRatingButtons.length === 0) return;

		const response = await fetch(`get_class_difficulty_rating.php?class_id=${encodeURIComponent(classId)}`, {
			credentials: 'same-origin'
		});
		const data = await response.json();

		if (!response.ok || !data.success) {
			throw new Error(data.message || 'Failed to load class difficulty rating.');
		}

		selectedDifficultyRating = String(data.rating || '').toLowerCase();
		syncDifficultyRatingUi(false);
		if (selectedDifficultyRating) {
			setDifficultyRatingStatus('Saved', 'saved');
		} else {
			setDifficultyRatingStatus('');
		}
	}

	async function saveDifficultyRating(rating) {
		if (!difficultyRatingWrap || !rating) return;

		const normalizedRating = String(rating).toLowerCase();
		selectedDifficultyRating = normalizedRating;
		syncDifficultyRatingUi(true);
		setDifficultyRatingStatus('Saving...');

		try {
			const body = new URLSearchParams({
				class_id: String(classId),
				difficulty_rating: normalizedRating
			});

			const response = await fetch('save_class_difficulty_rating.php', {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			});
			const data = await response.json();

			if (!response.ok || !data.success) {
				throw new Error(data.message || 'Failed to save class difficulty rating.');
			}

			selectedDifficultyRating = String(data.rating || normalizedRating).toLowerCase();
			syncDifficultyRatingUi(false);
			setDifficultyRatingStatus('Saved', 'saved');
		} catch (error) {
			syncDifficultyRatingUi(false);
			setDifficultyRatingStatus('Failed to save', 'error');
			alert(error.message || 'Failed to save class difficulty rating.');
		}
	}

	function syncPortfolioToggleLabel() {
		if (!portfolioToggleBtn) return;
		portfolioToggleBtn.textContent = isPortfolioSubmitted ? 'Undo Submit' : 'Submit Portfolio';
		portfolioToggleBtn.classList.toggle('is-undo-state', isPortfolioSubmitted);
	}

	function syncPortfolioReviewStatus() {
		if (!portfolioReviewStatus) return;

		const normalizedDecision = String(portfolioReviewDecision || '').toLowerCase();
		if (portfolioInfoWrap) {
			const hideInfo = normalizedDecision === 'approved' || normalizedDecision === 'rejected';
			portfolioInfoWrap.classList.toggle('hidden', hideInfo);
		}

		portfolioReviewStatus.classList.remove('hidden', 'is-approved', 'is-rejected');

		if (!isPortfolioSubmitted) {
			portfolioReviewStatus.classList.add('hidden');
			portfolioReviewStatus.innerHTML = '';
			return;
		}

		const reviewedAtText = portfolioReviewedAt ? formatOutputDateTime(portfolioReviewedAt) : '';
		if (normalizedDecision === 'approved') {
			portfolioReviewStatus.classList.add('is-approved');
			portfolioReviewStatus.innerHTML = `
				<span class="status-main">Approved <i class="fas fa-check-square" aria-hidden="true"></i></span>
				${reviewedAtText ? `<span class="status-time">${escapeStudentClassHtml(reviewedAtText)}</span>` : ''}
			`;
			return;
		}

		if (normalizedDecision === 'rejected') {
			portfolioReviewStatus.classList.add('is-rejected');
			portfolioReviewStatus.innerHTML = `
				<span class="status-main">Revised <i class="fas fa-exclamation-triangle" aria-hidden="true"></i></span>
				${reviewedAtText ? `<span class="status-time">${escapeStudentClassHtml(reviewedAtText)}</span>` : ''}
			`;
			return;
		}

		portfolioReviewStatus.innerHTML = '<span class="status-main">Submitted</span>';
	}

	async function togglePortfolioSubmission() {
		if (!portfolioToggleBtn) return;

		const endpoint = isPortfolioSubmitted ? 'undo_portfolio_submission.php' : 'submit_portfolio.php';
		const body = new URLSearchParams({ class_id: String(classId) });

		const originalLabel = portfolioToggleBtn.textContent;
		portfolioToggleBtn.disabled = true;
		portfolioToggleBtn.textContent = 'Processing...';

		try {
			const response = await fetch(endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString()
			});
			const data = await response.json();
			if (!response.ok || !data.success) {
				throw new Error(data.message || 'Failed to update portfolio submission status.');
			}

			const nextStatus = String(data.status || '').toLowerCase();
			if (nextStatus === 'submitted') {
				isPortfolioSubmitted = true;
			} else if (nextStatus === 'undone') {
				isPortfolioSubmitted = false;
			} else {
				isPortfolioSubmitted = !isPortfolioSubmitted;
			}

			if (!isPortfolioSubmitted) {
				portfolioReviewDecision = '';
				portfolioReviewedAt = '';
			}

			syncPortfolioToggleLabel();
			syncPortfolioReviewStatus();
		} catch (error) {
			portfolioToggleBtn.textContent = originalLabel;
			alert(error.message || 'Failed to update portfolio submission status.');
		} finally {
			portfolioToggleBtn.disabled = false;
			syncPortfolioToggleLabel();
			syncPortfolioReviewStatus();
		}
	}

	function renderOutputsList() {
		if (!outputsList) return;
		outputsList.innerHTML = '';

		if (!Array.isArray(studentOutputsCache) || studentOutputsCache.length === 0) {
			outputsList.innerHTML = '<li><span class="requirement-text">No required outputs yet.</span></li>';
			if (outputSortToggleBtn) {
				outputSortToggleBtn.disabled = true;
			}
			if (portfolioToggleBtn) {
				portfolioToggleBtn.disabled = true;
			}
			return;
		}

		const sortedOutputs = getSortedStudentOutputs(studentOutputsCache);
		sortedOutputs.forEach((entry) => {
			outputsList.appendChild(buildOutputItem(entry));
		});

		if (outputSortToggleBtn) {
			outputSortToggleBtn.disabled = false;
		}
		if (portfolioToggleBtn) {
			portfolioToggleBtn.disabled = false;
		}
	}

	async function loadOutputs() {
		const response = await fetch(`fetch_student_class_outputs.php?class_id=${encodeURIComponent(classId)}`, {
			credentials: 'same-origin'
		});
		const data = await response.json();

		if (!response.ok || !data.success) {
			throw new Error(data.message || 'Failed to load outputs.');
		}

		studentOutputsCache = Array.isArray(data.outputs) ? data.outputs : [];
		isPortfolioSubmitted = String(data.portfolio_status || '').toLowerCase() === 'submitted' || data.portfolio_submitted === true;
		portfolioReviewDecision = String(data.portfolio_review_decision || '').toLowerCase();
		portfolioReviewedAt = String(data.portfolio_reviewed_at || '').trim();
		syncPortfolioToggleLabel();
		syncPortfolioReviewStatus();
		renderOutputsList();
	}

	if (outputSortToggleBtn) {
		syncOutputSortToggleLabel();
		outputSortToggleBtn.addEventListener('click', () => {
			isStudentOutputSortRecentFirst = !isStudentOutputSortRecentFirst;
			syncOutputSortToggleLabel();
			renderOutputsList();
		});
	}

	if (portfolioToggleBtn) {
		syncPortfolioToggleLabel();
		syncPortfolioReviewStatus();
		portfolioToggleBtn.addEventListener('click', () => {
			togglePortfolioSubmission().catch((error) => {
				alert(error.message || 'Failed to update portfolio submission status.');
			});
		});
	}

	if (difficultyRatingWrap && difficultyRatingButtons.length > 0) {
		syncDifficultyRatingUi(false);
		difficultyRatingButtons.forEach((btn) => {
			btn.addEventListener('click', () => {
				const rating = String(btn.dataset.rating || '').toLowerCase();
				saveDifficultyRating(rating).catch((error) => {
					alert(error.message || 'Failed to save class difficulty rating.');
				});
			});
		});
	}

	outputsList.addEventListener('click', async (event) => {
		const target = event.target;
		const listItem = target.closest('li');
		if (!listItem) return;

		if (target.classList.contains('attach-output-btn') || target.classList.contains('attach-output-name')) {
			const inputFile = listItem.querySelector('.required-output-file-input');
			if (inputFile && !inputFile.disabled) {
				inputFile.click();
			}
			return;
		}

		if (target.classList.contains('student-submit-btn')) {
			const outputId = Number(listItem.dataset.outputId || 0);
			const requiredFormat = listItem.dataset.requiredFormat || '';
			const scoreInput = listItem.querySelector('.user-score');
			const fileInput = listItem.querySelector('.required-output-file-input');
			const attachName = listItem.querySelector('.attach-output-name');
			const noOutputCheckbox = listItem.querySelector('.no-output-checkbox');

			const isNoOutput = noOutputCheckbox && noOutputCheckbox.checked;
			const formData = new FormData();
			formData.append('output_id', String(outputId));

			// If "No Output" is checked, we skip score and file requirements
			if (isNoOutput) {
				formData.append('is_no_output', '1');
			} else {
				const score = scoreInput ? scoreInput.value.trim() : '';
				if (score === '') {
					alert('Please enter a score before submitting.');
					return;
				}

				if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
					alert('Please attach the required output before submitting.');
					return;
				}

				const selectedFile = fileInput.files[0];
				if (!isFormatAllowed(requiredFormat, selectedFile.name)) {
					alert(`Invalid file format. Required format is ${requiredFormat}.`);
					return;
				}

				formData.append('student_score', score);
				formData.append('attached_output', selectedFile);
			}

			try {
				const response = await fetch('submit_class_output.php', {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				});
				const data = await response.json();
				if (!response.ok || !data.success) {
					throw new Error(data.message || 'Failed to submit output.');
				}

				if (scoreInput) scoreInput.disabled = true;
				if (fileInput) fileInput.disabled = true;
				if (attachName) attachName.disabled = true;
				if (noOutputCheckbox) noOutputCheckbox.disabled = true; // Disable the checkbox after submission
				const attachBtn = listItem.querySelector('.attach-output-btn');
				if (attachBtn) attachBtn.disabled = true;
				target.style.display = 'none';
				const undoBtn = listItem.querySelector('.student-undo-btn');
				if (undoBtn) undoBtn.style.display = '';
			} catch (error) {
				alert(error.message || 'Failed to submit output.');
			}
			return;
		}

		if (target.classList.contains('student-undo-btn')) {
			const outputId = Number(listItem.dataset.outputId || 0);
			try {
				const body = new URLSearchParams({ output_id: String(outputId) });
				const response = await fetch('undo_class_output.php', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: body.toString()
				});
				const data = await response.json();
				if (!response.ok || !data.success) {
					throw new Error(data.message || 'Failed to undo submission.');
				}

				const scoreInput = listItem.querySelector('.user-score');
				const fileInput = listItem.querySelector('.required-output-file-input');
				const attachName = listItem.querySelector('.attach-output-name');
				const attachBtn = listItem.querySelector('.attach-output-btn');
				const noOutputCheckbox = listItem.querySelector('.no-output-checkbox');

				if (scoreInput) { scoreInput.disabled = false; scoreInput.value = ''; }
				if (fileInput) { fileInput.disabled = false; fileInput.value = ''; }
				if (attachName) { attachName.disabled = false; attachName.value = ''; }
				if (attachBtn) attachBtn.disabled = false;
				
				// Re-enable and uncheck the "No Output?" checkbox on Undo
				if (noOutputCheckbox) { 
					noOutputCheckbox.disabled = false; 
					noOutputCheckbox.checked = false; 
				}

				target.style.display = 'none';
				const submitBtn = listItem.querySelector('.student-submit-btn');
				if (submitBtn) submitBtn.style.display = '';
			} catch (error) {
				alert(error.message || 'Failed to undo submission.');
			}
			return;
		}
	});

	outputsList.addEventListener('change', (event) => {
		const target = event.target;
		// Logic to gray-out fields when "No Output?" is checked
		if (target.classList.contains('no-output-checkbox')) {
			const listItem = target.closest('li');
			const scoreInput = listItem.querySelector('.user-score');
			const fileInput = listItem.querySelector('.required-output-file-input');
			const attachBtn = listItem.querySelector('.attach-output-btn');
			const attachName = listItem.querySelector('.attach-output-name');
			const isChecked = target.checked;

			if (scoreInput) {
				scoreInput.disabled = isChecked;
				if (isChecked) scoreInput.value = '';
			}
			if (fileInput) {
				fileInput.disabled = isChecked;
				if (isChecked) fileInput.value = '';
			}
			if (attachBtn) attachBtn.disabled = isChecked;
			if (attachName) {
				attachName.disabled = isChecked;
				if (isChecked) attachName.value = 'No Output Selected';
				else attachName.value = '';
			}

			// AUTO-SUBMIT: Automatically click "Turn in" so the student doesn't have to!
			if (isChecked) {
				const submitBtn = listItem.querySelector('.student-submit-btn');
				if (submitBtn && submitBtn.style.display !== 'none') {
					submitBtn.click(); 
				}
			}
            
			return;
		}

		if (!target.classList.contains('required-output-file-input')) return;

		const listItem = target.closest('li');
		const attachName = listItem ? listItem.querySelector('.attach-output-name') : null;
		const requiredFormat = listItem ? listItem.dataset.requiredFormat || '' : '';

		if (!target.files || target.files.length === 0) {
			if (attachName) attachName.value = '';
			return;
		}

		const selected = target.files[0];
		if (!isFormatAllowed(requiredFormat, selected.name)) {
			alert(`Invalid file format. Required format is ${requiredFormat}.`);
			target.value = '';
			if (attachName) attachName.value = '';
			return;
		}

		if (attachName) attachName.value = selected.name;
	});

	Promise.all([loadDetails(), loadRequirements(), loadOutputs(), loadDifficultyRating()]).catch((error) => {
		alert(error.message || 'Failed to load class details.');
		window.location.href = './student_classes.html';
	});
}
