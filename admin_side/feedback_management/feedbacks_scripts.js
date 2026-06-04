let feedbackSearchDebounce = null;
let feedbackSearch = '';
let feedbackSortColumn = 'f.created_at';
let feedbackSortOrder = 'desc';

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

window.addEventListener('click', (event) => {
    const modal = document.getElementById('feedbackMessageModal');
    if (event.target === modal) {
        closeFeedbackMessageModal();
    }
});

function initFeedbacksPage() {
    if (window.__adminFeedbacksInitialized) return;
    window.__adminFeedbacksInitialized = true;
    bindFeedbackFilters();
    loadFeedbacks();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFeedbacksPage);
} else {
    initFeedbacksPage();
}

function bindFeedbackFilters() {
    const searchInput = document.getElementById('feedbackSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (event) => {
            feedbackSearch = (event.target.value || '').trim();
            if (feedbackSearchDebounce) {
                clearTimeout(feedbackSearchDebounce);
            }
            feedbackSearchDebounce = setTimeout(loadFeedbacks, 250);
        });
    }

    const sortBtn = document.getElementById('sortDateBtn');
    if (sortBtn) {
        sortBtn.addEventListener('click', () => {
            toggleSort('f.created_at');
        });
    }
}

function toggleSort(column) {
    if (feedbackSortColumn === column) {
        if (feedbackSortOrder === 'asc') {
            feedbackSortOrder = 'desc';
        } else {
            // Cycle back to default (created_at desc)
            feedbackSortColumn = 'f.created_at';
            feedbackSortOrder = 'desc';
        }
    } else {
        feedbackSortColumn = column;
        feedbackSortOrder = 'asc';
    }

    updateSortIcons();
    loadFeedbacks();
}

function updateSortIcons() {
    document.querySelectorAll('thead th i.fas').forEach(icon => {
        icon.className = 'fas fa-sort';
        icon.style.opacity = '0.3';
    });

    const activeTh = document.querySelector(`thead th[onclick*="'${feedbackSortColumn}'"]`);
    if (activeTh) {
        const icon = activeTh.querySelector('i');
        if (icon) {
            icon.className = feedbackSortOrder === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
            icon.style.opacity = '1';
        }
    }

    // Update legacy sort button if it exists
    const sortBtn = document.getElementById('sortDateBtn');
    if (sortBtn) {
        const icon = sortBtn.querySelector('i');
        const text = document.getElementById('sortDateText');
        if (feedbackSortColumn === 'f.created_at') {
            if (icon) icon.className = feedbackSortOrder === 'asc' ? 'fas fa-sort-amount-up' : 'fas fa-sort-down';
            if (text) text.textContent = feedbackSortOrder === 'asc' ? 'Oldest to Recent' : 'Recent to Oldest';
        } else {
            if (icon) icon.className = 'fas fa-history';
            if (text) text.textContent = 'Reset to Default';
        }
    }
}

window.toggleSort = toggleSort;

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
    if (feedbackSearch !== '') params.set('search', feedbackSearch);
    params.set('sort', feedbackSortColumn);
    params.set('order', feedbackSortOrder);
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

        // Message is now a View link that opens a modal
        const messageHtml = `<a href="#" class="feedback-view-link" onclick="return openFeedbackMessageModal('${escapeHtml(item.subject)}', '${escapeHtml(item.message)}', '${escapeHtml(item.full_name)}')">View</a>`;

        row.innerHTML = `
            <td>${escapeHtml(item.subject)}</td>
            <td>${messageHtml}</td>
            <td>${escapeHtml(item.full_name)}</td>
            <td>${escapeHtml(item.role_label)}</td>
            <td>${escapeHtml(item.user_email)}</td>
            <td>${screenshotHtml}</td>
            <td>${escapeHtml(item.created_at)}</td>
            <td>${formatStatusBadge(item.is_new_submission)}</td>
        `;

        tableBody.appendChild(row);
    });
}

function openFeedbackMessageModal(subject, message, fullName) {
    const modal = document.getElementById('feedbackMessageModal');
    const subjectEl = document.getElementById('modalSubject');
    const submitterEl = document.getElementById('modalSubmitter');
    const messageEl = document.getElementById('modalMessage');

    if (modal && subjectEl && submitterEl && messageEl) {
        subjectEl.textContent = subject;
        submitterEl.textContent = `By: ${fullName}`;
        messageEl.textContent = message;
        modal.style.display = 'block';
    }
    return false;
}

function closeFeedbackMessageModal() {
    const modal = document.getElementById('feedbackMessageModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

window.openFeedbackMessageModal = openFeedbackMessageModal;
window.closeFeedbackMessageModal = closeFeedbackMessageModal;

function resolveScreenshotUrl(path) {
    if (!path) return '#';
    if (path.startsWith('images/')) {
        return `../../${path}`;
    }
    return `../../images/feedbacks/${path}`;
}

function formatStatusBadge(isNew) {
    const label = isNew ? 'New' : 'Old';
    const className = `feedback-status status-${isNew ? 'new' : 'old'}`;
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
