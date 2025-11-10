// Course-specific script placed inside course_management/
// Adjusted endpoints: local course endpoints are expected to be in the same folder.

document.addEventListener("DOMContentLoaded", function() {
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    if (header && sidebar) sidebar.style.top = `${header.offsetHeight}px`;

    const confirmBtn = document.getElementById("confirmCourseBtn");
    let programId = null;

    const urlParams = new URLSearchParams(window.location.search);
    const programName = urlParams.get("program");
    const createCourseBtn = document.getElementById("createCourseBtn");
    const courseInputContainer = document.getElementById("courseInputContainer");
    const courseItemTemplate = document.getElementById("courseItemTemplate");
    const courseInput = document.getElementById("courseInput");
    const courseCodeInput = document.getElementById("courseCodeInput");
    const courseSearchInput = document.getElementById("courseSearchInput");
    const courseList = document.getElementById("courseList");

    courseSearchInput.addEventListener("input", function () {
        const searchTerm = courseSearchInput.value.toLowerCase().trim();
        const courseItems = courseList.querySelectorAll(".course-item");
        courseItems.forEach((item) => {
            const courseName = item.querySelector(".course-name").textContent.toLowerCase();
            item.style.display = courseName.includes(searchTerm) ? "" : "none";
        });
    });

    // fetch programs from program_management (one level up)
    fetch("../program_management/fetch_programs.php")
        .then((response) => response.json())
        .then((programs) => {
            const decodedProgramName = programName ? decodeURIComponent(programName) : null;
            let program = null;
            if (decodedProgramName) {
                // strict match first (case-insensitive)
                program = programs.find((p) => p.name && p.name.trim().toLowerCase() === decodedProgramName.trim().toLowerCase());
                // fallback: substring match
                if (!program) {
                    program = programs.find((p) => p.name && p.name.trim().toLowerCase().includes(decodedProgramName.trim().toLowerCase()));
                }
            }

            if (program) {
                programId = program.id;
                fetchCourses();
            } else {
                console.error("Program not found for parameter:", programName, "decoded:", decodedProgramName, "available programs:", programs);
                // if no program param provided or not found, do not crash — optionally show all courses or a helpful message
            }
        })
        .catch((error) => console.error("Error fetching programs:", error));

    createCourseBtn.addEventListener("click", () => {
        courseInputContainer.classList.remove("hidden");
        courseInput.focus();
    });

    confirmBtn.addEventListener("click", () => {
        const courseName = courseInput.value.trim();
        const courseCode = (courseCodeInput.value || "").trim().toUpperCase();
        if (!courseName) {
            alert("Please enter a valid course name.");
            return;
        }
        if (!programId) {
            alert("Program ID is not set. Please try again.");
            return;
        }

        fetch("create_course.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `programId=${encodeURIComponent(programId)}&courseName=${encodeURIComponent(courseName)}&courseCode=${encodeURIComponent(courseCode)}`
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                courseInput.value = "";
                fetchCourses();
                courseInputContainer.classList.add("hidden");
            } else {
                alert(data.message || 'Failed to create course');
            }
        })
        .catch((err) => console.error('Error creating course:', err));
    });

    function fetchCourses() {
        if (!programId) return;
        fetch(`fetch_courses.php?programId=${encodeURIComponent(programId)}`)
                .then((response) => response.json())
                .then((courses) => {
                    console.debug('fetchCourses response for programId', programId, courses);
                courseList.innerHTML = '';
                if (Array.isArray(courses) && courses.length > 0) {
                    courses.forEach((course) => {
                        const courseItem = courseItemTemplate.content.cloneNode(true);
                        // keep raw name and code separate to avoid accidental duplication when editing
                        const rawName = course.name || '';
                        const code = course.course_code && course.course_code.trim() !== '' ? course.course_code.trim() : '';
                        const displayName = code ? `${rawName} [${code}]` : rawName;
                        courseItem.querySelector(".course-name").textContent = displayName;
                        const itemEl = courseItem.querySelector('.course-item');
                        itemEl.dataset.courseId = course.id;

                        const editButton = courseItem.querySelector('.edit-btn');
                        const removeButton = courseItem.querySelector('.remove-btn');
                        const inlineEdit = courseItem.querySelector('.inline-edit');
                        const editInput = inlineEdit.querySelector('.edit-input');
                        const editCodeInput = inlineEdit.querySelector('.edit-code-input');
                        const saveBtn = inlineEdit.querySelector('.save-edit-btn');
                        const cancelBtn = inlineEdit.querySelector('.cancel-edit-btn');

                        if (editButton) {
                            editButton.addEventListener('click', function(e) {
                                e.stopPropagation();
                                inlineEdit.classList.remove('hidden');
                                // Read current values from the mutable `course` object so
                                // the editor always shows the latest saved data (in-memory)
                                editInput.value = course.name || '';
                                if (editCodeInput) editCodeInput.value = (course.course_code || '').trim().toUpperCase();
                                editInput.focus();
                                const dropdown = this.closest('.dropdown');
                                if (dropdown) dropdown.classList.add('hidden');
                            });
                        }

                        // stop propagation for clicks inside the inline editor
                        inlineEdit.addEventListener('click', (e) => e.stopPropagation());
                        editInput.addEventListener('click', (e) => e.stopPropagation());

                        saveBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            // take only the name portion from the input (user should edit only the name)
                            const rawInput = (editInput.value || '').trim();
                            // If user accidentally included the code in brackets in the name
                            // (e.g. "Name [CODE]"), strip a trailing bracketed token so we
                            // don't end up duplicating the code when we render the label.
                            const newName = rawInput.replace(/\s*\[[^\]]+\]\s*$/, '').trim();
                            const newCode = editCodeInput ? (editCodeInput.value || '').trim().toUpperCase() : code;
                            if (!newName) { alert('Course name cannot be empty.'); return; }
                            if (newName === rawName) { inlineEdit.classList.add('hidden'); return; }
                            fetch('edit_course.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `courseId=${encodeURIComponent(course.id)}&newCourseName=${encodeURIComponent(newName)}&newCourseCode=${encodeURIComponent(newCode)}` })
                                .then(async (r) => {
                                    const text = await r.text();
                                    try {
                                        const data = JSON.parse(text);
                                        if (data.success) {
                                            // update in-memory course name and code and the displayed label
                                            course.name = newName;
                                            course.course_code = newCode;
                                            const updatedDisplay = newCode ? `${newName} [${newCode}]` : newName;
                                            itemEl.querySelector('.course-name').textContent = updatedDisplay;
                                            inlineEdit.classList.add('hidden');
                                        } else {
                                            console.error('Server returned failure JSON:', data);
                                        }
                                    } catch (e) {
                                        if (r.ok) {
                                            // Non-JSON success: treat as success similarly
                                            course.name = newName;
                                            course.course_code = newCode;
                                            const updatedDisplay = newCode ? `${newName} [${newCode}]` : newName;
                                            itemEl.querySelector('.course-name').textContent = updatedDisplay;
                                            inlineEdit.classList.add('hidden');
                                        } else {
                                            console.error('Non-JSON server response:', text);
                                        }
                                    }
                                }).catch(err => { console.error('Error editing course:', err); alert('Error updating course'); });
                        });

                        cancelBtn.addEventListener('click', function(e) { e.stopPropagation(); inlineEdit.classList.add('hidden'); });

                        if (removeButton) removeButton.addEventListener('click', function(e) { e.stopPropagation(); removeCourse(this, course.id); });
                        courseList.appendChild(courseItem);
                    });
                } else {
                    courseList.innerHTML = "<p>No courses available har.</p>";
                }
            })
            .catch((error) => console.error('Error fetching courses:', error));
    }

});

function removeCourse(button, courseId) {
    const courseItem = button.closest('.course-item');
    // First check whether any classes reference this course so we can warn the user
    fetch(`check_course_usage.php?courseId=${encodeURIComponent(courseId)}`)
        .then((r) => r.json())
        .then((info) => {
            if (!info.success) {
                alert(info.message || 'Failed to check course usage.');
                return;
            }

            const count = parseInt(info.count || 0, 10);
            if (count > 0) {
                // If there are dependent classes, warn the user and do not proceed with delete.
                alert(`This course is used by ${count} class(es). You must remove or reassign those classes before deleting the course.`);
                return;
            }

            // No dependent classes — safe to confirm deletion
            if (!confirm("Are you sure you want to delete this course? This action cannot be undone.")) return;

            fetch("delete_course.php", { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `courseId=${encodeURIComponent(courseId)}` })
                .then((r) => r.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.message);
                        courseItem.remove();
                    } else {
                        alert(data.message);
                    }
                })
                .catch((err) => console.error('Error deleting course:', err));
        })
        .catch((err) => {
            console.error('Error checking course usage:', err);
            alert('Failed to check course usage. Try again later.');
        });
}

// legacy function kept for compatibility; forward to inline edit button if present
function editCourse(button, courseId) {
    try {
        const courseItem = button.closest('.course-item');
        const editBtn = courseItem.querySelector('.edit-btn');
        if (editBtn) { editBtn.click(); }
    } catch (e) { console.error('editCourse fallback error', e); }
}
