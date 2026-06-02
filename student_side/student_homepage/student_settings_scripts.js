// Load student settings on page load
document.addEventListener('DOMContentLoaded', function() {
    ensureStudentProfileSummaryPlaceholders();
    loadStudentSessionContext();
    loadStudentSettings();
});

function loadStudentSettings() {
    fetch('get_student_settings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('email').value = data.email || '';
                document.getElementById('idNumber').value = data.id_number || '';
                document.getElementById('program').value = data.program || '';
                
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.value = '********';
                    delete passwordInput.dataset.actualPassword;
                }
                
                // Load profile picture if exists
                if (data.profile_picture_path) {
                    displayProfilePicture(data.profile_picture_path);
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
    preview.innerHTML = '<img src="../../' + imagePath + '" alt="Profile Picture">';
}

function saveProfilePicture() {
    const fileInput = document.getElementById('profilePictureInput');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a profile picture first');
        return;
    }
    
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    fetch('upload_student_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile picture saved successfully!');
            loadStudentSettings();
            document.getElementById('profilePictureInput').value = '';
            
            // ADDED THIS: Instantly push the new image to the header avatar without refreshing
            if (data.path && typeof updateUIProfilePictures === 'function') {
                updateUIProfilePictures(data.path);
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

function toggleDropdown(element) {
    if (!element || !element.nextElementSibling) return;
    const dropdown = element.nextElementSibling;
    dropdown.classList.toggle('hidden');
    element.setAttribute('aria-expanded', dropdown.classList.contains('hidden') ? 'false' : 'true');
}

function goToStudentOverviewPage() {
    window.location.href = 'student_homepage.html';
}

function toggleCustomize() {
    // This function is called from the profile dropdown
    // You can customize the behavior as needed
    console.log('Customize E-Portfolio clicked');
}

// Additional helper functions for profile dropdown functionality
function normalizeStudentHeaderLinks() {
    const logoutPath = '../../user_info_V3/logout.php';
    document.querySelectorAll('.logout-link').forEach((linkEl) => {
        linkEl.setAttribute('href', logoutPath);
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const isDropdownToggle = event.target.closest('[onclick*="toggleDropdown"]') || 
                            event.target.closest('.profile-avatar-btn');
    
    if (!isDropdownToggle) {
        document.querySelectorAll('.dropdown:not(.hidden)').forEach(dropdown => {
            dropdown.classList.add('hidden');
            const toggleBtn = dropdown.previousElementSibling;
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
});

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
