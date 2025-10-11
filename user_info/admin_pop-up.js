/* Admin Confimartion */
function showPopup() {
    document.getElementById('popup').style.display = 'block';
}

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
    window.location.href = 'admin_signup_page.php';
}



