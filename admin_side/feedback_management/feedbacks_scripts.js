let feedbackSearchDebounce = null;
let currentSortOrder = 'desc';

const roleLabelMap = {
    all: 'All Roles',
    student: 'Student',
    professor: 'Professor',
    'program director': 'Program Director',
    'executive director': 'Executive Director'
};

const statusLabelMap = {
    all: 'All Status',
    new: 'New',
    reviewed: 'Reviewed',
    resolved: 'Resolved'
};

function initFeedbacksPage() {
    if (window.__adminFeedbacksInitialized) return;
    window.__adminFeedbacksInitialized = true;
    bindFeedbackFilters();
    loadFeedbacks();
    console.log('asdf');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFeedbacksPage);
    console.log('asdf1');
} else {
    initFeedbacksPage();
    console.log('asdf2');
}
    console.log('asdf2');

function bindFeedbackFilters() {
    const searchInput = document.getElementById('feedbackSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            currentSearch = (event.target.value || '').trim();
            if (feedbackSearchDebounce) {
                clearTimeout(feedbackSearchDebounce);
            }
            feedbackSearchDebounce = setTimeout(loadFeedbacks, 250);
        });
    }

    const sortBtn = document.getElementById('sortDateBtn');
    if (sortBtn) {
        sortBtn.addEventListener('click', () => {
            const icon = sortBtn.querySelector('i');
            const text = document.getElementById('sortDateText');
            
            if (currentSortOrder === 'desc') {
                currentSortOrder = 'asc';
                icon.className = 'fas fa-sort-amount-up';
                text.textContent = 'Oldest to Recent';
            } else {
                currentSortOrder = 'desc';
                icon.className = 'fas fa-sort-amount-down';
                text.textContent = 'Recent to Oldest';
            }
            loadFeedbacks();
        });
    }
}

function toggleFeedbackDropdown(id) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    dropdown.parentElement.classList.toggle('show');
}

function closeFeedbackDropdown(id) {
    const dropdown = document.getElementById(id);
    if (!dropdown) return;
    dropdown.parentElement.classList.remove('show');
}

function buildFeedbackQuery() {
    const params = new URLSearchParams();
    // Removed Role and Status params
    if (currentSearch !== '') params.set('search', currentSearch);
    params.set('sort', currentSortOrder);
    return params.toString();
}

function loadFeedbacks() {
    const query = buildFeedbackQuery();
    const url = query ? `get_feedbacks.php?${query}` : 'get_feedbacks.php';

    fetch(url, { credentials: 'same-origin' })
        .then(async (response) => {
            // Check if the response is actually OK before trying to parse JSON
            if (!response.ok) {
                console.error(`HTTP error! status: ${response.status}`);
            }
            
            // Get the raw text first instead of parsing JSON immediately
            const text = await response.text();
            
            try {
                const payload = JSON.parse(text);
                
                if (!payload.success) {
                    console.error("API returned false success:", payload.message);
                    renderFeedbacksTable([]);
                    return;
                }
                
                renderFeedbacksTable(payload.feedbacks || []);
            } catch (e) {
                // If it fails to parse as JSON, print the raw PHP output to the console!
                console.error("Failed to parse JSON. Raw PHP output was:", text);
                renderFeedbacksTable([]);
            }
        })
        .catch((error) => {
            console.error("Network Fetch Error:", error);
            renderFeedbacksTable([]);
        });
}

function renderFeedbacksTable(feedbacks) {
    const tableBody = document.getElementById('feedbackTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (!Array.isArray(feedbacks) || feedbacks.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8">No feedback found.</td></tr>';
        return;
    }

    feedbacks.forEach((item) => {
        const row = document.createElement('tr');
        const screenshotHtml = item.screenshot_path
            ? `<a class="feedback-screenshot-link" href="${resolveScreenshotUrl(item.screenshot_path)}" target="_blank" rel="noopener">View</a>`
            : '<span class="feedback-empty">-</span>';

        row.innerHTML = `
            <td>${escapeHtml(item.subject)}</td>
            <td class="feedback-message-cell">${escapeHtml(item.message)}</td>
            <td>${escapeHtml(item.full_name)}</td>
            <td>${escapeHtml(item.role_label)}</td>
            <td>${escapeHtml(item.user_email)}</td>
            <td>${screenshotHtml}</td>
            <td>${escapeHtml(item.created_at)}</td>
            <td>${formatStatusBadge(item.status)}</td>
        `;

        tableBody.appendChild(row);
    });
}

function resolveScreenshotUrl(path) {
    if (!path) return '#';
    if (path.startsWith('images/')) {
        return `../../${path}`;
    }
    return `../../images/feedbacks/${path}`;
}

function formatStatusBadge(status) {
    const normalized = String(status || '').toLowerCase();
    const label = statusLabelMap[normalized] || 'New';
    const className = `feedback-status status-${normalized || 'new'}`;
    return `<span class="${className}">${label}</span>`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
