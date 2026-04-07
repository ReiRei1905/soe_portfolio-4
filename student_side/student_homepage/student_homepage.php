<?php
session_start();
// Require authenticated student; otherwise redirect to login page
if (empty($_SESSION['email'])) {
    header('Location: ../../user_info_V3/index.php');
    exit();
}

$studentName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')) ?: 'Student Name';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-portfolio</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="../style_student.css" rel="stylesheet"/>
    <script src="../student_homepage/homepage_scripts.js" defer></script>
</head>
<body class="page-layout" data-student-name="<?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>" data-upload-context="homepage" data-home-url="../student_homepage/student_homepage.php">
<header>
    <div class="header-container">
        <div class="header-display">
            <img alt="College logo" class="college-header" src="../../images/soe apc logo.png"/>
        </div>
    </div>

    <div class="header-actions">
        <div class="header-home">
            <i class="fas fa-home" onclick="goToStudentOverviewPage()"></i>
            <h1 class="text-lg">E-PORTFOLIO</h1>
        </div>
        <div class="header-search">
            <input class="search-input" placeholder="Search" type="text"/>
            <i class="fas fa-search"></i>
        </div>
        
        <div class="header-profile">
            <!-- Notifications / Upload / Profile (dropdowns) -->
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <div class="relative header-notifications">
                    <i class="fas fa-bell text-lg cursor-pointer" onclick="toggleDropdown(this)" aria-haspopup="true" aria-expanded="false"></i>
                    <span id="studentNotificationBadge" class="notification-count-badge hidden">0</span>
                    <div id="notificationDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
                        <div id="studentNotificationList" class="notification-list">
                            <p class="text-gray-500 text-center">No new notifications at the moment.</p>
                        </div>
                    </div>
                </div>

                <div class="relative header-upload">
                    <i class="fas fa-cloud-upload-alt text-lg cursor-pointer" onclick="toggleDropdown(this)" aria-haspopup="true" aria-expanded="false"></i>
                    <div id="uploadDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
                        <div class="upload-header">
                            <i class="fas fa-cloud-upload-alt upload-icon-top cursor-pointer" onclick="triggerFileExplorer()" title="Upload from computer"></i>
                            <input type="file" id="hiddenFileInput" class="hidden" onchange="handleFileSelection(this)">
                            <input type="text" class="file-name-input" placeholder="Enter file name" id="uploadFileName">
                        </div>

                        <div class="upload-button-group">
                            <div class="relative">
                                <button class="btn-upload-outline" type="button" onclick="handleCustomFolder()">Add Custom Folder</button>
                                <div id="customFolderPopup" class="custom-folder-popup hidden">
                                    <div class="folder-input-wrapper">
                                        <i class="fas fa-folder folder-icon-small"></i>
                                        <input type="text" id="customFolderName" class="folder-name-input" placeholder="Enter folder name">
                                    </div>
                                    <div class="folder-popup-actions">
                                        <button id="btnCreateFolder" class="btn-folder-create is-disabled" type="button" onclick="createCustomFolder()" disabled aria-disabled="true">Create</button>
                                        <button class="btn-folder-cancel" type="button" onclick="hideCustomFolderPopup()">Cancel</button>
                                    </div>
                                </div>
                            </div>
                            <div class="relative">
                                <button class="btn-upload-outline" type="button" onclick="handleExistingFolder()">Use Existing Folder</button>
                                <div id="existingFolderPopup" class="existing-folder-popup hidden">
                                    <div class="existing-folder-header">
                                        <span>Folders</span>
                                    </div>
                                    <div id="folderListContainer" class="folder-list-container">
                                        <p class="text-gray-400 text-xs text-center py-4">No folders created yet.</p>
                                    </div>
                                    <div class="folder-popup-actions">
                                        <button class="btn-folder-add" type="button" onclick="selectFolder()">Add</button>
                                        <button class="btn-folder-back" type="button" onclick="hideExistingFolderPopup()">Back</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="category-selection-area">
                            <div class="category-option">
                                <input type="radio" name="category" value="assessment" id="cat-assessment">
                                <label for="cat-assessment" class="category-label-box">Assessments</label>
                            </div>
                            <div class="category-option">
                                <input type="radio" name="category" value="projects" id="cat-projects">
                                <label for="cat-projects" class="category-label-box">Projects</label>
                            </div>
                            <div class="category-option">
                                <input type="radio" name="category" value="certificates" id="cat-certificates">
                                <label for="cat-certificates" class="category-label-box">Certificate/Awards</label>
                            </div>
                        </div>

                        <div class="upload-footer-actions">
                            <button class="btn-upload-done" type="button" onclick="addFile()">Done</button>
                            <button class="btn-upload-cancel" type="button" onclick="closeUploadModal()">Cancel</button>
                        </div>
                    </div>
                </div>

                <div class="relative header-profile-menu">
                    <div class="w-10 h-10 profile-avatar-btn" onclick="toggleDropdown(this)" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user text-xl"></i>
                    </div>
                    <div id="profileDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
                        <div class="profile-summary">
                            <p id="studentProfileFullName" class="profile-summary-name"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p id="studentProfileEmail" class="profile-summary-email"><?php echo htmlspecialchars((string) ($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <a href="#" class="block menu-item"><i class="fas fa-edit mr-2"></i> Edit Profile</a>
                        <a href="#" onclick="toggleCustomize(); return false;" class="block menu-item"><i class="fas fa-paint-brush mr-2"></i> Customize E-Portfolio</a>
                        <a href="#" class="block menu-item"><i class="fas fa-cog mr-2"></i> Settings</a>
                        <a href="#" class="block menu-item"><i class="fas fa-question-circle mr-2"></i> Help</a>
                        <div style="text-align:center;margin-top:0.5rem;">
                            <a href="../../user_info_V3/logout.php" class="logout-link"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="homepage-header">
        <div class="header-left">
            <i class="fas fa-bars sidebar-toggle" onclick="toggleSidebar()"></i>
            <h2 class="homepage-title">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                <li class="breadcrumb-item" aria-current="page"><a href="../student_homepage/student_homepage.php"></a>Homepage</li>
                </ol>
            </nav>
            </h2>
        </div>
    </div>
</header>
<main class = "main-content">  
    <div id="sidebar">
        <nav>
            <a class="text-lg" href="../assessment/assessments.html">Assessments</a>
            <a class="text-lg" href="../projects/projects.html">Projects</a>
            <a class="text-lg" href="../certificates/certificates.html">Certificates/Awards</a>
            <a class="text-lg" href="../student_class/student_classes.html">Classes</a>
            <a class="text-lg" href="../faculty_about_us.html">About us</a>
        </nav>
    </div>

<div id="homepage" class="homepage-section">

    <div class="profile-wrapper">
        <section class="profile-card" aria-label="student profile">
            <button id="profileEditTrigger" class="edit-corner-trigger profile-edit-trigger" type="button" aria-label="Edit profile section">
                <i class="fas fa-pen-square" aria-hidden="true"></i>
            </button>
            <div class="profile-info">
                <h2 id="studentName" class="profile-name"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p id="profileBioText" class="profile-bio">About the user</p>

                <div id="profileEditPanel" class="profile-edit-panel hidden" aria-label="Edit profile text">
                    <input id="profileNameInput" type="text" placeholder="Enter name" maxlength="80" />
                    <input id="profileBioInput" type="text" placeholder="About the user" maxlength="160" />
                    <div class="profile-edit-actions">
                        <button type="button" class="btn-profile-save" onclick="saveProfileTextChanges()">Save</button>
                        <button type="button" class="btn-profile-cancel" onclick="cancelProfileTextChanges()">Cancel</button>
                    </div>
                </div>
            </div>
            <div class="profile-right">
                <div class="profile-avatar" role="img" aria-label="student avatar">
                    <i class="fas fa-user" aria-hidden="true"></i>
                    <span class="avatar-icon">👤</span>
                </div>
            </div>
        </section>
    </div>
    <div id="customizeControls" class="customize-controls floating-customize-controls hidden" aria-hidden="true" aria-label="Customize controls">
        <button id="addFileBtn" title="Add new category" class="control-btn control-add" type="button" onclick="addCategory()">
            <i class="fas fa-plus"></i>
        </button>

        <button title="Delete category" class="control-btn control-delete" type="button" onclick="deleteCategory()">
            <i class="fas fa-trash"></i>
        </button>

        <button title="Go back" class="control-btn control-back" type="button" onclick="goBack()">
            <i class="fas fa-arrow-left"></i>
        </button>

        <div id="addCategoryPopup" class="add-category-popup hidden" role="dialog" aria-modal="false" aria-labelledby="addCategoryPopupTitle">
            <div id="addCategoryPopupTitle" class="add-category-input-wrap">
                <i class="fas fa-folder" aria-hidden="true"></i>
                <input id="newCategoryName" type="text" placeholder="Enter Portfolio Name" maxlength="60" />
            </div>
            <div class="add-category-actions">
                <button type="button" class="btn-category-create" onclick="createCategoryFromPopup()">Create</button>
                <button type="button" class="btn-category-cancel" onclick="closeAddCategoryPopup()">Cancel</button>
            </div>
        </div>
    </div>

    <section class="quick-access" aria-label="quick access">
        <div class="quick-access-grid">
            <button onclick="openFileManagement('fileManagementModal','projects', event)" class="quick-card" type="button">
                <span class="edit-corner-trigger quick-card-edit-trigger" role="button" tabindex="0" aria-label="Edit portfolio title">
                    <i class="fas fa-pen-square" aria-hidden="true"></i>
                </span>
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top projects</h3>
            </button>

            <button onclick="openFileManagement('fileManagementModal','certificates', event)" class="quick-card" type="button">
                <span class="edit-corner-trigger quick-card-edit-trigger" role="button" tabindex="0" aria-label="Edit portfolio title">
                    <i class="fas fa-pen-square" aria-hidden="true"></i>
                </span>
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top certificates/awards</h3>
            </button>

            <button onclick="openFileManagement('fileManagementModal','assessments', event)" class="quick-card" type="button">
                <span class="edit-corner-trigger quick-card-edit-trigger" role="button" tabindex="0" aria-label="Edit portfolio title">
                    <i class="fas fa-pen-square" aria-hidden="true"></i>
                </span>
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top assessments</h3>
            </button>
        </div>

        <div id="quickTitleEditPopup" class="quick-title-edit-popup hidden" role="dialog" aria-modal="false" aria-label="Edit portfolio title">
            <input id="quickTitleEditInput" type="text" placeholder="Enter Portfolio Name" maxlength="60" />
            <div class="quick-title-edit-actions">
                <button type="button" class="btn-quick-save" onclick="saveQuickCardTitleChange()">Save</button>
                <button type="button" class="btn-quick-cancel" onclick="cancelQuickCardTitleChange()">Cancel</button>
            </div>
        </div>
    </section>

    <div id="fileManagementModal" class="modal hidden file-management" role="dialog" aria-modal="true" aria-labelledby="fileManagementTitle">
        <div class="modal-content">
            <button class="modal-close" onclick="closeFileManagement()">&times;</button>
            <h2 id="fileManagementTitle">Manage Files</h2>
            <div class="file-carousel-shell" aria-label="Portfolio item slider">
                <button id="fileCarouselPrev" class="file-carousel-arrow" type="button" onclick="showPreviousFileItem()" aria-label="Previous item">&#10094;</button>

                <div class="file-carousel-viewport">
                    <button id="fileCarouselItem" class="file-carousel-item" type="button" onclick="openCurrentFileItem()"></button>
                    <p id="fileCarouselCounter" class="file-carousel-counter">1 / 1</p>
                </div>

                <button id="fileCarouselNext" class="file-carousel-arrow" type="button" onclick="showNextFileItem()" aria-label="Next item">&#10095;</button>
            </div>
            <div class="file-management-actions">
                <button type="button" class="modal-action-open-picker" onclick="openQuickCardFilePicker()">Add Files</button>
                <button type="button" onclick="closeFileManagement()" class="modal-action-close">Done</button>
            </div>
        </div>
    </div>

    <div id="quickCardFilePickerModal" class="modal hidden file-picker-modal" role="dialog" aria-modal="true" aria-labelledby="quickCardFilePickerTitle">
        <div class="modal-content">
            <button class="modal-close" onclick="closeQuickCardFilePicker()">&times;</button>
            <h2 id="quickCardFilePickerTitle">Select Files</h2>
            <p class="picker-hint">Choose files only (PDF, PNG, JPG). Multiple selection is allowed.</p>

            <div id="quickCardPickerCategoryTabs" class="picker-category-tabs" role="tablist" aria-label="File categories">
                <button type="button" class="picker-tab is-active" data-category="all" onclick="setQuickCardPickerCategory('all')">All</button>
                <button type="button" class="picker-tab" data-category="assessment" onclick="setQuickCardPickerCategory('assessment')">Assessments</button>
                <button type="button" class="picker-tab" data-category="projects" onclick="setQuickCardPickerCategory('projects')">Projects</button>
                <button type="button" class="picker-tab" data-category="certificates" onclick="setQuickCardPickerCategory('certificates')">Certificates</button>
            </div>

            <div id="quickCardPickerList" class="picker-file-list" aria-live="polite"></div>

            <div class="picker-actions">
                <button type="button" class="picker-confirm-btn" onclick="confirmQuickCardFileSelection()">Confirm</button>
                <button type="button" class="picker-cancel-btn" onclick="closeQuickCardFilePicker()">Cancel</button>
            </div>
        </div>
    </div>

</div>
</main>
<footer class="footer-content">
    <div class="footer-container">
        <p class="footer-text">
            Copyright © 2024 |
            <a class="footer-link" href="#">JRC.inc.</a>
            All rights reserved
        </p>
    </div>
</footer>
</body>
</html>
