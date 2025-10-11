function goToOverviewPage() {
    // Scroll to the top of the page
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function handleProfileClick() {
    alert('Profile button clicked!');
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    sidebar.classList.toggle('active');
    mainContent.classList.toggle('shifted');

}

// Added this event listener to set the top property of the sidebar dynamically
window.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('header');
    const sidebar = document.getElementById('sidebar');
    sidebar.style.top = `${header.offsetHeight}px`;
});

function toggleDropdown(button) {
    var dropdownContent = button.nextElementSibling;
    dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
}

function checkUserInfo(userId) {
    // Implement the logic to check user info
    alert('Check User Info for user ID: ' + userId);
}

function removeUser(userId) {
    // Implement the logic to remove user
    alert('Remove User with user ID: ' + userId);
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
}

// Predefined list of users (replace with actual data fetching in a real application)
const users = [
    {
        id: 1,
        name: 'Kim Chaewon',
        email: 'chae@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12345',
        signUpDate: '20/03/2025'
    },
    {
        id: 2,
        name: 'Hong Eunchae',
        email: 'hong@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12346',
        signUpDate: '20/03/2025'
    },
    {
        id: 3,
        name: 'Kazuha',
        email: 'zuha@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12347',
        signUpDate: '20/03/2025'
    },

    {
        id: 4,
        name: 'Sakura Miyawaki',
        email: 'kura@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12348',
        signUpDate: '20/03/2025'

    },

    {
        id: 5,
        name: 'Huh Yunjin',
        email: 'huh@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12349',
        signUpDate: '20/03/2025'

    },

    {
        id: 6,
        name: 'Gawr Gura',
        email: 'gura@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12350',
        signUpDate: '20/03/2025'

    },

    {
        id: 7,
        name: 'Ammelia Watson',
        email: 'ame@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12351',
        signUpDate: '20/03/2025'

    },

    {
        id: 8,
        name: 'Ninomae Ina\'nis',
        email: 'ina@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12352',
        signUpDate: '20/03/2025'

    },

    {
        id: 9,
        name: 'Takanashi Kiara',
        email: 'kiara@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12353',
        signUpDate: '20/03/2025'

    },

    {
        id: 10,
        name: 'Mori Calliope',
        email: 'mori@example.com',
        program: 'Computer Engineering',
        yearEnroll: '2023',
        idNumber: '2023-12354',
        signUpDate: '20/03/2025'

    },

];

function checkUserInfo(userId) {
    // Find user data based on userId
    const userData = users.find(user => user.id === userId);

    if (userData) {
        console.log('User data found:', userData); // Debugging statement
        // Populate user detail view with fetched data
        document.getElementById('userName').innerText = `Name: ${userData.name}`;
        document.getElementById('userId').innerText = `ID: ${userData.id}`;
        document.getElementById('userEmail').innerText = `School Email: ${userData.email}`;
        document.getElementById('userProgram').innerText = `Program Enrolled: ${userData.program}`;
        document.getElementById('userYearEnroll').innerText = `Enrolled in: ${userData.yearEnroll}`;
        document.getElementById('userIdNumber').innerText = `ID Number: ${userData.idNumber}`;
        document.getElementById('userSignUpDate').innerText = `Signed up on ${userData.signUpDate}`;

        // Show user detail modal
        document.getElementById('userDetailModal').style.display = 'flex';
    } else {
        alert('User not found');
    }
}

function closeUserDetailModal() {
    document.getElementById('userDetailModal').style.display = 'none';
}

// Add event listener to close button
document.addEventListener('DOMContentLoaded', function() {
    const closeModalButton = document.querySelector('#closeModalButton');
    closeModalButton.addEventListener('click', closeUserDetailModal);
});

function setUserRole(role) {
    console.log(`User role set to: ${role}`);
    // Implement role setting logic here when applying database

}

/* Assuming users already signed up */
function toggleRole(button) {
    
    button.classList.toggle('selected');
}

function approveUser(userId) {
    console.log('User approved');
    // Implement user approval logic here when applying database
    const user = users.find(user => user.id === userId);
    if (user) {
        user.status = 'Verified';
        updateUserStatus(userId, 'Verified');
        closeUserDetailModal(); // Move this line after the status update
    }
}

function rejectUser(userId) {
    console.log('User rejected');
    // Implement user rejection logic here when applying database
    const user = users.find(user => user.id === userId);
    if (user) {
        user.status = 'Not Verified';
        updateUserStatus(userId, 'Not Verified');
        closeUserDetailModal(); // Move this line after the status update
    }
}

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

//
function toggleDropdown(button) {
    button.parentElement.classList.toggle("show");
}

function toggleInfoDropdown(button) {
    button.parentElement.classList.toggle("show");
}

function filterUsers(role) {
    console.log("Filtering users by role:", role);
}

function checkUserInfo(userId) {
    console.log("Checking user info for user ID:", userId);
}

function closeUserDetailModal() {
    console.log("Closing user detail modal");
}