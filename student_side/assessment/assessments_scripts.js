console.log("assessments_scripts.js loaded");

let currentAssessmentFolder = null;

function closeFileActionDropdowns() {
    document.querySelectorAll('.files-window .file-actions-dropdown').forEach((menu) => {
        menu.classList.add('hidden');
    });
}

function isAssessmentFolderEntry(file) {
    return file && (file.entryType === 'folder' || (!file.name && !!file.folder));
}

function ensureAssessmentEntryId(file, index) {
    if (!file.id) {
        file.id = `assessment-${Date.now()}-${index}-${Math.random().toString(16).slice(2, 6)}`;
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

function buildAssessmentMetaText(file, isFolderEntry, displayDate) {
    if (isFolderEntry) return displayDate;
    const sizeText = formatReadableFileSize(file.fileSize ?? file.file_size ?? 0);
    if (!displayDate) return sizeText;
    if (!sizeText) return displayDate;
    return `${displayDate} • ${sizeText}`;
}

async function removeAssessmentEntry(targetFile) {
    const targetIsFolder = isAssessmentFolderEntry(targetFile);
    await window.deletePortfolioEntry(targetIsFolder ? 'folder' : 'file', targetFile.id, 'assessment');
    await window.syncCategoryEntries('assessment');

    if (targetIsFolder && currentAssessmentFolder === (targetFile.folder || targetFile.name || '')) {
        currentAssessmentFolder = null;
    }
}

function openAssessmentFolder(folderName) {
    currentAssessmentFolder = folderName;
    renderCurrentSection();
}

function updateAssessmentBreadcrumb() {
    const rootLink = document.getElementById('assessmentBreadcrumbRoot');
    const folderCrumb = document.getElementById('assessmentBreadcrumbFolder');
    if (!rootLink || !folderCrumb) return;

    if (currentAssessmentFolder) {
        folderCrumb.textContent = currentAssessmentFolder;
        folderCrumb.classList.remove('hidden');
        rootLink.setAttribute('href', '#');
        rootLink.classList.add('breadcrumb-root-link');
    } else {
        folderCrumb.textContent = '';
        folderCrumb.classList.add('hidden');
        rootLink.setAttribute('href', '../assessment/assessments.html');
        rootLink.classList.remove('breadcrumb-root-link');
    }
}

function buildAssessmentGridItem(file, index, template) {
    const isFolderEntry = isAssessmentFolderEntry(file);
    const displayName = isFolderEntry ? (file.folder || file.name || 'Untitled Folder') : (file.name || 'Untitled File');
    const displayDate = file.timestamp ? String(file.timestamp).split(',')[0] : '';
    const displayMeta = buildAssessmentMetaText(file, isFolderEntry, displayDate);

    ensureAssessmentEntryId(file, index);

    if (!template) {
        const fallback = document.createElement('div');
        fallback.className = 'bg-white p-4 border border-blue-200 rounded-lg shadow-sm transition-all flex items-center gap-3';
        fallback.innerHTML = `
            <i class="${isFolderEntry ? 'fas fa-folder text-yellow-500 text-2xl' : 'fas fa-file-pdf text-red-500 text-2xl'}"></i>
            <div class="flex flex-col">
                <span class="text-gray-700 ${isFolderEntry ? 'font-semibold' : 'font-medium'}">${displayName}</span>
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

    iconEl.className = isFolderEntry ? 'fas fa-folder text-yellow-500 text-2xl file-entry-icon' : 'fas fa-file-pdf text-red-500 text-2xl file-entry-icon';
    nameEl.className = isFolderEntry ? 'file-entry-name text-gray-700 font-semibold' : 'file-entry-name text-gray-700 font-medium';
    nameEl.textContent = displayName;
    dateEl.className = 'file-entry-date text-xs text-gray-400';
    dateEl.textContent = displayMeta;

    const optionsBtn = fragment.querySelector('.file-options');
    const actionsDropdown = fragment.querySelector('.file-actions-dropdown');
    const editBtn = fragment.querySelector('.edit-btn');
    const downloadBtn = fragment.querySelector('.download-btn');
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
            openAssessmentFolder(displayName);
        };

        fileDiv.addEventListener('click', openFolder);
        fileDiv.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openAssessmentFolder(displayName);
            }
        });
    } else {
        nameEl.classList.add('is-file-link');
        nameEl.setAttribute('tabindex', '0');
        nameEl.setAttribute('role', 'link');
        nameEl.setAttribute('aria-label', `Open file ${displayName}`);

        const openFile = (event) => {
            event.stopPropagation();
            if (file.hasContent === false) {
                alert('This file record was created without binary content. Please re-upload the file.');
                return;
            }
            if (typeof window.openPortfolioFileEntry === 'function') {
                window.openPortfolioFileEntry(file.id, false);
            }
        };

        nameEl.addEventListener('click', openFile);
        nameEl.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openFile(event);
            }
        });
    }

    if (downloadBtn) {
        if (isFolderEntry) {
            downloadBtn.classList.add('hidden');
        } else {
            downloadBtn.addEventListener('click', (event) => {
                event.stopPropagation();
                actionsDropdown.classList.add('hidden');
                if (file.hasContent === false) {
                    alert('This file record was created without binary content. Please re-upload the file.');
                    return;
                }
                if (typeof window.openPortfolioFileEntry === 'function') {
                    window.openPortfolioFileEntry(file.id, true);
                }
            });
        }
    }

    optionsBtn.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = actionsDropdown.classList.contains('hidden');
        closeFileActionDropdowns();
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
            await removeAssessmentEntry(file);
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
            await window.renamePortfolioEntry(isFolderEntry ? 'folder' : 'file', file.id, newName, 'assessment');
            await window.syncCategoryEntries('assessment');
            if (isFolderEntry && currentAssessmentFolder === displayName) {
                currentAssessmentFolder = newName;
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

/* Toggle sidebar in assessments section */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('assessments');
    const assessmentsContent = document.querySelector('.assessments-content');

    if (!sidebar || !mainContent) return;
    
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
    
    if (assessmentsContent) {
        assessmentsContent.classList.toggle('shifted');
    }
}

function renderCurrentSection() {
    const container = document.querySelector('.assessments-content');
    const itemTemplate = document.getElementById('assessmentGridItemTemplate');
    if (!container) return;

    // Get all files from session storage
    const allFiles = (typeof studentFiles !== 'undefined') ? studentFiles : [];
    const assessmentFiles = allFiles.filter(f => f.category === 'assessment');
    const visibleFiles = currentAssessmentFolder
        ? assessmentFiles.filter((file) => !isAssessmentFolderEntry(file) && (file.folder || '') === currentAssessmentFolder)
        : assessmentFiles.filter((file) => isAssessmentFolderEntry(file) || !file.folder);

    updateAssessmentBreadcrumb();

    // Clear everything first
    container.innerHTML = '';
    container.classList.remove('has-files');

    if (!currentAssessmentFolder && assessmentFiles.length === 0) {
        container.innerHTML = `
            <div class="assessments-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No assessments added yet.</p>
                <p class="text-gray-400 text-sm">Click the upload icon above to add your first assessment!</p>
            </div>
        `;
        return;
    }

    if (currentAssessmentFolder && visibleFiles.length === 0) {
        container.innerHTML = `
            <div class="assessments-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No files in this folder yet.</p>
                <p class="text-gray-400 text-sm">Use the upload icon above to add files inside ${currentAssessmentFolder}.</p>
            </div>
        `;
        return;
    }

    container.classList.add('has-files');

    const scrollWindow = document.createElement('div');
    scrollWindow.className = 'files-window';

    // Create container for files at the root level (no folders)
    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4';
    
    visibleFiles.forEach((file, index) => {
        const itemNode = buildAssessmentGridItem(file, index, itemTemplate);
        grid.appendChild(itemNode);
    });

    scrollWindow.appendChild(grid);
    container.appendChild(scrollWindow);
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("Assessments page ready");
    
    // Update sidebar top based on header height
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
            closeFileActionDropdowns();
        }
    });

    const rootBreadcrumb = document.getElementById('assessmentBreadcrumbRoot');
    if (rootBreadcrumb) {
        rootBreadcrumb.addEventListener('click', (event) => {
            if (currentAssessmentFolder) {
                event.preventDefault();
                currentAssessmentFolder = null;
                renderCurrentSection();
            }
        });
    }

    // Load initial content from backend
    if (typeof window.syncCategoryEntries === 'function') {
        window.syncCategoryEntries('assessment')
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
