function goToOverviewPage() {
    /*// Scroll to the top of the page
    window.scrollTo({ top: 0, behavior: 'smooth' });*/
    
    // Redirect to the overview page
    window.location.href = "admin_homepage.html";
}

function handleProfileClick() {
    alert('Profile button clicked!');
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const listsUsers = document.getElementById('listsUsers')
    || document.getElementById('aboutUs') 
    || document.getElementById('programs')
    || document.getElementById('classes');

    // Toggle the sidebar visibility
    sidebar.classList.toggle('active');

    // Adjust the main content position
    listsUsers.classList.toggle('shifted');
}

// Added this event listener to set the top property of the sidebar dynamically
window.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    sidebar.style.top = `${header.offsetHeight}px`;
});

/*function toggleDropdown(button) {
    var dropdownContent = button.nextElementSibling;
    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
}

// Close the dropdown if the user clicks outside of it
window.onclick = function(event) {
    if (!event.target.matches('.dropbtn')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.style.display === 'block') {
                openDropdown.style.display = 'none';
            }
        }
    }
}*/

function toggleDropdown(button) {
    const dropdownContent = button.nextElementSibling;
    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
}

window.addEventListener('click', (event) => {
    if (!event.target.closest('.dropbtn')) {
        document.querySelectorAll('.dropdown-content.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});


// Predefined list of users (replace with actual data fetching in a real application)
// Eventually, when you have a backend, update fetchUsers() to fetch data via API.

/* 

 Replace this:
 async function fetchUsers() {
    return new Promise((resolve) => { /* Simulated delay  });
}

to this:

async function fetchUsers() {
    const response = await fetch('/api/users');  // Replace with actual API
    return await response.json();
}

*/ 

let users = [];

/*async function fetchUsers() {
    return new Promise((resolve) => {
        setTimeout(() => {
            resolve([
                {
                    id: 1,
                    name: 'Kim Chaewon',
                    role: 'Professor',
                    email: 'chae@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12345',
                    signUpDate: '20/03/2025'
                },
                // Add more users here

                {
                    id: 2,
                    name: 'Hong Eunchae',
                    role: 'Student',
                    email: 'hong@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12346',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 3,
                    name: 'Kazuha',
                    role: 'Student',
                    email: 'zuha@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12347',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 4,
                    name: 'Sakura Miyawaki',
                    role: 'Executive Director',
                    email: 'kura@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12348',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 5,
                    name: 'Huh Yunjin',
                    role: 'Professor',
                    email: 'huh@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12349',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 6,
                    name: 'Gawr Gura',
                    role: 'Student',
                    email: 'gura@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12350',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 7,
                    name: 'Ammelia Watson',
                    role: 'Program Director',
                    email: 'ame@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12351',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 8,
                    name: 'Ninomae Ina\'nis',
                    role: 'Professor',
                    email: 'ina@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12352',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 9,
                    name: 'Takanashi Kiara',
                    role: 'Professor',
                    email: 'kiara@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12353',
                    signUpDate: '20/03/2025'
                },
                {
                    id: 10,
                    name: 'Mori Calliope',
                    role: 'Program Director',
                    email: 'mori@example.com',
                    program: 'Computer Engineering',
                    yearEnroll: '2023',
                    idNumber: '2023-12354',
                    signUpDate: '20/03/2025'
                },

            ]);
        }, 500); // Simulates fetching delay
    });
}*/

async function loadUsers() {
    const users = await fetchUsers(); // Simulated or fetched user data
    const tableBody = document.getElementById('userTableBody'); // Ensure the correct ID is used
    tableBody.innerHTML = ''; // Clear existing rows

    users.forEach(user => {
        const row = document.createElement('tr');
        row.id = `userRow-${user.id}`;
        row.innerHTML = `
            <td>${user.firstName}</td>
            <td>${user.middleInitial || ''}</td>
            <td>${user.lastName}</td>
            <td>${user.suffix || ''}</td>
            <td>${user.role}</td>
            <td><span class="status not-verified">${user.status}</span></td>
            <td>${user.createdAccount}</td>
            <td>
                <div class="dropdown">
                    <button class="dropbtn" onclick="toggleDropdown(this)">⋮</button>
                    <div class="dropdown-content">
                        <a href="#" onclick="checkUserInfo(${user.id})">Check User Info</a>
                        <a href="#" onclick="removeUser(${user.id})">Remove User</a>
                    </div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Example fetchUsers function to simulate user data
async function fetchUsers() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve([
                {
                    id: 1,
                    firstName: 'Kim',
                    middleInitial: '',
                    lastName: 'Chaewon',
                    suffix: '',
                    role: 'Professor',
                    status: 'Not Verified',
                    createdAccount: '20/03/2025'
                },
                {
                    id: 2,
                    firstName: 'Hong',
                    middleInitial: '',
                    lastName: 'Eunchae',
                    suffix: '',
                    role: 'Student',
                    status: 'Not Verified',
                    createdAccount: '21/03/2025'
                },
                // Add more user objects as needed
            ]);
        }, 500); // Simulated delay
    });
}

// Ensure loadUsers is called on page load
document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
});

/*async function loadUsers() {
    const users = await fetchUsers();
    const tableBody = document.getElementById('userTableBody');
    tableBody.innerHTML = ''; // Clear existing entries

    users.forEach(user => {
        const row = document.createElement('tr');
        row.id = `userRow-${user.id}`;
        row.innerHTML = `
            <td>${user.id}</td>
            <td>${user.name}</td>
            <td>${user.role}</td>
            <td>${user.email}</td>
            <td>${user.program}</td>
            <td class="status">Pending</td>
            <td>
                <button onclick="checkUserInfo(${user.id})">View</button>
                <button onclick="approveUser(${user.id})">Approve</button>
                <button onclick="rejectUser(${user.id})">Reject</button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}*/

// Call function on page load
/*document.addEventListener('DOMContentLoaded', loadUsers);*/


/**/

function checkUserInfo(userId) {
    // Fetch user data (replace with actual backend call if needed)
    const users = [
        {
            id: 1,
            firstName: 'Kim',
            middleInitial: '',
            lastName: 'Chaewon',
            suffix: '',
            role: 'Professor',
            email: 'chae@example.com',
            program: 'Computer Engineering',
            yearEnroll: '2023',
            idNumber: '123456',
            signUpDate: '20/03/2025'
        },
        {
            id: 2,
            firstName: 'Hong',
            middleInitial: '',
            lastName: 'Eunchae',
            suffix: '',
            role: 'Student',
            email: 'hong@example.com',
            program: 'Computer Engineering',
            yearEnroll: '2023',
            idNumber: '654321',
            signUpDate: '21/03/2025'
        }
    ];

    // Find the user by ID
    const user = users.find(u => u.id === userId);
    if (!user) {
        alert('User not found!');
        return;
    }

    // Populate the modal with user details
    document.getElementById('userName').textContent = `${user.firstName} ${user.lastName}`;
    document.getElementById('userId').textContent = `ID: ${user.id}`;
    document.getElementById('userRole').textContent = `Role: ${user.role}`;
    document.getElementById('userEmail').textContent = `Email: ${user.email}`;
    document.getElementById('userProgram').textContent = `Program: ${user.program}`;
    document.getElementById('userYearEnroll').textContent = `Year Enrolled: ${user.yearEnroll}`;
    document.getElementById('userIdNumber').textContent = `ID Number: ${user.idNumber}`;
    document.getElementById('userSignUpDate').textContent = `Sign-Up Date: ${user.signUpDate}`;

    // Show the modal
    const modal = document.getElementById('userDetailModal');
    modal.style.display = 'block';
}

// Close the modal
function closeUserDetailModal() {
    const modal = document.getElementById('userDetailModal');
    modal.style.display = 'none';
}

// Add event listener to close button
document.addEventListener('DOMContentLoaded', function() {
    const closeModalButton = document.querySelector('#closeModalButton');
    if (closeModalButton) {
        closeModalButton.addEventListener('click', closeUserDetailModal);
    }
});

function setUserRole(role) {
    console.log(`User role set to: ${role}`);
    // Implement role setting logic here when applying database
}

/* Assuming users already signed up */
function toggleRole(button) {
    button.classList.toggle('selected');
}

async function approveUser(userId) {
    console.log('User approved:', userId);
    
    // Simulating API call (replace with real request later)
    await fetch(`/api/users/${userId}/approve`, { method: 'POST' });

    updateUserStatus(userId, 'Verified');
    closeUserDetailModal();
    // Close the dropdown
    closeAllDropdowns();
}

async function rejectUser(userId) {
    console.log('User rejected:', userId);

    await fetch(`/api/users/${userId}/reject`, { method: 'POST' });

    updateUserStatus(userId, 'Not Verified');
    closeUserDetailModal();
    // Close the dropdown
    closeAllDropdowns();
}

// Helper function to close all dropdowns
function closeAllDropdowns() {
    document.querySelectorAll('.dropdown-content, .search-dropdown-content').forEach(dropdown => {
        dropdown.classList.remove('show'); // Remove the 'show' class
        dropdown.style.display = 'none';  // Optionally set display to 'none'
    });
}


/*function approveUser(userId) {
    console.log('User approved');
    // Implement user approval logic here when applying database
    const user = users.find(user => user.id === userId);
    if (user) {
        user.status = 'Verified';
        updateUserStatus(userId, 'Verified');
        closeUserDetailModal(); // Move this line after the status update
    }
}*/

/*function rejectUser(userId) {
    console.log('User rejected');
    // Implement user rejection logic here when applying database
    const user = users.find(user => user.id === userId);
    if (user) {
        user.status = 'Not Verified';
        updateUserStatus(userId, 'Not Verified');
        closeUserDetailModal(); // Move this line after the status update
    }
}*/

function updateUserStatus(userId, status) {
    const userRow = document.querySelector(`#userRow-${userId}`);
    if (userRow) {
        const statusElement = userRow.querySelector('.status');
        if (statusElement) {
            statusElement.innerText = status;
            statusElement.classList.toggle('verified', status === 'Verified');
            statusElement.classList.toggle('not-verified', status === 'Not Verified');
        }
    }
}

// Add event listener to role buttons and approve/reject buttons
document.addEventListener('DOMContentLoaded', function() {
    const roleButtons = document.querySelectorAll('.role-button');
    roleButtons.forEach(button => {
        button.addEventListener('click', function() {
            toggleRole(button);
        });
    });

    const approveButton = document.getElementById('approveButton');
    const rejectButton = document.getElementById('rejectButton');
    approveButton.addEventListener('click', function() {
        const userId = parseInt(document.getElementById('userId').innerText.split(': ')[1]);
        approveUser(userId);
    });
    rejectButton.addEventListener('click', function() {
        const userId = parseInt(document.getElementById('userId').innerText.split(': ')[1]);
        rejectUser(userId);
    });
});

function toggleUsersDropdown(button) {
    button.parentElement.classList.toggle("show");
}

function toggleInfoDropdown(button) {
    button.parentElement.classList.toggle("show");
}

function filterUsers(role) {
    console.log("Filtering users by role:", role);

    // Update the selected filter text
    const selectedFilter = document.getElementById('selectedFilter');
    selectedFilter.innerText = role.charAt(0).toUpperCase() + role.slice(1).replace(/([A-Z])/g, ' $1').trim();

    // Get all user rows
    const userRows = document.querySelectorAll('tbody tr');

    // Loop through each user row and filter based on the role
    userRows.forEach(row => {
        const roleCell = row.querySelector('td:nth-child(6)'); // Assuming the role is in the 6th column
        if (role === 'all' || roleCell.innerText.toLowerCase() === role.toLowerCase()) {
            row.style.display = ''; // Show the row
        } else {
            row.style.display = 'none'; // Hide the row
        }
    });
}

function removeUser(userId) {
    console.log("Removing user with ID:", userId);
}