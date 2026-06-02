function initFacultySettingsPage() {
    if (window.__facultySettingsInitialized) return;
    window.__facultySettingsInitialized = true;
    loadFacultySettings();
    bindFacultySettingsActions();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFacultySettingsPage);
} else {
    initFacultySettingsPage();
}

function loadFacultySettings() {
    const endpoint = typeof buildFacultyPath === 'function'
        ? buildFacultyPath('get_faculty_settings.php')
        : 'get_faculty_settings.php';

    fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const userInfo = data.data || {};
                document.getElementById('email').value = userInfo.email || '';
                document.getElementById('idNumber').value = userInfo.id_number || '';
                document.getElementById('program').value = userInfo.program_name || '';

                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.value = '********';
                    delete passwordInput.dataset.actualPassword;
                }

                if (userInfo.profile_picture) {
                    displayProfilePicture(`images/user_images/${userInfo.profile_picture}`);
                }
            } else {
                console.error('Error loading settings:', data.message);
                alert('Error loading settings. Please refresh the page.');
            }
        })
        .catch(error => console.error('Error:', error));
}

function previewProfilePicture(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    const allowed_types = ['image/png', 'image/jpeg'];
    if (!allowed_types.includes(file.type)) {
        alert('Only PNG and JPG formats are allowed');
        event.target.value = '';
        return;
    }
    
    // Validate file size (5MB)
    const max_size = 5 * 1024 * 1024;
    if (file.size > max_size) {
        alert('File size must not exceed 5MB');
        event.target.value = '';
        return;
    }
    
    // Display preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('profilePicturePreview');
        preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile Picture Preview">';
    };
    reader.readAsDataURL(file);
}

function displayProfilePicture(imagePath) {
    const preview = document.getElementById('profilePicturePreview');
    const resolvedPath = imagePath.startsWith('images/') ? `../${imagePath}` : `../images/user_images/${imagePath}`;
    preview.innerHTML = '<img src="' + resolvedPath + '" alt="Profile Picture">';
}

function saveFacultyProfilePicture() {
    const fileInput = document.getElementById('profilePictureInput');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a profile picture first');
        return;
    }
    
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    fetch('upload_faculty_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile picture saved successfully!');
            loadFacultySettings();
            document.getElementById('profilePictureInput').value = '';
            if (data.profile_picture) {
                updateFacultyHeaderProfileImage(data.profile_picture);
            }
        } else {
            alert('Error saving profile picture: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving profile picture');
    });
}

function updateFacultyHeaderProfileImage(fileName) {
    if (!fileName) return;
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

        img.src = `../images/user_images/${fileName}`;
        img.classList.remove('hidden');

        const icon = button.querySelector('i');
        if (icon) icon.classList.add('hidden');
    });
}

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function redirectToPasswordChange() {
    // Redirect to the password reset/change page in user_info_V3
    window.location.href = '../../user_info_V3/recover_psw.php';
}

function switchSettingsTab(tabName) {
    // Hide all content
    document.querySelectorAll('.settings-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.settings-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected content
    const contentId = tabName + '-content';
    const contentElement = document.getElementById(contentId);
    if (contentElement) {
        contentElement.classList.add('active');
    }
    
    // Mark button as active
    const buttonElement = document.querySelector(`[data-tab="${tabName}"]`);
    if (buttonElement) {
        buttonElement.classList.add('active');
    }
}

function bindFacultySettingsActions() {
    const tabs = document.querySelectorAll('.settings-tab-btn[data-tab]');
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const tabName = tab.getAttribute('data-tab');
            if (tabName) switchSettingsTab(tabName);
        });
    });

    const toggleIcon = document.getElementById('passwordToggleIcon');
    if (toggleIcon) {
        toggleIcon.addEventListener('click', togglePasswordVisibility);
        toggleIcon.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                togglePasswordVisibility();
            }
        });
    }

    const fileInput = document.getElementById('profilePictureInput');
    if (fileInput) {
        fileInput.addEventListener('change', previewProfilePicture);
    }

    const triggerUploadBtn = document.querySelector('[data-profile-action="trigger-upload"]');
    if (triggerUploadBtn && fileInput) {
        triggerUploadBtn.addEventListener('click', () => fileInput.click());
    }

    const saveBtn = document.querySelector('[data-profile-action="save-picture"]');
    if (saveBtn) {
        saveBtn.addEventListener('click', saveFacultyProfilePicture);
    }

    const changePasswordBtn = document.querySelector('[data-profile-action="change-password"]');
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', redirectToPasswordChange);
    }

    const accessibilityBtn = document.querySelector('[data-accessibility-action="save"]');
    if (accessibilityBtn) {
        accessibilityBtn.addEventListener('click', saveAccessibilitySettings);
    }

    const feedbackBtn = document.querySelector('[data-feedback-action="submit"]');
    if (feedbackBtn) {
        feedbackBtn.addEventListener('click', submitFeedback);
    }
}

function saveAccessibilitySettings() {
    alert('Accessibility settings saved!');
}

function submitFeedback() {
    const subject = document.getElementById('feedbackSubject').value.trim();
    const message = document.getElementById('feedbackMessage').value.trim();
    const screenshotInput = document.getElementById('feedbackScreenshot');
    const screenshotFile = screenshotInput ? screenshotInput.files[0] : null;

    if (!subject || !message) {
        alert('Please fill in all fields');
        return;
    }

    if (screenshotFile) {
        const allowedTypes = ['image/png', 'image/jpeg'];
        if (!allowedTypes.includes(screenshotFile.type)) {
            alert('Screenshot must be PNG or JPG');
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        if (screenshotFile.size > maxSize) {
            alert('Screenshot must be 5MB or less');
            return;
        }
    }

    const formData = new FormData();
    formData.append('subject', subject);
    formData.append('message', message);
    if (screenshotFile) {
        formData.append('screenshot', screenshotFile);
    }

    fetch('../admin_side/feedback_management/submit_feedback.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then((response) => response.json())
    .then((data) => {
        if (!data.success) {
            alert(data.message || 'Unable to submit feedback.');
            return;
        }

        alert('Thank you for your feedback!');
        document.getElementById('feedbackSubject').value = '';
        document.getElementById('feedbackMessage').value = '';
        if (screenshotInput) screenshotInput.value = '';
    })
    .catch(() => {
        alert('An error occurred while submitting feedback.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const messageBox = document.getElementById('feedbackMessage');
    const fileInput = document.getElementById('feedbackScreenshot');
    const previewContainer = document.getElementById('screenshotPreviewContainer');
    const previewImage = document.getElementById('screenshotPreviewImage');
    const removeBtn = document.getElementById('removeScreenshotBtn');
    const uploadPrompt = document.getElementById('uploadPrompt');

    function attachImage(file) {
        // Ensure it's an image
        if (!file.type.startsWith('image/')) return;

        // Push the pasted/selected file into the actual <input type="file">
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        // Show the UI preview
        previewImage.src = URL.createObjectURL(file);
        previewContainer.classList.remove('hidden');
        uploadPrompt.classList.add('hidden'); // Hide the "Click to browse" text
    }

    // 1. Listen for Paste (Ctrl+V) inside the message textarea
    if (messageBox) {
        messageBox.addEventListener('paste', (e) => {
            const items = (e.clipboardData || window.clipboardData).items;
            for (let index in items) {
                const item = items[index];
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    const blob = item.getAsFile();
                    attachImage(blob);
                    e.preventDefault(); // Stops the browser from trying to paste a raw image string
                }
            }
        });
    }

    // 2. Listen for normal file browsing clicks
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                attachImage(e.target.files[0]);
            }
        });
    }

    // 3. Remove the image if the user clicks the red X
    if (removeBtn) {
        removeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation(); // Prevents triggering the file browse window
            fileInput.value = '';
            previewImage.src = '';
            previewContainer.classList.add('hidden');
            uploadPrompt.classList.remove('hidden');
        });
    }
});
