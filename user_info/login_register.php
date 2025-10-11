<?php 
include 'login_connect.php'; // Ensure this file contains the database connection

if(isset($_POST['email']) && isset($_POST['password'])){
    $user_email = $_POST['email'];
    $user_password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
        $_SESSION['password'] = $row['password']; 
        
        // Redirect to the About Us page
        header("Location: about_us.html"); // Change to your About Us page
        exit();
    } else {
        echo "Login Failed";
    }
}
?>