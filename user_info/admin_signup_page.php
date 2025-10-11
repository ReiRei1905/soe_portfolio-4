<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup</title>
    <link rel="stylesheet" href="style_admin_signup_page.css">
    <script src="admin_pop-up.js" defer></script>
</head>
<body>
    <div class="signup-container">
        <a href="role_page.html" class="back-arrow">&#x2190;</a> <!-- Update the href to point to the role page -->
        <h1>SOE E-PORTFOLIO SIGNING-UP</h1>
        <form id="admin-signup-form" action="admin_signup_register.php" method="POST"> <!-- Set action to your PHP script -->
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
                    <label for="id-number">ID Number (9 digits):</label>
                    <input type="text" id="id-number" name="id-number" placeholder="e.g YEAR-0000" required>
                </div>
            </div>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your school email address" required>

            <label for="password">Password: <span class="info-icon" title="Password Checking Policy:&#10;- Minimum of 12 characters&#10;Contains:&#10;- 1 Uppercase, Lowercase, Number, Special character, and EXCEPT exclamation point.">&#9432;</span></label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <div class="form-group">
                <label for="suffix">Suffix (Optional):</label>
                <input type="text" id="suffix" name="suffix" placeholder="e.g. Jr., Sr., III">
            </div>

            <button type="submit">Confirm</button> <!-- Change to submit button -->
        </form>
        <a href="login_page.php" class="login-link">Already have an account? Log-in</a> <!-- Update the href to point to the login page -->
    </div>

    <div id="popup" class="popup">
        <div class="popup-content">
            <p>Note: Choosing your Role as an Admin will make you grant access permission for the user roles, oversee the system and address user queries. (This cannot be changed easily, please recheck your credentials)</p>
            <button onclick="closePopup()">Back to Sign-in</button>
            <button onclick="submitForm()">Confirm</button>
        </div>
    </div>

    <div id="confirmation-popup" class="popup">
        <div class="popup-content">
            <img src="images/administrator.png" alt="Confirmation Icon">
            <p>Your account is pending approval by the owner of this system. You will receive an email once your account is approved.</p>
            <button onclick="redirectToLogin()">Back to Log-in</button>
            <button onclick="redirectToSignup()">Back to Sign-Up</button>
        </div>
    </div>
    
</body>
</html>