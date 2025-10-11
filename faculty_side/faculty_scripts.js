console.log("faculty_scripts.js loaded");

function handleNotificationClick() {
    const notificationDropdown = document.getElementById("notificationDropdown");
    const notificationBadge = document.getElementById("notificationBadge");

    if (notificationDropdown) {
        notificationDropdown.classList.toggle("hidden"); // Toggle the 'hidden' class
        console.log("Notification dropdown visibility:", !notificationDropdown.classList.contains("hidden"));

        if (!notificationDropdown.classList.contains("hidden")) {
            notificationBadge.classList.add("hidden");
        }

        console.log("Notification dropdown content:", notificationDropdown.innerHTML); // Debugging
    } else {
        console.error("Notification dropdown element not found!");
    }
}

function toggleChart() {
    const pieChart = document.getElementById('pieChart');
    const mostUploads = document.getElementById('mostUploads');
    const barChart = document.getElementById('barChart');
    const arrowRight = document.getElementById('arrowRight');
    const arrowLeft = document.getElementById('arrowLeft');

    if (pieChart.style.display === 'block') {
        pieChart.style.display = 'none';
        mostUploads.style.display = 'none';
        barChart.style.display = 'block';
        arrowRight.style.display = 'none';
        arrowLeft.style.display = 'block';
    } else {
        pieChart.style.display = 'block';
        mostUploads.style.display = 'block';
        barChart.style.display = 'none';
        arrowRight.style.display = 'block';
        arrowLeft.style.display = 'none';
    }
}

function goToOverviewPage() {
    /*// Scroll to the top of the page
    window.scrollTo({ top: 0, behavior: 'smooth' });*/
    // Redirect to the overview page
    window.location.href = "faculty_homepage.html";
}

/*Toggle sidebar in overview section */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('overview') 
    || document.getElementById('aboutUs') 
    || document.getElementById('programs')
    || document.getElementById('courses')
    || document.getElementById('classes')
    || document.getElementById('listsStudents');
    // Toggle the sidebar visibility
    sidebar.classList.toggle('active');
    // Adjust the main content position
    mainContent.classList.toggle('shifted');

}

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOM fully loaded and parsed");
    // Sidebar adjustment
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    sidebar.style.top = `${header.offsetHeight}px`;

    document.addEventListener("click", (event) => {
        const notificationDropdown = document.getElementById("notificationDropdown");
        const notificationIcon = document.querySelector(".header-notifications i");
    
        if (
            notificationDropdown &&
            !notificationDropdown.contains(event.target) &&
            !notificationIcon.contains(event.target)
        ) {
            notificationDropdown.classList.add("hidden"); // Hide the dropdown
        }
    });

    // Add this code here to handle clicks outside the dropdown
    document.addEventListener("click", (event) => {
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target) && !dropdown.previousElementSibling.contains(event.target)) {
                dropdown.classList.add("hidden"); // Hide dropdown if clicked outside
            }
        });
    });

    let programId = null; // Initialize programId variable
    // Get the program name from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const programName = urlParams.get("program");

    const programList = document.getElementById("programList");
    if (!programList) {
        console.warn("programList not found in the DOM. Skipping related logic.");
    }
    if (programName) {
        fetch("fetch_programs.php")
            .then((response) => response.json())
            .then((programs) => {
                const program = programs.find((p) => p.name === decodeURIComponent(programName));
                if (program) {
                    programId = program.id; // Set programId after fetching
                    console.log(`Program ID set to: ${programId}`);
                    console.log(`test`);
                } else {
                    console.error("Program not found");
                }
            })
            .catch((error) => console.error("Error fetching programs:", error));
    } else {
        console.warn("Program name not found in the URL.");
    }

    // Program creation logic with backend
    fetchPrograms();
    const createProgramBtn = document.getElementById("createProgramBtn");
    const programInputContainer = document.getElementById("programInputContainer");
    const programInput = document.getElementById("programInput");
    const confirmProgramBtn = document.getElementById("confirmProgramBtn");
    const programItemTemplate = document.getElementById("programItemTemplate");
    
    if (!programList) {
        console.warn("programList not found in the DOM. Skipping related logic.");
    }

    createProgramBtn.addEventListener("click", () => {
        programInputContainer.classList.remove("hidden");
        programInput.focus();
    });

    confirmProgramBtn.addEventListener("click", () => {
        const programName = programInput.value.trim();
        if (programName) {
            // Send program name to the server
            fetch("create_program.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `programName=${encodeURIComponent(programName)}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Clear the input and hide the input container
                        programInput.value = "";
                        programInputContainer.classList.add("hidden");
    
                        // Fetch the updated program list dynamically
                        fetchPrograms();
                    } else {
                        alert(data.message);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                });
        } else {
            alert("Please enter a valid program name.");
        }
    });
    
    console.log("Program list:", programList);
    
    function editProgram(button) {
        const programItem = button.closest('.program-item');
        const programNameSpan = programItem.querySelector('.program_name');
        const programId = programItem.dataset.programId; // Ensure program ID is stored in the DOM
        const newProgramName = prompt("Edit program name:", programNameSpan.textContent);
    
        if (newProgramName) {
            fetch("edit_program.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `programId=${encodeURIComponent(programId)}&newProgramName=${encodeURIComponent(newProgramName.trim())}`,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        alert(data.message);
                        programNameSpan.textContent = newProgramName.trim(); // Update the program name in the DOM
                    } else {
                        alert(data.message);
                    }
                })
                .catch((error) => console.error("Error editing program:", error));
        }
    }

    function deleteProgram(programId, programItem) {
        fetch("delete_program.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `programId=${encodeURIComponent(programId)}`,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Backend response:", data); // Log the backend response for debugging
    
                if (data.success) {
                    // Only remove the program from the DOM if deletion was successful
                    console.log("Deletion successful. Removing program from UI.");
                    alert(data.message);
                } else {
                    // Show error message if deletion failed
                    console.warn("Deletion failed. Program will remain in the UI.");
                    alert(data.message);
    
                    // Re-fetch the program list to ensure the UI reflects the backend state
                    fetchPrograms();
                }
            })
            .catch((error) => {
                console.error("Error deleting program:", error);
                alert("An error occurred while trying to delete the program. Please try again.");
    
                // Re-fetch the program list even if there's an error
                fetchPrograms();
            });
    }

    // Function to fetch and display programs
    function fetchPrograms() {
        fetch("fetch_programs.php")
            .then((response) => response.json())
            .then((programs) => {
                console.log("Fetched programs:", programs);
    
                if (programList) {
                    programList.innerHTML = ""; // Clear the current program list
                    programs.forEach((program) => {
                        const programItem = programItemTemplate.content.cloneNode(true);
                        programItem.querySelector(".program_name").textContent = program.name;
                        programItem.querySelector(".program-item").dataset.programId = program.id; // Add program ID
    
                        // Attach the editProgram function to the "Edit Program" button
                        const editButton = programItem.querySelector(".edit-btn");
                        editButton.addEventListener("click", function () {
                            editProgram(this);
                        });
    
                        const removeButton = programItem.querySelector(".remove-btn");
                        removeButton.addEventListener("click", () => {
                            if (confirm(`Are you sure you want to delete "${program.name}"?`)) {
                                deleteProgram(program.id, programItem);
                            }
                        });

                        // Attach dropdown toggle and stop propagation
                        const optionsIcon = programItem.querySelector('.program-options');
                        optionsIcon.addEventListener('click', function(event) {
                            event.stopPropagation(); // Prevent parent click
                            toggleDropdown(this);
                        });
    
                        programList.appendChild(programItem);
                    });
                } else {
                    console.error("programList not found in the DOM");
                }
            })
            .catch((error) => {
                console.error("Error fetching programs:", error);
            });

    }

    /*Start of Course Logic */
    updateBreadcrumb(programName);
    // Check if the URL contains a program name parameter
    function updateBreadcrumb(programName) {
        const breadcrumbProgram = document.getElementById("breadcrumbProgram");
        breadcrumbProgram.textContent = `Courses (${programName})`;
    }
    
    // Update the breadcrumb with the program name
    const breadcrumbProgram = document.getElementById("breadcrumbProgram");
    if (programName && breadcrumbProgram) {
        breadcrumbProgram.textContent = `[${decodeURIComponent(programName)}] Courses`;
    }

    if (programName && breadcrumbProgram) {
        breadcrumbProgram.textContent = `[${decodeURIComponent(programName)}] Courses`;
    } else {
        console.warn("Program name not found in the URL.");
    }

    console.log("Program name:", programName);
    // Course creation logic :
    const courseInput = document.getElementById("courseInput");
    const confirmCourseBtn = document.getElementById("confirmCourseBtn");
    const courseList = document.getElementById("courseList");
    const courseItemTemplate = document.getElementById("courseItemTemplate");
    
    if (!confirmCourseBtn) {
        console.error("Confirm button not found in the DOM.");
    } else {
        console.log("Confirm button found.");
    }

    if (!courseInput) {
        console.error("Course input field not found in the DOM.");
    } else {
        console.log("Course input field found.");
    }

    // Attach the click event listener directly to the button
    
    confirmCourseBtn.addEventListener("click", () => {
        console.log("Confirm button clicked");
        console.log(`Program ID inside click event: ${programId}`);

        const courseName = courseInput.value.trim();

        if (!courseName) {
            alert("Please enter a valid course name.");
            return;
        }

        if (!programId) {
            alert("Program ID is not set. Please try again.");
            return;
        }

        console.log(`Adding course: ${courseName} to program ID: ${programId}`);

        fetch("create_course.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `programId=${encodeURIComponent(programId)}&courseName=${encodeURIComponent(courseName)}`,
        })
            .then((response) => response.json())
            .then((data) => {
                console.log("Response from create_course.php:", data);
                if (data.success) {
                    courseInput.value = ""; // Clear the input field
                    fetchCourses(); // Refresh the course list
                } else {
                    alert(data.message);
                }
            })
            .catch((error) => console.error("Error creating course:", error));
    });
        

    // Fetch and display courses
    function fetchCourses() {
        if (!programId) {
            console.warn("Program ID is not set. Cannot fetch courses.");
            return;
        }
    
        fetch(`fetch_courses.php?programId=${encodeURIComponent(programId)}`)
            .then((response) => response.json())
            .then((courses) => {
                console.log("Fetched courses:", courses);
                courseList.innerHTML = ""; // Clear the current course list
    
                if (Array.isArray(courses) && courses.length > 0) {
                    courses.forEach((course) => {
                        const courseItem = courseItemTemplate.content.cloneNode(true);
                        courseItem.querySelector(".course-name").textContent = course.name;
    
                        const removeButton = courseItem.querySelector(".remove-btn");
                        removeButton.addEventListener("click", () => {
                            if (confirm(`Are you sure you want to delete "${course.name}"?`)) {
                                deleteCourse(course.id, courseItem);
                            }
                        });
    
                        courseList.appendChild(courseItem);
                    });
                } else {
                    courseList.innerHTML = "<p>No courses available.</p>";
                }
            })
            .catch((error) => console.error("Error fetching courses:", error));
    }

    function deleteCourse(courseId, courseItem) {
        fetch("delete_course.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `courseId=${encodeURIComponent(courseId)}`,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    courseList.removeChild(courseItem);
                    alert("Course deleted successfully.");
                } else {
                    alert(data.message);
                }
            })
            .catch((error) => console.error("Error deleting course:", error));
    }
    
});


    // Add click functionality to each program item
    programList.addEventListener("click", (event) => {
        // Check if the click is on the three-dot icon or dropdown menu
        if (event.target.closest(".program-options") || event.target.closest(".dropdown")) {
            // Stop propagation to prevent redirection
            event.stopPropagation();
            return;
        }
        
       // Add click functionality to each program item
        if (programList) {
            programList.addEventListener("click", (event) => {
                const programItem = event.target.closest(".program-item");
                if (programItem) {
                    const programName = programItem.querySelector(".program_name").textContent.trim();
                    const programSlug = encodeURIComponent(programName);
                    window.location.href = `courses.html?program=${programSlug}`;
                }
            });
        } else {
            console.warn("programList not found in the DOM. Skipping related logic.");
        }
    
    });

const programNameSpan = document.querySelector(".program-name-span"); // Update the selector as needed

programNameSpan.addEventListener("click", () => {
    const programSlug = encodeURIComponent(programNameSpan.textContent.trim());
    window.location.href = `courses.html?program=${programSlug}`;
});

// Remove program from the list
function removeProgram(button) {
    const programItem = button.closest('.program-item');
    programList.removeChild(programItem);
}

// Toggle dropdown menu
function toggleDropdown(icon) {
    
    const dropdown = icon.nextElementSibling;
    dropdown.classList.toggle('hidden');
}

// Toggle dropdown menu
function toggleDropdown(icon) {
     console.log("Toggling dropdown for:", icon);
    const dropdown = icon.nextElementSibling;

    // Close any other open dropdowns
    document.querySelectorAll('.dropdown').forEach((menu) => {
        if (menu !== dropdown) {
            menu.classList.add('hidden');
        }
    });

    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', (event) => {
    const isDropdown = event.target.closest('.dropdown');
    const isProgramsOptionsIcon = event.target.closest('.program-options');
    const isCoursesOptionsIcon = event.target.closest('.course-options');

    if (!isDropdown && !isProgramsOptionsIcon && !isCoursesOptionsIcon) {
        document.querySelectorAll('.dropdown').forEach((menu) => {
            menu.classList.add('hidden');
        });
    }
});

