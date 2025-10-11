/* Student Confirmation */
function showPopup() {
    document.getElementById('popup').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.confirm-button').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent form submission
        document.getElementById('confirmation-popup').style.display = 'flex';
    });
});

function closePopup() {
    document.getElementById('popup').style.display = 'none';
}

function submitForm() {
    // Assuming form validation and submission logic here
    document.getElementById('popup').style.display = 'none';
    document.getElementById('confirmation-popup').style.display = 'block';
}

function redirectToLogin() {
    window.location.href = 'login_page.php';
}

function redirectToSignup() {
    window.location.href = 'student_signup_page.php';
}