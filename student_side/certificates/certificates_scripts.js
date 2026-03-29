console.log("certificates_scripts.js loaded");

let currentCertificatesFolder = null;

function getCurrentCertificatesFolderName() {
    return currentCertificatesFolder || '';
}

window.getCurrentCertificatesFolderName = getCurrentCertificatesFolderName;

function closeCertificateActionDropdowns() {
    document.querySelectorAll('.files-window .file-actions-dropdown').forEach((menu) => {
        menu.classList.add('hidden');
    });
}

function isCertificatesFolderEntry(file) {
    return file && (file.entryType === 'folder' || (!file.name && !!file.folder));
}

function ensureCertificateEntryId(file, index) {
    if (!file.id) {
        file.id = `certificate-${Date.now()}-${index}-${Math.random().toString(16).slice(2, 6)}`;
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

function buildCertificatesMetaText(file, isFolderEntry, displayDate) {
    if (isFolderEntry) return displayDate;
    const sizeText = formatReadableFileSize(file.fileSize ?? file.file_size ?? 0);
    if (!displayDate) return sizeText;
    if (!sizeText) return displayDate;
    return `${displayDate} • ${sizeText}`;
}

async function removeCertificateEntry(targetFile) {
    const targetIsFolder = isCertificatesFolderEntry(targetFile);
    await window.deletePortfolioEntry(targetIsFolder ? 'folder' : 'file', targetFile.id, 'certificates');
    await window.syncCategoryEntries('certificates');

    if (targetIsFolder && currentCertificatesFolder === (targetFile.folder || targetFile.name || '')) {
        currentCertificatesFolder = null;
    }
}

function openCertificatesFolder(folderName) {
    currentCertificatesFolder = folderName;
    renderCurrentSection();
}

function updateCertificatesBreadcrumb() {
    const rootLink = document.getElementById('certificatesBreadcrumbRoot');
    const folderCrumb = document.getElementById('certificatesBreadcrumbFolder');
    if (!rootLink || !folderCrumb) return;

    if (currentCertificatesFolder) {
        folderCrumb.textContent = currentCertificatesFolder;
        folderCrumb.classList.remove('hidden');
        rootLink.setAttribute('href', '#');
        rootLink.classList.add('breadcrumb-root-link');
    } else {
        folderCrumb.textContent = '';
        folderCrumb.classList.add('hidden');
        rootLink.setAttribute('href', '../certificates/certificates.html');
        rootLink.classList.remove('breadcrumb-root-link');
    }
}

function buildCertificateGridItem(file, index, template) {
    const isFolderEntry = isCertificatesFolderEntry(file);
    const displayName = isFolderEntry ? (file.folder || file.name || 'Untitled Folder') : (file.name || 'Untitled File');
    const displayDate = file.timestamp ? String(file.timestamp).split(',')[0] : '';
    const displayMeta = buildCertificatesMetaText(file, isFolderEntry, displayDate);

    ensureCertificateEntryId(file, index);

    if (!template) {
        const fallback = document.createElement('div');
        fallback.className = 'bg-white p-5 border-b-4 border-orange-400 rounded-xl shadow-sm transition-all flex items-center gap-4';
        fallback.innerHTML = `
            <i class="${isFolderEntry ? 'fas fa-folder text-yellow-500 text-3xl' : 'fas fa-certificate text-yellow-500 text-3xl'}"></i>
            <div class="flex flex-col">
                <span class="text-orange-950 font-bold uppercase text-sm tracking-wide">${displayName}</span>
                <span class="text-xs text-orange-300">${displayMeta}</span>
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

    iconEl.className = isFolderEntry ? 'fas fa-folder text-yellow-500 text-3xl file-entry-icon' : 'fas fa-certificate text-yellow-500 text-3xl file-entry-icon';
    nameEl.className = 'file-entry-name text-orange-950 font-bold uppercase text-sm tracking-wide';
    nameEl.textContent = displayName;
    dateEl.className = 'file-entry-date text-xs text-orange-300';
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
            openCertificatesFolder(displayName);
        };

        fileDiv.addEventListener('click', openFolder);
        fileDiv.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openCertificatesFolder(displayName);
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
        closeCertificateActionDropdowns();
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
            await removeCertificateEntry(file);
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
            await window.renamePortfolioEntry(isFolderEntry ? 'folder' : 'file', file.id, newName, 'certificates');
            await window.syncCategoryEntries('certificates');
            if (isFolderEntry && currentCertificatesFolder === displayName) {
                currentCertificatesFolder = newName;
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

/* Toggle sidebar in certificates section */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('certificates');
    const certificatesContent = document.querySelector('.certificates-content');

    if (!sidebar || !mainContent) return;
    
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
    
    if (certificatesContent) {
        certificatesContent.classList.toggle('shifted');
    }
}

function renderCurrentSection() {
    const container = document.querySelector('.certificates-content');
    const itemTemplate = document.getElementById('certificatesGridItemTemplate');
    if (!container) return;

    // Get all files from session storage
    const allFiles = (typeof studentFiles !== 'undefined') ? studentFiles : [];
    const certFiles = allFiles.filter(f => f.category === 'certificates');
    const visibleFiles = currentCertificatesFolder
        ? certFiles.filter((file) => !isCertificatesFolderEntry(file) && (file.folder || '') === currentCertificatesFolder)
        : certFiles.filter((file) => isCertificatesFolderEntry(file) || !file.folder);

    updateCertificatesBreadcrumb();

    // Clear everything first
    container.innerHTML = '';
    container.classList.remove('has-files');

    if (!currentCertificatesFolder && certFiles.length === 0) {
        container.innerHTML = `
            <div class="certificates-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-certificate text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No certificates added yet.</p>
                <p class="text-gray-400 text-sm">Click the upload icon above to add your first certificate!</p>
            </div>
        `;
        return;
    }

    if (currentCertificatesFolder && visibleFiles.length === 0) {
        container.innerHTML = `
            <div class="certificates-placeholder flex flex-col items-center justify-center p-10 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50">
                <i class="fas fa-folder-open text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg font-medium">No files in this folder yet.</p>
                <p class="text-gray-400 text-sm">Use the upload icon above to add files inside ${currentCertificatesFolder}.</p>
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
        const itemNode = buildCertificateGridItem(file, index, itemTemplate);
        grid.appendChild(itemNode);
    });

    scrollWindow.appendChild(grid);
    container.appendChild(scrollWindow);
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("Certificates page ready");

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
            closeCertificateActionDropdowns();
        }
    });

    const rootBreadcrumb = document.getElementById('certificatesBreadcrumbRoot');
    if (rootBreadcrumb) {
        rootBreadcrumb.addEventListener('click', (event) => {
            if (currentCertificatesFolder) {
                event.preventDefault();
                currentCertificatesFolder = null;
                renderCurrentSection();
            }
        });
    }

    // Load initial content from backend
    if (typeof window.syncCategoryEntries === 'function') {
        window.syncCategoryEntries('certificates')
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
