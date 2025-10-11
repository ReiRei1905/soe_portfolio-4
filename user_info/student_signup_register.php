<?php
include("student_signup_connect.php"); // Include the correct database connection file

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first-name'];
    $middle_name = $_POST['middle-name'];
    $last_name = $_POST['last-name'];
    $year_enrollment = $_POST['year-enrollment']; // Added year of enrollment
    $program = $_POST['program']; // Added program
    $id_number = $_POST['id-number'];
    $user_email = $_POST['email'];
    $user_password = $_POST['password']; // Get the password from the form
    $hashed_password = password_hash($user_password, PASSWORD_DEFAULT); // Hash the password
    $suffix = $_POST['suffix'];

    // Check if the email already exists
    $check_email = "SELECT * FROM users WHERE user_email = ?"; // Use the correct table name
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "Email already exists";
    } else {
        // Insert data into database
        $sql = "INSERT INTO users (first_name, middle_name, last_name, year_enrollment, program, id_number, user_email, user_password, suffix) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->query($sql);
        $stmt->bind_param("sssssssss", $first_name, $middle_name, $last_name, $year_enrollment, $program, $id_number, $user_email, $hashed_password, $suffix);
        
        if ($stmt->execute()) {
            header("Location: login_page.php"); // Redirect to login page on success
            exit();
        } else {
            echo "Error: " . $stmt->error; // Display error if the query fails
        }
    }

    // Close the statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>