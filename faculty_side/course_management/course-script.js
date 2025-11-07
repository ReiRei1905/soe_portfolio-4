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
            const program = programs.find((p) => p.name === decodeURIComponent(programName));
            if (program) {
                programId = program.id;
                fetchCourses();
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
                courseList.innerHTML = '';
                if (Array.isArray(courses) && courses.length > 0) {
                    courses.forEach((course) => {
                        const courseItem = courseItemTemplate.content.cloneNode(true);
                        const displayName = course.course_code && course.course_code.trim() !== "" ? `${course.name} [${course.course_code}]` : course.name;
                        courseItem.querySelector(".course-name").textContent = displayName;
                        const editButton = courseItem.querySelector('.edit-btn');
                        editButton.setAttribute('onclick', `editCourse(this, ${course.id})`);
                        const removeButton = courseItem.querySelector('.remove-btn');
                        removeButton.setAttribute('onclick', `removeCourse(this, ${course.id})`);
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
    if (confirm("Are you sure you want to delete this course?")) {
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
    }
}

function editCourse(button, courseId) {
    const courseItem = button.closest('.course-item');
    const courseNameSpan = courseItem.querySelector('.course-name');
    const newCourseName = prompt("Edit course name:", courseNameSpan.textContent);
    if (!newCourseName) return;
    fetch("edit_course.php", { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `courseId=${encodeURIComponent(courseId)}&newCourseName=${encodeURIComponent(newCourseName.trim())}` })
        .then((r) => r.json())
        .then((data) => {
            if (data.success) {
                alert(data.message);
                courseNameSpan.textContent = newCourseName.trim();
            } else {
                alert(data.message);
            }
        })
        .catch((err) => console.error('Error editing course:', err));
}
