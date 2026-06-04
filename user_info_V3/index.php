<?php
session_start();

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$signInErrorMessage = '';
if ($flashMessage !== '') {
    $normalized = strtolower(trim((string) $flashMessage));
    if (
        str_contains($normalized, 'invalid email or password') ||
        str_contains($normalized, 'email and password are required')
    ) {
        $signInErrorMessage = 'You entered a wrong password!, Please try again carefully:D';
    }
}
?>
<!-- filepath: \\192.168.80.4\sambashare\soe_portfolio\user_info_V2 - For Debugging\index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.5">
    <title>SOE Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Link to external CSS file for styling -->
</head>
<body>

    <!-- Student Sign-Up -->
    <!--harhar-->
    <div class="container" id="studentSignUp" style="display: none;">
        <h1 class="form-title">Student Sign-Up</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" placeholder="First Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="mName" placeholder="Middle Name (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" placeholder="Last Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="suffix" placeholder="Suffix (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-calendar"></i>
                <input type="number" name="year_of_enrollment" placeholder="Year of Enrollment (e.g. 2024)" required>
            </div>
            <div class="input-group">
                <i class="fas fa-graduation-cap"></i>
                <select name="program_id" required>
                    <option value="" disabled selected>Select Program Enrolled</option>
                    <?php
                    include 'connect.php'; // Ensure the database connection is included
                    $query = "SELECT program_id, program_name FROM programs";
                    $result = $conn->query($query);

                    if ($result instanceof mysqli_result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['program_id'] . "'>" . $row['program_name'] . "</option>";
                        }
                    } elseif ($result instanceof mysqli_result) {
                        echo "<option value='' disabled>No programs available</option>";
                    } else {
                        error_log('Failed to load programs: ' . $conn->error);
                        echo "<option value='' disabled>Programs unavailable</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="id_number" placeholder="ID Number (10-digits e.g. 2023-140265)" pattern="\d{10}" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group password-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="password-input" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" aria-hidden="true"></i>
                <div class="password-strength"></div>
            </div>
            <div class="input-group">
                <label for="role_type">Sign-Up as:</label>
                <select id="role_type" name="role_type" required>
                    <option value="student" <?= isset($role) && $role === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="faculty" <?= isset($role) && $role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="admin" <?= isset($role) && $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="signup-submit-wrap">
                <input type="submit" class="btn" value="Sign-Up" name="signUp">
                <span class="signup-loading-spinner" aria-hidden="true"></span>
            </div>
            <div class="back-button-container">
                <button class="back-button">Back</button>
            </div>
        </form>
    </div>

    <!--hatdogggg -->
    <!-- Faculty Sign-Up -->
    <div class="container" id="facultySignUp" style="display: none;">
        <h1 class="form-title">Faculty Sign-Up</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" placeholder="First Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="mName" placeholder="Middle Name (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" placeholder="Last Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="suffix" placeholder="Suffix (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-graduation-cap"></i>
                <select name="program_id" required>
                    <option value="" disabled selected>Select Program assigned as Faculty</option>
                    <?php
                    include 'connect.php'; // Ensure the database connection is included
                    $query = "SELECT program_id, program_name FROM programs";
                    $result = $conn->query($query);

                    if ($result instanceof mysqli_result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['program_id'] . "'>" . $row['program_name'] . "</option>";
                        }
                    } elseif ($result instanceof mysqli_result) {
                        echo "<option value='' disabled>No programs available</option>";
                    } else {
                        error_log('Failed to load programs: ' . $conn->error);
                        echo "<option value='' disabled>Programs unavailable</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="input-group">
                <i class="fas fa-user-tie"></i>
                <select name="faculty_role" required>
                    <option value="professor">Professor</option>
                    <option value="program director">Program Director</option>
                    <option value="executive director">Executive Director</option>
                </select>
            </div>
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="id_number" placeholder="ID Number (9-digits e.g. 2024-00000)" pattern="\d{9}" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group password-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="password-input" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" aria-hidden="true"></i>
                <div class="password-strength"></div>
            </div>
            <div class="input-group">
                <label for="role_type">Sign-Up as:</label>
                <select id="role_type" name="role_type" required>
                    <option value="student" <?= isset($role) && $role === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="faculty" <?= isset($role) && $role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="admin" <?= isset($role) && $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="signup-submit-wrap">
                <input type="submit" class="btn" value="Sign-Up" name="signUp">
                <span class="signup-loading-spinner" aria-hidden="true"></span>
            </div>
            <div class="back-button-container">
                <button class="back-button">Back</button>
            </div>
        </form>
    </div>

    <!-- Admin Sign-Up -->
    <div class="container" id="adminSignUp" style="display: none;">
        <h1 class="form-title">Admin Sign-Up</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fName" placeholder="First Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="mName" placeholder="Middle Name (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lName" placeholder="Last Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="suffix" placeholder="Suffix (Optional)">
            </div>
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="id_number" placeholder="ID Number (9-digits e.g. 2024-00000)" pattern="\d{9}" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group password-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="password-input" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" aria-hidden="true"></i>
                <div class="password-strength"></div>
            </div>
            <div class="input-group">
                <label for="role_type">Sign-Up as:</label>
                <select id="role_type" name="role_type" required>
                   <option value="student" <?= isset($role) && $role === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="faculty" <?= isset($role) && $role === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="admin" <?= isset($role) && $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <div class="signup-submit-wrap">
                <input type="submit" class="btn" value="Sign-Up" name="signUp">
                <span class="signup-loading-spinner" aria-hidden="true"></span>
            </div>
            <div class="back-button-container">
                <button class="back-button">Back</button>
            </div>
        </form>
    </div>

    <!-- Sign-In Form -->
    <?php
    $redirect = $_GET['redirect'] ?? '';
    $formAction = 'register.php';
    if ($redirect !== '') {
        $formAction .= '?redirect=' . urlencode($redirect);
    }
    ?>
    <div class="container" id="signIn">
        <h1 class="form-title">SOE-Portfolio Sign-In</h1>
        <form method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group password-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" class="password-input" placeholder="Password" required>
                <i class="fas fa-eye toggle-password" aria-hidden="true"></i>
                
            </div>
            <?php if ($signInErrorMessage !== ''): ?>
                <p id="signInErrorMessage" class="sign-in-error-message"><?php echo htmlspecialchars($signInErrorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p class="recover">
                <a href="recover_psw.php">Forgot Password?</a>
            </p>
            <input type="submit" class="btn" value="Sign-In" name="signIn">
            <div class="links">
                <p>Don't have an account yet?</p>
                <button id="signUpButton">Sign Up</button>
                <a href="data_privacy.html" target="_blank">APC Data Privacy Policy</a>
            </div>
        </form>
    </div>
    <script src="script.js"></script>
        <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if the URL has ?showSignup=1
        if (window.location.search.includes('showSignup=1')) {
            document.getElementById('signIn').style.display = 'none';
            document.getElementById('studentSignUp').style.display = 'block';
            // Optionally hide other sign-up forms
            if(document.getElementById('facultySignUp')) document.getElementById('facultySignUp').style.display = 'none';
            if(document.getElementById('adminSignUp')) document.getElementById('adminSignUp').style.display = 'none';
        }
    });
    </script>
    <?php if ($flashMessage !== '' && strpos($flashMessage, 'APC email addresses') !== false): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            alert(<?php echo json_encode($flashMessage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        });
        </script>
    <?php endif; ?>
</body>
</html>