// class_scripts.js (moved to class_management)
// Adjusted to run from the same folder as the PHP endpoints.

const API_BASE = '';

document.addEventListener("DOMContentLoaded", () => {
    const createClassBtn = document.getElementById("createClassBtn");
    const classInputContainer = document.getElementById("classInputContainer");
    const confirmClassBtn = document.getElementById("confirmClassBtn");
    const classList = document.getElementById("classList");
    const classItemTemplate = document.getElementById("classItemTemplate");

    const courseSearchInput = document.getElementById("courseSearchInput");
    const courseDropdown = document.getElementById("courseDropdown");
    const classInput = document.getElementById("classInput");
    const termNumberSelect = document.getElementById("term_number");
    const startYearSelect = document.getElementById("startYear");
    const endYearSelect = document.getElementById("endYear");

    createClassBtn.addEventListener("click", () => {
        classInputContainer.classList.remove("hidden");
        classInput.focus();
    });

    const DEBUG_LOGS = true;
    // Cache courses client-side to avoid repeated network requests while typing
    let cachedCourses = null;
    let coursesPrefetching = false;

    const fetchCourses = async (searchTerm = "") => {
        try {
            // Course endpoints live in ../course_management relative to this script
            const fetchUrl = '../course_management/fetch_courses.php' + `?searchTerm=${encodeURIComponent(searchTerm)}`;
            const response = await fetch(fetchUrl);
            if (!response.ok) throw new Error("Failed to fetch courses");
            const courses = await response.json();
            if (DEBUG_LOGS) {
                try {
                    console.groupCollapsed(`Course fetch — term: "${searchTerm}" (${courses.length || 0} results)`);
                    console.log(courses);
                    if (Array.isArray(courses) && courses.length) console.table(courses);
                    console.groupEnd();
                } catch (e) {
                    console.log('Fetched courses (raw):', courses);
                }
            }
            return courses;
        } catch (error) {
            console.error(error);
            return [];
        }
    };

    // Render a dropdown of matching courses (used by the create-class course search)
    function renderDropdown(matches) {
        try {
            courseDropdown.innerHTML = '';
            if (!Array.isArray(matches) || matches.length === 0) {
                courseDropdown.classList.add('hidden');
                return;
            }

            // Limit suggestions to a reasonable number
            // Attach data attributes so the UI can directly carry the selected course id
            matches.slice(0, 20).forEach((c) => {
                const label = (c.course_code && String(c.course_code).trim() !== '') ? `${c.course_code} ${c.name || c.course_name || ''}`.trim() : (c.name || c.course_name || '');
                const row = document.createElement('div');
                row.className = 'course-item';
                row.textContent = label;
                // Store useful values on the DOM node for easy retrieval on click
                row.dataset.courseId = c.id || c.course_id || '';
                row.dataset.courseCode = c.course_code || '';
                row.dataset.courseName = c.name || c.course_name || '';
                row.addEventListener('click', (e) => {
                    e.stopPropagation();
                    // Set the visible input to the friendly label, but also keep the selected id
                    courseSearchInput.value = label;
                    // Set hidden selectedCourseId so creation uses the exact ID (avoids string matching issues)
                    try {
                        const sel = document.getElementById('selectedCourseId');
                        if (sel) sel.value = row.dataset.courseId || '';
                    } catch (err) { /* ignore */ }
                    courseDropdown.classList.add('hidden');
                });
                courseDropdown.appendChild(row);
            });

            courseDropdown.classList.remove('hidden');
        } catch (err) {
            console.error('renderDropdown error', err);
        }
    }

    const fetchClasses = async () => {
        try {
            const response = await fetch("fetch_classes.php");
            if (!response.ok) throw new Error("Failed to fetch classes");
            const data = await response.json();
            console.debug('fetchClasses response:', data);

            // (debug panel removed) — keep console.debug for development

            if (data.success) {
                console.log("Fetched classes:", data.classes);
                renderClasses(data.classes);
            } else {
                console.error("Error fetching classes:", data.message);
            }
        } catch (error) {
            console.error("Error:", error);
        }
    };

    const renderClasses = (classes) => {
        classList.innerHTML = "";
        classes.forEach((classItem) => {
            const template = document.getElementById("classItemTemplate");
            const item = template.content.cloneNode(true);

            // { changed code }
            // Build a display string that avoids repeating course_name/class_name or term/year if already present.
            function buildClassDisplay(ci) {
                // Prefer course_code for class-item display (so created classes show the code)
                const course = (ci.course_code || ci.course_name || "").trim();
                const cls = (ci.class_name || "").trim();
                const termNum = ci.term_number;
                const startY = ci.start_year;
                const endY = ci.end_year;

                // Normalize for comparisons
                const courseLower = course.toLowerCase();
                const clsLower = cls.toLowerCase();
                const yearRange = `${startY}-${endY}`;

                let base = "";

                if (course && cls) {
                    // If class name already contains the course text, prefer class name only.
                    if (clsLower.includes(courseLower)) {
                        base = cls;
                    } else if (courseLower.includes(clsLower)) {
                        base = course;
                    } else {
                        base = `${course} ${cls}`;
                    }
                } else {
                    base = course || cls || "";
                }

                const baseLower = base.toLowerCase();

                // If base already includes Term X or the year range, don't append term/year again.
                if (baseLower.includes(`term ${String(termNum).toLowerCase()}`) || baseLower.includes(yearRange)) {
                    return base;
                }

                // Append term/year if not present
                const termPart = `Term ${termNum} ${startY}-${endY}`;
                return `${base} ${termPart}`.trim();
            }

            item.querySelector(".class-name").textContent = buildClassDisplay(classItem);

            item.querySelector(".class-item").addEventListener("click", () => {
                // class-handling is now in the same folder (class_management)
                window.location.href = `./class-handling.html?class_id=${classItem.class_id}`;
            });

            item.querySelector(".class-options").addEventListener("click", (e) => {
                e.stopPropagation();
                toggleDropdown(e.target);
            });

            item.querySelector(".dropdown").addEventListener("click", (e) => {
                e.stopPropagation();
            });

            item.querySelector(".edit-btn").setAttribute("onclick", `editClass(this, ${classItem.class_id})`);
            item.querySelector(".remove-btn").setAttribute("onclick", `removeClass(this, ${classItem.class_id})`);

            // inline edit wiring
            const inlineEdit = item.querySelector('.inline-edit');
            const editInput = inlineEdit.querySelector('.edit-input');
            const saveBtn = inlineEdit.querySelector('.save-edit-btn');
            const cancelBtn = inlineEdit.querySelector('.cancel-edit-btn');
            const editBtn = item.querySelector('.edit-btn');
            const itemEl = item.querySelector('.class-item');

            if (editBtn) {
                // When user clicks the Edit button in the dropdown, open the inline editor
                // and make absolutely sure the dropdown is hidden. Stop propagation so
                // the row click doesn't navigate.
                editBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    try {
                        const classItemEl = editBtn.closest('.class-item') || item.querySelector('.class-item');

                        // Hide all dropdowns first to avoid any remaining visible menu
                        document.querySelectorAll('.dropdown').forEach((menu) => {
                            menu.classList.add('hidden');
                        });

                        // Also hide the dropdown that belongs to this item (if any)
                        if (classItemEl) {
                            const localDd = classItemEl.querySelector('.dropdown');
                            if (localDd) {
                                localDd.classList.add('hidden');
                            }
                        }

                        // Show inline editor for this item
                        const localInline = classItemEl ? classItemEl.querySelector('.inline-edit') : inlineEdit;
                        const localInput = localInline ? localInline.querySelector('.edit-input') : editInput;
                        if (localInline) {
                            localInline.classList.remove('hidden');
                            if (localInput) {
                                localInput.value = (classItemEl ? (classItemEl.querySelector('.class-name') && classItemEl.querySelector('.class-name').textContent) : item.querySelector('.class-name').textContent).trim();
                                localInput.focus();
                            }
                        }
                    } catch (err) {
                        // Fallback: show the existing inline editor variable
                        inlineEdit.classList.remove('hidden');
                        editInput.value = item.querySelector('.class-name').textContent.trim();
                        editInput.focus();
                    }
                });
            }


            // Prevent clicks inside inline editor from triggering item navigation
            inlineEdit.addEventListener('click', (e) => e.stopPropagation());
            editInput.addEventListener('click', (e) => e.stopPropagation());

            saveBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const newName = editInput.value.trim();
                if (!newName) { alert('Class name cannot be empty.'); return; }
                if (newName === itemEl.querySelector('.class-name').textContent.trim()) { inlineEdit.classList.add('hidden'); return; }

                fetch('edit_class.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `classId=${encodeURIComponent(classItem.class_id)}&newClassName=${encodeURIComponent(newName)}`
                }).then(async (r) => {
                    const text = await r.text();
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            itemEl.querySelector('.class-name').textContent = newName;
                            inlineEdit.classList.add('hidden');
                            if (data.message) alert(data.message);
                                // Ensure dropdown is hidden after save
                                try { item.querySelector('.dropdown').classList.add('hidden'); } catch (e) {}
                        } else {
                            console.error('Server returned failure JSON:', data);
                            alert(data.message || 'Error updating class');
                        }
                    } catch (parseErr) {
                        if (r.ok) {
                            // backend responded non-JSON but HTTP OK — treat as success
                            itemEl.querySelector('.class-name').textContent = newName;
                            inlineEdit.classList.add('hidden');
                        } else {
                            console.error('Non-JSON server response:', text);
                            alert('Error updating class');
                        }
                    }
                }).catch(err => { console.error('Error editing class:', err); alert('Error updating class'); });
            });

            cancelBtn.addEventListener('click', (e) => { e.stopPropagation(); inlineEdit.classList.add('hidden'); try { item.querySelector('.dropdown').classList.add('hidden'); } catch (e) {} });

            // end of per-item wiring
            // append the cloned template (DocumentFragment) to the list
            classList.appendChild(item);

        });

    };

    // Prefetch courses on focus (so typing doesn't issue new network requests)
    courseSearchInput.addEventListener('focus', async () => {
        if (cachedCourses === null && !coursesPrefetching) {
            coursesPrefetching = true;
            try {
                cachedCourses = await fetchCourses('');
            } catch (e) {
                cachedCourses = [];
            } finally {
                coursesPrefetching = false;
            }
        }
    });

    courseSearchInput.addEventListener("input", async () => {
        // If the user manually types, clear any previously-selected course id
        try { document.getElementById('selectedCourseId').value = ''; } catch (e) {}
        const searchTerm = courseSearchInput.value.trim();
    if (DEBUG_LOGS) console.log("class-search: Search term:", searchTerm);

        // Ensure we have a cached copy (fallback to one-time fetch if not prefetched)
        if (cachedCourses === null) {
            // this will perform a single network request; after this we filter locally
            cachedCourses = await fetchCourses('');
        }

        // Filter locally (no additional network requests)
        const norm = searchTerm.toLowerCase();
        const matches = (Array.isArray(cachedCourses) ? cachedCourses.filter(c => {
            const code = c.course_code ? String(c.course_code).toLowerCase() : '';
            const name = (c.name || c.course_name || '').toLowerCase();
            return code.includes(norm) || name.includes(norm);
        }) : []);

        // Friendly console log showing matches (concise)
        try {
            const labels = matches.map(c => (c.course_code && String(c.course_code).trim() !== "") ? c.course_code : (c.name || c.course_name || '')).filter(Boolean);
            if (DEBUG_LOGS) console.log(`class-search: Search term: "${searchTerm}" -> matches: ${labels.length ? labels.join(', ') : 'none'}`);
        } catch (e) { /* ignore */ }

        if (DEBUG_LOGS) {
            try {
                console.groupCollapsed(`class-search (local) — "${searchTerm}" (${matches.length || 0} results)`);
                console.log('class-search results (local):', matches);
                if (Array.isArray(matches) && matches.length) console.table(matches);
                console.groupEnd();
            } catch (e) {
                console.log('Search results (local):', matches);
            }
        }

        renderDropdown(matches);
    });

    document.addEventListener("click", (event) => {
        if (!courseSearchInput.contains(event.target) && !courseDropdown.contains(event.target)) {
            courseDropdown.classList.add("hidden");
        }
    });

    const currentYear = new Date().getFullYear();
    const maxStartYear = Math.min(currentYear, 2025);
    const minStartYear = 2010;

    for (let year = minStartYear; year <= maxStartYear; year++) {
        const option = document.createElement("option");
        option.value = year;
        option.textContent = year;
        startYearSelect.appendChild(option);
    }

    startYearSelect.addEventListener("change", () => {
        endYearSelect.innerHTML = '<option value="" disabled selected>Select End Year</option>';

        const selectedStartYear = parseInt(startYearSelect.value, 10);
        const maxEndYear = Math.min(selectedStartYear + 10, 2025);

        for (let year = selectedStartYear + 1; year <= maxEndYear; year++) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            endYearSelect.appendChild(option);
        }
    });

    confirmClassBtn.addEventListener("click", async () => {
        const courseName = courseSearchInput.value.trim();
        const className = classInput.value.trim();
        const termNumber = termNumberSelect.value.replace("term_", "");
        const startYear = startYearSelect.value;
        const endYear = endYearSelect.value;

        if (!courseName || !className || !termNumber || !startYear || !endYear) {
            alert("Please fill out all fields.");
            return;
        }

        // Prefer the selected course id saved when user clicked a dropdown item (more reliable)
        let courseId = '';
        try {
            const sel = document.getElementById('selectedCourseId');
            if (sel && sel.value) courseId = sel.value;
        } catch (err) { /* ignore */ }

        // Fallback: if no selectedCourseId (user typed and didn't click a suggestion), try to resolve by searching
        if (!courseId) {
            const fetchUrl2 = '../course_management/fetch_courses.php' + `?searchTerm=${encodeURIComponent(courseName)}`;
            const response = await fetch(fetchUrl2);
            const courses = await response.json();
            console.log("Create-class matched courses (fallback search):", courses);
            const normalizedInput = courseName.toLowerCase();
            const course = courses.find((c) => {
                const code = c.course_code ? String(c.course_code).toLowerCase() : "";
                const name = c.name ? String(c.name).toLowerCase() : "";
                // allow either exact matches or inputs that start with the code (e.g. "PROGLOD ...")
                return (code && (code === normalizedInput || normalizedInput.startsWith(code))) || name === normalizedInput;
            });

            if (!course) {
                alert("Course not found.");
                return;
            }

            courseId = course.id;
        }

        try {
            console.log("Sending data:", { courseId, className, termNumber, startYear, endYear });

            const result = await fetch('create_class.php', {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `courseId=${courseId}&className=${encodeURIComponent(className)}&termNumber=${termNumber}&startYear=${startYear}&endYear=${endYear}`,
            });

            const data = await result.json();
            console.log("Response from create_class.php:", data);

            if (data.success) {
                classInput.value = "";
                courseSearchInput.value = "";
                try { document.getElementById('selectedCourseId').value = ''; } catch (e) {}
                termNumberSelect.value = "";
                startYearSelect.value = "";
                endYearSelect.value = "";
                classInputContainer.classList.add("hidden");

                fetchClasses();
                alert("Class created successfully!");
            } else {
                alert(data.message || "Failed to create class.");
            }
        } catch (error) {
            console.error("Error:", error);
            alert("An error occurred while creating the class. Please try again.");
        }
    });

    function toggleDropdown(icon) {
        const dropdown = icon.nextElementSibling;
        document.querySelectorAll('.dropdown').forEach((menu) => {
            if (menu !== dropdown) menu.classList.add('hidden');
        });
        // If the dropdown is currently hidden, show it and clear any inline
        // style that might keep it invisible. If it's visible, hide it.
        const willShow = dropdown.classList.contains('hidden');
        if (willShow) {
            dropdown.classList.remove('hidden');
            try { dropdown.style.display = ''; } catch (e) { /* ignore */ }
        } else {
            dropdown.classList.add('hidden');
        }
    }

    window.toggleDropdown = toggleDropdown;

    document.addEventListener('click', (event) => {
        const isDropdown = event.target.closest('.dropdown');
        const isClassOptionsIcon = event.target.closest('.class-options');

        if (!isDropdown && !isClassOptionsIcon) {
            document.querySelectorAll('.dropdown').forEach((menu) => {
                menu.classList.add('hidden');
            });
        }
    });

    const courseClassInput = document.getElementById("courseClassInput");
    courseClassInput.addEventListener("input", () => {
        const searchTerm = courseClassInput.value.toLowerCase().trim();
        const classItems = classList.querySelectorAll(".class-item");
        console.log("Search term:", searchTerm);
        classItems.forEach((item) => {
            const className = item.querySelector(".class-name").textContent.toLowerCase();
            if (className.includes(searchTerm)) {
                item.style.display = "";
            } else {
                item.style.display = "none";
            }
        });
    });

    function editClass(button, classId) {
    // Open the inline editor directly for the given item instead of
    // triggering a click on the edit button (which could cause recursion
    // or ordering issues). Also ensure dropdowns are hidden.
        try {
            const classItem = button.closest('.class-item');
            if (!classItem) return;

            // Hide all dropdowns
            document.querySelectorAll('.dropdown').forEach((menu) => {
                menu.classList.add('hidden');
            });

            const inline = classItem.querySelector('.inline-edit');
            const input = inline ? inline.querySelector('.edit-input') : null;
            if (inline) {
                inline.classList.remove('hidden');
                if (input) {
                    input.value = (classItem.querySelector('.class-name') && classItem.querySelector('.class-name').textContent || '').trim();
                    input.focus();
                }
            }
        } catch (e) {
            console.error('editClass fallback error', e);
        }
    }

    window.editClass = editClass;

    function removeClass(button, classId) {
        if (confirm("Are you sure you want to delete this class?")) {
            fetch('delete_class.php', {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `classId=${encodeURIComponent(classId)}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // { changed code }
                        // Refresh the list so the UI always matches server state.
                        fetchClasses();
                        alert(data.message);
                    } else {
                        alert(data.message || "Failed to delete class.");
                    }
                })
                .catch((error) => {
                    console.error("Error deleting class:", error);
                    alert("An error occurred while deleting the class.");
                });
        }
    }

    window.removeClass = removeClass;

    // Initial load: fetch classes so the list is populated when the page opens
    fetchClasses();
});
