document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const popupOverlay = document.getElementById('popup-overlay');
    const popupMessage = document.getElementById('popup-message');
    const popupText = document.getElementById('popup-text');
    const confirmButton = document.querySelector('.confirm-button');
    const backToSignInButton = document.querySelector('#popup-message button:first-of-type');
    const confirmPopupButton = document.querySelector('#popup-message button:last-of-type');

    roleSelect.addEventListener('change', function(event) {
        showRolePopup(event.target.value);
    });

    confirmButton.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent form submission
        document.getElementById('confirmation-popup').style.display = 'flex';
    });

    backToSignInButton.addEventListener('click', function() {
        closePopup();
        roleSelect.selectedIndex = 0; // Reset the role selection to default
    });

    confirmPopupButton.addEventListener('click', function() {
        closePopup();
        // Keep the selected role as it is
    });

    function showRolePopup(role) {
        switch (role) {
            case 'professor':
                popupText.textContent = 'Note: Choosing your Role as a Prof will make you only create courses, manage assigned classes, and observe students’ academic and extracurricular portfolios (This cannot be changed easily, please recheck your credentials)';
                break;
            case 'program_director':
                popupText.textContent = 'Note: Choosing your Role as a P.D will make you only create courses, lists of students, and observe students’ academic and extracurricular portfolios (This cannot be changed easily, please recheck your credentials)';
                break;
            case 'executive_director':
                popupText.textContent = 'Note: Choosing your Role an ExD will give you access of creating programs and observe students’ academic portfolios and extracurricular portfolios (This cannot be changed easily, please recheck your credentials)';
                break;
            default:
                popupText.textContent = '';
                return; // Do not show the popup if no valid role is selected
        }

        popupOverlay.style.display = 'block';
        popupMessage.style.display = 'block';
    }

    function closePopup() {
        popupOverlay.style.display = 'none';
        popupMessage.style.display = 'none';
    }
});