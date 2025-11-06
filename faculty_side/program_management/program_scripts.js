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
        const programItem = button.closest('.program-item');
        const programNameSpan = programItem.querySelector('.program_name');
        const programId = programItem.dataset.programId;
        const newProgramName = prompt('Edit program name:', programNameSpan.textContent);

        if (!newProgramName) return;

        fetch(`${API_BASE}edit_program.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `programId=${encodeURIComponent(programId)}&newProgramName=${encodeURIComponent(newProgramName.trim())}`
        })
            .then((r) => r.json())
            .then((data) => {
                if (data.success) {
                    alert(data.message || 'Program updated');
                    programNameSpan.textContent = newProgramName.trim();
                } else {
                    alert(data.message || 'Failed to update program');
                }
            })
            .catch((err) => console.error('Error editing program:', err));
    }

    function deleteProgram(programId) {
        if (!confirm('Are you sure you want to delete this program?')) return;

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
    }

    function fetchPrograms() {
        fetch(`${API_BASE}fetch_programs.php`)
            .then((r) => r.json())
            .then((programs) => {
                programList.innerHTML = '';
                programs.forEach((program) => {
                    const programItem = programItemTemplate.content.cloneNode(true);
                    programItem.querySelector('.program_name').textContent = program.name;
                    programItem.querySelector('.program-item').dataset.programId = program.id;

                    const editButton = programItem.querySelector('.edit-btn');
                    if (editButton) {
                        editButton.addEventListener('click', function (e) { e.stopPropagation(); editProgram(this); });
                    }

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
