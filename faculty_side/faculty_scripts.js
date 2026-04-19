console.log("faculty_scripts.js loaded");

function getFacultyRelativePrefix() {
    const pathname = window.location.pathname || '';
    const marker = '/faculty_side/';
    const markerIndex = pathname.indexOf(marker);

    if (markerIndex < 0) {
        return '../';
    }

    const tail = pathname.slice(markerIndex + marker.length);
    const slashIndex = tail.lastIndexOf('/');
    if (slashIndex < 0) {
        return '../';
    }

    const dirPart = tail.slice(0, slashIndex).trim();
    const depth = dirPart === '' ? 0 : dirPart.split('/').filter(Boolean).length;
    return '../'.repeat(depth + 1);
}

function buildUserInfoPath(fileName) {
    const prefix = getFacultyRelativePrefix();
    return `${prefix}user_info_V3/${fileName}`;
}

function redirectToFacultyLogin() {
    window.location.href = buildUserInfoPath('index.php');
}

function goToOverviewPage() {
    const pathname = window.location.pathname || '';
    const marker = '/faculty_side/';
    const markerIndex = pathname.indexOf(marker);

    if (markerIndex < 0) {
        window.location.href = 'faculty_homepage.html';
        return;
    }

    const facultyRoot = pathname.slice(0, markerIndex + marker.length);
    window.location.href = `${window.location.origin}${facultyRoot}faculty_homepage.html`;
}

function ensureFacultyProfileSummaryPlaceholders() {
    const profileDropdown = document.getElementById('profileDropdown');
    if (!profileDropdown) return;

    let nameEl = document.getElementById('facultyProfileFullName');
    let emailEl = document.getElementById('facultyProfileEmail');

    if (nameEl && emailEl) return;

    const summary = document.createElement('div');
    summary.className = 'profile-summary';

    nameEl = document.createElement('p');
    nameEl.id = 'facultyProfileFullName';
    nameEl.className = 'profile-summary-name';
    nameEl.textContent = 'Full Name';

    emailEl = document.createElement('p');
    emailEl.id = 'facultyProfileEmail';
    emailEl.className = 'profile-summary-email';
    emailEl.textContent = 'fullname@email.com';

    summary.appendChild(nameEl);
    summary.appendChild(emailEl);
    profileDropdown.insertBefore(summary, profileDropdown.firstChild);
}

function ensureFacultyNotificationPlaceholders() {
    const notificationsWrap = document.querySelector('.header-notifications');
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (!notificationsWrap || !notificationDropdown) return;

    let badge = document.getElementById('facultyNotificationBadge');
    if (!badge) {
        const bellIcon = notificationsWrap.querySelector('i');
        badge = document.createElement('span');
        badge.id = 'facultyNotificationBadge';
        badge.className = 'notification-count-badge hidden';
        badge.textContent = '0';

        if (bellIcon && bellIcon.nextSibling) {
            notificationsWrap.insertBefore(badge, bellIcon.nextSibling);
        } else {
            notificationsWrap.appendChild(badge);
        }
    }

    let list = document.getElementById('facultyNotificationList');
    if (!list) {
        const existingPlaceholder = notificationDropdown.querySelector('p.text-gray-500');
        list = document.createElement('div');
        list.id = 'facultyNotificationList';
        list.className = 'notification-list';

        if (existingPlaceholder) {
            list.appendChild(existingPlaceholder.cloneNode(true));
        } else {
            list.innerHTML = '<p class="text-gray-500 text-center">No new notifications at the moment.</p>';
        }

        notificationDropdown.innerHTML = '';
        notificationDropdown.appendChild(list);
    }
}

function normalizeFacultyHeaderLinks() {
    const logoutPath = buildUserInfoPath('logout.php');
    document.querySelectorAll('.logout-link').forEach((linkEl) => {
        linkEl.setAttribute('href', logoutPath);
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function updateNotificationBadge(badgeId, unreadCount) {
    const badge = document.getElementById(badgeId);
    if (!badge) return;

    if (!unreadCount || unreadCount <= 0) {
        badge.textContent = '0';
        badge.classList.add('hidden');
        return;
    }

    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.classList.remove('hidden');
}

function renderNotificationList(containerId, notifications) {
    const listContainer = document.getElementById(containerId) || document.getElementById('notificationDropdown');
    if (!listContainer) return;

    if (!Array.isArray(notifications) || notifications.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500 text-center">No new notifications at the moment.</p>';
        return;
    }

    listContainer.innerHTML = notifications.map((item) => `
        <div class="notification-item">
            <p class="notification-message">${escapeHtml(item.message || '')}</p>
            <p class="notification-time">${escapeHtml(item.createdAt || '')}</p>
        </div>
    `).join('');
}

async function loadFacultySessionUser() {
    try {
        const response = await fetch(buildUserInfoPath('get_session_user.php'), {
            credentials: 'same-origin',
            cache: 'no-store'
        });
        const payload = await response.json();
        if (response.status === 401 || response.status === 403 || !response.ok || !payload.success) {
            redirectToFacultyLogin();
            return false;
        }

        const fullName = payload.user?.fullName || 'Professor Name';
        const email = payload.user?.email || 'fullname@email.com';

        const profileName = document.getElementById('facultyProfileFullName');
        const profileEmail = document.getElementById('facultyProfileEmail');
        const greeting = document.getElementById('facultyGreetingText');

        if (profileName) profileName.textContent = fullName;
        if (profileEmail) profileEmail.textContent = email;
        if (greeting) greeting.textContent = `Hello ${fullName}`;

        updateNotificationBadge('facultyNotificationBadge', Number(payload.user?.unreadNotifications || 0));
        return true;
    } catch (error) {
        console.warn('Failed to load faculty session user:', error);
        return false;
    }
}

async function loadFacultyNotifications(options = {}) {
    const markRead = options.markRead === true;

    try {
        const requestOptions = markRead
            ? {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: 'action=mark_read'
            }
            : { method: 'GET', credentials: 'same-origin' };

        const response = await fetch(buildUserInfoPath('notifications_api.php'), requestOptions);
        const payload = await response.json();
        if (!response.ok || !payload.success) return;

        renderNotificationList('facultyNotificationList', payload.notifications || []);
        updateNotificationBadge('facultyNotificationBadge', Number(payload.unreadCount || 0));
    } catch (error) {
        console.warn('Failed to load faculty notifications:', error);
    }
}

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

function toggleHeaderMenu(menuId, trigger) {
    const targetMenu = document.getElementById(menuId);
    if (!targetMenu) return;

    const willOpen = targetMenu.classList.contains('hidden');

    document.querySelectorAll('.header-menu-dropdown').forEach((menu) => {
        menu.classList.add('hidden');
        menu.setAttribute('aria-hidden', 'true');
    });

    if (willOpen) {
        targetMenu.classList.remove('hidden');
        targetMenu.setAttribute('aria-hidden', 'false');
    }

    if (trigger) {
        trigger.setAttribute('aria-expanded', String(willOpen));
    }

    if (willOpen && menuId === 'notificationDropdown') {
        loadFacultyNotifications({ markRead: true });
    }
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
        || document.getElementById('lists')
        || document.getElementById('listsStudents');

    if (!sidebar || !mainContent) return;
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');
}

function bindFacultyHeaderActions() {
    document.querySelectorAll('[data-faculty-action="go-home"]').forEach((el) => {
        el.addEventListener('click', goToOverviewPage);
    });

    document.querySelectorAll('[data-faculty-action="toggle-sidebar"]').forEach((el) => {
        el.addEventListener('click', toggleSidebar);
    });

    document.querySelectorAll('[data-faculty-action="toggle-header-menu"]').forEach((el) => {
        el.addEventListener('click', (event) => {
            const menuTarget = el.getAttribute('data-menu-target');
            if (!menuTarget) return;
            event.stopPropagation();
            toggleHeaderMenu(menuTarget, el);
        });

        // Keep keyboard access for profile menu button wrappers.
        if (el.classList.contains('profile-avatar-btn')) {
            el.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    el.click();
                }
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    ensureFacultyNotificationPlaceholders();
    ensureFacultyProfileSummaryPlaceholders();
    normalizeFacultyHeaderLinks();
    bindFacultyHeaderActions();

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

    // Close header notification/profile menus on outside click.
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.header-notifications') && !event.target.closest('.header-profile-menu')) {
            document.querySelectorAll('.header-menu-dropdown').forEach((menu) => {
                menu.classList.add('hidden');
                menu.setAttribute('aria-hidden', 'true');
            });
        }
    });

    loadFacultySessionUser().then((isAuthenticated) => {
        if (!isAuthenticated) return;

        loadFacultyNotifications();
        setInterval(() => {
            loadFacultySessionUser();
            loadFacultyNotifications();
        }, 15000);
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

