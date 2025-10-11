<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Link to external CSS file for styling -->
</head>
<body>
    <!-- This is the main container for the sign-up 
    <div class="container" id = signUp style="display: none;">
        <h1 class = "form-title"> Register </h1>
        <form method="post" action ="register.php">
            <div class="input-group">
                <i class = "fas fa-user"></i>
                <input type = "text" name = "fName" placeholder = "First Name" required> <!-- required attribute makes the field mandatory 
                <label for = "fName"></label>  label for accessibility, associates the label with the input field 
            </div>

            <div class="input-group">
                <i class = "fas fa-user"></i>
                <input type = "text" name = "lName" placeholder = "Last Name" required> <!-- required attribute makes the field mandatory 
                <label for = "lName"></label>  label for accessibility, associates the label with the input field 
            </div>

            <div class="input-group">
                <i class = "fas fa-envelope"></i>
                <input type = "email" name = "email" placeholder = "Email" required> <!-- required attribute makes the field mandatory 
                <label for = "email"></label>  label for accessibility, associates the label with the input field --
            </div>

            <div class="input-group">
                <i class = "fas fa-lock"></i>
                <input type = "password" name = "password" placeholder = "Password" required> <!-- required attribute makes the field mandatory 
                <label for = "password"></label>  label for accessibility, associates the label with the input field 
            </div>

            <select name="role_type" required>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
            <option value="admin">Admin</option>
            </select>


            <input type = "submit" class = "btn" value = "Sign-Up" name = "signUp"> <!-- submit button to send the form data 

            <div class = "links">
                <p>Already have an account?</p> prompt for users who already have an account 
                <button id = "signInButton">Sign In</button>
            </div>
        </form>
    </div>
     End of the main container for the sign-up -->


     <!-- Buttons to switch between forms -
    <div class="form-switcher">
        <button id="showStudent">Student Sign-Up</button>
        <button id="showFaculty">Faculty Sign-Up</button>
        <button id="showAdmin">Admin Sign-Up</button>
    </div> -->

     <!-- Student Sign-Up -->
     <div class="container" id= "studentSignUp" style="display: none;">
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
                
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['program_id'] . "'>" . $row['program_name'] . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No programs available</option>";
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
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <p>Sign-Up as:
                <select name="role_type" id="role_type" required>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="admin">Admin</option>
                </select>
            </p>
            
            <input type="submit" class="btn" value="Sign-Up" name="signUp">
        </form>
    </div>

    <!-- Faculty Sign-Up -->
    <div class="container" id="facultySignUp" style= "display: none;">
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
                
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['program_id'] . "'>" . $row['program_name'] . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No programs available</option>";
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
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <p>Sign-Up as:
                    <select name="role_type" id="role_type" required>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="admin">Admin</option>
                    </select>
            </p>
            <input type="submit" class="btn" value="Sign-Up" name="signUp">
        </form>
    </div>

    <!-- Admin Sign-Up -->
    <div class="container" id="adminSignUp" style= "display: none;">
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
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <p>Sign-Up as:
                    <select name="role_type" id="role_type" required>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="admin">Admin</option>
                    </select>
            </p>
            <input type="submit" class="btn" value="Sign-Up" name="signUp">
        </form>
    </div>

    <!-- Sign-In Form -->
    <div class="container" id = signIn>
        <h1 class = "form-title"> SOE-portfolio Sign-In </h1>
        <form method="post" action ="register.php">
            <div class="input-group">
                <i class = "fas fa-envelope"></i>
                <input type = "email" name = "email" placeholder = "Email" required> <!-- required attribute makes the field mandatory -->
                <label for = "email"></label> <!-- label for accessibility, associates the label with the input field -->
            </div>

            <div class="input-group">
                <i class = "fas fa-lock"></i>
                <input type = "password" name = "password" placeholder = "Password" required> <!-- required attribute makes the field mandatory -->
                <label for = "password"></label> <!-- label for accessibility, associates the label with the input field -->
            </div>

            <p class = "recover">
                <a href = "#">Forgot Password?</a> <!-- link for password recovery -->
            </p>

            <input type = "submit" class = "btn" value = "Sign-In" name = "signIn"> <!-- submit button to send the form data -->

            <div class = "links">
                <p>Don't have an account yet?</p> <!-- prompt for users who already have an account -->
                <button id ="signUpButton">Sign Up</button>
            </div>
        </form>
    </div>
    <script src="script.js"></script> <!-- Link to external JavaScript file for functionality -->
</body>
</html>