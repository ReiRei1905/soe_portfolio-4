// program_scripts.js (moved to program_management)
// Program management: fetch, create, edit, delete, and render programs

// Since this script lives in the same folder as the PHP endpoints after the
// move, use a local base path. This prevents double 'program_management/'
// prefixes when the page is loaded from program_management/.
const API_BASE = '';

document.addEventListener('DOMContentLoaded', () => {
    const programList = document.getElementById('programList');
    if (!programList) return; // not on programs page

    const programItemTemplate = document.getElementById('programItemTemplate');
    const createProgramBtn = document.getElementById('createProgramBtn');
    const programInputContainer = document.getElementById('programInputContainer');
    const programInput = document.getElementById('programInput');
    const confirmProgramBtn = document.getElementById('confirmProgramBtn');

    createProgramBtn.addEventListener('click', () => {
        programInputContainer.classList.remove('hidden');
        programInput.focus();
    });

    confirmProgramBtn.addEventListener('click', () => {
        const programName = programInput.value.trim();
        if (!programName) {
            alert('Please enter a valid program name.');
            return;
        }

        fetch(`${API_BASE}create_program.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `programName=${encodeURIComponent(programName)}`
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    programInput.value = '';
                    programInputContainer.classList.add('hidden');
                    fetchPrograms();
                } else {
                    alert(data.message || 'Failed to create program');
                }
            })
            .catch((err) => console.error('Error creating program:', err));
    });

    function editProgram(button) {
        // legacy function kept for compatibility when called directly; prefer inline handlers
        const programItem = button.closest('.program-item');
        const inlineEdit = programItem.querySelector('.inline-edit');
        const editInput = inlineEdit.querySelector('.edit-input');
        inlineEdit.classList.remove('hidden');
        editInput.value = programItem.querySelector('.program_name').textContent.trim();
        editInput.focus();
    }

    function deleteProgram(programId) {
        // First check whether there are courses or faculty tied to this program
        fetch(`${API_BASE}check_program_usage.php?programId=${encodeURIComponent(programId)}`)
            .then((r) => r.json())
            .then((info) => {
                if (!info || !info.success) {
                    alert(info && info.message ? info.message : 'Failed to check program usage.');
                    return;
                }

                const courseCount = parseInt(info.courses || 0, 10);
                const facultyCount = parseInt(info.faculty || 0, 10);

                if (courseCount > 0 || facultyCount > 0) {
                    let parts = [];
                    if (courseCount > 0) parts.push(`${courseCount} course(s)`);
                    if (facultyCount > 0) parts.push(`${facultyCount} faculty member(s)`);
                    alert(`Cannot delete program: it is used by ${parts.join(' and ')}. Please remove or reassign these before deleting the program.`);
                    return;
                }

                // Safe to delete
                if (!confirm('Are you sure you want to delete this program? This action cannot be undone.')) return;

                fetch(`${API_BASE}delete_program.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `programId=${encodeURIComponent(programId)}`
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (data.success) {
                            alert(data.message || 'Program deleted');
                            fetchPrograms();
                        } else {
                            alert(data.message || 'Failed to delete program');
                        }
                    })
                    .catch((err) => console.error('Error deleting program:', err));
            })
            .catch((err) => {
                console.error('Error checking program usage:', err);
                alert('Failed to check program usage. Try again later.');
            });
    }

    function fetchPrograms() {
        fetch(`${API_BASE}fetch_programs.php`)
            .then((r) => r.json())
            .then((programs) => {
                programList.innerHTML = '';
                programs.forEach((program) => {
                    const programItem = programItemTemplate.content.cloneNode(true);
                    programItem.querySelector('.program_name').textContent = program.name;
                    const itemEl = programItem.querySelector('.program-item');
                    itemEl.dataset.programId = program.id;

                    // inline edit elements
                    const editButton = programItem.querySelector('.edit-btn');
                    const inlineEdit = programItem.querySelector('.inline-edit');
                    const editInput = inlineEdit.querySelector('.edit-input');
                    const saveBtn = inlineEdit.querySelector('.save-edit-btn');
                    const cancelBtn = inlineEdit.querySelector('.cancel-edit-btn');

                    if (editButton) {
                        editButton.addEventListener('click', function (e) {
                            e.stopPropagation();
                            // show inline editor
                            inlineEdit.classList.remove('hidden');
                            editInput.value = program.name;
                            editInput.focus();
                            // close dropdown if open
                            const dropdown = this.closest('.dropdown');
                            if (dropdown) dropdown.classList.add('hidden');
                        });
                    }

                    // prevent clicks inside the inline editor from bubbling to the item (which navigates)
                    inlineEdit.addEventListener('click', (e) => { e.stopPropagation(); });
                    editInput.addEventListener('click', (e) => { e.stopPropagation(); });

                    // save handler
                    saveBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const newName = editInput.value.trim();
                        if (!newName) { alert('Program name cannot be empty.'); return; }
                        if (newName === program.name) { inlineEdit.classList.add('hidden'); return; }
                        fetch(`${API_BASE}edit_program.php`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `programId=${encodeURIComponent(program.id)}&newProgramName=${encodeURIComponent(newName)}`
                        })
                        .then(async (r) => {
                            const text = await r.text();
                            try {
                                const data = JSON.parse(text);
                                if (data.success) {
                                    // update the actual appended element
                                    itemEl.querySelector('.program_name').textContent = newName;
                                    // keep the in-memory `program` object in sync so subsequent edits
                                    // will prefill the correct (updated) name instead of the old one
                                    program.name = newName;
                                    inlineEdit.classList.add('hidden');
                                    // show success silently (no error alerts); keep an optional confirmation
                                } else {
                                    console.error('Server returned failure JSON:', data);
                                    // show only console error to avoid false UX alerts
                                }
                            } catch (e) {
                                // Non-JSON response (PHP warnings/newlines). If HTTP OK, treat as success.
                                if (r.ok) {
                                    itemEl.querySelector('.program_name').textContent = newName;
                                    program.name = newName;
                                    inlineEdit.classList.add('hidden');
                                } else {
                                    console.error('Non-JSON server response:', text);
                                }
                            }
                        })
                        .catch(err => { console.error('Error editing program:', err); alert('Error updating program'); });
                    });

                    cancelBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        inlineEdit.classList.add('hidden');
                    });

                    const removeButton = programItem.querySelector('.remove-btn');
                    if (removeButton) {
                        removeButton.addEventListener('click', function (e) { e.stopPropagation(); deleteProgram(program.id); });
                    }

                    const optionsIcon = programItem.querySelector('.program-options');
                    if (optionsIcon) optionsIcon.addEventListener('click', function (e) { e.stopPropagation(); toggleDropdown(this); });

                    // make the program item clickable: navigate to courses page by program name
                    const itemElement = programItem.querySelector('.program-item');
                    if (itemElement) {
                        itemElement.addEventListener('click', () => {
                            const programSlug = encodeURIComponent(program.name);
                            // programs.html is inside program_management/, courses page now lives in course_management/
                            window.location.href = `../course_management/courses.html?program=${programSlug}`;
                        });
                    }

                    programList.appendChild(programItem);
                });
            })
            .catch((err) => console.error('Error fetching programs:', err));
    }

    // Initial load
    fetchPrograms();
});
