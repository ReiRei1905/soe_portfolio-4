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
    const assignConfirmBtn = document.getElementById('confirmAssignProfessorBtn');
    const assignModalElement = document.getElementById('assignProfessorModal');
    let canManageClasses = false;
    let canCreateClasses = false;
    let isProgramDirector = false;
    let isProfessorUser = false;
    let hasAssignedProgram = false;
    let assignProfessorModal = null;
    let currentAssignClassId = 0;
    let currentAssignClassName = '';

    function redirectToLogin() {
        window.location.href = '../../user_info_V3/index.php';
    }

    function normalizeRoleLabel(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[_-]+/g, ' ')
            .replace(/\s+/g, ' ');
    }

    function updateProgramDirectorNotice() {
        const notice = document.getElementById('pdAssignmentNoticeClasses');
        if (!notice) return;

        const shouldShow = isProgramDirector && !hasAssignedProgram;
        notice.classList.toggle('hidden', !shouldShow);
    }

    if (assignModalElement && window.bootstrap && window.bootstrap.Modal) {
        assignProfessorModal = new window.bootstrap.Modal(assignModalElement);
    }

    async function loadClassAccessContext() {
        try {
            const response = await fetch('../../user_info_V3/get_session_user.php', { credentials: 'same-origin' });
            const payload = await response.json();
            if (response.status === 401 || response.status === 403 || !response.ok || !payload.success) {
                redirectToLogin();
                canManageClasses = false;
                throw new Error('Unauthorized');
            }

            const roleType = normalizeRoleLabel(payload.user?.roleType || '');
            const facultyRole = normalizeRoleLabel(payload.user?.facultyRole || '');
            const status = Number(payload.user?.status || 0);
            const isVerified = Number(payload.user?.isVerified || 0);
            const currentUserId = Number(payload.user?.userId || 0);
            const isExecDirectorRole = facultyRole.includes('executive director');
            const isProgramDirectorRole = facultyRole.includes('program director') || facultyRole.includes('program directors');
            const isProfessorRole = facultyRole.includes('professor');

            isProgramDirector = roleType === 'faculty'
                && status === 1
                && isVerified === 1
                && isProgramDirectorRole;

            isProfessorUser = roleType === 'faculty'
                && status === 1
                && isVerified === 1
                && isProfessorRole
                && !isExecDirectorRole
                && !isProgramDirectorRole;

            hasAssignedProgram = false;
            if (isProgramDirector) {
                const programResponse = await fetch('../program_management/fetch_programs.php', { credentials: 'same-origin' });
                const programs = await programResponse.json();

                if (programResponse.ok && Array.isArray(programs)) {
                    hasAssignedProgram = programs.some((program) => Number(program.assignedProgramDirectorUserId || 0) === currentUserId);
                }
            }

            canManageClasses = roleType === 'faculty'
                && status === 1
                && isVerified === 1
                && (isExecDirectorRole || (isProgramDirectorRole && hasAssignedProgram));

            canCreateClasses = roleType === 'faculty'
                && status === 1
                && isVerified === 1
                && (isExecDirectorRole || (isProgramDirectorRole && hasAssignedProgram) || isProfessorUser);
        } catch (error) {
            canManageClasses = false;
            canCreateClasses = false;
            isProgramDirector = false;
            isProfessorUser = false;
            hasAssignedProgram = false;
            console.warn('Unable to load classes access context:', error);
            if (String(error && error.message || '').toLowerCase().includes('unauthorized')) {
                throw error;
            }
        }

        updateProgramDirectorNotice();

        if (!canCreateClasses) {
            createClassBtn.style.display = 'none';
            classInputContainer.classList.add('hidden');
        }
    }

    createClassBtn.addEventListener("click", () => {
        if (!canCreateClasses) {
            alert('You only have view access for classes.');
            return;
        }
        classInputContainer.classList.remove("hidden");
        classInput.focus();
    });

    async function fetchAssignableProfessors(classId) {
        const response = await fetch(`get_verified_professors.php?classId=${encodeURIComponent(classId)}`, {
            credentials: 'same-origin'
        });
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to load professors.');
        }

        return payload.professors || [];
    }

    function renderProfessorSelectOptions(professors, selectedUserId = 0) {
        const select = document.getElementById('professorSelect');
        if (!select) return;

        select.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select Professor';
        placeholder.disabled = true;
        placeholder.selected = true;
        select.appendChild(placeholder);

        professors.forEach((professor) => {
            const option = document.createElement('option');
            option.value = String(professor.userId);
            const roleLabel = String(professor.facultyRole || '').trim();
            const roleSuffix = roleLabel ? ` (${roleLabel})` : '';
            option.textContent = `${professor.fullName}${roleSuffix} - ${professor.email}`;
            if (Number(professor.userId) === Number(selectedUserId)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    async function openAssignProfessorModal(classId, className, selectedUserId = 0) {
        if (!canManageClasses) return;

        currentAssignClassId = Number(classId);
        currentAssignClassName = className;

        const titleEl = document.getElementById('assignProfessorClassTitle');
        if (titleEl) {
            titleEl.textContent = `Class: ${className}`;
        }

        const professors = await fetchAssignableProfessors(classId);
        if (!Array.isArray(professors) || professors.length === 0) {
            alert('No verified faculty users are available for assignment.');
            return;
        }

        renderProfessorSelectOptions(professors, selectedUserId);

        if (assignProfessorModal) {
            assignProfessorModal.show();
        }
    }

    async function assignProfessorToCurrentClass() {
        if (!canManageClasses || currentAssignClassId <= 0) return;

        const select = document.getElementById('professorSelect');
        if (!select || !select.value) {
            alert('Please select a professor first.');
            return;
        }

        const body = new URLSearchParams({
            classId: String(currentAssignClassId),
            professorUserId: String(select.value)
        });

        const assignResponse = await fetch('assign_professor.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        });
        const assignPayload = await assignResponse.json();

        if (!assignResponse.ok || !assignPayload.success) {
            throw new Error(assignPayload.message || 'Failed to assign professor.');
        }

        if (assignProfessorModal) {
            assignProfessorModal.hide();
        }

        alert(assignPayload.message || `Professor assigned for ${currentAssignClassName}.`);
        await fetchClasses();
    }

    async function revokeProfessorForClass(classId) {
        if (!canManageClasses || Number(classId) <= 0) {
            return;
        }

        const confirmed = confirm('Clear the current Professor assignment so you can reassign this class?');
        if (!confirmed) return;

        const body = new URLSearchParams({
            classId: String(classId)
        });

        const response = await fetch('revoke_professor.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to clear professor assignment.');
        }

        alert(payload.message || 'Professor assignment cleared.');
    }

    async function reviewClassRequest(requestId, action, rejectionReason = '') {
        if (!canManageClasses || Number(requestId) <= 0) {
            return;
        }

        const body = new URLSearchParams({
            requestId: String(requestId),
            action: String(action)
        });

        if (action === 'reject' && rejectionReason.trim() !== '') {
            body.set('rejectionReason', rejectionReason.trim());
        }

        const response = await fetch('review_class_request.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to review class request.');
        }

        alert(payload.message || 'Class request reviewed successfully.');
    }

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
            const dedupedCourses = dedupeCoursesByLogicalIdentity(courses);
            if (DEBUG_LOGS) {
                try {
                    console.groupCollapsed(`Course fetch — term: "${searchTerm}" (${dedupedCourses.length || 0} results)`);
                    console.log(dedupedCourses);
                    if (Array.isArray(dedupedCourses) && dedupedCourses.length) console.table(dedupedCourses);
                    console.groupEnd();
                } catch (e) {
                    console.log('Fetched courses (deduped):', dedupedCourses);
                }
            }
            return dedupedCourses;
        } catch (error) {
            console.error(error);
            return [];
        }
    };

    function dedupeCoursesByLogicalIdentity(courses) {
        if (!Array.isArray(courses) || courses.length === 0) {
            return [];
        }

        const map = new Map();

        courses.forEach((course) => {
            const id = Number(course.id || course.course_id || 0);
            const rawCode = String(course.course_code || '').trim().toUpperCase();
            const rawName = String(course.name || course.course_name || '').trim();
            const normalizedName = rawName.toLowerCase().replace(/\s+/g, ' ');
            const key = rawCode ? `code:${rawCode}` : `name:${normalizedName}`;

            if (!map.has(key)) {
                map.set(key, course);
                return;
            }

            const existing = map.get(key) || {};
            const existingId = Number(existing.id || existing.course_id || 0);
            const existingHasCode = String(existing.course_code || '').trim() !== '';
            const currentHasCode = rawCode !== '';

            if ((!existingHasCode && currentHasCode) || (id > 0 && (existingId <= 0 || id < existingId))) {
                map.set(key, course);
            }
        });

        return Array.from(map.values());
    }

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
            if (response.status === 401 || response.status === 403) {
                redirectToLogin();
                return;
            }
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

            const classNameEl = item.querySelector('.class-name');
            const assignedProfEl = item.querySelector('.assigned-prof-label');
            const optionsIcon = item.querySelector('.class-options');
            const dropdown = item.querySelector('.dropdown');
            const approveRequestBtn = item.querySelector('.approve-request-btn');
            const rejectRequestBtn = item.querySelector('.reject-request-btn');
            const assignProfBtn = item.querySelector('.assign-Prof-btn');
            const editBtn = item.querySelector('.edit-btn');
            const removeBtn = item.querySelector('.remove-btn');
            const isPendingRequest = Number(classItem.is_pending_request || 0) === 1;
            const requestId = Number(classItem.request_id || 0);

            classNameEl.textContent = buildClassDisplay(classItem);

            const assignedProfessorName = String(classItem.assigned_professor_name || '').trim();
            const assignedProfessorUserId = Number(classItem.assigned_professor_user_id || 0);
            const hasAssignedProfessor = assignedProfessorUserId > 0;
            if (isPendingRequest && isProfessorUser) {
                const programDirectorName = String(classItem.program_director_name || '').trim();
                if (programDirectorName) {
                    const hasMultipleDirectors = programDirectorName.includes(' and ') || programDirectorName.includes(',');
                    assignedProfEl.textContent = hasMultipleDirectors
                        ? `Waiting for approval by Program Directors: ${programDirectorName}`
                        : `Waiting for approval by Program Director: ${programDirectorName}`;
                } else {
                    assignedProfEl.textContent = 'Waiting for Program Director approval';
                }
            } else if (isPendingRequest && canManageClasses) {
                const createdByProfessorName = String(classItem.created_by_professor_name || '').trim();
                assignedProfEl.textContent = createdByProfessorName
                    ? `Created by Professor: ${createdByProfessorName}`
                    : 'Created by Professor: Unknown';
            } else if (isProfessorUser) {
                const assignedByProgramDirectorName = String(classItem.assigned_by_program_director_name || '').trim();
                assignedProfEl.textContent = assignedByProgramDirectorName
                    ? `Assigned by Program Director: ${assignedByProgramDirectorName}`
                    : 'Assigned by Program Director: Not available';
            } else {
                assignedProfEl.textContent = assignedProfessorName
                    ? `Professor: ${assignedProfessorName}`
                    : 'Professor: Not assigned';
            }

            if (assignProfBtn) {
                assignProfBtn.textContent = hasAssignedProfessor ? 'Reassign Professor' : 'Assign Professor';
            }

            if (!isPendingRequest) {
                item.querySelector(".class-item").addEventListener("click", () => {
                    // class-handling is now in the same folder (class_management)
                    window.location.href = `./class-handling.html?class_id=${classItem.class_id}`;
                });
            }

            optionsIcon.addEventListener("click", (e) => {
                e.stopPropagation();
                toggleDropdown(e.target);
            });

            dropdown.addEventListener("click", (e) => {
                e.stopPropagation();
            });

            if (!canManageClasses) {
                if (optionsIcon) optionsIcon.style.display = 'none';
                if (dropdown) dropdown.classList.add('hidden');
            }

            if (isPendingRequest) {
                assignProfBtn.classList.add('hidden');
                editBtn.classList.add('hidden');
                removeBtn.classList.add('hidden');

                if (canManageClasses) {
                    approveRequestBtn.classList.remove('hidden');
                    rejectRequestBtn.classList.remove('hidden');
                } else if (optionsIcon) {
                    optionsIcon.style.display = 'none';
                }
            } else {
                approveRequestBtn.classList.add('hidden');
                rejectRequestBtn.classList.add('hidden');
            }

            assignProfBtn.addEventListener('click', async () => {
                if (!canManageClasses) return;
                try {
                    await openAssignProfessorModal(classItem.class_id, buildClassDisplay(classItem), assignedProfessorUserId);
                    dropdown.classList.add('hidden');
                } catch (error) {
                    alert(error.message || 'Failed to load professors.');
                }
            });


            if (approveRequestBtn) {
                approveRequestBtn.addEventListener('click', async () => {
                    if (!canManageClasses || !isPendingRequest || requestId <= 0) return;
                    try {
                        await reviewClassRequest(requestId, 'approve');
                        dropdown.classList.add('hidden');
                        await fetchClasses();
                    } catch (error) {
                        alert(error.message || 'Failed to approve class request.');
                    }
                });
            }

            if (rejectRequestBtn) {
                rejectRequestBtn.addEventListener('click', async () => {
                    if (!canManageClasses || !isPendingRequest || requestId <= 0) return;
                    const reason = prompt('Optional rejection reason:') || '';
                    try {
                        await reviewClassRequest(requestId, 'reject', reason);
                        dropdown.classList.add('hidden');
                        await fetchClasses();
                    } catch (error) {
                        alert(error.message || 'Failed to reject class request.');
                    }
                });
            }

            editBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                editClass(editBtn, classItem.class_id);
            });

            removeBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                await removeClass(removeBtn, classItem.class_id);
            });

            // inline edit wiring
            const inlineEdit = item.querySelector('.inline-edit');
            const editInput = inlineEdit.querySelector('.edit-input');
            const saveBtn = inlineEdit.querySelector('.save-edit-btn');
            const cancelBtn = inlineEdit.querySelector('.cancel-edit-btn');
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
    // Use the current year as the maximum start year so the UI reflects present day
    const maxStartYear = currentYear;
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
        // Allow end year up to 10 years after the selected start year
        const maxEndYear = selectedStartYear + 10;

        for (let year = selectedStartYear + 1; year <= maxEndYear; year++) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            endYearSelect.appendChild(option);
        }
    });

    confirmClassBtn.addEventListener("click", async () => {
        if (!canCreateClasses) {
            alert('You are not allowed to create classes.');
            return;
        }
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
    if (!canManageClasses) {
            alert('You are not allowed to edit classes.');
            return;
        }
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

    async function removeClass(button, classId) {
        if (!canManageClasses) {
            alert('You are not allowed to delete classes.');
            return;
        }
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

    if (assignConfirmBtn) {
        assignConfirmBtn.addEventListener('click', async () => {
            try {
                await assignProfessorToCurrentClass();
            } catch (error) {
                alert(error.message || 'Failed to assign professor.');
            }
        });
    }

    // Initial load: fetch classes so the list is populated when the page opens
    loadClassAccessContext()
        .then(() => {
            fetchClasses();
        })
        .catch(() => {
            // Redirect is already handled for unauthorized sessions.
        });
});
