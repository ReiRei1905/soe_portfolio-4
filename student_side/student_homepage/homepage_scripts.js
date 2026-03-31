console.log("homepage_scripts.js loaded");

function handleNotificationClick() {
    const notificationDropdown = document.getElementById("notificationDropdown");
    const notificationBadge = document.getElementById("notificationBadge");

    if (notificationDropdown) {
        notificationDropdown.classList.toggle("hidden");

        if (!notificationDropdown.classList.contains("hidden") && notificationBadge) {
            notificationBadge.classList.add("hidden");
        }
    } else {
        console.warn("Notification dropdown element not found");
    }
}

function handleProfileClick() {
    // Lightweight placeholder used by header icon across pages
    // You can extend this to open a profile menu/modal later
    alert('Profile button clicked!');
}

function goToStudentOverviewPage() {
    const bodyHomeUrl = document.body ? (document.body.dataset.homeUrl || '').trim() : '';
    if (bodyHomeUrl) {
        window.location.href = bodyHomeUrl;
        return;
    }

    const currentPath = (window.location.pathname || '').toLowerCase();
    const extension = currentPath.endsWith('.php') ? 'php' : 'html';
    window.location.href = `../student_homepage/student_homepage.${extension}`;
}

/* Toggle sidebar in overview section (shared) */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    // Try to find the main content element for the current page. The student and
    // faculty pages use different ids (e.g. `homepage`, `programs`, `overview`),
    // so check the common ones here. If none found, fall back to the first
    // element with class "main-content" as a safe default.
    const mainContent = document.getElementById('overview')
        || document.getElementById('aboutUs')
        || document.getElementById('assessments')
        || document.getElementById('projects')
        || document.getElementById('certificationsAwards')
        || document.getElementById('classes')
        || document.getElementById('homepage')
        || document.getElementById('programs')
        || document.getElementById('courses')
        || document.querySelector('.main-content');

    if (!sidebar || !mainContent) return;
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
}

document.addEventListener("DOMContentLoaded", () => {
    // Position sidebar below header when present
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    if (header && sidebar) sidebar.style.top = `${header.offsetHeight}px`;

    // Close notification dropdown when clicking outside
    document.addEventListener("click", (event) => {
        const notificationDropdown = document.getElementById("notificationDropdown");
        const notificationIcon = document.querySelector(".header-notifications i");

        if (notificationDropdown && notificationIcon && !notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
            notificationDropdown.classList.add("hidden");
        }
    });

    // Close any dropdowns when clicking outside
    document.addEventListener("click", (event) => {
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target) && dropdown.previousElementSibling && !dropdown.previousElementSibling.contains(event.target)) {
                dropdown.classList.add("hidden");
            }
        });
    });

    // Populate the student name in the profile card if present.
    // Priority: server-rendered content (present on PHP page) -> localStorage 'studentName' -> body[data-student-name] -> fallback text.
    const studentNameEl = document.getElementById('studentName');
    if (studentNameEl) {
        const currentText = (studentNameEl.textContent || '').trim();
        if (currentText && currentText !== 'Student Name') {
            // Server already rendered the name (PHP), keep it.
        } else {
            const fromStorage = localStorage.getItem('studentName');
            const fromBody = document.body ? document.body.dataset.studentName : null;
            // Try server endpoint only if we don't have a name rendered already.
            fetch('../../user_info_V3/get_student_info.php', { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    const serverName = data && (data.name || '').trim();
                    if (serverName) {
                        studentNameEl.textContent = serverName;
                        try { localStorage.setItem('studentName', serverName); } catch (e) { }
                    } else {
                        studentNameEl.textContent = fromStorage || fromBody || 'Student Name';
                    }
                })
                .catch(() => {
                    studentNameEl.textContent = fromStorage || fromBody || 'Student Name';
                });
        }
    }
    // Restore saved page zoom if user changed it previously
    const savedZoom = parseInt(localStorage.getItem('studentPageZoom'), 10);
    if (savedZoom && typeof setZoom === 'function') setZoom(savedZoom);

    // Create Folder requires a selected category.
    document.querySelectorAll('#uploadDropdown input[name="category"]').forEach((radio) => {
        radio.addEventListener('change', updateCreateFolderButtonState);
    });
    configureUploadCategorySelection();
    updateCreateFolderButtonState();
    const contextCategory = getUploadContextCategory();
    if (contextCategory) {
        syncCategoryEntries(contextCategory).catch((error) => {
            console.error('Failed to load category entries:', error);
        });
    } else {
        updateFolderListUI();
    }

    const newCategoryInput = document.getElementById('newCategoryName');
    if (newCategoryInput) {
        newCategoryInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                createCategoryFromPopup();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeAddCategoryPopup();
            }
        });
    }

    const profileEditTrigger = document.getElementById('profileEditTrigger');
    if (profileEditTrigger) {
        profileEditTrigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            if (!document.body.classList.contains('customize-mode')) return;
            openProfileTextEditor();
        });
    }

    const quickGrid = document.querySelector('.quick-access-grid');
    if (quickGrid) {
        quickGrid.addEventListener('click', async (event) => {
            const editTrigger = event.target.closest('.quick-card-edit-trigger');
            if (!editTrigger) return;

            event.preventDefault();
            event.stopPropagation();
            if (!document.body.classList.contains('customize-mode')) return;

            const card = editTrigger.closest('.quick-card');
            if (!card) return;

            if (quickCardDeleteMode) {
                const titleEl = card.querySelector('.quick-title');
                const title = titleEl ? titleEl.textContent.trim() : 'this portfolio card';
                if (confirm(`Delete "${title}"?`)) {
                    const portfolioId = Number(card.dataset.portfolioId || 0);
                    if (portfolioId > 0) {
                        try {
                            const deleteData = new FormData();
                            deleteData.append('portfolio_id', String(portfolioId));
                            await callHomepageApi('delete_quick_card.php', {
                                method: 'POST',
                                body: deleteData
                            });
                        } catch (error) {
                            alert(error.message || 'Failed to delete portfolio card.');
                            return;
                        }
                    }
                    card.remove();
                }
                return;
            }

            openQuickCardTitleEditor(card, editTrigger);
        }, true);

        quickGrid.addEventListener('keydown', async (event) => {
            const editTrigger = event.target.closest('.quick-card-edit-trigger');
            if (!editTrigger) return;

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                event.stopPropagation();
                if (!document.body.classList.contains('customize-mode')) return;
                const card = editTrigger.closest('.quick-card');
                if (!card) return;

                if (quickCardDeleteMode) {
                    const titleEl = card.querySelector('.quick-title');
                    const title = titleEl ? titleEl.textContent.trim() : 'this portfolio card';
                    if (confirm(`Delete "${title}"?`)) {
                        const portfolioId = Number(card.dataset.portfolioId || 0);
                        if (portfolioId > 0) {
                            try {
                                const deleteData = new FormData();
                                deleteData.append('portfolio_id', String(portfolioId));
                                await callHomepageApi('delete_quick_card.php', {
                                    method: 'POST',
                                    body: deleteData
                                });
                            } catch (error) {
                                alert(error.message || 'Failed to delete portfolio card.');
                                return;
                            }
                        }
                        card.remove();
                    }
                    return;
                }

                openQuickCardTitleEditor(card, editTrigger);
            }
        });
    }

    const quickEditInput = document.getElementById('quickTitleEditInput');
    if (quickEditInput) {
        quickEditInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveQuickCardTitleChange();
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                cancelQuickCardTitleChange();
            }
        });
    }

    document.addEventListener('click', (event) => {
        const popup = document.getElementById('quickTitleEditPopup');
        if (!popup || popup.classList.contains('hidden')) return;

        const clickedOnTrigger = !!event.target.closest('.quick-card-edit-trigger');
        const clickedInsidePopup = popup.contains(event.target);
        if (!clickedOnTrigger && !clickedInsidePopup) {
            cancelQuickCardTitleChange();
        }
    });

    loadHomepageState();
});

// Canonical dropdown toggle (shared)
function toggleDropdown(icon) {
    if (!icon) return;
    const dropdown = icon.nextElementSibling;
    if (!dropdown) return;

    // Close other open dropdowns and reset aria-expanded on their triggers
    document.querySelectorAll('.dropdown').forEach((menu) => {
        if (menu !== dropdown) {
            menu.classList.add('hidden');
            const trigger = menu.previousElementSibling;
            if (trigger && trigger.setAttribute) trigger.setAttribute('aria-expanded', 'false');
        }
    });

    dropdown.classList.toggle('hidden');
    // reflect state on the triggering element for accessibility
    const expanded = !dropdown.classList.contains('hidden');
    if (icon && icon.setAttribute) icon.setAttribute('aria-expanded', expanded ? 'true' : 'false');
}

// In-memory storage for current session only (no persistence)
let customFolders = []; // Reset on refresh
let studentFiles = []; // Reset on refresh
let selectedFolderIndex = -1;
let selectedUploadFolderName = '';
let activeQuickCardForEdit = null;
let quickCardDeleteMode = false;
let currentCarouselItems = [];
let currentCarouselIndex = 0;
let activeQuickCardPortfolioId = 0;
let activeQuickCardPortfolioKey = '';
let activeQuickCardTitle = '';
let quickCardPickerCategory = 'all';
let quickCardPickerAvailableFiles = [];
let quickCardPickerSelectedFileIds = new Set();

const PORTFOLIO_API_BASE = '../api';
const FILE_ACCESS_DEDUPE_MS = 1200;
const recentFileAccessActions = new Map();

function callHomepageApi(endpoint, options) {
    return callPortfolioApi(endpoint, options);
}

function normalizePortfolioKey(rawKey, fallbackTitle) {
    const source = (rawKey || '').trim() || (fallbackTitle || '').trim();
    const normalized = source.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return normalized || 'portfolio';
}

function mapPortfolioKeyToCategory(key) {
    const normalized = String(key || '').trim().toLowerCase();
    if (normalized === 'assessments' || normalized === 'assessment') return 'assessment';
    if (normalized === 'projects' || normalized === 'project') return 'projects';
    if (normalized === 'certificates' || normalized === 'certificate') return 'certificates';
    return 'all';
}

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderQuickAccessCards(quickCards) {
    const grid = document.querySelector('.quick-access-grid');
    if (!grid) return;

    grid.innerHTML = '';

    if (!Array.isArray(quickCards) || quickCards.length === 0) {
        const fallbackCard = createQuickCard('Top projects', 'projects', null);
        grid.appendChild(fallbackCard);
        setQuickCardCornerMode(quickCardDeleteMode);
        return;
    }

    quickCards.forEach((card) => {
        const title = String(card && card.title ? card.title : 'Untitled portfolio');
        const key = normalizePortfolioKey(card && card.portfolioKey ? card.portfolioKey : '', title);
        const id = Number(card && card.id ? card.id : 0);
        const node = createQuickCard(title, key, id > 0 ? id : null);
        grid.appendChild(node);
    });

    setQuickCardCornerMode(quickCardDeleteMode);
}

async function loadHomepageState() {
    try {
        const payload = await callHomepageApi('get_homepage_state.php', { method: 'GET' });
        const profile = payload && payload.profile ? payload.profile : {};

        const studentNameEl = document.getElementById('studentName');
        const bioEl = document.getElementById('profileBioText') || document.querySelector('.profile-bio');

        const displayName = String(profile.displayName || '').trim();
        const accountName = String(profile.accountName || '').trim();
        const effectiveName = displayName || accountName;

        if (studentNameEl && effectiveName) {
            studentNameEl.textContent = effectiveName;
            try { localStorage.setItem('studentName', effectiveName); } catch (error) { }
        }

        if (bioEl) {
            const bio = String(profile.bio || '').trim();
            bioEl.textContent = bio || 'About the user';
        }

        renderQuickAccessCards(Array.isArray(payload.quickCards) ? payload.quickCards : []);
    } catch (error) {
        console.warn('Homepage state load failed:', error);
    }
}

function buildPortfolioFileAccessUrl(fileId, download) {
    const modeParam = download ? '&download=1' : '';
    return `${PORTFOLIO_API_BASE}/file_access.php?file_id=${encodeURIComponent(String(fileId))}${modeParam}`;
}

function isDuplicateFileAccessAction(fileId, download) {
    const actionKey = `${String(fileId)}:${download ? 'download' : 'open'}`;
    const now = Date.now();
    const previous = recentFileAccessActions.get(actionKey) || 0;

    if (now - previous < FILE_ACCESS_DEDUPE_MS) {
        return true;
    }

    recentFileAccessActions.set(actionKey, now);
    return false;
}

function openPortfolioFileEntry(fileId, download) {
    if (!fileId) return;
    if (isDuplicateFileAccessAction(fileId, !!download)) return;

    const targetUrl = buildPortfolioFileAccessUrl(fileId, !!download);

    // Use a single deterministic navigation path for downloads to avoid
    // accidental double requests caused by popup behavior/fallback.
    if (download) {
        window.location.assign(targetUrl);
        return;
    }

    // Avoid using "noopener" in the feature string because some browsers can
    // return null even when the tab opens, which caused duplicate fallback requests.
    let newTab = null;
    try {
        newTab = window.open(targetUrl, '_blank');
    } catch (error) {
        newTab = null;
    }

    if (newTab) {
        try {
            newTab.opener = null;
        } catch (error) {
            // Ignore cross-origin access restrictions.
        }
        return;
    }

    const fallbackDownloadUrl = buildPortfolioFileAccessUrl(fileId, true);
    window.location.assign(fallbackDownloadUrl);
}

window.openPortfolioFileEntry = openPortfolioFileEntry;

async function callPortfolioApi(endpoint, options) {
    const response = await fetch(`${PORTFOLIO_API_BASE}/${endpoint}`, {
        credentials: 'same-origin',
        ...options
    });

    const payload = await response.json().catch(() => ({ ok: false, message: 'Invalid server response.' }));
    if (!response.ok || !payload.ok) {
        throw new Error(payload.message || `Request failed for ${endpoint}`);
    }
    return payload;
}

function syncFoldersFromStudentFiles() {
    const folderSet = new Set();
    studentFiles.forEach((entry) => {
        if (entry && entry.entryType === 'folder' && entry.folder) {
            folderSet.add(entry.folder);
        }
    });
    customFolders = Array.from(folderSet);
}

async function syncCategoryEntries(categoryKey) {
    const payload = await callPortfolioApi(`list_entries.php?category=${encodeURIComponent(categoryKey)}`, {
        method: 'GET'
    });

    const incomingEntries = Array.isArray(payload.entries) ? payload.entries : [];
    studentFiles = (studentFiles || []).filter((entry) => entry.category !== categoryKey).concat(incomingEntries);
    syncFoldersFromStudentFiles();
    updateFolderListUI();
    return incomingEntries;
}

window.syncCategoryEntries = syncCategoryEntries;

async function renamePortfolioEntry(entryType, entryId, newName, categoryKey) {
    const formData = new FormData();
    formData.append('entry_type', entryType);
    formData.append('entry_id', String(entryId));
    formData.append('new_name', newName);
    if (categoryKey) formData.append('category', categoryKey);

    return callPortfolioApi('rename_entry.php', {
        method: 'POST',
        body: formData
    });
}

window.renamePortfolioEntry = renamePortfolioEntry;

async function deletePortfolioEntry(entryType, entryId, categoryKey) {
    const formData = new FormData();
    formData.append('entry_type', entryType);
    formData.append('entry_id', String(entryId));
    if (categoryKey) formData.append('category', categoryKey);

    return callPortfolioApi('delete_entry.php', {
        method: 'POST',
        body: formData
    });
}

window.deletePortfolioEntry = deletePortfolioEntry;

function getSelectedUploadCategory() {
    return document.querySelector('#uploadDropdown input[name="category"]:checked');
}

function getUploadContextCategory() {
    const contextFromBody = document.body ? (document.body.dataset.uploadContext || '').trim() : '';

    if (contextFromBody === 'assessment' || contextFromBody === 'projects' || contextFromBody === 'certificates') {
        return contextFromBody;
    }

    if (contextFromBody === 'homepage') {
        return null;
    }

    const path = (window.location.pathname || '').toLowerCase();
    if (path.includes('/assessment/')) return 'assessment';
    if (path.includes('/projects/')) return 'projects';
    if (path.includes('/certificates/')) return 'certificates';
    return null;
}

function configureUploadCategorySelection() {
    const radios = Array.from(document.querySelectorAll('#uploadDropdown input[name="category"]'));
    if (radios.length === 0) return;

    const categoryArea = document.querySelector('#uploadDropdown .category-selection-area');
    const fixedCategory = getUploadContextCategory();

    radios.forEach((radio) => {
        const option = radio.closest('.category-option');

        if (!fixedCategory) {
            radio.disabled = false;
            if (option) option.classList.remove('is-disabled');
            return;
        }

        const isAllowed = radio.value === fixedCategory;
        radio.disabled = !isAllowed;
        radio.checked = isAllowed;
        if (option) option.classList.toggle('is-disabled', !isAllowed);
    });

    if (categoryArea) {
        categoryArea.classList.toggle('is-context-locked', !!fixedCategory);
    }
}

function updateCreateFolderButtonState() {
    const createBtn = document.getElementById('btnCreateFolder') || document.querySelector('#customFolderPopup .btn-folder-create');
    if (!createBtn) return;

    const hasCategory = !!getSelectedUploadCategory();
    createBtn.disabled = !hasCategory;
    createBtn.classList.toggle('is-disabled', !hasCategory);
    createBtn.setAttribute('aria-disabled', hasCategory ? 'false' : 'true');
}

function getCurrentOpenFolderFromSectionView() {
    const context = getUploadContextCategory();
    if (!context) return '';

    if (context === 'projects' && typeof window.getCurrentProjectsFolderName === 'function') {
        const activeFolder = String(window.getCurrentProjectsFolderName() || '').trim();
        if (activeFolder) return activeFolder;
    }

    if (context === 'certificates' && typeof window.getCurrentCertificatesFolderName === 'function') {
        const activeFolder = String(window.getCurrentCertificatesFolderName() || '').trim();
        if (activeFolder) return activeFolder;
    }

    const folderCrumbIdByContext = {
        assessment: 'assessmentBreadcrumbFolder',
        projects: 'projectsBreadcrumbFolder',
        certificates: 'certificatesBreadcrumbFolder'
    };

    const crumbId = folderCrumbIdByContext[context];
    if (!crumbId) return '';

    const crumbEl = document.getElementById(crumbId);
    if (!crumbEl || crumbEl.classList.contains('hidden')) return '';

    return (crumbEl.textContent || '').trim();
}

// (Remove these global storage markers if they were used for identification)
// let customFolders = JSON.parse(localStorage.getItem('studentCustomFolders') || '[]');
// let studentFiles = JSON.parse(localStorage.getItem('studentFiles') || '[]');


function closeUploadModal() {
    const upload = document.getElementById('uploadDropdown');
    if (upload) upload.classList.add('hidden');
    // Clear input on close
    const fileNameInput = document.getElementById('uploadFileName');
    const fileInput = document.getElementById('hiddenFileInput');
    if (fileNameInput) fileNameInput.value = '';
    if (fileInput) fileInput.value = ''; // Reset the actual file selection
    
    // Also hide custom folder popup if open
    hideCustomFolderPopup();
    hideExistingFolderPopup();
    selectedUploadFolderName = '';
    updateCreateFolderButtonState();
}

function triggerFileExplorer() {
    const fileInput = document.getElementById('hiddenFileInput');
    if (fileInput) {
        fileInput.click();
    }
}

function handleFileSelection(input) {
    const fileNameInput = document.getElementById('uploadFileName');
    if (input.files && input.files.length > 0) {
        // Get the name of the first file selected
        const selectedFileName = input.files[0].name;
        // Strip the extension if you just want the name, or keep it
        if (fileNameInput) {
            fileNameInput.value = selectedFileName;
        }
    }
}

function handleCustomFolder() {
    // Hide existing folder popup if it's open
    hideExistingFolderPopup(false);
    const popup = document.getElementById('customFolderPopup');
    if (popup) {
        updateCreateFolderButtonState();
        popup.classList.toggle('hidden');
        if (!popup.classList.contains('hidden')) {
            document.getElementById('customFolderName').focus();
        }
    }
}

function hideCustomFolderPopup() {
    const popup = document.getElementById('customFolderPopup');
    const input = document.getElementById('customFolderName');
    if (popup) popup.classList.add('hidden');
    if (input) input.value = '';
}

async function createCustomFolder() {
    const nameInput = document.getElementById('customFolderName');
    const selectedCategoryEl = getSelectedUploadCategory();

    if (!selectedCategoryEl) {
        alert('Please choose a category first (Assessments, Projects, or Certificates/Awards).');
        updateCreateFolderButtonState();
        return;
    }

    if (nameInput && nameInput.value.trim()) {
        const folderName = nameInput.value.trim();
        const category = selectedCategoryEl.value;

        try {
            const formData = new FormData();
            formData.append('category', category);
            formData.append('folder_name', folderName);
            await callPortfolioApi('create_folder.php', {
                method: 'POST',
                body: formData
            });

            selectedUploadFolderName = folderName;
            await syncCategoryEntries(category);
            alert(`Folder "${folderName}" created in ${category}.`);
            closeUploadModal();

            if (typeof renderCurrentSection === 'function') {
                renderCurrentSection();
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to create folder.');
        }
    } else {
        alert('Please enter a folder name.');
    }
}

function handleExistingFolder() {
    // Hide custom folder popup if it's open
    hideCustomFolderPopup();
    const popup = document.getElementById('existingFolderPopup');
    if (popup) {
        const selectedCategory = getSelectedUploadCategory();
        const categoryKey = selectedCategory ? selectedCategory.value : getUploadContextCategory();
        if (categoryKey) {
            syncCategoryEntries(categoryKey).catch((error) => {
                console.error('Failed to refresh folders:', error);
            });
        }
        popup.classList.toggle('hidden');
    }
}

function hideExistingFolderPopup(resetPickerSelection) {
    const popup = document.getElementById('existingFolderPopup');
    if (popup) popup.classList.add('hidden');
    if (resetPickerSelection !== false) {
        selectedFolderIndex = -1;
    }
    updateFolderListUI();
}

function updateFolderListUI() {
    const container = document.getElementById('folderListContainer');
    if (!container) return;

    if (customFolders.length === 0) {
        container.innerHTML = '<p class="text-gray-400 text-xs text-center py-4">No folders created yet.</p>';
        return;
    }

    container.innerHTML = '';
    customFolders.forEach((folder, index) => {
        const div = document.createElement('div');
        div.className = `folder-item ${selectedFolderIndex === index ? 'selected' : ''}`;
        
        // Add the folder icon
        const icon = document.createElement('i');
        icon.className = 'fas fa-folder';
        
        // Add the folder name span
        const span = document.createElement('span');
        span.textContent = folder;
        
        div.appendChild(icon);
        div.appendChild(span);
        
        div.onclick = () => {
            selectedFolderIndex = index;
            updateFolderListUI();
        };
        container.appendChild(div);
    });
}

function selectFolder() {
    if (selectedFolderIndex === -1) {
        alert('Please select a folder from the list first.');
        return;
    }
    const folderName = customFolders[selectedFolderIndex];
    selectedUploadFolderName = folderName;
    alert(`Target folder set to: ${folderName}`);
    hideExistingFolderPopup();
}

async function addFile() {
    const fileNameInput = document.getElementById('uploadFileName');
    const fileInput = document.getElementById('hiddenFileInput');
    const selectedCategoryEl = getSelectedUploadCategory();
    const customFolderNameInput = document.getElementById('customFolderName');
    
    // Check if we have a file name OR we are intending to just create/use a folder
    const fileName = fileNameInput ? fileNameInput.value.trim() : '';
    const selectedFile = fileInput && fileInput.files && fileInput.files.length > 0 ? fileInput.files[0] : null;
    
    // Logic: User must provide a File Name OR have a folder selected/created
    // But the user specifically asked: "can upload a folder without a file name"
    // AND "must not be able to upload if not entering a file OR adding a custom folder"
    
    // Use the folder from the input field IF it has a value, regardless of whether "Create" was clicked
    const customFolderValue = (customFolderNameInput ? customFolderNameInput.value.trim() : '');
    
    const folderFromPicker = selectedUploadFolderName || (selectedFolderIndex !== -1 ? customFolders[selectedFolderIndex] : '');
    const folderFromCurrentSectionView = getCurrentOpenFolderFromSectionView();
    const folderName = customFolderValue || folderFromPicker || folderFromCurrentSectionView;
    const hasFolder = !!folderName;

    if (!fileName && !selectedFile && !hasFolder) {
        alert('Please enter/select a file name or create/select a folder.');
        return;
    }

    if (!selectedCategoryEl) {
        alert('Please choose a category before continuing.');
        return;
    }
    
    const category = selectedCategoryEl.value;

    try {
        if (!fileName && !selectedFile && hasFolder) {
            const createFolderData = new FormData();
            createFolderData.append('category', category);
            createFolderData.append('folder_name', folderName);
            await callPortfolioApi('create_folder.php', {
                method: 'POST',
                body: createFolderData
            });
            await syncCategoryEntries(category);
            alert(`Folder "${folderName}" created in ${category}!`);
        } else {
            if (!selectedFile) {
                alert('Please select a real file using the upload icon before pressing Done.');
                return;
            }

            const uploadData = new FormData();
            uploadData.append('category', category);
            uploadData.append('display_name', fileName || (selectedFile ? selectedFile.name : 'Untitled File'));
            if (folderName) uploadData.append('folder_name', folderName);
            if (selectedFile) uploadData.append('file', selectedFile);

            await callPortfolioApi('upload_file.php', {
                method: 'POST',
                body: uploadData
            });

            await syncCategoryEntries(category);
            alert(`File "${fileName || (selectedFile ? selectedFile.name : 'Untitled File')}" uploaded${folderName ? ` to "${folderName}"` : ''} in ${category}!`);
        }
    } catch (error) {
        console.error(error);
        alert(error.message || 'Failed to save upload.');
        return;
    }

    
    // Reset selection and close
    selectedFolderIndex = -1;
    selectedUploadFolderName = '';
    if (customFolderNameInput) customFolderNameInput.value = ''; // Clear input
    closeUploadModal();
    
    if (typeof renderCurrentSection === 'function') {
        renderCurrentSection();
    } else {
        location.reload();
    }
}

// Simple zoom in/out controls that update the page zoom percentage shown in the profile dropdown
function setZoom(percent) {
    const clamped = Math.max(50, Math.min(200, percent));
    document.body.style.zoom = clamped / 100;
    const el = document.getElementById('zoomPercentage');
    if (el) el.textContent = clamped + '%';
    // persist for next navigation
    try { localStorage.setItem('studentPageZoom', String(clamped)); } catch (e) { /* ignore */ }
}

function zoomIn() {
    const el = document.getElementById('zoomPercentage');
    const current = el ? parseInt(el.textContent, 10) || 100 : (parseInt(localStorage.getItem('studentPageZoom')) || 100);
    setZoom(current + 10);
}

function zoomOut() {
    const el = document.getElementById('zoomPercentage');
    const current = el ? parseInt(el.textContent, 10) || 100 : (parseInt(localStorage.getItem('studentPageZoom')) || 100);
    setZoom(current - 10);
}

/* ---------- Customize controls (header) ---------- */
function toggleCustomize() {
    const controls = document.getElementById('customizeControls');
    if (!controls) return;

    // Close other dropdowns first
    document.querySelectorAll('.dropdown').forEach(d => d.classList.add('hidden'));
    // Toggle visibility and aria attributes
    const isHidden = controls.classList.contains('hidden');
    if (isHidden) {
        controls.classList.remove('hidden');
        controls.classList.add('show');
        controls.setAttribute('aria-hidden', 'false');
        document.body.classList.add('customize-mode');
        quickCardDeleteMode = false;
        setQuickCardCornerMode(false);
    } else {
        controls.classList.add('hidden');
        controls.classList.remove('show');
        controls.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('customize-mode');
        quickCardDeleteMode = false;
        setQuickCardCornerMode(false);
        closeAddCategoryPopup();
        cancelProfileTextChanges();
        cancelQuickCardTitleChange();
    }
}

function setQuickCardCornerMode(isDeleteMode) {
    const triggers = document.querySelectorAll('.quick-card-edit-trigger');
    triggers.forEach((trigger) => {
        const icon = trigger.querySelector('i');
        trigger.classList.toggle('is-delete-trigger', !!isDeleteMode);
        trigger.setAttribute('aria-label', isDeleteMode ? 'Delete portfolio card' : 'Edit portfolio title');
        trigger.dataset.actionMode = isDeleteMode ? 'delete' : 'edit';

        if (icon) {
            icon.className = isDeleteMode ? 'fas fa-trash' : 'fas fa-pen-square';
        }
    });

    const deleteBtn = document.querySelector('#customizeControls .control-delete');
    if (deleteBtn) {
        deleteBtn.classList.toggle('is-active', !!isDeleteMode);
        deleteBtn.setAttribute('title', isDeleteMode ? 'Exit delete mode' : 'Delete category');
    }
}

function openProfileTextEditor() {
    const panel = document.getElementById('profileEditPanel');
    if (!panel) return;

    const nameEl = document.getElementById('studentName');
    const bioEl = document.getElementById('profileBioText') || document.querySelector('.profile-bio');
    const nameInput = document.getElementById('profileNameInput');
    const bioInput = document.getElementById('profileBioInput');

    if (nameInput) nameInput.value = nameEl ? (nameEl.textContent || '').trim() : '';
    if (bioInput) bioInput.value = bioEl ? (bioEl.textContent || '').trim() : '';

    panel.classList.remove('hidden');
    if (nameInput) nameInput.focus();
}

async function saveProfileTextChanges() {
    const nameInput = document.getElementById('profileNameInput');
    const bioInput = document.getElementById('profileBioInput');
    const nameEl = document.getElementById('studentName');
    const bioEl = document.getElementById('profileBioText') || document.querySelector('.profile-bio');

    const nextName = nameInput ? nameInput.value.trim() : '';
    const nextBio = bioInput ? bioInput.value.trim() : '';

    if (!nextName) {
        alert('Name cannot be empty.');
        if (nameInput) nameInput.focus();
        return;
    }

    try {
        const payload = new FormData();
        payload.append('display_name', nextName);
        payload.append('bio', nextBio);
        await callHomepageApi('save_homepage_profile.php', {
            method: 'POST',
            body: payload
        });
    } catch (error) {
        alert(error.message || 'Failed to save profile.');
        return;
    }

    if (nameEl) nameEl.textContent = nextName;
    if (bioEl) bioEl.textContent = nextBio || 'About the user';
    try { localStorage.setItem('studentName', nextName); } catch (error) { }

    cancelProfileTextChanges();
}

function cancelProfileTextChanges() {
    const panel = document.getElementById('profileEditPanel');
    if (panel) panel.classList.add('hidden');
}

function openQuickCardTitleEditor(card, triggerElement) {
    activeQuickCardForEdit = card;
    const titleEl = card ? card.querySelector('.quick-title') : null;
    const popup = document.getElementById('quickTitleEditPopup');
    const input = document.getElementById('quickTitleEditInput');

    if (!popup || !input || !titleEl) return;

    input.value = (titleEl.textContent || '').trim();

    const anchor = triggerElement || card;
    const rect = anchor.getBoundingClientRect();
    const popupWidth = 250;
    const left = Math.max(12, Math.min(window.innerWidth - popupWidth - 12, rect.right - popupWidth));
    const top = Math.max(12, Math.min(window.innerHeight - 120, rect.bottom + 8));

    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;
    popup.classList.remove('hidden');
    input.focus();
    input.select();
}

async function saveQuickCardTitleChange() {
    const popup = document.getElementById('quickTitleEditPopup');
    const input = document.getElementById('quickTitleEditInput');
    if (!popup || !input || !activeQuickCardForEdit) return;

    const nextTitle = input.value.trim();
    if (!nextTitle) {
        alert('Portfolio title cannot be empty.');
        input.focus();
        return;
    }

    const portfolioId = Number(activeQuickCardForEdit.dataset.portfolioId || 0);
    if (portfolioId > 0) {
        try {
            const payload = new FormData();
            payload.append('portfolio_id', String(portfolioId));
            payload.append('title', nextTitle);
            await callHomepageApi('update_quick_card.php', {
                method: 'POST',
                body: payload
            });
        } catch (error) {
            alert(error.message || 'Failed to update portfolio title.');
            return;
        }
    }

    const titleEl = activeQuickCardForEdit.querySelector('.quick-title');
    if (titleEl) titleEl.textContent = nextTitle;

    cancelQuickCardTitleChange();
}

function cancelQuickCardTitleChange() {
    const popup = document.getElementById('quickTitleEditPopup');
    if (popup) popup.classList.add('hidden');
    activeQuickCardForEdit = null;
}

function addCategory() {
    const popup = document.getElementById('addCategoryPopup');
    if (!popup) return;

    if (!popup.classList.contains('hidden')) {
        closeAddCategoryPopup(false);
        return;
    }

    popup.classList.remove('hidden');
    const input = document.getElementById('newCategoryName');
    if (input) {
        input.value = '';
        input.focus();
    }
}

function closeAddCategoryPopup(clearInput = true) {
    const popup = document.getElementById('addCategoryPopup');
    if (popup) popup.classList.add('hidden');

    if (clearInput) {
        const input = document.getElementById('newCategoryName');
        if (input) input.value = '';
    }
}

async function createCategoryFromPopup() {
    const input = document.getElementById('newCategoryName');
    const name = input ? input.value : '';
    if (!name || !name.trim()) {
        alert('Portfolio name cannot be empty.');
        if (input) input.focus();
        return;
    }

    const trimmed = name.trim();

    const grid = document.querySelector('.quick-access-grid');
    if (!grid) {
        // Fallback: if the standard grid isn't available, try the fileTileContainer
        const fallback = document.getElementById('fileTileContainer') || document.getElementById('fileCategoryGrid');
        if (fallback) {
            const div = document.createElement('div');
            div.className = 'file-tile';
            div.textContent = trimmed;
            fallback.appendChild(div);
            closeAddCategoryPopup();
            return;
        }
        return alert('Quick-access area not found on this page.');
    }

    // Create and append the quick-card button
    let card = null;
    try {
        const payload = new FormData();
        payload.append('title', trimmed);
        const response = await callHomepageApi('create_quick_card.php', {
            method: 'POST',
            body: payload
        });

        if (response && response.quickCard) {
            const quickCard = response.quickCard;
            card = createQuickCard(
                String(quickCard.title || trimmed),
                normalizePortfolioKey(String(quickCard.portfolioKey || ''), trimmed),
                Number(quickCard.id || 0) || null
            );
        }
    } catch (error) {
        alert(error.message || 'Failed to create portfolio card.');
        return;
    }

    if (!card) {
        card = createQuickCard(trimmed);
    }

    grid.appendChild(card);
    setQuickCardCornerMode(quickCardDeleteMode);
    closeAddCategoryPopup();
}

function deleteCategory() {
    if (!document.body.classList.contains('customize-mode')) return;

    quickCardDeleteMode = !quickCardDeleteMode;
    closeAddCategoryPopup();
    cancelQuickCardTitleChange();
    setQuickCardCornerMode(quickCardDeleteMode);
}

// Helper: create a quick-card DOM node that matches the homepage markup
function createQuickCard(title, portfolioKey, portfolioId) {
    const slug = normalizePortfolioKey(portfolioKey || '', title);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'quick-card';
    if (portfolioId) btn.dataset.portfolioId = String(portfolioId);
    btn.dataset.portfolioKey = slug;
    btn.setAttribute('onclick', `openFileManagement('fileManagementModal','${slug}', event)`);

    const editTrigger = document.createElement('span');
    editTrigger.className = 'edit-corner-trigger quick-card-edit-trigger';
    editTrigger.setAttribute('role', 'button');
    editTrigger.setAttribute('tabindex', '0');
    editTrigger.setAttribute('aria-label', 'Edit portfolio title');

    const editIcon = document.createElement('i');
    editIcon.className = 'fas fa-pen-square';
    editIcon.setAttribute('aria-hidden', 'true');
    editTrigger.appendChild(editIcon);

    const icon = document.createElement('i');
    icon.className = 'fas fa-folder folder-fa';
    icon.setAttribute('aria-hidden', 'true');

    const h3 = document.createElement('h3');
    h3.className = 'quick-title';
    h3.textContent = title;

    btn.appendChild(editTrigger);
    btn.appendChild(icon);
    btn.appendChild(h3);
    return btn;
}

function slugify(str) {
    return str.toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
}

function goBack() {
    // If customize controls are visible, hide them; otherwise do nothing (could navigate back)
    closeAddCategoryPopup();
    cancelProfileTextChanges();
    cancelQuickCardTitleChange();

    const controls = document.getElementById('customizeControls');
    if (controls && !controls.classList.contains('hidden')) {
        controls.classList.add('hidden');
        controls.classList.remove('show');
        controls.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('customize-mode');
        quickCardDeleteMode = false;
        setQuickCardCornerMode(false);
        return;
    }
    // default fallback: navigate back to a previous page if desired
    // window.history.back();
}

/* Quick-access modal helpers used by the homepage cards */
async function openFileManagement(modalId, type, event) {
    if (event && event.target && event.target.closest('.quick-card-edit-trigger')) {
        event.preventDefault();
        return;
    }

    const card = event && event.currentTarget
        ? event.currentTarget
        : (event && event.target ? event.target.closest('.quick-card') : null);

    activeQuickCardPortfolioId = card ? Number(card.dataset.portfolioId || 0) : 0;
    activeQuickCardPortfolioKey = card ? String(card.dataset.portfolioKey || type || '') : String(type || '');
    const cardTitleEl = card ? card.querySelector('.quick-title') : null;
    activeQuickCardTitle = cardTitleEl ? cardTitleEl.textContent.trim() : 'Manage Files';
    quickCardPickerCategory = mapPortfolioKeyToCategory(activeQuickCardPortfolioKey);

    const modal = document.getElementById(modalId);
    if (!modal) return console.warn('Modal not found:', modalId);
    modal.classList.remove('hidden');
    // Prevent background scrolling while modal is open
    document.body.style.overflow = 'hidden';

    // If this is the file management modal, populate tiles (sample behavior)
    if (modalId === 'fileManagementModal') {
        const title = document.getElementById('fileManagementTitle');
        if (title) title.textContent = activeQuickCardTitle || 'Manage Files';

        await loadQuickCardFiles(activeQuickCardPortfolioId);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// Close modal when clicking outside modal-content
document.addEventListener('click', (e) => {
    const openModals = document.querySelectorAll('.modal:not(.hidden)');
    openModals.forEach((m) => {
        if (!m.contains(e.target)) return;
        // If the click target is the modal overlay itself (not modal-content), close
        const content = m.querySelector('.modal-content');
        if (e.target === m) closeModal(m.id);
        // If click was on a close button inside modal-content it's already handled by onclick
    });
});

// Convenience wrappers for the file-management modal used elsewhere in the code
function openFileManagementModal() {
    openFileManagement('fileManagementModal');
}

function closeFileManagement() {
    currentCarouselItems = [];
    currentCarouselIndex = 0;
    activeQuickCardPortfolioId = 0;
    activeQuickCardPortfolioKey = '';
    activeQuickCardTitle = '';
    closeQuickCardFilePicker();
    closeModal('fileManagementModal');
}

async function loadQuickCardFiles(portfolioId) {
    if (portfolioId <= 0) {
        currentCarouselItems = [];
        currentCarouselIndex = 0;
        quickCardPickerAvailableFiles = [];
        quickCardPickerSelectedFileIds = new Set();
        renderFileCarouselItem();
        return;
    }

    try {
        const payload = await callHomepageApi(`get_quick_card_files.php?portfolio_id=${encodeURIComponent(String(portfolioId))}`, {
            method: 'GET'
        });

        quickCardPickerAvailableFiles = Array.isArray(payload.availableFiles) ? payload.availableFiles : [];
        const selectedIds = Array.isArray(payload.selectedFileIds) ? payload.selectedFileIds : [];
        quickCardPickerSelectedFileIds = new Set(selectedIds.map((id) => Number(id)).filter((id) => id > 0));

        currentCarouselItems = Array.isArray(payload.attachedFiles) ? payload.attachedFiles : [];
        currentCarouselIndex = 0;
        renderFileCarouselItem();
    } catch (error) {
        alert(error.message || 'Failed to load portfolio files.');
        currentCarouselItems = [];
        currentCarouselIndex = 0;
        renderFileCarouselItem();
    }
}

function renderFileCarouselItem() {
    const itemBtn = document.getElementById('fileCarouselItem');
    const counter = document.getElementById('fileCarouselCounter');
    const prevBtn = document.getElementById('fileCarouselPrev');
    const nextBtn = document.getElementById('fileCarouselNext');

    if (!itemBtn || !counter || !prevBtn || !nextBtn) return;

    if (!Array.isArray(currentCarouselItems) || currentCarouselItems.length === 0) {
        itemBtn.textContent = 'No items yet';
        counter.textContent = '0 / 0';
        prevBtn.disabled = true;
        nextBtn.disabled = true;
        itemBtn.disabled = true;
        return;
    }

    const safeIndex = Math.max(0, Math.min(currentCarouselItems.length - 1, currentCarouselIndex));
    currentCarouselIndex = safeIndex;

    const entry = currentCarouselItems[safeIndex] || {};
    itemBtn.textContent = String(entry.name || 'Untitled file');
    counter.textContent = `${safeIndex + 1} / ${currentCarouselItems.length}`;
    itemBtn.disabled = false;

    prevBtn.disabled = safeIndex === 0;
    nextBtn.disabled = safeIndex === currentCarouselItems.length - 1;
}

function showPreviousFileItem() {
    if (!Array.isArray(currentCarouselItems) || currentCarouselItems.length === 0) return;
    if (currentCarouselIndex <= 0) return;
    currentCarouselIndex -= 1;
    renderFileCarouselItem();
}

function showNextFileItem() {
    if (!Array.isArray(currentCarouselItems) || currentCarouselItems.length === 0) return;
    if (currentCarouselIndex >= currentCarouselItems.length - 1) return;
    currentCarouselIndex += 1;
    renderFileCarouselItem();
}

function openCurrentFileItem() {
    if (!Array.isArray(currentCarouselItems) || currentCarouselItems.length === 0) return;
    const activeEntry = currentCarouselItems[currentCarouselIndex] || null;
    const fileId = activeEntry ? Number(activeEntry.id || 0) : 0;
    if (fileId > 0) {
        openPortfolioFileEntry(fileId, false);
    }
}

function openQuickCardFilePicker() {
    if (activeQuickCardPortfolioId <= 0) {
        alert('Please open a saved extracurricular portfolio first.');
        return;
    }

    const picker = document.getElementById('quickCardFilePickerModal');
    if (!picker) return;

    picker.classList.remove('hidden');
    renderQuickCardPickerTabs();
    renderQuickCardPickerList();
}

function closeQuickCardFilePicker() {
    const picker = document.getElementById('quickCardFilePickerModal');
    if (picker) picker.classList.add('hidden');
}

function setQuickCardPickerCategory(categoryKey) {
    quickCardPickerCategory = String(categoryKey || 'all').trim() || 'all';
    renderQuickCardPickerTabs();
    renderQuickCardPickerList();
}

function renderQuickCardPickerTabs() {
    const tabs = document.querySelectorAll('#quickCardPickerCategoryTabs .picker-tab');
    tabs.forEach((tab) => {
        const tabCategory = String(tab.dataset.category || '');
        tab.classList.toggle('is-active', tabCategory === quickCardPickerCategory);
    });
}

function buildQuickCardPickerGroups(files) {
    const groups = new Map();

    files.forEach((file) => {
        const categoryLabel = String(file.categoryLabel || file.categoryKey || 'Uncategorized');
        const folderName = String(file.folderName || '').trim() || 'No folder';
        const groupKey = `${categoryLabel}::${folderName}`;

        if (!groups.has(groupKey)) {
            groups.set(groupKey, {
                categoryLabel,
                folderName,
                files: []
            });
        }

        groups.get(groupKey).files.push(file);
    });

    return Array.from(groups.values());
}

function renderQuickCardPickerList() {
    const container = document.getElementById('quickCardPickerList');
    if (!container) return;

    const filteredFiles = quickCardPickerAvailableFiles.filter((file) => {
        if (quickCardPickerCategory === 'all') return true;
        return String(file.categoryKey || '').toLowerCase() === quickCardPickerCategory;
    });

    if (filteredFiles.length === 0) {
        container.innerHTML = '<p class="picker-empty">No PDF/PNG/JPG files found in this category.</p>';
        return;
    }

    const groups = buildQuickCardPickerGroups(filteredFiles);
    const html = groups.map((group) => {
        const rows = group.files.map((file) => {
            const fileId = Number(file.id || 0);
            const checked = quickCardPickerSelectedFileIds.has(fileId) ? 'checked' : '';
            const safeName = escapeHtml(String(file.name || 'Untitled file'));
            return `
                <label class="picker-file-row" data-file-id="${fileId}">
                    <input type="checkbox" class="picker-file-checkbox" value="${fileId}" ${checked} onchange="toggleQuickCardPickerFile(this)">
                    <span class="picker-file-name">${safeName}</span>
                </label>
            `;
        }).join('');

        return `
            <section class="picker-group">
                <h4 class="picker-group-title">${escapeHtml(group.categoryLabel)} <span class="picker-group-folder">/ ${escapeHtml(group.folderName)}</span></h4>
                <div class="picker-group-files">${rows}</div>
            </section>
        `;
    }).join('');

    container.innerHTML = html;
}

function toggleQuickCardPickerFile(checkbox) {
    if (!checkbox) return;
    const fileId = Number(checkbox.value || 0);
    if (fileId <= 0) return;

    if (checkbox.checked) {
        quickCardPickerSelectedFileIds.add(fileId);
    } else {
        quickCardPickerSelectedFileIds.delete(fileId);
    }
}

async function confirmQuickCardFileSelection() {
    if (activeQuickCardPortfolioId <= 0) {
        alert('No active portfolio selected.');
        return;
    }

    const payload = new FormData();
    payload.append('portfolio_id', String(activeQuickCardPortfolioId));
    Array.from(quickCardPickerSelectedFileIds).forEach((fileId) => {
        payload.append('file_ids[]', String(fileId));
    });

    try {
        await callHomepageApi('save_quick_card_files.php', {
            method: 'POST',
            body: payload
        });

        closeQuickCardFilePicker();
        await loadQuickCardFiles(activeQuickCardPortfolioId);
    } catch (error) {
        alert(error.message || 'Failed to save selected files.');
    }
}

// Create a DOM tile for a file (matches the Tailwind-like markup used elsewhere)
function createFileTile(name) {
    const tile = document.createElement('div');
    tile.className = 'file-tile'; // CSS maps to border rounded p-4 flex-col bg-gray-50 shadow-md

    const a = document.createElement('a');
    a.className = 'file-link';
    a.href = '#';
    a.textContent = name;
    a.addEventListener('click', (e) => { e.preventDefault(); alert(`Viewing: ${name}`); });

    tile.appendChild(a);
    return tile;
}

