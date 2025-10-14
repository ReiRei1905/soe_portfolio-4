function goToOverviewPage() {
    /*// Scroll to the top of the page
    window.scrollTo({ top: 0, behavior: 'smooth' });*/
    // Redirect to the overview page
    window.location.href = "faculty_homepage.html";
}

function handleProfileClick() {
    alert('Profile button clicked!');
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

document.addEventListener("DOMContentLoaded", function() {
    console.log("Course script loaded");
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    sidebar.style.top = `${header.offsetHeight}px`;

    const confirmBtn = document.getElementById("confirmCourseBtn");
    let programId = null;

    const urlParams = new URLSearchParams(window.location.search);
    const programName = urlParams.get("program");
    const createCourseBtn = document.getElementById("createCourseBtn");
    const courseInputContainer = document.getElementById("courseInputContainer");
    const courseItemTemplate = document.getElementById("courseItemTemplate");
    const courseCodeInput = document.getElementById("courseCodeInput");

    // For course search logic:
    const courseSearchInput = document.getElementById("courseSearchInput");
    const courseList = document.getElementById("courseList");

    courseSearchInput.addEventListener("input", function () {
        const searchTerm = courseSearchInput.value.toLowerCase().trim();

        // Get all course items
        const courseItems = courseList.querySelectorAll(".course-item");

        courseItems.forEach((item) => {
            const courseName = item.querySelector(".course-name").textContent.toLowerCase();

            // Show or hide the course item based on the search term
            if (courseName.includes(searchTerm)) {
                item.style.display = ""; // Show the item
            } else {
                item.style.display = "none"; // Hide the item
            }
        });
    });

    fetch("fetch_programs.php")
            .then((response) => response.json())
            .then((programs) => {
                const program = programs.find((p) => p.name === decodeURIComponent(programName));
                if (program) {
                    programId = program.id; // Set programId after fetching
                    console.log(`Program ID set to: ${programId}`);
                    console.log(`test`);
                    fetchCourses(); // Fetch courses when the page loads    

                } else {
                    console.error("Program not found");
                }
            })
            .catch((error) => console.error("Error fetching programs:", error));


            createCourseBtn.addEventListener("click", () => {
                courseInputContainer.classList.remove("hidden");
                courseInput.focus();
            });

    confirmBtn.addEventListener("click", () => {
        console.log("Confirm button clicked");
        console.log(`Program ID inside click event: ${programId}`);

            const courseName = courseInput.value.trim();
            const courseCode = (courseCodeInput.value || "").trim().toUpperCase();

        if (!courseName) {
            alert("Please enter a valid course name.");
            return;
        }

        if (!courseCode) {
            if (!confirm("No course code provided. Continue without a code?")) {
                return;
            }
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
            body: `programId=${encodeURIComponent(programId)}&courseName=${encodeURIComponent(courseName)}&courseCode=${encodeURIComponent(courseCode)}`,
        })
            .then((response) => response.json())
            .then((data) => {
                console.log("Response from create_course.php:", data);
                if (data.success) {
                    
                    courseInput.value = ""; // Clear the input field
                    fetchCourses(); // Refresh the course list
                    courseInputContainer.classList.add("hidden"); // Hide the input container
                } else {
                    alert(data.message);
                }
            })
            .catch((error) => console.error("Error creating course:", error));
    });

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
                        // If the backend returns a course_code, display as: Name [CODE]
                        const displayName = course.course_code && course.course_code.trim() !== "" ? `${course.name} [${course.course_code}]` : course.name;
                        courseItem.querySelector(".course-name").textContent = displayName;
    
                        const editButton = courseItem.querySelector(".edit-btn");
                        editButton.setAttribute("onclick", `editCourse(this, ${course.id})`);
    
                        const removeButton = courseItem.querySelector(".remove-btn");
                        removeButton.setAttribute("onclick", `removeCourse(this, ${course.id})`);
    
                        courseList.appendChild(courseItem);
                    });
                } else {
                    courseList.innerHTML = "<p>No courses available.</p>";
                }
            })
            .catch((error) => console.error("Error fetching courses:", error));
    }
    
});

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

function removeCourse(button, courseId) {
    const courseItem = button.closest('.course-item');

    if (confirm("Are you sure you want to delete this course?")) {
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
                    alert(data.message);
                    courseItem.remove(); // Remove the course from the DOM
                } else {
                    alert(data.message);
                }
            })
            .catch((error) => console.error("Error deleting course:", error));
    }
}

function editCourse(button, courseId) {
    const courseItem = button.closest('.course-item');
    const courseNameSpan = courseItem.querySelector('.course-name');
    const newCourseName = prompt("Edit course name:", courseNameSpan.textContent);

    if (newCourseName) {
        fetch("edit_course.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `courseId=${encodeURIComponent(courseId)}&newCourseName=${encodeURIComponent(newCourseName.trim())}`,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    alert(data.message);
                    courseNameSpan.textContent = newCourseName.trim(); // Update the course name in the DOM
                } else {
                    alert(data.message);
                }
            })
            .catch((error) => console.error("Error editing course:", error));
    }
}