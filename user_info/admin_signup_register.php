<?
include("admin_signup_connect.php");

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first-name'];
    $middle_name = $_POST['middle-name'];
    $last_name = $_POST['last-name'];
    $id_number = $_POST['id-number'];
    $user_email = $_POST['email'];
    $user_password = $_POST['password']; // You should hash this password before storing it
    $user_password =md5($password);
    $suffix = $_POST['suffix'];

    $check_email = "SELECT * FROM user WHERE email = '$email'";
    $result = $conn->query($check_email);
    if ($result->num_rows > 0) {
        echo "Email already exists";
    } else {
        // Insert data into database
        $sql = "INSERT INTO user (first_name, middle_name, last_name, id_number, user_email, user_password, suffix) VALUES ('$first_name', '$middle_name', '$last_name', '$id_number', '$email', '$password', '$suffix')";
        if ($conn->query($sql) === TRUE) {
            header("Location: login_page.php");
        } else {
            echo "Error: ".$conn->error;
        }
    }
}
?>