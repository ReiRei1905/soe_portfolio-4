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

    // Show input container
    createClassBtn.addEventListener("click", () => {
        classInputContainer.classList.remove("hidden");
        classInput.focus();
    });


    const fetchCourses = async (searchTerm = "") => {
        try {
            // Remove the hardcoded programId
            const response = await fetch(`fetch_courses.php?searchTerm=${encodeURIComponent(searchTerm)}`);
            if (!response.ok) throw new Error("Failed to fetch courses");
            const courses = await response.json();
            console.log("Fetched courses:", courses); // Debugging log
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
                console.log("Fetched classes:", data.classes); // Debugging log
                renderClasses(data.classes);
            } else {
                console.error("Error fetching classes:", data.message);
            }
        } catch (error) {
            console.error("Error:", error);
        }
    };
    
    // ...existing code...
    const renderClasses = (classes) => {
        classList.innerHTML = ""; // Clear the existing list
    
        classes.forEach((classItem) => {
            const template = document.getElementById("classItemTemplate");
            const item = template.content.cloneNode(true);
        
            item.querySelector(".class-name").textContent = `${classItem.course_name} ${classItem.class_name} Term ${classItem.term_number} ${classItem.start_year}-${classItem.end_year}`;
        
            // Redirect on class-item click
            item.querySelector(".class-item").addEventListener("click", () => {
                window.location.href = `class-handling.html?class_id=${classItem.class_id}`;
            });
        
            // Prevent redirect when clicking the ellipsis (class-options)
            item.querySelector(".class-options").addEventListener("click", (e) => {
                e.stopPropagation();
                toggleDropdown(e.target);
            });
        
            // Prevent redirect when clicking inside the dropdown
            item.querySelector(".dropdown").addEventListener("click", (e) => {
                e.stopPropagation();
            });
        
            // Set up edit/remove
            item.querySelector(".edit-btn").setAttribute("onclick", `editClass(this, ${classItem.class_id})`);
            item.querySelector(".remove-btn").setAttribute("onclick", `removeClass(this, ${classItem.class_id})`);
        
            classList.appendChild(item);
        });
    };
    // ...existing code...
    fetchClasses(); // Initial fetch to populate the class list

    const renderDropdown = (courses) => {
        courseDropdown.innerHTML = ""; // Clear previous results
        if (courses.length === 0) {
            courseDropdown.classList.add("hidden");
            return;
        }
    
        courses.forEach((course) => {
            const item = document.createElement("div");
            item.className = "course-item";
            item.textContent = course.name;
            item.addEventListener("click", () => {
                courseSearchInput.value = course.name; // Set the input value
                courseDropdown.classList.add("hidden"); // Hide the dropdown
            });
            courseDropdown.appendChild(item);
        });
    
        courseDropdown.classList.remove("hidden"); // Ensure the dropdown is visible
    };

    // Event listener for input changes
    courseSearchInput.addEventListener("input", async () => {
        const searchTerm = courseSearchInput.value.trim();
        console.log("Search term:", searchTerm); // Debugging log
        const courses = await fetchCourses(searchTerm);
        renderDropdown(courses);
    });

    // Fetch and render courses in dropdown
    courseSearchInput.addEventListener("input", async () => {
        const searchTerm = courseSearchInput.value.trim();
        const response = await fetch(`fetch_courses.php?searchTerm=${encodeURIComponent(searchTerm)}`);
        const courses = await response.json();
        courseDropdown.innerHTML = "";
        if (courses.length > 0) {
            courses.forEach((course) => {
                const item = document.createElement("div");
                item.className = "course-item";
                item.textContent = course.name;
                item.addEventListener("click", () => {
                    courseSearchInput.value = course.name;
                    courseDropdown.classList.add("hidden");
                });
                courseDropdown.appendChild(item);
            });
            courseDropdown.classList.remove("hidden");
        } else {
            courseDropdown.classList.add("hidden");
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener("click", (event) => {
        if (!courseSearchInput.contains(event.target) && !courseDropdown.contains(event.target)) {
            courseDropdown.classList.add("hidden");
        }
    });

    // Populate the start year dropdown
    const currentYear = new Date().getFullYear(); // Get the current year
    const maxStartYear = Math.min(currentYear, 2025); // Limit start year to 2025
    const minStartYear = 2010; // Minimum start year

    for (let year = minStartYear; year <= maxStartYear; year++) {
        const option = document.createElement("option");
        option.value = year;
        option.textContent = year;
        startYearSelect.appendChild(option);
    }

    // Populate the end year dropdown dynamically based on the selected start year
    startYearSelect.addEventListener("change", () => {
        // Clear previous end year options
        endYearSelect.innerHTML = '<option value="" disabled selected>Select End Year</option>';

        const selectedStartYear = parseInt(startYearSelect.value, 10);
        const maxEndYear = Math.min(selectedStartYear + 10, 2025); // End year is up to 10 years after start year, but not beyond 2025

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
        const termNumber = termNumberSelect.value.replace("term_", ""); // Convert "term_1" to "1"
        const startYear = startYearSelect.value;
        const endYear = endYearSelect.value;
    
        if (!courseName || !className || !termNumber || !startYear || !endYear) {
            alert("Please fill out all fields.");
            return;
        }
    
        // Fetch the course ID based on the course name
        const response = await fetch(`fetch_courses.php?searchTerm=${encodeURIComponent(courseName)}`);
        const courses = await response.json();
        const course = courses.find((c) => c.name === courseName);
    
        if (!course) {
            alert("Course not found.");
            return;
        }
    
        const courseId = course.id;
    
        try {
            console.log("Sending data:", { courseId, className, termNumber, startYear, endYear });
    
            const result = await fetch("create_class.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `courseId=${courseId}&className=${className}&termNumber=${termNumber}&startYear=${startYear}&endYear=${endYear}`,
            });
    
            const data = await result.json();
            console.log("Response from create_class.php:", data);
    
            if (data.success) {
                // Clear the input fields and hide the input container
                classInput.value = "";
                courseSearchInput.value = "";
                termNumberSelect.value = "";
                startYearSelect.value = "";
                endYearSelect.value = "";
                classInputContainer.classList.add("hidden");
    
                // Fetch the updated class list dynamically
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


    const renderClassItem = (courseName, className, termNumber, startYear, endYear) => {
        const classItem = classItemTemplate.content.cloneNode(true);
        classItem.querySelector(".class-name").textContent = `${courseName} ${className} Term ${termNumber} ${startYear}-${endYear}`;
        classList.appendChild(classItem);
    };

    
        function toggleDropdown(icon) {
            const dropdown = icon.nextElementSibling;
            document.querySelectorAll('.dropdown').forEach((menu) => {
                if (menu !== dropdown) menu.classList.add('hidden');
            });
            dropdown.classList.toggle('hidden');
        }
    
    // Attach to the global scope
    window.toggleDropdown = toggleDropdown;

    // Close dropdown when clicking outside
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
    // Event listener for class search input
    courseClassInput.addEventListener("input", () => {
        const searchTerm = courseClassInput.value.toLowerCase().trim();
        const classItems = classList.querySelectorAll(".class-item");
        console.log("Search term:", searchTerm); // Debugging log
        classItems.forEach((item) => {
            const className = item.querySelector(".class-name").textContent.toLowerCase();

            // Show or hide the class item based on the search term
            if (className.includes(searchTerm)) {
                item.style.display = ""; // Show the item
            } else {
                item.style.display = "none"; // Hide the item
            }
        });
    });

    function editClass(button, classId) {
        console.log("editClass called with classId:", classId); // Debugging log
        const classItem = button.closest('.class-item');
        const classNameSpan = classItem.querySelector('.class-name');
        const currentClassName = classNameSpan.textContent;
        const newClassName = prompt("Edit class name:", currentClassName);
    
        if (newClassName && newClassName.trim() !== currentClassName.trim()) {
            fetch("edit_class.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `classId=${encodeURIComponent(classId)}&newClassName=${encodeURIComponent(newClassName.trim())}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.message);
                        classNameSpan.textContent = newClassName.trim(); // Update the class name in the DOM
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

    // Attach to the global scope
    window.editClass = editClass;

    function removeClass(button, classId) {
        if (confirm("Are you sure you want to delete this class?")) {
            fetch("delete_class.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `classId=${encodeURIComponent(classId)}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.message);
                        const classItem = button.closest('.class-item');
                        classItem.remove(); // Remove the class from the DOM
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
    
    // Attach to the global scope
    window.removeClass = removeClass;
});