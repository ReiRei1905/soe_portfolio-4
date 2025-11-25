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
<body class="page-layout" data-student-name="<?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?>">
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
                    <div id="notificationDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
                        <p class="text-gray-500 text-center">No new notifications at the moment.</p>
                    </div>
                </div>

                <div class="relative header-upload">
                    <i class="fas fa-cloud-upload-alt text-lg cursor-pointer" onclick="toggleDropdown(this)" aria-haspopup="true" aria-expanded="false"></i>
                    <div id="uploadDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
                        <div style="display:flex;gap:0.5rem;">
                            <button class="btn-upload" type="button">Add Custom Folder</button>
                            <button class="btn-upload" type="button">Use Existing Folder</button>
                        </div>
                        <div style="margin-top:0.5rem;">
                            <label style="font-weight:600;">Select Category:</label>
                            <div style="margin-top:0.25rem;">
                                <label style="display:block;"><input type="radio" name="category" value="assessment" class="mr-1"> Assessment</label>
                                <label style="display:block;"><input type="radio" name="category" value="projects" class="mr-1"> Projects</label>
                                <label style="display:block;"><input type="radio" name="category" value="certificates" class="mr-1"> Certificate &amp; Awards</label>
                            </div>
                        </div>
                        <div style="display:flex;gap:0.5rem;margin-top:0.5rem;">
                            <button class="btn-cancel" type="button" onclick="closeUploadModal()">Cancel</button>
                            <button class="btn-done" type="button" onclick="addFile()">Done</button>
                        </div>
                    </div>
                </div>

                <div class="relative header-profile-menu">
                    <div class="w-10 h-10 profile-avatar-btn" onclick="toggleDropdown(this)" role="button" tabindex="0" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user text-xl"></i>
                    </div>
                    <div id="profileDropdown" class="dropdown hidden" role="menu" aria-hidden="true">
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
            <a class="text-lg" href="./assessments.html">Assessments</a>
            <a class="text-lg" href="../projects.html">Projects</a>
            <a class="text-lg" href="../certificates/awards.html">Certificates/Awards</a>
            <a class="text-lg" href="../classes.html">Classes</a>
            <a class="text-lg" href="../faculty_about_us.html">About us</a>
        </nav>
    </div>

<div id="homepage" class="homepage-section">

    <div class="profile-wrapper">
        <section class="profile-card" aria-label="student profile">
            <div class="profile-info">
                <!-- Server-rendered student name -->
                <h2 id="studentName" class="profile-name"><?php echo htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="profile-bio">About the user</p>
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
    </div>

    <section class="quick-access" aria-label="quick access">
        <div class="quick-access-grid">
            <button onclick="openFileManagement('fileManagementModal','projects')" class="quick-card" type="button">
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top projects</h3>
            </button>

            <button onclick="openFileManagement('fileManagementModal','certificates')" class="quick-card" type="button">
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top certificates/awards</h3>
            </button>

            <button onclick="openFileManagement('fileManagementModal','assessments')" class="quick-card" type="button">
                <i class="fas fa-folder folder-fa" aria-hidden="true"></i>
                <h3 class="quick-title">Top assessments</h3>
            </button>
        </div>
    </section>

    <div id="fileManagementModal" class="modal hidden file-management" role="dialog" aria-modal="true" aria-labelledby="fileManagementTitle">
        <div class="modal-content">
            <button class="modal-close" onclick="closeFileManagement()">&times;</button>
            <h2 id="fileManagementTitle">Manage Files</h2>
            <div id="fileTileContainer" class="file-tile-container">
                <div class="file-tile"><a class="file-link" href="#">Sample File 1</a></div>
                <div class="file-tile"><a class="file-link" href="#">Sample File 2</a></div>
                <div class="file-tile"><a class="file-link" href="#">Sample File 3</a></div>
                <div class="file-tile"><a class="file-link" href="#">Sample File 4</a></div>
            </div>
            <div style="margin-top:1rem;">
                <button onclick="closeFileManagement()" class="modal-action-close">Close</button>
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
