<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOE-Portfolio Faculty Signing Up</title>
    <link rel="stylesheet" href="style_faculty_signup_page.css">
</head>
<body>
    <div class="container">
        <a href="role_page.html" class="back-arrow">&#x2190;</a>
        <h1>SOE-PORTFOLIO SIGNING UP</h1>
        <form action="faculty_signup_register.php" method="POST"> <!-- Set action to your PHP script -->
            <div class="form-row">
                <div class="form-group">
                    <label for="first-name">First Name:</label>
                    <input type="text" id="first-name" name="first-name" placeholder="Enter your First Name" required>
                </div>
                <div class="form-group">
                    <label for="middle-name">Middle Name:</label>
                    <input type="text" id="middle-name" name="middle-name" placeholder="Enter your Middle Name">
                </div>
                <div class="form-group">
                    <label for="last-name">Last Name:</label>
                    <input type="text" id="last-name" name="last-name" placeholder="Enter your Last Name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="program">Program:</label>
                    <select id="program" name="program" required>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Electronics Engineering">Electronics Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="" selected disabled>Select your role</option>
                        <option value="professor">Professor</option>
                        <option value="program_director">Program Director</option>
                        <option value="executive_director">Executive Director</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id-number">ID Number (9 digits):</label>
                    <input type="text" id="id-number" name="id-number" placeholder="e.g YEAR-0000 Id number" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" id="email-form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Enter your school email address" required>
                </div>
                <div class="form-group">
                    <label for="password" class="password-label">Password: <span class="info-icon" title="Password Checking Policy:&#10;- Minimum of 12 characters&#10;Contains:&#10;- 1 Uppercase, Lowercase, Number, Special character, and EXCEPT exclamation point.">&#9432;</span></label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="suffix">Suffix (Optional):</label>
                    <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr., Sr., III">
                </div>
            </div>
            <div class="form-footer">
                <a href="login_page.html" class="login-link">Already have an account? Log-in</a>
                <button type="submit" class="confirm-button">Confirm</button>
            </div>
        </form>
    </div>
    <div id="popup-overlay" class="popup-overlay"></div>
    <div id="popup-message" class="popup-message">
        <p id="popup-text"></p>
        <button onclick="closePopup()">Back to Sign-in</button>
        <button onclick="closePopup()">Confirm</button>
    </div>

    <div id="confirmation-popup" class="popup">
        <div class="popup-content">
            <img src="images/administrator.png" alt="Confirmation Icon">
            <p>Your account is pending approval by the admin of this system. You will receive an email once your account is approved.</p>
            <button onclick="redirectToLogin()">Back to Log-in</button>
            <button onclick="redirectToSignup()">Back to Sign-Up</button>
        </div>
    </div>

    <script src="faculty_pop-up.js"></script>
    <script src="faculty_confirmation_pop-up.js"></script>
</body>
</html>