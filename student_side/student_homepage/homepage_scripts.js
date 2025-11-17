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
    // Priority: localStorage 'studentName' -> body[data-student-name] -> fallback text.
    const studentNameEl = document.getElementById('studentName');
    if (studentNameEl) {
        const fromStorage = localStorage.getItem('studentName');
        const fromBody = document.body ? document.body.dataset.studentName : null;
        studentNameEl.textContent = fromStorage || fromBody || 'Student Name';
    }
    // Restore saved page zoom if user changed it previously
    const savedZoom = parseInt(localStorage.getItem('studentPageZoom'), 10);
    if (savedZoom && typeof setZoom === 'function') setZoom(savedZoom);
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

function closeUploadModal() {
    const upload = document.getElementById('uploadDropdown');
    if (upload) upload.classList.add('hidden');
}

function addFile() {
    const selected = document.querySelector('#uploadDropdown input[name="category"]:checked');
    if (!selected) {
        alert('Please choose a category before continuing.');
        return;
    }
    // Placeholder behavior: show a message and close dropdown. Replace with real upload logic.
    alert(`Files will be added to: ${selected.value}`);
    closeUploadModal();
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

