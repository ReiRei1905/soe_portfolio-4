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

    const fetchClasses = async () => {
        try {
            const response = await fetch("fetch_classes.php");
            if (!response.ok) throw new Error("Failed to fetch classes");
            const data = await response.json();

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

            item.querySelector(".class-name").textContent = `${classItem.course_name} ${classItem.class_name} Term ${classItem.term_number} ${classItem.start_year}-${classItem.end_year}`;

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

            classList.appendChild(item);
        });
    };

    fetchClasses();

    const renderDropdown = (courses) => {
        courseDropdown.innerHTML = "";
        if (courses.length === 0) {
            courseDropdown.classList.add("hidden");
            return;
        }

        courses.forEach((course) => {
            const item = document.createElement("div");
            item.className = "course-item";
            const displayLabel = course.course_code && course.course_code.trim() !== "" ? course.course_code : course.name;
            item.textContent = displayLabel;
            item.addEventListener("click", () => {
                courseSearchInput.value = displayLabel;
                courseDropdown.classList.add("hidden");
            });
            courseDropdown.appendChild(item);
        });

        courseDropdown.classList.remove("hidden");
    };

    courseSearchInput.addEventListener("input", async () => {
        const searchTerm = courseSearchInput.value.trim();
        if (DEBUG_LOGS) console.log("Search term:", searchTerm);
        const courses = await fetchCourses(searchTerm);
        if (DEBUG_LOGS) {
            try {
                console.groupCollapsed(`Course search input — "${searchTerm}" (${courses.length || 0} results)`);
                console.log('Search results (from input):', courses);
                if (Array.isArray(courses) && courses.length) console.table(courses);
                console.groupEnd();
            } catch (e) {
                console.log('Search results (from input):', courses);
            }
        }
        renderDropdown(courses);
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

        const fetchUrl2 = '../course_management/fetch_courses.php' + `?searchTerm=${encodeURIComponent(courseName)}`;
        const response = await fetch(fetchUrl2);
        const courses = await response.json();
        console.log("Create-class matched courses:", courses);
        const normalizedInput = courseName.toLowerCase();
        const course = courses.find((c) => {
            const code = c.course_code ? String(c.course_code).toLowerCase() : "";
            const name = c.name ? String(c.name).toLowerCase() : "";
            return (code && code === normalizedInput) || name === normalizedInput;
        });

        if (!course) {
            alert("Course not found.");
            return;
        }

        const courseId = course.id;

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
        dropdown.classList.toggle('hidden');
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
        console.log("editClass called with classId:", classId);
        const classItem = button.closest('.class-item');
        const classNameSpan = classItem.querySelector('.class-name');
        const currentClassName = classNameSpan.textContent;
        const newClassName = prompt("Edit class name:", currentClassName);

        if (newClassName && newClassName.trim() !== currentClassName.trim()) {
            fetch('edit_class.php', {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `classId=${encodeURIComponent(classId)}&newClassName=${encodeURIComponent(newClassName.trim())}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.message);
                        classNameSpan.textContent = newClassName.trim();
                    } else {
                        alert(data.message || "Failed to update class name.");
                    }
                })
                .catch((error) => {
                    console.error("Error editing class:", error);
                    alert("An error occurred while updating the class name.");
                });
        } else if (newClassName.trim() === currentClassName.trim()) {
            alert("The class name is unchanged.");
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
                        alert(data.message);
                        const classItem = button.closest('.class-item');
                        classItem.remove();
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
});
