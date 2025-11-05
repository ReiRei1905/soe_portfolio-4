console.log("faculty_scripts.js loaded");

function handleNotificationClick() {
    const notificationDropdown = document.getElementById("notificationDropdown");
    const notificationBadge = document.getElementById("notificationBadge");

    if (notificationDropdown) {
        notificationDropdown.classList.toggle("hidden");

        if (!notificationDropdown.classList.contains("hidden") && notificationBadge) {
            notificationBadge.classList.add("hidden");
        }
    } else {
        console.warn("Notification dropdown element not found");
    }
}

function handleProfileClick() {
    // Lightweight placeholder used by header icon across pages
    // You can extend this to open a profile menu/modal later
    alert('Profile button clicked!');
}

function toggleChart() {
    const pieChart = document.getElementById('pieChart');
    const mostUploads = document.getElementById('mostUploads');
    const barChart = document.getElementById('barChart');
    const arrowRight = document.getElementById('arrowRight');
    const arrowLeft = document.getElementById('arrowLeft');

    if (!pieChart || !barChart) return;

    if (pieChart.style.display === 'block') {
        pieChart.style.display = 'none';
        if (mostUploads) mostUploads.style.display = 'none';
        barChart.style.display = 'block';
        if (arrowRight) arrowRight.style.display = 'none';
        if (arrowLeft) arrowLeft.style.display = 'block';
    } else {
        pieChart.style.display = 'block';
        if (mostUploads) mostUploads.style.display = 'block';
        barChart.style.display = 'none';
        if (arrowRight) arrowRight.style.display = 'block';
        if (arrowLeft) arrowLeft.style.display = 'none';
    }
}

/* Toggle sidebar in overview section (shared) */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('overview')
        || document.getElementById('aboutUs')
        || document.getElementById('programs')
        || document.getElementById('courses')
        || document.getElementById('classes')
        || document.getElementById('listsStudents');

    if (!sidebar || !mainContent) return;
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
}

document.addEventListener("DOMContentLoaded", () => {
    // Position sidebar below header when present
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    if (header && sidebar) sidebar.style.top = `${header.offsetHeight}px`;

    // Close notification dropdown when clicking outside
    document.addEventListener("click", (event) => {
        const notificationDropdown = document.getElementById("notificationDropdown");
        const notificationIcon = document.querySelector(".header-notifications i");

        if (notificationDropdown && notificationIcon && !notificationDropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
            notificationDropdown.classList.add("hidden");
        }
    });

    // Close any dropdowns when clicking outside
    document.addEventListener("click", (event) => {
        const dropdowns = document.querySelectorAll(".dropdown");
        dropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target) && dropdown.previousElementSibling && !dropdown.previousElementSibling.contains(event.target)) {
                dropdown.classList.add("hidden");
            }
        });
    });
});

// Canonical dropdown toggle (shared)
function toggleDropdown(icon) {
    if (!icon) return;
    const dropdown = icon.nextElementSibling;
    if (!dropdown) return;

    // Close other open dropdowns
    document.querySelectorAll('.dropdown').forEach((menu) => {
        if (menu !== dropdown) menu.classList.add('hidden');
    });

    dropdown.classList.toggle('hidden');
}

