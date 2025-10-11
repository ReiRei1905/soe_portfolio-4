document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.confirm-button').addEventListener('click', function(event) {
        event.preventDefault(); // Prevent form submission
        document.getElementById('confirmation-popup').style.display = 'flex';
    });
});

function closePopup() {
    document.getElementById('confirmation-popup').style.display = 'none';
}

function redirectToLogin() {
    window.location.href = 'login_page.php';
}

function redirectToSignup() {
    window.location.href = 'faculty_signup_page.php';
}