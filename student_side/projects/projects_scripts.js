console.log("projects_scripts.js loaded");

let currentProjectsFolder = null;

function getCurrentProjectsFolderName() {
    return currentProjectsFolder || '';
}

window.getCurrentProjectsFolderName = getCurrentProjectsFolderName;

function closeProjectActionDropdowns() {
    document.querySelectorAll('.files-window .file-actions-dropdown').forEach((menu) => {
        menu.classList.add('hidden');
    });
}

function isProjectsFolderEntry(file) {
    return file && (file.entryType === 'folder' || (!file.name && !!file.folder));
}

function ensureProjectEntryId(file, index) {
    if (!file.id) {
        file.id = `project-${Date.now()}-${index}-${Math.random().toString(16).slice(2, 6)}`;
    }
    return file.id;
}

function formatReadableFileSize(bytes) {
    const size = Number(bytes);
    if (!Number.isFinite(size) || size <= 0) return '';

    if (size < 1024) return `${size} B`;
    const units = ['KB', 'MB', 'GB', 'TB'];
    let value = size / 1024;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex += 1;
    }

    return `${value.toFixed(value >= 100 ? 0 : 1)} ${units[unitIndex]}`;
}

function buildProjectsMetaText(file, isFolderEntry, displayDate) {
    if (isFolderEntry) return displayDate;
    const sizeText = formatReadableFileSize(file.fileSize ?? file.file_size ?? 0);
    if (!displayDate) return sizeText;
    if (!sizeText) return displayDate;
    return `${displayDate} • ${sizeText}`;
}

async function removeProjectEntry(targetFile) {
    const targetIsFolder = isProjectsFolderEntry(targetFile);
    await window.deletePortfolioEntry(targetIsFolder ? 'folder' : 'file', targetFile.id, 'projects');
    await window.syncCategoryEntries('projects');

    if (targetIsFolder && currentProjectsFolder === (targetFile.folder || targetFile.name || '')) {
        currentProjectsFolder = null;
    }
}

function openProjectsFolder(folderName) {
    currentProjectsFolder = folderName;
    renderCurrentSection();
}

function updateProjectsBreadcrumb() {
    const rootLink = document.getElementById('projectsBreadcrumbRoot');
    const folderCrumb = document.getElementById('projectsBreadcrumbFolder');
    if (!rootLink || !folderCrumb) return;

    if (currentProjectsFolder) {
        folderCrumb.textContent = currentProjectsFolder;
        folderCrumb.classList.remove('hidden');
        rootLink.setAttribute('href', '#');
        rootLink.classList.add('breadcrumb-root-link');
    } else {
        folderCrumb.textContent = '';
        folderCrumb.classList.add('hidden');
        rootLink.setAttribute('href', '../projects/projects.html');
        rootLink.classList.remove('breadcrumb-root-link');
    }
}

function buildProjectGridItem(file, index, template) {
    const isFolderEntry = isProjectsFolderEntry(file);
    const displayName = isFolderEntry ? (file.folder || file.name || 'Untitled Folder') : (file.name || 'Untitled File');
    const displayDate = file.timestamp ? String(file.timestamp).split(',')[0] : '';
    const displayMeta = buildProjectsMetaText(file, isFolderEntry, displayDate);

    ensureProjectEntryId(file, index);

    if (!template) {
        const fallback = document.createElement('div');
        fallback.className = 'bg-white p-5 border border-green-200 rounded-xl shadow-sm transition-all flex items-center gap-3';
        fallback.innerHTML = `
            <i class="${isFolderEntry ? 'fas fa-folder text-yellow-500 text-3xl' : 'fas fa-project-diagram text-blue-500 text-3xl'}"></i>
            <div class="flex flex-col">
                <span class="text-gray-800 font-bold">${displayName}</span>
                <span class="text-xs text-gray-400">${displayMeta}</span>
            </div>
        `;
        return fallback;
    }

    const fragment = template.content.cloneNode(true);
    const fileDiv = fragment.querySelector('.file-grid-item');
    const iconEl = fragment.querySelector('.file-entry-icon');
    const nameEl = fragment.querySelector('.file-entry-name');
    const dateEl = fragment.querySelector('.file-entry-date');

    fileDiv.dataset.entryId = String(file.id);

    if (isFolderEntry) {
        fileDiv.classList.add('is-folder-entry');
        fileDiv.setAttribute('tabindex', '0');
        fileDiv.setAttribute('role', 'button');
        fileDiv.setAttribute('aria-label', `Open folder ${displayName}`);
    }

    iconEl.className = isFolderEntry ? 'fas fa-folder text-yellow-500 text-3xl file-entry-icon' : 'fas fa-project-diagram text-blue-500 text-3xl file-entry-icon';
    nameEl.className = 'file-entry-name text-gray-800 font-bold';
    nameEl.textContent = displayName;
    dateEl.className = 'file-entry-date text-xs text-gray-400';
    dateEl.textContent = displayMeta;

    const optionsBtn = fragment.querySelector('.file-options');
    const actionsDropdown = fragment.querySelector('.file-actions-dropdown');
    const editBtn = fragment.querySelector('.edit-btn');
    const removeBtn = fragment.querySelector('.remove-btn');
    const viewRow = fragment.querySelector('.file-view-row');
    const inlineEdit = fragment.querySelector('.inline-edit');
    const editInput = fragment.querySelector('.edit-input');
    const saveBtn = fragment.querySelector('.save-edit-btn');
    const cancelBtn = fragment.querySelector('.cancel-edit-btn');

    if (isFolderEntry) {
        const openFolder = (event) => {
            if (event.target.closest('.file-options') || event.target.closest('.file-actions-dropdown') || event.target.closest('.inline-edit')) {
                return;
            }
            openProjectsFolder(displayName);
        };

        fileDiv.addEventListener('click', openFolder);
        fileDiv.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openProjectsFolder(displayName);
            }
        });
    }

    optionsBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = actionsDropdown.classList.contains('hidden');
        closeProjectActionDropdowns();
        if (willOpen) actionsDropdown.classList.remove('hidden');
    });

    optionsBtn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            optionsBtn.click();
        }
    });

    editBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        actionsDropdown.classList.add('hidden');
        fileDiv.classList.add('is-editing');
        if (viewRow) viewRow.classList.add('hidden');
        inlineEdit.classList.remove('hidden');
        editInput.value = displayName;
        editInput.focus();
    });

    removeBtn.addEventListener('click', async (event) => {
        event.stopPropagation();
        actionsDropdown.classList.add('hidden');

        if (!confirm(`Delete ${isFolderEntry ? 'folder' : 'file'} "${displayName}"?`)) return;

        try {
            await removeProjectEntry(file);
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to delete item.');
            return;
        }
        renderCurrentSection();
    });

    saveBtn.addEventListener('click', async (event) => {
        event.stopPropagation();
        const newName = editInput.value.trim();
        if (!newName) {
            alert('Name cannot be empty.');
            return;
        }

        try {
            await window.renamePortfolioEntry(isFolderEntry ? 'folder' : 'file', file.id, newName, 'projects');
            await window.syncCategoryEntries('projects');
            if (isFolderEntry && currentProjectsFolder === displayName) {
                currentProjectsFolder = newName;
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Failed to rename item.');
            return;
        }

        renderCurrentSection();
    });

    cancelBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        inlineEdit.classList.add('hidden');
        fileDiv.classList.remove('is-editing');
        if (viewRow) viewRow.classList.remove('hidden');
    });

    inlineEdit.addEventListener('click', (event) => event.stopPropagation());
    editInput.addEventListener('click', (event) => event.stopPropagation());

    editInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveBtn.click();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            cancelBtn.click();
        }
    });

    return fragment;
}

/* Toggle sidebar in projects section */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('projects');
    const projectsContent = document.querySelector('.projects-content');

    if (!sidebar || !mainContent) return;
    
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
    
    if (projectsContent) {
        projectsContent.classList.toggle('shifted');
    }
}

function renderCurrentSection() {
    const container = document.querySelector('.projects-content');
    const itemTemplate = document.getElementById('projectsGridItemTemplate');
    if (!container) return;

    // Get all files from session storage
    const allFiles = (typeof studentFiles !== 'undefined') ? studentFiles : [];
    const projectsFiles = allFiles.filter(f => f.category === 'projects');
    const visibleFiles = currentProjectsFolder
        ? projectsFiles.filter((file) => !isProjectsFolderEntry(file) && (file.folder || '') === currentProjectsFolder)
        : projectsFiles.filter((file) => isProjectsFolderEntry(file) || !file.folder);

    updateProjectsBreadcrumb();

    // Clear everything first
    container.innerHTML = '';
    container.classList.remove('has-files');

    if (!currentProjectsFolder && projectsFiles.length === 0) {
        container.innerHTML = `
            <div class="projects-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No projects added yet.</p>
                <p class="text-gray-400 text-sm">Click the upload icon above to add your first project!</p>
            </div>
        `;
        return;
    }

    if (currentProjectsFolder && visibleFiles.length === 0) {
        container.innerHTML = `
            <div class="projects-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No files in this folder yet.</p>
                <p class="text-gray-400 text-sm">Use the upload icon above to add files inside ${currentProjectsFolder}.</p>
            </div>
        `;
        return;
    }

    container.classList.add('has-files');

    const scrollWindow = document.createElement('div');
    scrollWindow.className = 'files-window';

    // Create grid for files
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6';
    
    visibleFiles.forEach((file, index) => {
        const itemNode = buildProjectGridItem(file, index, itemTemplate);
        grid.appendChild(itemNode);
    });

    scrollWindow.appendChild(grid);
    container.appendChild(scrollWindow);
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("Projects page ready");

    // Position sidebar below header
    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const sidebar = document.getElementById('sidebar');

    // Keep shared layout vars in sync with real element heights.
    const syncLayoutVars = () => {
        if (header) {
            document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
        }
        if (footer) {
            document.documentElement.style.setProperty('--footer-height', `${footer.offsetHeight}px`);
        }
    };

    syncLayoutVars();
    window.addEventListener('resize', syncLayoutVars);

    if (header && sidebar) sidebar.style.top = `${header.offsetHeight}px`;

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.file-options') && !event.target.closest('.file-actions-dropdown')) {
            closeProjectActionDropdowns();
        }
    });

    const rootBreadcrumb = document.getElementById('projectsBreadcrumbRoot');
    if (rootBreadcrumb) {
        rootBreadcrumb.addEventListener('click', (event) => {
            if (currentProjectsFolder) {
                event.preventDefault();
                currentProjectsFolder = null;
                renderCurrentSection();
            }
        });
    }

    // Load initial content from backend
    if (typeof window.syncCategoryEntries === 'function') {
        window.syncCategoryEntries('projects')
            .catch((error) => {
                console.error(error);
            })
            .finally(() => {
                renderCurrentSection();
            });
    } else {
        renderCurrentSection();
    }
});
