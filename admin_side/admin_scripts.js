function goToOverviewPage() {
    window.location.href = 'admin_homepage.html';
}

function handleProfileClick() {
    alert('Profile button clicked!');
}

const roleLabels = {
    all: 'All Users',
    admin: 'Admin',
    executiveDirector: 'Executive Director',
    programDirector: 'Program Director',
    professor: 'Professor',
    student: 'Student'
};

const roleToFilterParam = {
    all: 'all',
    admin: 'admin',
    executiveDirector: 'executiveDirector',
    programDirector: 'programDirector',
    professor: 'professor',
    student: 'student'
};

let currentFilter = 'all';
let currentSearch = '';
let selectedUserId = null;
let selectedAccessRole = 'student';
let users = [];
let searchDebounceTimer = null;
let isAdminSessionContext = false;
let actionToastTimer = null;

function showActionToast(message, type = 'success') {
    const toast = document.getElementById('actionToast');
    if (!toast || !message) return;

    toast.textContent = message;
    toast.classList.remove('hidden', 'success', 'error');
    toast.classList.add(type === 'error' ? 'error' : 'success');

    if (actionToastTimer) {
        clearTimeout(actionToastTimer);
    }

    actionToastTimer = setTimeout(() => {
        toast.classList.add('hidden');
    }, 2600);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderNotificationList(containerId, notifications) {
    const listContainer = document.getElementById(containerId);
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

function updateNotificationBadge(badgeId, unreadCount) {
    const badge = document.getElementById(badgeId);
    if (!badge) return;

    if (!unreadCount || unreadCount <= 0) {
        badge.classList.add('hidden');
        badge.textContent = '0';
        return;
    }

    badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
    badge.classList.remove('hidden');
}

async function loadCurrentUserContext() {
    try {
        const response = await fetch('../user_info_V3/get_session_user.php', { credentials: 'same-origin' });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            isAdminSessionContext = false;
            return;
        }

        const roleType = String(payload.user?.roleType || '').toLowerCase();
        if (roleType !== 'admin') {
            isAdminSessionContext = false;
            const fullNameEl = document.getElementById('adminProfileFullName');
            const emailEl = document.getElementById('adminProfileEmail');
            if (fullNameEl) fullNameEl.textContent = 'Owner Local Mode';
            if (emailEl) emailEl.textContent = 'No admin session loaded';
            updateNotificationBadge('adminNotificationBadge', 0);
            renderNotificationList('adminNotificationList', []);
            return;
        }

        isAdminSessionContext = true;

        const fullName = payload.user?.fullName || 'Full Name';
        const email = payload.user?.email || 'fullname@email.com';

        const fullNameEl = document.getElementById('adminProfileFullName');
        const emailEl = document.getElementById('adminProfileEmail');
        if (fullNameEl) fullNameEl.textContent = fullName;
        if (emailEl) emailEl.textContent = email;

        updateNotificationBadge('adminNotificationBadge', Number(payload.user?.unreadNotifications || 0));
    } catch (error) {
        console.warn('Unable to load session context:', error);
    }
}

async function loadNotifications(options = {}) {
    if (!isAdminSessionContext) {
        updateNotificationBadge('adminNotificationBadge', 0);
        renderNotificationList('adminNotificationList', []);
        return;
    }

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

        const response = await fetch('../user_info_V3/notifications_api.php', requestOptions);
        const payload = await response.json();
        if (!response.ok || !payload.success) return;

        renderNotificationList('adminNotificationList', payload.notifications || []);
        updateNotificationBadge('adminNotificationBadge', Number(payload.unreadCount || 0));
    } catch (error) {
        console.warn('Unable to load notifications:', error);
    }
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
        loadNotifications({ markRead: true });
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const listsUsers = document.getElementById('listsUsers')
        || document.getElementById('aboutUs')
        || document.getElementById('programs')
        || document.getElementById('classes');

    if (sidebar) {
        sidebar.classList.toggle('active');
    }

    if (listsUsers) {
        listsUsers.classList.toggle('shifted');
    }
}

function setFilterLabel(filterKey) {
    const selectedFilter = document.getElementById('selectedFilter');
    if (selectedFilter) {
        selectedFilter.textContent = roleLabels[filterKey] || roleLabels.all;
    }
}

async function fetchUsersFromApi() {
    const query = new URLSearchParams({
        filter: roleToFilterParam[currentFilter] || 'all',
        search: currentSearch
    });

    const response = await fetch(`get_users.php?${query.toString()}`);
    const payload = await response.json();

    if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'Failed to load users.');
    }

    return payload.users || [];
}

function renderUsersTable() {
    const tableBody = document.getElementById('userTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="8">No users found.</td></tr>';
        return;
    }

    users.forEach((user) => {
        const statusClass = user.status === 'Verified' ? 'verified' : 'not-verified';

        const row = document.createElement('tr');
        row.id = `userRow-${user.id}`;
        row.innerHTML = `
            <td>${escapeHtml(user.firstName)}</td>
            <td>${escapeHtml(user.middleInitial || '')}</td>
            <td>${escapeHtml(user.lastName)}</td>
            <td>${escapeHtml(user.suffix || '')}</td>
            <td>${escapeHtml(user.role)}</td>
            <td><span class="status ${statusClass}">${escapeHtml(user.status)}</span></td>
            <td>${escapeHtml(user.createdAccount)}</td>
            <td>
                <div class="dropdown">
                    <button class="dropbtn" onclick="toggleDropdown(this)">⋮</button>
                    <div class="dropdown-content">
                        <a href="#" onclick="return checkUserInfo(${user.id})">Check User Info</a>
                        <a href="#" onclick="return removeUser(${user.id})">Remove User</a>
                    </div>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

async function loadUsers() {
    try {
        users = await fetchUsersFromApi();
        renderUsersTable();
    } catch (error) {
        console.error(error);
        const tableBody = document.getElementById('userTableBody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="8">Failed to load users.</td></tr>';
        }
    }
}

function toggleDropdown(button) {
    const dropdownContent = button.nextElementSibling;
    if (!dropdownContent) return;

    const willOpen = !dropdownContent.classList.contains('show');

    document.querySelectorAll('.dropdown-content.show').forEach((dropdown) => {
        dropdown.classList.remove('show');
    });

    dropdownContent.classList.toggle('show', willOpen);

    const tableBody = button.closest('.table__body');
    if (tableBody) {
        tableBody.classList.toggle('dropdown-open', willOpen);
    }
}

function toggleUsersDropdown(button) {
    const searchDropdown = button.parentElement;

    document.querySelectorAll('.search-dropdown.show, .dropdown.show').forEach((el) => {
        if (el !== searchDropdown) {
            el.classList.remove('show');
        }
    });

    if (searchDropdown) {
        searchDropdown.classList.toggle('show');
    }
}

function toggleInfoDropdown(button) {
    const parent = button.parentElement;
    if (parent) {
        parent.classList.toggle('show');
    }
}

function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-content.show').forEach((dropdown) => {
        dropdown.classList.remove('show');
    });

    const searchDropdown = document.querySelector('.search-dropdown.show');
    if (searchDropdown) {
        searchDropdown.classList.remove('show');
    }

    document.querySelectorAll('.table__body.dropdown-open').forEach((tableBody) => {
        tableBody.classList.remove('dropdown-open');
    });
}

function closeUserDetailModal() {
    const modal = document.getElementById('userDetailModal');
    if (modal) {
        modal.style.display = 'none';
    }
    selectedUserId = null;
}

function setSelectedAccessRole(roleKey) {
    selectedAccessRole = roleKey;
    const roleButtons = document.querySelectorAll('.role-button');

    roleButtons.forEach((button) => {
        const buttonRole = button.dataset.accessRole || '';
        button.classList.toggle('selected', buttonRole === roleKey);
    });
}

function updateModal(user) {
    document.getElementById('userName').textContent = user.fullName || 'N/A';
    document.getElementById('userId').textContent = `ID: ${user.id}`;
    document.getElementById('userRole').textContent = `Role: ${user.role || 'N/A'}`;
    document.getElementById('userEmail').textContent = `Email: ${user.email || 'N/A'}`;
    document.getElementById('userProgram').textContent = `Program: ${user.program || 'N/A'}`;
    document.getElementById('userYearEnroll').textContent = `Year Enrolled: ${user.yearEnroll || 'N/A'}`;
    document.getElementById('userIdNumber').textContent = `ID Number: ${user.idNumber || 'N/A'}`;
    document.getElementById('userSignUpDate').textContent = `Sign-Up Date: ${user.signUpDate || 'N/A'}`;

    setSelectedAccessRole(user.accessRole || 'student');
}

async function checkUserInfo(userId) {
    closeAllDropdowns();

    try {
        const response = await fetch(`get_user_details.php?user_id=${encodeURIComponent(userId)}`);
        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Unable to load user details.');
        }

        updateModal(payload.user);
        selectedUserId = payload.user.id;

        const modal = document.getElementById('userDetailModal');
        if (modal) {
            modal.style.display = 'block';
        }
    } catch (error) {
        console.error(error);
        alert(error.message || 'Failed to load user details.');
    }

    return false;
}

async function updateUserVerification(action) {
    if (!selectedUserId) {
        alert('Please open user details first.');
        return;
    }

    const body = new URLSearchParams({
        user_id: String(selectedUserId),
        action,
        access_role: selectedAccessRole
    });

    try {
        const response = await fetch('update_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Status update failed.');
        }

        showActionToast(payload.message || `User ${action}d successfully.`, 'success');

        await loadUsers();
        await checkUserInfo(selectedUserId);
    } catch (error) {
        console.error(error);
        showActionToast(error.message || 'Failed to update user status.', 'error');
        alert(error.message || 'Failed to update user status.');
    }
}

async function approveUser() {
    await updateUserVerification('approve');
}

async function rejectUser() {
    await updateUserVerification('reject');
}

async function removeUser(userId) {
    closeAllDropdowns();

    const shouldDelete = confirm('Are you sure you want to remove this user?');
    if (!shouldDelete) {
        return false;
    }

    const body = new URLSearchParams({ user_id: String(userId) });

    try {
        const response = await fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Failed to remove user.');
        }

        showActionToast(payload.message || 'User removed successfully.', 'success');

        if (selectedUserId === userId) {
            closeUserDetailModal();
        }

        await loadUsers();
    } catch (error) {
        console.error(error);
        showActionToast(error.message || 'Failed to remove user.', 'error');
        alert(error.message || 'Failed to remove user.');
    }

    return false;
}

function filterUsers(role) {
    currentFilter = role || 'all';
    setFilterLabel(currentFilter);

    const searchDropdown = document.querySelector('.search-dropdown');
    if (searchDropdown) {
        searchDropdown.classList.remove('show');
    }

    loadUsers();
    return false;
}

function toggleRole(button) {
    const selectedRole = button.dataset.accessRole || 'student';
    setSelectedAccessRole(selectedRole);
}

function wireSearch() {
    const userSearchInput = document.getElementById('userSearchInput');
    if (!userSearchInput) return;

    userSearchInput.addEventListener('input', (event) => {
        const value = (event.target.value || '').trim();
        currentSearch = value;

        if (searchDebounceTimer) {
            clearTimeout(searchDebounceTimer);
        }

        searchDebounceTimer = setTimeout(() => {
            loadUsers();
        }, 250);
    });
}

window.addEventListener('click', (event) => {
    if (!event.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-content.show').forEach((dropdown) => {
            dropdown.classList.remove('show');
        });

        document.querySelectorAll('.table__body.dropdown-open').forEach((tableBody) => {
            tableBody.classList.remove('dropdown-open');
        });
    }

    const searchDropdown = document.querySelector('.search-dropdown');
    if (searchDropdown && !searchDropdown.contains(event.target)) {
        searchDropdown.classList.remove('show');
    }

    const infoDropdownContainer = document.querySelector('.info-dropdown-container');
    if (infoDropdownContainer && !infoDropdownContainer.contains(event.target)) {
        infoDropdownContainer.classList.remove('show');
    }

    if (!event.target.closest('.header-notifications') && !event.target.closest('.header-profile-menu')) {
        document.querySelectorAll('.header-menu-dropdown').forEach((menu) => {
            menu.classList.add('hidden');
            menu.setAttribute('aria-hidden', 'true');
        });
    }

    const modal = document.getElementById('userDetailModal');
    if (modal && event.target === modal) {
        closeUserDetailModal();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    if (header && sidebar) {
        sidebar.style.top = `${header.offsetHeight}px`;
    }

    const roleButtons = document.querySelectorAll('.role-button');
    roleButtons.forEach((button) => {
        const buttonText = (button.textContent || '').trim().toLowerCase();
        if (buttonText === 'admin') button.dataset.accessRole = 'admin';
        if (buttonText === 'executive director') button.dataset.accessRole = 'executiveDirector';
        if (buttonText === 'program directors') button.dataset.accessRole = 'programDirector';
        if (buttonText === 'professors') button.dataset.accessRole = 'professor';
        if (buttonText === 'students') button.dataset.accessRole = 'student';

        button.addEventListener('click', () => {
            toggleRole(button);
        });
    });

    const approveButton = document.getElementById('approveButton');
    const rejectButton = document.getElementById('rejectButton');

    if (approveButton) {
        approveButton.addEventListener('click', approveUser);
    }

    if (rejectButton) {
        rejectButton.addEventListener('click', rejectUser);
    }

    wireSearch();
    setFilterLabel(currentFilter);
    setSelectedAccessRole('student');
    loadCurrentUserContext();
    loadNotifications();
    setInterval(() => {
        loadCurrentUserContext();
        loadNotifications();
    }, 15000);
    loadUsers();
});
