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

function buildFacultyPath(fileName) {
    const prefix = getFacultyRelativePrefix();
    return `${prefix}faculty_side/${fileName}`;
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

    const settingsPath = buildFacultyPath('faculty_settings.html');
    document.querySelectorAll('.header-profile-menu .menu-item').forEach((linkEl) => {
        if (linkEl.textContent.trim().toLowerCase() === 'settings') {
            linkEl.setAttribute('href', settingsPath);
        }
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

function updateFacultyHeaderAvatars(profileFile) {
    if (!profileFile) return;
    const avatarButtons = document.querySelectorAll('.profile-avatar-btn');
    if (!avatarButtons.length) return;

    avatarButtons.forEach((button) => {
        let img = button.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = 'Profile';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.margin = '0';
            img.style.border = '0';
            button.appendChild(img);
        }

        img.src = buildUserInfoPath('../images/user_images/' + profileFile);
        img.classList.remove('hidden');

        const icon = button.querySelector('i');
        if (icon) icon.classList.add('hidden');
    });
}

window.switchNotifTab = function(tabName, containerId) {
    const container = document.getElementById(containerId) || document.getElementById('notificationDropdown');
    if (!container) return;
    
    container.querySelectorAll('.notif-tab-btn').forEach(btn => btn.classList.remove('active'));
    container.querySelectorAll('.notif-tab-content').forEach(content => content.classList.remove('active'));
    
    const activeBtn = container.querySelector(`.notif-tab-btn[data-target="${tabName}"]`);
    const activeContent = container.querySelector(`.notif-tab-content[data-tab="${tabName}"]`);
    
    if (activeBtn) activeBtn.classList.add('active');
    if (activeContent) activeContent.classList.add('active');
};

function renderNotificationList(containerId, notifications) {
    const listContainer = document.getElementById(containerId) || document.getElementById('notificationDropdown');
    if (!listContainer) return;

    const newItems = Array.isArray(notifications) ? notifications.filter((item) => !item.isRead) : [];
    const reviewedItems = Array.isArray(notifications) ? notifications.filter((item) => item.isRead) : [];
    const actualContainerId = containerId || 'notificationDropdown';

    const buildItems = (items, emptyMsg) => {
        if (!items.length) return `<p class="notification-empty text-center" style="padding: 1rem 0;">${emptyMsg}</p>`;
        return items.map((item) => `
            <div class="notification-item">
                <p class="notification-message">${escapeHtml(item.message || '')}</p>
                <p class="notification-time">${escapeHtml(item.createdAt || '')}</p>
            </div>
        `).join('');
    };

    listContainer.innerHTML = `
        <div class="notif-tabs-header">
            <button class="notif-tab-btn active" data-target="new" onclick="switchNotifTab('new', '${actualContainerId}')">NEW</button>
            <button class="notif-tab-btn" data-target="reviewed" onclick="switchNotifTab('reviewed', '${actualContainerId}')">REVIEWED</button>
        </div>
        <div class="notif-tab-content active" data-tab="new">
            ${buildItems(newItems, 'No new notifications.')}
        </div>
        <div class="notif-tab-content" data-tab="reviewed">
            ${buildItems(reviewedItems, 'No reviewed notifications yet.')}
        </div>
    `;
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

        if (payload.user?.profile_picture) {
            updateFacultyHeaderAvatars(payload.user.profile_picture);
        }
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
        // Do nothing yet, let the user read them
    } else if (!willOpen && menuId === 'notificationDropdown') {
        // Mark as read ONLY when closing the dropdown
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
        || document.getElementById('homepage')
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
                const wasOpen = !menu.classList.contains('hidden');
                menu.classList.add('hidden');
                menu.setAttribute('aria-hidden', 'true');
                // Mark read if we are closing an open notification dropdown
                if (wasOpen && menu.id === 'notificationDropdown') {
                    loadFacultyNotifications({ markRead: true });
                }
            });
        }
    });

    loadFacultySessionUser().then((isAuthenticated) => {
        if (!isAuthenticated) return;

        loadFacultyNotifications();
        initializeRoleBasedSidebar();
        setInterval(() => {
            loadFacultySessionUser();
            loadFacultyNotifications();
        }, 15000);
    });

    // Ensure settings page still initializes even if the settings script fails to load.
    if (typeof initFacultySettingsPage === 'function') {
        initFacultySettingsPage();
    } else {
        bootstrapFacultySettingsFallback();
    }
});

function bootstrapFacultySettingsFallback() {
    if (window.__facultySettingsInitialized) return;
    const emailInput = document.getElementById('email');
    const tabs = document.querySelectorAll('.settings-tab-btn[data-tab]');
    if (!emailInput || !tabs.length) return;
    window.__facultySettingsInitialized = true;

    const endpoint = buildFacultyPath('get_faculty_settings.php');
    fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' })
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) return;
            const userInfo = data.data || {};
            const idInput = document.getElementById('idNumber');
            const programInput = document.getElementById('program');
            const passwordInput = document.getElementById('password');

            emailInput.value = userInfo.email || '';
            if (idInput) idInput.value = userInfo.id_number || '';
            if (programInput) programInput.value = userInfo.program_name || '';
            if (passwordInput) passwordInput.value = '********';

            if (userInfo.profile_picture) {
                const preview = document.getElementById('profilePicturePreview');
                if (preview) {
                    preview.innerHTML = '<img src="../images/user_images/' + userInfo.profile_picture + '" alt="Profile Picture">';
                }
            }
        })
        .catch((error) => {
            console.warn('Faculty settings fallback failed:', error);
        });

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            if (!tabName) return;

            document.querySelectorAll('.settings-content').forEach((content) => {
                content.classList.remove('active');
            });
            tabs.forEach((btn) => btn.classList.remove('active'));

            const content = document.getElementById(tabName + '-content');
            if (content) content.classList.add('active');
            tab.classList.add('active');
        });
    });
}

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

// Hide sidebar links based on faculty role
function hideSidebarLinksForRole(facultyRole) {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Find the "Lists of Students" link
    const listsLink = Array.from(sidebar.querySelectorAll('a')).find((link) => {
        const label = link.textContent.trim().toLowerCase();
        const href = (link.getAttribute('href') || '').toLowerCase();
        return label.includes('lists of students')
            || label.includes('student lists')
            || href.includes('list_students');
    });
    
    if (listsLink && facultyRole === 'professor') {
        listsLink.style.display = 'none';
    }
}

// Fetch faculty role and hide appropriate sidebar links
async function initializeRoleBasedSidebar() {
    try {
        const response = await fetch(buildFacultyPath('fetch_dashboard_stats.php'));
        const data = await response.json();
        
        if (data && data.faculty_role) {
            hideSidebarLinksForRole(data.faculty_role);
        }
    } catch (error) {
        console.warn('Could not initialize role-based sidebar:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInputs = document.querySelectorAll('.header-search .search-input');
    
    searchInputs.forEach(input => {
        // Create the dropdown container just once
        const dropdown = document.createElement('div');
        dropdown.className = 'search-results-dropdown hidden';
        input.parentElement.style.position = 'relative'; // Ensure dropdown anchors here
        input.parentElement.appendChild(dropdown);

        let debounceTimer;

        input.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            dropdown.innerHTML = ''; // Clear old results

            if (query.length < 2) {
                dropdown.classList.add('hidden');
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const rootPrefix = getFacultyRelativePrefix(); // Use your existing helper!
                
                fetch(`${rootPrefix}student_side/api/global_search.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.ok && data.results.length > 0) {
                            data.results.forEach(student => {
                                const item = document.createElement('div');
                                item.className = 'search-result-item';
                                item.innerHTML = `<strong>${student.name}</strong><br><small>${student.email}</small>`;
                                
                                // Redirect to view mode on click
                                item.addEventListener('click', () => {
                                    window.location.href = `${rootPrefix}faculty_side/faculty_homepage.html?view_student=${student.student_id}`;
                                });
                                
                                dropdown.appendChild(item);
                            });
                            dropdown.classList.remove('hidden');
                        } else {
                            dropdown.innerHTML = '<div class="search-result-item text-gray-500">No students found.</div>';
                            dropdown.classList.remove('hidden');
                        }
                    })
                    .catch(err => console.error("Search failed:", err));
            }, 300); // 300ms debounce
        });

        // Hide dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!input.parentElement.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const viewStudentId = urlParams.get('view_student');
    if (viewStudentId) {
        const mainSection = document.querySelector('.main-content');
        if (mainSection) {
            const sidebar = document.getElementById('sidebar');
            mainSection.innerHTML = '';
            if (sidebar) mainSection.appendChild(sidebar);

            const iframe = document.createElement('iframe');
            const rootPrefix = getFacultyRelativePrefix();
            iframe.src = `${rootPrefix}student_side/student_homepage/student_homepage.php?view_student=${viewStudentId}&embed=1`;
            iframe.style.width = '100%';
            iframe.style.height = 'calc(100vh - 80px)';
            iframe.style.border = 'none';
            iframe.style.flexGrow = '1';
            
            mainSection.appendChild(iframe);
            mainSection.style.display = 'flex';
            
            // Update breadcrumb if it exists
            const breadcrumb = document.querySelector('.breadcrumb-item.active') || document.querySelector('.breadcrumb-item');
            if (breadcrumb) {
                breadcrumb.innerHTML = 'View Student Profile';
            }
        }
    }
});


