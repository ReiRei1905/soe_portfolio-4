const API_BASE = '';

let canManagePrograms = false;
let currentSessionUserId = 0;
let isProgramDirectorUser = false;
let hasProgramDirectorAssignment = false;
let programDirectorModal = null;
let currentAssignProgramId = 0;
let currentAssignProgramName = '';

function normalizeRoleLabel(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[_-]+/g, ' ')
        .replace(/\s+/g, ' ');
}

function updateProgramAssignmentNoticeVisibility() {
    const notice = document.getElementById('pdAssignmentNoticePrograms');
    if (!notice) return;

    const shouldShow = isProgramDirectorUser && !hasProgramDirectorAssignment;
    notice.classList.toggle('hidden', !shouldShow);
}

function toggleProgramDropdown(icon, canManage) {
    if (!canManage) {
        return;
    }

    if (!icon) return;
    const dropdown = icon.nextElementSibling;
    if (!dropdown) return;

    document.querySelectorAll('.program-item .dropdown').forEach((menu) => {
        if (menu !== dropdown) menu.classList.add('hidden');
    });

    dropdown.classList.toggle('hidden');
}

async function loadProgramAccessContext() {
    try {
        const response = await fetch('../../user_info_V3/get_session_user.php', { credentials: 'same-origin' });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            canManagePrograms = false;
            return;
        }

        const roleType = normalizeRoleLabel(payload.user?.roleType || '');
        const facultyRole = normalizeRoleLabel(payload.user?.facultyRole || '');
        const status = Number(payload.user?.status || 0);
        const isVerified = Number(payload.user?.isVerified || 0);
        const isExecDirectorRole = facultyRole.includes('executive director');
        const isProgramDirectorRole = facultyRole.includes('program director') || facultyRole.includes('program directors');
        const isVerifiedFaculty = roleType === 'faculty' && status === 1 && isVerified === 1;

        currentSessionUserId = Number(payload.user?.userId || 0);
        isProgramDirectorUser = isVerifiedFaculty && isProgramDirectorRole;

        canManagePrograms = isVerifiedFaculty
            && isExecDirectorRole
            && status === 1
            && isVerified === 1;
    } catch (error) {
        canManagePrograms = false;
        console.warn('Unable to load program access context:', error);
    }
}

async function fetchAssignableFacultyUsers() {
    const response = await fetch(`${API_BASE}get_verified_faculty_for_assignment.php`, {
        credentials: 'same-origin'
    });
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load verified faculty users.');
    }

    return payload.facultyUsers || [];
}

function renderProgramDirectorSelectOptions(facultyUsers, selectedUserId = 0) {
    const select = document.getElementById('programDirectorSelect');
    if (!select) return;

    select.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Select Program Director';
    placeholder.disabled = true;
    placeholder.selected = true;
    select.appendChild(placeholder);

    facultyUsers.forEach((user) => {
        const option = document.createElement('option');
        option.value = String(user.userId);
        const roleLabel = user.facultyRole ? ` (${user.facultyRole})` : '';
        option.textContent = `${user.fullName}${roleLabel} - ${user.email}`;
        if (Number(user.userId) === Number(selectedUserId)) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

async function openAssignProgramDirectorModal(programId, programName, selectedUserId = 0) {
    if (!canManagePrograms) {
        return;
    }

    currentAssignProgramId = Number(programId);
    currentAssignProgramName = programName;

    const titleEl = document.getElementById('assignProgramTitle');
    if (titleEl) {
        titleEl.textContent = `Program: ${programName}`;
    }

    try {
        const facultyUsers = await fetchAssignableFacultyUsers();
        renderProgramDirectorSelectOptions(facultyUsers, selectedUserId);
    } catch (error) {
        alert(error.message || 'Unable to load faculty users.');
        return;
    }

    if (programDirectorModal) {
        programDirectorModal.show();
    }
}

async function assignProgramDirector() {
    if (!canManagePrograms || currentAssignProgramId <= 0) {
        return;
    }

    const select = document.getElementById('programDirectorSelect');
    if (!select || !select.value) {
        alert('Please select a Program Director first.');
        return;
    }

    const body = new URLSearchParams({
        programId: String(currentAssignProgramId),
        programDirectorUserId: String(select.value)
    });

    try {
        const response = await fetch(`${API_BASE}assign_program_director.php`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to assign Program Director.');
        }

        if (programDirectorModal) {
            programDirectorModal.hide();
        }

        alert(payload.message || 'Program Director assigned successfully.');
        await fetchPrograms();
    } catch (error) {
        alert(error.message || 'Failed to assign Program Director.');
    }
}

async function revokeProgramDirector(programId) {
    if (!canManagePrograms || Number(programId) <= 0) {
        return;
    }

    const confirmed = confirm('Revoke the current Program Director assignment for this program?');
    if (!confirmed) return;

    const body = new URLSearchParams({
        programId: String(programId)
    });

    const response = await fetch(`${API_BASE}revoke_program_director.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString()
    });

    const payload = await response.json();
    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to revoke Program Director.');
    }

    alert(payload.message || 'Program Director assignment revoked.');
}

function goToCoursesForProgram(programName) {
    const programSlug = encodeURIComponent(programName);
    window.location.href = `../course_management/courses.html?program=${programSlug}`;
}

async function createProgram(programInput, programInputContainer) {
    const programName = programInput.value.trim();
    if (!programName) {
        alert('Please enter a valid program name.');
        return;
    }

    const response = await fetch(`${API_BASE}create_program.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: `programName=${encodeURIComponent(programName)}`
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to create program');
    }

    programInput.value = '';
    programInputContainer.classList.add('hidden');
}

async function checkProgramUsage(programId) {
    const response = await fetch(`${API_BASE}check_program_usage.php?programId=${encodeURIComponent(programId)}`, {
        credentials: 'same-origin'
    });
    const info = await response.json();

    if (!response.ok || !info.success) {
        throw new Error(info.message || 'Failed to check program usage.');
    }

    return info;
}

async function deleteProgram(programId) {
    const info = await checkProgramUsage(programId);

    const courseCount = parseInt(info.courses || 0, 10);
    const facultyCount = parseInt(info.faculty || 0, 10);

    if (courseCount > 0 || facultyCount > 0) {
        const parts = [];
        if (courseCount > 0) parts.push(`${courseCount} course(s)`);
        if (facultyCount > 0) parts.push(`${facultyCount} faculty member(s)`);
        alert(`Cannot delete program: it is used by ${parts.join(' and ')}.`);
        return;
    }

    if (!confirm('Are you sure you want to delete this program?')) return;

    const response = await fetch(`${API_BASE}delete_program.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: `programId=${encodeURIComponent(programId)}`
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to delete program');
    }

    alert(data.message || 'Program deleted');
}

async function saveProgramEdit(programId, newName) {
    const response = await fetch(`${API_BASE}edit_program.php`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: `programId=${encodeURIComponent(programId)}&newProgramName=${encodeURIComponent(newName)}`
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Failed to update program.');
    }
}

async function fetchPrograms() {
    const programList = document.getElementById('programList');
    const programItemTemplate = document.getElementById('programItemTemplate');
    if (!programList || !programItemTemplate) {
        return;
    }

    const response = await fetch(`${API_BASE}fetch_programs.php`, { credentials: 'same-origin' });
    const programs = await response.json();
    if (!response.ok || !Array.isArray(programs)) {
        throw new Error('Failed to load programs.');
    }

    hasProgramDirectorAssignment = isProgramDirectorUser
        && programs.some((program) => Number(program.assignedProgramDirectorUserId || 0) === currentSessionUserId);
    updateProgramAssignmentNoticeVisibility();

    programList.innerHTML = '';

    programs.forEach((program) => {
        const clone = programItemTemplate.content.cloneNode(true);
        const itemEl = clone.querySelector('.program-item');
        const nameEl = clone.querySelector('.program_name');
        const assignedPdEl = clone.querySelector('.assigned-pd-label');
        const optionsIcon = clone.querySelector('.program-options');
        const dropdown = clone.querySelector('.dropdown');
        const assignBtn = clone.querySelector('.assign-PD-btn');
        const revokeBtn = clone.querySelector('.revoke-PD-btn');
        const editBtn = clone.querySelector('.edit-btn');
        const removeBtn = clone.querySelector('.remove-btn');
        const inlineEdit = clone.querySelector('.inline-edit');
        const editInput = clone.querySelector('.edit-input');
        const saveBtn = clone.querySelector('.save-edit-btn');
        const cancelBtn = clone.querySelector('.cancel-edit-btn');

        itemEl.dataset.programId = String(program.id);
        nameEl.textContent = program.name;

        const assignedName = String(program.assignedProgramDirectorName || '').trim();
        const hasAssignedDirector = Number(program.assignedProgramDirectorUserId || 0) > 0;
        assignedPdEl.textContent = assignedName ? `Program Director: ${assignedName}` : 'Program Director: Not assigned';

        if (!canManagePrograms) {
            optionsIcon.classList.add('hidden');
            dropdown.classList.add('hidden');
        }

        optionsIcon.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleProgramDropdown(optionsIcon, canManagePrograms);
        });

        itemEl.addEventListener('click', () => {
            goToCoursesForProgram(program.name);
        });

        [inlineEdit, editInput, saveBtn, cancelBtn, assignBtn, revokeBtn, editBtn, removeBtn, dropdown].forEach((el) => {
            if (el) {
                el.addEventListener('click', (event) => event.stopPropagation());
            }
        });

        if (revokeBtn) {
            revokeBtn.disabled = !hasAssignedDirector;
            revokeBtn.classList.toggle('is-disabled', !hasAssignedDirector);
        }

        assignBtn.addEventListener('click', async () => {
            if (!canManagePrograms) return;
            const selectedUserId = Number(program.assignedProgramDirectorUserId || 0);
            await openAssignProgramDirectorModal(program.id, program.name, selectedUserId);
            dropdown.classList.add('hidden');
        });

        if (revokeBtn) {
            revokeBtn.addEventListener('click', async () => {
                if (!canManagePrograms || !hasAssignedDirector) return;
                try {
                    await revokeProgramDirector(program.id);
                    dropdown.classList.add('hidden');
                    await fetchPrograms();
                } catch (error) {
                    alert(error.message || 'Failed to revoke Program Director.');
                }
            });
        }

        editBtn.addEventListener('click', () => {
            if (!canManagePrograms) return;
            inlineEdit.classList.remove('hidden');
            editInput.value = program.name;
            editInput.focus();
            dropdown.classList.add('hidden');
        });

        saveBtn.addEventListener('click', async () => {
            if (!canManagePrograms) return;
            const newName = editInput.value.trim();
            if (!newName) {
                alert('Program name cannot be empty.');
                return;
            }

            if (newName === program.name) {
                inlineEdit.classList.add('hidden');
                return;
            }

            try {
                await saveProgramEdit(program.id, newName);
                nameEl.textContent = newName;
                program.name = newName;
                inlineEdit.classList.add('hidden');
            } catch (error) {
                alert(error.message || 'Error updating program.');
            }
        });

        cancelBtn.addEventListener('click', () => {
            inlineEdit.classList.add('hidden');
        });

        removeBtn.addEventListener('click', async () => {
            if (!canManagePrograms) return;
            try {
                await deleteProgram(program.id);
                await fetchPrograms();
            } catch (error) {
                alert(error.message || 'Failed to delete program.');
            }
        });

        programList.appendChild(clone);
    });
}

document.addEventListener('DOMContentLoaded', async () => {
    const createProgramBtn = document.getElementById('createProgramBtn');
    const programInputContainer = document.getElementById('programInputContainer');
    const programInput = document.getElementById('programInput');
    const confirmProgramBtn = document.getElementById('confirmProgramBtn');
    const assignConfirmBtn = document.getElementById('confirmAssignProgramDirectorBtn');

    const modalElement = document.getElementById('assignProgramDirectorModal');
    if (modalElement && window.bootstrap && window.bootstrap.Modal) {
        programDirectorModal = new window.bootstrap.Modal(modalElement);
    }

    await loadProgramAccessContext();
    updateProgramAssignmentNoticeVisibility();

    if (!canManagePrograms) {
        createProgramBtn.classList.add('hidden');
        programInputContainer.classList.add('hidden');
    }

    createProgramBtn.addEventListener('click', () => {
        if (!canManagePrograms) return;
        programInputContainer.classList.remove('hidden');
        programInput.focus();
    });

    confirmProgramBtn.addEventListener('click', async () => {
        if (!canManagePrograms) return;
        try {
            await createProgram(programInput, programInputContainer);
            await fetchPrograms();
        } catch (error) {
            alert(error.message || 'Failed to create program.');
        }
    });

    assignConfirmBtn.addEventListener('click', async () => {
        await assignProgramDirector();
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.program-options') && !event.target.closest('.program-item .dropdown')) {
            document.querySelectorAll('.program-item .dropdown').forEach((menu) => menu.classList.add('hidden'));
        }
    });

    try {
        await fetchPrograms();
    } catch (error) {
        alert(error.message || 'Failed to load programs.');
    }
});
