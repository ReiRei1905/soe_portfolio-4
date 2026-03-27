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
    // Redirect to the student homepage
    window.location.href = "../student_homepage/student_homepage.html";
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

const PORTFOLIO_API_BASE = '../api';

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
    } else {
        controls.classList.add('hidden');
        controls.classList.remove('show');
        controls.setAttribute('aria-hidden', 'true');
    }
}

function addCategory() {
    // har Prompt user for a new quick-access category name and add a quick-card to
    // the homepage quick-access grid. This runs on the front-end only.
    const name = prompt('Enter name for the new quick-access category:');
    if (!name || !name.trim()) return alert('Category name cannot be empty.');
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
            alert(`Category "${trimmed}" added (front-end only).`);
            return;
        }
        return alert('Quick-access area not found on this page.');
    }

    // Create and append the quick-card button
    const card = createQuickCard(trimmed);
    grid.appendChild(card);
    // Provide feedback
    alert(`Quick-access category "${trimmed}" added.`);
}

function deleteCategory() {
    // Front-end deletion helper: show available quick-access categories and allow
    // the user to choose one to remove.
    const grid = document.querySelector('.quick-access-grid');
    if (!grid) return alert('No quick-access area found on this page.');

    const cards = Array.from(grid.querySelectorAll('.quick-card'));
    if (cards.length === 0) return alert('There are no categories to delete.');

    const names = cards.map((c, idx) => {
        const h = c.querySelector('.quick-title');
        return h ? h.textContent.trim() : `Item ${idx + 1}`;
    });

    // Build a numbered list for the prompt
    const listText = names.map((n, i) => `${i + 1}) ${n}`).join('\n');
    const input = prompt(`Choose a category to delete (enter number or exact name):\n\n${listText}`);
    if (!input) return; // cancelled

    let chosenIndex = -1;
    const asNum = parseInt(input, 10);
    if (!Number.isNaN(asNum) && asNum >= 1 && asNum <= names.length) {
        chosenIndex = asNum - 1;
    } else {
        // Match by exact (case-insensitive) name
        const normalized = input.trim().toLowerCase();
        chosenIndex = names.findIndex(n => n.toLowerCase() === normalized);
    }

    if (chosenIndex === -1) return alert('No matching category found.');

    const toDelete = cards[chosenIndex];
    const toDeleteName = names[chosenIndex];
    if (!confirm(`Delete quick-access category "${toDeleteName}"? This is front-end only.`)) return;

    toDelete.remove();
    alert(`Category "${toDeleteName}" deleted.`);
}

// Helper: create a quick-card DOM node that matches the homepage markup
function createQuickCard(title) {
    const slug = slugify(title);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'quick-card';
    btn.setAttribute('onclick', `openFileManagement('fileManagementModal','${slug}')`);

    const icon = document.createElement('i');
    icon.className = 'fas fa-folder folder-fa';
    icon.setAttribute('aria-hidden', 'true');

    const h3 = document.createElement('h3');
    h3.className = 'quick-title';
    h3.textContent = title;

    btn.appendChild(icon);
    btn.appendChild(h3);
    return btn;
}

function slugify(str) {
    return str.toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '');
}

function goBack() {
    // If customize controls are visible, hide them; otherwise do nothing (could navigate back)
    const controls = document.getElementById('customizeControls');
    if (controls && !controls.classList.contains('hidden')) {
        controls.classList.add('hidden');
        controls.classList.remove('show');
        controls.setAttribute('aria-hidden', 'true');
        return;
    }
    // default fallback: navigate back to a previous page if desired
    // window.history.back();
}

/* Quick-access modal helpers used by the homepage cards */
function openFileManagement(modalId, type) {
    const modal = document.getElementById(modalId);
    if (!modal) return console.warn('Modal not found:', modalId);
    modal.classList.remove('hidden');
    // Prevent background scrolling while modal is open
    document.body.style.overflow = 'hidden';

    // If this is the file management modal, populate tiles (sample behavior)
    if (modalId === 'fileManagementModal') {
        const container = document.getElementById('fileTileContainer');
        const title = document.getElementById('fileManagementTitle');
        if (title) {
            // Set a contextual title based on requested type
            if (type === 'projects') title.textContent = 'Top projects';
            else if (type === 'certificates') title.textContent = 'Top certificates / awards';
            else if (type === 'assessments') title.textContent = 'Top assessments';
            else title.textContent = 'Manage Files';
        }

        if (container) {
            container.innerHTML = '';
            // Provide sample items per type so the modal feels populated
            let sampleFiles = ['Sample File 1', 'Sample File 2'];
            if (type === 'projects') sampleFiles = ['Project - Final', 'Project - Prototype'];
            if (type === 'certificates') sampleFiles = ['Certificate - Course A', 'Award - Hackathon'];
            if (type === 'assessments') sampleFiles = ['Assessment - Quiz 1', 'Assessment - Lab 2'];

            sampleFiles.forEach(name => container.appendChild(createFileTile(name)));
        }
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
    // clear tiles then close
    const container = document.getElementById('fileTileContainer');
    if (container) container.innerHTML = '';
    closeModal('fileManagementModal');
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

