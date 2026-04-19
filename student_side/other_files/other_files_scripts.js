console.log("other_files_scripts.js loaded");

let currentOtherFilesFolder = null;

function getCurrentOtherFilesFolderName() {
    return currentOtherFilesFolder || '';
}

window.getCurrentOtherFilesFolderName = getCurrentOtherFilesFolderName;

function closeOtherFilesActionDropdowns() {
    document.querySelectorAll('.files-window .file-actions-dropdown').forEach((menu) => {
        menu.classList.add('hidden');
    });
}

function isOtherFilesFolderEntry(file) {
    return file && (file.entryType === 'folder' || (!file.name && !!file.folder));
}

function ensureOtherFilesEntryId(file, index) {
    if (!file.id) {
        file.id = `other-files-${Date.now()}-${index}-${Math.random().toString(16).slice(2, 6)}`;
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

function buildOtherFilesMetaText(file, isFolderEntry, displayDate) {
    if (isFolderEntry) return displayDate;
    const sizeText = formatReadableFileSize(file.fileSize ?? file.file_size ?? 0);
    if (!displayDate) return sizeText;
    if (!sizeText) return displayDate;
    return `${displayDate} • ${sizeText}`;
}

async function removeOtherFilesEntry(targetFile) {
    const targetIsFolder = isOtherFilesFolderEntry(targetFile);
    await window.deletePortfolioEntry(targetIsFolder ? 'folder' : 'file', targetFile.id, 'other_files');
    await window.syncCategoryEntries('other_files');

    if (targetIsFolder && currentOtherFilesFolder === (targetFile.folder || targetFile.name || '')) {
        currentOtherFilesFolder = null;
    }
}

function openOtherFilesFolder(folderName) {
    currentOtherFilesFolder = folderName;
    renderCurrentSection();
}

function updateOtherFilesBreadcrumb() {
    const rootLink = document.getElementById('otherFilesBreadcrumbRoot');
    const folderCrumb = document.getElementById('otherFilesBreadcrumbFolder');
    if (!rootLink || !folderCrumb) return;

    if (currentOtherFilesFolder) {
        folderCrumb.textContent = currentOtherFilesFolder;
        folderCrumb.classList.remove('hidden');
        rootLink.setAttribute('href', '#');
        rootLink.classList.add('breadcrumb-root-link');
    } else {
        folderCrumb.textContent = '';
        folderCrumb.classList.add('hidden');
        rootLink.setAttribute('href', '../other_files/other_files.html');
        rootLink.classList.remove('breadcrumb-root-link');
    }
}

function buildOtherFilesGridItem(file, index, template) {
    const isFolderEntry = isOtherFilesFolderEntry(file);
    const displayName = isFolderEntry ? (file.folder || file.name || 'Untitled Folder') : (file.name || 'Untitled File');
    const displayDate = file.timestamp ? String(file.timestamp).split(',')[0] : '';
    const displayMeta = buildOtherFilesMetaText(file, isFolderEntry, displayDate);

    ensureOtherFilesEntryId(file, index);

    if (!template) {
        const fallback = document.createElement('div');
        fallback.className = 'bg-white p-4 border border-blue-200 rounded-lg shadow-sm transition-all flex items-center gap-3';
        fallback.innerHTML = `
            <i class="${isFolderEntry ? 'fas fa-folder text-yellow-500 text-2xl' : 'fas fa-file text-blue-500 text-2xl'}"></i>
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

    iconEl.className = isFolderEntry ? 'fas fa-folder text-yellow-500 text-2xl file-entry-icon' : 'fas fa-file text-blue-500 text-2xl file-entry-icon';
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
            openOtherFilesFolder(displayName);
        };

        fileDiv.addEventListener('click', openFolder);
        fileDiv.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openOtherFilesFolder(displayName);
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
        closeOtherFilesActionDropdowns();
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
            await removeOtherFilesEntry(file);
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
            await window.renamePortfolioEntry(isFolderEntry ? 'folder' : 'file', file.id, newName, 'other_files');
            await window.syncCategoryEntries('other_files');
            if (isFolderEntry && currentOtherFilesFolder === displayName) {
                currentOtherFilesFolder = newName;
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

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('otherFiles');
    const sectionContent = document.querySelector('.other-files-content');

    if (!sidebar || !mainContent) return;

    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');

    if (sectionContent) {
        sectionContent.classList.toggle('shifted');
    }
}

function renderCurrentSection() {
    const container = document.querySelector('.other-files-content');
    const itemTemplate = document.getElementById('otherFilesGridItemTemplate');
    if (!container) return;

    const allFiles = (typeof studentFiles !== 'undefined') ? studentFiles : [];
    const categoryFiles = allFiles.filter(f => f.category === 'other_files');
    const visibleFiles = currentOtherFilesFolder
        ? categoryFiles.filter((file) => !isOtherFilesFolderEntry(file) && (file.folder || '') === currentOtherFilesFolder)
        : categoryFiles.filter((file) => isOtherFilesFolderEntry(file) || !file.folder);

    updateOtherFilesBreadcrumb();

    container.innerHTML = '';
    container.classList.remove('has-files');

    if (!currentOtherFilesFolder && categoryFiles.length === 0) {
        container.innerHTML = `
            <div class="other-files-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No external files added yet.</p>
                <p class="text-gray-400 text-sm">Click the upload icon above to add your first external file!</p>
            </div>
        `;
        return;
    }

    if (currentOtherFilesFolder && visibleFiles.length === 0) {
        container.innerHTML = `
            <div class="other-files-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No files in this folder yet.</p>
                <p class="text-gray-400 text-sm">Use the upload icon above to add files inside ${currentOtherFilesFolder}.</p>
            </div>
        `;
        return;
    }

    container.classList.add('has-files');

    const scrollWindow = document.createElement('div');
    scrollWindow.className = 'files-window';

    const grid = document.createElement('div');
    grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4';

    visibleFiles.forEach((file, index) => {
        const itemNode = buildOtherFilesGridItem(file, index, itemTemplate);
        grid.appendChild(itemNode);
    });

    scrollWindow.appendChild(grid);
    container.appendChild(scrollWindow);
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("External Files page ready");

    const header = document.querySelector('header');
    const footer = document.querySelector('footer');
    const sidebar = document.getElementById('sidebar');

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
            closeOtherFilesActionDropdowns();
        }
    });

    const rootBreadcrumb = document.getElementById('otherFilesBreadcrumbRoot');
    if (rootBreadcrumb) {
        rootBreadcrumb.addEventListener('click', (event) => {
            if (currentOtherFilesFolder) {
                event.preventDefault();
                currentOtherFilesFolder = null;
                renderCurrentSection();
            }
        });
    }

    if (typeof window.syncCategoryEntries === 'function') {
        window.syncCategoryEntries('other_files')
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
